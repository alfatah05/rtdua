<?php

namespace App\Services;

class AktivitasService
{
    public function list(array $filter = []): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('audit_log a')
            ->select('a.*, u.username, u.nama as nama_akun, u.jabatan, u.role')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where('a.tampil', 1)
            ->orderBy('a.waktu', 'DESC');

        if (!empty($filter['bulan']) && !empty($filter['tahun'])) {
            $ym = sprintf('%04d-%02d', (int) $filter['tahun'], (int) $filter['bulan']);
            $q->like('a.waktu', $ym, 'after');
        }
        $limit = min(100, max(1, (int) ($filter['limit'] ?? 50)));
        $rows = $q->limit($limit)->get()->getResultArray();

        return array_map(fn ($r) => $this->mapRow($r), $rows);
    }

    public function detail(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $db = \Config\Database::connect();
        $r = $db->table('audit_log a')
            ->select('a.*, u.username, u.nama as nama_akun, u.jabatan, u.role')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where('a.id', $id)
            ->where('a.tampil', 1)
            ->get()
            ->getRowArray();
        if (!$r) {
            return null;
        }
        return $this->mapRow($r);
    }

    private function mapRow(array $r): array
    {
        $pelaku = 'Sistem';
        $sumber = (string) ($r['sumber'] ?? '');
        if ($sumber === 'sistem_otomatis' || $sumber === 'developer') {
            $pelaku = 'Sistem';
        } elseif (!empty($r['user_id'])) {
            $nama = trim((string) ($r['nama_akun'] ?? ''));
            if ($nama === '') {
                $nama = trim((string) ($r['username'] ?? ''));
            }
            $pelaku = $nama !== '' ? $nama : 'Pengurus';
        }

        return [
            'id'       => (int) $r['id'],
            'waktu'    => $r['waktu'],
            'aksi'     => $r['aksi'],
            'objek'    => $r['objek'],
            'objek_id' => $r['objek_id'],
            'pelaku'   => $pelaku,
            'jabatan'  => $r['jabatan'] ?? null,
            'role'     => $r['role'] ?? null,
            'sumber'   => $r['sumber'],
            'sebelum'  => $r['sebelum'] ? json_decode($r['sebelum'], true) : null,
            'sesudah'  => $r['sesudah'] ? json_decode($r['sesudah'], true) : null,
        ];
    }
}
