<?php

namespace App\Services;

/**
 * Kas masuk/keluar.
 * Kategori tetap: Saldo awal, Pengembalian kelebihan,
 * otomatis Iuran kas / Iuran khusus / Denda ronda.
 * Selain itu keterangan bebas (pengurus mengisi).
 */
class KasService
{
    public const KAT_SALDO_AWAL = 'Saldo awal';
    public const KAT_PENGEMBALIAN = 'Pengembalian kelebihan';
    public const KAT_IURAN_KAS = 'Iuran kas';
    public const KAT_IURAN_KHUSUS = 'Iuran khusus';
    public const KAT_DENDA = 'Denda ronda';

    public function saldo(): int
    {
        $db = \Config\Database::connect();
        $masuk = (int) $db->table('kas_transaksi')
            ->selectSum('nominal')
            ->where('tipe', 'masuk')
            ->where('dibatalkan', 0)
            ->get()
            ->getRow()
            ->nominal;
        $keluar = (int) $db->table('kas_transaksi')
            ->selectSum('nominal')
            ->where('tipe', 'keluar')
            ->where('dibatalkan', 0)
            ->get()
            ->getRow()
            ->nominal;
        return $masuk - $keluar;
    }

    public function list(array $filter = []): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('kas_transaksi')->where('dibatalkan', 0);
        if (!empty($filter['bulan'])) {
            // YYYY-MM
            $q->like('tanggal', $filter['bulan'], 'after');
        }
        if (!empty($filter['tipe']) && in_array($filter['tipe'], ['masuk', 'keluar'], true)) {
            $q->where('tipe', $filter['tipe']);
        }
        if (!empty($filter['kategori'])) {
            $q->where('kategori', $filter['kategori']);
        }
        $rows = $q->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        return array_map(static function ($r) {
            return [
                'id'            => (int) $r['id'],
                'tipe'          => $r['tipe'],
                'nominal'       => (int) $r['nominal'],
                'kategori'      => $r['kategori'],
                'metode'        => $r['metode'],
                'keterangan'    => $r['keterangan'],
                'tanggal'       => $r['tanggal'],
                'keluarga_id'   => $r['keluarga_id'] ? (int) $r['keluarga_id'] : null,
                'pembayaran_id' => $r['pembayaran_id'] ? (int) $r['pembayaran_id'] : null,
                'otomatis'      => !empty($r['pembayaran_id']),
            ];
        }, $rows);
    }

    /**
     * Catat kas manual (bukan dari iuran).
     */
    public function tambah(array $data, int $userId): array
    {
        $tipe = ($data['tipe'] ?? '') === 'keluar' ? 'keluar' : 'masuk';
        $nominal = (int) ($data['nominal'] ?? 0);
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal wajib lebih dari 0.'];
        }
        $kategori = trim((string) ($data['kategori'] ?? ''));
        if ($kategori === '') {
            $kategori = $tipe === 'masuk' ? self::KAT_SALDO_AWAL : 'Lainnya';
        }
        // Kas otomatis iuran tidak boleh dicatat manual dengan kategori iuran
        if (in_array($kategori, [self::KAT_IURAN_KAS, self::KAT_IURAN_KHUSUS, self::KAT_DENDA], true)) {
            return ['ok' => false, 'message' => 'Kategori iuran hanya dari pembayaran otomatis.'];
        }

        $db = \Config\Database::connect();
        $db->table('kas_transaksi')->insert([
            'tipe'          => $tipe,
            'nominal'       => $nominal,
            'kategori'      => $kategori,
            'metode'        => in_array($data['metode'] ?? '', ['tunai', 'transfer'], true) ? $data['metode'] : 'tunai',
            'keterangan'    => $data['keterangan'] ?? null,
            'tanggal'       => $data['tanggal'] ?? date('Y-m-d'),
            'keluarga_id'   => !empty($data['keluarga_id']) ? (int) $data['keluarga_id'] : null,
            'pembayaran_id' => null,
            'dibatalkan'    => 0,
            'dicatat_oleh'  => $userId,
            'dicatat_pada'  => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('kas_' . $tipe, 'kas_transaksi', $id, null, [
            'nominal'  => $nominal,
            'kategori' => $kategori,
        ], 'pengguna', true, $userId);

        return ['ok' => true, 'data' => ['id' => $id, 'saldo' => $this->saldo()]];
    }

    public function batalkan(int $id, string $alasan, int $userId): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            return ['ok' => false, 'message' => 'Alasan wajib.'];
        }
        $db = \Config\Database::connect();
        $row = $db->table('kas_transaksi')->where('id', $id)->get()->getRowArray();
        if (!$row || (int) $row['dibatalkan'] === 1) {
            return ['ok' => false, 'message' => 'Transaksi tidak ditemukan.'];
        }
        if (!empty($row['pembayaran_id'])) {
            return ['ok' => false, 'message' => 'Kas dari iuran hanya batal bersama pembatalan pembayaran.'];
        }
        $db->table('kas_transaksi')->where('id', $id)->update([
            'dibatalkan'     => 1,
            'dibatalkan_oleh'=> $userId,
            'dibatalkan_pada'=> date('Y-m-d H:i:s'),
            'alasan_batal'   => $alasan,
        ]);
        (new AuditService())->log('batal_kas', 'kas_transaksi', $id, $row, ['alasan' => $alasan], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['saldo' => $this->saldo()]];
    }

    /**
     * Kas masuk otomatis dari pembayaran iuran (dipanggil dalam transaksi pembayaran).
     * Pecah per jenis sesuai alokasi.
     *
     * @param list<array{jenis:string, jumlah:int}> $potongan
     * @param int $sisaBayar kelebihan (masuk kategori Iuran kas sebagai sisa)
     */
    public function masukDariPembayaran(int $pembayaranId, int $keluargaId, string $metode, string $tanggal, array $potongan, int $sisaBayar, int $userId): void
    {
        $db = \Config\Database::connect();
        $map = [
            'kas'         => self::KAT_IURAN_KAS,
            'khusus'      => self::KAT_IURAN_KHUSUS,
            'denda_ronda' => self::KAT_DENDA,
        ];
        $agregat = [];
        foreach ($potongan as $p) {
            $kat = $map[$p['jenis']] ?? self::KAT_IURAN_KAS;
            $agregat[$kat] = ($agregat[$kat] ?? 0) + (int) $p['jumlah'];
        }
        if ($sisaBayar > 0) {
            // Kelebihan tetap masuk kas (sebagai Iuran kas) agar saldo cocok
            $agregat[self::KAT_IURAN_KAS] = ($agregat[self::KAT_IURAN_KAS] ?? 0) + $sisaBayar;
        }
        foreach ($agregat as $kategori => $nominal) {
            if ($nominal < 1) {
                continue;
            }
            $db->table('kas_transaksi')->insert([
                'tipe'          => 'masuk',
                'nominal'       => $nominal,
                'kategori'      => $kategori,
                'metode'        => $metode,
                'keterangan'    => 'Otomatis dari pembayaran #' . $pembayaranId,
                'tanggal'       => $tanggal,
                'keluarga_id'   => $keluargaId,
                'pembayaran_id' => $pembayaranId,
                'dibatalkan'    => 0,
                'dicatat_oleh'  => $userId,
                'dicatat_pada'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** Batalkan semua kas yang terikat pembayaran_id (saat batal bayar). */
    public function batalkanDariPembayaran(int $pembayaranId, int $userId): void
    {
        $db = \Config\Database::connect();
        $db->table('kas_transaksi')
            ->where('pembayaran_id', $pembayaranId)
            ->where('dibatalkan', 0)
            ->update([
                'dibatalkan'      => 1,
                'dibatalkan_oleh' => $userId,
                'dibatalkan_pada' => date('Y-m-d H:i:s'),
                'alasan_batal'    => 'Pembayaran dibatalkan',
            ]);
    }
}
