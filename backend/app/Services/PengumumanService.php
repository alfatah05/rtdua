<?php

namespace App\Services;

class PengumumanService
{
    public function list(bool $hanyaBeranda = false): array
    {
        $db = \Config\Database::connect();
        // Tanpa pin: urut terbaru. Beranda = 3 terbaru (nanti diganti waktu tampil).
        $q = $db->table('pengumuman')->orderBy('diterbitkan_pada', 'DESC');
        if ($hanyaBeranda) {
            $q->limit(3);
        }
        $rows = $q->get()->getResultArray();
        return array_map([$this, 'map'], $rows);
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
        $db = \Config\Database::connect();
        $db->table('pengumuman')->insert([
            'judul'            => mb_substr($judul, 0, 255),
            'isi'              => $isi,
            'lampiran_file'    => $data['lampiran_file'] ?? null,
            'lampiran_tipe'    => $data['lampiran_tipe'] ?? null,
            'pin'              => 0,
            'diterbitkan_pada' => date('Y-m-d H:i:s'),
            'dibuat_oleh'      => $userId,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();

        $this->kirimNotifSemua($judul, $id);

        (new AuditService())->log('buat_pengumuman', 'pengumuman', $id, null, ['judul' => $judul], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function ubah(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $old = $db->table('pengumuman')->where('id', $id)->get()->getRowArray();
        if (!$old) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $update = [];
        if (array_key_exists('judul', $data)) {
            $judul = trim((string) $data['judul']);
            if ($judul === '') {
                return ['ok' => false, 'message' => 'Judul wajib.'];
            }
            $update['judul'] = mb_substr($judul, 0, 255);
        }
        if (array_key_exists('isi', $data)) {
            $isi = trim((string) $data['isi']);
            if ($isi === '') {
                return ['ok' => false, 'message' => 'Isi wajib.'];
            }
            $update['isi'] = $isi;
        }
        // Pin dihapus dari sistem — selalu 0
        $update['pin'] = 0;
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

    private function kirimNotifSemua(string $judul, int $id): void
    {
        $n = new NotifikasiService();
        $db = \Config\Database::connect();
        $users = $db->table('users')->where('role', 'warga')->where('aktif', 1)->get()->getResultArray();
        foreach ($users as $u) {
            $n->kirim((int) $u['id'], 'pengumuman', 'Pengumuman baru', $judul, '/pengumuman/' . $id);
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
            'pin'              => false,
            'diterbitkan_pada' => $r['diterbitkan_pada'],
            'created_at'       => $r['created_at'],
        ];
    }
}
