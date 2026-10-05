<?php
/**
 * Thin proxy — subdomain warga-dev
 * Meneruskan /api/* ke backend CI4 di /home/rtdx8123/rt-app
 *
 * Path relatif dari file ini:
 *   api/ → warga-dev... → aa_sub_domain → public_html → home → rt-app/public/index.php
 */
$backend = dirname(__DIR__, 4) . '/rt-app/public/index.php';

if (file_exists($backend)) {
    require $backend;
} else {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'    => false,
        'error' => 'Backend belum terpasang atau path salah',
        'debug' => 'Mencari: ' . $backend,
    ]);
}
