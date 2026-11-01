<?php

namespace App\Services;

/**
 * Rincian komponen iuran tetap bulanan (mis. keamanan, kebersihan).
 * Total disinkronkan ke pengaturan.nominal_kas agar tagihan kas tetap jalan.
 */
class IuranKomponenService
{
    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS iuran_komponen (\n            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            nama VARCHAR(120) NOT NULL,\n            nominal INT UNSIGNED NOT NULL DEFAULT 0,\n            urutan INT NOT NULL DEFAULT 0,\n            aktif TINYINT(1) NOT NULL DEFAULT 1,\n            created_at DATETIME NULL,\n            updated_at DATETIME NULL,\n            INDEX idx_aktif_urutan (aktif, urutan)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $n = (int) $db->table('iuran_komponen')->countAllResults();
        if ($n === 0) {
            $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
            $kas = (int) ($peng['nominal_kas'] ?? 0);
            if ($kas > 0) {
                $now = date('Y-m-d H:i:s');
                $db->table('iuran_komponen')->insert([
                    'nama'       => 'Iuran kas',
                    'nominal'    => $kas,
                    'urutan'     => 0,
                    'aktif'      => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function list(bool $aktifSaja = false): array
    {
        // Default: komponen tarif utama saja (kompatibel UI lama)
        try {
            $utama = (new TarifIuranService())->idUtama();
            return $this->listByTarif($utama, $aktifSaja);
        } catch (\Throwable $e) {
            $this->ensureTable();
            $db = \Config\Database::connect();
            $q = $db->table('iuran_komponen')->orderBy('urutan', 'ASC')->orderBy('id', 'ASC');
            if ($aktifSaja) {
                $q->where('aktif', 1);
            }
            $rows = $q->get()->getResultArray();
            return $this->mapKomponenRows($rows);
        }
    }

    public function listByTarif(int $tarifId, bool $aktifSaja = false): array
    {
        $this->ensureTable();
        try {
            (new TarifIuranService())->ensureSchema();
        } catch (\Throwable $e) {
        }
        $db = \Config\Database::connect();
        $q = $db->table('iuran_komponen')->where('tarif_id', $tarifId)->orderBy('urutan', 'ASC')->orderBy('id', 'ASC');
        if ($aktifSaja) {
            $q->where('aktif', 1);
        }
        return $this->mapKomponenRows($q->get()->getResultArray());
    }

    private function mapKomponenRows(array $rows): array
    {
        return array_map(static function ($r) {
            return [
                'id'       => (int) $r['id'],
                'tarif_id' => isset($r['tarif_id']) ? (int) $r['tarif_id'] : null,
                'nama'     => $r['nama'],
                'nominal'  => (int) $r['nominal'],
                'urutan'   => (int) $r['urutan'],
                'aktif'    => (bool) $r['aktif'],
            ];
        }, $rows);
    }

    public function totalAktif(): int
    {
        try {
            $svc = new TarifIuranService();
            return $svc->totalTarif($svc->idUtama(), true);
        } catch (\Throwable $e) {
            $this->ensureTable();
            $db = \Config\Database::connect();
            $row = $db->table('iuran_komponen')
                ->selectSum('nominal', 'total')
                ->where('aktif', 1)
                ->get()
                ->getRowArray();
            return (int) ($row['total'] ?? 0);
        }
    }

    public function syncNominalKas(): int
    {
        $total = $this->totalAktif();
        $db = \Config\Database::connect();
        $db->table('pengaturan')->where('id', 1)->update([
            'nominal_kas' => $total,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        return $total;
    }

    public function create(array $data, int $userId): array
    {
        $nama = trim((string) ($data['nama'] ?? ''));
        $nominal = (int) ($data['nominal'] ?? 0);
        if ($nama === '' || $nominal < 1) {
            return ['ok' => false, 'message' => 'Nama dan nominal wajib.'];
        }
        $this->ensureTable();
        $db = \Config\Database::connect();
        $max = $db->table('iuran_komponen')->selectMax('urutan')->get()->getRowArray();
        $urutan = (int) ($max['urutan'] ?? 0) + 1;
        $now = date('Y-m-d H:i:s');
        $tarifId = (int) ($data['tarif_id'] ?? 0);
        try {
            $tarifSvc = new TarifIuranService();
            $tarifSvc->ensureSchema();
            if ($tarifId < 1) {
                $tarifId = $tarifSvc->idUtama();
            }
        } catch (\Throwable $e) {
            $tarifId = 0;
        }
        $insert = [
            'nama'       => $nama,
            'nominal'    => $nominal,
            'urutan'     => $urutan,
            'aktif'      => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if ($tarifId > 0) {
            $insert['tarif_id'] = $tarifId;
        }
        $db->table('iuran_komponen')->insert($insert);
        $id = (int) $db->insertID();
        $total = $this->totalAktif();
        try {
            if ($tarifId < 1 || $tarifId === (new TarifIuranService())->idUtama()) {
                $total = $this->syncNominalKas();
            } else {
                $total = (new TarifIuranService())->totalTarif($tarifId, true);
            }
        } catch (\Throwable $e) {
            $total = $this->syncNominalKas();
        }
        try {
            (new SubPosService())->ensureKomponen($id, $nama, true);
        } catch (\Throwable $e) {
        }
        (new AuditService())->log('tambah_iuran_komponen', 'iuran_komponen', $id, null, [
            'nama' => $nama,
            'nominal' => $nominal,
            'tarif_id' => $tarifId,
            'total_kas' => $total,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id, 'total' => $total, 'tarif_id' => $tarifId]];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('iuran_komponen')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Komponen tidak ditemukan.'];
        }
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        if (array_key_exists('nama', $data)) {
            $nama = trim((string) $data['nama']);
            if ($nama === '') {
                return ['ok' => false, 'message' => 'Nama wajib.'];
            }
            $update['nama'] = $nama;
        }
        if (array_key_exists('nominal', $data)) {
            $nominal = (int) $data['nominal'];
            if ($nominal < 1) {
                return ['ok' => false, 'message' => 'Nominal tidak valid.'];
            }
            $update['nominal'] = $nominal;
        }
        if (array_key_exists('aktif', $data)) {
            $update['aktif'] = !empty($data['aktif']) ? 1 : 0;
        }
        $db->table('iuran_komponen')->where('id', $id)->update($update);
        $total = $this->syncNominalKas();
        try {
            $namaBaru = $update['nama'] ?? $row['nama'];
            $aktifBaru = array_key_exists('aktif', $update) ? ((int) $update['aktif'] === 1) : ((int) $row['aktif'] === 1);
            (new SubPosService())->ensureKomponen($id, (string) $namaBaru, $aktifBaru);
        } catch (\Throwable $e) {
        }
        (new AuditService())->log('ubah_iuran_komponen', 'iuran_komponen', $id, $row, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['total' => $total]];
    }

    public function delete(int $id, int $userId): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('iuran_komponen')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Komponen tidak ditemukan.'];
        }
        $db->table('iuran_komponen')->where('id', $id)->delete();
        $total = $this->syncNominalKas();
        try {
            (new SubPosService())->ensureKomponen($id, (string) $row['nama'], false);
        } catch (\Throwable $e) {
        }
        (new AuditService())->log('hapus_iuran_komponen', 'iuran_komponen', $id, $row, null, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['total' => $total]];
    }

    public function terapkanBulanIni(int $userId): array
    {
        // Per keluarga sesuai tarif (utama / khusus)
        return (new TarifIuranService())->terapkanSemuaBulanIni($userId);
    }
}
