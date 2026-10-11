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

    public function jumlahMingguTemplate(): int
    {
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        $row = $db->query('SELECT COALESCE(MAX(minggu), 1) AS m FROM ronda_template_card')->getRowArray();
        $m = (int) ($row['m'] ?? 1);
        return max(1, $m);
    }

    /**
     * Index minggu template (1..N) untuk tanggal.
     * Urutan kemunculan hari DI BULAN KALENDER ini (Jumat ke-1 = Minggu 1).
     */
    public function mingguDariTanggal(string $tanggal): int
    {
        $n = $this->jumlahMingguTemplate();
        if ($n < 1) {
            return 1;
        }
        $ts = strtotime($tanggal . ' 12:00:00');
        if ($ts === false) {
            return 1;
        }

        $y = (int) date('Y', $ts);
        $m = (int) date('n', $ts);
        $day = (int) date('j', $ts);
        $dow = (int) date('w', $ts);

        $count = 0;
        for ($d = 1; $d <= $day; $d++) {
            $t = strtotime(sprintf('%04d-%02d-%02d 12:00:00', $y, $m, $d));
            if ((int) date('w', $t) === $dow) {
                $count++;
            }
        }
        if ($count < 1) {
            return 1;
        }
        return (($count - 1) % $n) + 1;
    }

    private function labelMinggu(int $m): string
    {
        return 'Minggu ' . $m;
    }

    private function labelHari(int $h): string
    {
        $map = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return $map[$h] ?? ('Hari ' . $h);
    }

    private function fetchCardKeluarga(int $cardId): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_template_keluarga tk')
            ->select('tk.keluarga_id, k.nomor, k.akhiran, b.nama as blok')
            ->join('keluarga k', 'k.id = tk.keluarga_id', 'left')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('tk.card_id', $cardId)
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $k) {
            $kid = (int) $k['keluarga_id'];
            $nama = null;
            $foto = null;
            $kep = $db->table('warga')
                ->select('nama, foto, hubungan')
                ->where('keluarga_id', $kid)
                ->where('status', 'aktif')
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
            foreach ($kep as $w) {
                $hub = strtolower((string) ($w['hubungan'] ?? ''));
                if (str_contains($hub, 'kepala')) {
                    $nama = $w['nama'];
                    $foto = $w['foto'] ?? null;
                    break;
                }
            }
            if ($nama === null && $kep) {
                $nama = $kep[0]['nama'] ?? null;
                $foto = $kep[0]['foto'] ?? null;
            }
            $out[] = [
                'keluarga_id' => $kid,
                'nama' => $nama,
                'alamat' => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
                'foto' => $foto,
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
        if ($durasi === 'minggu' || $durasi === 'week' || $durasi === '0') {
            $nMinggu = 1;
        } else {
            $bulan = max(1, min(12, (int) $durasi));
            $nMinggu = $bulan * 5;
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
        if ($ids) {
            $valid = $db->table('keluarga')->select('id')->whereIn('id', $ids)->get()->getResultArray();
            $ids = array_values(array_map(static fn ($r) => (int) $r['id'], $valid));
        }
        $db->table('ronda_template_keluarga')->where('card_id', $cardId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_template_keluarga')->insert(['card_id' => $cardId, 'keluarga_id' => $kid]);
        }
        return ['ok' => true, 'data' => [
            'id' => $cardId,
            'jumlah' => count($ids),
            'keluarga' => $this->fetchCardKeluarga($cardId),
        ]];
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
        $khususKids = [];
        foreach ($khususRows as $k) {
            $khususMap[$k['tanggal']] = $k;
            $kids = $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', (int) $k['id'])->get()->getResultArray();
            $khususKids[$k['tanggal']] = array_map(static fn ($x) => (int) $x['keluarga_id'], $kids);
        }
        $y = (int) substr($periode, 0, 4);
        $m = (int) substr($periode, 5, 2);
        $last = (int) date('t', strtotime(sprintf('%04d-%02d-01 12:00:00', $y, $m)));
        $out = [];
        for ($d = 1; $d <= $last; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $y, $m, $d);
            if (isset($khususMap[$tgl])) {
                $k = $khususMap[$tgl];
                $out[] = [
                    'tanggal' => $tgl,
                    'sumber' => 'khusus',
                    'jam_mulai' => $k['jam_mulai'],
                    'jam_selesai' => $k['jam_selesai'],
                    'keluarga_ids' => $khususKids[$tgl] ?? [],
                ];
                continue;
            }
            $w = (int) date('w', strtotime($tgl . ' 12:00:00'));
            if (isset($active[$w])) {
                $minggu = $this->mingguDariTanggal($tgl);
                $card = $db->table('ronda_template_card')->where('minggu', $minggu)->where('hari', $w)->get()->getRowArray();
                $ids = [];
                if ($card) {
                    $kids = $db->table('ronda_template_keluarga')->where('card_id', (int) $card['id'])->get()->getResultArray();
                    $ids = array_map(static fn ($x) => (int) $x['keluarga_id'], $kids);
                }
                $out[] = [
                    'tanggal' => $tgl,
                    'sumber' => 'tetap',
                    'jam_mulai' => $jamByHari[$w]['jam_mulai'] ?? '21:00:00',
                    'jam_selesai' => $jamByHari[$w]['jam_selesai'] ?? '00:00:00',
                    'keluarga_ids' => $ids,
                ];
            }
        }
        return $out;
    }

    public function malamIni(): ?array
    {
        return $this->detailMalam(date('Y-m-d'));
    }

    public function malamTerdekat(?int $keluargaId = null): ?array
    {
        $db = \Config\Database::connect();
        $this->ensureTemplateTables();
        $tetap = $db->table('ronda_jadwal_tetap')->get()->getResultArray();
        $active = array_fill_keys(array_map(static fn ($r) => (int) $r['hari'], $tetap), true);
        $khRows = $db->table('ronda_jadwal_khusus')->where('tanggal >=', date('Y-m-d'))->orderBy('tanggal', 'ASC')->get()->getResultArray();
        $khSet = [];
        foreach ($khRows as $kh) {
            $khSet[$kh['tanggal']] = true;
        }
        if (!$active && !$khSet) {
            return null;
        }
        $t = strtotime(date('Y-m-d'));
        for ($i = 0; $i < 90; $i++) {
            $tgl = date('Y-m-d', $t + $i * 86400);
            $w = (int) date('w', $t + $i * 86400);
            $isRonda = isset($khSet[$tgl]) || isset($active[$w]);
            if (!$isRonda) {
                continue;
            }
            $detail = $this->detailMalam($tgl);
            if (!$detail) {
                continue;
            }
            if ($keluargaId === null || $keluargaId < 1) {
                return $detail;
            }
            foreach ($detail['keluarga'] ?? [] as $k) {
                if ((int) ($k['keluarga_id'] ?? 0) === $keluargaId) {
                    return $detail;
                }
            }
        }
        return null;
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
        $w = (int) date('w', strtotime($tanggal . ' 12:00:00'));
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
                ->select('jk.keluarga_id, k.nomor, k.akhiran, b.nama as blok')
                ->join('keluarga k', 'k.id = jk.keluarga_id', 'left')
                ->join('blok b', 'b.id = k.blok_id', 'left')
                ->where('jk.jadwal_id', (int) $khusus['id'])
                ->get()->getResultArray();
            foreach ($kel as $k) {
                $kid = (int) $k['keluarga_id'];
                $nama = null;
                $foto = null;
                $kep = $db->table('warga')->select('nama, foto, hubungan')->where('keluarga_id', $kid)->where('status', 'aktif')->orderBy('id', 'ASC')->get()->getResultArray();
                foreach ($kep as $ww) {
                    if (str_contains(strtolower((string) ($ww['hubungan'] ?? '')), 'kepala')) {
                        $nama = $ww['nama'];
                        $foto = $ww['foto'] ?? null;
                        break;
                    }
                }
                if ($nama === null && $kep) {
                    $nama = $kep[0]['nama'] ?? null;
                    $foto = $kep[0]['foto'] ?? null;
                }
                $keluarga[] = [
                    'keluarga_id' => $kid,
                    'nama' => $nama,
                    'alamat' => ($k['blok'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
                    'foto' => $foto,
                ];
            }
        } else {
            $minggu = $this->mingguDariTanggal($tanggal);
            $card = $db->table('ronda_template_card')->where('minggu', $minggu)->where('hari', $w)->get()->getRowArray();
            if ($card) {
                $keluarga = $this->fetchCardKeluarga((int) $card['id']);
            }
        }

        $absenRows = $db->table('ronda_absen')->where('malam_id', $mid)->get()->getResultArray();
        $absenByKel = [];
        foreach ($absenRows as $a) {
            $absenByKel[(int) $a['keluarga_id']] = $a;
        }
        $periode = substr($tanggal, 0, 7);
        $terkunci = (bool) $db->table('ronda_kunci_bulan')->where('periode', $periode)->get()->getRowArray();

        $keluargaOut = [];
        foreach ($keluarga as $kk) {
            $kid = (int) $kk['keluarga_id'];
            $ab = $absenByKel[$kid] ?? null;
            $keluargaOut[] = [
                'keluarga_id' => $kid,
                'nama' => $kk['nama'] ?? null,
                'alamat' => $kk['alamat'] ?? '',
                'foto' => $kk['foto'] ?? null,
                'status' => $ab ? 'hadir' : 'belum',
                'absen' => $ab ? [
                    'id' => (int) $ab['id'],
                    'via' => $ab['via'] ?? 'manual',
                    'waktu' => $ab['created_at'] ?? null,
                    'foto' => $ab['foto'] ?? null,
                ] : null,
            ];
        }

        return [
            'id' => $mid,
            'tanggal' => $tanggal,
            'jam_mulai' => $jamM,
            'jam_selesai' => $jamS,
            'sumber' => $sumber,
            'keluarga' => $keluargaOut,
            'terkunci' => $terkunci,
            'bulan_terkunci' => $terkunci,
        ];
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
        $tanggal = $m['tanggal'];
        $ids = array_values(array_unique(array_filter(array_map('intval', $keluargaIds), static fn ($x) => $x > 0)));
        if ($ids) {
            $valid = $db->table('keluarga')->select('id')->whereIn('id', $ids)->get()->getResultArray();
            $ids = array_values(array_map(static fn ($r) => (int) $r['id'], $valid));
        }
        $db->table('ronda_malam_keluarga')->where('malam_id', $malamId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_malam_keluarga')->insert(['malam_id' => $malamId, 'keluarga_id' => $kid]);
        }
        $khusus = $db->table('ronda_jadwal_khusus')->where('tanggal', $tanggal)->get()->getRowArray();
        if ($khusus) {
            $jid = (int) $khusus['id'];
            $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', $jid)->delete();
            foreach ($ids as $kid) {
                $db->table('ronda_jadwal_khusus_keluarga')->insert(['jadwal_id' => $jid, 'keluarga_id' => $kid]);
            }
        } else {
            $w = (int) date('w', strtotime($tanggal . ' 12:00:00'));
            $minggu = $this->mingguDariTanggal($tanggal);
            $card = $db->table('ronda_template_card')->where('minggu', $minggu)->where('hari', $w)->get()->getRowArray();
            if ($card) {
                $cid = (int) $card['id'];
                $db->table('ronda_template_keluarga')->where('card_id', $cid)->delete();
                foreach ($ids as $kid) {
                    $db->table('ronda_template_keluarga')->insert(['card_id' => $cid, 'keluarga_id' => $kid]);
                }
            }
        }
        return ['ok' => true, 'data' => $this->detailMalam($tanggal)];
    }

    public function absenManual(int $malamId, int $keluargaId, int $userId): array
    {
        if ($malamId < 1 || $keluargaId < 1) {
            return ['ok' => false, 'message' => 'Data tidak valid.'];
        }
        $db = \Config\Database::connect();
        $exist = $db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->get()->getRowArray();
        if ($exist) {
            return ['ok' => true];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId,
            'keluarga_id' => $keluargaId,
            'via' => 'manual',
            'created_at' => date('Y-m-d H:i:s'),
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
        $exist = $db->table('ronda_absen')->where('malam_id', $malamId)->where('keluarga_id', $keluargaId)->get()->getRowArray();
        if ($exist) {
            return ['ok' => false, 'message' => 'Sudah absen.'];
        }
        $db->table('ronda_absen')->insert([
            'malam_id' => $malamId,
            'keluarga_id' => $keluargaId,
            'via' => 'foto',
            'foto' => $foto,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'data' => ['id' => (int) $db->insertID()]];
    }

    public function batalkanAbsen(int $absenId, int $userId): array
    {
        $db = \Config\Database::connect();
        $db->table('ronda_absen')->where('id', $absenId)->delete();
        return ['ok' => true];
    }

    public function terbitkanDenda(string $periodeLalu, string $periodeTagihan): array
    {
        $db = \Config\Database::connect();
        if (!preg_match('/^\d{4}-\d{2}$/', $periodeLalu) || !preg_match('/^\d{4}-\d{2}$/', $periodeTagihan)) {
            return ['ok' => false, 'message' => 'Format periode YYYY-MM.'];
        }
        $locked = $db->table('ronda_kunci_bulan')->where('periode', $periodeLalu)->get()->getRowArray();
        if ($locked) {
            return ['ok' => false, 'message' => 'Periode sudah dikunci.'];
        }
        $settings = $db->table('pengaturan')->where('kunci', 'denda_ronda')->get()->getRowArray();
        $nominal = (int) ($settings['nilai'] ?? 25000);
        if ($nominal < 1) {
            $nominal = 25000;
        }
        $start = $periodeLalu . '-01';
        $end = date('Y-m-t', strtotime($start));
        $malams = $db->table('ronda_malam')->where('tanggal >=', $start)->where('tanggal <=', $end)->get()->getResultArray();
        $dendaCount = 0;
        $totalNominal = 0;
        $notified = [];
        foreach ($malams as $malam) {
            $mid = (int) $malam['id'];
            $tgl = $malam['tanggal'];
            $detail = $this->detailMalam($tgl);
            if (!$detail) {
                continue;
            }
            foreach ($detail['keluarga'] ?? [] as $kk) {
                if (($kk['status'] ?? '') === 'hadir') {
                    continue;
                }
                $kid = (int) $kk['keluarga_id'];
                $exist = $db->table('iuran_tagihan')
                    ->where('keluarga_id', $kid)
                    ->where('periode', $periodeTagihan)
                    ->where('jenis', 'denda_ronda')
                    ->where('ref_tanggal', $tgl)
                    ->get()->getRowArray();
                if ($exist) {
                    continue;
                }
                $db->table('iuran_tagihan')->insert([
                    'keluarga_id' => $kid,
                    'periode' => $periodeTagihan,
                    'jenis' => 'denda_ronda',
                    'nominal' => $nominal,
                    'status' => 'belum',
                    'ref_tanggal' => $tgl,
                    'keterangan' => 'Denda ronda ' . $tgl,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $dendaCount++;
                $totalNominal += $nominal;
                $notified[$kid] = true;
            }
        }
        $db->table('ronda_kunci_bulan')->insert([
            'periode' => $periodeLalu,
            'dikunci_at' => date('Y-m-d H:i:s'),
        ]);
        try {
            $notif = new NotifikasiService();
            foreach (array_keys($notified) as $kid) {
                $u = $db->table('users')->where('keluarga_id', $kid)->where('role', 'warga')->get()->getRowArray();
                if ($u) {
                    $notif->kirim(
                        (int) $u['id'],
                        'denda_ronda',
                        'Denda ronda',
                        'Ada denda ronda pada tagihan periode ' . $periodeTagihan . '.',
                        '/keuangan'
                    );
                }
            }
        } catch (\Throwable $e) {
        }
        return [
            'ok' => true,
            'denda' => $dendaCount,
            'total_nominal' => $totalNominal,
            'nominal_satuan' => $nominal,
            'periode_lalu' => $periodeLalu,
            'periode_tagihan' => $periodeTagihan,
        ];
    }

    public function generateBulan(string $periode): array
    {
        return ['ok' => true, 'data' => $this->kalender($periode)];
    }

    public function isiOtomatis(array $data, int $userId): array
    {
        return ['ok' => false, 'message' => 'Fitur isi otomatis sudah dihapus. Pakai template card.'];
    }

    public function buatSlotKosong(array $data, int $userId): array
    {
        return $this->buatTemplateCards(['durasi' => '1'], 0);
    }

    public function hapusJadwalKeDepan(array $data, int $userId): array
    {
        return ['ok' => false, 'message' => 'Menu hapus jadwal massal sudah dihapus.'];
    }
}
