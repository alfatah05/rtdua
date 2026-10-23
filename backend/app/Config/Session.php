<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\FileHandler;

class Session extends BaseConfig
{
    public string $driver = FileHandler::class;

    /**
     * Nama default; diganti per request di SideFilter
     * menjadi sesi_warga atau sesi_pengurus.
     */
    public string $cookieName = 'sesi_warga';

    /**
     * Lama sesi login (detik).
     * 2592000 = 30 hari — biar warga/pengurus jarang login ulang.
     * Sebelumnya 7200 (2 jam).
     */
    public int $expiration = 2592000;

    public string $savePath = WRITEPATH . 'session';
    public bool $matchIP = false;

    /** Regenerasi ID sesi tiap 1 hari (keamanan), tanpa memutus login. */
    public int $timeToUpdate = 86400;

    public bool $regenerateDestroy = false;

    // Properti wajib CI4.5+ (hindari Error di PHP 8 saat session start)
    public ?string $cookieDomain = '';
    public string $cookiePath = '/';
    /**
     * Secure cookie: true jika request HTTPS (production).
     * Di konstruktor diset agar cocok shared hosting + proxy.
     */
    public bool $cookieSecure = false;
    public bool $cookieHTTPOnly = true;
    public string $cookieSameSite = 'Lax';

    public function __construct()
    {
        parent::__construct();
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
        $this->cookieSecure = $https;
    }
}
