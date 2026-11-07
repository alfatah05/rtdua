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

    private function queryParam(string $key)
    {
        $v = $this->request->getGet($key);
        if ($v !== null && $v !== '') {
            return is_array($v) ? (string) reset($v) : (string) $v;
        }
        if (array_key_exists($key, $_GET)) {
            $v = $_GET[$key];
            if (is_array($v)) {
                $v = reset($v);
            }
            return (string) $v;
        }
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

    private function resolveHal($halArg = null)
    {
        $source = 'default';
        $raw = null;

        if ($halArg !== null && $halArg !== '') {
            $raw = (string) $halArg;
            $source = 'path';
        }

        if ($raw === null) {
            $hdr = trim((string) $this->request->getHeaderLine('X-Catatan-Hal'));
            if ($hdr !== '') {
                $raw = $hdr;
                $source = 'header';
            }
        }

        if ($raw === null) {
            $q = $this->queryParam('hal');
            if ($q === null) {
                $q = $this->queryParam('page');
            }
            if ($q !== null && $q !== '') {
                $raw = $q;
                $source = 'query';
            }
        }

        $hal = 0;
        if ($raw !== null && $raw !== '') {
            $hal = (int) $raw;
        }

        return [$hal, $raw, $source];
    }

    private function resolvePerPage()
    {
        $rawPer = $this->queryParam('per_page');
        $per = ($rawPer !== null && $rawPer !== '') ? (int) $rawPer : 50;
        if ($per < 10) {
            $per = 10;
        }
        if ($per > 100) {
            $per = 100;
        }
        return $per;
    }

    private function respondList($halArg = null)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }

        list($hal, $raw, $source) = $this->resolveHal($halArg);
        $per = $this->resolvePerPage();
        $uid = isset($user['id']) ? (int) $user['id'] : null;

        try {
            $data = (new CatatanService())->listPage($hal, $per, $uid);
            $data['request_hal'] = $hal;
            $data['request_raw'] = $raw;
            $data['request_source'] = $source;
            $data['debug_qs'] = $_SERVER['QUERY_STRING'] ?? '';
            return $this->noStore(ApiResponse::ok($data));
        } catch (\Throwable $e) {
            log_message('error', 'Catatan listPage: {msg}', ['msg' => $e->getMessage()]);
            return ApiResponse::fail('Gagal memuat catatan: ' . $e->getMessage(), 500);
        }
    }

    public function index()
    {
        return $this->respondList(null);
    }

    /** GET /catatan/hal/(:num) */
    public function byHal($hal = 0)
    {
        return $this->respondList($hal);
    }

    public function search()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }

        // Prioritas: JSON body (POST) → form POST → query string
        $json = $this->request->getJSON(true);
        if (!is_array($json)) {
            $json = [];
        }

        $q = '';
        if (isset($json['q']) && $json['q'] !== null && $json['q'] !== '') {
            $q = trim((string) $json['q']);
        } elseif ($this->request->getPost('q') !== null) {
            $q = trim((string) $this->request->getPost('q'));
        } else {
            $q = trim((string) ($this->queryParam('q') ?? ''));
        }

        $limit = 80;
        if (isset($json['limit']) && is_numeric($json['limit'])) {
            $limit = (int) $json['limit'];
        } elseif ($this->request->getPost('limit') !== null) {
            $limit = (int) $this->request->getPost('limit');
        } else {
            $limit = (int) ($this->queryParam('limit') ?? 80);
        }

        $offset = 0;
        if (isset($json['offset']) && is_numeric($json['offset'])) {
            $offset = (int) $json['offset'];
        } elseif ($this->request->getPost('offset') !== null) {
            $offset = (int) $this->request->getPost('offset');
        } else {
            $offset = (int) ($this->queryParam('offset') ?? 0);
        }

        try {
            $rows = (new CatatanService())->search($q, $limit, $offset);
            return $this->noStore(ApiResponse::ok([
                'rows' => $rows,
                'q'    => $q,
                'limit' => $limit,
                'offset' => $offset,
            ]));
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
            $uid = isset($user['id']) ? (int) $user['id'] : null;
            $row = (new CatatanService())->createEmpty($uid);
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
