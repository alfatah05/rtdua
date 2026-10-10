<?php

namespace App\Services;

class RondaService
{
    public function listJadwalTetap(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $kel = $db->table('ronda_jadwal_tetap_keluarga j')
                ->select('j.keluarga_id, k.nomor, k.akhiran, b.nama as blok')
                ->join('keluarga k', 'k.id = j.keluarga_id')
                ->join('blok b', 'b.id = k.blok_id')
                ->where('j.jadwal_id', $r['id'])
                ->get()->getResultArray();
            $out[] = [
                'id' => (int) $r['id'],
                'hari' => (int) $r['hari'],
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
                'keluarga' => array_map(static function ($k) {
                    return [
                        'keluarga_id' => (int) $k['keluarga_id'],
                        'alamat' => $k['blok'] . '-' . $k['nomor'] . ($k['akhiran'] ?? ''),
                    ];
                }, $kel),
            ];
        }
        return $out;
    }

    // TEMPORARY STUB - full file will be restored in next commit
    public function detailMalam(string $tanggal): ?array
    {
        return null;
    }

    public function malamIni(): ?array { return $this->detailMalam(date('Y-m-d')); }
    public function malamTerdekat(): ?array { return null; }
    public function listJadwalKhusus(): array { return []; }
    public function simpanJadwalTetap(array $data, int $userId): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function simpanJadwalKhusus(array $data, int $userId): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function kalender(string $periode): array { return []; }
    public function absenManual(int $malamId, int $keluargaId, int $userId): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function absenWarga(int $malamId, int $keluargaId, string $foto): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function gantiKeluargaMalam(int $malamId, array $keluargaIds, int $userId): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function batalkanAbsen(int $absenId, int $userId): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function isiOtomatis(array $data, int $userId): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function buatSlotKosong(array $data, int $userId): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function terbitkanDenda(string $periodeLalu, string $periodeTagihan): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
    public function generateBulan(string $periode): array { return ['ok' => false, 'message' => 'Service sedang diperbaiki']; }
}
