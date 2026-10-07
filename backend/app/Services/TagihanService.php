<?php

namespace App\Services;

use App\Libraries\AlokasiPembayaran;

/**
 * Tagihan per keluarga: total, sisa per item, status.
 * Sisa dihitung ulang dari seluruh pembayaran (tidak disimpan per alokasi).
 */
class TagihanService
{
    /**
     * Ringkasan iuran satu keluarga.
     *
     * @return array{
     *   keluarga_id:int,
     *   total:int,
     *   status:string,
     *   menunggak:bool,
     *   tagihan:list<array>,
     *   pembayaran:list<array>
     * }
     */
    public function ringkasanKeluarga(int $keluargaId): array
    {
        $db = \Config\Database::connect();
        $tagihan = $db->table('iuran_tagihan')
            ->where('keluarga_id', $keluargaId)
            ->where('dibatalkan', 0)
            ->orderBy('periode', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $pembayaran = $db->table('pembayaran')
            ->where('keluarga_id', $keluargaId)
            ->where('dibatalkan', 0)
            ->orderBy('tanggal_bayar', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $items = [];
        foreach ($tagihan as $t) {
            $items[] = [
                'id'              => (int) $t['id'],
                'periode'         => $t['periode'],
                'jenis'           => $t['jenis'],
                'nominal'         => (int) $t['nominal'],
                'sisa'            => (int) $t['nominal'],
                'iuran_khusus_id' => $t['iuran_khusus_id'] ? (int) $t['iuran_khusus_id'] : null,
                'ronda_malam_id'  => $t['ronda_malam_id'] ? (int) $t['ronda_malam_id'] : null,
            ];
        }

        $totalBayar = 0;
        foreach ($pembayaran as $p) {
            $nominal = (int) $p['nominal'];
            $totalBayar += $nominal;
            $alok = AlokasiPembayaran::alokasi($items, $nominal);
            foreach ($alok['potongan'] as $pot) {
                foreach ($items as &$it) {
                    if ($it['id'] === (int) $pot['tagihan_id']) {
                        $it['sisa'] = max(0, $it['sisa'] - (int) $pot['jumlah']);
                        break;
                    }
                }
                unset($it);
            }
        }

        $totalTagihan = 0;
        $sisaPositif = 0;
        foreach ($items as $it) {
            $totalTagihan += $it['nominal'];
            $sisaPositif += $it['sisa'];
        }
        // total tampilan: tagihan - bayar (boleh minus = kelebihan)
        $total = $totalTagihan - $totalBayar;

        $bulanIni = date('Y-m');
        $menunggak = false;
        foreach ($items as $it) {
            if ($it['sisa'] > 0 && strcmp($it['periode'], $bulanIni) < 0) {
                $menunggak = true;
                break;
            }
        }

        $status = 'lunas';
        if ($total > 0) {
            $status = $menunggak ? 'menunggak' : 'belum_lunas';
        }

        $bayarList = [];
        foreach ($pembayaran as $p) {
            $bayarList[] = [
                'id'            => (int) $p['id'],
                'tanggal_bayar' => $p['tanggal_bayar'],
                'metode'        => $p['metode'],
                'nominal'       => (int) $p['nominal'],
                'catatan'       => $p['catatan'],
                'dicatat_oleh'  => $p['dicatat_oleh'] ? (int) $p['dicatat_oleh'] : null,
                'permintaan_id' => $p['permintaan_id'] ? (int) $p['permintaan_id'] : null,
            ];
        }

        return [
            'keluarga_id' => $keluargaId,
            'total'       => $total,
            'status'      => $status,
            'menunggak'   => $menunggak,
            'tagihan'     => $items,
            'pembayaran'  => $bayarList,
        ];
    }

    /**
     * Pratinjau alokasi tanpa menyimpan.
     *
     * @return array{potongan:list, sisaBayar:int, total_sekarang:int, total_setelah:int}
     */
    public function pratinjauAlokasi(int $keluargaId, int $nominalBayar): array
    {
        $ring = $this->ringkasanKeluarga($keluargaId);
        $alok = AlokasiPembayaran::alokasi($ring['tagihan'], $nominalBayar);
        return [
            'potongan'      => $alok['potongan'],
            'sisaBayar'     => $alok['sisaBayar'],
            'total_sekarang'=> $ring['total'],
            'total_setelah' => $ring['total'] - $nominalBayar,
        ];
    }

    /**
     * Pastikan tagihan kas ada untuk periode tertentu (semua keluarga aktif).
     * Dipakai saat ubah nominal "bulan ini" atau generate manual dev.
     */
    public function pastikanTagihanKasBulan(string $periode, ?int $nominalOverride = null): int
    {
        $db = \Config\Database::connect();
        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $nominal = $nominalOverride !== null
            ? max(0, $nominalOverride)
            : (int) ($peng['nominal_kas'] ?? 0);

        $keluarga = $db->table('keluarga')->where('status', 'aktif')->get()->getResultArray();
        $dibuat = 0;
        foreach ($keluarga as $k) {
            // Mulai bulan depan: lewati jika mulai_periode null atau > periode
            $mulai = $k['mulai_periode'];
            if ($mulai === null || $mulai === '') {
                // belum mulai tagihan
                continue;
            }
            if (strcmp($mulai, $periode) > 0) {
                continue;
            }
            $ada = $db->table('iuran_tagihan')
                ->where('keluarga_id', (int) $k['id'])
                ->where('periode', $periode)
                ->where('jenis', 'kas')
                ->where('dibatalkan', 0)
                ->countAllResults();
            if ($ada > 0) {
                continue;
            }
            $db->table('iuran_tagihan')->insert([
                'keluarga_id' => (int) $k['id'],
                'periode'     => $periode,
                'jenis'       => 'kas',
                'nominal'     => $nominal,
                'dibatalkan'  => 0,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
            $dibuat++;
        }
        return $dibuat;
    }

    /**
     * Ubah nominal kas/denda di pengaturan + opsi terapkan ke tagihan bulan ini.
     *
     * @return array{ok:bool, message?:string, data?:array}
     */
    public function ubahNominal(array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Pengaturan belum ada.'];
        }

        $update = [];
        $pratinjau = ['keluarga_terdampak' => 0, 'jadi_kelebihan' => 0];

        if (array_key_exists('nominal_kas', $data)) {
            $update['nominal_kas'] = max(0, (int) $data['nominal_kas']);
        }
        if (array_key_exists('denda_ronda', $data)) {
            $update['denda_ronda'] = max(0, (int) $data['denda_ronda']);
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada nominal diubah.'];
        }

        $terapkanBulanIni = !empty($data['terapkan_bulan_ini']);
        $bulanIni = date('Y-m');

        if ($terapkanBulanIni && isset($update['nominal_kas'])) {
            $rows = $db->table('iuran_tagihan')
                ->where('periode', $bulanIni)
                ->where('jenis', 'kas')
                ->where('dibatalkan', 0)
                ->get()
                ->getResultArray();
            $pratinjau['keluarga_terdampak'] = count($rows);
            $baru = $update['nominal_kas'];
            foreach ($rows as $r) {
                // hitung apakah setelah ubah jadi kelebihan
                $ring = $this->ringkasanKeluarga((int) $r['keluarga_id']);
                $selisih = $baru - (int) $r['nominal'];
                if ($ring['total'] + $selisih < 0) {
                    $pratinjau['jadi_kelebihan']++;
                }
            }
        }

        // Mode pratinjau saja?
        if (!empty($data['pratinjau_saja'])) {
            return [
                'ok'   => true,
                'data' => [
                    'pratinjau' => $pratinjau,
                    'nominal_baru' => $update,
                    'terapkan_bulan_ini' => $terapkanBulanIni,
                ],
            ];
        }

        $db->transStart();
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('pengaturan')->where('id', 1)->update($update);

        if ($terapkanBulanIni && isset($update['nominal_kas'])) {
            $db->table('iuran_tagihan')
                ->where('periode', $bulanIni)
                ->where('jenis', 'kas')
                ->where('dibatalkan', 0)
                ->update(['nominal' => $update['nominal_kas']]);
        }
        // Denda ronda: hanya tagihan baru (bulan depan) — tidak ubah denda yang sudah terbit
        $db->transComplete();

        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal menyimpan nominal.'];
        }

        (new AuditService())->log(
            'ubah_nominal_iuran',
            'pengaturan',
            1,
            [
                'nominal_kas' => $before['nominal_kas'] ?? null,
                'denda_ronda' => $before['denda_ronda'] ?? null,
            ],
            array_merge($update, ['terapkan_bulan_ini' => $terapkanBulanIni]),
            'pengguna',
            true,
            $userId
        );

        return [
            'ok'   => true,
            'data' => [
                'pratinjau' => $pratinjau,
                'nominal'   => $update,
            ],
        ];
    }

    /**
     * Daftar status iuran semua keluarga (halaman Iuran pengurus).
     */
    public function daftarIuran(array $filter = []): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('keluarga k')
            ->select('k.id, k.blok_id, k.nomor, k.akhiran, k.status, b.nama as blok_nama')
            ->join('blok b', 'b.id = k.blok_id')
            ->where('k.status', 'aktif');
        if (!empty($filter['blok_id'])) {
            $q->where('k.blok_id', (int) $filter['blok_id']);
        }
        $rows = $q->orderBy('b.nama')->orderBy('k.nomor')->get()->getResultArray();

        $out = [];
        $filterStatus = $filter['status'] ?? null; // lunas|belum_lunas|menunggak
        foreach ($rows as $r) {
            $ring = $this->ringkasanKeluarga((int) $r['id']);
            if ($filterStatus && $ring['status'] !== $filterStatus) {
                continue;
            }
            $kepala = $db->table('warga')
                ->where('keluarga_id', (int) $r['id'])
                ->where('hubungan', 'Kepala keluarga')
                ->where('status', 'aktif')
                ->get()
                ->getRowArray();
            $out[] = [
                'keluarga_id' => (int) $r['id'],
                'nama'        => $kepala['nama'] ?? '—',
                'alamat'      => $r['blok_nama'] . '-' . $r['nomor'] . ($r['akhiran'] ?? ''),
                'total'       => $ring['total'],
                'status'      => $ring['status'],
                'menunggak'   => $ring['menunggak'],
            ];
        }
        return $out;
    }
}
