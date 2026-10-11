<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    /** Baca body JSON tanpa throw (CI getJSON() melempar HTTPException jika body kosong/rusak). */
    private function jsonBody(): array
    {
        $raw = $this->request->getBody();
        if ($raw === null || $raw === '') {
            $raw = $this->request->getRawInput();
        }
        if (!$raw) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public function login()
    {
        try {
            $side = SideContext::fromRequest();
            $json = $this->jsonBody();
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
        } catch (\Throwable $e) {
            log_message('error', 'Auth login: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return ApiResponse::fail('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function logout()
    {
        try {
            $side = SideContext::fromRequest();
            (new AuthService())->logout($side);
            return ApiResponse::ok(null, 'Logout berhasil');
        } catch (\Throwable $e) {
            log_message('error', 'Auth logout: ' . $e->getMessage());
            return ApiResponse::fail('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function me()
    {
        try {
            $side = SideContext::fromRequest();
            $user = (new AuthService())->current($side);
            if (!$user) {
                return ApiResponse::fail('Belum login.', 401);
            }
            return ApiResponse::ok(['user' => $user, 'side' => $side]);
        } catch (\Throwable $e) {
            log_message('error', 'Auth me: ' . $e->getMessage());
            return ApiResponse::fail('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function changeCredential()
    {
        try {
            $side = SideContext::fromRequest();
            $auth = new AuthService();
            $user = $auth->current($side);
            if (!$user) {
                return ApiResponse::fail('Belum login.', 401);
            }
            $json = $this->jsonBody();
            $new = (string) ($json['new_password'] ?? $json['new_pin'] ?? '');
            $result = $auth->changeCredential($side, (int) $user['id'], $new);
            if (!$result['ok']) {
                return ApiResponse::fail($result['message'], 422);
            }
            return ApiResponse::ok(null, $result['message']);
        } catch (\Throwable $e) {
            log_message('error', 'Auth changeCredential: ' . $e->getMessage());
            return ApiResponse::fail('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function updateFoto()
    {
        try {
            $side = SideContext::fromRequest();
            $user = (new AuthService())->current($side);
            if (!$user) {
                return ApiResponse::fail('Unauthorized', 401);
            }
            $json = $this->jsonBody();
            $path = trim((string) ($json['foto'] ?? $json['path'] ?? ''));
            $res = (new AuthService())->updateFotoProfil($side, (int) $user['id'], $path);
            return $res['ok']
                ? ApiResponse::ok($res['data'] ?? null, 'Foto diperbarui')
                : ApiResponse::fail($res['message'], 422);
        } catch (\Throwable $e) {
            log_message('error', 'Auth updateFoto: ' . $e->getMessage());
            return ApiResponse::fail('Server error', 500);
        }
    }
}
