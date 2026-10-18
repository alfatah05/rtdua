<?php

namespace App\Services;

class KaderKontenService
{
    private const KATEGORI = ['posyandu', 'pengajian', 'lingkungan'];
    private const STATUS = ['direncanakan', 'berjalan', 'selesai'];

    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('kader_konten')) {
            return;
        }
        $db->query("CREATE TABLE IF NOT EXISTS `kader_konten` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `kategori` VARCHAR(32) NOT NULL,
          `judul` VARCHAR(255) NOT NULL,
          `banner` VARCHAR(512) DEFAULT NULL,
          `isi_html` MEDIUMTEXT NULL,
          `status` VARCHAR(32) NOT NULL DEFAULT 'direncanakan',
          `created_at` DATETIME NULL,
          `updated_at` DATETIME NULL,
          PRIMARY KEY (`id`),
          KEY `idx_kader_kategori` (`kategori`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function list(?string $kategori = null): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $b = $db->table('kader_konten')->orderBy('id', 'DESC');
        if ($kategori && in_array($kategori, self::KATEGORI, true)) {
            $b->where('kategori', $kategori);
        }
        $rows = $b->get()->getResultArray();
        return array_map([$this, 'map'], $rows);
    }

    public function detail(int $id): ?array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $r = $db->table('kader_konten')->where('id', $id)->get()->getRowArray();
        return $r ? $this->map($r) : null;
    }

    public function buat(array $data, int $userId): array
    {
        $this->ensureTable();
        $judul = trim((string) ($data['judul'] ?? ''));
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Judul wajib.'];
        }
        $kategori = (string) ($data['kategori'] ?? '');
        if (!in_array($kategori, self::KATEGORI, true)) {
            return ['ok' => false, 'message' => 'Kategori tidak valid.'];
        }
        $status = $data['status'] ?? 'direncanakan';
        if (!in_array($status, self::STATUS, true)) {
            $status = 'direncanakan';
        }
        $isi = $this->bersihkanKonten((string) ($data['isi_html'] ?? ''));
        $db = \Config\Database::connect();
        $db->table('kader_konten')->insert([
            'kategori'   => $kategori,
            'judul'      => mb_substr($judul, 0, 255),
            'banner'     => $data['banner'] ?? null,
            'isi_html'   => $isi,
            'status'     => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('tambah_kader_konten', 'kader_konten', $id, null, ['judul' => $judul, 'kategori' => $kategori], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function ubah(int $id, array $data, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $before = $db->table('kader_konten')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $update = [];
        if (isset($data['judul'])) {
            $update['judul'] = mb_substr(trim((string) $data['judul']), 0, 255);
        }
        if (array_key_exists('banner', $data)) {
            $update['banner'] = $data['banner'];
        }
        if (array_key_exists('isi_html', $data)) {
            $update['isi_html'] = $this->bersihkanKonten((string) $data['isi_html']);
        }
        if (isset($data['status']) && in_array($data['status'], self::STATUS, true)) {
            $update['status'] = $data['status'];
        }
        if (isset($data['kategori']) && in_array($data['kategori'], self::KATEGORI, true)) {
            $update['kategori'] = $data['kategori'];
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('kader_konten')->where('id', $id)->update($update);
        (new AuditService())->log('ubah_kader_konten', 'kader_konten', $id, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function hapus(int $id, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $before = $db->table('kader_konten')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $db->table('kader_konten')->where('id', $id)->delete();
        (new AuditService())->log('hapus_kader_konten', 'kader_konten', $id, $before, null, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    private function map(array $r): array
    {
        return [
            'id'         => (int) $r['id'],
            'kategori'   => $r['kategori'] ?? '',
            'judul'      => $r['judul'] ?? '',
            'banner'     => $r['banner'] ?? null,
            'isi_html'   => $r['isi_html'] ?? '',
            'status'     => $r['status'] ?? 'direncanakan',
            'created_at' => $r['created_at'] ?? null,
            'updated_at' => $r['updated_at'] ?? null,
        ];
    }

    private function bersihkanKonten(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? '';
        $html = preg_replace('#on\w+\s*=\s*("|\').*?\1#i', '', $html) ?? '';
        return $html;
    }
}
