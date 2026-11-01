<?php

namespace App\Services;

use App\Libraries\AlokasiPembayaran;

/**
 * Pembayaran jalur 1 (pengurus) dan jalur 2 (permintaan warga).
 * Semua mutasi dalam transaksi DB.
 * Alokasi menghormati tagihan_ids (checklist warga) bila ada.
 * T2: mutasi iuran ke sub pos, bukan kas_transaksi.
 */
class PembayaranService
{
    public function ensurePembayaranColumns(): void
    {
        $db = \Config\Database::connect();
        try {
            $cols = $db->query('SHOW COLUMNS FROM pembayaran')->getResultArray();
            $names = array_column($cols, 'Field');
            if (!in_array('tagihan_ids', $names, true)) {
                $db->query('ALTER TABLE pembayaran ADD COLUMN tagihan_ids TEXT NULL AFTER catatan');
            }
        } catch (\Throwable $e) {
        }
    }

    public function catat(array $data, int $userId): array
    {
        $keluargaId = (int) ($data['keluarga_id'] ?? 0);
        $nominal = (int) ($data['nominal'] ?? 0);
        $metode = ($data['metode'] ?? 'tunai') === 'transfer' ? 'transfer' : 'tunai';
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $catatan = $data['catatan'] ?? null;

        if ($keluargaId < 1 || $nominal < 1) {
            return ['ok' => false, 'message' => 'keluarga_id dan nominal wajib.'];
        }

        $db = \Config\Database::connect();
        $kel = $db->table('keluarga')->where('id', $keluargaId)->where('status', 'aktif')->get()->getRowArray();
        if (!$kel) {
            return ['ok' => false, 'message' => 'Keluarga tidak ditemukan.'];
        }

        $this->ensurePembayaranColumns();
        $tagihanSvc = new TagihanService();
        $ring = $tagihanSvc->ringkasanKeluarga($keluargaId);
        $hanyaIds = AlokasiPembayaran::parseIds($data['tagihan_ids'] ?? null);
        $alok = AlokasiPembayaran::alokasi($ring['tagihan'], $nominal, $hanyaIds);

        $db->transStart();
        $insertBayar = [
            'keluarga_id'   => $keluargaId,
            'tanggal_bayar' => $tanggal,
            'metode'        => $metode,
            'nominal'       => $nominal,
            'catatan'       => $catatan,
            'dicatat_oleh'  => $userId,
            'dicatat_pada'  => date('Y-m-d H:i:s'),
            'permintaan_id' => null,
            'dibatalkan'    => 0,
        ];
        if ($hanyaIds !== null) {
            $insertBayar['tagihan_ids'] = json_encode($hanyaIds);
        }
        $db->table('pembayaran')->insert($insertBayar);
        $pid = (int) $db->insertID();

        (new SubPosService())->masukDariPembayaran(
            $pid,
            $keluargaId,
            $metode,
            $tanggal,
            $alok['potongan'],
            $alok['sisaBayar'],
            $userId
        );
        $db->transComplete();

        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal menyimpan pembayaran.'];
        }

        (new AuditService())->log('catat_pembayaran', 'pembayaran', $pid, null, [
            'keluarga_id' => $keluargaId,
            'nominal'     => $nominal,
            'metode'      => $metode,
        ], 'pengguna', true, $userId);

        $uWarga = $db->table('users')->where('keluarga_id', $keluargaId)->where('role', 'warga')->get()->getRowArray();
        if ($uWarga) {
            (new NotifikasiService())->kirim(
                (int) $uWarga['id'],
                'pembayaran',
                'Pembayaran dicatat',
                'Pembayaran Rp ' . number_format($nominal, 0, ',', '.') . ' telah dicatat pengurus.',
                '/keuangan'
            );
        }

        return [
            'ok'   => true,
            'data' => [
                'id'        => $pid,
                'potongan'  => $alok['potongan'],
                'sisaBayar' => $alok['sisaBayar'],
                'ringkasan' => $tagihanSvc->ringkasanKeluarga($keluargaId),
            ],
        ];
    }

    public function ajukanPermintaan(array $data, int $userId, int $keluargaId): array
    {
        $nominal = (int) ($data['nominal'] ?? $data['nominal_diajukan'] ?? 0);
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal transfer wajib.'];
        }
        $db = \Config\Database::connect();
        // Blokir hanya jika ada overlap tagihan_ids dengan permintaan menunggu
        $pending = $db->table('pembayaran_permintaan')
            ->where('keluarga_id', $keluargaId)
            ->where('status', 'menunggu')
            ->get()
            ->getResultArray();
        $newIds = [];
        if (!empty($data['tagihan_ids']) && is_array($data['tagihan_ids'])) {
            $newIds = array_map('intval', $data['tagihan_ids']);
        } elseif (!empty($data['tagihan_ids']) && is_string($data['tagihan_ids'])) {
            $decoded = json_decode($data['tagihan_ids'], true);
            if (is_array($decoded)) {
                $newIds = array_map('intval', $decoded);
            }
        }
        $newIds = array_values(array_filter($newIds, static fn ($n) => $n > 0));
        if ($pending) {
            $locked = [];
            foreach ($pending as $pr) {
                $raw = $pr['tagihan_ids'] ?? null;
                if (is_string($raw) && $raw !== '') {
                    $d = json_decode($raw, true);
                    if (is_array($d)) {
                        foreach ($d as $x) {
                            $locked[(int) $x] = true;
                        }
                    }
                }
            }
            if ($newIds) {
                foreach ($newIds as $nid) {
                    if (!empty($locked[$nid])) {
                        return ['ok' => false, 'message' => 'Sebagian tagihan masih menunggu konfirmasi. Pilih jenis lain atau tunggu pengurus.'];
                    }
                }
            } elseif ($locked) {
                // permintaan baru tanpa tagihan_ids tapi masih ada pending ber-tagihan — izinkan
            } elseif (count($pending) > 0 && !$newIds) {
                // pending tanpa tagihan_ids (legacy): tetap blokir ajukan tanpa daftar tagihan
                return ['ok' => false, 'message' => 'Masih ada permintaan menunggu. Tunggu konfirmasi atau hubungi pengurus.'];
            }
        }

        $buktiPath = $data['bukti_file'] ?? null;
        (new RekeningPembayaranService())->ensureTable();

        $insert = [
            'keluarga_id'      => $keluargaId,
            'diajukan_oleh'    => $userId,
            'nominal_diajukan' => $nominal,
            'nama_pengirim'    => $data['nama_pengirim'] ?? null,
            'bank_pengirim'    => $data['bank_pengirim'] ?? null,
            'bukti_file'       => $buktiPath,
            'diajukan_pada'    => date('Y-m-d H:i:s'),
            'status'           => 'menunggu',
        ];
        if (!empty($data['metode_rekening'])) {
            $insert['metode_rekening'] = $data['metode_rekening'];
        }
        if (!empty($data['rekening_id'])) {
            $insert['rekening_id'] = (int) $data['rekening_id'];
        }
        if (!empty($data['tagihan_ids']) && is_array($data['tagihan_ids'])) {
            $insert['tagihan_ids'] = json_encode(array_values($data['tagihan_ids']));
        } elseif (!empty($data['tagihan_ids']) && is_string($data['tagihan_ids'])) {
            $insert['tagihan_ids'] = $data['tagihan_ids'];
        }
        $db->table('pembayaran_permintaan')->insert($insert);
        $id = (int) $db->insertID();

        (new AuditService())->log('ajukan_transfer', 'pembayaran_permintaan', $id, null, [
            'keluarga_id' => $keluargaId,
            'nominal'     => $nominal,
        ], 'pengguna', true, $userId);

        return ['ok' => true, 'data' => ['id' => $id]];
    }

    public function listPermintaan(string $status = 'menunggu'): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('pembayaran_permintaan p')
            ->select('p.*, k.nomor, k.akhiran, b.nama as blok_nama, w.nama as nama_kepala')
            ->join('keluarga k', 'k.id = p.keluarga_id')
            ->join('blok b', 'b.id = k.blok_id')
            ->join('warga w', "w.keluarga_id = k.id AND w.status = 'aktif' AND w.hubungan = 'Kepala keluarga'", 'left');
        if ($status !== 'semua') {
            $q->where('p.status', $status);
        }
        $rows = $q->orderBy('p.diajukan_pada', 'DESC')->get()->getResultArray();
        return array_map(static function ($r) {
            $nom = (int) $r['nominal_diajukan'];
            return [
                'id'                   => (int) $r['id'],
                'keluarga_id'          => (int) $r['keluarga_id'],
                'alamat'               => $r['blok_nama'] . '-' . $r['nomor'] . ($r['akhiran'] ?? ''),
                'nama'                 => $r['nama_kepala'] ?? null,
                'nama_kepala'          => $r['nama_kepala'] ?? null,
                'nominal_diajukan'     => $nom,
                'nominal'              => $nom,
                'nama_pengirim'        => $r['nama_pengirim'],
                'bank_pengirim'        => $r['bank_pengirim'],
                'bukti_file'           => $r['bukti_file'],
                'diajukan_pada'        => $r['diajukan_pada'],
                'status'               => $r['status'],
                'alasan_tolak'         => $r['alasan_tolak'],
                'nominal_dikonfirmasi' => $r['nominal_dikonfirmasi'] ? (int) $r['nominal_dikonfirmasi'] : null,
                'tagihan_ids'          => $r['tagihan_ids'] ?? null,
            ];
        }, $rows);
    }

    public function konfirmasiPermintaan(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $row = $db->query('SELECT * FROM pembayaran_permintaan WHERE id = ? FOR UPDATE', [$id])->getRowArray();
        if (!$row) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Permintaan tidak ditemukan.'];
        }
        if ($row['status'] !== 'menunggu') {
            $db->transRollback();
            $oleh = $row['diproses_oleh'] ? (int) $row['diproses_oleh'] : null;
            $nama = 'pengurus lain';
            if ($oleh) {
                $u = $db->table('users')->where('id', $oleh)->get()->getRowArray();
                if ($u) {
                    $nama = $u['username'];
                }
            }
            return [
                'ok'      => false,
                'message' => 'Sudah diproses oleh ' . $nama . ' pada ' . ($row['diproses_pada'] ?? '-'),
            ];
        }

        $nominal = isset($data['nominal']) ? (int) $data['nominal'] : (int) $row['nominal_diajukan'];
        if ($nominal < 1) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Nominal tidak valid.'];
        }

        $keluargaId = (int) $row['keluarga_id'];
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $this->ensurePembayaranColumns();
        $tagihanSvc = new TagihanService();
        $ring = $tagihanSvc->ringkasanKeluarga($keluargaId);
        $hanyaIds = AlokasiPembayaran::parseIds($data['tagihan_ids'] ?? ($row['tagihan_ids'] ?? null));
        $alok = AlokasiPembayaran::alokasi($ring['tagihan'], $nominal, $hanyaIds);

        $insertBayar = [
            'keluarga_id'   => $keluargaId,
            'tanggal_bayar' => $tanggal,
            'metode'        => 'transfer',
            'nominal'       => $nominal,
            'catatan'       => $data['catatan'] ?? ('Konfirmasi permintaan #' . $id),
            'dicatat_oleh'  => $userId,
            'dicatat_pada'  => date('Y-m-d H:i:s'),
            'permintaan_id' => $id,
            'dibatalkan'    => 0,
        ];
        if ($hanyaIds !== null) {
            $insertBayar['tagihan_ids'] = json_encode($hanyaIds);
        }
        $db->table('pembayaran')->insert($insertBayar);
        $pid = (int) $db->insertID();

        (new SubPosService())->masukDariPembayaran(
            $pid,
            $keluargaId,
            'transfer',
            $tanggal,
            $alok['potongan'],
            $alok['sisaBayar'],
            $userId
        );

        $db->table('pembayaran_permintaan')->where('id', $id)->update([
            'status'               => 'dikonfirmasi',
            'diproses_oleh'        => $userId,
            'diproses_pada'        => date('Y-m-d H:i:s'),
            'nominal_dikonfirmasi' => $nominal,
            'pembayaran_id'        => $pid,
        ]);

        $db->transComplete();
        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal konfirmasi.'];
        }

        (new AuditService())->log('konfirmasi_transfer', 'pembayaran_permintaan', $id, null, [
            'pembayaran_id' => $pid,
            'nominal'       => $nominal,
        ], 'pengguna', true, $userId);

        $uWarga = $db->table('users')->where('keluarga_id', $keluargaId)->where('role', 'warga')->get()->getRowArray();
        if ($uWarga) {
            (new NotifikasiService())->kirim(
                (int) $uWarga['id'],
                'pembayaran',
                'Transfer dikonfirmasi',
                'Transfer Rp ' . number_format($nominal, 0, ',', '.') . ' telah dikonfirmasi.',
                '/keuangan'
            );
        }

        return [
            'ok'   => true,
            'data' => [
                'pembayaran_id' => $pid,
                'potongan'      => $alok['potongan'],
                'ringkasan'     => $tagihanSvc->ringkasanKeluarga($keluargaId),
            ],
        ];
    }

    public function tolakPermintaan(int $id, string $alasan, int $userId): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            return ['ok' => false, 'message' => 'Alasan tolak wajib.'];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        $row = $db->query('SELECT * FROM pembayaran_permintaan WHERE id = ? FOR UPDATE', [$id])->getRowArray();
        if (!$row) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Permintaan tidak ditemukan.'];
        }
        if ($row['status'] !== 'menunggu') {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Permintaan sudah diproses.'];
        }
        $db->table('pembayaran_permintaan')->where('id', $id)->update([
            'status'        => 'ditolak',
            'diproses_oleh' => $userId,
            'diproses_pada' => date('Y-m-d H:i:s'),
            'alasan_tolak'  => $alasan,
        ]);
        $db->transComplete();

        (new AuditService())->log('tolak_transfer', 'pembayaran_permintaan', $id, null, ['alasan' => $alasan], 'pengguna', true, $userId);

        $uWarga = $db->table('users')->where('keluarga_id', (int) $row['keluarga_id'])->where('role', 'warga')->get()->getRowArray();
        if ($uWarga) {
            (new NotifikasiService())->kirim(
                (int) $uWarga['id'],
                'pembayaran',
                'Transfer ditolak',
                'Alasan: ' . $alasan,
                '/keuangan'
            );
        }

        return ['ok' => true];
    }

    public function batalkanPembayaran(int $id, string $alasan, int $userId): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            return ['ok' => false, 'message' => 'Alasan wajib.'];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        $row = $db->table('pembayaran')->where('id', $id)->get()->getRowArray();
        if (!$row || (int) $row['dibatalkan'] === 1) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Pembayaran tidak ditemukan.'];
        }
        $db->table('pembayaran')->where('id', $id)->update([
            'dibatalkan'      => 1,
            'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
            'alasan_batal'    => $alasan,
        ]);
        (new SubPosService())->batalkanDariPembayaran($id, $userId);
        (new KasService())->batalkanDariPembayaran($id, $userId);
        $db->transComplete();

        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal membatalkan.'];
        }

        (new AuditService())->log('batal_pembayaran', 'pembayaran', $id, $row, ['alasan' => $alasan], 'pengguna', true, $userId);

        $uWarga = $db->table('users')->where('keluarga_id', (int) $row['keluarga_id'])->where('role', 'warga')->get()->getRowArray();
        if ($uWarga) {
            (new NotifikasiService())->kirim(
                (int) $uWarga['id'],
                'pembayaran',
                'Pembayaran dibatalkan',
                'Pembayaran Rp ' . number_format((int) $row['nominal'], 0, ',', '.') . ' dibatalkan. Alasan: ' . $alasan,
                '/keuangan'
            );
        }

        return ['ok' => true, 'data' => (new TagihanService())->ringkasanKeluarga((int) $row['keluarga_id'])];
    }

    public function batalkanDenda(int $tagihanId, string $alasan, int $userId): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            return ['ok' => false, 'message' => 'Alasan wajib.'];
        }
        $db = \Config\Database::connect();
        $t = $db->table('iuran_tagihan')->where('id', $tagihanId)->get()->getRowArray();
        if (!$t || $t['jenis'] !== 'denda_ronda' || (int) $t['dibatalkan'] === 1) {
            return ['ok' => false, 'message' => 'Denda tidak ditemukan.'];
        }
        $db->table('iuran_tagihan')->where('id', $tagihanId)->update([
            'dibatalkan'      => 1,
            'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
            'alasan'          => $alasan,
        ]);
        (new AuditService())->log('batal_denda', 'iuran_tagihan', $tagihanId, $t, ['alasan' => $alasan], 'pengguna', true, $userId);

        $uWarga = $db->table('users')->where('keluarga_id', (int) $t['keluarga_id'])->where('role', 'warga')->get()->getRowArray();
        if ($uWarga) {
            (new NotifikasiService())->kirim(
                (int) $uWarga['id'],
                'denda',
                'Denda dibatalkan',
                'Denda ronda Rp ' . number_format((int) $t['nominal'], 0, ',', '.') . ' dibatalkan. Alasan: ' . $alasan,
                '/keuangan'
            );
        }

        return ['ok' => true];
    }
}
