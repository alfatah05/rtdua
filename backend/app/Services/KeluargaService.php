<?php

namespace App\Services;

use App\Libraries\NikCrypto;

class KeluargaService
{
    use KeluargaHelpers;

    public function list(array $filter = []): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('keluarga k')
            ->select('k.*, b.nama as blok_nama')
            ->join('blok b', 'b.id = k.blok_id');

        $status = $filter['status'] ?? 'aktif';
        if ($status !== 'semua') {
            $q->where('k.status', $status);
        }
        if (!empty($filter['blok_id'])) {
            $q->where('k.blok_id', (int) $filter['blok_id']);
        }
        if (!empty($filter['q'])) {
            $s = trim((string) $filter['q']);
            $like = '%' . $s . '%';
            $q->groupStart()
                ->like('k.nomor', $s)
                ->orLike('k.akhiran', $s)
                ->orLike('b.nama', $s)
                ->orLike('k.alamat', $s)
                ->orWhere(
                    'EXISTS (SELECT 1 FROM warga w WHERE w.keluarga_id = k.id AND w.status = \'aktif\' AND w.nama LIKE ' . $db->escape($like) . ')',
                    null,
                    false
                )
                ->groupEnd();
        }

        $rows = $q->orderBy('b.nama', 'ASC')->orderBy('k.nomor', 'ASC')->orderBy('k.akhiran', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->ringkasKeluarga($r, $filter);
        }

        if (!empty($filter['q'])) {
            $s = mb_strtolower(trim((string) $filter['q']));
            usort($out, static function ($a, $b) use ($s) {
                $an = str_contains(mb_strtolower($a['nama'] ?? ''), $s) ? 0 : 1;
                $bn = str_contains(mb_strtolower($b['nama'] ?? ''), $s) ? 0 : 1;
                if ($an !== $bn) {
                    return $an - $bn;
                }
                return strcmp($a['alamat'] ?? '', $b['alamat'] ?? '');
            });
        }

        if (!empty($filter['belum_lengkap'])) {
            $out = array_values(array_filter($out, static fn ($k) => $k['data_belum_lengkap']));
        }
        if (!empty($filter['belum_ganti_pin'])) {
            $out = array_values(array_filter($out, static fn ($k) => $k['belum_ganti_pin']));
        }

        return $out;
    }

    public function cariAnggotaAktif(string $q, int $limit = 40): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $db = \Config\Database::connect();
        $limit = max(1, min(80, $limit));
        $rows = $db->table('warga w')
            ->select('w.id, w.nama, w.hubungan, w.keluarga_id, k.nomor, k.akhiran, k.alamat as alamat_teks, b.nama as blok_nama')
            ->join('keluarga k', 'k.id = w.keluarga_id')
            ->join('blok b', 'b.id = k.blok_id')
            ->where('w.status', 'aktif')
            ->where('k.status', 'aktif')
            ->groupStart()
                ->like('w.nama', $q)
                ->orLike('b.nama', $q)
                ->orLike('k.nomor', $q)
                ->orLike('k.akhiran', $q)
                ->orLike('k.alamat', $q)
            ->groupEnd()
            ->orderBy('w.nama', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $out = [];
        $s = mb_strtolower($q);
        foreach ($rows as $r) {
            $alamat = $r['blok_nama'] . '-' . $r['nomor'] . ($r['akhiran'] ?? '');
            $out[] = [
                'id'          => (int) $r['id'],
                'nama'        => $r['nama'],
                'hubungan'    => $r['hubungan'],
                'keluarga_id' => (int) $r['keluarga_id'],
                'alamat'      => $alamat,
            ];
        }
        usort($out, static function ($a, $b) use ($s) {
            $an = str_contains(mb_strtolower($a['nama'] ?? ''), $s) ? 0 : 1;
            $bn = str_contains(mb_strtolower($b['nama'] ?? ''), $s) ? 0 : 1;
            if ($an !== $bn) {
                return $an - $bn;
            }
            return strcmp($a['nama'] ?? '', $b['nama'] ?? '');
        });
        return $out;
    }

    public function detail(int $id, bool $bukaNik = false, ?int $userId = null, bool $developer = false): ?array
    {
        $db = \Config\Database::connect();
        $r = $db->table('keluarga k')
            ->select('k.*, b.nama as blok_nama')
            ->join('blok b', 'b.id = k.blok_id')
            ->where('k.id', $id)
            ->get()
            ->getRowArray();
        if (!$r) {
            return null;
        }
        $anggota = $db->table('warga')->where('keluarga_id', $id)->orderBy('id', 'ASC')->get()->getResultArray();
        $list = [];
        foreach ($anggota as $a) {
            $item = [
                'id'            => (int) $a['id'],
                'nama'          => $a['nama'],
                'hubungan'      => $a['hubungan'],
                'no_kk'         => $a['no_kk'],
                'jenis_kelamin' => $a['jenis_kelamin'],
                'tempat_lahir'  => $a['tempat_lahir'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'agama'         => $a['agama'],
                'pekerjaan'     => $a['pekerjaan'],
                'foto'          => $a['foto'],
                'status'        => $a['status'],
                'tanggal_status'=> $a['tanggal_status'],
                'alasan'        => $a['alasan'],
                'punya_nik'     => !empty($a['nik_enc']),
            ];
            if ($bukaNik && !empty($a['nik_enc'])) {
                $item['nik'] = NikCrypto::decrypt($a['nik_enc']);
                if ($userId) {
                    (new AuditService())->log(
                        'buka_nik',
                        'warga',
                        (int) $a['id'],
                        null,
                        ['keluarga_id' => $id],
                        $developer ? 'developer' : 'pengguna',
                        false,
                        $userId
                    );
                }
            }
            $list[] = $item;
        }

        $user = $db->table('users')->where('keluarga_id', $id)->where('role', 'warga')->get()->getRowArray();
        return [
            'id'                 => (int) $r['id'],
            'blok_id'            => (int) $r['blok_id'],
            'blok'               => $r['blok_nama'],
            'nomor'              => $r['nomor'],
            'akhiran'            => $r['akhiran'],
            'alamat_label'       => $this->labelAlamat($r),
            'alamat'             => $r['alamat'],
            'telepon'            => $r['telepon'],
            'status'             => $r['status'],
            'tanggal_pindah'     => $r['tanggal_pindah'],
            'catatan_pindah'     => $r['catatan_pindah'],
            'mulai_periode'      => $r['mulai_periode'],
            'mulai_bulan_depan'  => empty($r['mulai_periode']),
            'username'           => $user['username'] ?? null,
            'belum_ganti_pin'    => $user ? (bool) $user['harus_ganti_kredensial'] : false,
            'data_belum_lengkap' => $this->cekBelumLengkap($r, $anggota),
            'anggota'            => $list,
        ];
    }

    public function create(array $data, int $userId): array
    {
        return ['ok' => false, 'message' => 'Service partial - restore incomplete. Contact admin.'];
    }
}
