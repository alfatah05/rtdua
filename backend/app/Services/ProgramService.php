<?php

namespace App\Services;

class ProgramService
{
    public function list(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('program_rt')->orderBy('id', 'DESC')->get()->getResultArray();
        return array_map([$this, 'map'], $rows);
    }

    public function detail(int $id): ?array
    {
        $db = \Config\Database::connect();
        $r = $db->table('program_rt')->where('id', $id)->get()->getRowArray();
        return $r ? $this->map($r) : null;
    }

    public function buat(array $data, int $userId): array
    {
        $judul = trim((string) ($data['judul'] ?? ''));
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Judul wajib.'];
        }
        $status = $data['status'] ?? 'direncanakan';
        if (!in_array($status, ['direncanakan', 'berjalan', 'selesai'], true)) {
            $status = 'direncanakan';
        }
        $isi = $this->bersihkanHtml((string) ($data['isi_html'] ?? ''));
        $db = \Config\Database::connect();
        $db->table('program_rt')->insert([
            'judul'      => mb_substr($judul, 0, 255),
            'banner'     => $data['banner'] ?? null,
            'isi_html'   => $isi,
            'status'     => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        (new AuditService())->log('tambah_program', 'program_rt', $id, null, ['judul' => $judul], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function ubah(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('program_rt')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $update = [];
        if (isset($data['judul'])) {
            $update['judul'] = mb_substr(trim((string) $data['judul']), 0, 255);
        }
        if (array_key_exists('banner', $data)) {
            $update['banner'] = $data['banner'];
        }
        if (array_key_exists('isi_html', $data)) {
            $update['isi_html'] = $this->bersihkanHtml((string) $data['isi_html']);
        }
        if (isset($data['status']) && in_array($data['status'], ['direncanakan', 'berjalan', 'selesai'], true)) {
            $update['status'] = $data['status'];
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('program_rt')->where('id', $id)->update($update);
        (new AuditService())->log('ubah_program', 'program_rt', $id, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detail($id)];
    }

    public function hapus(int $id, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('program_rt')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $db->table('program_rt')->where('id', $id)->delete();
        (new AuditService())->log('hapus_program', 'program_rt', $id, $before, null, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    /** HTML aman sederhana: izinkan tag dasar. */
    private function bersihkanHtml(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><ul><ol><li><h2><h3><h4><table><thead><tbody><tr><th><td><a><img>';
        return strip_tags($html, $allowed);
    }

    private function map(array $r): array
    {
        return [
            'id'       => (int) $r['id'],
            'judul'    => $r['judul'],
            'banner'   => $r['banner'],
            'isi_html' => $r['isi_html'],
            'status'   => $r['status'],
            'created_at' => $r['created_at'],
            'updated_at' => $r['updated_at'],
        ];
    }
}
