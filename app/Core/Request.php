<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;

    public function __construct()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $allowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $this->method = in_array($method, $allowed, true) ? $method : 'GET';
        $this->path = self::capturePath();
    }

    public static function capturePath(): string
    {
        static $cached = null;
        if (is_string($cached)) {
            return $cached;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        if ($path === '' || $path === false) {
            $path = '/';
        }
        if ($path === '/index.php') {
            $path = '/';
        } elseif (str_starts_with($path, '/index.php/')) {
            $path = substr($path, strlen('/index.php'));
        }

        $cached = '/' . trim((string) $path, '/');
        if ($cached === '/') {
            $cached = '/';
        }
        return $cached;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);
        return is_scalar($value) ? (int) $value : $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        return is_array($file) && isset($file['error']) ? $file : null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $_SERVER[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    public function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        if (str_contains($accept, 'application/json')) {
            return true;
        }
        return ($this->header('X-Requested-With') ?? '') === 'XMLHttpRequest';
    }

    /**
     * Hanya mengizinkan path lokal sebagai tujuan pengalihan (mencegah open redirect).
     */
    public function safeRedirectPath(mixed $value, string $fallback = '/'): string
    {
        if (!is_string($value)) {
            return $fallback;
        }
        $value = trim($value);
        if ($value === '' || !str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return $fallback;
        }
        if (str_contains($value, "\n") || str_contains($value, "\r") || str_contains($value, '://')) {
            return $fallback;
        }
        return $value;
    }
}
