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
            return ApiResponse::fail('Gagal cek status: ' . $e->getMessage(), 500);
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
            $namaKetua  = trim((string) ($json['nama_ketua'] ?? 'Ketua RT'));
            $blokList   = $json['blok'] ?? ['AB1', 'AB2', 'AB11', 'AB12'];
            if (!is_array($blokList) || $blokList === []) {
                $blokList = ['AB1', 'AB2', 'AB11', 'AB12'];
            }

            if ($namaRt === '' || $username === '' || strlen($password) < 8) {
                return ApiResponse::fail('nama_rt, username, dan password (min 8) wajib.', 422);
            }

            // Username unik
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

            $firstBlokName = null;
            foreach ($blokList as $b) {
                $b = strtoupper(trim((string) $b));
                if ($b === '') {
                    continue;
                }
                if ($firstBlokName === null) {
                    $firstBlokName = $b;
                }
                $exists = $db->table('blok')->where('nama', $b)->countAllResults();
                if ($exists === 0) {
                    $db->table('blok')->insert([
                        'nama'  => $b,
                        'aktif' => 1,
                    ]);
                }
            }

            if ($firstBlokName === null) {
                $firstBlokName = 'AB1';
                $db->table('blok')->insert(['nama' => 'AB1', 'aktif' => 1]);
            }

            $blokRow = $db->table('blok')->where('nama', $firstBlokName)->get()->getRowArray();
            $blokId  = $blokRow ? (int) $blokRow['id'] : 0;
            if ($blokId < 1) {
                $db->table('blok')->insert(['nama' => $firstBlokName, 'aktif' => 1]);
                $blokId = (int) $db->insertID();
            }

            $db->table('keluarga')->insert([
                'blok_id'       => $blokId,
                'nomor'         => '1',
                'akhiran'       => '',
                'status'        => 'aktif',
                'mulai_periode' => date('Y-m'),
            ]);
            $keluargaId = (int) $db->insertID();

            $db->table('warga')->insert([
                'keluarga_id' => $keluargaId,
                'nama'        => $namaKetua !== '' ? $namaKetua : 'Ketua RT',
                'hubungan'    => 'Kepala keluarga',
                'status'      => 'aktif',
            ]);
            $wargaId = (int) $db->insertID();

            $db->table('users')->insert([
                'role'                   => 'ketua',
                'username'               => $username,
                'password_hash'          => password_hash($password, PASSWORD_DEFAULT),
                'warga_id'               => $wargaId,
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
                ], 'pengguna', true, $userId);
            } catch (Throwable $e) {
                // Audit gagal tidak membatalkan setup yang sudah sukses
            }

            return ApiResponse::ok([
                'username' => $username,
                'role'     => 'ketua',
            ], 'Setup selesai. Login di aplikasi pengurus.');
        } catch (Throwable $e) {
            return ApiResponse::fail(
                'Setup gagal: ' . $e->getMessage(),
                500,
                [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ]
            );
        }
    }
}
