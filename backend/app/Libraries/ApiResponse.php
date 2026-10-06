<?php

namespace App\Libraries;

class ApiResponse
{
    public static function ok($data = null, string $message = 'OK', int $code = 200)
    {
        return self::json([
            'ok'      => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public static function fail(string $message, int $code = 400, $errors = null)
    {
        return self::json([
            'ok'      => false,
            'message' => $message,
            'errors'  => $errors,
            'data'    => null,
        ], $code);
    }

    private static function json(array $payload, int $code)
    {
        return service('response')
            ->setStatusCode($code)
            ->setJSON($payload);
    }
}
