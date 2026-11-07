<?php

namespace App\Services;

/**
 * Catatan pengurus standalone (bukan keuangan).
 * Tabel dibuat otomatis saat first use — tidak perlu import SQL manual.
 */
class CatatanService
{
    private bool $ensured = false;

    private function db()
    {
        return \Config\Database::connect();
    }

    public function ensureTable(): void
    {
        if ($this->ensured) {
            return;
        }
        $db = $this->db();
        if ($db->tableExists('catatan_pengurus')) {
            $this->ensured = true;
            return;
        }
        $forge = \Config\Database::forge();
        $forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tanggal' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'alamat' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'keperluan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'dokumentasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('tanggal');
        $forge->createTable('catatan_pengurus', true);
        $this->ensured = true;
    }

    private function mapRow(array $r): array
    {
        return [
            'id'          => (int) $r['id'],
            'no'          => (int) $r['id'], // nomor asli = id
            'tanggal'     => $r['tanggal'] ?: null,
            'nama'        => $r['nama'] ?? '',
            'alamat'      => $r['alamat'] ?? '',
            'keperluan'   => $r['keperluan'] ?? '',
            'keterangan'  => $r['keterangan'] ?? '',
            'dokumentasi' => $r['dokumentasi'] ?? null,
            'created_at'  => $r['created_at'] ?? null,
            'updated_at'  => $r['updated_at'] ?? null,
        ];
    }

    /**
     * @return array{rows: list, page: int, per_page: int, total: int, total_pages: int}
     */
    public function listPage(int $page, int $perPage = 50): array
    {
        $this->ensureTable();
        $perPage = max(10, min(100, $perPage));
        $db = $this->db();
        $total = (int) $db->table('catatan_pengurus')->countAllResults();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page < 1) {
            $page = $totalPages; // page terakhir
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $rows = $db->table('catatan_pengurus')
            ->orderBy('id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return [
            'rows'         => array_map(fn ($r) => $this->mapRow($r), $rows),
            'page'         => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => $totalPages,
        ];
    }

    /**
     * Search global, ranking sederhana, untuk infinite scroll.
     * @return list
     */
    public function search(string $q, int $limit = 200, int $offset = 0): array
    {
        $this->ensureTable();
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(300, $limit));
        $offset = max(0, $offset);
        $db = $this->db();
        $like = '%' . $db->escapeLikeString($q) . '%';

        // Ranking kasar: cocok di nama > keperluan > alamat > keterangan
        $sql = "SELECT *,
            (CASE
                WHEN nama LIKE ? THEN 40
                WHEN keperluan LIKE ? THEN 30
                WHEN alamat LIKE ? THEN 20
                WHEN keterangan LIKE ? THEN 10
                ELSE 0
            END) AS _rank
            FROM catatan_pengurus
            WHERE nama LIKE ? OR alamat LIKE ? OR keperluan LIKE ? OR keterangan LIKE ?
               OR CAST(id AS CHAR) = ?
            ORDER BY _rank DESC, id ASC
            LIMIT ? OFFSET ?";
        $params = [$like, $like, $like, $like, $like, $like, $like, $like, $q, $limit, $offset];
        $rows = $db->query($sql, $params)->getResultArray();
        return array_map(fn ($r) => $this->mapRow($r), $rows);
    }

    public function createEmpty(?int $userId): array
    {
        $this->ensureTable();
        $now = date('Y-m-d H:i:s');
        $this->db()->table('catatan_pengurus')->insert([
            'tanggal'    => date('Y-m-d'),
            'nama'       => '',
            'alamat'     => '',
            'keperluan'  => '',
            'keterangan' => '',
            'dokumentasi'=> null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $this->db()->insertID();
        $row = $this->db()->table('catatan_pengurus')->where('id', $id)->get()->getRowArray();
        return $this->mapRow($row ?: ['id' => $id]);
    }

    private const FIELDS = ['tanggal', 'nama', 'alamat', 'keperluan', 'keterangan', 'dokumentasi'];

    public function updateField(int $id, string $field, $value, ?int $userId): array
    {
        $this->ensureTable();
        if ($id < 1 || !in_array($field, self::FIELDS, true)) {
            return ['ok' => false, 'message' => 'Field tidak valid.'];
        }
        $db = $this->db();
        $exist = $db->table('catatan_pengurus')->where('id', $id)->get()->getRowArray();
        if (!$exist) {
            return ['ok' => false, 'message' => 'Baris tidak ditemukan.'];
        }

        if ($field === 'tanggal') {
            $value = $value ? substr((string) $value, 0, 10) : null;
            if ($value === '') {
                $value = null;
            }
        } elseif ($field === 'dokumentasi') {
            $value = $value ? (string) $value : null;
        } else {
            $value = is_string($value) ? $value : (string) $value;
        }

        $db->table('catatan_pengurus')->where('id', $id)->update([
            $field       => $value,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $row = $db->table('catatan_pengurus')->where('id', $id)->get()->getRowArray();
        return ['ok' => true, 'data' => $this->mapRow($row)];
    }

    public function deleteRow(int $id): array
    {
        $this->ensureTable();
        if ($id < 1) {
            return ['ok' => false, 'message' => 'ID tidak valid.'];
        }
        $db = $this->db();
        $n = $db->table('catatan_pengurus')->where('id', $id)->delete();
        return $n ? ['ok' => true] : ['ok' => false, 'message' => 'Gagal hapus.'];
    }
}
