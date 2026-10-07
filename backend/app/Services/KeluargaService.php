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
            $s = $filter['q'];
            $q->groupStart()
                ->like('k.nomor', $s)
                ->orLike('k.akhiran', $s)
                ->orLike('b.nama', $s)
                ->groupEnd();
        }

        $rows = $q->orderBy('b.nama', 'ASC')->orderBy('k.nomor', 'ASC')->orderBy('k.akhiran', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->ringkasKeluarga($r, $filter);
        }

        // Filter text by kepala name after load (nama di tabel warga)
        if (!empty($filter['q'])) {
            $s = mb_strtolower($filter['q']);
            $out = array_values(array_filter($out, static function ($k) use ($s) {
                return str_contains(mb_strtolower($k['nama'] ?? ''), $s)
                    || str_contains(mb_strtolower($k['alamat'] ?? ''), $s);
            }));
        }

        if (!empty($filter['belum_lengkap'])) {
            $out = array_values(array_filter($out, static fn ($k) => $k['data_belum_lengkap']));
        }
        if (!empty($filter['belum_ganti_pin'])) {
            $out = array_values(array_filter($out, static fn ($k) => $k['belum_ganti_pin']));
        }

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
                // Audit: buka NIK — tampil=false kecuali bukan view-only developer rules
                // Dokumen: aksi melihat NIK tidak tampil di Aktivitas app, tetap di DB
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
            'mulai_periode' => null, // mulai bulan depan
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

        // Akun warga dibuat sekarang dengan PIN 123456, tapi tagihan mulai bulan depan
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

        return [
            'ok'   => true,
            'data' => [
                'id'       => $keluargaId,
                'username' => $username,
                'pin_awal' => '123456',
                'catatan'  => 'Akun siap. Tagihan dan ronda mulai bulan depan.',
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

        // Sync username jika alamat berubah
        if (isset($update['blok_id'])) {
            $blok = $db->table('blok')->where('id', $update['blok_id'])->get()->getRowArray();
            $uname = $this->buatUsername($blok['nama'], $update['nomor'], $update['akhiran'] ?? '');
            $db->table('users')->where('keluarga_id', $id)->where('role', 'warga')->update(['username' => $uname]);
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
                $update[$k] = $a[$k];
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
        if ($status !== 'aktif' && strcasecmp($a['hubungan'], 'Kepala keluarga') === 0) {
            $aktifLain = $db->table('warga')
                ->where('keluarga_id', $a['keluarga_id'])
                ->where('status', 'aktif')
                ->where('id !=', $anggotaId)
                ->countAllResults();
            // Wajib pilih kepala baru dulu — dicek di controller dengan param kepala_baru_id
        }
        $db->table('warga')->where('id', $anggotaId)->update([
            'status'         => $status,
            'tanggal_status' => date('Y-m-d'),
            'alasan'         => $alasan,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        // Nonaktifkan akun pengurus yang tertaut anggota ini
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
            'aktif'     => 0,
            'username'  => 'pindah_' . $keluargaId . '_' . time(),
            'updated_at'=> date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();
        (new AuditService())->log('pindah_keluarga', 'keluarga', $keluargaId, null, ['tanggal' => $tanggal, 'catatan' => $catatan], 'pengguna', true, $userId);
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
        (new AuditService())->log('reset_pin', 'users', (int) $u['id'], null, ['keluarga_id' => $keluargaId], 'pengguna', true, $userId);
        (new NotifikasiService())->kirim((int) $u['id'], 'reset_pin', 'PIN direset', 'PIN Anda direset pengurus. PIN sementara: 123456. Wajib diganti saat login.', '/ganti-pin');
        return ['ok' => true, 'message' => 'PIN direset ke 123456.'];
    }

    public function gantiKepala(int $keluargaId, int $anggotaBaruId, int $userId): array
    {
        $db = \Config\Database::connect();
        $baru = $db->table('warga')->where('id', $anggotaBaruId)->where('keluarga_id', $keluargaId)->where('status', 'aktif')->get()->getRowArray();
        if (!$baru) {
            return ['ok' => false, 'message' => 'Anggota tidak valid.'];
        }
        $db->table('warga')->where('keluarga_id', $keluargaId)->where('hubungan', 'Kepala keluarga')->update(['hubungan' => 'Anggota']);
        $db->table('warga')->where('id', $anggotaBaruId)->update(['hubungan' => 'Kepala keluarga']);
        (new AuditService())->log('ganti_kepala', 'keluarga', $keluargaId, null, ['warga_id' => $anggotaBaruId], 'pengguna', true, $userId);
        return ['ok' => true];
    }

}
