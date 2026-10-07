<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\AuditService;
use App\Services\KeluargaService;
use App\Services\NotifikasiService;
use CodeIgniter\Controller;
use Throwable;

class KeluargaController extends Controller
{
    public function index()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $filter = [
            'status'         => $this->request->getGet('status') ?? 'aktif',
            'blok_id'        => $this->request->getGet('blok_id'),
            'q'              => $this->request->getGet('q'),
            'belum_lengkap'  => $this->request->getGet('belum_lengkap') === '1',
            'belum_ganti_pin'=> $this->request->getGet('belum_ganti_pin') === '1',
        ];
        return ApiResponse::ok((new KeluargaService())->list($filter));
    }

    public function show($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $bukaNik = $this->request->getGet('buka_nik') === '1';
        $data = (new KeluargaService())->detail(
            (int) $id,
            $bukaNik,
            (int) $user['id'],
            !empty($user['is_developer'])
        );
        if (!$data) {
            return ApiResponse::fail('Tidak ditemukan', 404);
        }
        return ApiResponse::ok($data);
    }

    public function create()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KeluargaService())->create($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Keluarga ditambah') : ApiResponse::fail($res['message'], 422);
    }

    public function update($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KeluargaService())->updateKeluarga((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function tambahAnggota($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KeluargaService())->tambahAnggota((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Anggota ditambah') : ApiResponse::fail($res['message'], 422);
    }

    public function updateAnggota($anggotaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $svc = new KeluargaService();
        $res = $svc->updateAnggota((int) $anggotaId, $json, (int) $user['id']);
        if (array_key_exists('foto', $json)) {
            $db = \Config\Database::connect();
            $db->table('warga')->where('id', (int) $anggotaId)->update([
                'foto' => $json['foto'] ? (string) $json['foto'] : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$res['ok'] && ($res['message'] ?? '') === 'Tidak ada perubahan.') {
                $res = ['ok' => true];
            }
        }
        return $res['ok'] ? ApiResponse::ok(null, 'Disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function statusAnggota($anggotaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $status = (string) ($json['status'] ?? '');
        $svc = new KeluargaService();
        if (!empty($json['kepala_baru_id'])) {
            $db = \Config\Database::connect();
            $a = $db->table('warga')->where('id', (int) $anggotaId)->get()->getRowArray();
            if ($a) {
                $svc->gantiKepala((int) $a['keluarga_id'], (int) $json['kepala_baru_id'], (int) $user['id']);
            }
        }
        $res = $svc->setStatusAnggota((int) $anggotaId, $status, $json['alasan'] ?? null, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Status diperbarui') : ApiResponse::fail($res['message'], 422);
    }

    public function pindah($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KeluargaService())->pindah((int) $id, $json['tanggal'] ?? null, $json['catatan'] ?? null, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Keluarga ditandai pindah') : ApiResponse::fail($res['message'], 422);
    }

    /**
     * Reset PIN warga ke 123456 + wajib ganti.
     * Jika akun role warga belum ada, dibuat otomatis dari alamat keluarga.
     */
    public function resetPin($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }

        try {
            $keluargaId = (int) $id;
            $db = \Config\Database::connect();

            $kel = $db->table('keluarga k')
                ->select('k.*, b.nama as blok_nama')
                ->join('blok b', 'b.id = k.blok_id')
                ->where('k.id', $keluargaId)
                ->get()
                ->getRowArray();

            if (!$kel || ($kel['status'] ?? '') !== 'aktif') {
                return ApiResponse::fail('Keluarga tidak ditemukan atau tidak aktif.', 422);
            }

            $u = $db->table('users')
                ->where('keluarga_id', $keluargaId)
                ->where('role', 'warga')
                ->get()
                ->getRowArray();

            $hash = password_hash('123456', PASSWORD_DEFAULT);

            if (!$u) {
                $base = strtolower(trim($kel['blok_nama']) . '-' . trim($kel['nomor']) . trim((string) ($kel['akhiran'] ?? '')));
                $uname = $base;
                $i = 0;
                while ($db->table('users')->where('username', $uname)->countAllResults() > 0) {
                    $i++;
                    $uname = $base . 'x' . $i;
                }
                $db->table('users')->insert([
                    'role'                   => 'warga',
                    'username'               => $uname,
                    'password_hash'          => $hash,
                    'keluarga_id'            => $keluargaId,
                    'aktif'                  => 1,
                    'harus_ganti_kredensial' => 1,
                    'created_at'             => date('Y-m-d H:i:s'),
                ]);
                $uid = (int) $db->insertID();
                $msg = 'Akun warga dibuat. PIN awal 123456 (wajib diganti). Username: ' . $uname;
            } else {
                $db->table('users')->where('id', (int) $u['id'])->update([
                    'password_hash'          => $hash,
                    'aktif'                  => 1,
                    'harus_ganti_kredensial' => 1,
                    'updated_at'             => date('Y-m-d H:i:s'),
                ]);
                $uid = (int) $u['id'];
                $msg = 'PIN direset ke 123456.';
            }

            try {
                (new AuditService())->log('reset_pin', 'users', $uid, null, ['keluarga_id' => $keluargaId], 'pengguna', true, (int) $user['id']);
            } catch (Throwable $e) {
                // jangan gagalkan reset
            }

            try {
                (new NotifikasiService())->kirim(
                    $uid,
                    'reset_pin',
                    'PIN direset',
                    'PIN Anda direset pengurus. PIN sementara: 123456. Wajib diganti saat login.',
                    '/ganti-pin'
                );
            } catch (Throwable $e) {
                // jangan gagalkan reset
            }

            return ApiResponse::ok(['user_id' => $uid], $msg);
        } catch (Throwable $e) {
            return ApiResponse::fail('Reset PIN gagal: ' . $e->getMessage(), 500, [
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function requirePengurus(): ?array
    {
        $side = SideContext::fromRequest();
        if ($side !== 'pengurus') {
            return null;
        }
        $user = (new AuthService())->current($side);
        if (!$user || !in_array($user['role'], ['ketua', 'pengurus'], true)) {
            return null;
        }
        return $user;
    }
}
