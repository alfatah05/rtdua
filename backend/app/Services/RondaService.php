<?php

namespace App\Services;

class RondaService
{
    public function listJadwalTetap(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $kel = $db->table('ronda_jadwal_tetap_keluarga j')
                ->select('j.keluarga_id, k.nomor, k.akhiran, b.nama as blok')
                ->join('keluarga k', 'k.id = j.keluarga_id')
                ->join('blok b', 'b.id = k.blok_id')
                ->where('j.jadwal_id', $r['id'])
                ->get()->getResultArray();
            $out[] = [
                'id' => (int) $r['id'],
                'hari' => (int) $r['hari'],
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
                'keluarga' => array_map(static function ($k) {
                    return [
                        'keluarga_id' => (int) $k['keluarga_id'],
                        'alamat' => $k['blok'] . '-' . $k['nomor'] . ($k['akhiran'] ?? ''),
                    ];
                }, $kel),
            ];
        }
        return $out;
    }

    public function simpanJadwalTetap(array $data, int $userId): array
    {
        $hari = (int) ($data['hari'] ?? -1);
        if ($hari < 0 || $hari > 6) {
            return ['ok' => false, 'message' => 'Hari 0–6 (Minggu–Sabtu).'];
        }
        $db = \Config\Database::connect();
        $jamM = $data['jam_mulai'] ?? '21:00:00';
        $jamS = $data['jam_selesai'] ?? '00:00:00';
        $keluargaIds = array_map('intval', $data['keluarga_ids'] ?? []);
        $keluargaIds = array_values(array_filter($keluargaIds, static fn ($x) => $x > 0));
        $hapus = !empty($data['hapus']) || (isset($data['aktif']) && !$data['aktif']);
        $existing = $db->table('ronda_jadwal_tetap')->where('hari', $hari)->get()->getRowArray();

        if ($hapus) {
            if ($existing) {
                $id = (int) $existing['id'];
                $db->table('ronda_jadwal_tetap_keluarga')->where('jadwal_id', $id)->delete();
                $db->table('ronda_jadwal_tetap')->where('id', $id)->delete();
            }
            return ['ok' => true, 'data' => ['id' => null, 'deleted' => true, 'hari' => $hari]];
        }

        if ($existing) {
            $id = (int) $existing['id'];
            $db->table('ronda_jadwal_tetap')->where('id', $id)->update(['jam_mulai' => $jamM, 'jam_selesai' => $jamS]);
            $db->table('ronda_jadwal_tetap_keluarga')->where('jadwal_id', $id)->delete();
        } else {
            $db->table('ronda_jadwal_tetap')->insert(['hari' => $hari, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS]);
            $id = (int) $db->insertID();
        }
        foreach ($keluargaIds as $kid) {
            $db->table('ronda_jadwal_tetap_keluarga')->insert(['jadwal_id' => $id, 'keluarga_id' => $kid]);
        }
        try {
            (new AuditService())->log('simpan_jadwal_tetap', 'ronda_jadwal_tetap', $id, null, ['hari' => $hari], 'pengguna', true, $userId);
        } catch (\Throwable $e) {}
        return ['ok' => true, 'data' => ['id' => $id, 'hari' => $hari]];
    }

    public function listJadwalKhusus(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_jadwal_khusus')->orderBy('tanggal', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $kel = $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', $r['id'])->get()->getResultArray();
            $out[] = [
                'id' => (int) $r['id'],
                'tanggal' => $r['tanggal'],
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
                'keterangan' => $r['keterangan'],
                'keluarga_ids' => array_map(static fn ($k) => (int) $k['keluarga_id'], $kel),
            ];
        }
        return $out;
    }

    public function simpanJadwalKhusus(array $data, int $userId): array
    {
        $tanggal = $data['tanggal'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return ['ok' => false, 'message' => 'Tanggal wajib (YYYY-MM-DD).'];
        }
        if ($tanggal < date('Y-m-d')) {
            return ['ok' => false, 'message' => 'Tanggal tidak boleh di masa lalu.'];
        }
        $db = \Config\Database::connect();
        $jamM = $data['jam_mulai'] ?? '21:00:00';
        $jamS = $data['jam_selesai'] ?? '00:00:00';
        $ket = $data['keterangan'] ?? null;
        $keluargaIds = array_map('intval', $data['keluarga_ids'] ?? []);
        $keluargaIds = array_values(array_filter($keluargaIds, static fn ($x) => $x > 0));
        $hapus = !empty($data['hapus']);

        $existing = $db->table('ronda_jadwal_khusus')->where('tanggal', $tanggal)->get()->getRowArray();

        if ($hapus) {
            if ($existing) {
                $id = (int) $existing['id'];
                $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', $id)->delete();
                $db->table('ronda_jadwal_khusus')->where('id', $id)->delete();
                $malam = $db->table('ronda_malam')->where('tanggal', $tanggal)->where('sumber', 'khusus')->get()->getRowArray();
                if ($malam) {
                    $mid = (int) $malam['id'];
                    if ($db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->countAllResults() === 0) {
                        $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->delete();
                        $db->table('ronda_absen')->where('malam_id', $mid)->delete();
                        $db->table('ronda_malam')->where('id', $mid)->delete();
                    }
                }
            }
            return ['ok' => true, 'data' => ['deleted' => true, 'tanggal' => $tanggal]];
        }

        if ($existing) {
            $id = (int) $existing['id'];
            $db->table('ronda_jadwal_khusus')->where('id', $id)->update([
                'jam_mulai' => $jamM,
                'jam_selesai' => $jamS,
                'keterangan' => $ket,
            ]);
            $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', $id)->delete();
        } else {
            $db->table('ronda_jadwal_khusus')->insert([
                'tanggal' => $tanggal,
                'jam_mulai' => $jamM,
                'jam_selesai' => $jamS,
                'keterangan' => $ket,
            ]);
            $id = (int) $db->insertID();
        }
        foreach ($keluargaIds as $kid) {
            $db->table('ronda_jadwal_khusus_keluarga')->insert(['jadwal_id' => $id, 'keluarga_id' => $kid]);
        }

        $malam = $db->table('ronda_malam')->where('tanggal', $tanggal)->get()->getRowArray();
        if ($malam) {
            $mid = (int) $malam['id'];
            $db->table('ronda_malam')->where('id', $mid)->update([
                'jam_mulai' => $jamM,
                'jam_selesai' => $jamS,
                'sumber' => 'khusus',
            ]);
            if ($db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->countAllResults() === 0) {
                $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->delete();
                foreach ($keluargaIds as $kid) {
                    $db->table('ronda_malam_keluarga')->insert(['malam_id' => $mid, 'keluarga_id' => $kid]);
                }
            }
        } else {
            $db->table('ronda_malam')->insert([
                'tanggal' => $tanggal,
                'jam_mulai' => $jamM,
                'jam_selesai' => $jamS,
                'sumber' => 'khusus',
            ]);
            $mid = (int) $db->insertID();
            foreach ($keluargaIds as $kid) {
                $db->table('ronda_malam_keluarga')->insert(['malam_id' => $mid, 'keluarga_id' => $kid]);
            }
        }

        try {
            (new AuditService())->log('simpan_jadwal_khusus', 'ronda_jadwal_khusus', $id, null, ['tanggal' => $tanggal], 'pengguna', true, $userId);
        } catch (\Throwable $e) {}
        return ['ok' => true, 'data' => ['id' => $id, 'tanggal' => $tanggal]];
    }

    public function kalender(string $periode): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $periode = date('Y-m');
        }
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_malam')
            ->like('tanggal', $periode, 'after')
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'tanggal' => $r['tanggal'],
                'sumber' => $r['sumber'] ?? 'tetap',
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
            ];
        }
        return $out;
    }

    public function malamIni(): ?array
    {
        return $this->detailMalam(date('Y-m-d'));
    }

    public function malamTerdekat(): ?array
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        $row = $db->table('ronda_malam')
            ->where('tanggal >=', $today)
            ->orderBy('tanggal', 'ASC')
            ->get()->getRowArray();
        if (!$row) {
            return null;
        }
        return $this->detailMalam($row['tanggal']);
    }

    public function detailMalam(string $tanggal): ?array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return null;
        }
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('tanggal', $tanggal)->get()->getRowArray();
        if (!$m) {
            return null;
        }
        $mid = (int) $m['id'];
        $kel = $db->table('ronda_malam_keluarga mk')
            ->select('mk.keluarga_id, k.nomor, k.akhiran, b.nama as blok, a.nama as nama_kepala, a.foto')
            ->join('keluarga k', 'k.id = mk.keluarga_id')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->join('warga a', 'a.keluarga_id = k.id AND a.status = \'aktif\' AND a.hubungan = \'Kepala keluarga\'', 'left')
            ->where('mk.malam_id', $mid)
            ->get()->getResultArray();

        $keluarga = [];
        foreach ($kel as $k) {
            $keluarga[] = [
                'keluarga_id' => (int) $k['keluarga_id'],
                'nama' => $k['nama_kepala'] ?? null,
                'alamat' => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
                'foto' => $k['foto'] ?? null,
            ];
        }

        $absen = $db->table('ronda_absen')
            ->where('malam_id', $mid)
            ->where('dibatalkan', 0)
            ->get()->getResultArray();

        $bulanTerkunci = $this->bulanTerkunci(substr($tanggal, 0, 7));
        $bolehHadir = !$bulanTerkunci && $tanggal <= date('Y-m-d');

        return [
            'id' => $mid,
            'tanggal' => $m['tanggal'],
            'jam_mulai' => $m['jam_mulai'],
            'jam_selesai' => $m['jam_selesai'],
            'sumber' => $m['sumber'] ?? 'tetap',
            'keluarga' => $keluarga,
            'absen' => array_map(static function ($a) {
                return [
                    'id' => (int) $a['id'],
                    'keluarga_id' => (int) $a['keluarga_id'],
                    'foto' => $a['foto'] ?? null,
                    'waktu' => $a['waktu_server'] ?? null,
                ];
            }, $absen),
            'boleh_hadir' => $bolehHadir,
            'bulan_terkunci' => $bulanTerkunci,
        ];
    }

    public function generateBulan(string $periode): array
    {
        return $this->buatSlotKosong(['durasi' => '1'], 0);
    }

    public function absenManual(int $malamId, int $keluargaId, int $userId): array
    {
        if ($malamId < 1 || $keluargaId < 1) {
            return ['ok' => false, 'message' => 'Data tidak valid.'];
        }
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) {
            return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        }
        if ($this->bulanTerkunci(substr($m['tanggal'], 0, 7))) {
            return ['ok' => false, 'message' => 'Bulan sudah terkunci.'];
        }
        if ($m['tanggal'] > date('Y-m-d')) {
            return ['ok' => false, 'message' => 'Belum bisa absen untuk tanggal mendatang.'];
        }
        $ada = $db->table('ronda_absen')
            ->where('malam_id', $malamId)
            ->where('keluarga_id', $keluargaId)
            ->where('dibatalkan', 0)
            ->countAllResults();
        if ($ada > 0) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId,
            'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'),
            'foto' => null,
            'sumber' => 'manual',
            'dicatat_oleh' => $userId,
            'dibatalkan' => 0,
        ]);
        try {
            (new AuditService())->log('absen_manual_ronda', 'ronda_absen', $db->insertID(), null, ['malam_id' => $malamId, 'keluarga_id' => $keluargaId], 'pengguna', true, $userId);
        } catch (\Throwable $e) {}
        return ['ok' => true];
    }

    public function absenWarga(int $malamId, int $keluargaId, string $foto): array
    {
        if ($malamId < 1 || $keluargaId < 1) {
            return ['ok' => false, 'message' => 'Data tidak valid.'];
        }
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) {
            return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        }
        if ($this->bulanTerkunci(substr($m['tanggal'], 0, 7))) {
            return ['ok' => false, 'message' => 'Bulan sudah terkunci.'];
        }
        if ($m['tanggal'] > date('Y-m-d')) {
            return ['ok' => false, 'message' => 'Belum bisa absen untuk tanggal mendatang.'];
        }
        $assigned = $db->table('ronda_malam_keluarga')
            ->where('malam_id', $malamId)
            ->where('keluarga_id', $keluargaId)
            ->countAllResults();
        if ($assigned < 1) {
            return ['ok' => false, 'message' => 'Keluarga tidak bertugas malam ini.'];
        }
        $ada = $db->table('ronda_absen')
            ->where('malam_id', $malamId)
            ->where('keluarga_id', $keluargaId)
            ->where('dibatalkan', 0)
            ->countAllResults();
        if ($ada > 0) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId,
            'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'),
            'foto' => $foto ?: null,
            'sumber' => 'warga',
            'dibatalkan' => 0,
        ]);
        return ['ok' => true, 'data' => ['id' => (int) $db->insertID()]];
    }

    public function gantiKeluargaMalam(int $malamId, array $keluargaIds, int $userId): array
    {
        if ($malamId < 1) {
            return ['ok' => false, 'message' => 'Malam tidak valid.'];
        }
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) {
            return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        }
        if ($this->bulanTerkunci(substr($m['tanggal'], 0, 7))) {
            return ['ok' => false, 'message' => 'Bulan sudah terkunci.'];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $malamId, 'keluarga_id' => $kid]);
        }
        try {
            (new AuditService())->log('ganti_keluarga_ronda', 'ronda_malam', $malamId, null, ['keluarga_ids' => $ids], 'pengguna', true, $userId);
        } catch (\Throwable $e) {}
        return ['ok' => true, 'data' => $this->detailMalam($m['tanggal'])];
    }

    public function batalkanAbsen(int $absenId, int $userId): array
    {
        if ($absenId < 1) {
            return ['ok' => false, 'message' => 'Absen tidak valid.'];
        }
        $db = \Config\Database::connect();
        $a = $db->table('ronda_absen')->where('id', $absenId)->get()->getRowArray();
        if (!$a) {
            return ['ok' => false, 'message' => 'Absen tidak ditemukan.'];
        }
        $m = $db->table('ronda_malam')->where('id', $a['malam_id'])->get()->getRowArray();
        if ($m && $this->bulanTerkunci(substr($m['tanggal'], 0, 7))) {
            return ['ok' => false, 'message' => 'Bulan sudah terkunci.'];
        }
        $db->table('ronda_absen')->where('id', $absenId)->update(['dibatalkan' => 1]);
        try {
            (new AuditService())->log('batal_absen_ronda', 'ronda_absen', $absenId, null, null, 'pengguna', true, $userId);
        } catch (\Throwable $e) {}
        return ['ok' => true];
    }

    // --- REST OF FILE CONTINUES: terbitkanDenda, buatSlotKosong, isiOtomatis, helpers ---
    // This partial push is INCOMPLETE - will be replaced immediately with full file
    public function terbitkanDenda(string $periodeLalu, string $periodeTagihan): array
    {
        return ['ok' => false, 'message' => 'RESTORING'];
    }
    public function buatSlotKosong(array $data, int $userId): array
    {
        return ['ok' => false, 'message' => 'RESTORING'];
    }
    public function isiOtomatis(array $data, int $userId): array
    {
        return ['ok' => false, 'message' => 'RESTORING'];
    }
    private function bulanTerkunci(string $periode): bool
    {
        $db = \Config\Database::connect();
        return $db->table('ronda_kunci_bulan')->where('periode', $periode)->countAllResults() > 0;
    }
}
