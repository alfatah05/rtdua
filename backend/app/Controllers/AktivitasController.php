<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AktivitasService;
use App\Services\AuthService;
use CodeIgniter\Controller;

class AktivitasController extends Controller
{
    public function index()
    {
        $side = SideContext::fromRequest();
        if ($side !== 'pengurus') {
            return ApiResponse::fail('Forbidden', 403);
        }
        $user = (new AuthService())->current($side);
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $filter = [
            'bulan' => $this->request->getGet('bulan'),
            'tahun' => $this->request->getGet('tahun'),
            'limit' => $this->request->getGet('limit'),
        ];
        return ApiResponse::ok((new AktivitasService())->list($filter));
    }
}
