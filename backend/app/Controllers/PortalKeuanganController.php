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
        // Sembunyikan detail internal yang tidak perlu warga
        return ApiResponse::ok([
            'total'      => $ring['total'],
            'status'     => $ring['status'],
            'menunggak'  => $ring['menunggak'],
            'tagihan'    => array_map(static function ($t) {
                return [
                    'id'      => $t['id'],
                    'periode' => $t['periode'],
                    'jenis'   => $t['jenis'],
                    'nominal' => $t['nominal'],
                    'sisa'    => $t['sisa'],
                ];
            }, $ring['tagihan']),
            'pembayaran' => array_map(static function ($p) {
                return [
                    'id'            => $p['id'],
                    'tanggal_bayar' => $p['tanggal_bayar'],
                    'metode'        => $p['metode'],
                    'nominal'       => $p['nominal'],
                    'catatan'       => $p['catatan'],
                ];
            }, $ring['pembayaran']),
        ]);
    }

    public function ajukanTransfer()
    {
        $ctx = $this->requireWarga();
        if (!$ctx) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        // Upload bukti: terima path yang sudah diunggah, atau base64 sederhana (dev)
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
            ->limit(10)
            ->get()
            ->getResultArray();
        $data = array_map(static function ($r) {
            return [
                'id'               => (int) $r['id'],
                'nominal_diajukan' => (int) $r['nominal_diajukan'],
                'nominal'          => (int) $r['nominal_diajukan'],
                'status'           => $r['status'],
                'diajukan_pada'    => $r['diajukan_pada'],
                'alasan_tolak'     => $r['alasan_tolak'],
                'nominal_dikonfirmasi' => $r['nominal_dikonfirmasi'] ? (int) $r['nominal_dikonfirmasi'] : null,
            ];
        }, $rows);
        return ApiResponse::ok($data);
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
        return 'bukti/' . $name; // relatif terhadap writable/uploads
    }
}
