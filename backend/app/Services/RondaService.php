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
     * Siklus kontinu (bukan reset tiap bulan kalender):
     * Sabtu ke-5 → Minggu 1, Sabtu pertama bulan berikutnya → Minggu 2.
     * Semua hari dalam minggu kalender yang sama memakai index minggu yang sama.
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

        // Senin 6 Jan 2020 = minggu ke-0 (anchor stabil)
        $epoch = strtotime('2020-01-06 12:00:00');
        $days = (int) floor(($ts - $epoch) / 86400);
        $weekIndex = (int) floor($days / 7);
        $mod = $weekIndex % $n;
        if ($mod < 0) {
            $mod += $n;
        }
        return $mod + 1;
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
            ->select('k.id, k.nomor, k.akhiran, b.nama as blok_nama')
            ->join('keluarga k', 'k.id = tk.keluarga_id')
            ->join('blok b', 'b.id = k.blok_id', 'left')
            ->where('tk.card_id', $cardId)
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $kep = $db->table('warga')
                ->where('keluarga_id', (int) $r['id'])
                ->where('status', 'aktif')
                ->where('hubungan', 'Kepala keluarga')
                ->get()->getRowArray();
            $out[] = [
                'id' => (int) $r['id'],
                'nama' => $kep['nama'] ?? '—',
                'alamat' => ($r['blok_nama'] ?? '') . '-' . ($r['nomor'] ?? '') . ($r['akhiran'] ?? ''),
                'foto' => $kep['foto'] ?? null,
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

    public function listJadwalTetap(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ronda_jadwal_tetap')->orderBy('hari', 'ASC')->get()->getResultArray();
        return array_map(static function ($r) {
            return [
                'id' => (int) $r['id'],
                'hari' => (int) $r['hari'],
                'jam_mulai' => $r['jam_mulai'],
                'jam_selesai' => $r['jam_selesai'],
            ];
        }, $rows);
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
            // 1 bulan = 4 card minggu (siklus 4 minggu, hari ke-5 pakai Minggu 1)
            $bulan = max(1, min(12, (int) $durasi));
            $nMinggu = $bulan * 4;
        }

        $oldCards = $db->table('ronda_template_card')->get()->getResultArray();
        $oldKel = [];
        foreach ($oldCards as $oc) {
            $key = ((int) $oc['minggu']) . '-' . ((int) $oc['hari']);
            $kids = $db->table('ronda_template_keluarga')->where('card_id', (int) $oc['id'])->get()->getResultArray();
            $oldKel[$key] = array_map(static fn ($x) => (int) $x['keluarga_id'], $kids);
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

        return [
            'ok' => true,
            'dibuat' => $dibuat,
            'minggu' => $nMinggu,
            'hari' => $hariReq,
            'cards' => $this->listTemplateCards(),
        ];
    }

    public function simpanTemplateKeluarga(int $cardId, array $keluargaIds, int $userId): array
    {
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        if ($cardId < 1) {
            return ['ok' => false, 'message' => 'Card tidak valid.'];
        }
        $card = $db->table('ronda_template_card')->where('id', $cardId)->get()->getRowArray();
        if (!$card) {
            return ['ok' => false, 'message' => 'Card tidak ditemukan.'];
        }
        $ids = array_values(array_unique(array_map('intval', $keluargaIds)));
        $ids = array_values(array_filter($ids, static fn ($x) => $x > 0));
        $db->table('ronda_template_keluarga')->where('card_id', $cardId)->delete();
        foreach ($ids as $kid) {
            $db->table('ronda_template_keluarga')->insert(['card_id' => $cardId, 'keluarga_id' => $kid]);
        }
        return [
            'ok' => true,
            'id' => $cardId,
            'keluarga_ids' => $ids,
            'keluarga' => $this->fetchCardKeluarga($cardId),
        ];
    }

    public function kalender(string $periode): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $periode = date('Y-m');
        }
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();

        $tetap = $db->table('ronda_jadwal_tetap')->get()->getResultArray();
        $active = [];
        $jamByHari = [];
        foreach ($tetap as $t) {
            $h = (int) $t['hari'];
            $active[$h] = true;
            $jamByHari[$h] = ['jam_mulai' => $t['jam_mulai'], 'jam_selesai' => $t['jam_selesai']];
        }

        $khususRows = $db->table('ronda_jadwal_khusus')->like('tanggal', $periode, 'after')->get()->getResultArray();
        $khususKids = [];
        foreach ($khususRows as $k) {
            $kids = $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', (int) $k['id'])->get()->getResultArray();
            $khususKids[$k['tanggal']] = array_map(static fn ($x) => (int) $x['keluarga_id'], $kids);
        }

        $y = (int) substr($periode, 0, 4);
        $m = (int) substr($periode, 5, 2);
        $daysInMonth = (int) date('t', strtotime(sprintf('%04d-%02d-01', $y, $m)));
        $out = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $y, $m, $d);
            if (isset($khususKids[$tgl]) || $db->table('ronda_jadwal_khusus')->where('tanggal', $tgl)->countAllResults() > 0) {
                $k = $db->table('ronda_jadwal_khusus')->where('tanggal', $tgl)->get()->getRowArray();
                if (!$k) {
                    continue;
                }
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
            $ids = $detail['keluarga_ids'] ?? [];
            if (in_array($keluargaId, $ids, true)) {
                return $detail;
            }
        }
        return null;
    }

    public function detailMalam(string $tanggal): ?array
    {
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return null;
        }

        $kh = $db->table('ronda_jadwal_khusus')->where('tanggal', $tanggal)->get()->getRowArray();
        $sumber = 'tetap';
        $jamM = '21:00:00';
        $jamS = '00:00:00';
        $keluargaIds = [];

        if ($kh) {
            $sumber = 'khusus';
            $jamM = $kh['jam_mulai'];
            $jamS = $kh['jam_selesai'];
            $kids = $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', (int) $kh['id'])->get()->getResultArray();
            $keluargaIds = array_map(static fn ($x) => (int) $x['keluarga_id'], $kids);
        } else {
            $w = (int) date('w', strtotime($tanggal . ' 12:00:00'));
            $tetap = $db->table('ronda_jadwal_tetap')->where('hari', $w)->get()->getRowArray();
            if (!$tetap) {
                return null;
            }
            $jamM = $tetap['jam_mulai'];
            $jamS = $tetap['jam_selesai'];
            $minggu = $this->mingguDariTanggal($tanggal);
            $card = $db->table('ronda_template_card')->where('minggu', $minggu)->where('hari', $w)->get()->getRowArray();
            if ($card) {
                $kids = $db->table('ronda_template_keluarga')->where('card_id', (int) $card['id'])->get()->getResultArray();
                $keluargaIds = array_map(static fn ($x) => (int) $x['keluarga_id'], $kids);
            }
        }

        $keluarga = [];
        foreach ($keluargaIds as $kid) {
            $k = $db->table('keluarga k')
                ->select('k.id, k.nomor, k.akhiran, b.nama as blok_nama')
                ->join('blok b', 'b.id = k.blok_id', 'left')
                ->where('k.id', $kid)
                ->get()->getRowArray();
            if (!$k) {
                continue;
            }
            $kep = $db->table('warga')
                ->where('keluarga_id', $kid)
                ->where('status', 'aktif')
                ->where('hubungan', 'Kepala keluarga')
                ->get()->getRowArray();
            $abs = $db->table('ronda_absen')->where('tanggal', $tanggal)->where('keluarga_id', $kid)->get()->getRowArray();
            $keluarga[] = [
                'id' => $kid,
                'nama' => $kep['nama'] ?? '—',
                'alamat' => ($k['blok_nama'] ?? '') . '-' . ($k['nomor'] ?? '') . ($k['akhiran'] ?? ''),
                'foto' => $kep['foto'] ?? null,
                'status_absen' => $abs['status'] ?? null,
                'waktu_absen' => $abs['waktu'] ?? null,
            ];
        }

        $periode = substr($tanggal, 0, 7);
        $kunci = $db->table('ronda_kunci_bulan')->where('periode', $periode)->get()->getRowArray();

        return [
            'tanggal' => $tanggal,
            'sumber' => $sumber,
            'jam_mulai' => $jamM,
            'jam_selesai' => $jamS,
            'keluarga_ids' => $keluargaIds,
            'keluarga' => $keluarga,
            'terkunci' => (bool) $kunci,
        ];
    }

    public function gantiKeluargaMalam(string $tanggal, array $keluargaIds, int $userId): array
    {
        $db = \Config\Database::connect();
        $ids = array_values(array_unique(array_map('intval', $keluargaIds)));
        $ids = array_values(array_filter($ids, static fn ($x) => $x > 0));

        $kh = $db->table('ronda_jadwal_khusus')->where('tanggal', $tanggal)->get()->getRowArray();
        if ($kh) {
            $db->table('ronda_jadwal_khusus_keluarga')->where('jadwal_id', (int) $kh['id'])->delete();
            foreach ($ids as $kid) {
                $db->table('ronda_jadwal_khusus_keluarga')->insert(['jadwal_id' => (int) $kh['id'], 'keluarga_id' => $kid]);
            }
        } else {
            $db->table('ronda_jadwal_khusus')->insert([
                'tanggal' => $tanggal,
                'jam_mulai' => '21:00:00',
                'jam_selesai' => '00:00:00',
                'catatan' => 'Ganti keluarga malam',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $jid = (int) $db->insertID();
            foreach ($ids as $kid) {
                $db->table('ronda_jadwal_khusus_keluarga')->insert(['jadwal_id' => $jid, 'keluarga_id' => $kid]);
            }
        }

        try {
            (new AuditService())->log('ganti_keluarga_ronda', 'ronda', null, null, [
                'tanggal' => $tanggal,
                'keluarga_ids' => $ids,
            ], 'pengguna', true, $userId);
        } catch (\Throwable $e) {
        }

        return ['ok' => true, 'data' => $this->detailMalam($tanggal)];
    }

    public function absen(string $tanggal, int $keluargaId, string $status, int $userId): array
    {
        if (!in_array($status, ['hadir', 'izin', 'alfa'], true)) {
            return ['ok' => false, 'message' => 'Status absen tidak valid.'];
        }
        $db = \Config\Database::connect();
        $exist = $db->table('ronda_absen')->where('tanggal', $tanggal)->where('keluarga_id', $keluargaId)->get()->getRowArray();
        if ($exist) {
            $db->table('ronda_absen')->where('id', (int) $exist['id'])->update([
                'status' => $status,
                'waktu' => date('Y-m-d H:i:s'),
                'oleh_user_id' => $userId,
            ]);
        } else {
            $db->table('ronda_absen')->insert([
                'tanggal' => $tanggal,
                'keluarga_id' => $keluargaId,
                'status' => $status,
                'waktu' => date('Y-m-d H:i:s'),
                'oleh_user_id' => $userId,
            ]);
        }
        return ['ok' => true, 'data' => $this->detailMalam($tanggal)];
    }

    public function terbitkanDenda(int $userId): array
    {
        $db = \Config\Database::connect();
        $periodeLalu = date('Y-m', strtotime('first day of last month'));
        $periodeTagihan = date('Y-m');
        $existKunci = $db->table('ronda_kunci_bulan')->where('periode', $periodeLalu)->get()->getRowArray();
        if ($existKunci) {
            return ['ok' => false, 'message' => 'Denda periode ' . $periodeLalu . ' sudah diterbitkan.'];
        }

        $peng = $db->table('pengaturan')->where('id', 1)->get()->getRowArray();
        $nominal = (int) ($peng['denda_ronda'] ?? 0);
        if ($nominal < 1) {
            return ['ok' => false, 'message' => 'Nominal denda ronda belum diatur.'];
        }

        $y = (int) substr($periodeLalu, 0, 4);
        $m = (int) substr($periodeLalu, 5, 2);
        $days = (int) date('t', strtotime($periodeLalu . '-01'));
        $dendaCount = 0;
        $totalNominal = 0;
        $notified = [];

        for ($d = 1; $d <= $days; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $y, $m, $d);
            $detail = $this->detailMalam($tgl);
            if (!$detail) {
                continue;
            }
            foreach ($detail['keluarga'] ?? [] as $k) {
                $st = $k['status_absen'] ?? null;
                if ($st === 'hadir' || $st === 'izin') {
                    continue;
                }
                $kid = (int) $k['id'];
                $exist = $db->table('iuran_tagihan')
                    ->where('keluarga_id', $kid)
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
