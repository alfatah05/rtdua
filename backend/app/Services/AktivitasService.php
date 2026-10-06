<?php

namespace App\Services;

class AktivitasService
{
    public function list(array $filter = []): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('audit_log a')
            ->select('a.*, u.username, u.jabatan, u.role')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where('a.tampil', 1)
            ->orderBy('a.waktu', 'DESC');

        if (!empty($filter['bulan']) && !empty($filter['tahun'])) {
            $ym = sprintf('%04d-%02d', (int) $filter['tahun'], (int) $filter['bulan']);
            $q->like('a.waktu', $ym, 'after');
        }
        $limit = min(100, max(1, (int) ($filter['limit'] ?? 50)));
        $rows = $q->limit($limit)->get()->getResultArray();

        return array_map(static function ($r) {
            $pelaku = 'Sistem';
            if (($r['sumber'] ?? '') === 'sistem_otomatis' || ($r['sumber'] ?? '') === 'developer') {
                $pelaku = 'Sistem';
            } elseif (!empty($r['username'])) {
                $pelaku = $r['username'];
                if (!empty($r['jabatan'])) {
                    $pelaku .= ' (' . $r['jabatan'] . ')';
                }
            }
            return [
                'id'     => (int) $r['id'],
                'waktu'  => $r['waktu'],
                'aksi'   => $r['aksi'],
                'objek'  => $r['objek'],
                'objek_id'=> $r['objek_id'],
                'pelaku' => $pelaku,
                'sumber' => $r['sumber'],
                'sebelum'=> $r['sebelum'] ? json_decode($r['sebelum'], true) : null,
                'sesudah'=> $r['sesudah'] ? json_decode($r['sesudah'], true) : null,
            ];
        }, $rows);
    }
}
