<?php

namespace App\Libraries;

/**
 * Alokasi pembayaran ke tagihan.
 *
 * - Jika $hanyaIds diisi: hanya tagihan itu (urutan = urutan checklist warga).
 * - Jika kosong: prioritas lama (khusus → kas → denda_ronda, periode terlama dulu)
 *   untuk pembayaran lama / catat pengurus tanpa pilih tagihan.
 */
class AlokasiPembayaran
{
    /** @var list<string> */
    public const URUTAN_JENIS = ['khusus', 'kas', 'denda_ronda'];

    /**
     * @param list<array{id:int|string, jenis:string, periode?:string, sisa?:int|float, nominal?:int|float}> $tagihan
     * @param list<int|string>|null $hanyaIds ID tagihan yang dipilih warga (opsional)
     * @return array{potongan: list<array{tagihan_id:int, jumlah:int, jenis:string, periode:string}>, sisaBayar: int}
     */
    public static function alokasi(array $tagihan, int|float $nominalBayar, ?array $hanyaIds = null): array
    {
        $sisa = (int) max(0, round((float) $nominalBayar));

        $byId = [];
        foreach ($tagihan as $t) {
            $id = (int) $t['id'];
            $sisaItem = (int) max(0, round((float) ($t['sisa'] ?? $t['nominal'] ?? 0)));
            if ($sisaItem <= 0) {
                continue;
            }
            $byId[$id] = [
                'id'      => $id,
                'jenis'   => (string) ($t['jenis'] ?? ''),
                'periode' => (string) ($t['periode'] ?? ''),
                'sisa'    => $sisaItem,
            ];
        }

        $list = [];
        $ids = null;
        if ($hanyaIds !== null && $hanyaIds !== []) {
            $ids = [];
            foreach ($hanyaIds as $raw) {
                $id = (int) $raw;
                if ($id > 0 && !in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
            // Urutan sesuai pilihan warga
            foreach ($ids as $id) {
                if (isset($byId[$id])) {
                    $list[] = $byId[$id];
                }
            }
        } else {
            $list = array_values($byId);
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
        }

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

    /**
     * Parse tagihan_ids dari JSON string / array.
     * @return list<int>|null null = tidak ditentukan (pakai prioritas lama)
     */
    public static function parseIds(mixed $raw): ?array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return null;
            }
            $raw = $decoded;
        }
        if (!is_array($raw)) {
            return null;
        }
        $ids = [];
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids === [] ? null : array_values(array_unique($ids));
    }
}
