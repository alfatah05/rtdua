<?php

namespace App\Services;

/**
 * Ronda: jadwal tetap/khusus, malam, absen, denda bulan berikutnya.
 */
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
        $jamM = $data['jam_mulai'] ?? null;
        $jamS = $data['jam_selesai'] ?? null;
        $keluargaIds = array_map('intval', $data['keluarga_ids'] ?? []);
        $existing = $db->table('ronda_jadwal_tetap')->where('hari', $hari)->get()->getRowArray();
        if ($existing) {
            $id = (int) $existing['id'];
            $db->table('ronda_jadwal_tetap')->where('id', $id)->update(['jam_mulai' => $jamM, 'jam_selesai' => $jamS]);
            $db->table('ronda_jadwal_tetap_keluarga')->where('jadwal_id', $id)->delete();
        } else {
            $db->table('ronda_jadwal_tetap')->insert(['hari' => $hari, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS]);
            $id = (int) $db->insertID();
        }
        foreach ($keluargaIds as $kid) {
            if ($kid < 1) continue;
            $db->table('ronda_jadwal_tetap_keluarga')->insert(['jadwal_id' => $id, 'keluarga_id' => $kid]);
        }
        (new AuditService())->log('simpan_jadwal_tetap', 'ronda_jadwal_tetap', $id, null, ['hari' => $hari], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id]];
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
        $db = \Config\Database::connect();
        $db->table('ronda_jadwal_khusus')->insert([
            'tanggal' => $tanggal,
            'jam_mulai' => $data['jam_mulai'] ?? null,
            'jam_selesai' => $data['jam_selesai'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);
        $id = (int) $db->insertID();
        foreach (array_map('intval', $data['keluarga_ids'] ?? []) as $kid) {
            if ($kid > 0) {
                $db->table('ronda_jadwal_khusus_keluarga')->insert(['jadwal_id' => $id, 'keluarga_id' => $kid]);
            }
        }
        (new AuditService())->log('simpan_jadwal_khusus', 'ronda_jadwal_khusus', $id, null, ['tanggal' => $tanggal], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => ['id' => $id]];
    }

    public function kalender(string $periode): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_malam')->like('tanggal', $periode, 'after')->orderBy('tanggal', 'ASC')->get()->getResultArray();
        return array_map(static function ($r) {
            return [
                'id' => (int) $r['id'],
                'tanggal' => $r['tanggal'],
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
                'sumber' => $r['sumber'],
            ];
        }, $rows);
    }

    public function detailMalam(string $tanggal): ?array
    {
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('tanggal', $tanggal)->get()->getRowArray();
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
        $terkunci = $this->bulanTerkunci(substr($tanggal, 0, 7));
        $list = [];
        foreach ($kel as $k) {
            $kid = (int) $k['keluarga_id'];
            $a = $absenByKel[$kid] ?? null;
            $list[] = [
                'keluarga_id' => $kid,
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
            'keluarga' => $list,
        ];
    }

    public function malamIni(): ?array
    {
        return $this->detailMalam(date('Y-m-d'));
    }

    public function generateBulan(string $periode): array
    {
        $db = \Config\Database::connect();
        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $defMulai = $peng['jam_ronda_mulai'] ?? '21:00:00';
        $defSelesai = $peng['jam_ronda_selesai'] ?? '00:00:00';
        [$y, $m] = array_map('intval', explode('-', $periode));
        $days = (int) date('t', strtotime($periode . '-01'));
        $dibuat = 0;
        $tetap = $db->table('ronda_jadwal_tetap')->get()->getResultArray();
        $tetapByHari = [];
        foreach ($tetap as $t) $tetapByHari[(int) $t['hari']] = $t;
        $khusus = $db->table('ronda_jadwal_khusus')->like('tanggal', $periode, 'after')->get()->getResultArray();
        $khususByTgl = [];
        foreach ($khusus as $k) $khususByTgl[$k['tanggal']] = $k;
        for ($d = 1; $d <= $days; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $y, $m, $d);
            if ($db->table('ronda_malam')->where('tanggal', $tgl)->countAllResults() > 0) continue;
            $sumber = 'tetap';
            $jamM = $defMulai;
            $jamS = $defSelesai;
            $kelIds = [];
            if (isset($khususByTgl[$tgl])) {
                $k = $khususByTgl[$tgl];
                $sumber = 'khusus';
                $jamM = $k['jam_mulai'] ?: $defMulai;
                $jamS = $k['jam_selesai'] ?: $defSelesai;
                $kelIds = array_column($db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', $k['id'])->get()->getResultArray(), 'keluarga_id');
            } else {
                $hari = (int) date('w', strtotime($tgl));
                if (!isset($tetapByHari[$hari])) continue;
                $t = $tetapByHari[$hari];
                $jamM = $t['jam_mulai'] ?: $defMulai;
                $jamS = $t['jam_selesai'] ?: $defSelesai;
                $kelIds = array_column($db->table('ronda_jadwal_tetap_keluarga')->where('jadwal_id', $t['id'])->get()->getResultArray(), 'keluarga_id');
            }
            if (!$kelIds) continue;
            $db->table('ronda_malam')->insert(['tanggal' => $tgl, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS, 'sumber' => $sumber]);
            $mid = (int) $db->insertID();
            foreach ($kelIds as $kid) {
                $db->table('ronda_malam_keluarga')->insert(['malam_id' => $mid, 'keluarga_id' => (int) $kid]);
            }
            $dibuat++;
        }
        return ['dibuat' => $dibuat, 'periode' => $periode];
    }

    public function absenManual(int $malamId, int $keluargaId, int $userId): array
    {
        if ($this->malamTerkunci($malamId)) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        if (!$db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->countAllResults()) {
            return ['ok' => false, 'message' => 'Keluarga tidak bertugas malam ini.'];
        }
        if ($db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->where('dibatalkan', 0)->countAllResults()) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId,
            'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'),
            'sumber' => 'manual',
            'dicatat_oleh' => $userId,
            'dibatalkan' => 0,
        ]);
        (new AuditService())->log('absen_manual', 'ronda_absen', (int) $db->insertID(), null, ['malam_id' => $malamId, 'keluarga_id' => $keluargaId], 'pengguna', true, $userId);
        (new NotifikasiService())->keKeluarga($keluargaId, 'ronda', 'Absen ronda dicatat pengurus', 'Kehadiran ronda dicatat manual oleh pengurus.', '/ronda');
        return ['ok' => true];
    }

    public function absenWarga(int $malamId, int $keluargaId, string $fotoPath): array
    {
        if ($keluargaId < 1) return ['ok' => false, 'message' => 'Keluarga tidak valid.'];
        if ($fotoPath === '' || !preg_match('#^ronda/[a-zA-Z0-9._-]+$#', $fotoPath)) {
            return ['ok' => false, 'message' => 'Foto absen wajib (unggah dulu).'];
        }
        if ($this->malamTerkunci($malamId)) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        if ($m['tanggal'] !== date('Y-m-d')) return ['ok' => false, 'message' => 'Absen hanya bisa pada malam tugas hari ini.'];
        if (!$db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->countAllResults()) {
            return ['ok' => false, 'message' => 'Keluarga Anda tidak bertugas malam ini.'];
        }
        if ($db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->where('dibatalkan', 0)->countAllResults()) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        if (!$this->dalamJendelaAbsen($m['jam_mulai'], $m['jam_selesai'])) {
            return ['ok' => false, 'message' => 'Di luar jam ronda (toleransi ±30 menit).'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId,
            'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'),
            'foto' => $fotoPath,
            'sumber' => 'warga',
            'dicatat_oleh' => null,
            'dibatalkan' => 0,
        ]);
        $aid = (int) $db->insertID();
        try { (new AuditService())->log('absen_warga', 'ronda_absen', $aid, null, ['malam_id' => $malamId, 'keluarga_id' => $keluargaId], 'pengguna', true, null); } catch (\Throwable $e) {}
        try { (new NotifikasiService())->kePengurus('ronda', 'Absen ronda warga', 'Ada absen ronda dengan foto.', '/ronda/malam/' . $m['tanggal']); } catch (\Throwable $e) {}
        return ['ok' => true, 'data' => ['id' => $aid]];
    }

    public function gantiKeluargaMalam(int $malamId, array $keluargaIds, int $userId): array
    {
        if ($this->malamTerkunci($malamId)) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return ['ok' => false, 'message' => 'Malam tidak ditemukan.'];
        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        if (!$ids) return ['ok' => false, 'message' => 'Pilih minimal satu keluarga.'];
        $valid = $db->table('keluarga')->whereIn('id', $ids)->get()->getResultArray();
        $validIds = array_map(static fn ($r) => (int) $r['id'], $valid);
        if (count($validIds) !== count($ids)) return ['ok' => false, 'message' => 'Ada keluarga tidak ditemukan.'];
        $before = $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->get()->getResultArray();
        $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->delete();
        foreach ($validIds as $kid) {
            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $malamId, 'keluarga_id' => $kid]);
        }
        (new AuditService())->log('ganti_keluarga_malam', 'ronda_malam', $malamId, ['keluarga' => array_column($before, 'keluarga_id')], ['keluarga' => $validIds], 'pengguna', true, $userId);
        return ['ok' => true, 'data' => $this->detailMalam($m['tanggal'])];
    }

    public function batalkanAbsen(int $absenId, int $userId): array
    {
        $db = \Config\Database::connect();
        $a = $db->table('ronda_absen')->where('id', $absenId)->get()->getRowArray();
        if (!$a || (int) $a['dibatalkan'] === 1) return ['ok' => false, 'message' => 'Absen tidak ditemukan.'];
        if ($this->malamTerkunci((int) $a['malam_id'])) return ['ok' => false, 'message' => 'Absensi bulan ini sudah terkunci.'];
        $db->table('ronda_absen')->where('id', $absenId)->update([
            'dibatalkan' => 1,
            'dibatalkan_oleh' => $userId,
            'dibatalkan_pada' => date('Y-m-d H:i:s'),
        ]);
        (new AuditService())->log('batal_absen', 'ronda_absen', $absenId, $a, null, 'pengguna', true, $userId);
        (new NotifikasiService())->keKeluarga((int) $a['keluarga_id'], 'ronda', 'Absen ronda dibatalkan', 'Catatan absen ronda Anda dibatalkan pengurus.', '/ronda');
        return ['ok' => true];
    }

    public function terbitkanDenda(string $periodeLalu, string $periodeTagihan): array
    {
        $db = \Config\Database::connect();
        if ($this->bulanTerkunci($periodeLalu)) return ['dibuat' => 0, 'note' => 'sudah terkunci'];
        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $nominal = (int) ($peng['denda_ronda'] ?? 10000);
        $malam = $db->table('ronda_malam')->like('tanggal', $periodeLalu, 'after')->get()->getResultArray();
        $dibuat = 0;
        foreach ($malam as $m) {
            if ($m['tanggal'] >= date('Y-m-d')) continue;
            $kel = $db->table('ronda_malam_keluarga')->where('malam_id', $m['id'])->get()->getResultArray();
            foreach ($kel as $k) {
                $kid = (int) $k['keluarga_id'];
                if ($db->table('ronda_absen')->where('malam_id', $m['id'])->where('keluarga_id', $kid)->where('dibatalkan', 0)->countAllResults()) continue;
                if ($db->table('iuran_tagihan')->where('keluarga_id', $kid)->where('periode', $periodeTagihan)->where('jenis', 'denda_ronda')->where('ronda_malam_id', $m['id'])->where('dibatalkan', 0)->countAllResults()) continue;
                $db->table('iuran_tagihan')->insert([
                    'keluarga_id' => $kid,
                    'periode' => $periodeTagihan,
                    'jenis' => 'denda_ronda',
                    'ronda_malam_id' => (int) $m['id'],
                    'nominal' => $nominal,
                    'dibatalkan' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $dibuat++;
            }
        }
        $db->table('ronda_kunci_bulan')->replace(['periode' => $periodeLalu, 'dikunci_pada' => date('Y-m-d H:i:s')]);
        return ['dibuat' => $dibuat, 'periode_lalu' => $periodeLalu, 'periode_tagihan' => $periodeTagihan];
    }

    private function bulanTerkunci(string $periode): bool
    {
        $db = \Config\Database::connect();
        return (bool) $db->table('ronda_kunci_bulan')->where('periode', $periode)->get()->getRowArray();
    }

    private function malamTerkunci(int $malamId): bool
    {
        $db = \Config\Database::connect();
        $m = $db->table('ronda_malam')->where('id', $malamId)->get()->getRowArray();
        if (!$m) return true;
        return $this->bulanTerkunci(substr($m['tanggal'], 0, 7));
    }

    private function dalamJendelaAbsen(?string $jamMulai, ?string $jamSelesai): bool
    {
        $now = time();
        $today = date('Y-m-d');
        $mulai = $jamMulai ?: '21:00:00';
        $selesai = $jamSelesai ?: '00:00:00';
        $tMulai = strtotime($today . ' ' . $mulai);
        $tSelesai = strtotime($today . ' ' . $selesai);
        if ($tSelesai <= $tMulai) $tSelesai = strtotime('+1 day', $tSelesai);
        $tol = 30 * 60;
        return $now >= ($tMulai - $tol) && $now <= ($tSelesai + $tol);
    }
}
