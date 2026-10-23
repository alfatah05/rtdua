<?php

namespace App\Services;

/**
 * Fitur Reward / Undian multi-event.
 * - Pool: keluarga aktif yang belum pernah menang di siklus event.
 * - Hangus tetap menghabiskan giliran.
 * - Reset siklus manual per event.
 */
class RewardService
{
    private const CLAIM_HARI = 3;

    public function ensureTables(): void
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('reward_event')) {
            $db->query("CREATE TABLE IF NOT EXISTS `reward_event` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `judul` VARCHAR(255) NOT NULL,
              `isi_html` MEDIUMTEXT NULL,
              `status` ENUM('aktif','arsip') NOT NULL DEFAULT 'aktif',
              `created_by` INT UNSIGNED NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NULL,
              PRIMARY KEY (`id`),
              KEY `idx_reward_event_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        if (!$db->tableExists('reward_batch')) {
            $db->query("CREATE TABLE IF NOT EXISTS `reward_batch` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `event_id` INT UNSIGNED NOT NULL,
              `diundi_pada` DATETIME NOT NULL,
              `diundi_oleh` INT UNSIGNED NULL,
              `dibatalkan_pada` DATETIME NULL,
              `dibatalkan_oleh` INT UNSIGNED NULL,
              PRIMARY KEY (`id`),
              KEY `idx_reward_batch_event` (`event_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        if (!$db->tableExists('reward_pemenang')) {
            $db->query("CREATE TABLE IF NOT EXISTS `reward_pemenang` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `batch_id` INT UNSIGNED NOT NULL,
              `event_id` INT UNSIGNED NOT NULL,
              `keluarga_id` INT UNSIGNED NOT NULL,
              `status` ENUM('tersedia','diklaim','hangus') NOT NULL DEFAULT 'tersedia',
              `expired_at` DATETIME NOT NULL,
              `diklaim_pada` DATETIME NULL,
              `diklaim_oleh` INT UNSIGNED NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_reward_pemenang_event` (`event_id`),
              KEY `idx_reward_pemenang_batch` (`batch_id`),
              KEY `idx_reward_pemenang_keluarga` (`keluarga_id`),
              KEY `idx_reward_pemenang_status` (`status`, `expired_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        if (!$db->tableExists('reward_event_pernah')) {
            $db->query("CREATE TABLE IF NOT EXISTS `reward_event_pernah` (
              `event_id` INT UNSIGNED NOT NULL,
              `keluarga_id` INT UNSIGNED NOT NULL,
              `batch_id` INT UNSIGNED NOT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`event_id`, `keluarga_id`),
              KEY `idx_reward_pernah_batch` (`batch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function prosesHangus(?int $eventId = null): void
    {
        $this->ensureTables();
        $db = \Config\Database::connect();
        $b = $db->table('reward_pemenang')
            ->where('status', 'tersedia')
            ->where('expired_at <', date('Y-m-d H:i:s'));
        if ($eventId) {
            $b->where('event_id', $eventId);
        }
        $b->update(['status' => 'hangus']);
    }

    public function listEvent(bool $untukWarga = false): array
    {
        $this->ensureTables();
        $this->prosesHangus();
        $db = \Config\Database::connect();
        $rows = $db->table('reward_event')->orderBy('id', 'DESC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->mapEventSummary($r);
        }
        return $out;
    }

    public function detailEvent(int $id, ?int $keluargaIdUser = null, bool $pengurus = false): ?array
    {
        $this->ensureTables();
        $this->prosesHangus($id);
        $db = \Config\Database::connect();
        $r = $db->table('reward_event')->where('id', $id)->get()->getRowArray();
        if (!$r) {
            return null;
        }
        $data = $this->mapEventSummary($r);
        $data['isi_html'] = $r['isi_html'] ?? '';
        $data['pemenang'] = $this->listPemenangEvent($id, $pengurus);
        $data['pool_sisa'] = $this->hitungPoolSisa($id);
        $data['bisa_undi'] = $data['pool_sisa'] >= 1;
        $data['klaim_saya'] = null;
        if ($keluargaIdUser) {
            $data['klaim_saya'] = $this->kuponAktifKeluarga($id, $keluargaIdUser);
        }
        return $data;
    }

    public function buatEvent(array $data, int $userId): array
    {
        $this->ensureTables();
        $judul = trim((string) ($data['judul'] ?? ''));
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Judul wajib.'];
        }
        $isi = (string) ($data['isi_html'] ?? $data['isi'] ?? '');
        $db = \Config\Database::connect();
        $db->table('reward_event')->insert([
            'judul' => mb_substr($judul, 0, 255),
            'isi_html' => $isi,
            'status' => 'aktif',
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('reward_buat_event', 'reward_event', $id, null, ['judul' => $judul], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailEvent($id, null, true)];
    }

    public function ubahEvent(int $id, array $data, int $userId): array
    {
        $this->ensureTables();
        $db = \Config\Database::connect();
        $before = $db->table('reward_event')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Event tidak ditemukan.'];
        }
        $update = [];
        if (isset($data['judul'])) {
            $j = trim((string) $data['judul']);
            if ($j === '') {
                return ['ok' => false, 'message' => 'Judul wajib.'];
            }
            $update['judul'] = mb_substr($j, 0, 255);
        }
        if (array_key_exists('isi_html', $data) || array_key_exists('isi', $data)) {
            $update['isi_html'] = (string) ($data['isi_html'] ?? $data['isi'] ?? '');
        }
        if (isset($data['status']) && in_array($data['status'], ['aktif', 'arsip'], true)) {
            $update['status'] = $data['status'];
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('reward_event')->where('id', $id)->update($update);
        (new AuditService())->log('reward_ubah_event', 'reward_event', $id, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailEvent($id, null, true)];
    }

    public function hapusEvent(int $id, int $userId): array
    {
        $this->ensureTables();
        $db = \Config\Database::connect();
        $before = $db->table('reward_event')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Event tidak ditemukan.'];
        }
        $db->table('reward_pemenang')->where('event_id', $id)->delete();
        $db->table('reward_event_pernah')->where('event_id', $id)->delete();
        $db->table('reward_batch')->where('event_id', $id)->delete();
        $db->table('reward_event')->where('id', $id)->delete();
        (new AuditService())->log('reward_hapus_event', 'reward_event', $id, $before, null, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    public function undi(int $eventId, int $userId): array
    {
        $this->ensureTables();
        $this->prosesHangus($eventId);
        $db = \Config\Database::connect();
        $event = $db->table('reward_event')->where('id', $eventId)->get()->getRowArray();
        if (!$event) {
            return ['ok' => false, 'message' => 'Event tidak ditemukan.'];
        }
        $pool = $this->listPoolEligible($eventId);
        if (count($pool) < 1) {
            return ['ok' => false, 'message' => 'Pool kosong. Reset siklus dulu untuk mengundi lagi.'];
        }
        $ambil = min(2, count($pool));
        shuffle($pool);
        $pilih = array_slice($pool, 0, $ambil);
        $db->transStart();
        $db->table('reward_batch')->insert([
            'event_id' => $eventId,
            'diundi_pada' => date('Y-m-d H:i:s'),
            'diundi_oleh' => $userId,
        ]);
        $batchId = (int) $db->insertID();
        $expired = date('Y-m-d H:i:s', strtotime('+' . self::CLAIM_HARI . ' days'));
        foreach ($pilih as $keluargaId) {
            $db->table('reward_pemenang')->insert([
                'batch_id' => $batchId,
                'event_id' => $eventId,
                'keluarga_id' => $keluargaId,
                'status' => 'tersedia',
                'expired_at' => $expired,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('reward_event_pernah')->replace([
                'event_id' => $eventId,
                'keluarga_id' => $keluargaId,
                'batch_id' => $batchId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['ok' => false, 'message' => 'Gagal menyimpan hasil undian.'];
        }
        $judulEv = $event['judul'] ?? 'Reward';
        foreach ($pilih as $keluargaId) {
            $this->notifKeluarga(
                $keluargaId,
                'reward',
                'Selamat! Keluarga kamu terpilih',
                'Kamu terpilih di undian "' . $judulEv . '". Klaim dalam 3 hari di menu Reward.',
                '/reward/' . $eventId
            );
        }
        (new AuditService())->log('reward_undi', 'reward_batch', $batchId, null, [
            'event_id' => $eventId,
            'keluarga' => $pilih,
        ], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailEvent($eventId, null, true)];
    }

    public function batalkanBatch(int $batchId, int $userId): array
    {
        $this->ensureTables();
        $db = \Config\Database::connect();
        $batch = $db->table('reward_batch')->where('id', $batchId)->get()->getRowArray();
        if (!$batch) {
            return ['ok' => false, 'message' => 'Batch tidak ditemukan.'];
        }
        if (!empty($batch['dibatalkan_pada'])) {
            return ['ok' => false, 'message' => 'Batch sudah dibatalkan.'];
        }
        $pemenang = $db->table('reward_pemenang')->where('batch_id', $batchId)->get()->getResultArray();
        foreach ($pemenang as $p) {
            if (($p['status'] ?? '') === 'diklaim') {
                return ['ok' => false, 'message' => 'Tidak bisa dibatalkan: ada pemenang yang sudah klaim.'];
            }
        }
        $eventId = (int) $batch['event_id'];
        $db->transStart();
        $db->table('reward_batch')->where('id', $batchId)->update([
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
            'dibatalkan_oleh' => $userId,
        ]);
        $db->table('reward_pemenang')->where('batch_id', $batchId)->delete();
        $db->table('reward_event_pernah')->where('batch_id', $batchId)->delete();
        $db->transComplete();
        if ($db->transStatus() === false) {
            return ['ok' => false, 'message' => 'Gagal membatalkan.'];
        }
        (new AuditService())->log('reward_batal_batch', 'reward_batch', $batchId, $batch, null, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailEvent($eventId, null, true)];
    }

    public function resetSiklus(int $eventId, int $userId): array
    {
        $this->ensureTables();
        $db = \Config\Database::connect();
        $event = $db->table('reward_event')->where('id', $eventId)->get()->getRowArray();
        if (!$event) {
            return ['ok' => false, 'message' => 'Event tidak ditemukan.'];
        }
        $aktif = $db->table('reward_pemenang')
            ->where('event_id', $eventId)
            ->where('status', 'tersedia')
            ->countAllResults();
        if ($aktif > 0) {
            return ['ok' => false, 'message' => 'Masih ada kupon menunggu klaim. Tunggu hangus atau batalkan batch dulu.'];
        }
        $db->table('reward_event_pernah')->where('event_id', $eventId)->delete();
        (new AuditService())->log('reward_reset_siklus', 'reward_event', $eventId, null, null, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailEvent($eventId, null, true)];
    }

    public function klaim(int $pemenangId, int $userId, int $keluargaId): array
    {
        $this->ensureTables();
        $this->prosesHangus();
        $db = \Config\Database::connect();
        $p = $db->table('reward_pemenang')->where('id', $pemenangId)->get()->getRowArray();
        if (!$p) {
            return ['ok' => false, 'message' => 'Kupon tidak ditemukan.'];
        }
        if ((int) $p['keluarga_id'] !== $keluargaId) {
            return ['ok' => false, 'message' => 'Kupon bukan untuk keluarga kamu.'];
        }
        if (($p['status'] ?? '') === 'hangus') {
            return ['ok' => false, 'message' => 'Kupon sudah hangus.'];
        }
        if (($p['status'] ?? '') === 'diklaim') {
            return ['ok' => false, 'message' => 'Kupon sudah diklaim.'];
        }
        if (strtotime($p['expired_at']) < time()) {
            $db->table('reward_pemenang')->where('id', $pemenangId)->update(['status' => 'hangus']);
            return ['ok' => false, 'message' => 'Kupon sudah hangus.'];
        }
        $db->table('reward_pemenang')->where('id', $pemenangId)->update([
            'status' => 'diklaim',
            'diklaim_pada' => date('Y-m-d H:i:s'),
            'diklaim_oleh' => $userId,
        ]);
        $event = $db->table('reward_event')->where('id', (int) $p['event_id'])->get()->getRowArray();
        $alamat = $this->alamatKeluarga((int) $p['keluarga_id']);
        $this->notifPengurus(
            'reward',
            'Hadiah diklaim',
            'Keluarga ' . $alamat . ' mengklaim hadiah "' . ($event['judul'] ?? 'Reward') . '". Siapkan serah terima di rumah RT.',
            '/reward/' . (int) $p['event_id']
        );
        (new AuditService())->log('reward_klaim', 'reward_pemenang', $pemenangId, $p, ['status' => 'diklaim'], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailEvent((int) $p['event_id'], $keluargaId, false)];
    }

    private function mapEventSummary(array $r): array
    {
        $id = (int) $r['id'];
        $db = \Config\Database::connect();
        $jumlahBatch = 0;
        $jumlahPemenang = 0;
        if ($db->tableExists('reward_batch')) {
            $jumlahBatch = $db->table('reward_batch')
                ->where('event_id', $id)
                ->where('dibatalkan_pada', null)
                ->countAllResults();
            $jumlahPemenang = $db->table('reward_pemenang')->where('event_id', $id)->countAllResults();
        }
        return [
            'id' => $id,
            'judul' => $r['judul'],
            'status' => $r['status'] ?? 'aktif',
            'created_at' => $r['created_at'] ?? null,
            'jumlah_batch' => $jumlahBatch,
            'jumlah_pemenang' => $jumlahPemenang,
            'pool_sisa' => $this->hitungPoolSisa($id),
        ];
    }

    private function listPemenangEvent(int $eventId, bool $pengurus): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('reward_pemenang p')
            ->select('p.*, b.diundi_pada, b.dibatalkan_pada')
            ->join('reward_batch b', 'b.id = p.batch_id', 'left')
            ->where('p.event_id', $eventId)
            ->orderBy('p.id', 'DESC')
            ->get()
            ->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $item = [
                'id' => (int) $r['id'],
                'batch_id' => (int) $r['batch_id'],
                'keluarga_id' => (int) $r['keluarga_id'],
                'alamat' => $this->alamatKeluarga((int) $r['keluarga_id']),
                'status' => $r['status'],
                'expired_at' => $r['expired_at'],
                'diklaim_pada' => $r['diklaim_pada'],
                'diundi_pada' => $r['diundi_pada'],
            ];
            if ($pengurus) {
                $item['bisa_batal'] = $this->batchBisaBatal((int) $r['batch_id']);
            }
            $out[] = $item;
        }
        return $out;
    }

    private function batchBisaBatal(int $batchId): bool
    {
        $db = \Config\Database::connect();
        $batch = $db->table('reward_batch')->where('id', $batchId)->get()->getRowArray();
        if (!$batch || !empty($batch['dibatalkan_pada'])) {
            return false;
        }
        $claimed = $db->table('reward_pemenang')
            ->where('batch_id', $batchId)
            ->where('status', 'diklaim')
            ->countAllResults();
        return $claimed === 0;
    }

    /** Kupon hero: tersedia ATAU diklaim, selama expired_at belum lewat (jendela 3 hari). */
    private function kuponAktifKeluarga(int $eventId, int $keluargaId): ?array
    {
        $db = \Config\Database::connect();
        $r = $db->table('reward_pemenang')
            ->where('event_id', $eventId)
            ->where('keluarga_id', $keluargaId)
            ->whereIn('status', ['tersedia', 'diklaim'])
            ->where('expired_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();
        if (!$r) {
            return null;
        }
        return [
            'id' => (int) $r['id'],
            'status' => $r['status'],
            'expired_at' => $r['expired_at'],
            'diklaim_pada' => $r['diklaim_pada'] ?? null,
            'alamat' => $this->alamatKeluarga($keluargaId),
        ];
    }

    private function hitungPoolSisa(int $eventId): int
    {
        return count($this->listPoolEligible($eventId));
    }

    private function listPoolEligible(int $eventId): array
    {
        $db = \Config\Database::connect();
        $pernah = $db->table('reward_event_pernah')
            ->select('keluarga_id')
            ->where('event_id', $eventId)
            ->get()
            ->getResultArray();
        $exclude = array_map(static fn ($r) => (int) $r['keluarga_id'], $pernah);
        $b = $db->table('keluarga')->select('id')->where('status', 'aktif');
        if ($exclude) {
            $b->whereNotIn('id', $exclude);
        }
        $rows = $b->get()->getResultArray();
        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    private function alamatKeluarga(int $keluargaId): string
    {
        $db = \Config\Database::connect();
        $r = $db->table('keluarga k')
            ->select('k.nomor, k.akhiran, b.nama as blok_nama')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('k.id', $keluargaId)
            ->get()
            ->getRowArray();
        if (!$r) {
            return 'Alamat #' . $keluargaId;
        }
        return trim(($r['blok_nama'] ?? '') . '-' . ($r['nomor'] ?? '') . ($r['akhiran'] ?? ''), '-');
    }

    private function notifKeluarga(int $keluargaId, string $jenis, string $judul, string $isi, string $tautan): void
    {
        $db = \Config\Database::connect();
        $users = $db->table('users')
            ->where('keluarga_id', $keluargaId)
            ->where('role', 'warga')
            ->get()
            ->getResultArray();
        $svc = new NotifikasiService();
        foreach ($users as $u) {
            $svc->kirim((int) $u['id'], $jenis, $judul, $isi, $tautan, true);
        }
    }

    private function notifPengurus(string $jenis, string $judul, string $isi, string $tautan): void
    {
        $db = \Config\Database::connect();
        $users = $db->table('users')
            ->whereIn('role', ['pengurus', 'ketua'])
            ->get()
            ->getResultArray();
        $svc = new NotifikasiService();
        foreach ($users as $u) {
            $svc->kirim((int) $u['id'], $jenis, $judul, $isi, $tautan, true);
        }
    }
}
