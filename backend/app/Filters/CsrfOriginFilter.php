<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\ApiResponse;

/**
 * Proteksi CSRF untuk SPA cookie-session:
 * request mutasi (POST/PUT/PATCH/DELETE) harus berasal dari origin/host yang sama.
 */
class CsrfOriginFilter implements FilterInterface
{
    private array $skipPaths = [
        // health/setup status boleh dari mana saja (monitoring)
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $method = strtoupper($request->getMethod(true) ?: $request->getMethod());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        $path = trim($request->getUri()->getPath(), '/');
        // lewati cron (pakai token sendiri)
        if (str_starts_with($path, 'api/cron') || str_starts_with($path, 'cron')) {
            return null;
        }

        $host = strtolower((string) ($request->getServer('HTTP_HOST') ?: $request->getHeaderLine('Host')));
        $host = preg_replace('/:\d+$/', '', $host) ?: $host;
        if ($host === '') {
            return null;
        }

        $origin = trim((string) $request->getHeaderLine('Origin'));
        $referer = trim((string) $request->getHeaderLine('Referer'));

        if ($origin !== '') {
            $oh = $this->hostFromUrl($origin);
            if ($oh === null || !$this->hostMatches($oh, $host)) {
                return ApiResponse::fail('Origin ditolak', 403);
            }
            return null;
        }

        if ($referer !== '') {
            $rh = $this->hostFromUrl($referer);
            if ($rh === null || !$this->hostMatches($rh, $host)) {
                return ApiResponse::fail('Referer ditolak', 403);
            }
            return null;
        }

        // Tanpa Origin & Referer: tolak mutasi dari browser (cegah CSRF klasik).
        // Klien non-browser (curl) harus set Origin sendiri atau header khusus tidak dipakai.
        return ApiResponse::fail('Permintaan ditolak (CSRF)', 403);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function hostFromUrl(string $url): ?string
    {
        $h = parse_url($url, PHP_URL_HOST);
        if (!is_string($h) || $h === '') {
            return null;
        }
        return strtolower($h);
    }

    private function hostMatches(string $candidate, string $expected): bool
    {
        if ($candidate === $expected) {
            return true;
        }
        // izinkan subdomain langsung di bawah host yang sama (jarang dipakai)
        return str_ends_with($candidate, '.' . $expected);
    }
}
