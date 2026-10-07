<?php

namespace App\Services;

class PengumumanService
{
    public function list(bool $hanyaBeranda = false): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('pengumuman')->orderBy('pin', 'DESC')->orderBy('diterbitkan_pada', 'DESC');
        if ($hanyaBeranda) {
            $q->limit(20);
        }
        $rows = $q->get()->getResultArray();
        $out = array_map([$this, 'map'], $rows);
        if ($hanyaBeranda) {
            // max 3: pin dulu lalu terbaru
            $pin = array_values(array_filter($out, static fn ($r) => $r['pin']));
            $lain = array_values(array_filter($out, static fn ($r) => !$r['pin']));
            return array_slice(array_merge($pin, $lain), 0, 3);
        }
        return $out;
    }

    public function detail(int $id): ?array
    {
        $db = \Config\Database::connect();
        $r = $db->table('pengumuman')->where('id', $id)->get()->getRowArray();
        return $r ? $this->map($r) : null;
    }

    public function buat(array $data, int $userId): array
    {
        $judul = trim((string) ($data['judul'] ?? ''));
        $isi = trim((string) ($data['isi'] ?? ''));
        if ($judul === '' || $isi === '') {
            return ['ok' => false, 'message' => 'Judul dan isi wajib.'];
        }
        $pin = !empty($data['pin']) ? 1 : 0;
        if ($pin && $this->jumlahPin() >= 2) {
            return ['ok' => false, 'message' => 'Pin maksimal 2. Lepas salah satu dulu.'];
        }
        $db = \Config\Database::connect();
        $db->table('pengumuman')->insert([
            'judul'            => mb_substr($judul, 0, 255),
            'isi'              => $isi,
            'lampiran_file'    => $data['lampiran_file'] ?? null,
            'lampiran_tipe'    => $data['lampiran_tipe'] ?? null,
            'pin'              => $pin,
            'diterbitkan_pada' => date('Y-m-d H:i:s'),
            'dibuat_oleh'      => $userId,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();

        // Push ke semua warga (satu notifikasi per akun warga)
        $this->kirimNotifSemua($judul, $id);

        (new AuditService())->log('buat_pengumuman', 'pengumuman', $id, null, ['judul' => $judul, 'pin' => $pin], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function ubah(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $old = $db->table('pengumuman')->where('id', $id)->get()->getRowArray();
        if (!$old) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $update = ['updated_at' => date('Y-m-d H:i:s'), 'diubah_oleh' => $userId];
        if (array_key_exists('judul', $data)) {
            $j = trim((string) $data['judul']);
            if ($j === '') {
                return ['ok' => false, 'message' => 'Judul wajib.'];
            }
            $update['judul'] = mb_substr($j, 0, 255);
        }
        if (array_key_exists('isi', $data)) {
            $i = trim((string) $data['isi']);
            if ($i === '') {
                return ['ok' => false, 'message' => 'Isi wajib.'];
            }
            $update['isi'] = $i;
        }
        if (array_key_exists('pin', $data)) {
            $pin = !empty($data['pin']) ? 1 : 0;
            if ($pin && !(int) $old['pin'] && $this->jumlahPin() >= 2) {
                return ['ok' => false, 'message' => 'Pin maksimal 2.'];
            }
            $update['pin'] = $pin;
        }
        if (array_key_exists('lampiran_file', $data)) {
            $update['lampiran_file'] = $data['lampiran_file'];
            $update['lampiran_tipe'] = $data['lampiran_tipe'] ?? null;
        }
        // Edit naik ke atas
        $update['diterbitkan_pada'] = date('Y-m-d H:i:s');

        $db->table('pengumuman')->where('id', $id)->update($update);

        if (!empty($data['kirim_notif_lagi'])) {
            $judul = $update['judul'] ?? $old['judul'];
            $this->kirimNotifSemua($judul, $id);
        }

        (new AuditService())->log('ubah_pengumuman', 'pengumuman', $id, $old, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function hapus(int $id, int $userId): array
    {
        $db = \Config\Database::connect();
        $old = $db->table('pengumuman')->where('id', $id)->get()->getRowArray();
        if (!$old) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $db->table('pengumuman')->where('id', $id)->delete();
        (new AuditService())->log('hapus_pengumuman', 'pengumuman', $id, $old, null, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    private function jumlahPin(): int
    {
        $db = \Config\Database::connect();
        return (int) $db->table('pengumuman')->where('pin', 1)->countAllResults();
    }

    private function kirimNotifSemua(string $judul, int $id): void
    {
        $n = new NotifikasiService();
        $db = \Config\Database::connect();
        $users = $db->table('users')->where('role', 'warga')->where('aktif', 1)->get()->getResultArray();
        foreach ($users as $u) {
            $n->keUser((int) $u['id'], 'pengumuman', 'Pengumuman baru', $judul, '/pengumuman/' . $id);
        }
    }

    private function map(array $r): array
    {
        return [
            'id'               => (int) $r['id'],
            'judul'            => $r['judul'],
            'isi'              => $r['isi'],
            'lampiran_file'    => $r['lampiran_file'],
            'lampiran_tipe'    => $r['lampiran_tipe'],
            'pin'              => (bool) $r['pin'],
            'diterbitkan_pada' => $r['diterbitkan_pada'],
            'created_at'       => $r['created_at'],
        ];
    }
}
