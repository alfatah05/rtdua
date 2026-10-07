<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\GaleriService;
use App\Services\PengumumanService;
use App\Services\ProgramService;
use CodeIgniter\Controller;

class KontenController extends Controller
{
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

    private function requireLogin(): ?array
    {
        $side = SideContext::fromRequest();
        $user = (new AuthService())->current($side);
        return $user ?: null;
    }

    // ---- Pengumuman ----
    public function listPengumuman()
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $beranda = $this->request->getGet('beranda') === '1';
        return ApiResponse::ok((new PengumumanService())->list($beranda));
    }

    public function detailPengumuman($id)
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $d = (new PengumumanService())->detail((int) $id);
        return $d ? ApiResponse::ok($d) : ApiResponse::fail('Tidak ditemukan', 404);
    }

    public function buatPengumuman()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PengumumanService())->buat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Pengumuman dibuat') : ApiResponse::fail($res['message'], 422);
    }

    public function ubahPengumuman($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PengumumanService())->ubah((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function hapusPengumuman($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new PengumumanService())->hapus((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Dihapus') : ApiResponse::fail($res['message'], 422);
    }

    // ---- Program ----
    public function listProgram()
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new ProgramService())->list());
    }

    public function detailProgram($id)
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $d = (new ProgramService())->detail((int) $id);
        return $d ? ApiResponse::ok($d) : ApiResponse::fail('Tidak ditemukan', 404);
    }

    public function buatProgram()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new ProgramService())->buat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data']) : ApiResponse::fail($res['message'], 422);
    }

    public function ubahProgram($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new ProgramService())->ubah((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data']) : ApiResponse::fail($res['message'], 422);
    }

    public function hapusProgram($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new ProgramService())->hapus((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Dihapus') : ApiResponse::fail($res['message'], 422);
    }

    // ---- Galeri ----
    public function listAlbum()
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new GaleriService())->listAlbum());
    }

    public function detailAlbum($id)
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $d = (new GaleriService())->detailAlbum((int) $id);
        return $d ? ApiResponse::ok($d) : ApiResponse::fail('Tidak ditemukan', 404);
    }

    public function buatAlbum()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new GaleriService())->buatAlbum($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data']) : ApiResponse::fail($res['message'], 422);
    }

    public function hapusAlbum($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new GaleriService())->hapusAlbum((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Dihapus') : ApiResponse::fail($res['message'], 422);
    }

    public function tambahFoto($albumId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new GaleriService())->tambahFoto((int) $albumId, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data']) : ApiResponse::fail($res['message'], 422);
    }

    public function hapusFoto()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $ids = $json['ids'] ?? [];
        $res = (new GaleriService())->hapusFoto(is_array($ids) ? $ids : [], (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data']) : ApiResponse::fail($res['message'], 422);
    }
}
