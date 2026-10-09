<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\RondaService;
use CodeIgniter\Controller;

class RondaController extends Controller
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
        return (new AuthService())->current($side) ?: null;
    }

    public function kalender()
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $periode = $this->request->getGet('bulan') ?: date('Y-m');
        return ApiResponse::ok((new RondaService())->kalender($periode));
    }

    public function malamIni()
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $d = (new RondaService())->malamIni();
        return ApiResponse::ok($d);
    }

    public function detailMalam($tanggal)
    {
        if (!$this->requireLogin()) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $d = (new RondaService())->detailMalam($tanggal);
        return $d ? ApiResponse::ok($d) : ApiResponse::fail('Tidak ada ronda tanggal itu', 404);
    }

    public function listJadwalTetap()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new RondaService())->listJadwalTetap());
    }

    public function simpanJadwalTetap()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RondaService())->simpanJadwalTetap($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data']) : ApiResponse::fail($res['message'], 422);
    }

    public function listJadwalKhusus()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new RondaService())->listJadwalKhusus());
    }

    public function simpanJadwalKhusus()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RondaService())->simpanJadwalKhusus($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data']) : ApiResponse::fail($res['message'], 422);
    }

    public function generateBulan()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $periode = $json['periode'] ?? date('Y-m');
        $res = (new RondaService())->generateBulan($periode);
        return ApiResponse::ok($res, 'Generate selesai');
    }

    public function isiOtomatis()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RondaService())->isiOtomatis($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Isi otomatis selesai') : ApiResponse::fail($res['message'], 422);
    }

    public function absenManual()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RondaService())->absenManual(
            (int) ($json['malam_id'] ?? 0),
            (int) ($json['keluarga_id'] ?? 0),
            (int) $user['id']
        );
        return $res['ok'] ? ApiResponse::ok(null, 'Absen dicatat') : ApiResponse::fail($res['message'], 422);
    }

    public function absenWarga()
    {
        $side = SideContext::fromRequest();
        if ($side !== 'warga') {
            return ApiResponse::fail('Hanya warga.', 403);
        }
        $user = (new AuthService())->current($side);
        if (!$user || empty($user['keluarga_id'])) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RondaService())->absenWarga(
            (int) ($json['malam_id'] ?? 0),
            (int) $user['keluarga_id'],
            (string) ($json['foto'] ?? '')
        );
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Absen dicatat') : ApiResponse::fail($res['message'], 422);
    }

    public function gantiKeluarga($malamId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new RondaService())->gantiKeluargaMalam(
            (int) $malamId,
            $json['keluarga_ids'] ?? [],
            (int) $user['id']
        );
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Daftar keluarga diperbarui') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanAbsen($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new RondaService())->batalkanAbsen((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Absen dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    public function terbitkanDenda()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $lalu = $json['periode_lalu'] ?? date('Y-m', strtotime('first day of last month'));
        $tagihan = $json['periode_tagihan'] ?? date('Y-m');
        $res = (new RondaService())->terbitkanDenda($lalu, $tagihan);
        return ApiResponse::ok($res);
    }
}
