<?php
/**
 * Jalankan sekali di browser setelah deploy:
 *   https://api.domain-mu/patch_users_foto.php
 * Lalu HAPUS file ini.
 *
 * Alternatif SQL:
 *   ALTER TABLE users ADD COLUMN foto VARCHAR(255) NULL AFTER nama;
 */
header('Content-Type: text/plain; charset=utf-8');

// Bootstrap CI4 lewat index (lebih aman di shared hosting)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$db = \Config\Database::connect();

try {
    $fields = $db->getFieldNames('users');
    if (!in_array('foto', $fields, true)) {
        // setelah nama jika ada, else setelah username
        if (in_array('nama', $fields, true)) {
            $db->query('ALTER TABLE users ADD COLUMN foto VARCHAR(255) NULL AFTER nama');
        } else {
            $db->query('ALTER TABLE users ADD COLUMN foto VARCHAR(255) NULL AFTER username');
        }
        echo "OK: kolom users.foto ditambahkan.\n";
    } else {
        echo "SKIP: kolom users.foto sudah ada.\n";
    }
    echo "Selesai. Hapus file patch_users_foto.php.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
