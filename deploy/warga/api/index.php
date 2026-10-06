<?php
/**
 * Thin proxy — warga
 * URL: /api/health  →  rt-app/public/index.php  (route: health)
 */
$_SERVER['HTTP_X_APP_SIDE'] = 'warga';

// Path dari: .../warga-dev/api/index.php
// naik 3: aa_sub_domain → public_html → home user → rt-app
// naik 4: jika struktur lebih dalam
$candidates = [
    dirname(__DIR__, 3) . '/rt-app/public/index.php', // public_html sibling? depends
    dirname(__DIR__, 4) . '/rt-app/public/index.php',
    dirname(__DIR__, 5) . '/rt-app/public/index.php',
    dirname(__DIR__, 2) . '/rt-app/public/index.php',
];

// Struktur umum:
// FTP root/
//   rt-app/public/index.php
//   public_html/aa_sub_domain/warga-dev/api/index.php  → dirname 4 levels up to FTP root
//   dari api/: __DIR__ = .../warga-dev/api
//   dirname 1 = warga-dev
//   dirname 2 = aa_sub_domain
//   dirname 3 = public_html
//   dirname 4 = FTP root (sibling of public_html) → /rt-app/public/index.php

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

// CI4: path request harus tanpa prefix /api jika route di CI tidak punya api/
// Banyak setup: REQUEST_URI tetap /api/health — di Routes kita pakai health tanpa api
// Strip /api dari path agar route 'health' cocok
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#/api(/.*)$#', $uri, $m)) {
    $_SERVER['REQUEST_URI'] = $m[1] ?: '/';
    $_SERVER['PATH_INFO'] = $m[1] ?: '/';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

chdir(dirname($backend));
require $backend;
