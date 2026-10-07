<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Services\TugasService;
use CodeIgniter\RESTful\ResourceController;

/**
 * Endpoint cron (header X-Cron-Token).
 * Contoh: GET /api/tugas/harian
 */
class TugasController extends ResourceController
{
    public function harian()
    {
        $hasil = (new TugasService())->harian();
        return ApiResponse::ok($hasil, 'Tugas harian selesai');
    }

    public function sore()
    {
        $hasil = (new TugasService())->sore();
        return ApiResponse::ok($hasil, 'Tugas sore selesai');
    }

    public function tiapLimaMenit()
    {
        $hasil = (new TugasService())->tiapLimaMenit();
        return ApiResponse::ok($hasil, 'Tugas 5 menit selesai');
    }
}
