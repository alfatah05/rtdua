<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\PushService;
use CodeIgniter\RESTful\ResourceController;

class PushController extends ResourceController
{
    private function userId(): ?int
    {
        $side = SideContext::fromRequest();
        $u = (new AuthService())->current($side);
        return $u ? (int) $u['id'] : null;
    }

    /** Kunci publik VAPID untuk subscribe di browser. */
    public function vapidPublic()
    {
        $key = (new PushService())->publicKey();
        if (!$key) {
            return ApiResponse::fail('VAPID belum dikonfigurasi di server.', 503);
        }
        return ApiResponse::ok(['publicKey' => $key]);
    }

    public function subscribe()
    {
        $uid = $this->userId();
        if (!$uid) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $sub = $json['subscription'] ?? $json;
        $perangkat = isset($json['perangkat']) ? (string) $json['perangkat'] : null;
        $res = (new PushService())->simpanLangganan($uid, is_array($sub) ? $sub : [], $perangkat);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal menyimpan langganan', 422);
        }
        return ApiResponse::ok(['id' => $res['id']], 'Push diaktifkan');
    }

    public function unsubscribe()
    {
        $uid = $this->userId();
        if (!$uid) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $endpoint = (string) ($json['endpoint'] ?? '');
        $svc = new PushService();
        if ($endpoint !== '') {
            $svc->nonaktifkanEndpoint($endpoint);
        } else {
            $svc->nonaktifkanSemuaUser($uid);
        }
        return ApiResponse::ok(null, 'Push dimatikan di perangkat ini');
    }
}
