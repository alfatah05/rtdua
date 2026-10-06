<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\PengaturanService;
use CodeIgniter\Controller;

class PengaturanController extends Controller
{
    public function index()
    {
        $side = SideContext::fromRequest();
        $svc = new PengaturanService();
        if ($side === 'warga') {
            return ApiResponse::ok($svc->getPublik());
        }
        $user = (new AuthService())->current($side);
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok($svc->get());
    }

    public function updateAplikasi()
    {
        $user = $this->requireKetua();
        if (!$user) {
            return ApiResponse::fail('Hanya ketua.', 403);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PengaturanService())->updateAplikasi($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Pengaturan disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function updateWarga()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PengaturanService())->updateWarga($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Pengaturan disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function listBlok()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $semua = $this->request->getGet('semua') === '1';
        return ApiResponse::ok((new PengaturanService())->listBlok($semua));
    }

    public function tambahBlok()
    {
        $user = $this->requireKetua();
        if (!$user) {
            return ApiResponse::fail('Hanya ketua.', 403);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PengaturanService())->tambahBlok((string) ($json['nama'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Blok ditambah') : ApiResponse::fail($res['message'], 422);
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

    private function requireKetua(): ?array
    {
        $user = $this->requirePengurus();
        if (!$user || $user['role'] !== 'ketua') {
            return null;
        }
        return $user;
    }
}
