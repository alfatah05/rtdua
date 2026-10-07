<?php

namespace App\Services;

/**
 * Notifikasi in-app + antrian push (satu baris = satu notifikasi).
 * Push dikirim setelah insert (best-effort); gagal diulang cron tiap 5 menit.
 */
class NotifikasiService
{
    public function listForUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $db = \Config\Database::connect();
        $limit = min(100, max(1, $limit));
        $offset = max(0, $offset);
        $rows = $db->table('notifikasi')
            ->where('user_id', $userId)
            ->orderBy('dibuat_pada', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return array_map(static function ($r) {
            return [
                'id'         => (int) $r['id'],
                'jenis'      => $r['jenis'],
                'judul'      => $r['judul'],
                'isi'        => $r['isi'],
                'tautan'     => $r['tautan'],
                'dibaca'     => !empty($r['dibaca_pada']),
                'dibaca_pada'=> $r['dibaca_pada'],
                'dibuat_pada'=> $r['dibuat_pada'],
            ];
        }, $rows);
    }

    public function jumlahBelumDibaca(int $userId): int
    {
        $db = \Config\Database::connect();
        return (int) $db->table('notifikasi')
            ->where('user_id', $userId)
            ->where('dibaca_pada', null)
            ->countAllResults();
    }

    public function tandaiDibaca(int $userId, int $id): bool
    {
        $db = \Config\Database::connect();
        $row = $db->table('notifikasi')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$row) {
            return false;
        }
        if (empty($row['dibaca_pada'])) {
            $db->table('notifikasi')->where('id', $id)->update(['dibaca_pada' => date('Y-m-d H:i:s')]);
        }
        return true;
    }

    public function tandaiSemuaDibaca(int $userId): int
    {
        $db = \Config\Database::connect();
        $db->table('notifikasi')
            ->where('user_id', $userId)
            ->where('dibaca_pada', null)
            ->update(['dibaca_pada' => date('Y-m-d H:i:s')]);
        return $db->affectedRows();
    }

    /**
     * Simpan notifikasi + antri push. Jangan lempar error ke caller.
     */
    public function kirim(int $userId, string $jenis, string $judul, string $isi, ?string $tautan = null, bool $kirimPush = true): ?int
    {
        try {
            $db = \Config\Database::connect();
            $data = [
                'user_id'     => $userId,
                'jenis'       => $jenis,
                'judul'       => mb_substr($judul, 0, 255),
                'isi'         => $isi,
                'tautan'      => $tautan,
                'dibuat_pada' => date('Y-m-d H:i:s'),
            ];
            // kolom push_status opsional (setelah migrasi 003)
            if ($db->fieldExists('push_status', 'notifikasi')) {
                $data['push_status'] = $kirimPush ? 'pending' : 'skip';
                $data['push_coba'] = 0;
            }
            $db->table('notifikasi')->insert($data);
            $id = (int) $db->insertID();

            if ($kirimPush && $id > 0) {
                try {
                    (new PushService())->kirimUntukNotifikasi($id);
                } catch (\Throwable $e) {
                    log_message('error', 'Push gagal notif #' . $id . ': ' . $e->getMessage());
                }
            }
            return $id;
        } catch (\Throwable $e) {
            log_message('error', 'NotifikasiService::kirim: ' . $e->getMessage());
            return null;
        }
    }

    /** Semua ketua/pengurus aktif kecuali developer; opsional exclude pelaku. */
    public function kePengurus(string $jenis, string $judul, string $isi, ?string $tautan = null, ?int $kecualiUserId = null): void
    {
        $db = \Config\Database::connect();
        $q = $db->table('users')
            ->whereIn('role', ['ketua', 'pengurus'])
            ->where('aktif', 1)
            ->where('is_developer', 0);
        if ($kecualiUserId) {
            $q->where('id !=', $kecualiUserId);
        }
        $rows = $q->get()->getResultArray();
        foreach ($rows as $u) {
            $this->kirim((int) $u['id'], $jenis, $judul, $isi, $tautan);
        }
    }

    /** Notifikasi ke akun warga satu keluarga. */
    public function keKeluarga(int $keluargaId, string $jenis, string $judul, string $isi, ?string $tautan = null): void
    {
        $db = \Config\Database::connect();
        $rows = $db->table('users')
            ->where('role', 'warga')
            ->where('keluarga_id', $keluargaId)
            ->where('aktif', 1)
            ->get()
            ->getResultArray();
        foreach ($rows as $u) {
            $this->kirim((int) $u['id'], $jenis, $judul, $isi, $tautan);
        }
    }

    /** Hapus notifikasi lebih dari N hari. */
    public function bersihkanLama(int $hari = 90): int
    {
        $db = \Config\Database::connect();
        $batas = date('Y-m-d H:i:s', strtotime("-{$hari} days"));
        $db->table('notifikasi')->where('dibuat_pada <', $batas)->delete();
        return $db->affectedRows();
    }
}
