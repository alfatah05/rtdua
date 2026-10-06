<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\KeluargaService;
use CodeIgniter\Controller;

class PortalController extends Controller
{
    public function daftarWarga()
    {
        if (SideContext::fromRequest() !== 'warga') {
            return ApiResponse::fail('Forbidden', 403);
        }
        $user = (new AuthService())->current('warga');
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $list = (new KeluargaService())->list(['status' => 'aktif']);
        $data = array_map(static function ($k) {
            return [
                'id'     => $k['id'],
                'nama'   => $k['nama'],
                'alamat' => $k['alamat'],
            ];
        }, $list);
        return ApiResponse::ok($data);
    }
}
