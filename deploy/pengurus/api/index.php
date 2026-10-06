<?php
/**
 * Thin proxy — pengurus
 */
$_SERVER['HTTP_X_APP_SIDE'] = 'pengurus';

$candidates = [
    dirname(__DIR__, 3) . '/rt-app/public/index.php',
    dirname(__DIR__, 4) . '/rt-app/public/index.php',
    dirname(__DIR__, 5) . '/rt-app/public/index.php',
    dirname(__DIR__, 2) . '/rt-app/public/index.php',
];

$backend = null;
$tried = [];
foreach ($candidates as $p) {
    $tried[] = $p;
    if (is_file($p)) {
        $backend = $p;
        break;
    }
}

if (!$backend) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'message' => 'Backend belum terpasang / path rt-app salah',
        'data' => ['tried' => $tried, '__DIR__' => __DIR__],
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#/api(/.*)$#', $uri, $m)) {
    $_SERVER['REQUEST_URI'] = $m[1] ?: '/';
    $_SERVER['PATH_INFO'] = $m[1] ?: '/';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

chdir(dirname($backend));
require $backend;
