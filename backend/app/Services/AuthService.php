<?php

namespace App\Services;

use App\Libraries\SideContext;
use App\Services\AuditService;

class AuthService
{
    private const MAX_FAIL = 5;
    private const LOCK_MINUTES = 15;

    public function attempt(string $side, string $username, string $secret): array
    {
        $db = \Config\Database::connect();
        $usernameNorm = $this->normalizeUsername($username, $side);

        if ($this->isLocked($usernameNorm, $side)) {
            return ['ok' => false, 'message' => 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.'];
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

        // Akses warga ditutup?
        if ($side === 'warga') {
            $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
            if ($peng && !(int) $peng['akses_warga'] && !(int) $user['is_developer']) {
                return ['ok' => false, 'message' => 'Akses warga sedang ditutup.'];
            }
        }

        $this->recordAttempt($usernameNorm, $side, true);
        $this->startSession($side, $user);

        (new AuditService())->log('login', 'users', $user['id'], null, ['side' => $side], 'pengguna', true, (int) $user['id']);

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
            (new AuditService())->log('logout', 'users', $uid, null, ['side' => $side], 'pengguna', true, (int) $uid);
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
        $hash = password_hash($newSecret, PASSWORD_DEFAULT);
        $db->table('users')->where('id', $userId)->update([
            'password_hash' => $hash,
            'harus_ganti_kredensial' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        (new AuditService())->log('ganti_kredensial', 'users', $userId, null, null, 'pengguna', true, $userId);
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
        return [
            'id'         => (int) $user['id'],
            'username'   => $user['username'],
            'nama'       => $user['jabatan'] ? ($user['username'] . ' · ' . $user['jabatan']) : $user['username'],
            'role'       => $user['role'],
            'jabatan'    => $user['jabatan'],
            'keluarga_id'=> $user['keluarga_id'] ? (int) $user['keluarga_id'] : null,
            'warga_id'   => $user['warga_id'] ? (int) $user['warga_id'] : null,
            'is_developer' => (bool) $user['is_developer'],
            'harus_ganti_kredensial' => (bool) $user['harus_ganti_kredensial'],
        ];
    }

    private function normalizeUsername(string $u, string $side): string
    {
        $u = trim($u);
        if ($side === 'warga') {
            // tidak bedakan huruf/spasi
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
