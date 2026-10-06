<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Services\AuditService;
use CodeIgniter\Controller;

class SetupController extends Controller
{
    public function status()
    {
        $db = \Config\Database::connect();
        $row = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $done = $row && (int) $row['setup_selesai'] === 1;
        return ApiResponse::ok(['setup_selesai' => $done]);
    }

    public function run()
    {
        $db = \Config\Database::connect();
        $row = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        if ($row && (int) $row['setup_selesai'] === 1) {
            return ApiResponse::fail('Setup sudah selesai dan terkunci.', 403);
        }

        $json = $this->request->getJSON(true) ?? [];
        $namaRt = trim((string) ($json['nama_rt'] ?? ''));
        $perumahan = trim((string) ($json['nama_perumahan'] ?? ''));
        $username = strtolower(trim((string) ($json['username'] ?? '')));
        $password = (string) ($json['password'] ?? '');
        $namaKetua = trim((string) ($json['nama_ketua'] ?? 'Ketua RT'));
        $blokList = $json['blok'] ?? ['AB1', 'AB2', 'AB11', 'AB12'];

        if ($namaRt === '' || $username === '' || strlen($password) < 8) {
            return ApiResponse::fail('nama_rt, username, dan password (min 8) wajib.', 422);
        }

        $db->transStart();

        $db->table('pengaturan')->where('id', 1)->update([
            'nama_rt' => $namaRt,
            'nama_perumahan' => $perumahan,
            'setup_selesai' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ((array) $blokList as $b) {
            $b = trim((string) $b);
            if ($b === '') continue;
            $exists = $db->table('blok')->where('nama', $b)->countAllResults();
            if (!$exists) {
                $db->table('blok')->insert(['nama' => $b, 'aktif' => 1]);
            }
        }

        // Buat keluarga dummy + warga untuk ketua (minimal)
        $blokId = $db->table('blok')->where('nama', $blokList[0] ?? 'AB1')->get()->getRowArray()['id'] ?? null;
        if (!$blokId) {
            $db->table('blok')->insert(['nama' => 'AB1', 'aktif' => 1]);
            $blokId = $db->insertID();
        }

        $db->table('keluarga')->insert([
            'blok_id' => $blokId,
            'nomor' => '1',
            'akhiran' => '',
            'status' => 'aktif',
            'mulai_periode' => date('Y-m'),
        ]);
        $keluargaId = $db->insertID();

        $db->table('warga')->insert([
            'keluarga_id' => $keluargaId,
            'nama' => $namaKetua,
            'hubungan' => 'Kepala keluarga',
            'status' => 'aktif',
        ]);
        $wargaId = $db->insertID();

        $db->table('users')->insert([
            'role' => 'ketua',
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'warga_id' => $wargaId,
            'jabatan' => 'Ketua RT',
            'tampil_di_bantuan' => 1,
            'urutan_struktur' => 1,
            'is_developer' => 0,
            'aktif' => 1,
            'harus_ganti_kredensial' => 0,
        ]);
        $userId = $db->insertID();

        $db->transComplete();

        if (!$db->transStatus()) {
            return ApiResponse::fail('Gagal menyimpan setup.', 500);
        }

        (new AuditService())->log('setup_awal', 'pengaturan', 1, null, [
            'nama_rt' => $namaRt,
            'username_ketua' => $username,
        ], 'pengguna', true, $userId);

        return ApiResponse::ok([
            'username' => $username,
            'role' => 'ketua',
        ], 'Setup selesai. Login di aplikasi pengurus.');
    }
}
