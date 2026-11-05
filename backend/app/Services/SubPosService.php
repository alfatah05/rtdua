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
        return $this->detail($id) ?? ['id' => $id, 'jenis' => self::JENIS_KOMPONEN, 'ref_id' => $komponenId > 0 ? $komponenId : null, 'judul' => $judul, 'aktif' => $aktif, 'saldo' => 0];
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
                // Split menurut komponen tarif KELUARGA yang bayar (utama / khusus)
                foreach ($this->splitKasKeKomponen($jumlah, $keluargaId) as $spId => $nom) {
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
            foreach ($this->splitKasKeKomponen($sisaBayar, $keluargaId) as $spId => $nom) {
                $agregat[$spId] = ($agregat[$spId] ?? 0) + $nom;
            }
        }
        $labelBayar = $this->labelPembayar($keluargaId);
        $ketDasar = $labelBayar !== ''
            ? ('Pembayaran dari ' . $labelBayar)
            : ('Pembayaran #' . $pembayaranId);
        $now = date('Y-m-d H:i:s');
        foreach ($agregat as $spId => $nominal) {
            $nominal = (int) $nominal;
            if ($nominal < 1 || (int) $spId < 1) {
                continue;
            }
            $db->table('sub_pos_mutasi')->insert([
                'sub_pos_id' => (int) $spId, 'tipe' => 'masuk', 'nominal' => $nominal,
                'tanggal' => $tanggal, 'keterangan' => $ketDasar,
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

    // NOTE: remaining methods (catatMasuk, catatKeluar, alokasiKePosKas, listMutasi, batalkanMutasi, rekapBulan, splitKasKeKomponen, labelPembayar, infoKhususDariTagihan, hasKasPosColumn, map) continue below in full file — this push was truncated by length; use local fixed file.
}