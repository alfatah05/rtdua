<?php

namespace App\Services;

/**
 * Web Push (VAPID). Butuh paket minishlink/web-push + VAPID di .env.
 * Jika library/kunci belum siap, langganan tetap disimpan; kirim di-skip aman.
 */
class PushService
{
    public function simpanLangganan(int $userId, array $sub, ?string $perangkat = null): array
    {
        $endpoint = (string) ($sub['endpoint'] ?? '');
        if ($endpoint === '') {
            return ['ok' => false, 'message' => 'Endpoint push wajib.'];
        }
        $keys = $sub['keys'] ?? [];
        $kunci = json_encode([
            'p256dh' => $keys['p256dh'] ?? '',
            'auth'   => $keys['auth'] ?? '',
        ], JSON_UNESCAPED_SLASHES);

        $db = \Config\Database::connect();
        $existing = $db->table('push_langganan')->where('endpoint', $endpoint)->get()->getRowArray();
        if ($existing) {
            $db->table('push_langganan')->where('id', $existing['id'])->update([
                'user_id'     => $userId,
                'kunci'       => $kunci,
                'perangkat'   => $perangkat,
                'aktif'       => 1,
                'jumlah_gagal'=> 0,
            ]);
            return ['ok' => true, 'id' => (int) $existing['id']];
        }
        $db->table('push_langganan')->insert([
            'user_id'   => $userId,
            'endpoint'  => $endpoint,
            'kunci'     => $kunci,
            'perangkat' => $perangkat,
            'aktif'     => 1,
            'jumlah_gagal' => 0,
            'created_at'=> date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'id' => (int) $db->insertID()];
    }

    public function nonaktifkanEndpoint(string $endpoint): void
    {
        if ($endpoint === '') {
            return;
        }
        $db = \Config\Database::connect();
        $db->table('push_langganan')->where('endpoint', $endpoint)->update(['aktif' => 0]);
    }

    public function nonaktifkanSemuaUser(int $userId): void
    {
        $db = \Config\Database::connect();
        $db->table('push_langganan')->where('user_id', $userId)->update(['aktif' => 0]);
    }

    public function publicKey(): ?string
    {
        $k = (string) env('VAPID_PUBLIC_KEY', '');
        return $k !== '' ? $k : null;
    }

    public function kirimUntukNotifikasi(int $notifId): void
    {
        $db = \Config\Database::connect();
        $n = $db->table('notifikasi')->where('id', $notifId)->get()->getRowArray();
        if (!$n) {
            return;
        }
        $payload = json_encode([
            'title' => $n['judul'],
            'body'  => mb_substr((string) $n['isi'], 0, 120),
            'url'   => $n['tautan'] ?: '/notifikasi',
            'tag'   => 'notif-' . $notifId,
        ], JSON_UNESCAPED_UNICODE);

        $subs = $db->table('push_langganan')
            ->where('user_id', (int) $n['user_id'])
            ->where('aktif', 1)
            ->get()
            ->getResultArray();

        if (!$subs) {
            $this->tandaiStatus($notifId, 'skip');
            return;
        }

        $okAny = false;
        foreach ($subs as $s) {
            $r = $this->kirimKeLangganan($s, $payload);
            if ($r === true) {
                $okAny = true;
            }
        }
        $this->tandaiStatus($notifId, $okAny ? 'sent' : 'pending');
    }

    public function ulangGagal(int $limit = 40): array
    {
        $db = \Config\Database::connect();
        $sent = 0;
        $fail = 0;
        if (!$db->fieldExists('push_status', 'notifikasi')) {
            return ['sent' => 0, 'fail' => 0, 'note' => 'kolom push_status belum ada'];
        }
        $rows = $db->table('notifikasi')
            ->where('push_status', 'pending')
            ->where('push_coba <', 5)
            ->where('dibuat_pada >', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
        foreach ($rows as $n) {
            try {
                $this->kirimUntukNotifikasi((int) $n['id']);
                $sent++;
            } catch (\Throwable $e) {
                $fail++;
                log_message('error', 'ulangGagal: ' . $e->getMessage());
            }
        }
        $db->table('push_langganan')->where('jumlah_gagal >=', 8)->update(['aktif' => 0]);
        return ['sent' => $sent, 'fail' => $fail];
    }

    private function tandaiStatus(int $id, string $status): void
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('push_status', 'notifikasi')) {
            return;
        }
        $db->table('notifikasi')->where('id', $id)->set([
            'push_status' => $status,
            'push_coba'   => 'push_coba + 1',
        ], false)->update();
    }

    private function kirimKeLangganan(array $row, string $payload): ?bool
    {
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            return null;
        }
        $public = (string) env('VAPID_PUBLIC_KEY', '');
        $private = (string) env('VAPID_PRIVATE_KEY', '');
        $subject = (string) env('VAPID_SUBJECT', 'mailto:admin@localhost');
        if ($public === '' || $private === '') {
            return null;
        }

        $keys = json_decode((string) $row['kunci'], true) ?: [];
        $subscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $row['endpoint'],
            'publicKey'=> $keys['p256dh'] ?? '',
            'authToken'=> $keys['auth'] ?? '',
        ]);

        try {
            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject'    => $subject,
                    'publicKey'  => $public,
                    'privateKey' => $private,
                ],
            ]);
            $report = $webPush->sendOneNotification($subscription, $payload);
            $db = \Config\Database::connect();
            if ($report->isSuccess()) {
                $db->table('push_langganan')->where('id', $row['id'])->update(['jumlah_gagal' => 0]);
                return true;
            }
            $code = $report->getResponse() ? $report->getResponse()->getStatusCode() : 0;
            $db->table('push_langganan')->where('id', $row['id'])->set('jumlah_gagal', 'jumlah_gagal + 1', false)->update();
            if (in_array($code, [404, 410], true)) {
                $db->table('push_langganan')->where('id', $row['id'])->update(['aktif' => 0]);
            }
            return false;
        } catch (\Throwable $e) {
            log_message('error', 'WebPush: ' . $e->getMessage());
            return false;
        }
    }
}
