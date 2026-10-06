<?php
/**
 * Thin proxy — subdomain pengurus
 */
$_SERVER['HTTP_X_APP_SIDE'] = 'pengurus';

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
    require $backend;
} else {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'Backend belum terpasang', 'data' => null]);
}
