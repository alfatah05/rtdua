<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Services\AuditService;
use CodeIgniter\Controller;
use Throwable;

class SetupController extends Controller
{
    public function status()
    {
        try {
            $db = \Config\Database::connect();
            $row = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
            $done = $row && (int) ($row['setup_selesai'] ?? 0) === 1;
            return ApiResponse::ok(['setup_selesai' => $done]);
        } catch (Throwable $e) {
            log_message('error', 'Setup status: ' . $e->getMessage());
            return ApiResponse::fail('Gagal cek status setup.', 500);
        }
    }

    public function run()
    {
        try {
            $db = \Config\Database::connect();
            $row = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
            if ($row && (int) ($row['setup_selesai'] ?? 0) === 1) {
                return ApiResponse::fail('Setup sudah selesai dan terkunci.', 403);
            }

            $json = $this->request->getJSON(true);
            if (!is_array($json)) {
                $json = [];
            }

            $namaRt     = trim((string) ($json['nama_rt'] ?? ''));
            $perumahan  = trim((string) ($json['nama_perumahan'] ?? ''));
            $username   = strtolower(trim((string) ($json['username'] ?? '')));
            $password   = (string) ($json['password'] ?? '');
            $namaKetua  = trim((string) ($json['nama_ketua'] ?? ''));
            if ($namaKetua === '') {
                $namaKetua = $username;
            }
            $blokList   = $json['blok'] ?? ['AB1', 'AB2', 'AB11', 'AB12'];
            if (!is_array($blokList) || $blokList === []) {
                $blokList = ['AB1', 'AB2', 'AB11', 'AB12'];
            }

            if ($namaRt === '' || $username === '' || strlen($password) < 8) {
                return ApiResponse::fail('nama_rt, username, dan password (min 8) wajib.', 422);
            }

            if ($db->table('users')->where('username', $username)->countAllResults() > 0) {
                return ApiResponse::fail('Username sudah dipakai.', 422);
            }

            $db->transStart();

            $db->table('pengaturan')->where('id', 1)->update([
                'nama_rt'        => $namaRt,
                'nama_perumahan' => $perumahan,
                'setup_selesai'  => 1,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            foreach ($blokList as $b) {
                $b = strtoupper(trim((string) $b));
                if ($b === '') {
                    continue;
                }
                $exists = $db->table('blok')->where('nama', $b)->countAllResults();
                if ($exists === 0) {
                    $db->table('blok')->insert([
                        'nama'  => $b,
                        'aktif' => 1,
                    ]);
                }
            }

            // Akun ketua mandiri — nama tampilan terpisah dari jabatan & username
            $db->table('users')->insert([
                'role'                   => 'ketua',
                'username'               => $username,
                'nama'                   => $namaKetua,
                'password_hash'          => password_hash($password, PASSWORD_DEFAULT),
                'warga_id'               => null,
                'keluarga_id'            => null,
                'jabatan'                => 'Ketua RT',
                'tampil_di_bantuan'      => 1,
                'urutan_struktur'        => 1,
                'is_developer'           => 0,
                'aktif'                  => 1,
                'harus_ganti_kredensial' => 0,
            ]);
            $userId = (int) $db->insertID();

            $db->transComplete();

            if (!$db->transStatus()) {
                return ApiResponse::fail('Gagal menyimpan setup (transaksi DB).', 500);
            }

            try {
                (new AuditService())->log('setup_awal', 'pengaturan', 1, null, [
                    'nama_rt'        => $namaRt,
                    'username_ketua' => $username,
                    'nama_ketua'     => $namaKetua,
                ], 'pengguna', true, $userId);
            } catch (Throwable $e) {
            }

            return ApiResponse::ok([
                'username' => $username,
                'role'     => 'ketua',
            ], 'Setup selesai. Login di aplikasi pengurus.');
        } catch (Throwable $e) {
            log_message('error', 'Setup run: ' . $e->getMessage());
            return ApiResponse::fail('Setup gagal. Periksa log server.', 500);
        }
    }
}
