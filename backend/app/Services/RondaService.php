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
            }
            return ['ok' => true, 'data' => ['deleted' => true, 'tanggal' => $tanggal]];
        }
        if ($existing) {
            $id = (int) $existing['id'];
            $db->table('ronda_jadwal_khusus')->where('id', $id)->update(['jam_mulai' => $jamM, 'jam_selesai' => $jamS, 'keterangan' => $ket]);
            $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', $id)->delete();
        } else {
            $db->table('ronda_jadwal_khusus')->insert(['tanggal' => $tanggal, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS, 'keterangan' => $ket]);
            $id = (int) $db->insertID();
        }
        foreach ($keluargaIds as $kid) {
            $db->table('ronda_jadwal_khusus_keluarga')->insert(['jadwal_id' => $id, 'keluarga_id' => $kid]);
        }
        return ['ok' => true, 'data' => ['id' => $id, 'tanggal' => $tanggal]];
    }

    public function kalender(string $periode): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $periode = date('Y-m');
        }
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_malam')->like('tanggal', $periode, 'after')->orderBy('tanggal', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['tanggal' => $r['tanggal'], 'sumber' => $r['sumber'] ?? 'tetap', 'jam_mulai' => $r['jam_mulai'], 'jam_selesai' => $r['jam_selesai']];
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
        $row = $db->table('ronda_malam')->where('tanggal >=', date('Y-m-d'))->orderBy('tanggal', 'ASC')->get()->getRowArray();
        return $row ? $this->detailMalam($row['tanggal']) : null;
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
        $absen = $db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->get()->getResultArray();
        $bulanTerkunci = $this->bulanTerkunci(substr($tanggal, 0, 7));
        return [
            'id' => $mid,
            'tanggal' => $m['tanggal'],
            'jam_mulai' => $m['jam_mulai'],
            'jam_selesai' => $m['jam_selesai'],
            'sumber' => $m['sumber'] ?? 'tetap',
            'keluarga' => $keluarga,
            'absen' => array_map(static fn ($a) => ['id' => (int) $a['id'], 'keluarga_id' => (int) $a['keluarga_id'], 'foto' => $a['foto'] ?? null, 'waktu' => $a['waktu_server'] ?? null], $absen),
            'boleh_hadir' => !$bulanTerkunci && $tanggal <= date('Y-m-d'),
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
        if ($db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->where('dibatalkan', 0)->countAllResults() > 0) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert(['malam_id' => $malamId, 'keluarga_id' => $keluargaId, 'waktu_server' => date('Y-m-d H:i:s'), 'foto' => null, 'sumber' => 'manual', 'dicatat_oleh' => $userId, 'dibatalkan' => 0]);
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
        if ($this->bulanTerkunci(substr($m['tanggal'], 0, 7)) || $m['tanggal'] > date('Y-m-d')) {
            return ['ok' => false, 'message' => 'Tidak bisa absen.'];
        }
        if ($db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->countAllResults() < 1) {
            return ['ok' => false, 'message' => 'Keluarga tidak bertugas malam ini.'];
        }
        if ($db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->countAllResults() > 0) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert(['malam_id' => $malamId, 'keluarga_id' => $keluargaId, 'waktu_server' => date('Y-m-d H:i:s'), 'foto' => $foto ?: null, 'sumber' => 'warga', 'dibatalkan' => 0]);
        return ['ok' => true, 'data' => ['id' => (int) $db->insertID()]];
    }

    public function gantiKeluargaMalam(int $malamId, array $keluargaIds, int $userId): array
    {
        if ($malamId < 1) {
            return ['ok' => false, 'message' => 'Malam tidak valid.'];
        }
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m || $this->bulanTerkunci(substr($m['tanggal'], 0, 7))) {
            return ['ok' => false, 'message' => 'Tidak bisa mengubah.'];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $malamId, 'keluarga_id' => $kid]);
        }
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
        return ['ok' => true];
    }

    public function terbitkanDenda(string $periodeLalu, string $periodeTagihan): array
    {
        return ['ok' => false, 'message' => 'Fitur denda sedang dipulihkan.'];
    }

    public function buatSlotKosong(array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $tetap = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
        if (!$tetap) {
            return ['ok' => false, 'message' => 'Atur jadwal tetap dulu (pilih hari ronda).'];
        }
        $hariExisting = array_values(array_unique(array_map(static fn ($r) => (int) $r['hari'], $tetap)));
        $hariReq = array_values(array_unique(array_map('intval', $data['hari'] ?? [])));
        $hariReq = array_values(array_filter($hariReq, static fn ($h) => $h >= 0 && $h <= 6));
        $hari = $hariReq ? array_values(array_intersect($hariReq, $hariExisting)) : $hariExisting;
        if (!$hari) {
            return ['ok' => false, 'message' => 'Tidak ada hari ronda yang cocok dengan jadwal tetap.'];
        }
        $hariSet = array_fill_keys($hari, true);

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

        $tetapByHari = [];
        foreach ($tetap as $tt) {
            $tetapByHari[(int) $tt['hari']] = $tt;
        }
        $defMulai = '21:00:00';
        $defSelesai = '00:00:00';
        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        if ($peng) {
            $defMulai = $peng['jam_ronda_mulai'] ?? $defMulai;
            $defSelesai = $peng['jam_ronda_selesai'] ?? $defSelesai;
        }

        $dibuat = 0;
        $dilewati = 0;
        $tcur = strtotime($today);
        $tEnd = strtotime($end);
        while ($tcur <= $tEnd) {
            $tgl = date('Y-m-d', $tcur);
            $h = (int) date('w', $tcur);
            $tcur = strtotime('+1 day', $tcur);
            if (!isset($hariSet[$h])) {
                continue;
            }
            if ($db->table('ronda_jadwal_khusus')->where('tanggal', $tgl)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            if ($db->table('ronda_malam')->where('tanggal', $tgl)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            $jm = $tetapByHari[$h]['jam_mulai'] ?? $defMulai;
            $js = $tetapByHari[$h]['jam_selesai'] ?? $defSelesai;
            $db->table('ronda_malam')->insert([
                'tanggal' => $tgl,
                'jam_mulai' => $jm,
                'jam_selesai' => $js,
                'sumber' => 'tetap',
            ]);
            $dibuat++;
        }

        return [
            'ok' => true,
            'data' => [
                'dibuat' => $dibuat,
                'dilewati' => $dilewati,
                'dari' => $today,
                'sampai' => $end,
            ],
        ];
    }

    public function hapusJadwalKeDepan(array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        $mode = $data['mode'] ?? 'slot';
        if (!in_array($mode, ['penugasan', 'slot'], true)) {
            $mode = 'slot';
        }
        $rows = $db->table('ronda_malam')
            ->where('tanggal >=', $today)
            ->where('sumber', 'tetap')
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
        $cleared = 0;
        $deleted = 0;
        $skipped = 0;
        foreach ($rows as $m) {
            $mid = (int) $m['id'];
            $hasAbsen = $db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->countAllResults() > 0;
            if ($hasAbsen) {
                $skipped++;
                continue;
            }
            $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->delete();
            $cleared++;
            if ($mode === 'slot') {
                $db->table('ronda_absen')->where('malam_id', $mid)->delete();
                $db->table('ronda_malam')->where('id', $mid)->delete();
                $deleted++;
            }
        }
        return [
            'ok' => true,
            'data' => [
                'mode' => $mode,
                'penugasan_dihapus' => $cleared,
                'slot_dihapus' => $deleted,
                'dilewati_ada_absen' => $skipped,
            ],
        ];
    }

    public function isiOtomatis(array $data, int $userId): array
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        $tetap = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
        if (!$tetap) {
            return ['ok' => false, 'message' => 'Atur jadwal tetap dulu (pilih hari ronda).'];
        }
        $activeDays = array_values(array_unique(array_map(static fn ($r) => (int) $r['hari'], $tetap)));
        sort($activeDays);
        $D = count($activeDays);
        if ($D < 1) {
            return ['ok' => false, 'message' => 'Tidak ada hari ronda aktif.'];
        }
        $N = $D * 4;
        $kk = $db->table('keluarga k')->select('k.id')->join('blok b', 'b.id = k.blok_id', 'left')->where('k.status', 'aktif')->orderBy('b.nama', 'ASC')->orderBy('k.nomor', 'ASC')->orderBy('k.akhiran', 'ASC')->orderBy('k.id', 'ASC')->get()->getResultArray();
        $kkIds = array_map(static fn ($r) => (int) $r['id'], $kk);
        $totalKk = count($kkIds);
        if ($totalKk < 1) {
            return ['ok' => false, 'message' => 'Tidak ada keluarga aktif untuk diisi.'];
        }
        $base = intdiv($totalKk, $N);
        $sisa = $totalKk % $N;
        $groups = [];
        $offset = 0;
        for ($g = 0; $g < $N; $g++) {
            $size = $base + ($g < $sisa ? 1 : 0);
            $groups[$g] = $size > 0 ? array_slice($kkIds, $offset, $size) : [];
            $offset += $size;
        }
        $malamRows = $db->table('ronda_malam')->where('tanggal >=', $today)->where('sumber', 'tetap')->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        if (!$malamRows) {
            return ['ok' => false, 'message' => 'Belum ada slot jadwal tetap. Buat jadwal dulu di menu Jadwal tetap.'];
        }
        $activeSet = array_fill_keys($activeDays, true);
        $diisi = 0;
        $dilewati = 0;
        foreach ($malamRows as $m) {
            $mid = (int) $m['id'];
            $tgl = $m['tanggal'];
            if ($db->table('ronda_jadwal_khusus')->where('tanggal', $tgl)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            if ($db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            if ($db->table('ronda_malam_keluarga')->where('malam_id', $mid)->countAllResults() > 0) {
                $dilewati++;
                continue;
            }
            $w = (int) date('w', strtotime($tgl));
            if (!isset($activeSet[$w])) {
                $dilewati++;
                continue;
            }
            $ts = strtotime($tgl);
            $y = (int) date('Y', $ts);
            $mo = (int) date('n', $ts);
            $lastDay = (int) date('t', $ts);
            $slotIndex = -1;
            $seq = 0;
            for ($d = 1; $d <= $lastDay; $d++) {
                $t = strtotime(sprintf('%04d-%02d-%02d', $y, $mo, $d));
                $dw = (int) date('w', $t);
                if (!isset($activeSet[$dw])) {
                    continue;
                }
                if ($d === (int) date('j', $ts)) {
                    $slotIndex = $seq;
                    break;
                }
                $seq++;
            }
            if ($slotIndex < 0) {
                $dilewati++;
                continue;
            }
            $g = $slotIndex % $N;
            foreach ($groups[$g] as $kid) {
                $db->table('ronda_malam_keluarga')->insert(['malam_id' => $mid, 'keluarga_id' => $kid]);
            }
            $diisi++;
        }
        return [
            'ok' => true,
            'data' => [
                'diisi' => $diisi,
                'dilewati' => $dilewati,
                'total_kk' => $totalKk,
                'jumlah_kelompok' => $N,
                'ukuran_kelompok' => array_map('count', $groups),
                'hari_aktif' => $activeDays,
            ],
        ];
    }

    private function bulanTerkunci(string $periode): bool
    {
        $db = \Config\Database::connect();
        return $db->table('ronda_kunci_bulan')->where('periode', $periode)->countAllResults() > 0;
    }
}
