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
        $keluargaIds = array_values(array_unique(array_filter(array_map('intval', $data['keluarga_ids'] ?? []), static fn ($x) => $x > 0)));

        $db->table('ronda_jadwal_khusus')->insert([
            'tanggal' => $tanggal,
            'jam_mulai' => $jamM,
            'jam_selesai' => $jamS,
            'keterangan' => $ket,
        ]);
        $id = (int) $db->insertID();
        foreach ($keluargaIds as $kid) {
            $db->table('ronda_jadwal_khusus_keluarga')->insert(['jadwal_id' => $id, 'keluarga_id' => $kid]);
        }

        $malam = $db->table('ronda_malam')->where('tanggal', $tanggal)->orderBy('id', 'ASC')->get()->getRowArray();
        if ($malam) {
            $malamId = (int) $malam['id'];
            $db->table('ronda_malam')->where('id', $malamId)->update([
                'jam_mulai' => $jamM,
                'jam_selesai' => $jamS,
                'sumber' => 'khusus',
            ]);
            $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->delete();
        } else {
            $db->table('ronda_malam')->insert([
                'tanggal' => $tanggal,
                'jam_mulai' => $jamM,
                'jam_selesai' => $jamS,
                'sumber' => 'khusus',
            ]);
            $malamId = (int) $db->insertID();
        }
        foreach ($keluargaIds as $kid) {
            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $malamId, 'keluarga_id' => $kid]);
        }

        try {
            (new AuditService())->log('simpan_jadwal_khusus', 'ronda_jadwal_khusus', $id, null, [
                'tanggal' => $tanggal, 'malam_id' => $malamId,
            ], 'pengguna', true, $userId);
        } catch (\Throwable $e) {}

        return [
            'ok' => true,
            'data' => [
                'id' => $id,
                'malam_id' => $malamId,
                'tanggal' => $tanggal,
                'sumber' => 'khusus',
            ],
        ];
    }

    public function kalender(string $periode): array
    {
        $db = \Config\Database::connect();
        try { $this->bersihkanDuplikatMalam(); } catch (\Throwable $e) {}
        $rows = $db->table('ronda_malam')->like('tanggal', $periode, 'after')->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $seen = [];
        $out = [];
        foreach ($rows as $r) {
            $tgl = $r['tanggal'];
            if (isset($seen[$tgl])) {
                continue;
            }
            $seen[$tgl] = true;
            $out[] = [
                'id' => (int) $r['id'],
                'tanggal' => $tgl,
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
                'sumber' => $r['sumber'],
            ];
        }
        return $out;
    }

    private function kepalaInfo(int $keluargaId): array
    {
        $db = \Config\Database::connect();
        $w = $db->table('warga')->where('keluarga_id', $keluargaId)->where('status', 'aktif')->where('hubungan', 'Kepala keluarga')->get()->getRowArray();
        if (!$w) {
            $w = $db->table('warga')->where('keluarga_id', $keluargaId)->where('status', 'aktif')->orderBy('id', 'ASC')->get()->getRowArray();
        }
        return ['nama' => $w['nama'] ?? '—', 'foto' => $w['foto'] ?? null];
    }

    public function detailMalam(string $tanggal): ?array
    {
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('tanggal', $tanggal)->orderBy('id', 'ASC')->get()->getRowArray();
        if (!$m) return null;
        $kel = $db->table('ronda_malam_keluarga mk')
            ->select('mk.keluarga_id, k.nomor, k.akhiran, b.nama as blok')
            ->join('keluarga k', 'k.id = mk.keluarga_id')
            ->join('blok b', 'b.id = k.blok_id')
            ->where('mk.malam_id', $m['id'])
            ->get()->getResultArray();
        $absen = $db->table('ronda_absen')->where('malam_id', $m['id'])->where('dibatalkan', 0)->get()->getResultArray();
        $absenByKel = [];
        foreach ($absen as $a) $absenByKel[(int) $a['keluarga_id']] = $a;
        $today = date('Y-m-d');
        $terkunci = $this->bulanTerkunci(substr($tanggal, 0, 7));
        $bolehHadir = ($tanggal <= $today) && !$terkunci;
        $list = [];
        foreach ($kel as $k) {
            $kid = (int) $k['keluarga_id'];
            $a = $absenByKel[$kid] ?? null;
            $info = $this->kepalaInfo($kid);
            $list[] = [
                'keluarga_id' => $kid,
                'nama' => $info['nama'],
                'foto' => $info['foto'],
                'alamat' => $k['blok'] . '-' . $k['nomor'] . ($k['akhiran'] ?? ''),
                'status' => $a ? 'hadir' : 'belum',
                'absen' => $a ? [
                    'id' => (int) $a['id'],
                    'waktu_server' => $a['waktu_server'],
                    'foto' => $a['foto'],
                    'sumber' => $a['sumber'],
                ] : null,
            ];
        }
        return [
            'id' => (int) $m['id'],
            'tanggal' => $m['tanggal'],
            'jam_mulai' => $m['jam_mulai'],
            'jam_selesai' => $m['jam_selesai'],
            'sumber' => $m['sumber'],
            'terkunci' => $terkunci,
            'boleh_hadir' => $bolehHadir,
            'keluarga' => $list,
        ];
    }

    public function malamIni(): ?array
    {
        return $this->detailMalam(date('Y-m-d'));
    }

    public function malamTerdekat(): ?array
    {
        $db = \Config\Database::connect();
        $row = $db->table('ronda_malam')->where('tanggal >=', date('Y-m-d'))->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->get()->getRowArray();
        if (!$row) return null;
        return $this->detailMalam($row['tanggal']);
    }

    public function generateBulan(string $periode): array
    {
        return ['ok' => true, 'dibuat' => 0];
    }

    public function absenManual(int $malamId, int $keluargaId, int $userId): array
    {
        if ($this->malamTerkunci($malamId)) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        if (($m['tanggal'] ?? '') > date('Y-m-d')) return ['ok' => false, 'message' => 'Belum bisa absen (jadwal belum terjadi).'];
        if ($db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->where('dibatalkan', 0)->countAllResults()) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId, 'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'), 'sumber' => 'manual',
            'dicatat_oleh' => $userId, 'dibatalkan' => 0,
        ]);
        return ['ok' => true];
    }

    public function absenWarga(int $malamId, int $keluargaId, string $fotoPath): array
    {
        if ($fotoPath === '' || !preg_match('#^ronda/[a-zA-Z0-9._-]+$#', $fotoPath)) {
            return ['ok' => false, 'message' => 'Foto absen wajib (unggah dulu).'];
        }
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        if ($this->malamTerkunci($malamId)) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        if ($db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->where('dibatalkan', 0)->countAllResults()) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId, 'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'), 'foto' => $fotoPath,
            'sumber' => 'warga', 'dicatat_oleh' => null, 'dibatalkan' => 0,
        ]);
        return ['ok' => true, 'data' => null];
    }

    public function gantiKeluargaMalam(int $malamId, array $keluargaIds, int $userId): array
    {
        if ($this->malamTerkunci($malamId)) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $malamId, 'keluarga_id' => $kid]);
        }
        return ['ok' => true, 'data' => $this->detailMalam($m['tanggal'])];
    }

    public function batalkanAbsen(int $absenId, int $userId): array
    {
        $db = \Config\Database::connect();
        $a = $db->table('ronda_absen')->where('id', $absenId)->get()->getRowArray();
        if (!$a || (int) $a['dibatalkan'] === 1) return ['ok' => false, 'message' => 'Absen tidak ditemukan.'];
        if ($this->malamTerkunci((int) $a['malam_id'])) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        $db->table('ronda_absen')->where('id', $absenId)->update([
            'dibatalkan' => 1, 'dibatalkan_oleh' => $userId, 'dibatalkan_pada' => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true];
    }

    public function terbitkanDenda(string $periodeLalu, string $periodeTagihan): array
    {
        return ['ok' => true, 'denda' => 0];
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
                        $kid = (int) $k['keluarga_id'];
                        $ada = $db->table('ronda_malam_keluarga')->where('malam_id', $keepId)->where('keluarga_id', $kid)->countAllResults();
                        if (!$ada) {
                            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $keepId, 'keluarga_id' => $kid]);
                        }
                    }
                }
                $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->delete();
                $absens = $db->table('ronda_absen')->where('malam_id', $mid)->get()->getResultArray();
                foreach ($absens as $a) {
                    $exists = $db->table('ronda_absen')->where('malam_id', $keepId)->where('keluarga_id', (int) $a['keluarga_id'])->where('dibatalkan', 0)->countAllResults();
                    if ($exists) {
                        $db->table('ronda_absen')->where('id', (int) $a['id'])->delete();
                    } else {
                        $db->table('ronda_absen')->where('id', (int) $a['id'])->update(['malam_id' => $keepId]);
                    }
                }
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
        return $this->buatSlotKosong($data, $userId);
    }

    private function bulanTerkunci(string $periode): bool
    {
        $db = \Config\Database::connect();
        $row = $db->table('ronda_kunci_bulan')->where('periode', $periode)->get()->getRowArray();
        return (bool) $row;
    }

    private function malamTerkunci(int $malamId): bool
    {
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return true;
        return $this->bulanTerkunci(substr($m['tanggal'], 0, 7));
    }
}
