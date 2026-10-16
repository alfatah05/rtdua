<?php

namespace App\Services;

/**
 * Pos kas (multi-kas): Kas RT, Kas sosial, dll.
 * jatah_bulanan = target nominal dari iuran per bulan (bukan saldo).
 */
class KasPosService
{
    public function ensureDefault(): void
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('kas_pos')) {
            return;
        }
        $n = (int) $db->table('kas_pos')->countAllResults();
        if ($n < 1) {
            $db->table('kas_pos')->insert([
                'nama'          => 'Kas RT',
                'jatah_bulanan' => 0,
                'aktif'         => 1,
                'urutan'        => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** Pos aktif pertama (fallback transaksi otomatis). */
    public function defaultPosId(): ?int
    {
        $this->ensureDefault();
        $db = \Config\Database::connect();
        if (!$db->tableExists('kas_pos')) {
            return null;
        }
        $row = $db->table('kas_pos')
            ->where('aktif', 1)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();
        return $row ? (int) $row['id'] : null;
    }

    /**
     * @param bool $aktifSaja
     * @return list<array{id:int,nama:string,jatah_bulanan:int,aktif:bool,urutan:int,saldo:int}>
     */
    public function list(bool $aktifSaja = false): array
    {
        $this->ensureDefault();
        $db = \Config\Database::connect();
        if (!$db->tableExists('kas_pos')) {
            return [];
        }
        $q = $db->table('kas_pos');
        if ($aktifSaja) {
            $q->where('aktif', 1);
        }
        $rows = $q->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $out[] = [
                'id'            => $id,
                'nama'          => $r['nama'],
                'jatah_bulanan' => (int) $r['jatah_bulanan'],
                'aktif'         => (int) $r['aktif'] === 1,
                'urutan'        => (int) $r['urutan'],
                'saldo'         => $this->saldoPos($id),
            ];
        }
        return $out;
    }

    public function saldoPos(int $posId): int
    {
        $db = \Config\Database::connect();
        $hasCol = $this->hasKasPosColumn();
        $masukQ = $db->table('kas_transaksi')
            ->selectSum('nominal')
            ->where('tipe', 'masuk')
            ->where('dibatalkan', 0);
        $keluarQ = $db->table('kas_transaksi')
            ->selectSum('nominal')
            ->where('tipe', 'keluar')
            ->where('dibatalkan', 0);
        if ($hasCol) {
            $masukQ->where('kas_pos_id', $posId);
            $keluarQ->where('kas_pos_id', $posId);
        } else {
            // Skema lama: semua dianggap pos default
            $def = $this->defaultPosId();
            if ($def !== $posId) {
                return 0;
            }
        }
        $masuk = (int) ($masukQ->get()->getRow()->nominal ?? 0);
        $keluar = (int) ($keluarQ->get()->getRow()->nominal ?? 0);
        return $masuk - $keluar;
    }

    public function create(array $data, int $userId): array
    {
        $nama = trim((string) ($data['nama'] ?? ''));
        if ($nama === '') {
            return ['ok' => false, 'message' => 'Nama pos wajib.'];
        }
        $jatah = max(0, (int) ($data['jatah_bulanan'] ?? 0));
        $db = \Config\Database::connect();
        if (!$db->tableExists('kas_pos')) {
            return ['ok' => false, 'message' => 'Tabel kas_pos belum ada. Jalankan sql/025_kas_pos.sql'];
        }
        $maxU = (int) ($db->table('kas_pos')->selectMax('urutan')->get()->getRow()->urutan ?? 0);
        $db->table('kas_pos')->insert([
            'nama'          => $nama,
            'jatah_bulanan' => $jatah,
            'aktif'         => 1,
            'urutan'        => $maxU + 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('kas_pos_buat', 'kas_pos', $id, null, [
            'nama' => $nama,
            'jatah_bulanan' => $jatah,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id]];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $row = $db->table('kas_pos')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Pos tidak ditemukan.'];
        }
        $upd = ['updated_at' => date('Y-m-d H:i:s')];
        if (array_key_exists('nama', $data)) {
            $nama = trim((string) $data['nama']);
            if ($nama === '') {
                return ['ok' => false, 'message' => 'Nama tidak boleh kosong.'];
            }
            $upd['nama'] = $nama;
        }
        if (array_key_exists('jatah_bulanan', $data)) {
            $upd['jatah_bulanan'] = max(0, (int) $data['jatah_bulanan']);
        }
        if (array_key_exists('aktif', $data)) {
            $upd['aktif'] = !empty($data['aktif']) ? 1 : 0;
        }
        if (array_key_exists('urutan', $data)) {
            $upd['urutan'] = (int) $data['urutan'];
        }
        $db->table('kas_pos')->where('id', $id)->update($upd);
        (new AuditService())->log('kas_pos_ubah', 'kas_pos', $id, $row, $upd, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id]];
    }

    /** Soft-nonaktif (jangan hard-delete bila sudah ada transaksi). */
    public function nonaktifkan(int $id, int $userId): array
    {
        $db = \Config\Database::connect();
        $row = $db->table('kas_pos')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Pos tidak ditemukan.'];
        }
        $aktif = (int) $db->table('kas_pos')->where('aktif', 1)->countAllResults();
        if ($aktif <= 1 && (int) $row['aktif'] === 1) {
            return ['ok' => false, 'message' => 'Minimal satu pos kas harus aktif.'];
        }
        $db->table('kas_pos')->where('id', $id)->update([
            'aktif'      => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        (new AuditService())->log('kas_pos_nonaktif', 'kas_pos', $id, $row, ['aktif' => 0], 'pengguna', true, $userId);
        return ['ok' => true];
    }

    private function hasKasPosColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $db = \Config\Database::connect();
        try {
            $fields = $db->getFieldNames('kas_transaksi');
            $cached = in_array('kas_pos_id', $fields, true);
        } catch (\Throwable $e) {
            $cached = false;
        }
        return $cached;
    }
}
