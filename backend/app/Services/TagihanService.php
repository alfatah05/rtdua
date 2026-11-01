<?php

namespace App\Services;

use App\Libraries\AlokasiPembayaran;

/**
 * Tagihan per keluarga: total, sisa per item, status.
 * Sisa dihitung ulang dari seluruh pembayaran (tidak disimpan per alokasi).
 * Jika pembayaran punya tagihan_ids (checklist warga), potongan hanya ke ID itu.
 */
class TagihanService
{
    /**
     * Ringkasan iuran satu keluarga.
     * Otomatis memastikan tagihan kas bulan berjalan ada (aman untuk keluarga
     * yang ditambah lewat SQL / tanpa lewat create app).
     *
     * @return array{
     *   keluarga_id:int,
     *   total:int,
     *   status:string,
     *   menunggak:bool,
     *   tagihan:list<array>,
     *   pembayaran:list<array>,
     *   riwayat:list<array>
     * }
     */
    public function ringkasanKeluarga(int $keluargaId): array
    {
        // Safety net: keluarga baru (SQL/app) tanpa tagihan kas bulan ini
        try {
            $this->pastikanTagihanKasKeluarga($keluargaId, date('Y-m'));
        } catch (\Throwable $e) {
            log_message('error', 'pastikanTagihanKasKeluarga: ' . $e->getMessage());
        }

        $db = \Config\Database::connect();
        $tagihan = $db->table('iuran_tagihan t')
            ->select('t.*, ik.nama as nama_khusus')
            ->join('iuran_khusus ik', 'ik.id = t.iuran_khusus_id', 'left')
            ->where('t.keluarga_id', $keluargaId)
            ->where('t.dibatalkan', 0)
            ->orderBy('t.periode', 'ASC')
            ->orderBy('t.id', 'ASC')
            ->get()
            ->getResultArray();

        $pembayaran = $db->table('pembayaran')
            ->where('keluarga_id', $keluargaId)
            ->where('dibatalkan', 0)
            ->orderBy('tanggal_bayar', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // Map permintaan (transfer warga) + rekening tujuan
        $permMap = [];
        $rekMap = [];
        $permIds = [];
        foreach ($pembayaran as $p) {
            if (!empty($p['permintaan_id'])) {
                $permIds[] = (int) $p['permintaan_id'];
            }
        }
        $permIds = array_values(array_unique(array_filter($permIds)));
        if ($permIds) {
            try {
                $prows = $db->table('pembayaran_permintaan')
                    ->whereIn('id', $permIds)
                    ->get()
                    ->getResultArray();
                $rekIds = [];
                foreach ($prows as $pr) {
                    $permMap[(int) $pr['id']] = $pr;
                    if (!empty($pr['rekening_id'])) {
                        $rekIds[] = (int) $pr['rekening_id'];
                    }
                }
                $rekIds = array_values(array_unique(array_filter($rekIds)));
                if ($rekIds) {
                    try {
                        (new RekeningPembayaranService())->ensureTable();
                        $rrows = $db->table('rekening_pembayaran')
                            ->whereIn('id', $rekIds)
                            ->get()
                            ->getResultArray();
                        foreach ($rrows as $rr) {
                            $rekMap[(int) $rr['id']] = [
                                'id'        => (int) $rr['id'],
                                'jenis'     => $rr['jenis'] ?? null,
                                'nama'      => $rr['nama'] ?? null,
                                'nomor'     => $rr['nomor'] ?? null,
                                'atas_nama' => $rr['atas_nama'] ?? null,
                                'qris_file' => $rr['qris_file'] ?? null,
                            ];
                        }
                    } catch (\Throwable $e) {
                        // tabel rekening opsional
                    }
                }
            } catch (\Throwable $e) {
                // kolom opsional — jangan gagalkan ringkasan
            }
        }

        $items = [];
        foreach ($tagihan as $t) {
            $items[] = [
                'id'              => (int) $t['id'],
                'periode'         => $t['periode'],
                'jenis'           => $t['jenis'],
                'nama'            => $t['nama_khusus'] ?? null,
                'nominal'         => (int) $t['nominal'],
                'sisa'            => (int) $t['nominal'],
                'iuran_khusus_id' => $t['iuran_khusus_id'] ? (int) $t['iuran_khusus_id'] : null,
                'ronda_malam_id'  => $t['ronda_malam_id'] ? (int) $t['ronda_malam_id'] : null,
            ];
        }

        $totalBayar = 0;
        $riwayat = [];
        foreach ($pembayaran as $p) {
            $nominal = (int) $p['nominal'];
            $totalBayar += $nominal;
            $pidPerm = !empty($p['permintaan_id']) ? (int) $p['permintaan_id'] : 0;
            $perm = $pidPerm && isset($permMap[$pidPerm]) ? $permMap[$pidPerm] : null;
            $namaPengirim = $perm['nama_pengirim'] ?? null;
            $bankPengirim = $perm['bank_pengirim'] ?? null;
            $metodeRek = $perm['metode_rekening'] ?? null;
            $rekId = $perm && !empty($perm['rekening_id']) ? (int) $perm['rekening_id'] : 0;
            $rekening = $rekId && isset($rekMap[$rekId]) ? $rekMap[$rekId] : null;

            $hanyaIds = AlokasiPembayaran::parseIds($p['tagihan_ids'] ?? null);
            $alok = AlokasiPembayaran::alokasi($items, $nominal, $hanyaIds);
            foreach ($alok['potongan'] as $pot) {
                foreach ($items as &$it) {
                    if ($it['id'] === (int) $pot['tagihan_id']) {
                        $it['sisa'] = max(0, $it['sisa'] - (int) $pot['jumlah']);
                        break;
                    }
                }
                unset($it);
                $nama = null;
                foreach ($items as $itLookup) {
                    if ((int) $itLookup['id'] === (int) $pot['tagihan_id']) {
                        $nama = $itLookup['nama'] ?? null;
                        break;
                    }
                }
                $riwayat[] = [
                    'pembayaran_id'   => (int) $p['id'],
                    'tagihan_id'      => (int) $pot['tagihan_id'],
                    'tanggal'         => $p['tanggal_bayar'],
                    'jenis'           => $pot['jenis'],
                    'periode'         => $pot['periode'],
                    'nama'            => $nama,
                    'jumlah'          => (int) $pot['jumlah'],
                    'metode'          => $p['metode'] ?? null,
                    'nama_pengirim'   => $namaPengirim,
                    'bank_pengirim'   => $bankPengirim,
                    'metode_rekening' => $metodeRek,
                    'permintaan_id'   => $pidPerm ?: null,
                    'rekening'        => $rekening,
                    'nominal_bayar'   => $nominal,
                ];
            }
            if (!empty($alok['sisaBayar']) && (int) $alok['sisaBayar'] > 0) {
                $riwayat[] = [
                    'pembayaran_id'   => (int) $p['id'],
                    'tagihan_id'      => null,
                    'tanggal'         => $p['tanggal_bayar'],
                    'jenis'           => 'kelebihan',
                    'periode'         => '',
                    'jumlah'          => (int) $alok['sisaBayar'],
                    'metode'          => $p['metode'] ?? null,
                    'nama_pengirim'   => $namaPengirim,
                    'bank_pengirim'   => $bankPengirim,
                    'metode_rekening' => $metodeRek,
                    'permintaan_id'   => $pidPerm ?: null,
                    'rekening'        => $rekening,
                    'nominal_bayar'   => $nominal,
                ];
            }
        }
        $riwayat = array_reverse($riwayat);

        $totalTagihan = 0;
        $sisaPositif = 0;
        foreach ($items as $it) {
            $totalTagihan += $it['nominal'];
            $sisaPositif += $it['sisa'];
        }
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
            $pidPerm = !empty($p['permintaan_id']) ? (int) $p['permintaan_id'] : 0;
            $perm = $pidPerm && isset($permMap[$pidPerm]) ? $permMap[$pidPerm] : null;
            $rekId = $perm && !empty($perm['rekening_id']) ? (int) $perm['rekening_id'] : 0;
            $rekening = $rekId && isset($rekMap[$rekId]) ? $rekMap[$rekId] : null;
            $bayarList[] = [
                'id'              => (int) $p['id'],
                'tanggal_bayar'   => $p['tanggal_bayar'],
                'metode'          => $p['metode'],
                'nominal'         => (int) $p['nominal'],
                'catatan'         => $p['catatan'],
                'dicatat_oleh'    => $p['dicatat_oleh'] ? (int) $p['dicatat_oleh'] : null,
                'permintaan_id'   => $pidPerm ?: null,
                'nama_pengirim'   => $perm['nama_pengirim'] ?? null,
                'bank_pengirim'   => $perm['bank_pengirim'] ?? null,
                'metode_rekening' => $perm['metode_rekening'] ?? null,
                'rekening'        => $rekening,
            ];
        }

        return [
            'keluarga_id' => $keluargaId,
            'total'       => $total,
            'status'      => $status,
            'menunggak'   => $menunggak,
            'tagihan'     => $items,
            'pembayaran'  => $bayarList,
            'riwayat'     => $riwayat,
        ];
    }

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
     * Pastikan satu keluarga punya tagihan kas untuk periode.
     * @return bool true jika baru dibuat
     */
    public function pastikanTagihanKasKeluarga(int $keluargaId, string $periode, ?int $nominalOverride = null): bool
    {
        if ($keluargaId < 1 || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
            return false;
        }
        $db = \Config\Database::connect();
        $k = $db->table('keluarga')->where('id', $keluargaId)->where('status', 'aktif')->get()->getRowArray();
        if (!$k) {
            return false;
        }
        $mulai = $k['mulai_periode'];
        if ($mulai === null || $mulai === '') {
            $mulai = date('Y-m');
        }
        if (strcmp((string) $mulai, $periode) > 0) {
            return false;
        }

        $ada = $db->table('iuran_tagihan')
            ->where('keluarga_id', $keluargaId)
            ->where('periode', $periode)
            ->where('jenis', 'kas')
            ->where('dibatalkan', 0)
            ->countAllResults();
        if ($ada > 0) {
            return false;
        }

        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $nominal = $nominalOverride !== null
            ? max(0, $nominalOverride)
            : (int) ($peng['nominal_kas'] ?? 0);

        // Prefer total komponen aktif jika tersedia
        try {
            $totalKomp = (new IuranKomponenService())->totalAktif();
            if ($totalKomp > 0 && $nominalOverride === null) {
                $nominal = $totalKomp;
            }
        } catch (\Throwable $e) {
        }

        $db->table('iuran_tagihan')->insert([
            'keluarga_id' => $keluargaId,
            'periode'     => $periode,
            'jenis'       => 'kas',
            'nominal'     => $nominal,
            'dibatalkan'  => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function pastikanTagihanKasBulan(string $periode, ?int $nominalOverride = null): int
    {
        $db = \Config\Database::connect();
        $keluarga = $db->table('keluarga')->where('status', 'aktif')->get()->getResultArray();
        $dibuat = 0;
        foreach ($keluarga as $k) {
            if ($this->pastikanTagihanKasKeluarga((int) $k['id'], $periode, $nominalOverride)) {
                $dibuat++;
            }
        }
        return $dibuat;
    }

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
                $ring = $this->ringkasanKeluarga((int) $r['keluarga_id']);
                $selisih = $baru - (int) $r['nominal'];
                if ($ring['total'] + $selisih < 0) {
                    $pratinjau['jadi_kelebihan']++;
                }
            }
        }

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

    public function daftarIuran(array $filter = []): array
    {
        $db = \Config\Database::connect();
        // Pastikan semua keluarga aktif punya tagihan kas bulan ini
        try {
            $this->pastikanTagihanKasBulan(date('Y-m'));
        } catch (\Throwable $e) {
            log_message('error', 'pastikanTagihanKasBulan di daftarIuran: ' . $e->getMessage());
        }

        $q = $db->table('keluarga k')
            ->select('k.id, k.blok_id, k.nomor, k.akhiran, k.status, b.nama as blok_nama')
            ->join('blok b', 'b.id = k.blok_id')
            ->where('k.status', 'aktif');
        if (!empty($filter['blok_id'])) {
            $q->where('k.blok_id', (int) $filter['blok_id']);
        }
        $rows = $q->orderBy('b.nama')->orderBy('k.nomor')->get()->getResultArray();

        $out = [];
        $filterStatus = $filter['status'] ?? null;
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
