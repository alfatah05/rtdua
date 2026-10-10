<?php

namespace App\Services;

class RondaService
{
    private function ensureTemplateTables(): void
    {
        $db = \Config\Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS ronda_template_card (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            minggu TINYINT UNSIGNED NOT NULL,
            hari TINYINT UNSIGNED NOT NULL,
            jam_mulai TIME NOT NULL DEFAULT '21:00:00',
            jam_selesai TIME NOT NULL DEFAULT '00:00:00',
            UNIQUE KEY uq_minggu_hari (minggu, hari)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS ronda_template_keluarga (
            card_id INT UNSIGNED NOT NULL,
            keluarga_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (card_id, keluarga_id),
            KEY idx_keluarga (keluarga_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function mingguDariTanggal(string $tanggal): int
    {
        $day = (int) date('j', strtotime($tanggal));
        $w = (int) ceil($day / 7);
        if ($w > 4) {
            $w = (($w - 1) % 4) + 1;
        }
        return max(1, min(4, $w));
    }

    private function labelMinggu(int $m): string
    {
        $map = [1 => 'pertama', 2 => 'kedua', 3 => 'ketiga', 4 => 'keempat'];
        return 'Minggu ' . ($map[$m] ?? (string) $m);
    }

    private function labelHari(int $h): string
    {
        $map = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return $map[$h] ?? ('Hari ' . $h);
    }

    private function fetchCardKeluarga(int $cardId): array
    {
        $db = \Config\Database::connect();
        $kel = $db->table('ronda_template_keluarga tk')
            ->select('tk.keluarga_id, k.nomor, k.akhiran, b.nama as blok, a.nama as nama_kepala, a.foto')
            ->join('keluarga k', 'k.id = tk.keluarga_id')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->join('warga a', "a.keluarga_id = k.id AND a.status = 'aktif' AND a.hubungan = 'Kepala keluarga'", 'left')
            ->where('tk.card_id', $cardId)
            ->get()->getResultArray();
        $out = [];
        foreach ($kel as $k) {
            $out[] = [
                'keluarga_id' => (int) $k['keluarga_id'],
                'nama' => $k['nama_kepala'] ?? null,
                'alamat' => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
                'foto' => $k['foto'] ?? null,
            ];
        }
        return $out;
    }

    public function listJadwalTetap(): array
    {
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'hari' => (int) $r['hari'],
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
                'keluarga' => [],
            ];
        }
        return $out;
    }

    public function listTemplateCards(): array
    {
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_template_card')->orderBy('minggu', 'ASC')->orderBy('hari', 'ASC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $minggu = (int) $r['minggu'];
            $hari = (int) $r['hari'];
            $out[] = [
                'id' => (int) $r['id'],
                'minggu' => $minggu,
                'hari' => $hari,
                'label' => $this->labelMinggu($minggu) . ' - ' . $this->labelHari($hari),
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
                'keluarga' => $this->fetchCardKeluarga((int) $r['id']),
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
        } else {
            $db->table('ronda_jadwal_tetap')->insert(['hari' => $hari, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS]);
            $id = (int) $db->insertID();
        }
        return ['ok' => true, 'data' => ['id' => $id, 'hari' => $hari]];
    }

    public function buatTemplateCards(array $data, int $userId): array
    {
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        $hariReq = array_values(array_unique(array_map('intval', $data['hari'] ?? [])));
        $hariReq = array_values(array_filter($hariReq, static fn ($h) => $h >= 0 && $h <= 6));
        sort($hariReq);
        if (!$hariReq) {
            $tetap = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
            $hariReq = array_values(array_unique(array_map(static fn ($r) => (int) $r['hari'], $tetap)));
        }
        if (!$hariReq) {
            return ['ok' => false, 'message' => 'Pilih minimal satu hari ronda.'];
        }
        $jamM = $data['jam_mulai'] ?? '21:00:00';
        $jamS = $data['jam_selesai'] ?? '00:00:00';
        if (strlen($jamM) === 5) { $jamM .= ':00'; }
        if (strlen($jamS) === 5) { $jamS .= ':00'; }

        $existingHari = $db->table('ronda_jadwal_tetap')->get()->getResultArray();
        $existingSet = [];
        foreach ($existingHari as $eh) {
            $existingSet[(int) $eh['hari']] = (int) $eh['id'];
        }
        $wantSet = array_fill_keys($hariReq, true);
        foreach ($existingSet as $h => $id) {
            if (!isset($wantSet[$h])) {
                $db->table('ronda_jadwal_tetap_keluarga')->where('jadwal_id', $id)->delete();
                $db->table('ronda_jadwal_tetap')->where('id', $id)->delete();
            } else {
                $db->table('ronda_jadwal_tetap')->where('id', $id)->update(['jam_mulai' => $jamM, 'jam_selesai' => $jamS]);
            }
        }
        foreach ($hariReq as $h) {
            if (!isset($existingSet[$h])) {
                $db->table('ronda_jadwal_tetap')->insert(['hari' => $h, 'jam_mulai' => $jamM, 'jam_selesai' => $jamS]);
            }
        }

        $durasi = $data['durasi'] ?? '1';
        $nMinggu = 4;
        if ($durasi === 'minggu' || $durasi === 'week' || $durasi === '0') {
            $nMinggu = 1;
        }

        $oldCards = $db->table('ronda_template_card')->get()->getResultArray();
        $oldKel = [];
        foreach ($oldCards as $oc) {
            $key = ((int) $oc['minggu']) . '-' . ((int) $oc['hari']);
            $kids = $db->table('ronda_template_keluarga')->where('card_id', (int) $oc['id'])->get()->getResultArray();
            $oldKel[$key] = array_map(static fn ($x) => (int) $x['keluarga_id'], $kids);
        }
        foreach ($oldCards as $oc) {
            $db->table('ronda_template_keluarga')->where('card_id', (int) $oc['id'])->delete();
            $db->table('ronda_template_card')->where('id', (int) $oc['id'])->delete();
        }

        $dibuat = 0;
        for ($m = 1; $m <= $nMinggu; $m++) {
            foreach ($hariReq as $h) {
                $db->table('ronda_template_card')->insert([
                    'minggu' => $m,
                    'hari' => $h,
                    'jam_mulai' => $jamM,
                    'jam_selesai' => $jamS,
                ]);
                $cid = (int) $db->insertID();
                $key = $m . '-' . $h;
                foreach ($oldKel[$key] ?? [] as $kid) {
                    $db->table('ronda_template_keluarga')->insert(['card_id' => $cid, 'keluarga_id' => $kid]);
                }
                $dibuat++;
            }
        }

        return ['ok' => true, 'data' => [
            'dibuat' => $dibuat,
            'minggu' => $nMinggu,
            'hari' => $hariReq,
            'cards' => $this->listTemplateCards(),
        ]];
    }

    public function simpanTemplateKeluarga(int $cardId, array $keluargaIds, int $userId): array
    {
        $this->ensureTemplateTables();
        if ($cardId < 1) {
            return ['ok' => false, 'message' => 'Card tidak valid.'];
        }
        $db = \Config\Database::connect();
        $card = $db->table('ronda_template_card')->where('id', $cardId)->get()->getRowArray();
        if (!$card) {
            return ['ok' => false, 'message' => 'Card tidak ditemukan.'];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        $db->table('ronda_template_keluarga')->where('card_id', $cardId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_template_keluarga')->insert(['card_id' => $cardId, 'keluarga_id' => $kid]);
        }
        return ['ok' => true, 'data' => ['id' => $cardId, 'keluarga' => $this->fetchCardKeluarga($cardId)]];
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
        $keluargaIds = array_values(array_filter(array_map('intval', $data['keluarga_ids'] ?? []), static fn ($x) => $x > 0));
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
        $this->ensureTemplateTables();
        $tetap = $db->table('ronda_jadwal_tetap')->get()->getResultArray();
        $active = array_fill_keys(array_map(static fn ($r) => (int) $r['hari'], $tetap), true);
        $jamByHari = [];
        foreach ($tetap as $t) {
            $jamByHari[(int) $t['hari']] = $t;
        }
        $khususRows = $db->table('ronda_jadwal_khusus')->like('tanggal', $periode, 'after')->get()->getResultArray();
        $khususMap = [];
        foreach ($khususRows as $k) {
            $khususMap[$k['tanggal']] = $k;
        }
        $y = (int) substr($periode, 0, 4);
        $m = (int) substr($periode, 5, 2);
        $last = (int) date('t', strtotime(sprintf('%04d-%02d-01', $y, $m)));
        $out = [];
        for ($d = 1; $d <= $last; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $y, $m, $d);
            if (isset($khususMap[$tgl])) {
                $k = $khususMap[$tgl];
                $out[] = ['tanggal' => $tgl, 'sumber' => 'khusus', 'jam_mulai' => $k['jam_mulai'], 'jam_selesai' => $k['jam_selesai']];
                continue;
            }
            $w = (int) date('w', strtotime($tgl));
            if (isset($active[$w])) {
                $out[] = [
                    'tanggal' => $tgl,
                    'sumber' => 'tetap',
                    'jam_mulai' => $jamByHari[$w]['jam_mulai'] ?? '21:00:00',
                    'jam_selesai' => $jamByHari[$w]['jam_selesai'] ?? '00:00:00',
                ];
            }
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
        $tetap = $db->table('ronda_jadwal_tetap')->get()->getResultArray();
        $active = array_fill_keys(array_map(static fn ($r) => (int) $r['hari'], $tetap), true);
        if (!$active) {
            return null;
        }
        $kh = $db->table('ronda_jadwal_khusus')->where('tanggal >=', date('Y-m-d'))->orderBy('tanggal', 'ASC')->get()->getRowArray();
        $khTgl = $kh['tanggal'] ?? null;
        $t = strtotime(date('Y-m-d'));
        for ($i = 0; $i < 60; $i++) {
            $tgl = date('Y-m-d', $t + $i * 86400);
            if ($khTgl && $tgl === $khTgl) {
                return $this->detailMalam($tgl);
            }
            $w = (int) date('w', $t + $i * 86400);
            if (isset($active[$w])) {
                if ($khTgl && $tgl > $khTgl) {
                    return $this->detailMalam($khTgl);
                }
                return $this->detailMalam($tgl);
            }
        }
        return $khTgl ? $this->detailMalam($khTgl) : null;
    }

    public function detailMalam(string $tanggal): ?array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return null;
        }
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        $khusus = $db->table('ronda_jadwal_khusus')->where('tanggal', $tanggal)->get()->getRowArray();
        $tetap = $db->table('ronda_jadwal_tetap')->get()->getResultArray();
        $active = array_fill_keys(array_map(static fn ($r) => (int) $r['hari'], $tetap), true);
        $w = (int) date('w', strtotime($tanggal));
        if (!$khusus && !isset($active[$w])) {
            $m0 = $db->table('ronda_malam')->where('tanggal', $tanggal)->get()->getRowArray();
            if (!$m0) {
                return null;
            }
        }
        $sumber = $khusus ? 'khusus' : 'tetap';
        $jamM = '21:00:00';
        $jamS = '00:00:00';
        if ($khusus) {
            $jamM = $khusus['jam_mulai'];
            $jamS = $khusus['jam_selesai'];
        } else {
            foreach ($tetap as $t) {
                if ((int) $t['hari'] === $w) {
                    $jamM = $t['jam_mulai'];
                    $jamS = $t['jam_selesai'];
                    break;
                }
            }
        }
        $m = $db->table('ronda_malam')->where('tanggal', $tanggal)->get()->getRowArray();
        if (!$m) {
            $db->table('ronda_malam')->insert([
                'tanggal' => $tanggal,
                'jam_mulai' => $jamM,
                'jam_selesai' => $jamS,
                'sumber' => $sumber,
            ]);
            $m = $db->table('ronda_malam')->where('tanggal', $tanggal)->get()->getRowArray();
        }
        $mid = (int) $m['id'];
        $keluarga = [];
        if ($khusus) {
            $kel = $db->table('ronda_jadwal_khusus_keluarga jk')
                ->select('jk.keluarga_id, k.nomor, k.akhiran, b.nama as blok, a.nama as nama_kepala, a.foto')
                ->join('keluarga k', 'k.id = jk.keluarga_id')
                ->join('blok b', 'b.id = k.blok_id', 'left')
                ->join('warga a', "a.keluarga_id = k.id AND a.status = 'aktif' AND a.hubungan = 'Kepala keluarga'", 'left')
                ->where('jk.jadwal_id', (int) $khusus['id'])
                ->get()->getResultArray();
            foreach ($kel as $k) {
                $keluarga[] = [
                    'keluarga_id' => (int) $k['keluarga_id'],
                    'nama' => $k['nama_kepala'] ?? null,
                    'alamat' => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
                    'foto' => $k['foto'] ?? null,
                ];
            }
        } else {
            $minggu = $this->mingguDariTanggal($tanggal);
            $card = $db->table('ronda_template_card')->where('minggu', $minggu)->where('hari', $w)->get()->getRowArray();
            if ($card) {
                $keluarga = $this->fetchCardKeluarga((int) $card['id']);
                $db->table('ronda_malam_keluarga')->where('malam_id', $mid)->delete();
                foreach ($keluarga as $kk) {
                    $db->table('ronda_malam_keluarga')->insert(['malam_id' => $mid, 'keluarga_id' => $kk['keluarga_id']]);
                }
            }
        }
        $absen = $db->table('ronda_absen')->where('malam_id', $mid)->where('dibatalkan', 0)->get()->getResultArray();
        $bulanTerkunci = $this->bulanTerkunci(substr($tanggal, 0, 7));
        return [
            'id' => $mid,
            'tanggal' => $tanggal,
            'jam_mulai' => $m['jam_mulai'] ?? $jamM,
            'jam_selesai' => $m['jam_selesai'] ?? $jamS,
            'sumber' => $sumber,
            'keluarga' => $keluarga,
            'absen' => array_map(static fn ($a) => [
                'id' => (int) $a['id'],
                'keluarga_id' => (int) $a['keluarga_id'],
                'foto' => $a['foto'] ?? null,
                'waktu' => $a['waktu_server'] ?? null,
            ], $absen),
            'boleh_hadir' => !$bulanTerkunci && $tanggal <= date('Y-m-d'),
            'bulan_terkunci' => $bulanTerkunci,
        ];
    }

    public function generateBulan(string $periode): array
    {
        return $this->buatTemplateCards(['durasi' => '1'], 0);
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
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId, 'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'), 'foto' => null,
            'sumber' => 'manual', 'dicatat_oleh' => $userId, 'dibatalkan' => 0,
        ]);
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
        $this->detailMalam($m['tanggal']);
        if ($db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->countAllResults() < 1) {
            return ['ok' => false, 'message' => 'Keluarga tidak bertugas malam ini.'];
        }
        if ($db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->countAllResults() > 0) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId, 'keluarga_id' => $keluargaId,
            'waktu_server' => date('Y-m-d H:i:s'), 'foto' => $foto ?: null,
            'sumber' => 'warga', 'dibatalkan' => 0,
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
        if (!$m || $this->bulanTerkunci(substr($m['tanggal'], 0, 7))) {
            return ['ok' => false, 'message' => 'Tidak bisa mengubah.'];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $malamId, 'keluarga_id' => $kid]);
        }
        $this->ensureTemplateTables();
        $minggu = $this->mingguDariTanggal($m['tanggal']);
        $w = (int) date('w', strtotime($m['tanggal']));
        $card = $db->table('ronda_template_card')->where('minggu', $minggu)->where('hari', $w)->get()->getRowArray();
        if ($card) {
            $this->simpanTemplateKeluarga((int) $card['id'], $ids, $userId);
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
        return $this->buatTemplateCards($data, $userId);
    }

    public function hapusJadwalKeDepan(array $data, int $userId): array
    {
        return ['ok' => false, 'message' => 'Menu hapus jadwal sudah dihapus.'];
    }

    public function isiOtomatis(array $data, int $userId): array
    {
        return ['ok' => false, 'message' => 'Fitur isi otomatis sudah dihapus. Isi warga lewat card jadwal tetap.'];
    }

    private function bulanTerkunci(string $periode): bool
    {
        $db = \Config\Database::connect();
        return $db->table('ronda_kunci_bulan')->where('periode', $periode)->countAllResults() > 0;
    }
}
