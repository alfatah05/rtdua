<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AktivitasService;
use App\Services\AuthService;
use CodeIgniter\Controller;

class AktivitasController extends Controller
{
    private function requirePengurus()
    {
        $side = SideContext::fromRequest();
        if ($side !== 'pengurus') {
            return null;
        }
        return (new AuthService())->current($side);
    }

    public function index()
    {
        if (!$this->requirePengurus()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $filter = [
            'bulan' => $this->request->getGet('bulan'),
            'tahun' => $this->request->getGet('tahun'),
            'limit' => $this->request->getGet('limit'),
        ];
        return ApiResponse::ok((new AktivitasService())->list($filter));
    }

    public function detail($id)
    {
        if (!$this->requirePengurus()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $row = (new AktivitasService())->detail((int) $id);
        if (!$row) {
            return ApiResponse::fail('Aktivitas tidak ditemukan', 404);
        }
        return ApiResponse::ok($row);
    }
}
