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
        $db->query("CREATE TABLE IF NOT EXISTS iuran_komponen (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(120) NOT NULL,
            nominal INT UNSIGNED NOT NULL DEFAULT 0,
            urutan INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_aktif_urutan (aktif, urutan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
        $this->ensureTable();
        $db = \Config\Database::connect();
        $q = $db->table('iuran_komponen')->orderBy('urutan', 'ASC')->orderBy('id', 'ASC');
        if ($aktifSaja) {
            $q->where('aktif', 1);
        }
        $rows = $q->get()->getResultArray();
        return array_map(static function ($r) {
            return [
                'id'      => (int) $r['id'],
                'nama'    => $r['nama'],
                'nominal' => (int) $r['nominal'],
                'urutan'  => (int) $r['urutan'],
                'aktif'   => (bool) $r['aktif'],
            ];
        }, $rows);
    }

    public function totalAktif(): int
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $row = $db->table('iuran_komponen')
            ->selectSum('nominal', 'total')
            ->where('aktif', 1)
            ->get()
            ->getRowArray();
        return (int) ($row['total'] ?? 0);
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
        $db->table('iuran_komponen')->insert([
            'nama'       => $nama,
            'nominal'    => $nominal,
            'urutan'     => $urutan,
            'aktif'      => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        $total = $this->syncNominalKas();
        (new AuditService())->log('tambah_iuran_komponen', 'iuran_komponen', $id, null, [
            'nama' => $nama,
            'nominal' => $nominal,
            'total_kas' => $total,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id, 'total' => $total]];
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
        (new AuditService())->log('hapus_iuran_komponen', 'iuran_komponen', $id, $row, null, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['total' => $total]];
    }

    public function terapkanBulanIni(int $userId): array
    {
        $total = $this->syncNominalKas();
        $bulanIni = date('Y-m');
        // Buat dulu tagihan kas untuk keluarga aktif yang belum punya (warga SQL/import)
        $dibuat = (new TagihanService())->pastikanTagihanKasBulan($bulanIni, $total);
        $db = \Config\Database::connect();
        $db->table('iuran_tagihan')
            ->where('periode', $bulanIni)
            ->where('jenis', 'kas')
            ->where('dibatalkan', 0)
            ->update(['nominal' => $total]);
        $n = $db->table('iuran_tagihan')
            ->where('periode', $bulanIni)
            ->where('jenis', 'kas')
            ->where('dibatalkan', 0)
            ->countAllResults();
        (new AuditService())->log('terapkan_komponen_bulan_ini', 'iuran_komponen', 0, null, [
            'periode' => $bulanIni,
            'nominal' => $total,
            'kk' => $n,
            'baru_dibuat' => $dibuat,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => [
            'total' => $total,
            'keluarga_terdampak' => $n,
            'baru_dibuat' => $dibuat,
            'periode' => $bulanIni,
        ]];
    }
}
