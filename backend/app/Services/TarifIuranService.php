<?php

namespace App\Services;

/**
 * Tarif iuran tetap: utama + khusus (komponen per tarif, assign keluarga).
 */
class TarifIuranService
{
    public function ensureSchema(): void
    {
        $db = \Config\Database::connect();

        $db->query("CREATE TABLE IF NOT EXISTS `tarif_iuran` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `nama` VARCHAR(120) NOT NULL,
          `is_utama` TINYINT(1) NOT NULL DEFAULT 0,
          `aktif` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NULL,
          `updated_at` DATETIME NULL,
          PRIMARY KEY (`id`),
          KEY `idx_tarif_utama` (`is_utama`),
          KEY `idx_tarif_aktif` (`aktif`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `tarif_iuran_keluarga` (
          `keluarga_id` INT UNSIGNED NOT NULL,
          `tarif_id` INT UNSIGNED NOT NULL,
          `created_at` DATETIME NULL,
          PRIMARY KEY (`keluarga_id`),
          KEY `idx_tik_tarif` (`tarif_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Pastikan tabel komponen ada
        (new IuranKomponenService())->ensureTable();

        if (!$this->columnExists('iuran_komponen', 'tarif_id')) {
            $db->query('ALTER TABLE `iuran_komponen` ADD COLUMN `tarif_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`, ADD KEY `idx_komponen_tarif` (`tarif_id`)');
        }

        $utama = $db->table('tarif_iuran')->where('is_utama', 1)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        if (!$utama) {
            $db->table('tarif_iuran')->insert([
                'nama' => 'Tarif utama',
                'is_utama' => 1,
                'aktif' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $utamaId = (int) $db->insertID();
        } else {
            $utamaId = (int) $utama['id'];
        }

        // Komponen lama tanpa tarif_id → taruh di utama
        $db->table('iuran_komponen')
            ->groupStart()
            ->where('tarif_id', null)
            ->orWhere('tarif_id', 0)
            ->groupEnd()
            ->update(['tarif_id' => $utamaId]);
    }

    private function columnExists(string $table, string $col): bool
    {
        $db = \Config\Database::connect();
        try {
            $cols = $db->query('SHOW COLUMNS FROM `' . $table . '`')->getResultArray();
            foreach ($cols as $c) {
                if (strcasecmp((string) ($c['Field'] ?? ''), $col) === 0) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }
        return false;
    }

    public function idUtama(): int
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $row = $db->table('tarif_iuran')->where('is_utama', 1)->get()->getRowArray();
        return (int) ($row['id'] ?? 0);
    }

    public function totalTarif(int $tarifId, bool $aktifSaja = true): int
    {
        $this->ensureSchema();
        if ($tarifId < 1) {
            return 0;
        }
        $db = \Config\Database::connect();
        $q = $db->table('iuran_komponen')->selectSum('nominal', 'total')->where('tarif_id', $tarifId);
        if ($aktifSaja) {
            $q->where('aktif', 1);
        }
        $row = $q->get()->getRowArray();
        return (int) ($row['total'] ?? 0);
    }

    /** Nominal iuran tetap untuk satu keluarga (khusus jika assign, else utama). */
    public function nominalUntukKeluarga(int $keluargaId): int
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $asg = $db->table('tarif_iuran_keluarga tik')
            ->select('tik.tarif_id, t.aktif')
            ->join('tarif_iuran t', 't.id = tik.tarif_id')
            ->where('tik.keluarga_id', $keluargaId)
            ->get()
            ->getRowArray();
        if ($asg && (int) ($asg['aktif'] ?? 0) === 1) {
            return $this->totalTarif((int) $asg['tarif_id'], true);
        }
        return $this->totalTarif($this->idUtama(), true);
    }

    public function tarifIdUntukKeluarga(int $keluargaId): int
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $asg = $db->table('tarif_iuran_keluarga tik')
            ->select('tik.tarif_id, t.aktif')
            ->join('tarif_iuran t', 't.id = tik.tarif_id')
            ->where('tik.keluarga_id', $keluargaId)
            ->get()
            ->getRowArray();
        if ($asg && (int) ($asg['aktif'] ?? 0) === 1) {
            return (int) $asg['tarif_id'];
        }
        return $this->idUtama();
    }

    public function listTarif(): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $rows = $db->table('tarif_iuran')->orderBy('is_utama', 'DESC')->orderBy('id', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $nKk = 0;
            if (!(int) $r['is_utama']) {
                $nKk = (int) $db->table('tarif_iuran_keluarga')->where('tarif_id', $id)->countAllResults();
            }
            $out[] = [
                'id' => $id,
                'nama' => $r['nama'],
                'is_utama' => (bool) $r['is_utama'],
                'aktif' => (bool) $r['aktif'],
                'total' => $this->totalTarif($id, true),
                'jumlah_keluarga' => $nKk,
            ];
        }
        return $out;
    }

    public function detail(int $id): ?array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $r = $db->table('tarif_iuran')->where('id', $id)->get()->getRowArray();
        if (!$r) {
            return null;
        }
        $komponen = (new IuranKomponenService())->listByTarif($id, false);
        $anggota = [];
        if (!(int) $r['is_utama']) {
            $anggota = $this->listAnggota($id);
        }
        return [
            'id' => (int) $r['id'],
            'nama' => $r['nama'],
            'is_utama' => (bool) $r['is_utama'],
            'aktif' => (bool) $r['aktif'],
            'total' => $this->totalTarif($id, true),
            'komponen' => $komponen,
            'anggota' => $anggota,
            'jumlah_keluarga' => count($anggota),
        ];
    }

    public function buatKhusus(string $nama, int $userId): array
    {
        $nama = trim($nama);
        if ($nama === '') {
            return ['ok' => false, 'message' => 'Nama tarif wajib.'];
        }
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('tarif_iuran')->insert([
            'nama' => $nama,
            'is_utama' => 0,
            'aktif' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('buat_tarif_khusus', 'tarif_iuran', $id, null, ['nama' => $nama], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id]];
    }

    public function ubah(int $id, array $data, int $userId): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $row = $db->table('tarif_iuran')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Tarif tidak ditemukan.'];
        }
        $upd = ['updated_at' => date('Y-m-d H:i:s')];
        if (array_key_exists('nama', $data)) {
            $n = trim((string) $data['nama']);
            if ($n === '') {
                return ['ok' => false, 'message' => 'Nama wajib.'];
            }
            $upd['nama'] = $n;
        }
        if (array_key_exists('aktif', $data) && !(int) $row['is_utama']) {
            $upd['aktif'] = !empty($data['aktif']) ? 1 : 0;
            // Nonaktif → lepas semua anggota (balik ke utama)
            if (!(int) $upd['aktif']) {
                $db->table('tarif_iuran_keluarga')->where('tarif_id', $id)->delete();
            }
        }
        $db->table('tarif_iuran')->where('id', $id)->update($upd);
        (new AuditService())->log('ubah_tarif_iuran', 'tarif_iuran', $id, $row, $upd, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function hapusKhusus(int $id, int $userId): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $row = $db->table('tarif_iuran')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Tarif tidak ditemukan.'];
        }
        if ((int) $row['is_utama']) {
            return ['ok' => false, 'message' => 'Tarif utama tidak bisa dihapus.'];
        }
        $db->table('tarif_iuran_keluarga')->where('tarif_id', $id)->delete();
        $komps = $db->table('iuran_komponen')->where('tarif_id', $id)->get()->getResultArray();
        foreach ($komps as $k) {
            (new IuranKomponenService())->delete((int) $k['id'], $userId);
        }
        $db->table('tarif_iuran')->where('id', $id)->delete();
        (new AuditService())->log('hapus_tarif_khusus', 'tarif_iuran', $id, $row, null, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function listAnggota(int $tarifId): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $rows = $db->table('tarif_iuran_keluarga tik')
            ->select('tik.keluarga_id, k.nomor, k.akhiran, k.alamat, k.blok_id, b.nama as blok_nama')
            ->join('keluarga k', 'k.id = tik.keluarga_id')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('tik.tarif_id', $tarifId)
            ->orderBy('b.nama', 'ASC')
            ->orderBy('k.nomor', 'ASC')
            ->get()
            ->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $kid = (int) $r['keluarga_id'];
            $kepala = $db->table('warga')
                ->select('nama')
                ->where('keluarga_id', $kid)
                ->where('status', 'aktif')
                ->where('hubungan', 'Kepala keluarga')
                ->get()
                ->getRowArray();
            if (!$kepala) {
                $kepala = $db->table('warga')
                    ->select('nama')
                    ->where('keluarga_id', $kid)
                    ->where('status', 'aktif')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getRowArray();
            }
            $blok = (string) ($r['blok_nama'] ?? '');
            $nomor = (string) ($r['nomor'] ?? '');
            $akhiran = (string) ($r['akhiran'] ?? '');
            $alamatLabel = $blok !== '' ? ($blok . '-' . $nomor . $akhiran) : (string) ($r['alamat'] ?? '');
            $out[] = [
                'keluarga_id' => $kid,
                'nama' => $kepala['nama'] ?? '—',
                'nama_kepala' => $kepala['nama'] ?? '—',
                'alamat' => $alamatLabel,
                'blok' => $blok,
            ];
        }
        return $out;
    }

    /**
     * Map keluarga_id => info tarif khusus (untuk UI list pilih).
     */
    public function mapKeluargaTarifKhusus(): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $rows = $db->table('tarif_iuran_keluarga tik')
            ->select('tik.keluarga_id, tik.tarif_id, t.nama')
            ->join('tarif_iuran t', 't.id = tik.tarif_id')
            ->where('t.aktif', 1)
            ->where('t.is_utama', 0)
            ->get()
            ->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['keluarga_id']] = [
                'tarif_id' => (int) $r['tarif_id'],
                'nama' => $r['nama'],
            ];
        }
        return $map;
    }

    public function setAnggota(int $tarifId, array $keluargaIds, int $userId, bool $terapkanBulanIni = false): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $tarif = $db->table('tarif_iuran')->where('id', $tarifId)->get()->getRowArray();
        if (!$tarif || (int) $tarif['is_utama']) {
            return ['ok' => false, 'message' => 'Hanya untuk tarif khusus.'];
        }
        if (!(int) $tarif['aktif']) {
            return ['ok' => false, 'message' => 'Tarif nonaktif.'];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        $now = date('Y-m-d H:i:s');

        // Catat anggota lama (yang akan dilepas) supaya nominal mereka balik ke tarif utama
        $oldRows = $db->table('tarif_iuran_keluarga')->select('keluarga_id')->where('tarif_id', $tarifId)->get()->getResultArray();
        $oldIds = array_map(static fn ($r) => (int) $r['keluarga_id'], $oldRows);
        $dilepas = array_values(array_diff($oldIds, $ids));

        // Hapus assign lama untuk tarif ini, lalu set ulang
        $db->table('tarif_iuran_keluarga')->where('tarif_id', $tarifId)->delete();

        foreach ($ids as $kid) {
            // Keluarkan dari tarif khusus lain dulu
            $db->table('tarif_iuran_keluarga')->where('keluarga_id', $kid)->delete();
            $k = $db->table('keluarga')->where('id', $kid)->where('status', 'aktif')->get()->getRowArray();
            if (!$k) {
                continue;
            }
            $db->table('tarif_iuran_keluarga')->insert([
                'keluarga_id' => $kid,
                'tarif_id' => $tarifId,
                'created_at' => $now,
            ]);
        }

        // Real-time: selalu sinkron nominal tagihan bulan ini ke total rincian
        $nApply = 0;
        foreach ($ids as $kid) {
            $nApply += $this->terapkanNominalKeBulanIniUntukKeluarga($kid);
        }
        foreach ($dilepas as $kid) {
            $nApply += $this->terapkanNominalKeBulanIniUntukKeluarga($kid);
        }

        (new AuditService())->log('set_anggota_tarif', 'tarif_iuran', $tarifId, null, [
            'keluarga_ids' => $ids,
            'dilepas' => $dilepas,
            'terapkan_bulan_ini' => true,
            'diterapkan' => $nApply,
        ], 'pengguna', true, $userId);

        return ['ok' => true, 'data' => [
            'jumlah' => count($ids),
            'diterapkan_bulan_ini' => $nApply,
            'detail' => $this->detail($tarifId),
        ]];
    }

    public function tambahAnggota(int $tarifId, int $keluargaId, int $userId, bool $terapkanBulanIni = false): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $tarif = $db->table('tarif_iuran')->where('id', $tarifId)->where('is_utama', 0)->where('aktif', 1)->get()->getRowArray();
        if (!$tarif) {
            return ['ok' => false, 'message' => 'Tarif khusus tidak valid.'];
        }
        $k = $db->table('keluarga')->where('id', $keluargaId)->where('status', 'aktif')->get()->getRowArray();
        if (!$k) {
            return ['ok' => false, 'message' => 'Keluarga tidak ditemukan.'];
        }
        $exist = $db->table('tarif_iuran_keluarga')->where('keluarga_id', $keluargaId)->get()->getRowArray();
        if ($exist && (int) $exist['tarif_id'] !== $tarifId) {
            return ['ok' => false, 'message' => 'Keluarga sudah di tarif khusus lain. Keluarkan dulu.'];
        }
        if (!$exist) {
            $db->table('tarif_iuran_keluarga')->insert([
                'keluarga_id' => $keluargaId,
                'tarif_id' => $tarifId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        // Real-time: selalu sinkron nominal bulan ini
        $n = $this->terapkanNominalKeBulanIniUntukKeluarga($keluargaId);
        return ['ok' => true, 'data' => ['diterapkan' => $n]];
    }

    public function hapusAnggota(int $tarifId, int $keluargaId, int $userId, bool $terapkanBulanIni = false): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $db->table('tarif_iuran_keluarga')
            ->where('tarif_id', $tarifId)
            ->where('keluarga_id', $keluargaId)
            ->delete();
        // Real-time: balik ke tarif utama / nominal baru
        $n = $this->terapkanNominalKeBulanIniUntukKeluarga($keluargaId);
        (new AuditService())->log('hapus_anggota_tarif', 'tarif_iuran', $tarifId, null, [
            'keluarga_id' => $keluargaId,
            'diterapkan' => $n,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['diterapkan' => $n]];
    }

    public function terapkanNominalKeBulanIniUntukKeluarga(int $keluargaId): int
    {
        $bulan = date('Y-m');
        $nominal = $this->nominalUntukKeluarga($keluargaId);
        $db = \Config\Database::connect();
        (new TagihanService())->pastikanTagihanKasKeluarga($keluargaId, $bulan, $nominal);
        $db->table('iuran_tagihan')
            ->where('keluarga_id', $keluargaId)
            ->where('periode', $bulan)
            ->where('jenis', 'kas')
            ->where('dibatalkan', 0)
            ->update(['nominal' => $nominal]);
        return 1;
    }

    public function terapkanNominalKeBulanIniUntukTarif(int $tarifId): int
    {
        $db = \Config\Database::connect();
        $ids = $db->table('tarif_iuran_keluarga')->select('keluarga_id')->where('tarif_id', $tarifId)->get()->getResultArray();
        $n = 0;
        foreach ($ids as $r) {
            $n += $this->terapkanNominalKeBulanIniUntukKeluarga((int) $r['keluarga_id']);
        }
        return $n;
    }

    /** Terapkan nominal per-keluarga ke semua tagihan kas bulan ini (utama + khusus). */
    public function terapkanSemuaBulanIni(int $userId): array
    {
        $this->ensureSchema();
        $bulan = date('Y-m');
        $db = \Config\Database::connect();
        $keluarga = $db->table('keluarga')->where('status', 'aktif')->get()->getResultArray();
        $tagihan = new TagihanService();
        $n = 0;
        foreach ($keluarga as $k) {
            $kid = (int) $k['id'];
            $nominal = $this->nominalUntukKeluarga($kid);
            $tagihan->pastikanTagihanKasKeluarga($kid, $bulan, $nominal);
            $db->table('iuran_tagihan')
                ->where('keluarga_id', $kid)
                ->where('periode', $bulan)
                ->where('jenis', 'kas')
                ->where('dibatalkan', 0)
                ->update(['nominal' => $nominal]);
            $n++;
        }
        (new IuranKomponenService())->syncNominalKas();
        (new AuditService())->log('terapkan_semua_tarif_bulan_ini', 'tarif_iuran', 0, null, [
            'periode' => $bulan,
            'kk' => $n,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => [
            'periode' => $bulan,
            'keluarga_terdampak' => $n,
            'total_utama' => $this->totalTarif($this->idUtama(), true),
        ]];
    }
}
