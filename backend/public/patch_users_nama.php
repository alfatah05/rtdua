<?php
/**
 * Jalankan sekali di browser setelah deploy:
 *   https://api.domain-mu/patch_users_nama.php
 * Lalu HAPUS file ini.
 */
header('Content-Type: text/plain; charset=utf-8');

require dirname(__DIR__) . '/vendor/autoload.php';

$pathsConfig = new \Config\Paths();
require rtrim($pathsConfig->systemDirectory, '\\/ ') . '/Boot.php';

// Bootstrap minimal CI DB
$app = \Config\Services::codeigniter();
$app->initialize();
$db = \Config\Database::connect();

try {
    $fields = $db->getFieldNames('users');
    if (!in_array('nama', $fields, true)) {
        $db->query('ALTER TABLE users ADD COLUMN nama VARCHAR(128) NULL AFTER username');
        echo "OK: kolom users.nama ditambahkan.\n";
    } else {
        echo "SKIP: kolom users.nama sudah ada.\n";
    }

    // Ketua tanpa nama: isi default dari username (bukan jabatan)
    $db->query("UPDATE users SET nama = username WHERE role = 'ketua' AND (nama IS NULL OR nama = '')");
    echo "OK: ketua tanpa nama diisi dari username.\n";
    echo "Selesai. Hapus file patch_users_nama.php.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
