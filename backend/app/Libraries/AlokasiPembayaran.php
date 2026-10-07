<?php

namespace App\Libraries;

/**
 * Fungsi murni alokasi pembayaran (dokumen 02).
 * Prioritas jenis: iuran khusus → kas → denda ronda.
 * Di dalam tiap jenis: periode (bulan) terlama dulu.
 * Boleh cicil; boleh bayar lebih (sisaBayar = kelebihan).
 *
 * Tidak bergantung Database — aman diuji unit tanpa bootstrap CI4.
 */
class AlokasiPembayaran
{
    /** @var list<string> */
    public const URUTAN_JENIS = ['khusus', 'kas', 'denda_ronda'];

    /**
     * @param list<array{id:int|string, jenis:string, periode?:string, sisa?:int|float, nominal?:int|float}> $tagihan
     * @return array{potongan: list<array{tagihan_id:int, jumlah:int, jenis:string, periode:string}>, sisaBayar: int}
     */
    public static function alokasi(array $tagihan, int|float $nominalBayar): array
    {
        $sisa = (int) max(0, round((float) $nominalBayar));

        $list = [];
        foreach ($tagihan as $t) {
            $sisaItem = (int) max(0, round((float) ($t['sisa'] ?? $t['nominal'] ?? 0)));
            if ($sisaItem <= 0) {
                continue;
            }
            $list[] = [
                'id'      => (int) $t['id'],
                'jenis'   => (string) ($t['jenis'] ?? ''),
                'periode' => (string) ($t['periode'] ?? ''),
                'sisa'    => $sisaItem,
            ];
        }

        usort($list, static function (array $a, array $b): int {
            $ja = array_search($a['jenis'], self::URUTAN_JENIS, true);
            $jb = array_search($b['jenis'], self::URUTAN_JENIS, true);
            if ($ja === false) {
                $ja = 99;
            }
            if ($jb === false) {
                $jb = 99;
            }
            if ($ja !== $jb) {
                return $ja <=> $jb;
            }
            return strcmp($a['periode'], $b['periode']);
        });

        $potongan = [];
        foreach ($list as $t) {
            if ($sisa <= 0) {
                break;
            }
            $ambil = min($t['sisa'], $sisa);
            if ($ambil > 0) {
                $potongan[] = [
                    'tagihan_id' => $t['id'],
                    'jumlah'     => $ambil,
                    'jenis'      => $t['jenis'],
                    'periode'    => $t['periode'],
                ];
                $sisa -= $ambil;
            }
        }

        return [
            'potongan'  => $potongan,
            'sisaBayar' => $sisa,
        ];
    }
}
