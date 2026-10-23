<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;

class HealthController extends \CodeIgniter\Controller
{
    public function index()
    {
        $dbOk = false;
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $e) {
            log_message('error', 'Health DB: ' . $e->getMessage());
            $dbOk = false;
        }

        $sessionPath = WRITEPATH . 'session';
        $writableOk = is_dir(WRITEPATH) && is_writable(WRITEPATH);
        $sessionOk = is_dir($sessionPath) || @mkdir($sessionPath, 0755, true);

        return ApiResponse::ok([
            'app' => 'rtdua',
            'side' => SideContext::fromRequest(),
            'time' => date('c'),
            'tz' => date_default_timezone_get(),
            'php' => PHP_VERSION,
            'db' => $dbOk ? 'ok' : 'fail',
            'writable' => $writableOk,
            'session_path' => $sessionOk,
        ], 'alive');
    }
}
