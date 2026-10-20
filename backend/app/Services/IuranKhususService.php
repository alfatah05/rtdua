<?php

namespace App\Services;

/**
 * Iuran khusus: buat untuk semua keluarga aktif, atau per keluarga.
 */
class IuranKhususService
{
    public function list(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('iuran_khusus')->orderBy('periode', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        return array_map(static function ($r) use ($db) {
            $id = (int) $r['id'];
            $jumlah = 0;
            if ($id > 0) {
                $jumlah = (int) $db->table('iuran_tagihan')
                    ->where('iuran_khusus_id', $id)
                    ->where('dibatalkan', 0)
                    ->countAllResults();
            }
            return [
                'id'         => $id,
                'nama'       => $r['nama'],
                'periode'    => $r['periode'],
                'nominal'    => (int) $r['nominal'],
                'dibatalkan' => (bool) $r['dibatalkan'],
                'jumlah_kk'  => $jumlah,
                'created_at' => $r['created_at'],
            ];
        }, $rows);
    }

    public function buat(array $data, int $userId): array
    {
        $nama = trim((string) ($data['nama'] ?? ''));
        $periode = trim((string) ($data['periode'] ?? ''));
        $nominal = (int) ($data['nominal'] ?? 0);
        if ($nama === '' || !preg_match('/^\d{4}-\d{2}$/', $periode) || $nominal < 1) {
            return ['ok' => false, 'message' => 'Nama, periode (YYYY-MM), dan nominal wajib.'];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $db->table('iuran_khusus')->insert([
            'nama'       => $nama,
            'periode'    => $periode,
            'nominal'    => $nominal,
            'dibatalkan' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $iid = (int) $db->insertID();

        $keluarga = $db->table('keluarga')->where('status', 'aktif')->get()->getResultArray();
        $jumlah = 0;
        foreach ($keluarga as $k) {
            $mulai = $k['mulai_periode'] ?? null;
            if ($mulai === null || $mulai === '') {
                continue;
            }
            if (strcmp((string) $mulai, $periode) > 0) {
                continue;
            }
            $db->table('iuran_tagihan')->insert([
                'keluarga_id'     => (int) $k['id'],
                'periode'         => $periode,
                'jenis'           => 'khusus',
                'iuran_khusus_id' => $iid,
                'nominal'         => $nominal,
                'dibatalkan'      => 0,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $jumlah++;
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal membuat iuran khusus.'];
        }

        try {
            (new SubPosService())->ensureKhusus($nama, $iid);
        } catch (\Throwable $e) {
        }

        (new AuditService())->log('buat_iuran_khusus', 'iuran_khusus', $iid, null, [
            'nama'    => $nama,
            'periode' => $periode,
            'nominal' => $nominal,
            'kk'      => $jumlah,
        ], 'pengguna', true, $userId);

        return ['ok' => true, 'data' => ['id' => $iid, 'keluarga_terdampak' => $jumlah]];
    }

    public function ubahNominal(int $id, int $nominalBaru, int $userId): array
    {
        if ($nominalBaru < 1) {
            return ['ok' => false, 'message' => 'Nominal tidak valid.'];
        }
        $db = \Config\Database::connect();
        $row = $db->table('iuran_khusus')->where('id', $id)->get()->getRowArray();
        if (!$row || (int) $row['dibatalkan'] === 1) {
            return ['ok' => false, 'message' => 'Iuran khusus tidak ditemukan.'];
        }
        $db->transStart();
        $db->table('iuran_khusus')->where('id', $id)->update(['nominal' => $nominalBaru]);
        $db->table('iuran_tagihan')
            ->where('iuran_khusus_id', $id)
            ->where('dibatalkan', 0)
            ->update(['nominal' => $nominalBaru]);
        $db->transComplete();

        (new AuditService())->log('ubah_iuran_khusus', 'iuran_khusus', $id, ['nominal' => $row['nominal']], ['nominal' => $nominalBaru], 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function batalkan(int $id, string $alasan, int $userId): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            return ['ok' => false, 'message' => 'Alasan wajib.'];
        }
        $db = \Config\Database::connect();
        $row = $db->table('iuran_khusus')->where('id', $id)->get()->getRowArray();
        if (!$row || (int) $row['dibatalkan'] === 1) {
            return ['ok' => false, 'message' => 'Iuran khusus tidak ditemukan.'];
        }
        $db->transStart();
        $db->table('iuran_khusus')->where('id', $id)->update(['dibatalkan' => 1]);
        $db->table('iuran_tagihan')
            ->where('iuran_khusus_id', $id)
            ->where('dibatalkan', 0)
            ->update([
                'dibatalkan'      => 1,
                'dibatalkan_oleh' => $userId,
                'dibatalkan_pada' => date('Y-m-d H:i:s'),
                'alasan'          => $alasan,
            ]);
        $db->transComplete();

        (new AuditService())->log('batal_iuran_khusus', 'iuran_khusus', $id, $row, ['alasan' => $alasan], 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function buatPerKeluarga(int $keluargaId, array $data, int $userId): array
    {
        if ($keluargaId < 1) {
            return ['ok' => false, 'message' => 'Keluarga tidak valid.'];
        }
        $nama = trim((string) ($data['nama'] ?? $data['judul'] ?? ''));
        $nominal = (int) ($data['nominal'] ?? 0);
        $periode = trim((string) ($data['periode'] ?? date('Y-m')));
        if ($nama === '') {
            return ['ok' => false, 'message' => 'Judul tagihan wajib.'];
        }
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal wajib lebih dari 0.'];
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            return ['ok' => false, 'message' => 'Periode tidak valid (YYYY-MM).'];
        }

        $db = \Config\Database::connect();
        $kel = $db->table('keluarga')->where('id', $keluargaId)->get()->getRowArray();
        if (!$kel) {
            return ['ok' => false, 'message' => 'Keluarga tidak ditemukan.'];
        }
        if (($kel['status'] ?? '') !== 'aktif') {
            return ['ok' => false, 'message' => 'Keluarga tidak aktif.'];
        }

        $db->transStart();
        $db->table('iuran_khusus')->insert([
            'nama'       => $nama,
            'periode'    => $periode,
            'nominal'    => $nominal,
            'dibatalkan' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $iid = (int) $db->insertID();
        $db->table('iuran_tagihan')->insert([
            'keluarga_id'     => $keluargaId,
            'periode'         => $periode,
            'jenis'           => 'khusus',
            'iuran_khusus_id' => $iid,
            'nominal'         => $nominal,
            'dibatalkan'      => 0,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $tid = (int) $db->insertID();
        $db->transComplete();

        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal membuat tagihan khusus.'];
        }

        try {
            (new SubPosService())->ensureKhusus($nama, $iid);
        } catch (\Throwable $e) {
        }

        (new AuditService())->log(
            'buat_tagihan_khusus_keluarga',
            'iuran_tagihan',
            $tid,
            null,
            [
                'keluarga_id'     => $keluargaId,
                'iuran_khusus_id' => $iid,
                'nama'            => $nama,
                'periode'         => $periode,
                'nominal'         => $nominal,
            ],
            'pengguna',
            true,
            $userId
        );

        return [
            'ok'   => true,
            'data' => [
                'iuran_khusus_id' => $iid,
                'tagihan_id'      => $tid,
                'keluarga_id'     => $keluargaId,
                'nama'            => $nama,
                'periode'         => $periode,
                'nominal'         => $nominal,
            ],
        ];
    }

    public function ubahByTagihan(int $tagihanId, array $data, int $userId): array
    {
        if ($tagihanId < 1) {
            return ['ok' => false, 'message' => 'Tagihan tidak valid.'];
        }
        $nama = trim((string) ($data['nama'] ?? $data['judul'] ?? ''));
        $nominal = (int) ($data['nominal'] ?? 0);
        $periode = trim((string) ($data['periode'] ?? ''));

        if ($nama === '') {
            return ['ok' => false, 'message' => 'Judul tagihan wajib.'];
        }
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal wajib lebih dari 0.'];
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            return ['ok' => false, 'message' => 'Periode tidak valid (YYYY-MM).'];
        }

        $db = \Config\Database::connect();
        $tag = $db->table('iuran_tagihan')->where('id', $tagihanId)->get()->getRowArray();
        if (!$tag || (int) ($tag['dibatalkan'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'Tagihan tidak ditemukan.'];
        }
        if (($tag['jenis'] ?? '') !== 'khusus' || empty($tag['iuran_khusus_id'])) {
            return ['ok' => false, 'message' => 'Bukan tagihan khusus.'];
        }
        $iid = (int) $tag['iuran_khusus_id'];
        $master = $db->table('iuran_khusus')->where('id', $iid)->get()->getRowArray();
        if (!$master || (int) ($master['dibatalkan'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'Master iuran khusus tidak ditemukan.'];
        }

        $before = [
            'nama'    => $master['nama'] ?? null,
            'periode' => $tag['periode'] ?? null,
            'nominal' => (int) ($tag['nominal'] ?? 0),
        ];

        $db->transStart();
        $db->table('iuran_khusus')->where('id', $iid)->update([
            'nama'    => $nama,
            'periode' => $periode,
            'nominal' => $nominal,
        ]);
        $db->table('iuran_tagihan')->where('id', $tagihanId)->update([
            'periode' => $periode,
            'nominal' => $nominal,
        ]);
        $db->transComplete();

        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal menyimpan perubahan.'];
        }

        (new AuditService())->log(
            'ubah_tagihan_khusus_keluarga',
            'iuran_tagihan',
            $tagihanId,
            $before,
            ['nama' => $nama, 'periode' => $periode, 'nominal' => $nominal],
            'pengguna',
            true,
            $userId
        );

        return [
            'ok'   => true,
            'data' => [
                'tagihan_id'      => $tagihanId,
                'iuran_khusus_id' => $iid,
                'keluarga_id'     => (int) $tag['keluarga_id'],
                'nama'            => $nama,
                'periode'         => $periode,
                'nominal'         => $nominal,
            ],
        ];
    }

    public function hapusByTagihan(int $tagihanId, string $alasan, int $userId): array
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            $alasan = 'Dihapus pengurus';
        }
        if ($tagihanId < 1) {
            return ['ok' => false, 'message' => 'Tagihan tidak valid.'];
        }

        $db = \Config\Database::connect();
        $tag = $db->table('iuran_tagihan')->where('id', $tagihanId)->get()->getRowArray();
        if (!$tag || (int) ($tag['dibatalkan'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'Tagihan tidak ditemukan.'];
        }
        if (($tag['jenis'] ?? '') !== 'khusus' || empty($tag['iuran_khusus_id'])) {
            return ['ok' => false, 'message' => 'Bukan tagihan khusus.'];
        }
        $iid = (int) $tag['iuran_khusus_id'];

        $db->transStart();
        $db->table('iuran_tagihan')->where('id', $tagihanId)->update([
            'dibatalkan'      => 1,
            'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
            'alasan'          => $alasan,
        ]);
        $sisaAktif = (int) $db->table('iuran_tagihan')
            ->where('iuran_khusus_id', $iid)
            ->where('dibatalkan', 0)
            ->countAllResults();
        if ($sisaAktif === 0) {
            $db->table('iuran_khusus')->where('id', $iid)->update(['dibatalkan' => 1]);
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal menghapus tagihan.'];
        }

        (new AuditService())->log(
            'hapus_tagihan_khusus_keluarga',
            'iuran_tagihan',
            $tagihanId,
            $tag,
            ['alasan' => $alasan],
            'pengguna',
            true,
            $userId
        );

        return [
            'ok'   => true,
            'data' => [
                'tagihan_id'  => $tagihanId,
                'keluarga_id' => (int) $tag['keluarga_id'],
            ],
        ];
    }
}
