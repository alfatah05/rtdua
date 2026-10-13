<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\KeluargaService;
use CodeIgniter\Controller;

class PortalController extends Controller
{
    public function daftarWarga()
    {
        if (SideContext::fromRequest() !== 'warga') {
            return ApiResponse::fail('Forbidden', 403);
        }
        $user = (new AuthService())->current('warga');
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $list = (new KeluargaService())->list(['status' => 'aktif']);
        $data = array_map(static function ($k) {
            return [
                'id'     => $k['id'],
                'nama'   => $k['nama'],
                'alamat' => $k['alamat'],
                'foto'   => $k['foto'] ?? null,
            ];
        }, $list);
        return ApiResponse::ok($data);
    }

    /**
     * Detail keluarga publik untuk warga lain.
     * Hanya nama, alamat, foto kepala, anggota (nama + hubungan). Tanpa NIK/telepon.
     */
    public function detailKeluarga($id)
    {
        if (SideContext::fromRequest() !== 'warga') {
            return ApiResponse::fail('Forbidden', 403);
        }
        $user = (new AuthService())->current('warga');
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $kid = (int) $id;
        if ($kid < 1) {
            return ApiResponse::fail('Tidak valid', 400);
        }

        $db = \Config\Database::connect();
        $k = $db->table('keluarga k')
            ->select('k.id, k.nomor, k.akhiran, k.status, b.nama as blok')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('k.id', $kid)
            ->where('k.status', 'aktif')
            ->get()->getRowArray();
        if (!$k) {
            return ApiResponse::fail('Keluarga tidak ditemukan', 404);
        }

        $anggotaRows = $db->table('warga')
            ->select('id, nama, hubungan, foto, status')
            ->where('keluarga_id', $kid)
            ->where('status', 'aktif')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $kepalaNama = null;
        $kepalaFoto = null;
        $anggota = [];
        foreach ($anggotaRows as $a) {
            $hub = (string) ($a['hubungan'] ?? '');
            $isKepala = str_contains(strtolower($hub), 'kepala');
            if ($isKepala && $kepalaNama === null) {
                $kepalaNama = $a['nama'];
                $kepalaFoto = $a['foto'] ?: null;
            }
            $anggota[] = [
                'id'       => (int) $a['id'],
                'nama'     => $a['nama'],
                'hubungan' => $hub !== '' ? $hub : '—',
            ];
        }
        if ($kepalaNama === null && $anggotaRows) {
            $kepalaNama = $anggotaRows[0]['nama'] ?? null;
            $kepalaFoto = $anggotaRows[0]['foto'] ?? null;
        }

        return ApiResponse::ok([
            'id'      => (int) $k['id'],
            'nama'    => $kepalaNama ?: '—',
            'alamat'  => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
            'foto'    => $kepalaFoto,
            'anggota' => $anggota,
        ]);
    }

    /** Data keluarga sendiri untuk warga (tanpa NIK). */
    public function keluargaSaya()
    {
        $side = SideContext::fromRequest();
        if ($side !== 'warga') {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $user = (new AuthService())->current($side);
        if (!$user || empty($user['keluarga_id'])) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $kid = (int) $user['keluarga_id'];
        $db = \Config\Database::connect();
        $k = $db->table('keluarga k')
            ->select('k.id, k.nomor, k.akhiran, b.nama as blok')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('k.id', $kid)
            ->get()->getRowArray();
        if (!$k) {
            return ApiResponse::fail('Keluarga tidak ditemukan', 404);
        }
        $anggota = $db->table('warga')
            ->select('id, nama, hubungan, jenis_kelamin, tanggal_lahir, foto, status')
            ->where('keluarga_id', $kid)
            ->whereIn('status', ['aktif', 'meninggal'])
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $kepalaNama = null;
        $kepalaFoto = null;
        $list = [];
        foreach ($anggota as $a) {
            $hub = (string) ($a['hubungan'] ?? '');
            $isKepala = str_contains(strtolower($hub), 'kepala');
            if ($isKepala && $kepalaNama === null) {
                $kepalaNama = $a['nama'];
                $kepalaFoto = $a['foto'] ?: null;
            }
            $list[] = [
                'id'            => (int) $a['id'],
                'nama'          => $a['nama'],
                'hubungan'      => $hub !== '' ? $hub : '—',
                'jenis_kelamin' => $a['jenis_kelamin'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'foto'          => $a['foto'],
                'status'        => $a['status'],
            ];
        }
        if ($kepalaNama === null && $anggota) {
            $kepalaNama = $anggota[0]['nama'] ?? null;
            $kepalaFoto = $anggota[0]['foto'] ?? null;
        }

        return ApiResponse::ok([
            'id'      => (int) $k['id'],
            'nama'    => $kepalaNama ?: '—',
            'alamat'  => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
            'foto'    => $kepalaFoto,
            'anggota' => $list,
        ]);
    }

    /**
     * Warga menambah anggota untuk keluarga sendiri saja.
     * Body: nama (wajib), hubungan (opsional, default Anggota).
     */
    public function tambahAnggotaSaya()
    {
        $side = SideContext::fromRequest();
        if ($side !== 'warga') {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $user = (new AuthService())->current($side);
        if (!$user || empty($user['keluarga_id'])) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $kid = (int) $user['keluarga_id'];
        $body = $this->request->getJSON(true) ?? [];
        $nama = trim((string) ($body['nama'] ?? ''));
        if ($nama === '') {
            return ApiResponse::fail('Nama wajib diisi', 422);
        }
        $hubungan = trim((string) ($body['hubungan'] ?? 'Anggota'));
        if ($hubungan === '') {
            $hubungan = 'Anggota';
        }
        // Kepala keluarga hanya lewat pengurus
        if (str_contains(strtolower($hubungan), 'kepala')) {
            return ApiResponse::fail('Hubungan Kepala keluarga hanya bisa diatur pengurus', 422);
        }
        $payload = [
            'nama'     => $nama,
            'hubungan' => $hubungan,
        ];
        $res = (new KeluargaService())->tambahAnggota($kid, $payload, (int) $user['id']);
        if (!($res['ok'] ?? false)) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data'] ?? ['id' => 0]);
    }
}
