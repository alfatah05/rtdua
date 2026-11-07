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

    public function index()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $pageParam = $this->request->getGet('page');
        $page = ($pageParam === null || $pageParam === '') ? 0 : (int) $pageParam;
        $per  = (int) ($this->request->getGet('per_page') ?? 50);
        $data = (new CatatanService())->listPage($page, $per);
        return ApiResponse::ok($data);
    }

    public function search()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $q = (string) ($this->request->getGet('q') ?? '');
        $limit = (int) ($this->request->getGet('limit') ?? 80);
        $offset = (int) ($this->request->getGet('offset') ?? 0);
        $rows = (new CatatanService())->search($q, $limit, $offset);
        return ApiResponse::ok(['rows' => $rows, 'q' => $q]);
    }

    public function create()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $row = (new CatatanService())->createEmpty(isset($user['id']) ? (int) $user['id'] : null);
        return ApiResponse::ok($row, 'Baris ditambah');
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
        $res = (new CatatanService())->updateField(
            (int) $id,
            $field,
            $value,
            isset($user['id']) ? (int) $user['id'] : null
        );
        if (empty($res['ok'])) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data'], 'Tersimpan');
    }

    public function delete($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new CatatanService())->deleteRow((int) $id);
        if (empty($res['ok'])) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok(null, 'Dihapus');
    }
}
