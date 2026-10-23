<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\RewardService;
use CodeIgniter\Controller;

class RewardController extends Controller
{
    private function requirePengurus(): ?array
    {
        $side = SideContext::fromRequest();
        if ($side !== 'pengurus') {
            return null;
        }
        $user = (new AuthService())->current($side);
        if (!$user || !in_array($user['role'], ['ketua', 'pengurus'], true)) {
            return null;
        }
        return $user;
    }

    private function requireLogin(): ?array
    {
        $side = SideContext::fromRequest();
        $user = (new AuthService())->current($side);
        return $user ?: null;
    }

    public function list()
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $svc = new RewardService();
        return ApiResponse::ok(['items' => $svc->listEvent()]);
    }

    public function detail($id)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $side = SideContext::fromRequest();
        $pengurus = $side === 'pengurus' && in_array($user['role'] ?? '', ['ketua', 'pengurus'], true);
        $keluargaId = !empty($user['keluarga_id']) ? (int) $user['keluarga_id'] : null;
        $svc = new RewardService();
        $data = $svc->detailEvent((int) $id, $keluargaId, $pengurus);
        if (!$data) {
            return ApiResponse::fail('Event tidak ditemukan.', 404);
        }
        return ApiResponse::ok($data);
    }

    public function buat()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Hanya pengurus.', 403);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RewardService())->buatEvent($json, (int) $user['id']);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data'], 'Event dibuat');
    }

    public function ubah($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Hanya pengurus.', 403);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RewardService())->ubahEvent((int) $id, $json, (int) $user['id']);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data'], 'Event diubah');
    }

    public function hapus($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Hanya pengurus.', 403);
        }
        $res = (new RewardService())->hapusEvent((int) $id, (int) $user['id']);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok(null, 'Event dihapus');
    }

    public function undi($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Hanya pengurus.', 403);
        }
        $res = (new RewardService())->undi((int) $id, (int) $user['id']);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal undi', 422);
        }
        return ApiResponse::ok($res['data'], 'Undian selesai');
    }

    public function batalkanBatch($batchId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Hanya pengurus.', 403);
        }
        $res = (new RewardService())->batalkanBatch((int) $batchId, (int) $user['id']);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data'], 'Batch dibatalkan');
    }

    public function resetSiklus($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Hanya pengurus.', 403);
        }
        $res = (new RewardService())->resetSiklus((int) $id, (int) $user['id']);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data'], 'Siklus direset');
    }

    public function klaim($pemenangId)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return ApiResponse::fail('Belum login.', 401);
        }
        $keluargaId = !empty($user['keluarga_id']) ? (int) $user['keluarga_id'] : 0;
        if ($keluargaId < 1) {
            return ApiResponse::fail('Akun tidak tertaut keluarga.', 422);
        }
        $res = (new RewardService())->klaim((int) $pemenangId, (int) $user['id'], $keluargaId);
        if (!$res['ok']) {
            return ApiResponse::fail($res['message'] ?? 'Gagal klaim', 422);
        }
        return ApiResponse::ok($res['data'], 'Berhasil diklaim');
    }
}
