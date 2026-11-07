<?php

namespace App\Services;

/**
 * Catatan pengurus — grid Excel, 50 baris/halaman.
 *
 * Aturan:
 * - Jumlah baris selalu kelipatan 50 (halaman penuh).
 * - Saat baris ke-45..50 di suatu halaman terisi → halaman berikutnya
 *   (50 baris kosong) otomatis dibuat.
 */
class CatatanService
{
    public const PER_PAGE = 50;

    /** Posisi di halaman (1–50) yang memicu halaman baru */
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

    /** Lengkapi agar total selalu kelipatan $perPage (tiap halaman 50 baris). */
    private function padToFullPages(int $perPage, ?int $userId): void
    {
        $total = $this->countAll();
        if ($total < $perPage) {
            $need = $perPage - $total;
            for ($i = 0; $i < $need; $i++) {
                $this->insertBlank($userId);
            }
            return;
        }
        $mod = $total % $perPage;
        if ($mod !== 0) {
            $need = $perPage - $mod;
            for ($i = 0; $i < $need; $i++) {
                $this->insertBlank($userId);
            }
        }
    }

    /**
     * Pastikan ada halaman penuh 50 baris SETELAH halaman tempat $id berada,
     * jika posisi baris di halaman >= TRIGGER_ROW (45).
     */
    public function ensureNextPageAfterFill(int $id, ?int $userId = null, int $perPage = self::PER_PAGE): void
    {
        $this->ensureTable();
        $perPage = max(10, min(100, $perPage));
        $db = $this->db();

        // Urutan baris = id ASC → ranking 1..N
        $ids = $db->table('catatan_pengurus')->select('id')->orderBy('id', 'ASC')->get()->getResultArray();
        $rank = 0; // 1-based
        foreach ($ids as $i => $row) {
            if ((int) $row['id'] === $id) {
                $rank = $i + 1;
                break;
            }
        }
        if ($rank < 1) {
            return;
        }

        $posInPage = (($rank - 1) % $perPage) + 1; // 1..perPage
        if ($posInPage < self::TRIGGER_ROW) {
            return;
        }

        $pageOfRow = (int) ceil($rank / $perPage);
        $total = count($ids);
        $totalPages = max(1, (int) ceil($total / $perPage));

        // Butuh minimal satu halaman penuh setelah halaman baris ini
        $needPages = $pageOfRow + 1;
        $needTotal = $needPages * $perPage;
        while ($total < $needTotal) {
            $this->insertBlank($userId);
            $total++;
        }
    }

    public function ensureGrid(int $perPage = self::PER_PAGE, ?int $userId = null): void
    {
        $this->ensureTable();
        $perPage = max(10, min(100, $perPage));
        $this->padToFullPages($perPage, $userId);
    }

    private function mapRow(array $r): array
    {
        return [
            'id'          => (int) $r['id'],
            'no'          => (int) $r['id'],
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
        $this->ensureGrid($perPage, $userId);

        $db = $this->db();
        $total = $this->countAll();
        // pad lagi jika perlu
        if ($total % $perPage !== 0 || $total < $perPage) {
            $this->padToFullPages($perPage, $userId);
            $total = $this->countAll();
        }

        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page < 1) {
            $page = $totalPages;
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

        // Jaminan: selalu $perPage baris di response
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
            $rows = $db->table('catatan_pengurus')
                ->orderBy('id', 'ASC')
                ->limit($perPage, $offset)
                ->get()
                ->getResultArray();
        }

        return [
            'rows'        => array_map(fn ($r) => $this->mapRow($r), $rows),
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
        ];
    }

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

        $sql = "SELECT *,
            (CASE
                WHEN nama LIKE ? THEN 40
                WHEN keperluan LIKE ? THEN 30
                WHEN alamat LIKE ? THEN 20
                WHEN keterangan LIKE ? THEN 10
                ELSE 0
            END) AS _rank
            FROM catatan_pengurus
            WHERE (nama LIKE ? OR alamat LIKE ? OR keperluan LIKE ? OR keterangan LIKE ?
               OR CAST(id AS CHAR) = ?)
              AND NOT (
                (tanggal IS NULL OR tanggal = '')
                AND (nama IS NULL OR nama = '')
                AND (alamat IS NULL OR alamat = '')
                AND (keperluan IS NULL OR keperluan = '')
                AND (keterangan IS NULL OR keterangan = '')
                AND (dokumentasi IS NULL OR dokumentasi = '')
              )
            ORDER BY _rank DESC, id ASC
            LIMIT ? OFFSET ?";
        $params = [$like, $like, $like, $like, $like, $like, $like, $like, $q, $limit, $offset];
        $rows = $db->query($sql, $params)->getResultArray();
        return array_map(fn ($r) => $this->mapRow($r), $rows);
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

        // Jika baris ini sudah terisi dan posisinya >= 45 di halaman → buat halaman berikutnya
        if ($row && !$this->isBlankRow($row)) {
            $this->ensureNextPageAfterFill($id, $userId, self::PER_PAGE);
        }

        $mapped = $this->mapRow($row);
        $total = $this->countAll();
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        return [
            'ok'   => true,
            'data' => $mapped,
            // info opsional agar frontend bisa update chevron tanpa reload penuh
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
