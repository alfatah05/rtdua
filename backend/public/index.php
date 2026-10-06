<?php
/**
 * Placeholder. Setelah `composer install`, file ini diganti otomatis
 * oleh index.php resmi CodeIgniter 4 dari vendor.
 */
header('Content-Type: application/json; charset=utf-8');
http_response_code(503);
echo json_encode([
    'ok' => false,
    'message' => 'Backend belum di-build. Jalankan composer install (via GitHub Actions).',
    'data' => null,
]);
