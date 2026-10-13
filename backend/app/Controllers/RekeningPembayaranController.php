<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\RekeningPembayaranService;
use CodeIgniter\Controller;

class RekeningPembayaranController extends Controller
{
    public function index()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new RekeningPembayaranService())->listAll());
    }

    public function create()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $body = $this->request->getJSON(true) ?? [];
        $res = (new RekeningPembayaranService())->create($body, (int) $user['id']);
        if (!($res['ok'] ?? false)) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data']);
    }

    public function update($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $body = $this->request->getJSON(true) ?? [];
        $res = (new RekeningPembayaranService())->update((int) $id, $body, (int) $user['id']);
        if (!($res['ok'] ?? false)) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data']);
    }

    public function delete($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new RekeningPembayaranService())->delete((int) $id, (int) $user['id']);
        if (!($res['ok'] ?? false)) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok(['deleted' => true]);
    }

    public function portalList()
    {
        if (SideContext::fromRequest() !== 'warga') {
            return ApiResponse::fail('Forbidden', 403);
        }
        $user = (new AuthService())->current('warga');
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new RekeningPembayaranService())->listAktif());
    }

    private function requirePengurus(): ?array
    {
        $side = SideContext::fromRequest();
        if ($side !== 'pengurus') {
            return null;
        }
        $user = (new AuthService())->current($side);
        if (!$user || !in_array($user['role'] ?? '', ['ketua', 'pengurus'], true)) {
            return null;
        }
        return $user;
    }
}
