<?php

namespace App\Libraries;

class SideContext
{
    public static function fromRequest(): string
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        // Thin proxy may pass X-App-Side
        $header = strtolower($_SERVER['HTTP_X_APP_SIDE'] ?? '');
        if (in_array($header, ['warga', 'pengurus'], true)) {
            return $header;
        }
        if (str_contains($host, 'pengurus')) {
            return 'pengurus';
        }
        if (str_contains($host, 'warga')) {
            return 'warga';
        }
        // Fallback query (local only)
        $q = strtolower($_GET['side'] ?? '');
        if (in_array($q, ['warga', 'pengurus'], true)) {
            return $q;
        }
        return 'warga';
    }

    public static function cookieName(string $side): string
    {
        return $side === 'pengurus' ? 'sesi_pengurus' : 'sesi_warga';
    }
}
