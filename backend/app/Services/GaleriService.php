<?php

namespace App\Services;

class GaleriService
{
    public function listAlbum(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('galeri_album')->orderBy('tanggal_kegiatan', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        $out = [];
        $hasBanner = $db->fieldExists('banner', 'galeri_album');
        foreach ($rows as $r) {
            $n = (int) $db->table('galeri_foto')->where('album_id', $r['id'])->countAllResults();
            $banner = $hasBanner ? ($r['banner'] ?? null) : null;
            // fallback: foto pertama album sebagai cover
            if (!$banner) {
                $first = $db->table('galeri_foto')->where('album_id', $r['id'])->orderBy('id', 'ASC')->get()->getRowArray();
                if ($first) {
                    $banner = $first['thumb'] ?: $first['file'];
                }
            }
            $out[] = [
                'id'               => (int) $r['id'],
                'judul'            => $r['judul'],
                'banner'           => $banner,
                'tanggal_kegiatan' => $r['tanggal_kegiatan'],
                'jumlah_foto'      => $n,
                'created_at'       => $r['created_at'],
            ];
        }
        return $out;
    }

    public function detailAlbum(int $id): ?array
    {
        $db = \Config\Database::connect();
        $a = $db->table('galeri_album')->where('id', $id)->get()->getRowArray();
        if (!$a) {
            return null;
        }
        $fotos = $db->table('galeri_foto')->where('album_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        $hasBanner = $db->fieldExists('banner', 'galeri_album');
        return [
            'id'               => (int) $a['id'],
            'judul'            => $a['judul'],
            'banner'           => $hasBanner ? ($a['banner'] ?? null) : null,
            'tanggal_kegiatan' => $a['tanggal_kegiatan'],
            'foto'             => array_map(static function ($f) {
                return [
                    'id'    => (int) $f['id'],
                    'file'  => $f['file'],
                    'thumb' => $f['thumb'],
                    'lebar' => $f['lebar'] ? (int) $f['lebar'] : null,
                    'tinggi'=> $f['tinggi'] ? (int) $f['tinggi'] : null,
                ];
            }, $fotos),
        ];
    }

    public function buatAlbum(array $data, int $userId): array
    {
        $judul = trim((string) ($data['judul'] ?? ''));
        if ($judul === '') {
            return ['ok' => false, 'message' => 'Judul album wajib.'];
        }
        $db = \Config\Database::connect();
        $row = [
            'judul'            => mb_substr($judul, 0, 255),
            'tanggal_kegiatan' => $data['tanggal_kegiatan'] ?? null,
            'created_at'       => date('Y-m-d H:i:s'),
        ];
        if ($db->fieldExists('banner', 'galeri_album') && array_key_exists('banner', $data)) {
            $row['banner'] = $data['banner'] ?: null;
        }
        $db->table('galeri_album')->insert($row);
        $id = (int) $db->insertID();
        (new AuditService())->log('tambah_album', 'galeri_album', $id, null, ['judul' => $judul], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailAlbum($id)];
    }

    public function ubahAlbum(int $id, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('galeri_album')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $update = [];
        if (isset($data['judul'])) {
            $j = trim((string) $data['judul']);
            if ($j !== '') {
                $update['judul'] = mb_substr($j, 0, 255);
            }
        }
        if (array_key_exists('tanggal_kegiatan', $data)) {
            $update['tanggal_kegiatan'] = $data['tanggal_kegiatan'] ?: null;
        }
        if ($db->fieldExists('banner', 'galeri_album') && array_key_exists('banner', $data)) {
            $update['banner'] = $data['banner'] ?: null;
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan.'];
        }
        $db->table('galeri_album')->where('id', $id)->update($update);
        (new AuditService())->log('ubah_album', 'galeri_album', $id, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailAlbum($id)];
    }

    public function hapusAlbum(int $id, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('galeri_album')->where('id', $id)->get()->getRowArray();
        if (!$before) {
            return ['ok' => false, 'message' => 'Tidak ditemukan.'];
        }
        $fotos = $db->table('galeri_foto')->where('album_id', $id)->get()->getResultArray();
        foreach ($fotos as $f) {
            $this->hapusFileFisik($f['file'] ?? null);
            $this->hapusFileFisik($f['thumb'] ?? null);
        }
        if (!empty($before['banner'])) {
            $this->hapusFileFisik($before['banner']);
        }
        $db->table('galeri_foto')->where('album_id', $id)->delete();
        $db->table('galeri_album')->where('id', $id)->delete();
        (new AuditService())->log('hapus_album', 'galeri_album', $id, $before, null, 'pengguna', true, $userId);
        return ['ok' => true];
    }

    /** path relatif di data['file'] setelah upload terpisah. */
    public function tambahFoto(int $albumId, array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $a = $db->table('galeri_album')->where('id', $albumId)->get()->getRowArray();
        if (!$a) {
            return ['ok' => false, 'message' => 'Album tidak ditemukan.'];
        }
        $file = $data['file'] ?? null;
        if (!$file) {
            return ['ok' => false, 'message' => 'File wajib.'];
        }
        $db->table('galeri_foto')->insert([
            'album_id'      => $albumId,
            'file'          => $file,
            'thumb'         => $data['thumb'] ?? null,
            'lebar'         => $data['lebar'] ?? null,
            'tinggi'        => $data['tinggi'] ?? null,
            'diunggah_oleh' => $userId,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        return ['ok' => true, 'data' => ['id' => $id]];
    }

    public function hapusFoto(array $ids, int $userId): array
    {
        $db = \Config\Database::connect();
        $ids = array_map('intval', $ids);
        if (!$ids) {
            return ['ok' => false, 'message' => 'Pilih foto.'];
        }
        $rows = $db->table('galeri_foto')->whereIn('id', $ids)->get()->getResultArray();
        foreach ($rows as $f) {
            $this->hapusFileFisik($f['file'] ?? null);
            $this->hapusFileFisik($f['thumb'] ?? null);
            $db->table('galeri_foto')->where('id', $f['id'])->delete();
        }
        (new AuditService())->log('hapus_foto_galeri', 'galeri_foto', null, null, ['ids' => $ids], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['hapus' => count($rows)]];
    }

    private function hapusFileFisik(?string $rel): void
    {
        if (!$rel) {
            return;
        }
        $path = WRITEPATH . ltrim(str_replace(['..', '\\'], '', $rel), '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
