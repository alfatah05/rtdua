<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\PengurusService;
use CodeIgniter\Controller;

class PengurusController extends Controller
{
    public function index()
    {
        $user = $this->requireKetua();
        if (!$user) {
            return ApiResponse::fail('Hanya ketua.', 403);
        }
        return ApiResponse::ok((new PengurusService())->list());
    }

    public function struktur()
    {
        $side = SideContext::fromRequest();
        $user = (new AuthService())->current($side);
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new PengurusService())->struktur());
    }

    public function bantuan()
    {
        $side = SideContext::fromRequest();
        $user = (new AuthService())->current($side);
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new PengurusService())->bantuan());
    }

    public function angkat()
    {
        $user = $this->requireKetua();
        if (!$user) {
            return ApiResponse::fail('Hanya ketua.', 403);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PengurusService())->angkat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Pengurus diangkat') : ApiResponse::fail($res['message'], 422);
    }

    public function update($id)
    {
        $user = $this->requireKetua();
        if (!$user) {
            return ApiResponse::fail('Hanya ketua.', 403);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PengurusService())->update((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Disimpan') : ApiResponse::fail($res['message'], 422);
    }

    private function requireKetua(): ?array
    {
        $side = SideContext::fromRequest();
        if ($side !== 'pengurus') {
            return null;
        }
        $user = (new AuthService())->current($side);
        if (!$user || $user['role'] !== 'ketua') {
            return null;
        }
        return $user;
    }
}
