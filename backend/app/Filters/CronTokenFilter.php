<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proteksi endpoint cron: header X-Cron-Token harus sama dengan env CRON_TOKEN.
 */
class CronTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $expected = (string) env('CRON_TOKEN', '');
        if ($expected === '') {
            return service('response')
                ->setStatusCode(503)
                ->setJSON(['ok' => false, 'message' => 'CRON_TOKEN belum diset di .env']);
        }

        $got = (string) ($request->getHeaderLine('X-Cron-Token') ?: $request->getGet('token') ?: '');
        if (!hash_equals($expected, $got)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['ok' => false, 'message' => 'Token cron tidak valid']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
