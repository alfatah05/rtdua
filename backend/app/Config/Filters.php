<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;

class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf'          => \CodeIgniter\Filters\CSRF::class,
        'toolbar'       => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot'      => \CodeIgniter\Filters\Honeypot::class,
        'invalidchars'  => \CodeIgniter\Filters\InvalidChars::class,
        'secureheaders' => \CodeIgniter\Filters\SecureHeaders::class,
        'cors'          => \CodeIgniter\Filters\Cors::class,
        'forcehttps'    => \CodeIgniter\Filters\ForceHTTPS::class,
        'pagecache'     => \CodeIgniter\Filters\PageCache::class,
        'performance'   => \CodeIgniter\Filters\PerformanceMetrics::class,
        'side'          => \App\Filters\SideFilter::class,
        'auth'          => \App\Filters\AuthFilter::class,
        'cron'          => \App\Filters\CronTokenFilter::class,
        'secheaders'    => \App\Filters\SecurityHeadersFilter::class,
        'csrforigin'    => \App\Filters\CsrfOriginFilter::class,
    ];

    public array $required = [
        'before' => [],
        'after'  => [],
    ];

    public array $globals = [
        'before' => [
            'side',
            'csrforigin',
        ],
        'after' => [
            'secheaders',
        ],
    ];

    public array $methods = [];

    public array $filters = [];
}
