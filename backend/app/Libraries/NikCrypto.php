<?php

namespace App\Libraries;

/**
 * NIK: disimpan terenkripsi; hash HMAC untuk cek duplikat tanpa dekripsi.
 * Kunci dari env encryption.key (wajib di production).
 */
class NikCrypto
{
    public static function hash(?string $nik): ?string
    {
        $nik = self::normalize($nik);
        if ($nik === null) {
            return null;
        }
        $key = self::keyMaterial();
        return hash_hmac('sha256', $nik, $key);
    }

    public static function encrypt(?string $nik): ?string
    {
        $nik = self::normalize($nik);
        if ($nik === null) {
            return null;
        }
        try {
            $encrypter = \Config\Services::encrypter();
            return base64_encode($encrypter->encrypt($nik));
        } catch (\Throwable $e) {
            $key = substr(hash('sha256', self::keyMaterial(), true), 0, 32);
            $iv = random_bytes(16);
            $cipher = openssl_encrypt($nik, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            return base64_encode($iv . $cipher);
        }
    }

    public static function decrypt(?string $enc): ?string
    {
        if ($enc === null || $enc === '') {
            return null;
        }
        try {
            $encrypter = \Config\Services::encrypter();
            return $encrypter->decrypt(base64_decode($enc));
        } catch (\Throwable $e) {
            $raw = base64_decode($enc, true);
            if ($raw === false || strlen($raw) < 17) {
                return null;
            }
            $key = substr(hash('sha256', self::keyMaterial(), true), 0, 32);
            $iv = substr($raw, 0, 16);
            $cipher = substr($raw, 16);
            $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            return $plain === false ? null : $plain;
        }
    }

    private static function normalize(?string $nik): ?string
    {
        if ($nik === null) {
            return null;
        }
        $n = preg_replace('/\D/', '', $nik);
        if ($n === '' || strlen($n) < 8) {
            return null;
        }
        return $n;
    }

    private static function keyMaterial(): string
    {
        $key = env('encryption.key') ?: (getenv('encryption.key') ?: '');
        if ($key === '') {
            $key = env('encryption.nikKey') ?: 'rtdua-dev-only-change-me';
        }
        return (string) $key;
    }
}
