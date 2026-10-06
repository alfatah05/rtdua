<?php

namespace App\Services;

class NotifikasiService
{
    public function kirim(int $userId, string $jenis, string $judul, string $isi, ?string $tautan = null): void
    {
        $db = \Config\Database::connect();
        $db->table('notifikasi')->insert([
            'user_id'    => $userId,
            'jenis'      => $jenis,
            'judul'      => $judul,
            'isi'        => $isi,
            'tautan'     => $tautan,
            'dibuat_pada'=> date('Y-m-d H:i:s'),
        ]);
    }

    public function kePengurus(string $jenis, string $judul, string $isi, ?string $tautan = null): void
    {
        $db = \Config\Database::connect();
        $rows = $db->table('users')
            ->whereIn('role', ['ketua', 'pengurus'])
            ->where('aktif', 1)
            ->where('is_developer', 0)
            ->get()
            ->getResultArray();
        foreach ($rows as $u) {
            $this->kirim((int) $u['id'], $jenis, $judul, $isi, $tautan);
        }
    }
}
