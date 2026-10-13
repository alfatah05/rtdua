<?php

namespace App\Services;

class RekeningPembayaranService
{
    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS rekening_pembayaran (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            jenis ENUM('qris','ewallet','bank') NOT NULL,
            nama VARCHAR(100) NOT NULL,
            nomor VARCHAR(100) NULL,
            atas_nama VARCHAR(150) NULL,
            qris_file VARCHAR(255) NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            urutan INT NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_jenis_aktif (jenis, aktif)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $n = (int) $db->table('rekening_pembayaran')->countAllResults();
        if ($n === 0) {
            $now = date('Y-m-d H:i:s');
            $db->table('rekening_pembayaran')->insert([
                'jenis'      => 'qris',
                'nama'       => 'QRIS RT',
                'nomor'      => null,
                'atas_nama'  => null,
                'qris_file'  => null,
                'aktif'      => 1,
                'urutan'     => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        try {
            $cols = $db->query('SHOW COLUMNS FROM pembayaran_permintaan')->getResultArray();
            $names = array_column($cols, 'Field');
            if (!in_array('metode_rekening', $names, true)) {
                $db->query('ALTER TABLE pembayaran_permintaan ADD COLUMN metode_rekening VARCHAR(120) NULL AFTER bank_pengirim');
            }
            if (!in_array('rekening_id', $names, true)) {
                $db->query('ALTER TABLE pembayaran_permintaan ADD COLUMN rekening_id INT UNSIGNED NULL AFTER metode_rekening');
            }
            if (!in_array('tagihan_ids', $names, true)) {
                $db->query('ALTER TABLE pembayaran_permintaan ADD COLUMN tagihan_ids TEXT NULL AFTER rekening_id');
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function listAll(): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $rows = $db->table('rekening_pembayaran')
            ->orderBy('jenis', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        return array_map([$this, 'mapRow'], $rows);
    }

    public function listAktif(): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $rows = $db->table('rekening_pembayaran')
            ->where('aktif', 1)
            ->orderBy('jenis', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        return array_map([$this, 'mapRow'], $rows);
    }

    public function get(int $id): ?array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('rekening_pembayaran')->where('id', $id)->get()->getRowArray();
        return $row ? $this->mapRow($row) : null;
    }

    public function create(array $data, int $userId): array
    {
        $this->ensureTable();
        $jenis = $data['jenis'] ?? '';
        if (!in_array($jenis, ['qris', 'ewallet', 'bank'], true)) {
            return ['ok' => false, 'message' => 'Jenis tidak valid'];
        }
        $nama = trim((string) ($data['nama'] ?? ''));
        if ($nama === '') {
            return ['ok' => false, 'message' => 'Nama wajib'];
        }
        if ($jenis !== 'qris' && trim((string) ($data['nomor'] ?? '')) === '') {
            return ['ok' => false, 'message' => 'Nomor rekening / VA wajib'];
        }
        $now = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();
        $db->table('rekening_pembayaran')->insert([
            'jenis'      => $jenis,
            'nama'       => $nama,
            'nomor'      => $data['nomor'] ?? null,
            'atas_nama'  => $data['atas_nama'] ?? null,
            'qris_file'  => $data['qris_file'] ?? null,
            'aktif'      => isset($data['aktif']) ? (int) (bool) $data['aktif'] : 1,
            'urutan'     => (int) ($data['urutan'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('tambah_rekening', 'rekening_pembayaran', $id, null, ['nama' => $nama, 'jenis' => $jenis], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->get($id)];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $before = $db->table('rekening_pembayaran')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan'];
        }
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['nama', 'nomor', 'atas_nama', 'qris_file'] as $k) {
            if (array_key_exists($k, $data)) {
                $update[$k] = $data[$k];
            }
        }
        if (array_key_exists('aktif', $data)) {
            $update['aktif'] = (int) (bool) $data['aktif'];
        }
        if (array_key_exists('urutan', $data)) {
            $update['urutan'] = (int) $data['urutan'];
        }
        if (array_key_exists('jenis', $data) && in_array($data['jenis'], ['qris', 'ewallet', 'bank'], true)) {
            $update['jenis'] = $data['jenis'];
        }
        $db->table('rekening_pembayaran')->where('id', $id)->update($update);
        (new AuditService())->log('ubah_rekening', 'rekening_pembayaran', $id, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->get($id)];
    }

    public function delete(int $id, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $before = $db->table('rekening_pembayaran')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan'];
        }
        $db->table('rekening_pembayaran')->where('id', $id)->delete();
        (new AuditService())->log('hapus_rekening', 'rekening_pembayaran', $id, $before, null, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    private function mapRow(array $r): array
    {
        return [
            'id'        => (int) $r['id'],
            'jenis'     => $r['jenis'],
            'nama'      => $r['nama'],
            'nomor'     => $r['nomor'],
            'atas_nama' => $r['atas_nama'],
            'qris_file' => $r['qris_file'],
            'aktif'     => (int) $r['aktif'] === 1,
            'urutan'    => (int) $r['urutan'],
        ];
    }
}
