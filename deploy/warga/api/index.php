<?php
/**
 * Thin proxy — subdomain warga
 * Meneruskan /api/* ke backend CI4 (rt-app).
 */
$_SERVER['HTTP_X_APP_SIDE'] = 'warga';

// Path ke backend: sesuaikan kedalaman folder hosting
// Struktur tipikal: public_html/aa_sub_domain/warga-dev/api/ → ../../../rt-app
$candidates = [
    dirname(__DIR__, 3) . '/rt-app/public/index.php',
    dirname(__DIR__, 4) . '/rt-app/public/index.php',
    dirname(__DIR__, 2) . '/rt-app/public/index.php',
];
$backend = null;
foreach ($candidates as $p) {
    if (is_file($p)) {
        $backend = $p;
        break;
    }
}
if ($backend) {
    // Strip /api prefix for CI4 routing if needed
    // CI4 receives PATH_INFO relative to public/
    require $backend;
} else {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'Backend belum terpasang', 'data' => null]);
}
