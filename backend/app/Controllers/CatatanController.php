<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\CatatanService;
use CodeIgniter\Controller;

class CatatanController extends Controller
{
    private function requirePengurus()
    {
        $side = SideContext::fromRequest();
        if ($side !== 'pengurus') {
            return null;
        }
        $user = (new AuthService())->current($side);
        if (!$user) {
            return null;
        }
        $role = (string) ($user['role'] ?? '');
        if (!empty($user['is_developer']) || in_array($role, ['ketua', 'pengurus'], true)) {
            return $user;
        }
        return null;
    }

    private function noStore($response)
    {
        return $response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }

    /** Ambil nilai query tanpa mengandalkan empty() — '0' harus tetap '0'. */
    private function queryParam(string $key)
    {
        // 1) CI request
        $v = $this->request->getGet($key);
        if ($v !== null && $v !== '') {
            return is_array($v) ? (string) reset($v) : (string) $v;
        }
        // 2) $_GET mentah
        if (array_key_exists($key, $_GET)) {
            $v = $_GET[$key];
            if (is_array($v)) {
                $v = reset($v);
            }
            return (string) $v;
        }
        // 3) parse QUERY_STRING manual
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        if ($qs !== '') {
            parse_str($qs, $parsed);
            if (array_key_exists($key, $parsed)) {
                $v = $parsed[$key];
                if (is_array($v)) {
                    $v = reset($v);
                }
                return (string) $v;
            }
        }
        return null;
    }

    public function index()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }

        $rawHal = $this->queryParam('hal');
        if ($rawHal === null) {
            $rawHal = $this->queryParam('page');
        }

        // $hal = 0; // default: halaman fokus
        if ($rawHal !== null && $rawHal !== '') {
            $hal = (int) $rawHal;
        }

        $rawPer = $this->queryParam('per_page');
        $per = ($rawPer !== null && $rawPer !== '') ? (int) $rawPer : 50;
        if ($per < 10) {
            $per = 10;
        }
        if ($per > 100) {
            $per = 100;
        }

        $uid = isset($user['id']) ? (int) $user['id'] : null;
        try {
            $data = (new CatatanService())->listPage($hal, $per, $uid);
            $data['request_hal'] = $hal;
            $data['request_raw'] = $rawHal;
            $data['debug_qs'] = $_SERVER['QUERY_STRING'] ?? '';
            return $this->noStore(ApiResponse::ok($data));
        } catch (\Throwable $e) {
            log_message('error', 'Catatan listPage: {msg}', ['msg' => $e->getMessage()]);
            return ApiResponse::fail('Gagal memuat catatan: ' . $e->getMessage(), 500);
        }
    }

    public function search()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $q = (string) ($this->queryParam('q') ?? '');
        $limit = (int) ($this->queryParam('limit') ?? 80);
        $offset = (int) ($this->queryParam('offset') ?? 0);
        try {
            $rows = (new CatatanService())->search($q, $limit, $offset);
            return $this->noStore(ApiResponse::ok(['rows' => $rows, 'q' => $q]));
        } catch (\Throwable $e) {
            log_message('error', 'Catatan search: {msg}', ['msg' => $e->getMessage()]);
            return ApiResponse::fail('Gagal cari catatan: ' . $e->getMessage(), 500);
        }
    }

    public function create()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        try {
            $row = (new CatatanService())->createEmpty(isset($user['id']) ? (int) $user['id'] : null);
            return ApiResponse::ok($row, 'Baris ditambah');
        } catch (\Throwable $e) {
            return ApiResponse::fail('Gagal tambah baris: ' . $e->getMessage(), 500);
        }
    }

    public function patch($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true);
        if (!is_array($json)) {
            $json = [];
        }
        $field = (string) ($json['field'] ?? '');
        $value = $json['value'] ?? '';
        try {
            $res = (new CatatanService())->updateField(
                (int) $id,
                $field,
                $value,
                isset($user['id']) ? (int) $user['id'] : null
            );
        } catch (\Throwable $e) {
            return ApiResponse::fail('Gagal simpan: ' . $e->getMessage(), 500);
        }
        if (empty($res['ok'])) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        $payload = $res['data'];
        if (is_array($payload) && !empty($res['meta'])) {
            $payload = array_merge($payload, ['_meta' => $res['meta']]);
        }
        return ApiResponse::ok($payload, 'Tersimpan');
    }

    public function delete($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        try {
            $res = (new CatatanService())->deleteRow((int) $id);
        } catch (\Throwable $e) {
            return ApiResponse::fail('Gagal hapus: ' . $e->getMessage(), 500);
        }
        if (empty($res['ok'])) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok(null, 'Dihapus');
    }
}
