<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\KeluargaService;
use CodeIgniter\Controller;

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
        $res = (new KeluargaService())->updateAnggota((int) $anggotaId, $json, (int) $user['id']);
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
            // set kepala baru dulu jika ada
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

    public function resetPin($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new KeluargaService())->resetPin((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, $res['message']) : ApiResponse::fail($res['message'], 422);
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
