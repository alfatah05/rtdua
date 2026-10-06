<?php

namespace App\Services;

class PengurusService
{
    public function list(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('users u')
            ->select('u.id, u.role, u.username, u.jabatan, u.nomor_hp, u.tampil_di_bantuan, u.urutan_struktur, u.aktif, u.warga_id, w.nama as nama_warga')
            ->join('warga w', 'w.id = u.warga_id', 'left')
            ->whereIn('u.role', ['ketua', 'pengurus'])
            ->where('u.is_developer', 0)
            ->orderBy('u.urutan_struktur', 'ASC')
            ->orderBy('u.id', 'ASC')
            ->get()
            ->getResultArray();
        return array_map(static function ($r) {
            return [
                'id'               => (int) $r['id'],
                'role'             => $r['role'],
                'username'         => $r['username'],
                'nama'             => $r['nama_warga'] ?: $r['username'],
                'jabatan'          => $r['jabatan'],
                'nomor_hp'         => $r['nomor_hp'],
                'tampil_di_bantuan'=> (bool) $r['tampil_di_bantuan'],
                'urutan_struktur'  => (int) $r['urutan_struktur'],
                'aktif'            => (bool) $r['aktif'],
                'warga_id'         => $r['warga_id'] ? (int) $r['warga_id'] : null,
            ];
        }, $rows);
    }

    /** Struktur untuk warga/pengurus: tree ketua + pengurus aktif. */
    public function struktur(): array
    {
        $all = array_values(array_filter($this->list(), static fn ($p) => $p['aktif']));
        $ketua = null;
        $pengurus = [];
        foreach ($all as $p) {
            if ($p['role'] === 'ketua' && $ketua === null) {
                $ketua = $p;
            } else {
                $pengurus[] = $p;
            }
        }
        return ['ketua' => $ketua, 'pengurus' => $pengurus];
    }

    /** Daftar untuk menu Bantuan (ada nomor HP, tampil_di_bantuan). */
    public function bantuan(): array
    {
        return array_values(array_filter($this->list(), static function ($p) {
            return $p['aktif'] && $p['tampil_di_bantuan'] && !empty($p['nomor_hp']);
        }));
    }

    public function angkat(array $data, int $userId): array
    {
        $wargaId = (int) ($data['warga_id'] ?? 0);
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $jabatan = trim((string) ($data['jabatan'] ?? 'Pengurus'));
        $role = ($data['role'] ?? 'pengurus') === 'ketua' ? 'ketua' : 'pengurus';

        if ($wargaId < 1 || $username === '' || strlen($password) < 8) {
            return ['ok' => false, 'message' => 'warga_id, username, dan password (min 8) wajib.'];
        }

        $db = \Config\Database::connect();
        $warga = $db->table('warga')->where('id', $wargaId)->where('status', 'aktif')->get()->getRowArray();
        if (!$warga) {
            return ['ok' => false, 'message' => 'Anggota warga tidak ditemukan.'];
        }
        if ($db->table('users')->where('warga_id', $wargaId)->whereIn('role', ['ketua', 'pengurus'])->where('aktif', 1)->countAllResults() > 0) {
            return ['ok' => false, 'message' => 'Anggota ini sudah menjadi pengurus.'];
        }
        if ($db->table('users')->where('username', $username)->countAllResults() > 0) {
            return ['ok' => false, 'message' => 'Username sudah dipakai.'];
        }

        $db->table('users')->insert([
            'role'                   => $role,
            'username'               => $username,
            'password_hash'          => password_hash($password, PASSWORD_DEFAULT),
            'warga_id'               => $wargaId,
            'jabatan'                => $jabatan,
            'nomor_hp'               => $data['nomor_hp'] ?? null,
            'tampil_di_bantuan'      => !empty($data['tampil_di_bantuan']) ? 1 : 0,
            'urutan_struktur'        => (int) ($data['urutan_struktur'] ?? 10),
            'is_developer'           => 0,
            'aktif'                  => 1,
            'harus_ganti_kredensial' => 0,
            'created_at'             => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('angkat_pengurus', 'users', $id, null, [
            'username' => $username,
            'role'     => $role,
            'warga_id' => $wargaId,
            'jabatan'  => $jabatan,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id, 'username' => $username]];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $u = $db->table('users')->where('id', $id)->whereIn('role', ['ketua', 'pengurus'])->where('is_developer', 0)->get()->getRowArray();
        if (!$u) {
            return ['ok' => false, 'message' => 'Pengurus tidak ditemukan.'];
        }
        $update = [];
        foreach (['jabatan', 'nomor_hp'] as $k) {
            if (array_key_exists($k, $data)) {
                $update[$k] = $data[$k];
            }
        }
        if (array_key_exists('tampil_di_bantuan', $data)) {
            $update['tampil_di_bantuan'] = $data['tampil_di_bantuan'] ? 1 : 0;
        }
        if (array_key_exists('urutan_struktur', $data)) {
            $update['urutan_struktur'] = (int) $data['urutan_struktur'];
        }
        if (array_key_exists('aktif', $data)) {
            $update['aktif'] = $data['aktif'] ? 1 : 0;
        }
        if (!empty($data['password']) && strlen((string) $data['password']) >= 8) {
            $update['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('users')->where('id', $id)->update($update);
        (new AuditService())->log('ubah_pengurus', 'users', $id, null, $update, 'pengguna', true, $userId);
        return ['ok' => true];
    }
}
