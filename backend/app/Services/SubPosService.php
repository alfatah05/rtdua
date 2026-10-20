<?php

namespace App\Services;

/**
 * Sub pos iuran: buku saldo terpisah per komponen / judul khusus / denda.
 * T1: tabel + ensure + list.
 * T2: masukDariPembayaran + batalkanDariPembayaran.
 * T3: catatMasuk/Keluar, alokasiKePosKas, listMutasi, batalkanMutasi.
 */
class SubPosService
{
    public const JENIS_KOMPONEN = 'komponen';
    public const JENIS_KHUSUS   = 'khusus';
    public const JENIS_DENDA    = 'denda';
    public const KAT_SETORAN    = 'Setoran sub pos';

    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS `sub_pos` (\n          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n          `jenis` VARCHAR(16) NOT NULL,\n          `ref_id` INT UNSIGNED DEFAULT NULL,\n          `judul` VARCHAR(255) NOT NULL,\n          `aktif` TINYINT(1) NOT NULL DEFAULT 1,\n          `created_at` DATETIME NULL,\n          `updated_at` DATETIME NULL,\n          PRIMARY KEY (`id`),\n          KEY `idx_sub_pos_jenis_ref` (`jenis`, `ref_id`),\n          KEY `idx_sub_pos_jenis_judul` (`jenis`, `judul`)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `sub_pos_mutasi` (\n          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n          `sub_pos_id` INT UNSIGNED NOT NULL,\n          `tipe` VARCHAR(16) NOT NULL,\n          `nominal` INT UNSIGNED NOT NULL DEFAULT 0,\n          `tanggal` DATE NOT NULL,\n          `keterangan` VARCHAR(512) DEFAULT NULL,\n          `pembayaran_id` INT UNSIGNED DEFAULT NULL,\n          `kas_transaksi_id` INT UNSIGNED DEFAULT NULL,\n          `pos_kas_id` INT UNSIGNED DEFAULT NULL,\n          `dicatat_oleh` INT UNSIGNED DEFAULT NULL,\n          `dibatalkan` TINYINT(1) NOT NULL DEFAULT 0,\n          `dibatalkan_oleh` INT UNSIGNED DEFAULT NULL,\n          `dibatalkan_pada` DATETIME DEFAULT NULL,\n          `alasan_batal` VARCHAR(255) DEFAULT NULL,\n          `created_at` DATETIME NULL,\n          PRIMARY KEY (`id`),\n          KEY `idx_sub_pos_mutasi_pos` (`sub_pos_id`, `dibatalkan`),\n          KEY `idx_sub_pos_mutasi_bayar` (`pembayaran_id`),\n          KEY `idx_sub_pos_mutasi_kas` (`kas_transaksi_id`)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function syncFromSources(): void
    {
        $this->ensureTable();
        $this->ensureDenda();
        $komp = new IuranKomponenService();
        $komp->ensureTable();
        foreach ($komp->list(false) as $k) {
            $this->ensureKomponen((int) $k['id'], (string) $k['nama'], !empty($k['aktif']));
        }
    }

    public function ensureDenda(): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos')->where('jenis', self::JENIS_DENDA)->get()->getRowArray();
        if ($row) {
            return $this->map($row);
        }
        $now = date('Y-m-d H:i:s');
        $db->table('sub_pos')->insert([
            'jenis' => self::JENIS_DENDA, 'ref_id' => null, 'judul' => 'Denda ronda',
            'aktif' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        return $this->detail($id) ?? ['id' => $id, 'jenis' => self::JENIS_DENDA, 'ref_id' => null, 'judul' => 'Denda ronda', 'aktif' => true, 'saldo' => 0];
    }

    public function ensureKomponen(int $komponenId, string $judul, bool $aktif = true): array
    {
        $this->ensureTable();
        if ($komponenId < 1) {
            return ['ok' => false, 'message' => 'ref komponen invalid'];
        }
        $judul = trim($judul);
        if ($judul === '') {
            $judul = 'Komponen #' . $komponenId;
        }
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos')->where('jenis', self::JENIS_KOMPONEN)->where('ref_id', $komponenId)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        if ($row) {
            $upd = ['updated_at' => $now];
            if ((string) $row['judul'] !== $judul) {
                $upd['judul'] = $judul;
            }
            $aktifInt = $aktif ? 1 : 0;
            if ((int) $row['aktif'] !== $aktifInt) {
                $upd['aktif'] = $aktifInt;
            }
            if (count($upd) > 1) {
                $db->table('sub_pos')->where('id', (int) $row['id'])->update($upd);
            }
            return $this->map(array_merge($row, $upd));
        }
        $db->table('sub_pos')->insert([
            'jenis' => self::JENIS_KOMPONEN, 'ref_id' => $komponenId, 'judul' => mb_substr($judul, 0, 255),
            'aktif' => $aktif ? 1 : 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        return $this->detail($id) ?? ['id' => $id, 'jenis' => self::JENIS_KOMPONEN, 'ref_id' => $komponenId, 'judul' => $judul, 'aktif' => $aktif, 'saldo' => 0];
    }

    public function ensureKhusus(string $judul, ?int $refIuranKhususId = null): array
    {
        $this->ensureTable();
        $judul = trim($judul);
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Judul khusus wajib'];
        }
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos')->where('jenis', self::JENIS_KHUSUS)->where('judul', $judul)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        if ($row) {
            if ($refIuranKhususId && empty($row['ref_id'])) {
                $db->table('sub_pos')->where('id', (int) $row['id'])->update(['ref_id' => $refIuranKhususId, 'updated_at' => $now]);
                $row['ref_id'] = $refIuranKhususId;
            }
            return $this->map($row);
        }
        $db->table('sub_pos')->insert([
            'jenis' => self::JENIS_KHUSUS, 'ref_id' => $refIuranKhususId, 'judul' => mb_substr($judul, 0, 255),
            'aktif' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        return $this->detail($id) ?? ['id' => $id, 'jenis' => self::JENIS_KHUSUS, 'ref_id' => $refIuranKhususId, 'judul' => $judul, 'aktif' => true, 'saldo' => 0];
    }

    public function saldo(int $subPosId): int
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $masuk = (int) ($db->table('sub_pos_mutasi')->selectSum('nominal', 'total')->where('sub_pos_id', $subPosId)->where('dibatalkan', 0)->whereIn('tipe', ['masuk'])->get()->getRowArray()['total'] ?? 0);
        $keluar = (int) ($db->table('sub_pos_mutasi')->selectSum('nominal', 'total')->where('sub_pos_id', $subPosId)->where('dibatalkan', 0)->whereIn('tipe', ['keluar', 'alokasi'])->get()->getRowArray()['total'] ?? 0);
        return $masuk - $keluar;
    }

    public function list(bool $aktifSaja = false): array
    {
        $this->syncFromSources();
        $db = \Config\Database::connect();
        $q = $db->table('sub_pos')->orderBy('jenis', 'ASC')->orderBy('judul', 'ASC');
        if ($aktifSaja) {
            $q->where('aktif', 1);
        }
        $rows = $q->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $m = $this->map($r);
            $m['saldo'] = $this->saldo((int) $r['id']);
            $out[] = $m;
        }
        return $out;
    }

    public function detail(int $id): ?array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $r = $db->table('sub_pos')->where('id', $id)->get()->getRowArray();
        if (!$r) {
            return null;
        }
        $m = $this->map($r);
        $m['saldo'] = $this->saldo($id);
        return $m;
    }

    public function masukDariPembayaran(int $pembayaranId, int $keluargaId, string $metode, string $tanggal, array $potongan, int $sisaBayar, int $userId): void
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $agregat = [];
        foreach ($potongan as $p) {
            $jenis = (string) ($p['jenis'] ?? '');
            $jumlah = (int) ($p['jumlah'] ?? 0);
            if ($jumlah < 1) {
                continue;
            }
            if ($jenis === 'kas') {
                foreach ($this->splitKasKeKomponen($jumlah) as $spId => $nom) {
                    $agregat[$spId] = ($agregat[$spId] ?? 0) + $nom;
                }
            } elseif ($jenis === 'khusus') {
                $judul = $this->judulKhususDariTagihan((int) ($p['tagihan_id'] ?? 0));
                if ($judul === '') {
                    $judul = 'Iuran khusus';
                }
                $sp = $this->ensureKhusus($judul);
                $spId = (int) ($sp['id'] ?? 0);
                if ($spId > 0) {
                    $agregat[$spId] = ($agregat[$spId] ?? 0) + $jumlah;
                }
            } elseif ($jenis === 'denda_ronda') {
                $sp = $this->ensureDenda();
                $spId = (int) ($sp['id'] ?? 0);
                if ($spId > 0) {
                    $agregat[$spId] = ($agregat[$spId] ?? 0) + $jumlah;
                }
            }
        }
        if ($sisaBayar > 0) {
            foreach ($this->splitKasKeKomponen($sisaBayar) as $spId => $nom) {
                $agregat[$spId] = ($agregat[$spId] ?? 0) + $nom;
            }
        }
        $now = date('Y-m-d H:i:s');
        foreach ($agregat as $spId => $nominal) {
            $nominal = (int) $nominal;
            if ($nominal < 1 || (int) $spId < 1) {
                continue;
            }
            $db->table('sub_pos_mutasi')->insert([
                'sub_pos_id' => (int) $spId, 'tipe' => 'masuk', 'nominal' => $nominal,
                'tanggal' => $tanggal, 'keterangan' => 'Otomatis dari pembayaran #' . $pembayaranId,
                'pembayaran_id' => $pembayaranId, 'dicatat_oleh' => $userId, 'dibatalkan' => 0, 'created_at' => $now,
            ]);
        }
    }

    public function batalkanDariPembayaran(int $pembayaranId, int $userId): void
    {
        if ($pembayaranId < 1) {
            return;
        }
        $this->ensureTable();
        $db = \Config\Database::connect();
        $db->table('sub_pos_mutasi')->where('pembayaran_id', $pembayaranId)->where('dibatalkan', 0)->update([
            'dibatalkan' => 1, 'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'), 'alasan_batal' => 'Pembayaran dibatalkan',
        ]);
    }

    public function catatMasuk(int $subPosId, array $data, int $userId): array
    {
        $this->ensureTable();
        $sp = $this->detail($subPosId);
        if (!$sp) {
            return ['ok' => false, 'message' => 'Sub pos tidak ditemukan.'];
        }
        $nominal = (int) ($data['nominal'] ?? 0);
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal wajib lebih dari 0.'];
        }
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $ket = trim((string) ($data['keterangan'] ?? ''));
        $db = \Config\Database::connect();
        $db->table('sub_pos_mutasi')->insert([
            'sub_pos_id' => $subPosId, 'tipe' => 'masuk', 'nominal' => $nominal, 'tanggal' => $tanggal,
            'keterangan' => $ket !== '' ? $ket : 'Masuk manual', 'pembayaran_id' => null,
            'dicatat_oleh' => $userId, 'dibatalkan' => 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $mid = (int) $db->insertID();
        (new AuditService())->log('sub_pos_masuk', 'sub_pos_mutasi', $mid, null, [
            'sub_pos_id' => $subPosId, 'judul' => $sp['judul'], 'nominal' => $nominal,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['mutasi_id' => $mid, 'sub_pos_id' => $subPosId, 'saldo' => $this->saldo($subPosId)]];
    }

    public function catatKeluar(int $subPosId, array $data, int $userId): array
    {
        $this->ensureTable();
        $sp = $this->detail($subPosId);
        if (!$sp) {
            return ['ok' => false, 'message' => 'Sub pos tidak ditemukan.'];
        }
        $nominal = (int) ($data['nominal'] ?? 0);
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal wajib lebih dari 0.'];
        }
        $saldo = $this->saldo($subPosId);
        if ($saldo < $nominal) {
            return ['ok' => false, 'message' => 'Saldo sub pos tidak cukup (tersedia ' . number_format($saldo, 0, ',', '.') . ').'];
        }
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $ket = trim((string) ($data['keterangan'] ?? ''));
        $db = \Config\Database::connect();
        $db->table('sub_pos_mutasi')->insert([
            'sub_pos_id' => $subPosId, 'tipe' => 'keluar', 'nominal' => $nominal, 'tanggal' => $tanggal,
            'keterangan' => $ket !== '' ? $ket : 'Keluar manual', 'pembayaran_id' => null,
            'dicatat_oleh' => $userId, 'dibatalkan' => 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $mid = (int) $db->insertID();
        (new AuditService())->log('sub_pos_keluar', 'sub_pos_mutasi', $mid, null, [
            'sub_pos_id' => $subPosId, 'judul' => $sp['judul'], 'nominal' => $nominal,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['mutasi_id' => $mid, 'sub_pos_id' => $subPosId, 'saldo' => $this->saldo($subPosId)]];
    }

    public function alokasiKePosKas(int $subPosId, array $data, int $userId): array
    {
        $this->ensureTable();
        $sp = $this->detail($subPosId);
        if (!$sp) {
            return ['ok' => false, 'message' => 'Sub pos tidak ditemukan.'];
        }
        $nominal = (int) ($data['nominal'] ?? 0);
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal wajib lebih dari 0.'];
        }
        $saldo = $this->saldo($subPosId);
        if ($saldo < $nominal) {
            return ['ok' => false, 'message' => 'Saldo sub pos tidak cukup (tersedia ' . number_format($saldo, 0, ',', '.') . ').'];
        }
        $posId = (int) ($data['pos_kas_id'] ?? 0);
        $posSvc = new KasPosService();
        if ($posId < 1) {
            $posId = (int) ($posSvc->defaultPosId() ?? 0);
        }
        if ($posId < 1) {
            return ['ok' => false, 'message' => 'Pos kas tujuan wajib.'];
        }
        $db = \Config\Database::connect();
        $pos = $db->table('kas_pos')->where('id', $posId)->get()->getRowArray();
        if (!$pos || (int) ($pos['aktif'] ?? 0) !== 1) {
            return ['ok' => false, 'message' => 'Pos kas tidak ditemukan atau nonaktif.'];
        }
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $ketExtra = trim((string) ($data['keterangan'] ?? ''));
        $judul = (string) $sp['judul'];
        $ket = 'Setoran sub pos: ' . $judul;
        if ($ketExtra !== '') {
            $ket .= ' — ' . $ketExtra;
        }
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('sub_pos_mutasi')->insert([
            'sub_pos_id' => $subPosId, 'tipe' => 'alokasi', 'nominal' => $nominal, 'tanggal' => $tanggal,
            'keterangan' => $ket, 'pembayaran_id' => null, 'pos_kas_id' => $posId,
            'dicatat_oleh' => $userId, 'dibatalkan' => 0, 'created_at' => $now,
        ]);
        $mid = (int) $db->insertID();
        $kasRow = [
            'tipe' => 'masuk', 'nominal' => $nominal, 'kategori' => self::KAT_SETORAN, 'metode' => 'tunai',
            'keterangan' => $ket, 'tanggal' => $tanggal, 'keluarga_id' => null, 'pembayaran_id' => null,
            'dibatalkan' => 0, 'dicatat_oleh' => $userId, 'dicatat_pada' => $now,
        ];
        if ($this->hasKasPosColumn()) {
            $kasRow['kas_pos_id'] = $posId;
        }
        $db->table('kas_transaksi')->insert($kasRow);
        $kid = (int) $db->insertID();
        $db->table('sub_pos_mutasi')->where('id', $mid)->update(['kas_transaksi_id' => $kid]);
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal menyimpan alokasi.'];
        }
        (new AuditService())->log('sub_pos_alokasi', 'sub_pos_mutasi', $mid, null, [
            'sub_pos_id' => $subPosId, 'judul' => $judul, 'pos_kas_id' => $posId,
            'pos_kas_nama' => $pos['nama'] ?? null, 'nominal' => $nominal, 'kas_transaksi_id' => $kid,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => [
            'mutasi_id' => $mid, 'kas_transaksi_id' => $kid, 'sub_pos_id' => $subPosId,
            'saldo_sub_pos' => $this->saldo($subPosId), 'pos_kas_id' => $posId, 'pos_kas_nama' => $pos['nama'] ?? null,
        ]];
    }

    public function listMutasi(int $subPosId, array $filter = []): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $q = $db->table('sub_pos_mutasi')->where('sub_pos_id', $subPosId)->where('dibatalkan', 0)
            ->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC');
        if (!empty($filter['tipe']) && in_array($filter['tipe'], ['masuk', 'keluar', 'alokasi'], true)) {
            $q->where('tipe', $filter['tipe']);
        }
        if (!empty($filter['bulan'])) {
            $q->like('tanggal', (string) $filter['bulan'], 'after');
        }
        $limit = (int) ($filter['limit'] ?? 100);
        if ($limit > 0) {
            $q->limit(min($limit, 500));
        }
        $rows = $q->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r['id'], 'sub_pos_id' => (int) $r['sub_pos_id'], 'tipe' => $r['tipe'],
                'nominal' => (int) $r['nominal'], 'tanggal' => $r['tanggal'], 'keterangan' => $r['keterangan'],
                'pembayaran_id' => $r['pembayaran_id'] ? (int) $r['pembayaran_id'] : null,
                'kas_transaksi_id' => $r['kas_transaksi_id'] ? (int) $r['kas_transaksi_id'] : null,
                'pos_kas_id' => $r['pos_kas_id'] ? (int) $r['pos_kas_id'] : null,
                'dicatat_oleh' => $r['dicatat_oleh'] ? (int) $r['dicatat_oleh'] : null,
                'created_at' => $r['created_at'] ?? null,
            ];
        }
        return $out;
    }

    public function batalkanMutasi(int $mutasiId, string $alasan, int $userId): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            return ['ok' => false, 'message' => 'Alasan wajib.'];
        }
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos_mutasi')->where('id', $mutasiId)->get()->getRowArray();
        if (!$row || (int) $row['dibatalkan'] === 1) {
            return ['ok' => false, 'message' => 'Mutasi tidak ditemukan.'];
        }
        if (!empty($row['pembayaran_id'])) {
            return ['ok' => false, 'message' => 'Mutasi dari pembayaran hanya batal bersama pembatalan pembayaran.'];
        }
        $db->transStart();
        $db->table('sub_pos_mutasi')->where('id', $mutasiId)->update([
            'dibatalkan' => 1, 'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'), 'alasan_batal' => $alasan,
        ]);
        if (($row['tipe'] ?? '') === 'alokasi' && !empty($row['kas_transaksi_id'])) {
            $db->table('kas_transaksi')->where('id', (int) $row['kas_transaksi_id'])->where('dibatalkan', 0)->update([
                'dibatalkan' => 1, 'dibatalkan_oleh' => $userId,
                'dibatalkan_pada' => date('Y-m-d H:i:s'), 'alasan_batal' => 'Alokasi sub pos dibatalkan: ' . $alasan,
            ]);
        }
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal membatalkan mutasi.'];
        }
        (new AuditService())->log('sub_pos_batal_mutasi', 'sub_pos_mutasi', $mutasiId, $row, ['alasan' => $alasan], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['sub_pos_id' => (int) $row['sub_pos_id'], 'saldo' => $this->saldo((int) $row['sub_pos_id'])]];
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

    private function splitKasKeKomponen(int $nominal): array
    {
        if ($nominal < 1) {
            return [];
        }
        $svc = new IuranKomponenService();
        $svc->ensureTable();
        $komp = $svc->list(true);
        if ($komp === []) {
            $komp = $svc->list(false);
        }
        if ($komp === []) {
            return [];
        }
        $totalWeight = 0;
        foreach ($komp as $k) {
            $totalWeight += max(0, (int) $k['nominal']);
        }
        $parts = [];
        foreach ($komp as $k) {
            $sp = $this->ensureKomponen((int) $k['id'], (string) $k['nama'], !empty($k['aktif']));
            $spId = (int) ($sp['id'] ?? 0);
            if ($spId < 1) {
                continue;
            }
            $parts[] = ['id' => $spId, 'weight' => max(0, (int) $k['nominal'])];
        }
        if ($parts === []) {
            return [];
        }
        $out = [];
        if ($totalWeight < 1) {
            $n = count($parts);
            $each = intdiv($nominal, $n);
            $rem = $nominal - ($each * $n);
            foreach ($parts as $i => $p) {
                $share = $each + ($i === 0 ? $rem : 0);
                if ($share > 0) {
                    $out[$p['id']] = ($out[$p['id']] ?? 0) + $share;
                }
            }
            return $out;
        }
        $allocated = 0;
        foreach ($parts as &$p) {
            $share = (int) floor($nominal * $p['weight'] / $totalWeight);
            $p['share'] = $share;
            $allocated += $share;
        }
        unset($p);
        $rem = $nominal - $allocated;
        usort($parts, static fn ($a, $b) => $b['weight'] <=> $a['weight']);
        if ($rem > 0 && $parts !== []) {
            $parts[0]['share'] = ($parts[0]['share'] ?? 0) + $rem;
        }
        foreach ($parts as $p) {
            $share = (int) ($p['share'] ?? 0);
            if ($share > 0) {
                $out[$p['id']] = ($out[$p['id']] ?? 0) + $share;
            }
        }
        return $out;
    }

    private function judulKhususDariTagihan(int $tagihanId): string
    {
        if ($tagihanId < 1) {
            return '';
        }
        $db = \Config\Database::connect();
        $t = $db->table('iuran_tagihan t')->select('ik.nama')->join('iuran_khusus ik', 'ik.id = t.iuran_khusus_id', 'left')->where('t.id', $tagihanId)->get()->getRowArray();
        return trim((string) ($t['nama'] ?? ''));
    }

    private function map(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'jenis' => (string) ($r['jenis'] ?? ''),
            'ref_id' => isset($r['ref_id']) && $r['ref_id'] !== null && $r['ref_id'] !== '' ? (int) $r['ref_id'] : null,
            'judul' => (string) ($r['judul'] ?? ''),
            'aktif' => (int) ($r['aktif'] ?? 1) === 1,
            'created_at' => $r['created_at'] ?? null,
            'updated_at' => $r['updated_at'] ?? null,
        ];
    }
}
