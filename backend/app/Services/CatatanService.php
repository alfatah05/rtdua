<?php

namespace App\Services;

/**
 * Catatan pengurus — 50 baris/halaman (OFFSET/LIMIT).
 */
class CatatanService
{
    public const PER_PAGE = 50;
    public const TRIGGER_ROW = 45;

    private const FIELDS = ['tanggal', 'nama', 'alamat', 'keperluan', 'keterangan', 'dokumentasi'];

    /** @var bool */
    private $ensured = false;

    private function db()
    {
        return \Config\Database::connect();
    }

    public function ensureTable()
    {
        if ($this->ensured) {
            return;
        }
        $db = $this->db();
        try {
            if ($db->tableExists('catatan_pengurus')) {
                $this->ensured = true;
                return;
            }
        } catch (\Throwable $e) {
            // lanjut coba create
        }
        try {
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
        } catch (\Throwable $e) {
            // tabel mungkin sudah ada (race)
        }
        $this->ensured = true;
    }

    private function isBlankRow(array $r)
    {
        $tanggal = trim((string) ($r['tanggal'] ?? ''));
        $nama = trim((string) ($r['nama'] ?? ''));
        $alamat = trim((string) ($r['alamat'] ?? ''));
        $kep = trim((string) ($r['keperluan'] ?? ''));
        $ket = trim((string) ($r['keterangan'] ?? ''));
        $dok = trim((string) ($r['dokumentasi'] ?? ''));
        return $tanggal === '' && $nama === '' && $alamat === '' && $kep === '' && $ket === '' && $dok === '';
    }

    private function insertBlank($userId)
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

    private function countAll()
    {
        $row = $this->db()->query('SELECT COUNT(*) AS c FROM catatan_pengurus')->getRowArray();
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Ambil baris per halaman.
     * MySQL: LIMIT offset, count  (bukan hanya LIMIT n OFFSET m — lebih kompatibel).
     */
    private function fetchPageRows($perPage, $offset)
    {
        $perPage = max(1, (int) $perPage);
        $offset  = max(0, (int) $offset);
        $sql = 'SELECT * FROM catatan_pengurus ORDER BY id ASC LIMIT ' . $offset . ', ' . $perPage;
        return $this->db()->query($sql)->getResultArray();
    }

    private function ensureMinRows($perPage, $userId)
    {
        $total = $this->countAll();
        if ($total >= $perPage) {
            return;
        }
        $need = $perPage - $total;
        for ($i = 0; $i < $need; $i++) {
            $this->insertBlank($userId);
        }
    }

    public function ensureNextPageAfterFill($id, $userId = null, $perPage = self::PER_PAGE)
    {
        $this->ensureTable();
        $perPage = max(10, min(100, (int) $perPage));

        $ids = $this->db()->query('SELECT id FROM catatan_pengurus ORDER BY id ASC')->getResultArray();
        $rank = 0;
        foreach ($ids as $i => $row) {
            if ((int) $row['id'] === (int) $id) {
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

    public function lastFilledInfo($perPage = self::PER_PAGE)
    {
        $this->ensureTable();
        $all = $this->db()->query('SELECT * FROM catatan_pengurus ORDER BY id ASC')->getResultArray();
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

    private function mapRow(array $r, $no = null)
    {
        return [
            'id'          => (int) $r['id'],
            'no'          => $no !== null ? (int) $no : (int) $r['id'],
            'tanggal'     => !empty($r['tanggal']) ? $r['tanggal'] : null,
            'nama'        => $r['nama'] ?? '',
            'alamat'      => $r['alamat'] ?? '',
            'keperluan'   => $r['keperluan'] ?? '',
            'keterangan'  => $r['keterangan'] ?? '',
            'dokumentasi' => $r['dokumentasi'] ?? null,
            'created_at'  => $r['created_at'] ?? null,
            'updated_at'  => $r['updated_at'] ?? null,
        ];
    }

    public function listPage($page, $perPage = self::PER_PAGE, $userId = null)
    {
        $perPage = max(10, min(100, (int) $perPage));
        $page = (int) $page;
        $this->ensureTable();
        $this->ensureMinRows($perPage, $userId);

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
        $rows = $this->fetchPageRows($perPage, $offset);

        // Lengkapi sisa baris halaman terakhir saja (maks perPage)
        if (count($rows) < $perPage) {
            $need = $perPage - count($rows);
            for ($i = 0; $i < $need; $i++) {
                $this->insertBlank($userId);
            }
            $total = $this->countAll();
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;
            $rows = $this->fetchPageRows($perPage, $offset);
        }

        $mapped = [];
        foreach ($rows as $i => $r) {
            $mapped[] = $this->mapRow($r, $offset + $i + 1);
        }

        $firstId = null;
        $lastId = null;
        if (!empty($mapped)) {
            $firstId = $mapped[0]['id'];
            $lastId = $mapped[count($mapped) - 1]['id'];
        }

        return [
            'rows'         => $mapped,
            'page'         => $page,
            'per_page'     => $perPage,
            'offset'       => $offset,
            'total'        => $total,
            'total_pages'  => $totalPages,
            'first_id'     => $firstId,
            'last_id'      => $lastId,
            'focus_row_id' => $focus['id'],
            'focus_rank'   => $focus['rank'],
        ];
    }

    public function search($q, $limit = 200, $offset = 0)
    {
        $this->ensureTable();
        $q = trim((string) $q);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(300, (int) $limit));
        $offset = max(0, (int) $offset);

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
        $builder->limit($limit);
        if ($offset > 0) {
            $builder->offset($offset);
        }
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

    public function createEmpty($userId)
    {
        $this->ensureTable();
        $this->insertBlank($userId);
        $id = (int) $this->db()->insertID();
        $row = $this->db()->table('catatan_pengurus')->where('id', $id)->get()->getRowArray();
        return $this->mapRow($row ?: ['id' => $id]);
    }

    public function updateField($id, $field, $value, $userId)
    {
        $this->ensureTable();
        $id = (int) $id;
        $field = (string) $field;
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

    public function deleteRow($id)
    {
        $this->ensureTable();
        $id = (int) $id;
        if ($id < 1) {
            return ['ok' => false, 'message' => 'ID tidak valid.'];
        }
        $n = $this->db()->table('catatan_pengurus')->where('id', $id)->delete();
        return $n ? ['ok' => true] : ['ok' => false, 'message' => 'Gagal hapus.'];
    }
}
