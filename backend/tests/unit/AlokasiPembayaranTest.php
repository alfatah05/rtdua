<?php

/**
 * Tes unit murni — tidak butuh database / bootstrap CI4.
 * Dijalankan: php backend/tests/unit/AlokasiPembayaranTest.php
 * atau lewat GitHub Actions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../app/Libraries/AlokasiPembayaran.php';

use App\Libraries\AlokasiPembayaran;

function assert_eq($expected, $actual, string $label): void
{
    if ($expected !== $actual) {
        $e = var_export($expected, true);
        $a = var_export($actual, true);
        fwrite(STDERR, "FAIL: {$label}\n  expected: {$e}\n  actual:   {$a}\n");
        exit(1);
    }
    echo "OK: {$label}\n";
}

// Contoh dokumen 02: kas 40rb + denda 30rb, bayar 50rb → kas lunas, denda terpotong 10rb
$tagihan = [
    ['id' => 1, 'jenis' => 'kas', 'periode' => '2026-10', 'sisa' => 40000],
    ['id' => 2, 'jenis' => 'denda_ronda', 'periode' => '2026-09', 'sisa' => 10000],
    ['id' => 3, 'jenis' => 'denda_ronda', 'periode' => '2026-10', 'sisa' => 10000],
    ['id' => 4, 'jenis' => 'denda_ronda', 'periode' => '2026-10', 'sisa' => 10000],
];
$r = AlokasiPembayaran::alokasi($tagihan, 50000);
assert_eq(0, $r['sisaBayar'], 'contoh dok: tidak ada kelebihan');
assert_eq(40000, $r['potongan'][0]['jumlah'], 'contoh dok: kas terpotong penuh');
assert_eq('kas', $r['potongan'][0]['jenis'], 'contoh dok: pertama kas');
assert_eq(10000, $r['potongan'][1]['jumlah'], 'contoh dok: denda 09 terpotong 10rb');
assert_eq(2, $r['potongan'][1]['tagihan_id'], 'contoh dok: id denda 09');

$tagihan2 = [
    ['id' => 10, 'jenis' => 'kas', 'periode' => '2026-08', 'sisa' => 40000],
    ['id' => 11, 'jenis' => 'khusus', 'periode' => '2026-10', 'sisa' => 25000],
];
$r2 = AlokasiPembayaran::alokasi($tagihan2, 30000);
assert_eq('khusus', $r2['potongan'][0]['jenis'], 'khusus didahulukan');
assert_eq(25000, $r2['potongan'][0]['jumlah'], 'khusus penuh');
assert_eq('kas', $r2['potongan'][1]['jenis'], 'sisa ke kas');
assert_eq(5000, $r2['potongan'][1]['jumlah'], 'kas terpotong sisa');

$r3 = AlokasiPembayaran::alokasi([
    ['id' => 1, 'jenis' => 'kas', 'periode' => '2026-10', 'sisa' => 40000],
], 50000);
assert_eq(10000, $r3['sisaBayar'], 'kelebihan 10rb');

$r4 = AlokasiPembayaran::alokasi([], 1000);
assert_eq(1000, $r4['sisaBayar'], 'tanpa tagihan: semua kelebihan');
assert_eq([], $r4['potongan'], 'tanpa tagihan: potongan kosong');

$tagihan5 = [
    ['id' => 1, 'jenis' => 'kas', 'periode' => '2026-10', 'sisa' => 40000],
    ['id' => 2, 'jenis' => 'kas', 'periode' => '2026-08', 'sisa' => 40000],
];
$r5 = AlokasiPembayaran::alokasi($tagihan5, 40000);
assert_eq(2, $r5['potongan'][0]['tagihan_id'], 'bulan 08 lebih dulu dari 10');

$r6 = AlokasiPembayaran::alokasi([
    ['id' => 1, 'jenis' => 'kas', 'periode' => '2026-10', 'sisa' => 40000],
], 15000);
assert_eq(15000, $r6['potongan'][0]['jumlah'], 'cicil 15rb');
assert_eq(0, $r6['sisaBayar'], 'tidak kelebihan');

echo "\nSemua tes alokasi lulus.\n";
exit(0);
