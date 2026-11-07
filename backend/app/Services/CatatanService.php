<?php

namespace App\Services;

/**
 * Catatan pengurus — 50 baris/halaman.
 *
 * - Data kosong → 1 halaman (50 baris), buka halaman 1.
 * - Buka → halaman berisi baris terakhir yang terisi + scroll ke baris itu.
 * - Baris ke-45..50 terisi → baru buat halaman berikutnya (50 kosong).
 * - Halaman kosong ekstra di belakang dihapus.
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

    /** @return list<array> semua baris order id ASC */
    private function allRowsAsc(): array
    {
        return $this->db()->table('catatan_pengurus')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Rank 1-based baris terakhir yang terisi. 0 jika semua kosong.
     * @return array{rank:int,id:int|null}
     */
    private function lastFilledInfo(): array
    {
        $rows = $this->allRowsAsc();
        $lastRank = 0;
        $lastId = null;
        foreach ($rows as $i => $r) {
            if (!$this->isBlankRow($r)) {
                $lastRank = $i + 1;
                $lastId = (int) $r['id'];
            }
        }
        return ['rank' => $lastRank, 'id' => $lastId];
    }

    /**
     * Hapus halaman kosong ekstra di belakang.
     * - Tidak ada data → sisakan tepat 50 baris.
     * - Ada data, posisi terakhir < 45 di halaman → sisakan sampai akhir halaman itu saja.
     * - Ada data, posisi terakhir >= 45 → sisakan sampai akhir halaman BERIKUTNYA (buffer).
     */
    public function pruneAndPad(?int $userId = null, int $perPage = self::PER_PAGE): void
    {
        $this->ensureTable();
        $perPage = max(10, min(100, $perPage));
        $db = $this->db();
        $rows = $this->allRowsAsc();
        $total = count($rows);
        $filled = $this->lastFilledInfo();
        $lastRank = $filled['rank'];

        if ($lastRank === 0) {
            // Semua kosong: tepat 1 halaman
            $keep = $perPage;
        } else {
            $posInPage = (($lastRank - 1) % $perPage) + 1;
            $pageOfLast = (int) ceil($lastRank / $perPage);
            if ($posInPage >= self::TRIGGER_ROW) {
                $keepPages = $pageOfLast + 1;
            } else {
                $keepPages = $pageOfLast;
            }
            $keep = $keepPages * $perPage;
        }

        if ($total > $keep) {
            // Hapus id setelah baris ke-$keep (by order)
            $idsToDelete = [];
            for ($i = $keep; $i < $total; $i++) {
                $idsToDelete[] = (int) $rows[$i]['id'];
            }
            if ($idsToDelete) {
                $db->table('catatan_pengurus')->whereIn('id', $idsToDelete)->delete();
            }
            $total = $keep;
        }

        while ($total < $keep) {
            $this->insertBlank($userId);
            $total++;
        }
    }

    public function ensureNextPageAfterFill(int $id, ?int $userId = null, int $perPage = self::PER_PAGE): void
    {
        $this->ensureTable();
        $perPage = max(10, min(100, $perPage));
        $rows = $this->allRowsAsc();
        $rank = 0;
        foreach ($rows as $i => $row) {
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
        // Cek baris ini masih terisi
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id && $this->isBlankRow($row)) {
                return;
            }
        }
        $pageOfRow = (int) ceil($rank / $perPage);
        $needTotal = ($pageOfRow + 1) * $perPage;
        $total = count($rows);
        while ($total < $needTotal) {
            $this->insertBlank($userId);
            $total++;
        }
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

    /**
     * @param int $page 0 = auto: halaman baris terakhir terisi (atau 1 jika kosong)
     */
    public function listPage(int $page, int $perPage = self::PER_PAGE, ?int $userId = null): array
    {
        $perPage = max(10, min(100, $perPage));
        $this->pruneAndPad($userId, $perPage);

        $db = $this->db();
        $total = $this->countAll();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $filled = $this->lastFilledInfo();
        $scrollToId = $filled['id'];

        if ($page < 1) {
            // Auto buka: halaman baris terakhir terisi, atau 1
            if ($filled['rank'] > 0) {
                $page = (int) ceil($filled['rank'] / $perPage);
            } else {
                $page = 1;
                $scrollToId = null;
            }
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

        // Jaminan panjang halaman
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

        // scroll_to hanya relevan jika baris ada di halaman ini
        $idsOnPage = array_map(fn ($r) => (int) $r['id'], $rows);
        if ($scrollToId && !in_array($scrollToId, $idsOnPage, true)) {
            $scrollToId = null;
        }
        // Halaman auto kosong → tidak scroll
        if ($filled['rank'] === 0) {
            $scrollToId = null;
        }

        return [
            'rows'         => array_map(fn ($r) => $this->mapRow($r), $rows),
            'page'         => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => $totalPages,
            'scroll_to_id' => $scrollToId,
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

        if ($row && !$this->isBlankRow($row)) {
            $this->ensureNextPageAfterFill($id, $userId, self::PER_PAGE);
        } else {
            // Kalau dikosongkan lagi, rapikan halaman ekstra
            $this->pruneAndPad($userId, self::PER_PAGE);
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
        $n = $this->db()->table('catatan_pengurus')->where('id', $id)->delete();
        return $n ? ['ok' => true] : ['ok' => false, 'message' => 'Gagal hapus.'];
    }
}
