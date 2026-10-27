<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . url($path), true, $status);
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $target = $fallback;
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (is_string($referer) && $referer !== '') {
            $path = parse_url($referer, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $base = base_path();
                if ($base !== '' && str_starts_with($path, $base)) {
                    $path = substr($path, strlen($base));
                }
                if ($path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//')) {
                    $target = $path;
                }
            }
        }
        self::redirect($target);
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }
}
