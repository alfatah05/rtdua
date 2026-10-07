<?php

namespace App\Services;

/**
 * Iuran khusus: buat untuk semua keluarga aktif pada periode terpilih.
 */
class IuranKhususService
{
    public function list(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('iuran_khusus')->orderBy('periode', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        return array_map(static function ($r) {
            return [
                'id'         => (int) $r['id'],
                'nama'       => $r['nama'],
                'periode'    => $r['periode'],
                'nominal'    => (int) $r['nominal'],
                'dibatalkan' => (bool) $r['dibatalkan'],
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
            $mulai = $k['mulai_periode'];
            if ($mulai === null || $mulai === '') {
                continue;
            }
            if (strcmp($mulai, $periode) > 0) {
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
}
