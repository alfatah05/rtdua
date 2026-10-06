<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;

class HealthController extends \CodeIgniter\Controller
{
    public function index()
    {
        return ApiResponse::ok([
            'app' => 'rtdua',
            'side' => SideContext::fromRequest(),
            'time' => date('c'),
            'tz' => date_default_timezone_get(),
        ], 'alive');
    }
}
