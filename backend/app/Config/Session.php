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

    public int $expiration = 7200;
    public string $savePath = WRITEPATH . 'session';
    public bool $matchIP = false;
    public int $timeToUpdate = 300;
    public bool $regenerateDestroy = false;

    // Properti wajib CI4.5+ (hindari Error di PHP 8 saat session start)
    public ?string $cookieDomain = '';
    public string $cookiePath = '/';
    public bool $cookieSecure = false;
    public bool $cookieHTTPOnly = true;
    public string $cookieSameSite = 'Lax';
}
