<?php

namespace App\Services;

class AuditService
{
    public function log(
        string $aksi,
        ?string $objek = null,
        $objekId = null,
        $sebelum = null,
        $sesudah = null,
        string $sumber = 'pengguna',
        bool $tampil = true,
        ?int $userId = null
    ): void {
        $db = \Config\Database::connect();
        $db->table('audit_log')->insert([
            'waktu'    => date('Y-m-d H:i:s'),
            'user_id'  => $userId,
            'sumber'   => $sumber,
            'tampil'   => $tampil ? 1 : 0,
            'aksi'     => $aksi,
            'objek'    => $objek,
            'objek_id' => $objekId !== null ? (string) $objekId : null,
            'sebelum'  => $sebelum !== null ? json_encode($sebelum, JSON_UNESCAPED_UNICODE) : null,
            'sesudah'  => $sesudah !== null ? json_encode($sesudah, JSON_UNESCAPED_UNICODE) : null,
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
