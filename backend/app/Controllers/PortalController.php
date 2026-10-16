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

    /** Data keluarga sendiri untuk warga (tanpa NIK plaintext). */
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
            ->select('id, nama, hubungan, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, pekerjaan, foto, status, nik_enc')
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
                'tempat_lahir'  => $a['tempat_lahir'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'agama'         => $a['agama'],
                'pekerjaan'     => $a['pekerjaan'],
                'foto'          => $a['foto'],
                'status'        => $a['status'],
                'punya_nik'     => !empty($a['nik_enc']),
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
        if (str_contains(strtolower($hubungan), 'kepala')) {
            return ApiResponse::fail('Hubungan Kepala keluarga hanya bisa diatur pengurus', 422);
        }

        $nik = isset($body['nik']) ? preg_replace('/\D/', '', (string) $body['nik']) : '';
        if ($nik !== '' && strlen($nik) !== 16) {
            return ApiResponse::fail('NIK harus 16 digit jika diisi.', 422);
        }

        $payload = [
            'nama'          => $nama,
            'hubungan'      => $hubungan,
            'nik'           => $nik !== '' ? $nik : null,
            'jenis_kelamin' => !empty($body['jenis_kelamin']) ? (string) $body['jenis_kelamin'] : null,
            'tempat_lahir'  => !empty($body['tempat_lahir']) ? trim((string) $body['tempat_lahir']) : null,
            'tanggal_lahir' => !empty($body['tanggal_lahir']) ? (string) $body['tanggal_lahir'] : null,
            'agama'         => !empty($body['agama']) ? trim((string) $body['agama']) : null,
            'pekerjaan'     => !empty($body['pekerjaan']) ? trim((string) $body['pekerjaan']) : null,
            'no_kk'         => !empty($body['no_kk']) ? trim((string) $body['no_kk']) : null,
        ];
        $res = (new KeluargaService())->tambahAnggota($kid, $payload, (int) $user['id']);
        if (!($res['ok'] ?? false)) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok($res['data'] ?? ['id' => 0], 'Anggota ditambahkan');
    }

    /**
     * Warga ubah data anggota milik keluarga sendiri.
     * Tidak bisa ganti jadi/bukan Kepala keluarga (kecuali sudah kepala: hubungan dikunci).
     */
    public function updateAnggotaSaya($id)
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
        $anggotaId = (int) $id;
        if ($anggotaId < 1) {
            return ApiResponse::fail('Tidak valid', 400);
        }

        $db = \Config\Database::connect();
        $row = $db->table('warga')
            ->where('id', $anggotaId)
            ->where('keluarga_id', $kid)
            ->get()->getRowArray();
        if (!$row) {
            return ApiResponse::fail('Anggota tidak ditemukan di keluarga Anda', 404);
        }
        if (($row['status'] ?? '') !== 'aktif') {
            return ApiResponse::fail('Anggota tidak aktif, tidak bisa diubah', 422);
        }

        $body = $this->request->getJSON(true) ?? [];
        $isKepala = str_contains(strtolower((string) ($row['hubungan'] ?? '')), 'kepala');

        $payload = [];
        if (array_key_exists('nama', $body)) {
            $nama = trim((string) $body['nama']);
            if ($nama === '') {
                return ApiResponse::fail('Nama tidak boleh kosong', 422);
            }
            $payload['nama'] = $nama;
        }

        if (array_key_exists('hubungan', $body)) {
            $hub = trim((string) $body['hubungan']);
            if ($isKepala) {
                // Kepala tetap kepala — abaikan perubahan hubungan
                $payload['hubungan'] = 'Kepala keluarga';
            } else {
                if ($hub === '' || str_contains(strtolower($hub), 'kepala')) {
                    return ApiResponse::fail('Hubungan Kepala keluarga hanya bisa diatur pengurus', 422);
                }
                $payload['hubungan'] = $hub;
            }
        }

        foreach (['jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'pekerjaan'] as $k) {
            if (array_key_exists($k, $body)) {
                $v = $body[$k];
                if ($v === '' || $v === null) {
                    $payload[$k] = null;
                } else {
                    $payload[$k] = is_string($v) ? trim($v) : $v;
                }
            }
        }

        if (array_key_exists('nik', $body) && $body['nik'] !== null && $body['nik'] !== '') {
            $nik = preg_replace('/\D/', '', (string) $body['nik']);
            if (strlen($nik) !== 16) {
                return ApiResponse::fail('NIK harus 16 digit jika diisi.', 422);
            }
            $payload['nik'] = $nik;
        }

        if ($payload === []) {
            return ApiResponse::fail('Tidak ada perubahan', 422);
        }

        $res = (new KeluargaService())->updateAnggota($anggotaId, $payload, (int) $user['id']);
        if (!($res['ok'] ?? false)) {
            return ApiResponse::fail($res['message'] ?? 'Gagal', 422);
        }
        return ApiResponse::ok(null, 'Data anggota disimpan');
    }
}
