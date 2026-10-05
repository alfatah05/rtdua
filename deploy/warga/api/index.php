<?php
/**
 * Thin proxy — subdomain warga
 * Semua request /api/* diteruskan ke backend CI4 di folder rt-app (di luar public).
 * Sesuaikan path relatif ke backend sesuai struktur hosting.
 */
$backend = dirname(__DIR__, 3) . '/rt-app/public/index.php'; // sesuaikan saat deploy
if (file_exists($backend)) {
    require $backend;
} else {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Backend belum terpasang']);
}
EOF