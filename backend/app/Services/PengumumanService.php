<?php

namespace App\Services;

class PengumumanService
{
    private static bool $schemaOk = false;

    private function ensureSchema(): void
    {
        if (self::$schemaOk) {
            return;
        }
        $db = \Config\Database::connect();
        try {
            $cols = $db->query('SHOW COLUMNS FROM pengumuman')->getResultArray();
            $names = array_map(static fn ($c) => $c['Field'] ?? $c['field'] ?? '', $cols);
            if (!in_array('tampil_mulai', $names, true)) {
                $db->query('ALTER TABLE pengumuman ADD COLUMN tampil_mulai DATETIME NULL AFTER pin');
            }
            if (!in_array('tampil_selesai', $names, true)) {
                $db->query('ALTER TABLE pengumuman ADD COLUMN tampil_selesai DATETIME NULL AFTER tampil_mulai');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Pengumuman ensureSchema: ' . $e->getMessage());
        }
        self::$schemaOk = true;
    }

    /** Normalisasi input datetime dari form (ISO / datetime-local). */
    private function parseDt(?string $s): ?string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return null;
        }
        $s = str_replace('T', ' ', $s);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $s)) {
            $s .= ':00';
        }
        $ts = strtotime($s);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    public function list(bool $hanyaBeranda = false): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $q = $db->table('pengumuman')->orderBy('diterbitkan_pada', 'DESC');

        if ($hanyaBeranda) {
            // Ambil lebih banyak lalu filter di PHP (aman untuk NULL + shared hosting)
            $q->limit(30);
        }

        $rows = $q->get()->getResultArray();
        $out = array_map([$this, 'map'], $rows);
        if ($hanyaBeranda) {
            $now = time();
            $out = array_values(array_filter($out, static function ($r) use ($now) {
                $mulai = $r['tampil_mulai'] ?? null;
                $selesai = $r['tampil_selesai'] ?? null;
                if ($mulai) {
                    $t0 = strtotime(str_replace('T', ' ', (string) $mulai));
                    if ($t0 !== false && $now < $t0) {
                        return false;
                    }
                }
                if ($selesai) {
                    $t1 = strtotime(str_replace('T', ' ', (string) $selesai));
                    if ($t1 !== false && $now > $t1) {
                        return false;
                    }
                }
                return true;
            }));
            return array_slice($out, 0, 3);
        }
        return $out;
    }

    public function detail(int $id): ?array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();
        $r = $db->table('pengumuman')->where('id', $id)->get()->getRowArray();
        return $r ? $this->map($r) : null;
    }

    public function buat(array $data, int $userId): array
    {
        $this->ensureSchema();
        $judul = trim((string) ($data['judul'] ?? ''));
        $isi = trim((string) ($data['isi'] ?? ''));
        if ($judul === '' || $isi === '') {
            return ['ok' => false, 'message' => 'Judul dan isi wajib.'];
        }

        $mulai = $this->parseDt($data['tampil_mulai'] ?? null) ?? date('Y-m-d H:i:s');
        $selesai = $this->parseDt($data['tampil_selesai'] ?? null);
        if ($selesai === null) {
            $selesai = date('Y-m-d H:i:s', strtotime($mulai . ' +7 days'));
        }
        if (strtotime($selesai) < strtotime($mulai)) {
            return ['ok' => false, 'message' => 'Waktu selesai tampil harus setelah waktu mulai.'];
        }

        $db = \Config\Database::connect();
        $db->table('pengumuman')->insert([
            'judul'            => mb_substr($judul, 0, 255),
            'isi'              => $isi,
            'lampiran_file'    => $data['lampiran_file'] ?? null,
            'lampiran_tipe'    => $data['lampiran_tipe'] ?? null,
            'pin'              => 0,
            'tampil_mulai'     => $mulai,
            'tampil_selesai'   => $selesai,
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
        $this->ensureSchema();
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
        $update['pin'] = 0;

        if (array_key_exists('tampil_mulai', $data) || array_key_exists('tampil_selesai', $data)) {
            $mulai = array_key_exists('tampil_mulai', $data)
                ? ($this->parseDt($data['tampil_mulai']) ?? $old['tampil_mulai'] ?? date('Y-m-d H:i:s'))
                : ($old['tampil_mulai'] ?? date('Y-m-d H:i:s'));
            $selesai = array_key_exists('tampil_selesai', $data)
                ? ($this->parseDt($data['tampil_selesai']) ?? $old['tampil_selesai'])
                : ($old['tampil_selesai'] ?? null);
            if ($selesai && strtotime((string) $selesai) < strtotime((string) $mulai)) {
                return ['ok' => false, 'message' => 'Waktu selesai tampil harus setelah waktu mulai.'];
            }
            $update['tampil_mulai'] = $mulai;
            $update['tampil_selesai'] = $selesai;
        }

        if (array_key_exists('lampiran_file', $data)) {
            $update['lampiran_file'] = $data['lampiran_file'];
            $update['lampiran_tipe'] = $data['lampiran_tipe'] ?? null;
        }

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
            'tampil_mulai'     => $r['tampil_mulai'] ?? null,
            'tampil_selesai'   => $r['tampil_selesai'] ?? null,
            'diterbitkan_pada' => $r['diterbitkan_pada'],
            'created_at'       => $r['created_at'],
        ];
    }
}
