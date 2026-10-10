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
                // hapus malam khusus di tanggal itu jika belum ada absen
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

        // sync / override ronda_malam untuk tanggal ini (khusus override tetap)
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
            ->join('anggota a', 'a.keluarga_id = k.id AND a.status = \'aktif\' AND a.hubungan = \'kepala\'', 'left')
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
                    'waktu' => $a['created_at'] ?? null,
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
            'foto' => null,
            'dibatalkan' => 0,
            'created_at' => date('Y-m-d H:i:s'),
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
        // pastikan keluarga memang ditugaskan
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
            'foto' => $foto ?: null,
            'dibatalkan' => 0,
            'created_at' => date('Y-m-d H:i:s'),
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

    public function terbitkanDenda(string $periodeLalu, string $periodeTagihan): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periodeLalu) || !preg_match('/^\d{4}-\d{2}$/', $periodeTagihan)) {
            return ['ok' => false, 'message' => 'Periode wajib format YYYY-MM.'];
        }
        $db = \Config\Database::connect();
        if ($this->bulanTerkunci($periodeLalu)) {
            return ['ok' => false, 'message' => 'Bulan ' . $periodeLalu . ' sudah terkunci / denda sudah diterbitkan.'];
        }

        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $nominalSatuan = max(0, (int) ($peng['denda_ronda'] ?? 0));
        if ($nominalSatuan < 1) {
            return ['ok' => false, 'message' => 'Nominal denda ronda belum diatur (Pengaturan warga).'];
        }

        $malam = $db->table('ronda_malam')
            ->like('tanggal', $periodeLalu, 'after')
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();

        // hitung pelanggaran per keluarga
        $countByKel = [];
        foreach ($malam as $m) {
            $mid = (int) $m['id'];
            $tgl = $m['tanggal'];
            // hanya malam yang sudah lewat
            if ($tgl > date('Y-m-d')) {
                continue;
            }
            $kel = $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->get()->getResultArray();
            foreach ($kel as $k) {
                $kid = (int) $k['keluarga_id'];
                $hadir = $db->table('ronda_absen')
                    ->where('malam_id', $mid)
                    ->where('keluarga_id', $kid)
                    ->where('dibatalkan', 0)
                    ->countAllResults();
                if ($hadir > 0) {
                    continue;
                }
                $countByKel[$kid] = ($countByKel[$kid] ?? 0) + 1;
            }
        }

        $dibuat = 0;
        $totalNominal = 0;
        foreach ($countByKel as $kid => $kali) {
            $nominal = $kali * $nominalSatuan;
            // cegah duplikat denda periode yang sama
            $ada = $db->table('iuran_tagihan')
                ->where('keluarga_id', $kid)
                ->where('periode', $periodeTagihan)
                ->where('jenis', 'denda_ronda')
                ->where('dibatalkan', 0)
                ->countAllResults();
            if ($ada > 0) {
                continue;
            }
            $db->table('iuran_tagihan')->insert([
                'keluarga_id' => $kid,
                'periode' => $periodeTagihan,
                'jenis' => 'denda_ronda',
                'nominal' => $nominal,
                'dibatalkan' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $dibuat++;
            $totalNominal += $nominal;
        }

        // kunci absensi bulan lalu
        $adaKunci = $db->table('ronda_kunci_bulan')->where('periode', $periodeLalu)->countAllResults();
        if (!$adaKunci) {
            try {
                $db->table('ronda_kunci_bulan')->insert([
                    'periode' => $periodeLalu,
                    'dikunci_pada' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                // fallback kolom minimal
                try {
                    $db->table('ronda_kunci_bulan')->insert(['periode' => $periodeLalu]);
                } catch (\Throwable $e2) {
                }
            }
        }

        return [
            'ok' => true,
            'denda' => $dibuat,
            'keluarga' => count($countByKel),
            'total_nominal' => $totalNominal,
            'nominal_satuan' => $nominalSatuan,
            'periode_lalu' => $periodeLalu,
            'periode_tagihan' => $periodeTagihan,
        ];
    }

    private function bersihkanDuplikatMalam(): int
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_malam')->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $byTgl = [];
        foreach ($rows as $r) {
            $byTgl[$r['tanggal']][] = $r;
        }
        $dihapus = 0;
        foreach ($byTgl as $tgl => $list) {
            if (count($list) < 2) continue;
            $keepId = (int) $list[0]['id'];
            foreach ($list as $r) {
                $mid = (int) $r['id'];
                if ($db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->countAllResults() > 0) {
                    $keepId = $mid;
                    break;
                }
            }
            foreach ($list as $r) {
                $mid = (int) $r['id'];
                if ($mid === $keepId) continue;
                $keepHasKel = $db->table('ronda_malam_keluarga')->where('malam_id', $keepId)->countAllResults();
                if ($keepHasKel === 0) {
                    $kels = $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->get()->getResultArray();
                    foreach ($kels as $k) {
                        $db->table('ronda_malam_keluarga')->insert([
                            'malam_id' => $keepId,
                            'keluarga_id' => $k['keluarga_id'],
                        ]);
                    }
                }
                $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->delete();
                $db->table('ronda_absen')->where('malam_id', $mid)->delete();
                $db->table('ronda_malam')->where('id', $mid)->delete();
                $dihapus++;
            }
        }
        return $dihapus;
    }

    public function buatSlotKosong(array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $dupHapus = $this->bersihkanDuplikatMalam();
        $tetap = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
        if (!$tetap) {
            return ['ok' => false, 'message' => 'Atur jadwal tetap dulu (pilih hari ronda).'];
        }
        $hariExisting = array_map(static fn ($r) => (int) $r['hari'], $tetap);
        $hariReq = array_values(array_unique(array_map('intval', $data['hari'] ?? [])));
        $hariReq = array_values(array_filter($hariReq, static fn ($h) => $h >= 0 && $h <= 6));
        $hari = $hariReq ? array_values(array_intersect($hariReq, $hariExisting)) : array_values(array_unique($hariExisting));
        if (!$hari) {
            return ['ok' => false, 'message' => 'Tidak ada hari ronda yang cocok dengan jadwal tetap.'];
        }
        sort($hari);
        $hariSet = array_fill_keys($hari, true);

        $durasi = $data['durasi'] ?? '1';
        $today = date('Y-m-d');
        if ($durasi === 'minggu' || $durasi === 'week' || $durasi === '0') {
            $n = (int) date('N');
            $end = date('Y-m-d', strtotime($today . ' +' . (7 - $n) . ' days'));
            if ($end < $today) $end = $today;
        } else {
            $nBulan = max(1, min(12, (int) $durasi));
            $end = date('Y-m-d', strtotime($today . ' +' . $nBulan . ' months -1 day'));
        }

        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $tetapByHari = [];
        foreach ($tetap as $tt) $tetapByHari[(int) $tt['hari']] = $tt;
        $defMulai = $peng['jam_ronda_mulai'] ?? '21:00:00';
        $defSelesai = $peng['jam_ronda_selesai'] ?? '00:00:00';

        $target = [];
        $tcur = strtotime($today);
        $tEnd = strtotime($end);
        while ($tcur <= $tEnd) {
            $tgl = date('Y-m-d', $tcur);
            $h = (int) date('w', $tcur);
            $tcur = strtotime('+1 day', $tcur);
            if (!isset($hariSet[$h])) continue;
            $target[$tgl] = $h;
        }
        if (!$target) {
            return ['ok' => false, 'message' => 'Tidak ada tanggal cocok di periode ini.'];
        }

        $dihapus = (int) $dupHapus;
        $existingFuture = $db->table('ronda_malam')
            ->where('tanggal >=', $today)
            ->where('sumber', 'tetap')
            ->get()->getResultArray();
        foreach ($existingFuture as $row) {
            if (isset($target[$row['tanggal']])) continue;
            $mid = (int) $row['id'];
            if ($db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->countAllResults() > 0) continue;
            $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->delete();
            $db->table('ronda_absen')->where('malam_id', $mid)->delete();
            $db->table('ronda_malam')->where('id', $mid)->delete();
            $dihapus++;
        }

        $dibuat = 0;
        $dilewati = 0;
        foreach ($target as $tgl => $h) {
            if ($db->table('ronda_malam')->where('tanggal', $tgl)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            $db->table('ronda_malam')->insert([
                'tanggal' => $tgl,
                'jam_mulai' => $tetapByHari[$h]['jam_mulai'] ?? $defMulai,
                'jam_selesai' => $tetapByHari[$h]['jam_selesai'] ?? $defSelesai,
                'sumber' => 'tetap',
            ]);
            $dibuat++;
        }

        if ($dibuat === 0 && $dilewati === 0 && $dihapus === 0) {
            return ['ok' => false, 'message' => 'Tidak ada perubahan jadwal.'];
        }

        try {
            (new AuditService())->log('buat_slot_ronda', 'ronda_malam', null, null, [
                'hari' => $hari, 'durasi' => $durasi, 'dari' => $today, 'sampai' => $end,
                'dibuat' => $dibuat, 'dilewati' => $dilewati, 'dihapus' => $dihapus,
            ], 'pengguna', true, $userId);
        } catch (\Throwable $e) {}

        return [
            'ok' => true,
            'data' => [
                'hari' => $hari,
                'durasi' => $durasi,
                'dari' => $today,
                'sampai' => $end,
                'malam_target' => count($target),
                'dibuat' => $dibuat,
                'dilewati' => $dilewati,
                'dihapus' => $dihapus,
            ],
        ];
    }

    public function isiOtomatis(array $data, int $userId): array
    {
        // 1) pastikan slot tetap ada di periode (abaikan "tidak ada perubahan")
        $slot = $this->buatSlotKosong($data, $userId);
        if (!$slot['ok']) {
            $msg = (string) ($slot['message'] ?? '');
            // gagal keras: belum ada jadwal tetap / tidak ada hari cocok
            if (stripos($msg, 'jadwal tetap') !== false || stripos($msg, 'hari ronda') !== false) {
                return $slot;
            }
            // selain itu lanjut isi malam yang sudah ada
        }

        $db = \Config\Database::connect();
        $perMalam = max(1, min(20, (int) ($data['keluarga_per_malam'] ?? 2)));
        $durasi = $data['durasi'] ?? '1';
        $today = date('Y-m-d');
        if ($durasi === 'minggu' || $durasi === 'week' || $durasi === '0') {
            $n = (int) date('N');
            $end = date('Y-m-d', strtotime($today . ' +' . (7 - $n) . ' days'));
            if ($end < $today) {
                $end = $today;
            }
        } else {
            $nBulan = max(1, min(12, (int) $durasi));
            $end = date('Y-m-d', strtotime($today . ' +' . $nBulan . ' months -1 day'));
        }

        // KK aktif urut blok + nomor rumah
        $kk = $db->table('keluarga k')
            ->select('k.id')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('k.status', 'aktif')
            ->orderBy('b.nama', 'ASC')
            ->orderBy('k.nomor', 'ASC')
            ->orderBy('k.akhiran', 'ASC')
            ->orderBy('k.id', 'ASC')
            ->get()->getResultArray();
        $kkIds = array_map(static fn ($r) => (int) $r['id'], $kk);
        $totalKk = count($kkIds);
        if ($totalKk < 1) {
            return ['ok' => false, 'message' => 'Tidak ada keluarga aktif untuk diisi.'];
        }

        // malam tetap di periode, skip khusus
        $malamRows = $db->table('ronda_malam')
            ->where('tanggal >=', $today)
            ->where('tanggal <=', $end)
            ->where('sumber', 'tetap')
            ->orderBy('tanggal', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $diisi = 0;
        $dilewati = 0;
        $cursor = 0;

        // lanjut rotasi dari penugasan terakhir (agar bergiliran lintas periode)
        $last = $db->table('ronda_malam_keluarga mk')
            ->select('mk.keluarga_id, m.tanggal')
            ->join('ronda_malam m', 'm.id = mk.malam_id')
            ->where('m.tanggal <', $today)
            ->where('m.sumber', 'tetap')
            ->orderBy('m.tanggal', 'DESC')
            ->orderBy('mk.id', 'DESC')
            ->get()->getRowArray();
        if ($last) {
            $pos = array_search((int) $last['keluarga_id'], $kkIds, true);
            if ($pos !== false) {
                $cursor = ((int) $pos + 1) % $totalKk;
            }
        }

        foreach ($malamRows as $m) {
            $mid = (int) $m['id'];
            // jangan sentuh malam yang sudah ada absen
            if ($db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            // hanya isi yang masih kosong (tidak overwrite manual)
            if ($db->table('ronda_malam_keluarga')->where('malam_id', $mid)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            $picked = [];
            for ($i = 0; $i < $perMalam; $i++) {
                $picked[] = $kkIds[$cursor % $totalKk];
                $cursor++;
            }
            $picked = array_values(array_unique($picked));
            foreach ($picked as $kid) {
                $db->table('ronda_malam_keluarga')->insert([
                    'malam_id' => $mid,
                    'keluarga_id' => $kid,
                ]);
            }
            $diisi++;
        }

        try {
            (new AuditService())->log('isi_otomatis_ronda', 'ronda_malam', null, null, [
                'durasi' => $durasi,
                'keluarga_per_malam' => $perMalam,
                'diisi' => $diisi,
                'dilewati' => $dilewati,
                'total_kk' => $totalKk,
                'slot' => $slot['data'] ?? null,
            ], 'pengguna', true, $userId);
        } catch (\Throwable $e) {
        }

        return [
            'ok' => true,
            'data' => [
                'diisi' => $diisi,
                'dilewati' => $dilewati,
                'total_kk' => $totalKk,
                'keluarga_per_malam' => $perMalam,
                'dari' => $today,
                'sampai' => $end,
                'slot' => $slot['data'] ?? null,
                'dibuat' => (int) (($slot['data']['dibuat'] ?? 0)),
            ],
        ];
    }

    private function bulanTerkunci(string $periode): bool
    {
        $db = \Config\Database::connect();
        return $db->table('ronda_kunci_bulan')->where('periode', $periode)->countAllResults() > 0;
    }

    private function malamTerkunci(int $malamId): bool
    {
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return true;
        return $this->bulanTerkunci(substr($m['tanggal'], 0, 7));
    }
}
