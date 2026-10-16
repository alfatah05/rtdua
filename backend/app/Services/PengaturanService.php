<?php

namespace App\Services;

class PengaturanService
{
    public function get(): array
    {
        $db = \Config\Database::connect();
        $row = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        if (!$row) {
            return [];
        }
        $blok = $db->table('blok')->where('aktif', 1)->orderBy('nama', 'ASC')->get()->getResultArray();
        $logoLogin = $row['logo_login'] ?? 'rt';
        if ($logoLogin !== 'desa' && $logoLogin !== 'rt') {
            $logoLogin = 'rt';
        }
        return [
            'nama_app'         => $row['nama_app'] ?? 'rtdua',
            'nama_rt'          => $row['nama_rt'] ?? '',
            'nama_perumahan'   => $row['nama_perumahan'] ?? '',
            'logo_rt'          => $row['logo_rt'] ?? null,
            'logo_desa'        => $row['logo_desa'] ?? null,
            'logo_login'       => $logoLogin,
            'akses_warga'      => (bool) ($row['akses_warga'] ?? 0),
            'nominal_kas'      => (int) ($row['nominal_kas'] ?? 0),
            'denda_ronda'      => (int) ($row['denda_ronda'] ?? 0),
            'jam_ronda_mulai'  => substr((string) ($row['jam_ronda_mulai'] ?? '21:00:00'), 0, 5),
            'jam_ronda_selesai'=> substr((string) ($row['jam_ronda_selesai'] ?? '00:00:00'), 0, 5),
            'setup_selesai'    => (bool) ($row['setup_selesai'] ?? 0),
            'blok'             => array_map(static function ($b) {
                return ['id' => (int) $b['id'], 'nama' => $b['nama'], 'aktif' => (bool) $b['aktif']];
            }, $blok),
        ];
    }

    public function getPublik(): array
    {
        $g = $this->get();
        return [
            'nama_app'       => $g['nama_app'] ?? 'rtdua',
            'nama_rt'        => $g['nama_rt'] ?? '',
            'nama_perumahan' => $g['nama_perumahan'] ?? '',
            'logo_rt'        => $g['logo_rt'] ?? null,
            'logo_desa'      => $g['logo_desa'] ?? null,
            'logo_login'     => $g['logo_login'] ?? 'rt',
            'akses_warga'    => $g['akses_warga'] ?? false,
        ];
    }

    /**
     * Data branding + blok untuk halaman login (tanpa auth).
     */
    public function getLoginBranding(): array
    {
        $p = $this->getPublik();
        $blok = $this->listBlok(false);
        return [
            'nama_app'    => $p['nama_app'],
            'nama_rt'     => $p['nama_rt'],
            'logo_rt'     => $p['logo_rt'],
            'logo_desa'   => $p['logo_desa'],
            'logo_login'  => $p['logo_login'],
            'blok'        => array_map(static function ($b) {
                return ['id' => $b['id'], 'nama' => $b['nama']];
            }, $blok),
        ];
    }

    public function updateAplikasi(array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $update = [];
        foreach (['nama_app', 'nama_rt', 'nama_perumahan'] as $k) {
            if (array_key_exists($k, $data)) {
                $update[$k] = trim((string) $data[$k]);
            }
        }
        if (array_key_exists('akses_warga', $data)) {
            $update['akses_warga'] = $data['akses_warga'] ? 1 : 0;
        }
        foreach (['logo_rt', 'logo_desa'] as $k) {
            if (array_key_exists($k, $data)) {
                $v = $data[$k];
                if ($v === null || $v === '') {
                    $update[$k] = null;
                } else {
                    $v = preg_replace('#[^a-zA-Z0-9._/-]#', '', (string) $v);
                    if (preg_match('#^logo/[a-zA-Z0-9._-]+$#', $v)) {
                        $update[$k] = $v;
                    }
                }
            }
        }
        if (array_key_exists('logo_login', $data)) {
            $v = strtolower(trim((string) $data['logo_login']));
            if ($v === 'rt' || $v === 'desa') {
                if ($this->hasLogoLoginColumn($db)) {
                    $update['logo_login'] = $v;
                }
            }
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada data diubah.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        try {
            $db->table('pengaturan')->where('id', 1)->update($update);
        } catch (\Throwable $e) {
            if (isset($update['logo_login'])) {
                unset($update['logo_login']);
                if ($update === [] || (count($update) === 1 && isset($update['updated_at']))) {
                    return ['ok' => false, 'message' => 'Kolom logo_login belum ada. Jalankan sql/006_logo_login.sql di phpMyAdmin.'];
                }
                $db->table('pengaturan')->where('id', 1)->update($update);
            } else {
                throw $e;
            }
        }
        (new AuditService())->log('ubah_pengaturan_aplikasi', 'pengaturan', 1, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->get()];
    }

    public function updateWarga(array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $before = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $update = [];
        if (array_key_exists('nominal_kas', $data)) {
            $update['nominal_kas'] = max(0, (int) $data['nominal_kas']);
        }
        if (array_key_exists('denda_ronda', $data)) {
            $update['denda_ronda'] = max(0, (int) $data['denda_ronda']);
        }
        if (array_key_exists('jam_ronda_mulai', $data)) {
            $update['jam_ronda_mulai'] = $this->normJam($data['jam_ronda_mulai']);
        }
        if (array_key_exists('jam_ronda_selesai', $data)) {
            $update['jam_ronda_selesai'] = $this->normJam($data['jam_ronda_selesai']);
        }
        if ($update === []) {
            return ['ok' => false, 'message' => 'Tidak ada data diubah.'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $db->table('pengaturan')->where('id', 1)->update($update);
        (new AuditService())->log('ubah_pengaturan_warga', 'pengaturan', 1, $before, $update, 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->get()];
    }

    public function listBlok(bool $semua = false): array
    {
        $db = \Config\Database::connect();
        $q = $db->table('blok')->orderBy('nama', 'ASC');
        if (!$semua) {
            $q->where('aktif', 1);
        }
        return array_map(static function ($b) {
            return ['id' => (int) $b['id'], 'nama' => $b['nama'], 'aktif' => (bool) $b['aktif']];
        }, $q->get()->getResultArray());
    }

    public function tambahBlok(string $nama, int $userId): array
    {
        $nama = strtoupper(trim($nama));
        if ($nama === '') {
            return ['ok' => false, 'message' => 'Nama blok wajib.'];
        }
        $db = \Config\Database::connect();
        if ($db->table('blok')->where('nama', $nama)->countAllResults() > 0) {
            return ['ok' => false, 'message' => 'Blok sudah ada.'];
        }
        $db->table('blok')->insert(['nama' => $nama, 'aktif' => 1]);
        $id = (int) $db->insertID();
        (new AuditService())->log('tambah_blok', 'blok', $id, null, ['nama' => $nama], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id, 'nama' => $nama, 'aktif' => true]];
    }

    private function hasLogoLoginColumn($db): bool
    {
        try {
            $fields = $db->getFieldNames('pengaturan');
            return in_array('logo_login', $fields, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normJam($v): string
    {
        $s = trim((string) $v);
        if (preg_match('/^\d{2}:\d{2}/', $s)) {
            return substr($s, 0, 5) . ':00';
        }
        return '21:00:00';
    }
}
