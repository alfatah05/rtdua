<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    public function login()
    {
        $side = SideContext::fromRequest();
        $json = $this->request->getJSON(true) ?? [];
        $username = trim((string) ($json['username'] ?? ''));
        $secret = (string) ($json['password'] ?? $json['pin'] ?? '');

        if ($username === '' || $secret === '') {
            return ApiResponse::fail('Username dan kredensial wajib diisi.', 422);
        }

        $result = (new AuthService())->attempt($side, $username, $secret);
        if (!$result['ok']) {
            return ApiResponse::fail($result['message'], 401);
        }

        return ApiResponse::ok([
            'user' => $result['user'],
            'harus_ganti_kredensial' => $result['harus_ganti_kredensial'],
            'side' => $side,
        ], 'Login berhasil');
    }

    public function logout()
    {
        $side = SideContext::fromRequest();
        (new AuthService())->logout($side);
        return ApiResponse::ok(null, 'Logout berhasil');
    }

    public function me()
    {
        $side = SideContext::fromRequest();
        $user = (new AuthService())->current($side);
        if (!$user) {
            return ApiResponse::fail('Belum login.', 401);
        }
        return ApiResponse::ok(['user' => $user, 'side' => $side]);
    }

    public function changeCredential()
    {
        $side = SideContext::fromRequest();
        $auth = new AuthService();
        $user = $auth->current($side);
        if (!$user) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $new = (string) ($json['new_password'] ?? $json['new_pin'] ?? '');
        $result = $auth->changeCredential($side, (int) $user['id'], $new);
        if (!$result['ok']) {
            return ApiResponse::fail($result['message'], 422);
        }
        return ApiResponse::ok(null, $result['message']);
    }
}
