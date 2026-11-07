<?php

namespace App\Services;

/**
 * Catatan pengurus — model sederhana:
 * - 50 baris per halaman (OFFSET/LIMIT)
 * - total_pages = ceil(jumlah_baris / 50)
 * - Halaman buffer kosong ditambah saat baris ke-45..50 terisi
 * - Tidak menghapus baris saat edit field (hindari “hilang halaman”)
 */
class CatatanService
{
    public const PER_PAGE = 50;
    public const TRIGGER_ROW = 45;

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

    private function isBlankRow(array $r): bool
    {
        $tanggal = trim((string) ($r['tanggal'] ?? ''));
        $nama = trim((string) ($r['nama'] ?? ''));
        $alamat = trim((string) ($r['alamat'] ?? ''));
        $kep = trim((string) ($r['keperluan'] ?? ''));
        $ket = trim((string) ($r['keterangan'] ?? ''));
        $dok = trim((string) ($r['dokumentasi'] ?? ''));
        return $tanggal === '' && $nama === '' && $alamat === '' && $kep === '' && $ket === '' && $dok === '';
    }

    private function insertBlank(?int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db()->table('catatan_pengurus')->insert([
            'tanggal'     => null,
            'nama'        => '',
            'alamat'      => '',
            'keperluan'   => '',
            'keterangan'  => '',
            'dokumentasi' => null,
            'created_by'  => $userId,
            'updated_by'  => $userId,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    private function countAll(): int
    {
        return (int) $this->db()->table('catatan_pengurus')->countAllResults();
    }

    /** Minimal 1 halaman penuh (50 baris). */
    private function ensureMinOneFullPage(int $perPage, ?int $userId): void
    {
        $total = $this->countAll();
        if ($total < $perPage) {
            $need = $perPage - $total;
            for ($i = 0; $i < $need; $i++) {
                $this->insertBlank($userId);
            }
        }
    }

    /** Pastikan jumlah baris kelipatan perPage (lengkapi sisa halaman terakhir). */
    private function ensureFullPages(int $perPage, ?int $userId): void
    {
        $this->ensureMinOneFullPage($perPage, $userId);
        $total = $this->countAll();
        $mod = $total % $perPage;
        if ($mod !== 0) {
            for ($i = 0; $i < $perPage - $mod; $i++) {
                $this->insertBlank($userId);
            }
        }
    }

    /** Halaman berikutnya penuh 50 kosong setelah baris terisi di pos >= 45. */
    public function ensureNextPageAfterFill(int $id, ?int $userId = null, int $perPage = self::PER_PAGE): void
    {
        $this->ensureTable();
        $perPage = max(10, min(100, $perPage));
        $db = $this->db();

        $ids = $db->table('catatan_pengurus')->select('id')->orderBy('id', 'ASC')->get()->getResultArray();
        $rank = 0;
        foreach ($ids as $i => $row) {
            if ((int) $row['id'] === $id) {
                $rank = $i + 1;
                break;
            }
        }
        if ($rank < 1) {
            return;
        }

        $posInPage = (($rank - 1) % $perPage) + 1;
        if ($posInPage < self::TRIGGER_ROW) {
            return;
        }

        $pageOfRow = (int) ceil($rank / $perPage);
        $needTotal = ($pageOfRow + 1) * $perPage;
        $total = count($ids);
        while ($total < $needTotal) {
            $this->insertBlank($userId);
            $total++;
        }
    }

    /**
     * @return array{rank:int, page:int, id:?int}
     */
    public function lastFilledInfo(int $perPage = self::PER_PAGE): array
    {
        $this->ensureTable();
        $all = $this->db()->table('catatan_pengurus')->orderBy('id', 'ASC')->get()->getResultArray();
        $lastRank = 0;
        $lastId = null;
        foreach ($all as $i => $r) {
            if (!$this->isBlankRow($r)) {
                $lastRank = $i + 1;
                $lastId = (int) $r['id'];
            }
        }
        if ($lastRank === 0) {
            return ['rank' => 0, 'page' => 1, 'id' => null];
        }
        $page = (int) ceil($lastRank / $perPage);
        return ['rank' => $lastRank, 'page' => $page, 'id' => $lastId];
    }

    private function mapRow(array $r, ?int $no = null): array
    {
        return [
            'id'          => (int) $r['id'],
            'no'          => $no !== null ? $no : (int) $r['id'],
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

    public function listPage(int $page, int $perPage = self::PER_PAGE, ?int $userId = null): array
    {
        $perPage = max(10, min(100, $perPage));
        $this->ensureTable();
        $this->ensureFullPages($perPage, $userId);

        $db = $this->db();
        $total = $this->countAll();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $focus = $this->lastFilledInfo($perPage);

        // page = 0 → halaman fokus (baris terakhir terisi)
        if ($page < 1) {
            $page = (int) $focus['page'];
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        if ($page < 1) {
            $page = 1;
        }

        $offset = ($page - 1) * $perPage;
        $rows = $db->table('catatan_pengurus')
            ->orderBy('id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        // Jaminan 50 baris di halaman terakhir
        if (count($rows) < $perPage) {
            $need = $perPage - count($rows);
            for ($i = 0; $i < $need; $i++) {
                $this->insertBlank($userId);
            }
            $total = $this->countAll();
            $totalPages = max(1, (int) ceil($total / $perPage));
            $offset = ($page - 1) * $perPage;
            $rows = $db->table('catatan_pengurus')
                ->orderBy('id', 'ASC')
                ->limit($perPage, $offset)
                ->get()
                ->getResultArray();
        }

        $mapped = [];
        foreach ($rows as $i => $r) {
            $mapped[] = $this->mapRow($r, $offset + $i + 1);
        }

        return [
            'rows'         => $mapped,
            'page'         => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => $totalPages,
            'focus_row_id' => $focus['id'],
            'focus_rank'   => $focus['rank'],
        ];
    }

    public functionsearch(string $q, int $limit = 200, int $offset = 0): array
    {
        $this->ensureTable();
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(300, $limit));
        $offset = max(0, $offset);

        $builder = $this->db()->table('catatan_pengurus');
        $builder->groupStart()
            ->like('nama', $q)
            ->orLike('alamat', $q)
            ->orLike('keperluan', $q)
            ->orLike('keterangan', $q);
        if (ctype_digit($q)) {
            $builder->orWhere('id', (int) $q);
        }
        $builder->groupEnd();
        $builder->orderBy('id', 'ASC');
        $builder->limit($limit, $offset);
        $rows = $builder->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            if ($this->isBlankRow($r)) {
                continue;
            }
            $out[] = $this->mapRow($r);
        }
        return $out;
    }

    public function createEmpty(?int $userId): array
    {
        $this->ensureTable();
        $this->insertBlank($userId);
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

        // Hanya tambah halaman buffer bila perlu — JANGAN hapus baris
        if ($row && !$this->isBlankRow($row)) {
            $this->ensureNextPageAfterFill($id, $userId, self::PER_PAGE);
        }

        $mapped = $this->mapRow($row);
        $total = $this->countAll();
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        return [
            'ok'   => true,
            'data' => $mapped,
            'meta' => [
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ];
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
