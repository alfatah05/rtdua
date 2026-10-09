<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\UploadService;
use CodeIgniter\Controller;

class UploadController extends Controller
{
    public function store()
    {
        $side = SideContext::fromRequest();
        $user = (new AuthService())->current($side);
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $jenis = (string) ($this->request->getPost('jenis') ?? '');
        if ($jenis === 'bukti' || $jenis === 'ronda') {
            // warga atau pengurus
        } elseif (in_array($jenis, ['galeri', 'banner', 'foto_profil', 'logo', 'lampiran'], true)) {
            if ($side !== 'pengurus' || !in_array($user['role'], ['ketua', 'pengurus'], true)) {
                return ApiResponse::fail('Hanya pengurus.', 403);
            }
            if ($jenis === 'logo' && $user['role'] !== 'ketua') {
                return ApiResponse::fail('Hanya ketua yang boleh unggah logo.', 403);
            }
        } else {
            return ApiResponse::fail('Jenis tidak valid.', 422);
        }
        $file = $this->request->getFile('file');
        $thumb = $this->request->getPost('thumb') ?: null;
        $res = (new UploadService())->simpan($file, $jenis, is_string($thumb) ? $thumb : null);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Uploaded') : ApiResponse::fail($res['message'], 422);
    }

    public function media($jenis = null, $file = null)
    {
        $path = trim((string) $jenis, '/') . '/' . trim((string) $file, '/');
        $svc = new UploadService();
        $full = $svc->pathFisik($path);
        if (!$full) {
            return ApiResponse::fail('Tidak ditemukan', 404);
        }
        $needAuth = !$svc->isPublicJenis($path);
        if ($needAuth) {
            $side = SideContext::fromRequest();
            $user = (new AuthService())->current($side);
            if (!$user) {
                return ApiResponse::fail('Unauthorized', 401);
            }
        }
        $mime = mime_content_type($full) ?: 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setBody((string) file_get_contents($full));
    }
}
