<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\IuranKhususService;
use App\Services\IuranKomponenService;
use App\Services\KasPosService;
use App\Services\KasService;
use App\Services\PembayaranService;
use App\Services\TagihanService;
use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use CodeIgniter\RESTful\ResourceController;

class KeuanganController extends ResourceController
{
    public function ringkasanKeluarga($keluargaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $data = (new TagihanService())->ringkasanKeluarga((int) $keluargaId);
        return ApiResponse::ok($data);
    }

    public function pratinjauAlokasi($keluargaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $nominal = (int) ($this->request->getGet('nominal') ?? 0);
        $data = (new TagihanService())->pratinjauAlokasi((int) $keluargaId, $nominal);
        return ApiResponse::ok($data);
    }

    public function daftarIuran()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $filters = [
            'q' => $this->request->getGet('q'),
            'status' => $this->request->getGet('status'),
        ];
        return ApiResponse::ok((new TagihanService())->daftarIuran($filters));
    }

    public function ubahNominal($tagihanId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new TagihanService())->ubahNominal((int) $tagihanId, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Nominal diubah') : ApiResponse::fail($res['message'], 422);
    }

    public function pastikanTagihanKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $periode = (string) ($json['periode'] ?? date('Y-m'));
        $res = (new TagihanService())->pastikanTagihanKasBulan($periode, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Tagihan kas dipastikan') : ApiResponse::fail($res['message'], 422);
    }

    public function catatPembayaran()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->catat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Pembayaran dicatat') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanPembayaran($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->batalkan((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Pembayaran dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanDenda($tagihanId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new TagihanService())->ubahNominal((int) $tagihanId, ['nominal' => 0, 'alasan' => $json['alasan'] ?? 'Batalkan denda'], (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Denda dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    public function listPermintaan()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new PembayaranService())->listPermintaan($this->request->getGet() ?? []));
    }

    public function konfirmasiPermintaan($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new PembayaranService())->konfirmasiPermintaan((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Permintaan dikonfirmasi') : ApiResponse::fail($res['message'], 422);
    }

    public function tolakPermintaan($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new PembayaranService())->tolakPermintaan((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Permintaan ditolak') : ApiResponse::fail($res['message'], 422);
    }

    public function saldoKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new KasService())->saldo());
    }

    public function listKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new KasService())->list($this->request->getGet() ?? []));
    }

    public function tambahKas()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasService())->tambah($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Kas dicatat') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanKas($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasService())->batalkan((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Kas dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    public function listKasPos()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new KasPosService())->list());
    }

    public function buatKasPos()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasPosService())->buat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Pos kas dibuat') : ApiResponse::fail($res['message'], 422);
    }

    public function ubahKasPos($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasPosService())->ubah((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Pos kas diubah') : ApiResponse::fail($res['message'], 422);
    }

    public function nonaktifKasPos($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new KasPosService())->nonaktif((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Pos kas dinonaktifkan') : ApiResponse::fail($res['message'], 422);
    }

    public function alokasiKasPos()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasService())->alokasiAntarPos($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Alokasi berhasil') : ApiResponse::fail($res['message'], 422);
    }

    public function listIuranKomponen()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new IuranKomponenService())->list());
    }

    public function buatIuranKomponen()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKomponenService())->buat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Komponen dibuat') : ApiResponse::fail($res['message'], 422);
    }

    public function ubahIuranKomponen($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKomponenService())->ubah((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Komponen diubah') : ApiResponse::fail($res['message'], 422);
    }

    public function hapusIuranKomponen($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new IuranKomponenService())->hapus((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Komponen dihapus') : ApiResponse::fail($res['message'], 422);
    }

    public function terapkanKomponenBulanIni()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKomponenService())->terapkanBulanIni($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Komponen diterapkan') : ApiResponse::fail($res['message'], 422);
    }

    public function listIuranKhusus()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new IuranKhususService())->list($this->request->getGet() ?? []));
    }

    public function buatIuranKhusus()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->buat($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'] ?? null, 'Iuran khusus dibuat') : ApiResponse::fail($res['message'], 422);
    }

    public function ubahIuranKhusus($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->ubahNominal((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Iuran khusus diubah') : ApiResponse::fail($res['message'], 422);
    }

    public function batalkanIuranKhusus($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->batalkan((int) $id, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Iuran khusus dibatalkan') : ApiResponse::fail($res['message'], 422);
    }

    public function buatTagihanKhususKeluarga($keluargaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->buatPerKeluarga((int) $keluargaId, $json, (int) $user['id']);
        return $res['ok']
            ? ApiResponse::ok($res['data'], 'Tagihan khusus dibuat')
            : ApiResponse::fail($res['message'], 422);
    }

    public function ubahTagihanKhusus($tagihanId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->ubahByTagihan((int) $tagihanId, $json, (int) $user['id']);
        return $res['ok']
            ? ApiResponse::ok($res['data'] ?? null, 'Tagihan khusus diubah')
            : ApiResponse::fail($res['message'], 422);
    }

    public function hapusTagihanKhusus($tagihanId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->hapusByTagihan((int) $tagihanId, (string) ($json['alasan'] ?? ''), (int) $user['id']);
        return $res['ok']
            ? ApiResponse::ok($res['data'] ?? null, 'Tagihan khusus dihapus')
            : ApiResponse::fail($res['message'], 422);
    }

    public function laporan()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $mk = (new KasService())->totalMasukKeluarTanpaAlokasi($this->request->getGet() ?? []);
        return ApiResponse::ok([
            'kas' => $mk,
            'iuran' => (new TagihanService())->daftarIuran([]),
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
