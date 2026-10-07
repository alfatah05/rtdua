<?php

namespace App\Services;

/**
 * Pekerjaan cron: idempoten lewat tabel tugas_log (jenis + periode unik).
 */
class TugasService
{
    public function sudah(string $jenis, string $periode): bool
    {
        $db = \Config\Database::connect();
        $row = $db->table('tugas_log')->where('jenis', $jenis)->where('periode', $periode)->get()->getRowArray();
        return (bool) $row;
    }

    public function tandai(string $jenis, string $periode): void
    {
        $db = \Config\Database::connect();
        try {
            $db->table('tugas_log')->insert([
                'jenis'       => $jenis,
                'periode'     => $periode,
                'selesai_pada'=> date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // unique → sudah ada
        }
    }

    public function harian(): array
    {
        $hari = date('Y-m-d');
        $periode = date('Y-m');
        $out = ['periode' => $periode, 'langkah' => []];

        $kunciTagihan = 'tagihan_kas_' . $periode;
        if (!$this->sudah($kunciTagihan, $periode)) {
            try {
                $svc = new TagihanService();
                $r = $svc->pastikanTagihanKasBulan($periode);
                $out['langkah']['tagihan_kas'] = $r;
                $this->tandai($kunciTagihan, $periode);
            } catch (\Throwable $e) {
                $out['langkah']['tagihan_kas'] = ['error' => $e->getMessage()];
                log_message('error', 'Tugas harian tagihan: ' . $e->getMessage());
            }
        } else {
            $out['langkah']['tagihan_kas'] = 'skip (sudah)';
        }

        if ((int) date('j') === 1) {
            $kunciNotif = 'notif_tagihan_baru_' . $periode;
            if (!$this->sudah($kunciNotif, $periode)) {
                try {
                    $this->notifTagihanBulanBaru($periode);
                    $this->tandai($kunciNotif, $periode);
                    $out['langkah']['notif_tagihan'] = 'ok';
                } catch (\Throwable $e) {
                    $out['langkah']['notif_tagihan'] = $e->getMessage();
                }
            } else {
                $out['langkah']['notif_tagihan'] = 'skip';
            }
        }

        // Denda ronda bulan lalu → tagihan bulan ini (setelah jam 12 tgl 1)
        if ((int) date('j') === 1 && (int) date('G') >= 12) {
            $lalu = date('Y-m', strtotime('first day of last month'));
            $kunciDenda = 'denda_ronda_' . $lalu;
            if (!$this->sudah($kunciDenda, $lalu)) {
                try {
                    $r = (new RondaService())->terbitkanDenda($lalu, $periode);
                    $out['langkah']['denda_ronda'] = $r;
                    $this->tandai($kunciDenda, $lalu);
                } catch (\Throwable $e) {
                    $out['langkah']['denda_ronda'] = ['error' => $e->getMessage()];
                    log_message('error', 'Tugas denda ronda: ' . $e->getMessage());
                }
            } else {
                $out['langkah']['denda_ronda'] = 'skip (sudah)';
            }
        }

        // Pastikan malam ronda bulan ini ter-generate (idempoten per tanggal)
        $kunciMalam = 'generate_malam_' . $periode;
        if (!$this->sudah($kunciMalam, $periode)) {
            try {
                $r = (new RondaService())->generateBulan($periode);
                $out['langkah']['malam_ronda'] = $r;
                $this->tandai($kunciMalam, $periode);
            } catch (\Throwable $e) {
                $out['langkah']['malam_ronda'] = ['error' => $e->getMessage()];
            }
        } else {
            $out['langkah']['malam_ronda'] = 'skip (sudah)';
        }

        $kunciBersih = 'bersih_notif_' . $hari;
        if (!$this->sudah($kunciBersih, $hari)) {
            $n = (new NotifikasiService())->bersihkanLama(90);
            $this->tandai($kunciBersih, $hari);
            $out['langkah']['bersih_notif'] = $n;
        }

        $out['langkah']['bersih_bukti'] = $this->bersihFileLama('writable/uploads/bukti', 90);

        return $out;
    }

    public function sore(): array
    {
        $hari = date('Y-m-d');
        $kunci = 'pengingat_ronda_' . $hari;
        if ($this->sudah($kunci, $hari)) {
            return ['status' => 'skip', 'note' => 'sudah dijalankan hari ini'];
        }

        try {
            $malam = (new RondaService())->malamIni();
            if ($malam && !empty($malam['keluarga'])) {
                $notif = new NotifikasiService();
                foreach ($malam['keluarga'] as $k) {
                    if (($k['status'] ?? '') === 'hadir') {
                        continue;
                    }
                    $notif->keKeluarga(
                        (int) $k['keluarga_id'],
                        'ronda',
                        'Pengingat ronda malam ini',
                        'Keluarga Anda bertugas ronda malam ini. Jangan lupa absen.',
                        '/ronda'
                    );
                }
                $out = ['status' => 'ok', 'jumlah' => count($malam['keluarga'])];
            } else {
                $out = ['status' => 'ok', 'note' => 'tidak ada ronda malam ini'];
            }
        } catch (\Throwable $e) {
            $out = ['status' => 'error', 'note' => $e->getMessage()];
        }

        $this->tandai($kunci, $hari);
        return $out;
    }

    public function tiapLimaMenit(): array
    {
        return (new PushService())->ulangGagal(40);
    }

    private function notifTagihanBulanBaru(string $periode): void
    {
        $db = \Config\Database::connect();
        $kel = $db->table('keluarga')->where('status', 'aktif')->get()->getResultArray();
        $notif = new NotifikasiService();
        $label = $this->labelPeriode($periode);
        foreach ($kel as $k) {
            $notif->keKeluarga(
                (int) $k['id'],
                'tagihan_baru',
                'Tagihan bulan baru terbit',
                'Tagihan kas ' . $label . ' sudah tersedia.',
                '/keuangan'
            );
        }
    }

    private function labelPeriode(string $ym): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $p = explode('-', $ym);
        $y = (int) ($p[0] ?? date('Y'));
        $m = (int) ($p[1] ?? date('n'));
        return ($bulan[$m] ?? $ym) . ' ' . $y;
    }

    private function bersihFileLama(string $relDir, int $hari): array
    {
        $base = WRITEPATH . str_replace('writable/', '', $relDir);
        if (!is_dir($base)) {
            return ['hapus' => 0, 'note' => 'folder belum ada'];
        }
        $batas = time() - ($hari * 86400);
        $n = 0;
        foreach (glob($base . '/*') ?: [] as $f) {
            if (is_file($f) && filemtime($f) < $batas) {
                @unlink($f);
                $n++;
            }
        }
        return ['hapus' => $n];
    }
}
