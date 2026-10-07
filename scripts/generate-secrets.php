<?php
/**
 * Generate CRON_TOKEN + VAPID keys untuk rtdua Stage 12.
 *
 * Cara pakai (pilih salah satu):
 *
 * A) Di komputer yang punya PHP:
 *    php scripts/generate-secrets.php
 *
 * B) Di hosting (File Manager → Terminal / SSH), dari folder backend setelah deploy:
 *    php generate-secrets.php
 *
 * C) Lewat browser (sementara saja, lalu HAPUS file ini dari public!):
 *    Upload ke folder yang bisa diakses, buka URL, salin hasilnya, lalu hapus file.
 *
 * Output: teks siap tempel ke file .env di hosting.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

function randomToken(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/**
 * Generate VAPID key pair (P-256) tanpa library.
 * Public key: URL-safe base64 (tanpa padding) — format yang dipakai Web Push.
 */
function generateVapidKeys(): array
{
    if (!function_exists('openssl_pkey_new')) {
        throw new RuntimeException('Ekstensi openssl PHP tidak aktif.');
    }

    $config = [
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name'       => 'prime256v1',
    ];
    $key = openssl_pkey_new($config);
    if ($key === false) {
        throw new RuntimeException('Gagal membuat kunci EC: ' . openssl_error_string());
    }

    $details = openssl_pkey_get_details($key);
    if ($details === false || empty($details['ec']['x']) || empty($details['ec']['y']) || empty($details['ec']['d'])) {
        throw new RuntimeException('Detail kunci EC tidak lengkap.');
    }

    // Public uncompressed: 0x04 || X || Y
    $public = "\x04" . $details['ec']['x'] . $details['ec']['y'];
    $private = $details['ec']['d'];

    return [
        'publicKey'  => base64UrlEncode($public),
        'privateKey' => base64UrlEncode($private),
    ];
}

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// ---- generate ----
$cron = randomToken(32);
try {
    $vapid = generateVapidKeys();
} catch (Throwable $e) {
    echo "ERROR VAPID: " . $e->getMessage() . "\n";
    echo "CRON_TOKEN tetap bisa dipakai:\n";
    echo "CRON_TOKEN={$cron}\n";
    exit(1);
}

$subject = 'mailto:ganti-dengan-email-kamu@contoh.com';

$out = <<<ENV
# ===== Tempel baris di bawah ke file .env di HOSTING (bukan di GitHub) =====
# Lokasi .env: folder backend di server (satu level dengan app/, public/, writable/)
# Setelah disimpan, HAPUS file generate-secrets.php dari server bila di-upload.

CRON_TOKEN={$cron}
VAPID_PUBLIC_KEY={$vapid['publicKey']}
VAPID_PRIVATE_KEY={$vapid['privateKey']}
VAPID_SUBJECT={$subject}

# Catatan:
# - CRON_TOKEN: rahasia untuk header X-Cron-Token di cron-job.org
# - VAPID_*: kunci push browser (satu set per lingkungan: dev beda dengan prod)
# - VAPID_SUBJECT: ganti ke email kamu (format mailto:...)
# - Jangan commit .env ke GitHub
ENV;

echo $out, "\n";
