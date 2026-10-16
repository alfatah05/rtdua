<?php

namespace App\Services;

/**
 * Kas masuk/keluar.
 * Kategori tetap: Saldo awal, Pengembalian kelebihan,
 * otomatis Iuran kas / Iuran khusus / Denda ronda.
 * Selain itu keterangan bebas (pengurus mengisi).
 * Multi-pos: kolom kas_pos_id (opsional, setelah migrasi 025).
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
        $hasPos = $this->hasKasPosColumn();
        $q = $db->table('kas_transaksi kt')->where('kt.dibatalkan', 0);
        if ($hasPos) {
            $q->select('kt.*, kp.nama as kas_pos_nama')
                ->join('kas_pos kp', 'kp.id = kt.kas_pos_id', 'left');
        } else {
            $q->select('kt.*');
        }
        if (!empty($filter['bulan'])) {
            $q->like('kt.tanggal', $filter['bulan'], 'after');
        }
        if (!empty($filter['tipe']) && in_array($filter['tipe'], ['masuk', 'keluar'], true)) {
            $q->where('kt.tipe', $filter['tipe']);
        }
        if (!empty($filter['kategori'])) {
            $q->where('kt.kategori', $filter['kategori']);
        }
        if ($hasPos && !empty($filter['kas_pos_id'])) {
            $q->where('kt.kas_pos_id', (int) $filter['kas_pos_id']);
        }
        $rows = $q->orderBy('kt.tanggal', 'DESC')->orderBy('kt.id', 'DESC')->get()->getResultArray();
        return array_map(static function ($r) use ($hasPos) {
            return [
                'id'            => (int) $r['id'],
                'tipe'          => $r['tipe'],
                'nominal'       => (int) $r['nominal'],
                'kategori'      => $r['kategori'],
                'kas_pos_id'    => $hasPos && !empty($r['kas_pos_id']) ? (int) $r['kas_pos_id'] : null,
                'kas_pos_nama'  => $hasPos ? ($r['kas_pos_nama'] ?? null) : null,
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
        if (in_array($kategori, [self::KAT_IURAN_KAS, self::KAT_IURAN_KHUSUS, self::KAT_DENDA], true)) {
            return ['ok' => false, 'message' => 'Kategori iuran hanya dari pembayaran otomatis.'];
        }

        $posId = !empty($data['kas_pos_id']) ? (int) $data['kas_pos_id'] : null;
        if ($posId === null && $this->hasKasPosColumn()) {
            $posId = (new KasPosService())->defaultPosId();
        }

        $db = \Config\Database::connect();
        $row = [
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
        ];
        if ($this->hasKasPosColumn() && $posId) {
            $row['kas_pos_id'] = $posId;
        }
        $db->table('kas_transaksi')->insert($row);
        $id = (int) $db->insertID();
        (new AuditService())->log('kas_' . $tipe, 'kas_transaksi', $id, null, [
            'nominal'    => $nominal,
            'kategori'   => $kategori,
            'kas_pos_id' => $posId,
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
            'dibatalkan'      => 1,
            'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
            'alasan_batal'    => $alasan,
        ]);
        (new AuditService())->log('batal_kas', 'kas_transaksi', $id, $row, ['alasan' => $alasan], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['saldo' => $this->saldo()]];
    }

    /**
     * Kas masuk otomatis dari pembayaran iuran.
     *
     * @param list<array{jenis:string, jumlah:int}> $potongan
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
            $agregat[self::KAT_IURAN_KAS] = ($agregat[self::KAT_IURAN_KAS] ?? 0) + $sisaBayar;
        }
        $posId = $this->hasKasPosColumn() ? (new KasPosService())->defaultPosId() : null;
        foreach ($agregat as $kategori => $nominal) {
            if ($nominal < 1) {
                continue;
            }
            $row = [
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
            ];
            if ($posId) {
                $row['kas_pos_id'] = $posId;
            }
            $db->table('kas_transaksi')->insert($row);
        }
    }

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

    /**
     * Data portal warga: saldo, pos, items kas, pembayaran (riwayat iuran RT).
     */
    public function portalRingkasanKas(): array
    {
        $posSvc = new KasPosService();
        $pos = $posSvc->list(true);
        $items = $this->list();
        $items = array_slice($items, 0, 80);

        $db = \Config\Database::connect();
        $payRows = $db->table('pembayaran p')
            ->select('p.id, p.tanggal_bayar, p.nominal, p.metode, p.catatan, k.nomor, k.akhiran, b.nama as blok_nama')
            ->join('keluarga k', 'k.id = p.keluarga_id', 'left')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('p.dibatalkan', 0)
            ->orderBy('p.tanggal_bayar', 'DESC')
            ->orderBy('p.id', 'DESC')
            ->limit(80)
            ->get()
            ->getResultArray();

        $pembayaran = array_map(static function ($r) {
            $blok = $r['blok_nama'] ?? '';
            $nomor = $r['nomor'] ?? '';
            $akhir = $r['akhiran'] ?? '';
            $alamat = trim($blok . ($nomor !== '' ? '-' . $nomor : '') . $akhir);
            return [
                'id'       => (int) $r['id'],
                'tanggal'  => $r['tanggal_bayar'],
                'nominal'  => (int) $r['nominal'],
                'metode'   => $r['metode'],
                'catatan'  => $r['catatan'],
                'alamat'   => $alamat !== '' ? $alamat : '—',
            ];
        }, $payRows);

        return [
            'saldo'      => $this->saldo(),
            'pos'        => $pos,
            'items'      => array_map(static function ($r) {
                return [
                    'id'           => $r['id'],
                    'tipe'         => $r['tipe'],
                    'nominal'      => $r['nominal'],
                    'kategori'     => $r['kategori'],
                    'kas_pos_id'   => $r['kas_pos_id'],
                    'kas_pos_nama' => $r['kas_pos_nama'],
                    'keterangan'   => $r['keterangan'],
                    'tanggal'      => $r['tanggal'],
                    'otomatis'     => $r['otomatis'],
                ];
            }, $items),
            'pembayaran' => $pembayaran,
        ];
    }

    private function hasKasPosColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $db = \Config\Database::connect();
        try {
            $fields = $db->getFieldNames('kas_transaksi');
            $cached = in_array('kas_pos_id', $fields, true);
        } catch (\Throwable $e) {
            $cached = false;
        }
        return $cached;
    }
}
