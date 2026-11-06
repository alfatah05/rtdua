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

        $bulan = (int) ($filter['bulan'] ?? 0);
        $tahun = (int) ($filter['tahun'] ?? 0);
        if ($bulan >= 1 && $bulan <= 12 && $tahun >= 2000 && $tahun <= 2100) {
            // Range eksplisit (lebih andal daripada LIKE)
            $start = sprintf('%04d-%02d-01 00:00:00', $tahun, $bulan);
            $endTs = strtotime(sprintf('%04d-%02d-01 +1 month', $tahun, $bulan));
            $end = date('Y-m-d H:i:s', $endTs);
            $q->where('a.waktu >=', $start)
                ->where('a.waktu <', $end);
        }
        $limit = min(300, max(1, (int) ($filter['limit'] ?? 200)));
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

        $sebelum = $r['sebelum'] ? json_decode($r['sebelum'], true) : null;
        $sesudah = $r['sesudah'] ? json_decode($r['sesudah'], true) : null;
        if (!is_array($sebelum)) {
            $sebelum = null;
        }
        if (!is_array($sesudah)) {
            $sesudah = null;
        }

        $subjek = $this->resolveSubjek($r, $sebelum, $sesudah);

        return [
            'id'            => (int) $r['id'],
            'waktu'         => $r['waktu'],
            'aksi'          => $r['aksi'],
            'objek'         => $r['objek'],
            'objek_id'      => $r['objek_id'],
            'pelaku'        => $pelaku,
            'jabatan'       => $r['jabatan'] ?? null,
            'role'          => $r['role'] ?? null,
            'sumber'        => $r['sumber'],
            'sebelum'       => $sebelum,
            'sesudah'       => $sesudah,
            // Identitas warga/keluarga terkait (kepala keluarga prioritas)
            'keluarga_id'   => $subjek['keluarga_id'],
            'subjek_nama'   => $subjek['nama'],
            'subjek_alamat'  => $subjek['alamat'],
        ];
    }

    /**
     * Ambil nama kepala keluarga + alamat dari audit (keluarga_id / objek).
     */
    private function resolveSubjek(array $r, ?array $sebelum, ?array $sesudah): array
    {
        $out = ['keluarga_id' => null, 'nama' => null, 'alamat' => null];

        $kid = 0;
        foreach ([$sesudah, $sebelum] as $blob) {
            if (!is_array($blob)) {
                continue;
            }
            foreach (['keluarga_id', 'keluargaId', 'id_keluarga'] as $k) {
                if (!empty($blob[$k]) && (int) $blob[$k] > 0) {
                    $kid = (int) $blob[$k];
                    break 2;
                }
            }
            // kadang nested
            if (!empty($blob['keluarga']['id'])) {
                $kid = (int) $blob['keluarga']['id'];
                break;
            }
        }

        $objek = strtolower((string) ($r['objek'] ?? ''));
        $objekId = (int) ($r['objek_id'] ?? 0);
        if ($kid < 1 && $objekId > 0) {
            if (in_array($objek, ['keluarga', 'warga_keluarga'], true)) {
                $kid = $objekId;
            } elseif (in_array($objek, ['pembayaran', 'pembayaran_permintaan', 'iuran_tagihan'], true)) {
                $kid = $this->keluargaIdDariObjek($objek, $objekId);
            }
        }

        // Nama/alamat yang sudah ada di JSON (prioritas nama)
        $namaJson = null;
        $alamatJson = null;
        foreach ([$sesudah, $sebelum] as $blob) {
            if (!is_array($blob)) {
                continue;
            }
            foreach (['nama_kepala', 'nama', 'nama_warga', 'kepala_keluarga'] as $k) {
                $v = trim((string) ($blob[$k] ?? ''));
                if ($v !== '' && !$this->looksLikeAlamat($v)) {
                    $namaJson = $v;
                    break;
                }
            }
            foreach (['alamat', 'blok_nomor', 'rumah'] as $k) {
                $v = trim((string) ($blob[$k] ?? ''));
                if ($v !== '') {
                    $alamatJson = $v;
                    break;
                }
            }
            if (!empty($blob['blok']) || !empty($blob['nomor'])) {
                $alamatJson = trim((string) ($blob['blok'] ?? '')) . '-' . trim((string) ($blob['nomor'] ?? '')) . trim((string) ($blob['akhiran'] ?? ''));
            }
        }

        if ($kid > 0) {
            $info = $this->infoKeluarga($kid);
            $out['keluarga_id'] = $kid;
            $out['nama'] = $info['nama'] ?: $namaJson;
            $out['alamat'] = $info['alamat'] ?: $alamatJson;
            return $out;
        }

        $out['nama'] = $namaJson;
        $out['alamat'] = $alamatJson;
        return $out;
    }

    private function keluargaIdDariObjek(string $objek, int $objekId): int
    {
        if ($objekId < 1) {
            return 0;
        }
        $db = \Config\Database::connect();
        try {
            if ($objek === 'pembayaran') {
                $row = $db->table('pembayaran')->select('keluarga_id')->where('id', $objekId)->get()->getRowArray();
                return (int) ($row['keluarga_id'] ?? 0);
            }
            if ($objek === 'pembayaran_permintaan') {
                $row = $db->table('pembayaran_permintaan')->select('keluarga_id')->where('id', $objekId)->get()->getRowArray();
                return (int) ($row['keluarga_id'] ?? 0);
            }
            if ($objek === 'iuran_tagihan') {
                $row = $db->table('iuran_tagihan')->select('keluarga_id')->where('id', $objekId)->get()->getRowArray();
                return (int) ($row['keluarga_id'] ?? 0);
            }
        } catch (\Throwable $e) {
        }
        return 0;
    }

    private function infoKeluarga(int $keluargaId): array
    {
        $db = \Config\Database::connect();
        $alamat = '';
        $nama = '';
        try {
            $k = $db->table('keluarga k')
                ->select('k.nomor, k.akhiran, b.nama as blok')
                ->join('blok b', 'b.id = k.blok_id', 'left')
                ->where('k.id', $keluargaId)
                ->get()->getRowArray();
            if ($k) {
                $alamat = trim((string) ($k['blok'] ?? '')) . '-' . trim((string) ($k['nomor'] ?? '')) . trim((string) ($k['akhiran'] ?? ''));
                if ($alamat === '-') {
                    $alamat = '';
                }
            }
        } catch (\Throwable $e) {
        }
        try {
            $w = $db->table('warga')
                ->select('nama, hubungan')
                ->where('keluarga_id', $keluargaId)
                ->where('status', 'aktif')
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
            foreach ($w as $row) {
                $hub = strtolower((string) ($row['hubungan'] ?? ''));
                if (str_contains($hub, 'kepala')) {
                    $nama = trim((string) ($row['nama'] ?? ''));
                    break;
                }
            }
            if ($nama === '' && $w) {
                $nama = trim((string) ($w[0]['nama'] ?? ''));
            }
        } catch (\Throwable $e) {
        }
        return ['nama' => $nama !== '' ? $nama : null, 'alamat' => $alamat !== '' ? $alamat : null];
    }

    private function looksLikeAlamat(string $v): bool
    {
        // pola "A-12", "Blok A-1", dll.
        return (bool) preg_match('/^[A-Za-z]{1,4}-?\d+[A-Za-z]?$/', $v)
            || (bool) preg_match('/\bblok\b/i', $v);
    }
}
