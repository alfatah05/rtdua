<?php

namespace App\Services;

class BazisService
{
    public const RTS = [
        'RT 001', 'RT 002', 'RT 003', 'RT 004',
        'RT 005', 'RT 006', 'RT 007', 'RT 008',
    ];

    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('bazis_setting')) {
            return;
        }
        $db->query("CREATE TABLE IF NOT EXISTS `bazis_setting` (
          `id` INT UNSIGNED NOT NULL DEFAULT 1,
          `label_bulan` VARCHAR(64) NOT NULL DEFAULT '',
          `rt_json` TEXT NULL,
          `updated_at` DATETIME NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function get(): array
    {
        $this->ensureTable();
        $db = \Config\Database::connect();
        $r = $db->table('bazis_setting')->where('id', 1)->get()->getRowArray();
        if (!$r) {
            return $this->defaultData();
        }
        $map = $this->decodeRt($r['rt_json'] ?? '');
        return [
            'label_bulan' => $r['label_bulan'] !== ''
                ? $r['label_bulan']
                : $this->defaultLabel(),
            'items' => $this->itemsFromMap($map),
            'updated_at' => $r['updated_at'] ?? null,
        ];
    }

    public function simpan(array $data, int $userId): array
    {
        $this->ensureTable();
        $label = trim((string) ($data['label_bulan'] ?? ''));
        if ($label === '') {
            $label = $this->defaultLabel();
        }
        $label = mb_substr($label, 0, 64);

        $map = [];
        $itemsIn = $data['items'] ?? $data['rt'] ?? [];
        if (is_array($itemsIn)) {
            $isList = array_keys($itemsIn) === range(0, count($itemsIn) - 1);
            if ($isList) {
                foreach ($itemsIn as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $rt = trim((string) ($row['rt'] ?? ''));
                    if ($rt === '') {
                        continue;
                    }
                    $map[$rt] = (int) ($row['total'] ?? $row['nominal'] ?? 0);
                }
            } else {
                foreach ($itemsIn as $rt => $val) {
                    $map[trim((string) $rt)] = (int) $val;
                }
            }
        }

        $normalized = [];
        foreach (self::RTS as $rt) {
            $normalized[$rt] = max(0, (int) ($map[$rt] ?? 0));
        }

        $db = \Config\Database::connect();
        $payload = [
            'id'          => 1,
            'label_bulan' => $label,
            'rt_json'     => json_encode($normalized, JSON_UNESCAPED_UNICODE),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        $exists = $db->table('bazis_setting')->where('id', 1)->get()->getRowArray();
        if ($exists) {
            $db->table('bazis_setting')->where('id', 1)->update($payload);
        } else {
            $db->table('bazis_setting')->insert($payload);
        }

        (new AuditService())->log(
            'ubah_bazis',
            'bazis_setting',
            1,
            $exists ?: null,
            $payload,
            'pengguna',
            true,
            $userId
        );

        return ['ok' => true, 'data' => $this->get()];
    }

    private function defaultLabel(): string
    {
        $bulan = (int) date('n');
        $tahun = date('Y');
        $nama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return 'Bazis ' . ($nama[$bulan] ?? '') . ' ' . $tahun;
    }

    private function defaultData(): array
    {
        $map = [];
        foreach (self::RTS as $rt) {
            $map[$rt] = 0;
        }
        return [
            'label_bulan' => $this->defaultLabel(),
            'items' => $this->itemsFromMap($map),
            'updated_at' => null,
        ];
    }

    private function decodeRt(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $d = json_decode($json, true);
        return is_array($d) ? $d : [];
    }

    private function itemsFromMap(array $map): array
    {
        $items = [];
        foreach (self::RTS as $rt) {
            $items[] = [
                'rt' => $rt,
                'total' => max(0, (int) ($map[$rt] ?? 0)),
            ];
        }
        return $items;
    }
}
