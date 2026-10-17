<?php
/**
 * Jalankan sekali di browser setelah deploy:
 *   https://api-mu/patch_pastikan_tagihan_kas.php
 * Opsional: ?periode=2026-10
 * Lalu HAPUS file ini.
 */
header('Content-Type: text/plain; charset=utf-8');

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$db = \Config\Database::connect();
$periode = $_GET['periode'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
    http_response_code(400);
    echo "Periode tidak valid.\n";
    exit;
}

echo "Periode: {$periode}\n";

$peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
$nominalKas = (int) ($peng['nominal_kas'] ?? 0);
echo "pengaturan.nominal_kas = {$nominalKas}\n";

try {
    $komp = $db->table('iuran_komponen')->selectSum('nominal', 'total')->where('aktif', 1)->get()->getRowArray();
    $totalKomp = (int) ($komp['total'] ?? 0);
    echo "total iuran_komponen aktif = {$totalKomp}\n";
} catch (Throwable $e) {
    $totalKomp = 0;
    echo "iuran_komponen: " . $e->getMessage() . "\n";
}

$nominal = $totalKomp > 0 ? $totalKomp : $nominalKas;
echo "nominal yang dipakai = {$nominal}\n\n";

if ($nominal < 1) {
    echo "STOP: nominal 0. Isi komponen kas / nominal_kas dulu.\n";
    exit;
}

$keluarga = $db->table('keluarga')->where('status', 'aktif')->get()->getResultArray();
echo "Keluarga aktif: " . count($keluarga) . "\n";

$dibuat = 0;
$skip = 0;
$detail = [];

foreach ($keluarga as $k) {
    $kid = (int) $k['id'];
    $mulai = $k['mulai_periode'];
    if ($mulai === null || $mulai === '') {
        $mulai = date('Y-m');
    }
    if (strcmp((string) $mulai, $periode) > 0) {
        $skip++;
        $detail[] = "skip #{$kid} mulai_periode={$mulai} (belum mulai)";
        continue;
    }
    $ada = $db->table('iuran_tagihan')
        ->where('keluarga_id', $kid)
        ->where('periode', $periode)
        ->where('jenis', 'kas')
        ->where('dibatalkan', 0)
        ->countAllResults();
    if ($ada > 0) {
        $skip++;
        continue;
    }
    $db->table('iuran_tagihan')->insert([
        'keluarga_id' => $kid,
        'periode'     => $periode,
        'jenis'       => 'kas',
        'nominal'     => $nominal,
        'dibatalkan'  => 0,
        'created_at'  => date('Y-m-d H:i:s'),
    ]);
    $dibuat++;
    $detail[] = "OK buat #{$kid} nominal={$nominal}";
}

echo "Dibuat: {$dibuat}\n";
echo "Skip (sudah ada / belum mulai): {$skip}\n\n";
foreach ($detail as $line) {
    echo $line . "\n";
}
echo "\nSelesai. Hapus file patch_pastikan_tagihan_kas.php.\n";
