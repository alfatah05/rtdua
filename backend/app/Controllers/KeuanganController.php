<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SideContext;
use App\Services\AuthService;
use App\Services\IuranKhususService;
use App\Services\KasPosService;
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
            'bulan'      => $this->request->getGet('bulan'),
            'tipe'       => $this->request->getGet('tipe'),
            'kategori'   => $this->request->getGet('kategori'),
            'kas_pos_id' => $this->request->getGet('kas_pos_id'),
        ];
        $svc = new KasService();
        return ApiResponse::ok([
            'saldo' => $svc->saldo(),
            'pos'   => (new KasPosService())->list(false),
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

    // ---- Pos kas (multi-kas) ----

    public function listKasPos()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        return ApiResponse::ok((new KasPosService())->list(false));
    }

    public function buatKasPos()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasPosService())->create($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Pos kas ditambah') : ApiResponse::fail($res['message'], 422);
    }

    public function ubahKasPos($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasPosService())->update((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function nonaktifKasPos($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new KasPosService())->nonaktifkan((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok(null, 'Pos dinonaktifkan') : ApiResponse::fail($res['message'], 422);
    }

    /** Pindah dana antar pos (mis. Kas RT → Kas sosial). */
    public function alokasiKasPos()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new KasService())->alokasiAntarPos($json, (int) $user['id']);
        return $res['ok']
            ? ApiResponse::ok($res['data'], 'Alokasi berhasil')
            : ApiResponse::fail($res['message'], 422);
    }

    // ---- Komponen iuran tetap ----

    public function listIuranKomponen()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $svc = new \App\Services\IuranKomponenService();
        $items = $svc->list(false);
        return ApiResponse::ok([
            'items' => $items,
            'total' => $svc->totalAktif(),
        ]);
    }

    public function buatIuranKomponen()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new \App\Services\IuranKomponenService())->create($json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Komponen ditambah') : ApiResponse::fail($res['message'], 422);
    }

    public function ubahIuranKomponen($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new \App\Services\IuranKomponenService())->update((int) $id, $json, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Disimpan') : ApiResponse::fail($res['message'], 422);
    }

    public function hapusIuranKomponen($id)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new \App\Services\IuranKomponenService())->delete((int) $id, (int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Dihapus') : ApiResponse::fail($res['message'], 422);
    }

    public function terapkanKomponenBulanIni()
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $res = (new \App\Services\IuranKomponenService())->terapkanBulanIni((int) $user['id']);
        return $res['ok'] ? ApiResponse::ok($res['data'], 'Diterapkan ke bulan ini') : ApiResponse::fail($res['message'] ?? 'Gagal', 422);
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

    /** Tagihan khusus untuk satu keluarga saja. */
    public function buatTagihanKhususKeluarga($keluargaId)
    {
        $user = $this->requirePengurus();
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        $json = $this->request->getJSON(true) ?? [];
        $res = (new IuranKhususService())->buatPerKeluarga(
            (int) $keluargaId,
            $json,
            (int) $user['id']
        );
        return $res['ok']
            ? ApiResponse::ok($res['data'], 'Tagihan khusus dibuat')
            : ApiResponse::fail($res['message'] ?? 'Gagal', 422);
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
        $totals = $kas->totalMasukKeluarTanpaAlokasi($bulan);
        $perKat = [];
        foreach ($items as $it) {
            if (!empty($it['is_alokasi'])) {
                if (!isset($perKat[KasService::KAT_ALOKASI])) {
                    $perKat[KasService::KAT_ALOKASI] = ['masuk' => 0, 'keluar' => 0, 'alokasi' => 0];
                }
                $perKat[KasService::KAT_ALOKASI]['alokasi'] += $it['nominal'];
                continue;
            }
            $k = $it['kategori'];
            if (!isset($perKat[$k])) {
                $perKat[$k] = ['masuk' => 0, 'keluar' => 0];
            }
            $perKat[$k][$it['tipe']] += $it['nominal'];
        }
        return ApiResponse::ok([
            'bulan'             => $bulan,
            'saldo_keseluruhan' => $kas->saldo(),
            'total_masuk'       => $totals['masuk'],
            'total_keluar'      => $totals['keluar'],
            'per_kategori'      => $perKat,
            'transaksi'         => $items,
            'rekap_iuran'       => (new TagihanService())->daftarIuran([]),
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
