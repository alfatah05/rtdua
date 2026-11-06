<?php
/**
 * Thin proxy — warga
 *
 * Struktur hosting (sejajar):
 *   {parent}/warga/api/index.php   (file ini)
 *   {parent}/pengurus/...
 *   {parent}/rt-app/public/index.php
 *
 * Default backend folder: rt-app.
 * Deploy Dev: sed ganti rt-app → rt-app-dev (nama folder backend dev).
 * Override: env RT_BACKEND_DIR.
 */
header('Content-Type: application/json; charset=utf-8');

$_SERVER['HTTP_X_APP_SIDE'] = 'warga';

$backendDir = getenv('RT_BACKEND_DIR') ?: 'rt-app';

// Prioritas: folder sejajar (parent dari warga/pengurus), lalu fallback layout lama
$candidates = [
    // {parent}/rt-app/public/index.php  — struktur sejajar (utama)
    dirname(__DIR__, 2) . '/' . $backendDir . '/public/index.php',
    // fallback layout lebih dalam / lebih dangkal
    dirname(__DIR__, 3) . '/' . $backendDir . '/public/index.php',
    dirname(__DIR__, 4) . '/' . $backendDir . '/public/index.php',
    dirname(__DIR__, 5) . '/' . $backendDir . '/public/index.php',
    dirname(__DIR__, 1) . '/' . $backendDir . '/public/index.php',
];

$backend = null;
$tried = [];
foreach ($candidates as $p) {
    $ok = is_file($p);
    $tried[] = ['path' => $p, 'exists' => $ok];
    if ($ok && $backend === null) {
        $backend = $p;
    }
}

if ($backend === null) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'message' => $backendDir . ' tidak ketemu',
        'side' => 'warga',
        'backend_dir' => $backendDir,
        '__DIR__' => __DIR__,
        'tried' => $tried,
        'hint' => 'Pastikan folder warga, pengurus, dan ' . $backendDir . ' sejajar di parent yang sama',
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$root = dirname($backend, 2);
$boot = $root . '/vendor/codeigniter4/framework/system/Boot.php';

if (isset($_GET['debug'])) {
    echo json_encode([
        'ok' => true,
        'message' => 'path ketemu',
        'backend' => $backend,
        'side' => 'warga',
        'backend_dir' => $backendDir,
        'vendor_boot' => is_file($boot),
        'layout' => 'sejajar (dirname 2 = parent warga/pengurus/rt-app)',
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
$pathOnly = parse_url($uri, PHP_URL_PATH) ?: $uri;
if (preg_match('#/api(/.*)$#', $pathOnly, $m)) {
    $_SERVER['REQUEST_URI'] = $m[1] ?: '/';
    $_SERVER['PATH_INFO'] = $m[1] ?: '/';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

try {
    chdir(dirname($backend));
    require $backend;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'CI boot error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'side' => 'warga',
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
