<?php

namespace App\Services;

/**
 * Sub pos iuran: buku saldo terpisah per komponen / judul khusus / denda.
 * T1: tabel + ensure + list.
 * T2: masukDariPembayaran + batalkanDariPembayaran.
 * T3: catatMasuk/Keluar, alokasiKePosKas, listMutasi, batalkanMutasi.
 * T6: rekapBulan, ensureKhusus stabil (ref_id), split hanya komponen aktif.
 * Nama komponen sama = 1 sub pos (share antar tarif utama/khusus).
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
        try {
            $komp = $db->table('iuran_komponen')->get()->getResultArray();
            foreach ($komp as $k) {
                $this->ensureKomponen((int) $k['id'], (string) $k['nama'], !empty($k['aktif']));
            }
            try { $this->konsolidasiKomponenByJudul(); } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            // tabel mungkin belum ada
        }
        try {
            $khusus = $db->table('iuran_khusus')->select('id, nama')->get()->getResultArray();
            foreach ($khusus as $k) {
                $this->ensureKhusus((string) $k['nama'], (int) $k['id']);
            }
        } catch (\Throwable $e) {
            // ignore
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

        // 1) Cari by ref_id (komponen spesifik)
        $row = null;
        if ($komponenId > 0) {
            $row = $db->table('sub_pos')
                ->where('jenis', self::JENIS_KOMPONEN)
                ->where('ref_id', $komponenId)
                ->get()
                ->getRowArray();
        }

        // 2) Nama sama → pakai 1 sub pos (tarif utama & khusus share)
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
            $aktifInt = $aktif ? 1 : 0;
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

    /**
     * Gabungkan sub pos komponen duplikat (judul sama) ke id terkecil.
     * Mutasi dipindah; baris duplikat dihapus. Aman dipanggil berulang.
     */
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
            'sub_pos_id'    => $subPosId,
            'tipe'          => 'masuk',
            'nominal'       => $nominal,
            'tanggal'       => $tanggal,
            'keterangan'    => $ket !== '' ? $ket : 'Masuk manual',
            'pembayaran_id' => null,
            'dicatat_oleh'  => $userId,
            'dibatalkan'    => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $mid = (int) $db->insertID();
        (new AuditService())->log('sub_pos_masuk', 'sub_pos_mutasi', $mid, null, [
            'sub_pos_id' => $subPosId,
            'judul'      => $sp['judul'],
            'nominal'    => $nominal,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => [
            'mutasi_id' => $mid,
            'sub_pos_id' => $subPosId,
            'saldo' => $this->saldo($subPosId),
        ]];
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
            'sub_pos_id'    => $subPosId,
            'tipe'          => 'keluar',
            'nominal'       => $nominal,
            'tanggal'       => $tanggal,
            'keterangan'    => $ket !== '' ? $ket : 'Keluar manual',
            'pembayaran_id' => null,
            'dicatat_oleh'  => $userId,
            'dibatalkan'    => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $mid = (int) $db->insertID();
        (new AuditService())->log('sub_pos_keluar', 'sub_pos_mutasi', $mid, null, [
            'sub_pos_id' => $subPosId,
            'judul'      => $sp['judul'],
            'nominal'    => $nominal,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => [
            'mutasi_id' => $mid,
            'sub_pos_id' => $subPosId,
            'saldo' => $this->saldo($subPosId),
        ]];
    }
