<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\SideContext;
use App\Libraries\ApiResponse;
use App\Services\AuthService;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $side = SideContext::fromRequest();
        $user = (new AuthService())->current($side);
        if (!$user) {
            return ApiResponse::fail('Unauthorized', 401);
        }
        // Role guard opsional: arguments = ['ketua'] atau ['ketua','pengurus']
        if ($arguments && !in_array($user['role'], $arguments, true)) {
            return ApiResponse::fail('Forbidden', 403);
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
