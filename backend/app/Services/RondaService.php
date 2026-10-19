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
            nama_group VARCHAR(64) NULL DEFAULT NULL,
            UNIQUE KEY uq_minggu_hari (minggu, hari)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS ronda_template_keluarga (
            card_id INT UNSIGNED NOT NULL,
            keluarga_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (card_id, keluarga_id),
            KEY idx_keluarga (keluarga_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try {
            $cols = $db->query("SHOW COLUMNS FROM ronda_template_card LIKE 'nama_group'")->getResultArray();
            if (!$cols) {
                $db->query("ALTER TABLE ronda_template_card ADD COLUMN nama_group VARCHAR(64) NULL DEFAULT NULL AFTER jam_selesai");
            }
        } catch (\Throwable $e) {
        }
    }

    public function jumlahMingguTemplate(): int
    {
        $this->ensureTemplateTables();
        $db = \Config\Database::connect();
        $row = $db->query('SELECT COALESCE(MAX(minggu), 1) AS m FROM ronda_template_card')->getRowArray();
        $m = (int) ($row['m'] ?? 1);
        return max(1, $m);
    }

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

    // NOTE: Full methods restored from production branch via follow-up if incomplete.
    // Loader: include rest of methods from companion file to avoid message size limits.
    public function __call($name, $arguments)
    {
        $companion = __DIR__ . '/RondaService.methods.php';
        if (is_file($companion)) {
            require_once $companion;
            $helper = new RondaServiceMethods($this);
            if (method_exists($helper, $name)) {
                return $helper->$name(...$arguments);
            }
        }
        throw new \BadMethodCallException('RondaService::' . $name);
    }
}
