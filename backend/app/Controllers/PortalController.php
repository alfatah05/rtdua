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
        $list = array_map(static function ($a) {
            return [
                'id'            => (int) $a['id'],
                'nama'          => $a['nama'],
                'hubungan'      => $a['hubungan'],
                'jenis_kelamin' => $a['jenis_kelamin'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'foto'          => $a['foto'],
                'status'        => $a['status'],
            ];
        }, $anggota);
        return ApiResponse::ok([
            'id'      => (int) $k['id'],
            'alamat'  => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
            'anggota' => $list,
        ]);
    }
}
