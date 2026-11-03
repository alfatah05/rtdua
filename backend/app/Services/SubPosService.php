<?php

namespace App\Services;

/**
 * Sub pos iuran: buku saldo terpisah per komponen / judul khusus / denda.
 * T1: tabel + ensure + list.
 * T2: masukDariPembayaran + batalkanDariPembayaran.
 * T3: catatMasuk/Keluar, alokasiKePosKas, listMutasi, batalkanMutasi.
 * T6: rekapBulan, ensureKhusus stabil (ref_id), split hanya komponen aktif.
 * Komponen iuran di-link manual ke sub pos (sub_pos_id di iuran_komponen).
 * Sub pos bisa dibuat/diubah/dihapus dari UI (jenis komponen).
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

        $db->query("CREATE TABLE IF NOT EXISTS `sub_pos_mutasi` (\n          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n          `sub_pos_id` INT UNSIGNED NOT NULL,\n          `tipe` VARCHAR(16) NOT NULL,\n          `nominal` INT NOT NULL DEFAULT 0,\n          `tanggal` DATE NOT NULL,\n          `keterangan` VARCHAR(500) NULL,\n          `pembayaran_id` INT UNSIGNED NULL,\n          `kas_transaksi_id` INT UNSIGNED NULL,\n          `pos_kas_id` INT UNSIGNED NULL,\n          `dicatat_oleh` INT UNSIGNED NULL,\n          `dibatalkan` TINYINT(1) NOT NULL DEFAULT 0,\n          `dibatalkan_oleh` INT UNSIGNED NULL,\n          `dibatalkan_pada` DATETIME NULL,\n          `alasan_batal` VARCHAR(255) NULL,\n          `created_at` DATETIME NULL,\n          PRIMARY KEY (`id`),\n          KEY `idx_spm_sub_pos` (`sub_pos_id`),\n          KEY `idx_spm_pembayaran` (`pembayaran_id`),\n          KEY `idx_spm_tanggal` (`tanggal`)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function syncFromSources(): void
    {
        $this->ensureTable();
        $this->ensureDenda();
        $db = \Config\Database::connect();
        // Komponen tidak di-auto-create lagi — pengurus buat sub pos lalu link dari komponen.
        try {
            $khusus = $db->table('iuran_khusus')->select('id, nama')->get()->getResultArray();
            foreach ($khusus as $k) {
                $this->ensureKhusus((string) $k['nama'], (int) $k['id']);
            }
        } catch (\Throwable $e) {
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
        $judul = trim($judul) !== '' ? trim($judul) : ('Komponen #' . $komponenId);
        $judul = mb_substr($judul, 0, 255);
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $row = null;
        if ($komponenId > 0) {
            $row = $db->table('sub_pos')
                ->where('jenis', self::JENIS_KOMPONEN)
                ->where('ref_id', $komponenId)
                ->get()
                ->getRowArray();
        }

        if (!$row && $judul !== '') {
            $row = $db->table('sub_pos')
                ->where('jenis', self::JENIS_KOMPONEN)
                ->where('judul', $judul)
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        }

        if ($row) {
            $upd = [];
            if ($aktif && (int) $row['aktif'] !== 1) {
                $upd['aktif'] = 1;
            }
            if ($komponenId > 0 && empty($row['ref_id'])) {
                $upd['ref_id'] = $komponenId;
            }
            if ($upd) {
                $upd['updated_at'] = $now;
                $db->table('sub_pos')->where('id', (int) $row['id'])->update($upd);
            }
            return $this->detail((int) $row['id']) ?? $this->map($row);
        }

        $db->table('sub_pos')->insert([
            'jenis' => self::JENIS_KOMPONEN,
            'ref_id' => $komponenId > 0 ? $komponenId : null,
            'judul' => $judul,
            'aktif' => $aktif ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        return $this->detail($id) ?? [
            'id' => $id,
            'jenis' => self::JENIS_KOMPONEN,
            'ref_id' => $komponenId > 0 ? $komponenId : null,
            'judul' => $judul,
            'aktif' => $aktif,
            'saldo' => 0,
        ];
    }

    public function konsolidasiKomponenByJudul(): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $rows = $db->table('sub_pos')
            ->where('jenis', self::JENIS_KOMPONEN)
            ->orderBy('judul', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        $byJudul = [];
        foreach ($rows as $r) {
            $j = mb_strtolower(trim((string) $r['judul']));
            if ($j === '') {
                continue;
            }
            $byJudul[$j][] = $r;
        }
        $digabung = 0;
        $dipindahMutasi = 0;
        foreach ($byJudul as $group) {
            if (count($group) < 2) {
                continue;
            }
            $keep = (int) $group[0]['id'];
            for ($i = 1; $i < count($group); $i++) {
                $dup = (int) $group[$i]['id'];
                $n = $db->table('sub_pos_mutasi')->where('sub_pos_id', $dup)->countAllResults();
                if ($n > 0) {
                    $db->table('sub_pos_mutasi')->where('sub_pos_id', $dup)->update(['sub_pos_id' => $keep]);
                    $dipindahMutasi += $n;
                }
                $db->table('sub_pos')->where('id', $dup)->delete();
                $digabung++;
            }
            $anyAktif = false;
            foreach ($group as $g) {
                if ((int) ($g['aktif'] ?? 0) === 1) {
                    $anyAktif = true;
                    break;
                }
            }
            if ($anyAktif) {
                $db->table('sub_pos')->where('id', $keep)->update(['aktif' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        return ['digabung' => $digabung, 'mutasi_dipindah' => $dipindahMutasi];
    }

    public function ensureKhusus(string $judul, ?int $refIuranKhususId = null): array
    {
        $this->ensureTable();
        $judul = trim($judul);
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Judul khusus wajib'];
        }
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $row = null;
        if ($refIuranKhususId && $refIuranKhususId > 0) {
            $row = $db->table('sub_pos')->where('jenis', self::JENIS_KHUSUS)->where('ref_id', $refIuranKhususId)->get()->getRowArray();
        }
        if (!$row) {
            $row = $db->table('sub_pos')->where('jenis', self::JENIS_KHUSUS)->where('judul', $judul)->get()->getRowArray();
        }
        if ($row) {
            $upd = [];
            if ($refIuranKhususId && $refIuranKhususId > 0 && (int) ($row['ref_id'] ?? 0) !== $refIuranKhususId) {
                if (empty($row['ref_id'])) {
                    $upd['ref_id'] = $refIuranKhususId;
                    $row['ref_id'] = $refIuranKhususId;
                }
            }
            if ($judul !== '' && (string) ($row['judul'] ?? '') !== $judul) {
                if ($refIuranKhususId && (int) ($row['ref_id'] ?? 0) === $refIuranKhususId) {
                    $upd['judul'] = mb_substr($judul, 0, 255);
                    $row['judul'] = $judul;
                }
            }
            if ($upd) {
                $upd['updated_at'] = $now;
                $db->table('sub_pos')->where('id', (int) $row['id'])->update($upd);
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
                $info = $this->infoKhususDariTagihan((int) ($p['tagihan_id'] ?? 0));
                $judul = $info['judul'] !== '' ? $info['judul'] : 'Iuran khusus';
                $sp = $this->ensureKhusus($judul, $info['iuran_khusus_id'] ?: null);
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
            'dibatalkan' => 1,
            'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
            'alasan_batal' => 'Pembayaran dibatalkan',
        ]);
    }


    /**
     * Buat sub pos manual (untuk ditautkan ke komponen iuran).
     * Jenis default: komponen. Judul unik disarankan.
     */
    public function buat(array $data, int $userId): array
    {
        $this->ensureTable();
        $judul = trim((string) ($data['judul'] ?? ''));
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Nama sub pos wajib.'];
        }
        $judul = mb_substr($judul, 0, 255);
        $db = \Config\Database::connect();
        $dup = $db->table('sub_pos')
            ->where('jenis', self::JENIS_KOMPONEN)
            ->where('judul', $judul)
            ->countAllResults();
        if ($dup > 0) {
            return ['ok' => false, 'message' => 'Sudah ada sub pos dengan nama itu.'];
        }
        $now = date('Y-m-d H:i:s');
        $db->table('sub_pos')->insert([
            'jenis' => self::JENIS_KOMPONEN,
            'ref_id' => null,
            'judul' => $judul,
            'aktif' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('sub_pos_buat', 'sub_pos', $id, null, ['judul' => $judul], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function ubah(int $id, array $data, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Sub pos tidak ditemukan.'];
        }
        if ((string) ($row['jenis'] ?? '') === self::JENIS_DENDA) {
            return ['ok' => false, 'message' => 'Sub pos denda tidak boleh diubah dari sini.'];
        }
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        if (array_key_exists('judul', $data)) {
            $judul = trim((string) $data['judul']);
            if ($judul === '') {
                return ['ok' => false, 'message' => 'Nama wajib.'];
            }
            $judul = mb_substr($judul, 0, 255);
            $dup = $db->table('sub_pos')
                ->where('jenis', $row['jenis'])
                ->where('judul', $judul)
                ->where('id !=', $id)
                ->countAllResults();
            if ($dup > 0) {
                return ['ok' => false, 'message' => 'Nama sub pos sudah dipakai.'];
            }
            $update['judul'] = $judul;
        }
        if (array_key_exists('aktif', $data)) {
            $update['aktif'] = !empty($data['aktif']) ? 1 : 0;
        }
        if (count($update) < 2) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $db->table('sub_pos')->where('id', $id)->update($update);
        (new AuditService())->log('sub_pos_ubah', 'sub_pos', $id, $row, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    /**
     * Hapus sub pos. Gagal jika masih tertaut komponen, atau ada mutasi/saldo.
     */
    public function hapus(int $id, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Sub pos tidak ditemukan.'];
        }
        if ((string) ($row['jenis'] ?? '') === self::JENIS_DENDA) {
            return ['ok' => false, 'message' => 'Sub pos denda tidak boleh dihapus.'];
        }
        try {
            if ($db->fieldExists('sub_pos_id', 'iuran_komponen')) {
                $linked = (int) $db->table('iuran_komponen')->where('sub_pos_id', $id)->countAllResults();
                if ($linked > 0) {
                    return ['ok' => false, 'message' => 'Masih dipakai ' . $linked . ' komponen iuran. Lepas tautan dulu.'];
                }
            }
        } catch (\Throwable $e) {
        }
        $saldo = $this->saldo($id);
        if ($saldo !== 0) {
            return ['ok' => false, 'message' => 'Saldo masih ' . $saldo . '. Kosongkan dulu (alokasi/keluar) sebelum hapus.'];
        }
        $mutasi = (int) $db->table('sub_pos_mutasi')->where('sub_pos_id', $id)->where('dibatalkan', 0)->countAllResults();
        if ($mutasi > 0) {
            return ['ok' => false, 'message' => 'Masih ada riwayat mutasi. Batalkan mutasi atau biarkan nonaktif saja.'];
        }
        $db->table('sub_pos_mutasi')->where('sub_pos_id', $id)->delete();
        $db->table('sub_pos')->where('id', $id)->delete();
        (new AuditService())->log('sub_pos_hapus', 'sub_pos', $id, $row, null, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id]];
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
            'sub_pos_id' => $subPosId, 'tipe' => 'masuk', 'nominal' => $nominal,
            'tanggal' => $tanggal, 'keterangan' => $ket !== '' ? $ket : 'Masuk manual',
            'pembayaran_id' => null, 'dicatat_oleh' => $userId, 'dibatalkan' => 0,
            'created_at' => date('Y-m-d H:i:s'),
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
            'sub_pos_id' => $subPosId, 'tipe' => 'keluar', 'nominal' => $nominal,
            'tanggal' => $tanggal, 'keterangan' => $ket !== '' ? $ket : 'Keluar manual',
            'pembayaran_id' => null, 'dicatat_oleh' => $userId, 'dibatalkan' => 0,
            'created_at' => date('Y-m-d H:i:s'),
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
            'sub_pos_id' => $subPosId, 'tipe' => 'alokasi', 'nominal' => $nominal,
            'tanggal' => $tanggal, 'keterangan' => $ket, 'pembayaran_id' => null,
            'pos_kas_id' => $posId, 'dicatat_oleh' => $userId, 'dibatalkan' => 0, 'created_at' => $now,
        ]);
        $mid = (int) $db->insertID();
        $kasRow = [
            'tipe' => 'masuk', 'kategori' => self::KAT_SETORAN, 'nominal' => $nominal,
            'metode' => 'tunai', 'keterangan' => $ket, 'tanggal' => $tanggal,
            'keluarga_id' => null, 'pembayaran_id' => null, 'dibatalkan' => 0,
            'dicatat_oleh' => $userId, 'dicatat_pada' => $now,
        ];
        if ($this->hasKasPosColumn()) {
            $kasRow['kas_pos_id'] = $posId;
        }
        $db->table('kas_transaksi')->insert($kasRow);
        $kasId = (int) $db->insertID();
        $db->table('sub_pos_mutasi')->where('id', $mid)->update(['kas_transaksi_id' => $kasId]);
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal menyimpan alokasi.'];
        }
        (new AuditService())->log('sub_pos_alokasi', 'sub_pos_mutasi', $mid, null, [
            'sub_pos_id' => $subPosId, 'judul' => $judul, 'nominal' => $nominal, 'pos_kas_id' => $posId, 'kas_transaksi_id' => $kasId,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['mutasi_id' => $mid, 'kas_transaksi_id' => $kasId, 'sub_pos_id' => $subPosId, 'saldo' => $this->saldo($subPosId)]];
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
                'id' => (int) $r['id'],
                'sub_pos_id' => (int) $r['sub_pos_id'],
                'tipe' => (string) $r['tipe'],
                'nominal' => (int) $r['nominal'],
                'tanggal' => $r['tanggal'],
                'keterangan' => $r['keterangan'],
                'pembayaran_id' => !empty($r['pembayaran_id']) ? (int) $r['pembayaran_id'] : null,
                'kas_transaksi_id' => !empty($r['kas_transaksi_id']) ? (int) $r['kas_transaksi_id'] : null,
                'pos_kas_id' => !empty($r['pos_kas_id']) ? (int) $r['pos_kas_id'] : null,
                'created_at' => $r['created_at'] ?? null,
            ];
        }
        return $out;
    }

    public function batalkanMutasi(int $mutasiId, int $userId, string $alasan = ''): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos_mutasi')->where('id', $mutasiId)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Mutasi tidak ditemukan.'];
        }
        if ((int) ($row['dibatalkan'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'Mutasi sudah dibatalkan.'];
        }
        $db->table('sub_pos_mutasi')->where('id', $mutasiId)->update([
            'dibatalkan' => 1,
            'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
            'alasan_batal' => $alasan !== '' ? $alasan : 'Dibatalkan manual',
        ]);
        if (!empty($row['kas_transaksi_id'])) {
            $db->table('kas_transaksi')->where('id', (int) $row['kas_transaksi_id'])->update(['dibatalkan' => 1]);
        }
        (new AuditService())->log('sub_pos_batal_mutasi', 'sub_pos_mutasi', $mutasiId, null, [
            'alasan' => $alasan, 'tipe' => $row['tipe'] ?? null, 'nominal' => (int) ($row['nominal'] ?? 0),
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['sub_pos_id' => (int) $row['sub_pos_id'], 'saldo' => $this->saldo((int) $row['sub_pos_id'])]];
    }

    private function splitKasKeKomponen(int $nominal): array
    {
        if ($nominal < 1) {
            return [];
        }
        $svc = new IuranKomponenService();
        $svc->ensureTable();
        $komp = $svc->list(true);
        $weights = [];
        $totalW = 0;
        foreach ($komp as $k) {
            $spId = (int) ($k['sub_pos_id'] ?? 0);
            if ($spId < 1) {
                // fallback lama: cari sub_pos by ref_id komponen
                try {
                    $row = \Config\Database::connect()->table('sub_pos')
                        ->where('jenis', self::JENIS_KOMPONEN)
                        ->where('ref_id', (int) $k['id'])
                        ->get()->getRowArray();
                    $spId = $row ? (int) $row['id'] : 0;
                } catch (\Throwable $e) {
                    $spId = 0;
                }
            }
            if ($spId < 1) {
                continue;
            }
            $w = (int) ($k['nominal'] ?? 0);
            if ($w < 1) {
                $w = 1;
            }
            $weights[$spId] = ($weights[$spId] ?? 0) + $w;
            $totalW += $w;
        }
        if ($totalW < 1 || !$weights) {
            return [];
        }
        $out = [];
        $assigned = 0;
        $ids = array_keys($weights);
        foreach ($ids as $i => $spId) {
            if ($i === count($ids) - 1) {
                $out[$spId] = $nominal - $assigned;
            } else {
                $part = (int) floor($nominal * $weights[$spId] / $totalW);
                $out[$spId] = $part;
                $assigned += $part;
            }
        }
        return $out;
    }

    private function infoKhususDariTagihan(int $tagihanId): array
    {
        $db = \Config\Database::connect();
        $out = ['judul' => '', 'iuran_khusus_id' => 0];
        if ($tagihanId < 1) {
            return $out;
        }
        try {
            $t = $db->table('iuran_tagihan')->where('id', $tagihanId)->get()->getRowArray();
            if (!$t) {
                return $out;
            }
            $judul = trim((string) ($t['nama'] ?? $t['judul'] ?? ''));
            $ref = (int) ($t['ref_id'] ?? $t['iuran_khusus_id'] ?? 0);
            if ($judul === '' && $ref > 0) {
                $k = $db->table('iuran_khusus')->where('id', $ref)->get()->getRowArray();
                if ($k) {
                    $judul = (string) ($k['nama'] ?? '');
                }
            }
            $out['judul'] = $judul;
            $out['iuran_khusus_id'] = $ref;
        } catch (\Throwable $e) {
        }
        return $out;
    }

    public function rekapBulan(string $bulan): array
    {
        $this->ensureTable();
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }
        $db = \Config\Database::connect();
        $rows = $db->table('sub_pos')->orderBy('jenis', 'ASC')->orderBy('judul', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $masuk = (int) ($db->table('sub_pos_mutasi')->selectSum('nominal', 'total')->where('sub_pos_id', $id)->where('dibatalkan', 0)->where('tipe', 'masuk')->like('tanggal', $bulan, 'after')->get()->getRowArray()['total'] ?? 0);
            $keluar = (int) ($db->table('sub_pos_mutasi')->selectSum('nominal', 'total')->where('sub_pos_id', $id)->where('dibatalkan', 0)->whereIn('tipe', ['keluar', 'alokasi'])->like('tanggal', $bulan, 'after')->get()->getRowArray()['total'] ?? 0);
            $out[] = [
                'id' => $id,
                'jenis' => (string) $r['jenis'],
                'judul' => (string) $r['judul'],
                'aktif' => (int) ($r['aktif'] ?? 1) === 1,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'netto' => $masuk - $keluar,
                'saldo' => $this->saldo($id),
            ];
        }
        return $out;
    }

    private function hasKasPosColumn(): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $db = \Config\Database::connect();
            $cache = $db->fieldExists('kas_pos_id', 'kas_transaksi');
        } catch (\Throwable $e) {
            $cache = false;
        }
        return $cache;
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
