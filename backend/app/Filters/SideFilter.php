<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\SideContext;

/**
 * Mengenali sisi (warga|pengurus) dan menyetel nama cookie sesi
 * menjadi sesi_warga / sesi_pengurus sebelum session dipakai.
 */
class SideFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $side = SideContext::fromRequest();

        // Cookie terpisah per sisi (dokumen 01) — set sebelum session start
        $sessionConfig = config('Session');
        if ($sessionConfig) {
            $sessionConfig->cookieName = SideContext::cookieName($side);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
