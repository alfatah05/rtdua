<?php

namespace App\Services;

class AuthService
{
    private const MAX_FAIL = 5;
    /** Kunci sementara setelah gagal berulang (menit). Dev: 1 menit. */
    private const LOCK_MINUTES = 1;

    public function attempt(string $side, string $username, string $secret): array
    {
        $db = \Config\Database::connect();
        $usernameNorm = $this->normalizeUsername($username, $side);

        if ($this->isLocked($usernameNorm, $side)) {
            return ['ok' => false, 'message' => 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.'];
        }

        $user = $db->table('users')
            ->where('username', $usernameNorm)
            ->where('aktif', 1)
            ->get()
            ->getRowArray();

        if (!$user || !$this->roleAllowed($side, $user['role'])) {
            $this->recordAttempt($usernameNorm, $side, false);
            return ['ok' => false, 'message' => 'Username atau kredensial salah.'];
        }

        if (!password_verify($secret, $user['password_hash'])) {
            $this->recordAttempt($usernameNorm, $side, false);
            return ['ok' => false, 'message' => 'Username atau kredensial salah.'];
        }

        if ($side === 'warga') {
            $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
            if ($peng && !(int) $peng['akses_warga'] && !(int) $user['is_developer']) {
                return ['ok' => false, 'message' => 'Akses warga sedang ditutup.'];
            }
        }

        $this->recordAttempt($usernameNorm, $side, true);
        $this->startSession($side, $user);

        try {
            (new AuditService())->log('login', 'users', $user['id'], null, ['side' => $side], 'pengguna', true, (int) $user['id']);
        } catch (\Throwable $e) {
            // jangan gagalkan login
        }

        return [
            'ok'   => true,
            'user' => $this->publicUser($user),
            'harus_ganti_kredensial' => (bool) $user['harus_ganti_kredensial'],
        ];
    }

    public function logout(string $side): void
    {
        $session = session();
        $key = 'auth_' . $side;
        $uid = $session->get($key . '_id');
        $session->remove($key . '_id');
        $session->remove($key . '_role');
        $session->remove($key . '_user');
        if ($uid) {
            try {
                (new AuditService())->log('logout', 'users', $uid, null, ['side' => $side], 'pengguna', true, (int) $uid);
            } catch (\Throwable $e) {
            }
        }
    }

    public function current(string $side): ?array
    {
        $session = session();
        $key = 'auth_' . $side;
        $id = $session->get($key . '_id');
        if (!$id) {
            return null;
        }
        $db = \Config\Database::connect();
        $user = $db->table('users')->where('id', $id)->where('aktif', 1)->get()->getRowArray();
        return $user ? $this->publicUser($user) : null;
    }

    public function changeCredential(string $side, int $userId, string $newSecret): array
    {
        if ($side === 'warga') {
            if (!preg_match('/^\d{6}$/', $newSecret) || $newSecret === '123456') {
                return ['ok' => false, 'message' => 'PIN harus 6 digit dan bukan 123456.'];
            }
        } else {
            if (strlen($newSecret) < 8) {
                return ['ok' => false, 'message' => 'Password minimal 8 karakter.'];
            }
        }

        $db = \Config\Database::connect();
        $existing = $db->table('users')->where('id', $userId)->where('aktif', 1)->get()->getRowArray();
        if (!$existing) {
            return ['ok' => false, 'message' => 'Akun tidak ditemukan.'];
        }

        $hash = password_hash($newSecret, PASSWORD_DEFAULT);
        $db->table('users')->where('id', $userId)->update([
            'password_hash'          => $hash,
            'harus_ganti_kredensial' => 0,
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        $after = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if (!$after || !password_verify($newSecret, $after['password_hash'])) {
            return ['ok' => false, 'message' => 'Gagal menyimpan kredensial baru.'];
        }

        try {
            (new AuditService())->log('ganti_kredensial', 'users', $userId, null, null, 'pengguna', true, $userId);
        } catch (\Throwable $e) {
        }

        return ['ok' => true, 'message' => 'Kredensial diperbarui.'];
    }

    private function startSession(string $side, array $user): void
    {
        $session = session();
        $key = 'auth_' . $side;
        $session->set([
            $key . '_id'   => (int) $user['id'],
            $key . '_role' => $user['role'],
            $key . '_user' => $this->publicUser($user),
        ]);
    }

    private function publicUser(array $user): array
    {
        $nama = $user['jabatan']
            ? ($user['username'] . ' · ' . $user['jabatan'])
            : $user['username'];
        $alamat = null;
        $foto = null;
        $wargaId = $user['warga_id'] ? (int) $user['warga_id'] : null;
        $keluargaId = $user['keluarga_id'] ? (int) $user['keluarga_id'] : null;

        $db = \Config\Database::connect();

        if ($wargaId) {
            $w = $db->table('warga')->where('id', $wargaId)->get()->getRowArray();
            if ($w) {
                if (!empty($w['nama'])) {
                    $nama = $w['nama'];
                }
                if (!empty($w['foto'])) {
                    $foto = $w['foto'];
                }
                if (!$keluargaId && !empty($w['keluarga_id'])) {
                    $keluargaId = (int) $w['keluarga_id'];
                }
            }
        } elseif ($keluargaId && ($user['role'] ?? '') === 'warga') {
            $kep = $db->table('warga')
                ->where('keluarga_id', $keluargaId)
                ->where('status', 'aktif')
                ->where('hubungan', 'Kepala keluarga')
                ->get()->getRowArray();
            if ($kep) {
                $nama = $kep['nama'] ?: $nama;
                $foto = $kep['foto'] ?? null;
                $wargaId = (int) $kep['id'];
            }
        }

        if ($keluargaId) {
            $k = $db->table('keluarga k')
                ->select('k.nomor, k.akhiran, b.nama as blok')
                ->join('blok b', 'b.id = k.blok_id', 'left')
                ->where('k.id', $keluargaId)
                ->get()->getRowArray();
            if ($k) {
                $alamat = ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? '');
            }
        }

        if (in_array($user['role'] ?? '', ['ketua', 'pengurus'], true) && $user['jabatan']) {
            $base = $nama;
            if ($wargaId) {
                $w = $db->table('warga')->select('nama')->where('id', $wargaId)->get()->getRowArray();
                if ($w && !empty($w['nama'])) {
                    $base = $w['nama'];
                }
            }
            $nama = $base;
        }

        return [
            'id'         => (int) $user['id'],
            'username'   => $user['username'],
            'nama'       => $nama,
            'alamat'     => $alamat,
            'foto'       => $foto,
            'role'       => $user['role'],
            'jabatan'    => $user['jabatan'],
            'keluarga_id'=> $keluargaId,
            'warga_id'   => $wargaId,
            'is_developer' => (bool) $user['is_developer'],
            'harus_ganti_kredensial' => (bool) $user['harus_ganti_kredensial'],
        ];
    }

    public function updateFotoProfil(string $side, int $userId, string $path): array
    {
        $path = trim($path);
        if ($path === '') {
            return ['ok' => false, 'message' => 'Path foto wajib.'];
        }
        $db = \Config\Database::connect();
        $user = $db->table('users')->where('id', $userId)->where('aktif', 1)->get()->getRowArray();
        if (!$user) {
            return ['ok' => false, 'message' => 'Akun tidak ditemukan.'];
        }

        $wargaId = $user['warga_id'] ? (int) $user['warga_id'] : null;
        if (!$wargaId && !empty($user['keluarga_id'])) {
            $kep = $db->table('warga')
                ->where('keluarga_id', (int) $user['keluarga_id'])
                ->where('status', 'aktif')
                ->where('hubungan', 'Kepala keluarga')
                ->get()->getRowArray();
            if ($kep) {
                $wargaId = (int) $kep['id'];
            }
        }
        if (!$wargaId) {
            return ['ok' => false, 'message' => 'Data warga tidak tertaut ke akun.'];
        }

        $db->table('warga')->where('id', $wargaId)->update([
            'foto' => $path,
        ]);

        $fresh = $db->table('users')->where('id', $userId)->get()->getRowArray();
        return ['ok' => true, 'data' => $this->publicUser($fresh ?: $user)];
    }

    private function normalizeUsername(string $u, string $side): string
    {
        $u = trim($u);
        if ($side === 'warga') {
            return strtolower(preg_replace('/\s+/', '', $u));
        }
        return strtolower($u);
    }

    private function roleAllowed(string $side, string $role): bool
    {
        if ($side === 'warga') {
            return $role === 'warga';
        }
        return in_array($role, ['ketua', 'pengurus'], true);
    }

    private function isLocked(string $identitas, string $side): bool
    {
        $db = \Config\Database::connect();
        $since = date('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60);
        $fails = $db->table('login_percobaan')
            ->where('identitas', $identitas)
            ->where('sisi', $side)
            ->where('berhasil', 0)
            ->where('dicoba_pada >=', $since)
            ->countAllResults();
        return $fails >= self::MAX_FAIL;
    }

    private function recordAttempt(string $identitas, string $side, bool $ok): void
    {
        $db = \Config\Database::connect();
        $db->table('login_percobaan')->insert([
            'identitas'   => $identitas,
            'sisi'        => $side,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'berhasil'    => $ok ? 1 : 0,
            'dicoba_pada' => date('Y-m-d H:i:s'),
        ]);
    }
}
