<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\NotifikasiService;
use CodeIgniter\RESTful\ResourceController;

class NotifikasiController extends ResourceController
{
    private function userId(): ?int
    {
        $side = SideContext::fromRequest();
        $u = (new AuthService())->current($side);
        return $u ? (int) $u['id'] : null;
    }

    public function index()
    {
        $uid = $this->userId();
        if (!$uid) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $offset = (int) ($this->request->getGet('offset') ?? 0);
        $svc = new NotifikasiService();
        return ApiResponse::ok([
            'items' => $svc->listForUser($uid, $limit, $offset),
            'belum_dibaca' => $svc->jumlahBelumDibaca($uid),
        ]);
    }

    public function show($id = null)
    {
        $uid = $this->userId();
        if (!$uid) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $item = (new NotifikasiService())->detailForUser($uid, (int) $id);
        if (!$item) {
            return ApiResponse::fail('Notifikasi tidak ditemukan.', 404);
        }
        return ApiResponse::ok($item);
    }

    public function badge()
    {
        $uid = $this->userId();
        if (!$uid) {
            return ApiResponse::fail('Belum login.', 401);
        }
        return ApiResponse::ok(['belum_dibaca' => (new NotifikasiService())->jumlahBelumDibaca($uid)]);
    }

    public function baca($id)
    {
        $uid = $this->userId();
        if (!$uid) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $ok = (new NotifikasiService())->tandaiDibaca($uid, (int) $id);
        if (!$ok) {
            return ApiResponse::fail('Notifikasi tidak ditemukan.', 404);
        }
        return ApiResponse::ok(null, 'Ditandai dibaca');
    }

    public function bacaSemua()
    {
        $uid = $this->userId();
        if (!$uid) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $n = (new NotifikasiService())->tandaiSemuaDibaca($uid);
        return ApiResponse::ok(['jumlah' => $n], 'Semua ditandai dibaca');
    }
}
