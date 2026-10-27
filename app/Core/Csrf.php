<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf_token', $token);
        }
        return $token;
    }

    public static function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set('_csrf_token', $token);
        return $token;
    }

    public static function verify(?string $token): bool
    {
        $expected = Session::get('_csrf_token');
        if (!is_string($expected) || $expected === '' || $token === null || $token === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }
}
