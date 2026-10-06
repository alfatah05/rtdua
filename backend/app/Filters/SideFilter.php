<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\SideContext;
use App\Libraries\ApiResponse;

class SideFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Pastikan sisi dikenali; tidak ada pemblokiran di sini kecuali path khusus
        SideContext::fromRequest();
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
