<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\IuranKhususService;
use App\Services\KasService;
use App\Services\PembayaranService;
use App\Services\TagihanService;
use CodeIgniter\Controller;

class KeuanganController extends Controller
{
    // ---- Tagihan / Iuran ----

    public function ringkasanKeluarga($keluargaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new TagihanService())->ringkasanKeluarga((int) $keluargaId));
    }

    public function pratinjauAlokasi($keluargaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $nominal = (int) ($json['nominal'] ?? $this->request->getGet('nominal') ?? 0);
        return ApiResponse::ok((new TagihanService())->pratinjauAlokasi((int) $keluargaId, $nominal));
    }

    public function daftarIuran()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $filter = [
            'status'  => $this->request->getGet('status'),
            'blok_id' => $this->request->getGet('blok_id'),
        ];
        return ApiResponse::ok((new TagihanService())->daftarIuran($filter));
    }

    public function ubahNominal()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new TagihanService())->ubahNominal($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'OK') : ApiResponse::fail($res['message'], 422);
    }

    public function pastikanTagihanKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $periode = $json['periode'] ?? date('Y-m');
        $n = (new TagihanService())->pastikanTagihanKasBulan($periode);
        return ApiResponse::ok(['dibuat' => $n, 'periode' => $periode]);
    }

    // ---- Pembayaran ----

    public function catatPembayaran()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->catat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Pembayaran dicatat') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanPembayaran($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->batalkanPembayaran((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Pembayaran dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanDenda($tagihanId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->batalkanDenda((int) $tagihanId, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Denda dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    public function listPermintaan()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $status = $this->request->getGet('status') ?? 'menunggu';
        return ApiResponse::ok((new PembayaranService())->listPermintaan($status));
    }

    public function konfirmasiPermintaan($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->konfirmasiPermintaan((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Dikonfirmasi') : ApiResponse::fail($res['message'], 422);
    }

    public function tolakPermintaan($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->tolakPermintaan((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Ditolak') : ApiResponse::fail($res['message'], 422);
    }

    // ---- Kas ----

    public function saldoKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok(['saldo' => (new KasService())->saldo()]);
    }

    public function listKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $filter = [
            'bulan'    => $this->request->getGet('bulan'),
            'tipe'     => $this->request->getGet('tipe'),
            'kategori' => $this->request->getGet('kategori'),
        ];
        $svc = new KasService();
        return ApiResponse::ok([
            'saldo' => $svc->saldo(),
            'items' => $svc->list($filter),
        ]);
    }

    public function tambahKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasService())->tambah($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Kas dicatat') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanKas($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasService())->batalkan((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Kas dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    // ---- Iuran khusus ----

    public function listIuranKhusus()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new IuranKhususService())->list());
    }

    public function buatIuranKhusus()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->buat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Iuran khusus dibuat') : ApiResponse::fail($res['message'], 422);
    }

    public function ubahIuranKhusus($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->ubahNominal((int) $id, (int) ($json['nominal'] ?? 0), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanIuranKhusus($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->batalkan((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    // ---- Laporan ringkas ----

    public function laporan()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        $kas = new KasService();
        $items = $kas->list(['bulan' => $bulan]);
        $masuk = 0;
        $keluar = 0;
        $perKat = [];
        foreach ($items as $it) {
            if ($it['tipe'] === 'masuk') {
                $masuk += $it['nominal'];
            } else {
                $keluar += $it['nominal'];
            }
            $k = $it['kategori'];
            if (!isset($perKat[$k])) {
                $perKat[$k] = ['masuk' => 0, 'keluar' => 0];
            }
            $perKat[$k][$it['tipe']] += $it['nominal'];
        }
        return ApiResponse::ok([
            'bulan'          => $bulan,
            'saldo_keseluruhan' => $kas->saldo(),
            'total_masuk'    => $masuk,
            'total_keluar'   => $keluar,
            'per_kategori'   => $perKat,
            'transaksi'      => $items,
            'rekap_iuran'    => (new TagihanService())->daftarIuran([]),
        ]);
    }

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
}
