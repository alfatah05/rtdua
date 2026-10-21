<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\PembayaranService;
use App\Services\TagihanService;
use CodeIgniter\Controller;

/**
 * Endpoint keuangan sisi warga (/api/portal/*).
 * Tidak pernah mengirim NIK atau data keluarga lain.
 */
class PortalKeuanganController extends Controller
{
    public function ringkasanSaya()
    {
        $ctx = $this->requireWarga();
        if (!$ctx) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $ring = (new TagihanService())->ringkasanKeluarga($ctx['keluarga_id']);
        $komponen = (new \App\Services\IuranKomponenService())->list(true);
        return ApiResponse::ok([
            'total'      => $ring['total'],
            'status'     => $ring['status'],
            'menunggak'  => $ring['menunggak'],
            'komponen_kas' => $komponen,
            'total_kas_bulanan' => (new \App\Services\IuranKomponenService())->totalAktif(),
            'tagihan'    => array_map(static function ($t) {
                return [
                    'id'      => $t['id'],
                    'periode' => $t['periode'],
                    'jenis'   => $t['jenis'],
                    'nama'    => $t['nama'] ?? null,
                    'nominal' => $t['nominal'],
                    'sisa'    => $t['sisa'],
                ];
            }, $ring['tagihan']),
            'pembayaran' => array_map(static function ($p) {
                return [
                    'id'             => $p['id'],
                    'tanggal_bayar'  => $p['tanggal_bayar'],
                    'metode'         => $p['metode'],
                    'nominal'        => $p['nominal'],
                    'catatan'        => $p['catatan'],
                    'nama_pengirim'  => $p['nama_pengirim'] ?? null,
                    'bank_pengirim'  => $p['bank_pengirim'] ?? null,
                    'metode_rekening'=> $p['metode_rekening'] ?? null,
                ];
            }, $ring['pembayaran']),
            'riwayat' => array_map(static function ($r) {
                return [
                    'pembayaran_id'  => $r['pembayaran_id'] ?? null,
                    'tagihan_id'     => $r['tagihan_id'] ?? null,
                    'tanggal'        => $r['tanggal'] ?? null,
                    'jenis'          => $r['jenis'] ?? '',
                    'periode'        => $r['periode'] ?? '',
                    'nama'           => $r['nama'] ?? null,
                    'jumlah'         => (int) ($r['jumlah'] ?? 0),
                    'metode'         => $r['metode'] ?? null,
                    'nama_pengirim'  => $r['nama_pengirim'] ?? null,
                    'bank_pengirim'  => $r['bank_pengirim'] ?? null,
                    'metode_rekening'=> $r['metode_rekening'] ?? null,
                ];
            }, $ring['riwayat'] ?? []),
        ]);
    }

    public function ajukanTransfer()
    {
        $ctx = $this->requireWarga();
        if (!$ctx) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        if (!empty($json['bukti_base64']) && empty($json['bukti_file'])) {
            $path = $this->simpanBuktiBase64((string) $json['bukti_base64'], $ctx['keluarga_id']);
            if ($path === null) {
                return ApiResponse::fail('Gagal menyimpan bukti.', 422);
            }
            $json['bukti_file'] = $path;
        }
        $res = (new PembayaranService())->ajukanPermintaan($json, $ctx['user_id'], $ctx['keluarga_id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Permintaan dikirim') : ApiResponse::fail($res['message'], 422);
    }

    public function statusPermintaan()
    {
        $ctx = $this->requireWarga();
        if (!$ctx) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $db = \Config\Database::connect();
        $rows = $db->table('pembayaran_permintaan')
            ->where('keluarga_id', $ctx['keluarga_id'])
            ->orderBy('diajukan_pada', 'DESC')
            ->get()
            ->getResultArray();
        $data = array_map(static function ($r) {
            return [
                'id'               => (int) $r['id'],
                'status'           => $r['status'],
                'nominal_diajukan' => (int) $r['nominal_diajukan'],
                'nominal'          => (int) $r['nominal_diajukan'],
                'nominal_dikonfirmasi' => $r['nominal_dikonfirmasi'] ? (int) $r['nominal_dikonfirmasi'] : null,
                'nama_pengirim'    => $r['nama_pengirim'],
                'bank_pengirim'    => $r['bank_pengirim'],
                'metode_rekening'  => $r['metode_rekening'] ?? null,
                'diajukan_pada'    => $r['diajukan_pada'],
                'alasan_tolak'     => $r['alasan_tolak'],
            ];
        }, $rows);
        return ApiResponse::ok($data);
    }

    public function kas()
    {
        $ctx = $this->requireWarga();
        if (!$ctx) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        try {
            $data = (new \App\Services\KasService())->portalRingkasanKas();
            return ApiResponse::ok($data);
        } catch (\Throwable $e) {
            return ApiResponse::fail('Gagal memuat kas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Status iuran seluruh keluarga aktif (untuk tab Status Iuran Warga).
     * Data publik antar warga: nama, alamat, foto, status lunas/belum.
     */
    public function statusIuranWarga()
    {
        $ctx = $this->requireWarga();
        if (!$ctx) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        try {
            $list = (new TagihanService())->daftarIuran([]);
            $db = \Config\Database::connect();
            $out = [];
            foreach ($list as $row) {
                $kid = (int) ($row['keluarga_id'] ?? 0);
                $foto = null;
                if ($kid > 0) {
                    $kepala = $db->table('warga')
                        ->select('foto')
                        ->where('keluarga_id', $kid)
                        ->where('hubungan', 'Kepala keluarga')
                        ->where('status', 'aktif')
                        ->get()
                        ->getRowArray();
                    $foto = $kepala['foto'] ?? null;
                }
                $st = (string) ($row['status'] ?? '');
                $lunas = $st === 'lunas';
                $out[] = [
                    'id'     => $kid,
                    'nama'   => $row['nama'] ?? '—',
                    'alamat' => $row['alamat'] ?? '',
                    'foto'   => $foto,
                    'status' => $lunas ? 'lunas' : 'belum_lunas',
                    'total'  => (int) ($row['total'] ?? 0),
                ];
            }
            return ApiResponse::ok($out);
        } catch (\Throwable $e) {
            return ApiResponse::fail('Gagal memuat status iuran: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @return array{user_id:int, keluarga_id:int}|null
     */
    private function requireWarga(): ?array
    {
        if (SideContext::fromRequest() !== 'warga') {
            return null;
        }
        $user = (new AuthService())->current('warga');
        if (!$user || empty($user['keluarga_id'])) {
            return null;
        }
        return [
            'user_id'     => (int) $user['id'],
            'keluarga_id' => (int) $user['keluarga_id'],
        ];
    }

    private function simpanBuktiBase64(string $b64, int $keluargaId): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $b64, $m)) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
            $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        } else {
            $ext = 'jpg';
        }
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }
        $raw = base64_decode($b64, true);
        if ($raw === false || strlen($raw) < 100 || strlen($raw) > 5 * 1024 * 1024) {
            return null;
        }
        $dir = WRITEPATH . 'uploads/bukti';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $name = 'k' . $keluargaId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $full = $dir . '/' . $name;
        if (file_put_contents($full, $raw) === false) {
            return null;
        }
        return 'bukti/' . $name;
    }
}
