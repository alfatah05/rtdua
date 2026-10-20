<?php

namespace App\Services;

/**
 * Kas masuk/keluar.
 * T2: pembayaran iuran tidak lagi otomatis mengisi kas_transaksi (lihat SubPosService).
 * Kategori tetap: Saldo awal, Pengembalian kelebihan,
 * otomatis Iuran kas / Iuran khusus / Denda ronda (historis), Alokasi antar pos.
 * Alokasi antar pos: 2 baris DB (keluar+masuk) agar saldo per-pos benar,
 * tapi di list ringkas hanya 1 baris (bukan pengeluaran).
 * Multi-pos: kolom kas_pos_id (opsional, setelah migrasi 025).
 */
class KasService
{
    public const KAT_SALDO_AWAL = 'Saldo awal';
    public const KAT_PENGEMBALIAN = 'Pengembalian kelebihan';
    public const KAT_IURAN_KAS = 'Iuran kas';
    public const KAT_IURAN_KHUSUS = 'Iuran khusus';
    public const KAT_DENDA = 'Denda ronda';
    public const KAT_ALOKASI = 'Alokasi antar pos';

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

    public function totalMasukKeluarTanpaAlokasi(?string $bulan = null): array
    {
        $db = \Config\Database::connect();
        $masukQ = $db->table('kas_transaksi')
            ->selectSum('nominal')
            ->where('tipe', 'masuk')
            ->where('dibatalkan', 0)
            ->where('kategori !=', self::KAT_ALOKASI);
        $keluarQ = $db->table('kas_transaksi')
            ->selectSum('nominal')
            ->where('tipe', 'keluar')
            ->where('dibatalkan', 0)
            ->where('kategori !=', self::KAT_ALOKASI);
        if ($bulan) {
            $masukQ->like('tanggal', $bulan, 'after');
            $keluarQ->like('tanggal', $bulan, 'after');
        }
        return [
            'masuk'  => (int) ($masukQ->get()->getRow()->nominal ?? 0),
            'keluar' => (int) ($keluarQ->get()->getRow()->nominal ?? 0),
        ];
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
        if (!empty($filter['tanpa_otomatis'])) {
            $q->groupStart()
                ->where('kt.pembayaran_id', null)
                ->orWhere('kt.pembayaran_id', 0)
                ->groupEnd();
        }
        $rows = $q->orderBy('kt.tanggal', 'DESC')->orderBy('kt.id', 'DESC')->get()->getResultArray();

        $ringkasAlokasi = empty($filter['kas_pos_id']) && empty($filter['tanpa_ringkas_alokasi']);

        $out = [];
        foreach ($rows as $r) {
            $isAlokasi = ($r['kategori'] ?? '') === self::KAT_ALOKASI;
            if ($ringkasAlokasi && $isAlokasi && ($r['tipe'] ?? '') === 'masuk') {
                continue;
            }
            $item = [
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
                'is_alokasi'    => $isAlokasi,
            ];
            if ($isAlokasi) {
                $item['display_tipe'] = 'alokasi';
            } else {
                $item['display_tipe'] = $r['tipe'];
            }
            $out[] = $item;
        }
        return $out;
    }

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
        if (in_array($kategori, [self::KAT_IURAN_KAS, self::KAT_IURAN_KHUSUS, self::KAT_DENDA, self::KAT_ALOKASI], true)) {
            return ['ok' => false, 'message' => 'Kategori khusus hanya dari sistem (pembayaran / alokasi).'];
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

    public function alokasiAntarPos(array $data, int $userId): array
    {
        if (!$this->hasKasPosColumn()) {
            return ['ok' => false, 'message' => 'Kolom kas_pos_id belum ada. Jalankan migrasi 025.'];
        }

        $dariId = (int) ($data['dari_pos_id'] ?? 0);
        $keId = (int) ($data['ke_pos_id'] ?? 0);
        $nominal = (int) ($data['nominal'] ?? 0);
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $ketExtra = trim((string) ($data['keterangan'] ?? ''));

        if ($dariId < 1 || $keId < 1) {
            return ['ok' => false, 'message' => 'Pos sumber dan tujuan wajib dipilih.'];
        }
        if ($dariId === $keId) {
            return ['ok' => false, 'message' => 'Pos sumber dan tujuan harus berbeda.'];
        }
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal wajib lebih dari 0.'];
        }

        $posSvc = new KasPosService();
        $db = \Config\Database::connect();
        $dari = $db->table('kas_pos')->where('id', $dariId)->get()->getRowArray();
        $ke = $db->table('kas_pos')->where('id', $keId)->get()->getRowArray();
        if (!$dari || !$ke) {
            return ['ok' => false, 'message' => 'Pos tidak ditemukan.'];
        }
        if ((int) $dari['aktif'] !== 1 || (int) $ke['aktif'] !== 1) {
            return ['ok' => false, 'message' => 'Pos sumber dan tujuan harus aktif.'];
        }

        $saldoDari = $posSvc->saldoPos($dariId);
        if ($saldoDari < $nominal) {
            return [
                'ok'      => false,
                'message' => 'Saldo "' . $dari['nama'] . '" tidak cukup (tersedia ' . number_format($saldoDari, 0, ',', '.') . ').',
            ];
        }

        $namaDari = $dari['nama'];
        $namaKe = $ke['nama'];
        $ketDasar = 'Alokasi: ' . $namaDari . ' → ' . $namaKe;
        if ($ketExtra !== '') {
            $ketDasar .= ' — ' . $ketExtra;
        }

        $now = date('Y-m-d H:i:s');
        $base = [
            'nominal'       => $nominal,
            'kategori'      => self::KAT_ALOKASI,
            'metode'        => 'tunai',
            'keterangan'    => $ketDasar,
            'tanggal'       => $tanggal,
            'keluarga_id'   => null,
            'pembayaran_id' => null,
            'dibatalkan'    => 0,
            'dicatat_oleh'  => $userId,
            'dicatat_pada'  => $now,
        ];

        $db->transStart();

        $rowKeluar = $base;
        $rowKeluar['tipe'] = 'keluar';
        $rowKeluar['kas_pos_id'] = $dariId;
        $db->table('kas_transaksi')->insert($rowKeluar);
        $idKeluar = (int) $db->insertID();

        $rowMasuk = $base;
        $rowMasuk['tipe'] = 'masuk';
        $rowMasuk['kas_pos_id'] = $keId;
        $db->table('kas_transaksi')->insert($rowMasuk);
        $idMasuk = (int) $db->insertID();

        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['ok' => false, 'message' => 'Gagal menyimpan alokasi.'];
        }

        (new AuditService())->log(
            'kas_alokasi_pos',
            'kas_transaksi',
            $idKeluar,
            null,
            [
                'dari_pos_id'   => $dariId,
                'dari_pos_nama' => $namaDari,
                'ke_pos_id'     => $keId,
                'ke_pos_nama'   => $namaKe,
                'nominal'       => $nominal,
                'tanggal'       => $tanggal,
                'keterangan'    => $ketExtra !== '' ? $ketExtra : null,
                'id_keluar'     => $idKeluar,
                'id_masuk'      => $idMasuk,
            ],
            'pengguna',
            true,
            $userId
        );

        return [
            'ok'   => true,
            'data' => [
                'id_keluar' => $idKeluar,
                'id_masuk'  => $idMasuk,
                'nominal'   => $nominal,
                'dari'      => ['id' => $dariId, 'nama' => $namaDari, 'saldo' => $posSvc->saldoPos($dariId)],
                'ke'        => ['id' => $keId, 'nama' => $namaKe, 'saldo' => $posSvc->saldoPos($keId)],
                'saldo'     => $this->saldo(),
            ],
        ];
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
        if (($row['kategori'] ?? '') === self::KAT_ALOKASI) {
            $pairQ = $db->table('kas_transaksi')
                ->where('kategori', self::KAT_ALOKASI)
                ->where('dibatalkan', 0)
                ->where('nominal', (int) $row['nominal'])
                ->where('tanggal', $row['tanggal'])
                ->where('keterangan', $row['keterangan'])
                ->where('dicatat_pada', $row['dicatat_pada']);
            $pairQ->update([
                'dibatalkan'      => 1,
                'dibatalkan_oleh' => $userId,
                'dibatalkan_pada' => date('Y-m-d H:i:s'),
                'alasan_batal'    => $alasan,
            ]);
            (new AuditService())->log('batal_kas_alokasi', 'kas_transaksi', $id, $row, ['alasan' => $alasan], 'pengguna', true, $userId);
            return ['ok' => true, 'data' => ['saldo' => $this->saldo()]];
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
     * DEPRECATED T2: pembayaran iuran tidak lagi mengisi kas_transaksi.
     * Mutasi masuk ke SubPosService::masukDariPembayaran.
     *
     * @param list<array{jenis:string, jumlah:int}> $potongan
     */
    public function masukDariPembayaran(int $pembayaranId, int $keluargaId, string $metode, string $tanggal, array $potongan, int $sisaBayar, int $userId): void
    {
        // T2: iuran masuk sub pos, bukan kas.
        return;
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

    public function portalRingkasanKas(): array
    {
        $posSvc = new KasPosService();
        $pos = $posSvc->list(true);
        $items = $this->list(['tanpa_otomatis' => true]);
        $items = array_slice($items, 0, 80);

        $db = \Config\Database::connect();
        $payRows = $db->table('pembayaran p')
            ->select('p.id, p.tanggal_bayar, p.nominal, p.metode, p.catatan, p.keluarga_id')
            ->where('p.dibatalkan', 0)
            ->orderBy('p.tanggal_bayar', 'DESC')
            ->orderBy('p.id', 'DESC')
            ->limit(80)
            ->get()
            ->getResultArray();

        $kepalaMap = [];
        $keluargaIds = array_values(array_unique(array_filter(array_map(
            static fn ($r) => (int) ($r['keluarga_id'] ?? 0),
            $payRows
        ))));
        if ($keluargaIds !== []) {
            $wRows = $db->table('warga')
                ->select('keluarga_id, nama')
                ->whereIn('keluarga_id', $keluargaIds)
                ->where('status', 'aktif')
                ->groupStart()
                    ->where('hubungan', 'Kepala keluarga')
                    ->orWhere('hubungan', 'Kepala Keluarga')
                ->groupEnd()
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($wRows as $w) {
                $kid = (int) $w['keluarga_id'];
                if (!isset($kepalaMap[$kid])) {
                    $kepalaMap[$kid] = $w['nama'];
                }
            }
            $missing = array_diff($keluargaIds, array_keys($kepalaMap));
            if ($missing !== []) {
                $fb = $db->table('warga')
                    ->select('keluarga_id, nama')
                    ->whereIn('keluarga_id', $missing)
                    ->where('status', 'aktif')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();
                foreach ($fb as $w) {
                    $kid = (int) $w['keluarga_id'];
                    if (!isset($kepalaMap[$kid])) {
                        $kepalaMap[$kid] = $w['nama'];
                    }
                }
            }
        }

        $pembayaran = array_map(static function ($r) use ($kepalaMap) {
            $kid = (int) ($r['keluarga_id'] ?? 0);
            $nama = $kepalaMap[$kid] ?? 'Warga';
            return [
                'id'          => (int) $r['id'],
                'tanggal'     => $r['tanggal_bayar'],
                'nominal'     => (int) $r['nominal'],
                'metode'      => $r['metode'],
                'catatan'     => $r['catatan'],
                'nama_kepala' => $nama,
            ];
        }, $payRows);

        return [
            'saldo'      => $this->saldo(),
            'pos'        => $pos,
            'items'      => $items,
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
