<?php

namespace App\Services;

/**
 * Sub pos iuran: buku saldo terpisah per komponen / judul khusus / denda.
 * T1: tabel + ensure + list saldo. Belum mengubah alur bayar (masih ke kas).
 */
class SubPosService
{
    public const JENIS_KOMPONEN = 'komponen';
    public const JENIS_KHUSUS   = 'khusus';
    public const JENIS_DENDA    = 'denda';

    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS `sub_pos` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `jenis` VARCHAR(16) NOT NULL,
          `ref_id` INT UNSIGNED DEFAULT NULL,
          `judul` VARCHAR(255) NOT NULL,
          `aktif` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NULL,
          `updated_at` DATETIME NULL,
          PRIMARY KEY (`id`),
          KEY `idx_sub_pos_jenis_ref` (`jenis`, `ref_id`),
          KEY `idx_sub_pos_jenis_judul` (`jenis`, `judul`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `sub_pos_mutasi` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `sub_pos_id` INT UNSIGNED NOT NULL,
          `tipe` VARCHAR(16) NOT NULL,
          `nominal` INT UNSIGNED NOT NULL DEFAULT 0,
          `tanggal` DATE NOT NULL,
          `keterangan` VARCHAR(512) DEFAULT NULL,
          `pembayaran_id` INT UNSIGNED DEFAULT NULL,
          `kas_transaksi_id` INT UNSIGNED DEFAULT NULL,
          `pos_kas_id` INT UNSIGNED DEFAULT NULL,
          `dicatat_oleh` INT UNSIGNED DEFAULT NULL,
          `dibatalkan` TINYINT(1) NOT NULL DEFAULT 0,
          `dibatalkan_oleh` INT UNSIGNED DEFAULT NULL,
          `dibatalkan_pada` DATETIME DEFAULT NULL,
          `alasan_batal` VARCHAR(255) DEFAULT NULL,
          `created_at` DATETIME NULL,
          PRIMARY KEY (`id`),
          KEY `idx_sub_pos_mutasi_pos` (`sub_pos_id`, `dibatalkan`),
          KEY `idx_sub_pos_mutasi_bayar` (`pembayaran_id`),
          KEY `idx_sub_pos_mutasi_kas` (`kas_transaksi_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Pastikan sub pos ada untuk semua komponen + denda.
     */
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
        $row = $db->table('sub_pos')
            ->where('jenis', self::JENIS_DENDA)
            ->get()
            ->getRowArray();
        if ($row) {
            return $this->map($row);
        }
        $now = date('Y-m-d H:i:s');
        $db->table('sub_pos')->insert([
            'jenis'      => self::JENIS_DENDA,
            'ref_id'     => null,
            'judul'      => 'Denda ronda',
            'aktif'      => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        return $this->detail($id) ?? [
            'id'     => $id,
            'jenis'  => self::JENIS_DENDA,
            'ref_id' => null,
            'judul'  => 'Denda ronda',
            'aktif'  => true,
            'saldo'  => 0,
        ];
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
        $row = $db->table('sub_pos')
            ->where('jenis', self::JENIS_KOMPONEN)
            ->where('ref_id', $komponenId)
            ->get()
            ->getRowArray();
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
            'jenis'      => self::JENIS_KOMPONEN,
            'ref_id'     => $komponenId,
            'judul'      => mb_substr($judul, 0, 255),
            'aktif'      => $aktif ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        return $this->detail($id) ?? [
            'id'     => $id,
            'jenis'  => self::JENIS_KOMPONEN,
            'ref_id' => $komponenId,
            'judul'  => $judul,
            'aktif'  => $aktif,
            'saldo'  => 0,
        ];
    }

    /**
     * Iuran khusus: 1 sub pos per judul (repost reuse judul sama).
     */
    public function ensureKhusus(string $judul, ?int $refIuranKhususId = null): array
    {
        $this->ensureTable();
        $judul = trim($judul);
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Judul khusus wajib'];
        }
        $db = \Config\Database::connect();
        $row = $db->table('sub_pos')
            ->where('jenis', self::JENIS_KHUSUS)
            ->where('judul', $judul)
            ->get()
            ->getRowArray();
        $now = date('Y-m-d H:i:s');
        if ($row) {
            if ($refIuranKhususId && empty($row['ref_id'])) {
                $db->table('sub_pos')->where('id', (int) $row['id'])->update([
                    'ref_id'     => $refIuranKhususId,
                    'updated_at' => $now,
                ]);
                $row['ref_id'] = $refIuranKhususId;
            }
            return $this->map($row);
        }
        $db->table('sub_pos')->insert([
            'jenis'      => self::JENIS_KHUSUS,
            'ref_id'     => $refIuranKhususId,
            'judul'      => mb_substr($judul, 0, 255),
            'aktif'      => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        return $this->detail($id) ?? [
            'id'     => $id,
            'jenis'  => self::JENIS_KHUSUS,
            'ref_id' => $refIuranKhususId,
            'judul'  => $judul,
            'aktif'  => true,
            'saldo'  => 0,
        ];
    }

    public function saldo(int $subPosId): int
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $masuk = (int) ($db->table('sub_pos_mutasi')
            ->selectSum('nominal', 'total')
            ->where('sub_pos_id', $subPosId)
            ->where('dibatalkan', 0)
            ->whereIn('tipe', ['masuk'])
            ->get()
            ->getRowArray()['total'] ?? 0);
        $keluar = (int) ($db->table('sub_pos_mutasi')
            ->selectSum('nominal', 'total')
            ->where('sub_pos_id', $subPosId)
            ->where('dibatalkan', 0)
            ->whereIn('tipe', ['keluar', 'alokasi'])
            ->get()
            ->getRowArray()['total'] ?? 0);
        return $masuk - $keluar;
    }

    /**
     * @return list<array>
     */
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

    private function map(array $r): array
    {
        return [
            'id'         => (int) $r['id'],
            'jenis'      => (string) ($r['jenis'] ?? ''),
            'ref_id'     => isset($r['ref_id']) && $r['ref_id'] !== null && $r['ref_id'] !== ''
                ? (int) $r['ref_id']
                : null,
            'judul'      => (string) ($r['judul'] ?? ''),
            'aktif'      => (int) ($r['aktif'] ?? 1) === 1,
            'created_at' => $r['created_at'] ?? null,
            'updated_at' => $r['updated_at'] ?? null,
        ];
    }
}
