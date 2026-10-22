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


    /**
     * Cari anggota aktif by nama (primary) atau alamat/blok/nomor.
     * Dipakai UI angkat pengurus (floating search).
     * Query raw agar stabil di shared hosting (hindari groupStart/orLike CI4).
     */
    public function cariAnggotaAktif(string $q, int $limit = 40): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $db = \Config\Database::connect();
        $limit = max(1, min(80, (int) $limit));
        $like = '%' . $db->escapeLikeString($q) . '%';

        $sql = "SELECT w.id, w.nama, w.hubungan, w.keluarga_id,
                       k.nomor, k.akhiran, b.nama AS blok_nama
                FROM warga w
                INNER JOIN keluarga k ON k.id = w.keluarga_id
                LEFT JOIN blok b ON b.id = k.blok_id
                WHERE w.status = 'aktif'
                  AND k.status = 'aktif'
                  AND (
                        w.nama LIKE ? ESCAPE '!'
                     OR IFNULL(b.nama, '') LIKE ? ESCAPE '!'
                     OR CAST(k.nomor AS CHAR) LIKE ? ESCAPE '!'
                     OR IFNULL(k.akhiran, '') LIKE ? ESCAPE '!'
                     OR IFNULL(k.alamat, '') LIKE ? ESCAPE '!'
                     OR CONCAT(IFNULL(b.nama,''), '-', k.nomor, IFNULL(k.akhiran,'')) LIKE ? ESCAPE '!'
                  )
                ORDER BY w.nama ASC
                LIMIT {$limit}";

        try {
            $rows = $db->query($sql, [$like, $like, $like, $like, $like, $like])->getResultArray();
        } catch (\Throwable $e) {
            // fallback Query Builder sederhana (nama saja)
            $rows = $db->table('warga w')
                ->select('w.id, w.nama, w.hubungan, w.keluarga_id, k.nomor, k.akhiran, b.nama as blok_nama')
                ->join('keluarga k', 'k.id = w.keluarga_id')
                ->join('blok b', 'b.id = k.blok_id', 'left')
                ->where('w.status', 'aktif')
                ->where('k.status', 'aktif')
                ->like('w.nama', $q)
                ->orderBy('w.nama', 'ASC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        }

        $out = [];
        $s = mb_strtolower($q);
        foreach ($rows as $r) {
            $alamat = ($r['blok_nama'] ?? '') . '-' . ($r['nomor'] ?? '') . ($r['akhiran'] ?? '');
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
        $db = \Config\Database::connect();
        $blokId = (int) ($data['blok_id'] ?? 0);
        $nomor = trim((string) ($data['nomor'] ?? ''));
        $akhiran = trim((string) ($data['akhiran'] ?? ''));
        if ($blokId < 1 || $nomor === '') {
            return ['ok' => false, 'message' => 'Blok dan nomor rumah wajib.'];
        }
        $blok = $db->table('blok')->where('id', $blokId)->where('aktif', 1)->get()->getRowArray();
        if (!$blok) {
            return ['ok' => false, 'message' => 'Blok tidak valid.'];
        }
        if ($this->alamatDuplikat($blokId, $nomor, $akhiran)) {
            return ['ok' => false, 'message' => 'Alamat rumah sudah dipakai keluarga aktif.'];
        }

        $anggotaIn = $data['anggota'] ?? [];
        if (!is_array($anggotaIn) || count($anggotaIn) < 1) {
            return ['ok' => false, 'message' => 'Minimal satu anggota (kepala keluarga).'];
        }

        $db->transStart();
        $db->table('keluarga')->insert([
            'blok_id'       => $blokId,
            'nomor'         => $nomor,
            'akhiran'       => $akhiran,
            'alamat'        => $data['alamat'] ?? null,
            'telepon'       => $data['telepon'] ?? null,
            'status'        => 'aktif',
            'mulai_periode' => date('Y-m'),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $keluargaId = (int) $db->insertID();

        $kepalaCount = 0;
        foreach ($anggotaIn as $a) {
            $hubungan = trim((string) ($a['hubungan'] ?? 'Anggota'));
            if (strcasecmp($hubungan, 'Kepala keluarga') === 0) {
                $kepalaCount++;
            }
            $nik = $a['nik'] ?? null;
            $hash = NikCrypto::hash($nik);
            if ($hash && $this->nikDuplikat($hash)) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'NIK sudah terdaftar pada anggota lain.'];
            }
            $db->table('warga')->insert([
                'keluarga_id'   => $keluargaId,
                'nama'          => trim((string) ($a['nama'] ?? '')),
                'hubungan'      => $hubungan,
                'no_kk'         => $a['no_kk'] ?? null,
                'nik_enc'       => NikCrypto::encrypt($nik),
                'nik_hash'      => $hash,
                'jenis_kelamin' => $a['jenis_kelamin'] ?? null,
                'tempat_lahir'  => $a['tempat_lahir'] ?? null,
                'tanggal_lahir' => $a['tanggal_lahir'] ?? null,
                'agama'         => $a['agama'] ?? null,
                'pekerjaan'     => $a['pekerjaan'] ?? null,
                'status'        => 'aktif',
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
        if ($kepalaCount !== 1) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Harus ada tepat satu Kepala keluarga.'];
        }

        $username = $this->buatUsername($blok['nama'], $nomor, $akhiran);
        $db->table('users')->insert([
            'role'                    => 'warga',
            'username'                => $username,
            'password_hash'           => password_hash('123456', PASSWORD_DEFAULT),
            'keluarga_id'             => $keluargaId,
            'aktif'                   => 1,
            'harus_ganti_kredensial'  => 1,
            'created_at'              => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();
        if (!$db->transStatus()) {
            return ['ok' => false, 'message' => 'Gagal menyimpan keluarga.'];
        }

        (new AuditService())->log('tambah_keluarga', 'keluarga', $keluargaId, null, [
            'username' => $username,
            'blok'     => $blok['nama'],
            'nomor'    => $nomor,
            'akhiran'  => $akhiran,
        ], 'pengguna', true, $userId);

        try {
            (new TagihanService())->pastikanTagihanKasBulan(date('Y-m'));
        } catch (\Throwable $e) {
            log_message('error', 'pastikanTagihan setelah tambah keluarga: ' . $e->getMessage());
        }

        return [
            'ok'   => true,
            'data' => [
                'id'       => $keluargaId,
                'username' => $username,
                'pin_awal' => '123456',
                'catatan'  => 'Akun siap. Tagihan iuran berlaku mulai bulan ini.',
            ],
        ];
    }

    public function updateKeluarga(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('keluarga')->where('id', $id)->get()->getRowArray();
        if (!$before || $before['status'] !== 'aktif') {
            return ['ok' => false, 'message' => 'Keluarga tidak ditemukan atau tidak aktif.'];
        }
        $update = [];
        foreach (['alamat', 'telepon'] as $k) {
            if (array_key_exists($k, $data)) {
                $update[$k] = $data[$k];
            }
        }
        if (isset($data['blok_id'], $data['nomor'])) {
            $blokId = (int) $data['blok_id'];
            $nomor = trim((string) $data['nomor']);
            $akhiran = trim((string) ($data['akhiran'] ?? ''));
            if ($this->alamatDuplikat($blokId, $nomor, $akhiran, $id)) {
                return ['ok' => false, 'message' => 'Alamat rumah sudah dipakai.'];
            }
            $update['blok_id'] = $blokId;
            $update['nomor'] = $nomor;
            $update['akhiran'] = $akhiran;
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('keluarga')->where('id', $id)->update($update);

        // Username hanya diubah jika alamat (blok/nomor/akhiran) benar-benar berubah.
        // Exclude user sendiri agar tidak jadi ab12-19x1 karena bentrok dengan username-nya sendiri.
        if (isset($update['blok_id'])) {
            $alamatBerubah =
                (int) $before['blok_id'] !== (int) $update['blok_id']
                || (string) $before['nomor'] !== (string) $update['nomor']
                || (string) ($before['akhiran'] ?? '') !== (string) ($update['akhiran'] ?? '');
            if ($alamatBerubah) {
                $userWarga = $db->table('users')->where('keluarga_id', $id)->where('role', 'warga')->get()->getRowArray();
                $blok = $db->table('blok')->where('id', $update['blok_id'])->get()->getRowArray();
                if ($blok) {
                    $kecualiId = $userWarga ? (int) $userWarga['id'] : null;
                    $uname = $this->buatUsername($blok['nama'], $update['nomor'], $update['akhiran'] ?? '', $kecualiId);
                    if (!$userWarga || strcasecmp((string) $userWarga['username'], $uname) !== 0) {
                        $db->table('users')->where('keluarga_id', $id)->where('role', 'warga')->update([
                            'username'   => $uname,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

        (new AuditService())->log('ubah_keluarga', 'keluarga', $id, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function tambahAnggota(int $keluargaId, array $a, int $userId): array
    {
        $db = \Config\Database::connect();
        $k = $db->table('keluarga')->where('id', $keluargaId)->where('status', 'aktif')->get()->getRowArray();
        if (!$k) {
            return ['ok' => false, 'message' => 'Keluarga tidak ditemukan.'];
        }
        $nama = trim((string) ($a['nama'] ?? ''));
        if ($nama === '') {
            return ['ok' => false, 'message' => 'Nama wajib.'];
        }
        $hubungan = trim((string) ($a['hubungan'] ?? 'Anggota'));
        if (strcasecmp($hubungan, 'Kepala keluarga') === 0) {
            $ada = $db->table('warga')->where('keluarga_id', $keluargaId)->where('hubungan', 'Kepala keluarga')->where('status', 'aktif')->countAllResults();
            if ($ada > 0) {
                return ['ok' => false, 'message' => 'Sudah ada Kepala keluarga.'];
            }
        }
        $nik = $a['nik'] ?? null;
        $hash = NikCrypto::hash($nik);
        if ($hash && $this->nikDuplikat($hash)) {
            return ['ok' => false, 'message' => 'NIK sudah terdaftar.'];
        }
        $db->table('warga')->insert([
            'keluarga_id'   => $keluargaId,
            'nama'          => $nama,
            'hubungan'      => $hubungan,
            'no_kk'         => $a['no_kk'] ?? null,
            'nik_enc'       => NikCrypto::encrypt($nik),
            'nik_hash'      => $hash,
            'jenis_kelamin' => $a['jenis_kelamin'] ?? null,
            'tempat_lahir'  => $a['tempat_lahir'] ?? null,
            'tanggal_lahir' => $a['tanggal_lahir'] ?? null,
            'agama'         => $a['agama'] ?? null,
            'pekerjaan'     => $a['pekerjaan'] ?? null,
            'status'        => 'aktif',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $wid = (int) $db->insertID();
        (new AuditService())->log('tambah_anggota', 'warga', $wid, null, ['nama' => $nama, 'keluarga_id' => $keluargaId], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $wid]];
    }

    public function updateAnggota(int $anggotaId, array $a, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('warga')->where('id', $anggotaId)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Anggota tidak ditemukan.'];
        }
        $update = [];
        foreach (['nama', 'hubungan', 'no_kk', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'pekerjaan'] as $k) {
            if (array_key_exists($k, $a)) {
                if ($k === 'nama') {
                    $nama = trim((string) $a['nama']);
                    if ($nama === '') {
                        return ['ok' => false, 'message' => 'Nama tidak boleh kosong.'];
                    }
                    $update['nama'] = $nama;
                } else {
                    $update[$k] = $a[$k];
                }
            }
        }
        if (array_key_exists('nik', $a)) {
            $hash = NikCrypto::hash($a['nik']);
            if ($hash && $this->nikDuplikat($hash, $anggotaId)) {
                return ['ok' => false, 'message' => 'NIK sudah terdaftar.'];
            }
            $update['nik_enc'] = NikCrypto::encrypt($a['nik']);
            $update['nik_hash'] = $hash;
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('warga')->where('id', $anggotaId)->update($update);
        (new AuditService())->log('ubah_anggota', 'warga', $anggotaId, ['nama' => $before['nama']], $update, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function hapusAnggota(int $anggotaId, ?string $alasan, int $userId, ?int $kepalaBaruId = null): array
    {
        $db = \Config\Database::connect();
        $a = $db->table('warga')->where('id', $anggotaId)->get()->getRowArray();
        if (!$a) {
            return ['ok' => false, 'message' => 'Anggota tidak ditemukan.'];
        }
        if (($a['status'] ?? '') !== 'aktif') {
            return ['ok' => false, 'message' => 'Anggota sudah tidak aktif.'];
        }
        $keluargaId = (int) $a['keluarga_id'];
        $isKepala = strcasecmp((string) $a['hubungan'], 'Kepala keluarga') === 0;

        if ($isKepala) {
            $lain = $db->table('warga')
                ->where('keluarga_id', $keluargaId)
                ->where('status', 'aktif')
                ->where('id !=', $anggotaId)
                ->countAllResults();
            if ($lain > 0) {
                if (!$kepalaBaruId) {
                    return ['ok' => false, 'message' => 'Pilih kepala keluarga baru sebelum menghapus kepala.'];
                }
                $ganti = $this->gantiKepala($keluargaId, (int) $kepalaBaruId, $userId);
                if (!$ganti['ok']) {
                    return $ganti;
                }
            }
        }

        return $this->setStatusAnggota(
            $anggotaId,
            'keluar',
            $alasan !== null && $alasan !== '' ? $alasan : 'Dihapus pengurus',
            $userId
        );
    }

    public function setStatusAnggota(int $anggotaId, string $status, ?string $alasan, int $userId): array
    {
        if (!in_array($status, ['meninggal', 'keluar', 'aktif'], true)) {
            return ['ok' => false, 'message' => 'Status tidak valid.'];
        }
        $db = \Config\Database::connect();
        $a = $db->table('warga')->where('id', $anggotaId)->get()->getRowArray();
        if (!$a) {
            return ['ok' => false, 'message' => 'Anggota tidak ditemukan.'];
        }
        $db->table('warga')->where('id', $anggotaId)->update([
            'status'         => $status,
            'tanggal_status' => date('Y-m-d'),
            'alasan'         => $alasan,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        if ($status !== 'aktif') {
            $this->nonaktifkanPengurusDariWarga((int) $anggotaId, $userId);
        }
        (new AuditService())->log('status_anggota_' . $status, 'warga', $anggotaId, ['status' => $a['status']], ['status' => $status, 'alasan' => $alasan], 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function pindah(int $keluargaId, ?string $tanggal, ?string $catatan, int $userId): array
    {
        $db = \Config\Database::connect();
        $k = $db->table('keluarga')->where('id', $keluargaId)->get()->getRowArray();
        if (!$k || $k['status'] !== 'aktif') {
            return ['ok' => false, 'message' => 'Keluarga tidak ditemukan.'];
        }
        $db->transStart();
        $db->table('keluarga')->where('id', $keluargaId)->update([
            'status'         => 'pindah',
            'tanggal_pindah' => $tanggal ?: date('Y-m-d'),
            'catatan_pindah' => $catatan,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $anggota = $db->table('warga')->where('keluarga_id', $keluargaId)->where('status', 'aktif')->get()->getResultArray();
        foreach ($anggota as $a) {
            $db->table('warga')->where('id', $a['id'])->update([
                'status'         => 'keluar',
                'tanggal_status' => date('Y-m-d'),
                'alasan'         => 'Keluarga pindah',
            ]);
            $this->nonaktifkanPengurusDariWarga((int) $a['id'], $userId);
        }
        $db->table('users')->where('keluarga_id', $keluargaId)->where('role', 'warga')->update([
            'aktif'      => 0,
            'username'   => 'pindah_' . $keluargaId . '_' . time(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();
        (new AuditService())->log('pindah_keluarga', 'keluarga', $keluargaId, null, ['tanggal' => $tanggal, 'catatan' => $catatan], 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function hapusKeluarga(int $keluargaId, ?string $alasan, int $userId): array
    {
        $db = \Config\Database::connect();
        $k = $db->table('keluarga')->where('id', $keluargaId)->get()->getRowArray();
        if (!$k) {
            return ['ok' => false, 'message' => 'Keluarga tidak ditemukan.'];
        }
        if (($k['status'] ?? '') === 'hapus') {
            return ['ok' => false, 'message' => 'Keluarga sudah dihapus.'];
        }
        $db->transStart();
        $db->table('keluarga')->where('id', $keluargaId)->update([
            'status'         => 'hapus',
            'catatan_pindah' => $alasan ?: 'Dihapus pengurus',
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $anggota = $db->table('warga')->where('keluarga_id', $keluargaId)->where('status', 'aktif')->get()->getResultArray();
        foreach ($anggota as $a) {
            $db->table('warga')->where('id', $a['id'])->update([
                'status'         => 'keluar',
                'tanggal_status' => date('Y-m-d'),
                'alasan'         => $alasan ?: 'Keluarga dihapus',
            ]);
            $this->nonaktifkanPengurusDariWarga((int) $a['id'], $userId);
        }
        $db->table('users')->where('keluarga_id', $keluargaId)->where('role', 'warga')->update([
            'aktif'      => 0,
            'username'   => 'hapus_' . $keluargaId . '_' . time(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();
        (new AuditService())->log('hapus_keluarga', 'keluarga', $keluargaId, null, ['alasan' => $alasan], 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function resetPin(int $keluargaId, int $userId): array
    {
        $db = \Config\Database::connect();
        $u = $db->table('users')->where('keluarga_id', $keluargaId)->where('role', 'warga')->get()->getRowArray();
        if (!$u) {
            return ['ok' => false, 'message' => 'Akun warga tidak ditemukan.'];
        }
        $db->table('users')->where('id', $u['id'])->update([
            'password_hash'          => password_hash('123456', PASSWORD_DEFAULT),
            'harus_ganti_kredensial' => 1,
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        (new AuditService())->log('reset_pin_warga', 'users', (int) $u['id'], null, ['keluarga_id' => $keluargaId], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['pin_awal' => '123456']];
    }

    public function gantiKepala(int $keluargaId, int $anggotaBaruId, int $userId): array
    {
        $db = \Config\Database::connect();
        $baru = $db->table('warga')->where('id', $anggotaBaruId)->where('keluarga_id', $keluargaId)->where('status', 'aktif')->get()->getRowArray();
        if (!$baru) {
            return ['ok' => false, 'message' => 'Anggota baru tidak valid.'];
        }
        $db->transStart();
        $lama = $db->table('warga')->where('keluarga_id', $keluargaId)->where('hubungan', 'Kepala keluarga')->where('status', 'aktif')->get()->getResultArray();
        foreach ($lama as $l) {
            if ((int) $l['id'] === $anggotaBaruId) {
                continue;
            }
            $db->table('warga')->where('id', $l['id'])->update([
                'hubungan'   => 'Anggota',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->table('warga')->where('id', $anggotaBaruId)->update([
            'hubungan'   => 'Kepala keluarga',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();
        (new AuditService())->log('ganti_kepala', 'keluarga', $keluargaId, null, ['anggota_baru_id' => $anggotaBaruId], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($keluargaId)];
    }
}
