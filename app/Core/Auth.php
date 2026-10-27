<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;

        $id = Session::get('user_id');
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }

        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1', [(int) $id]);
        if ($user === null) {
            Session::forget('user_id');
            Session::forget('auth_fingerprint');
            return null;
        }

        $fingerprint = Session::get('auth_fingerprint');
        if (!is_string($fingerprint)
            || !hash_equals(self::fingerprint((string) $user['password_hash']), $fingerprint)) {
            Session::forget('user_id');
            Session::forget('auth_fingerprint');
            return null;
        }

        self::$user = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user !== null ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isAuthor(): bool
    {
        return in_array(self::role(), ['author', 'admin'], true);
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('auth_fingerprint', self::fingerprint((string) $user['password_hash']));
        Csrf::rotate();
        self::$user = $user;
        self::$loaded = true;
        Database::execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [(int) $user['id']]);
    }

    /**
     * Menyelaraskan sidik jari sesi setelah pengguna mengganti kata sandinya sendiri.
     * Reset kata sandi oleh admin tidak memanggil metode ini sehingga sesi lama berakhir.
     */
    public static function syncFingerprint(): void
    {
        $id = Session::get('user_id');
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return;
        }

        $hash = Database::fetchColumn(
            'SELECT password_hash FROM users WHERE id = ? AND is_active = 1',
            [(int) $id]
        );
        if (is_string($hash)) {
            Session::set('auth_fingerprint', self::fingerprint($hash));
        }
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$loaded = false;
    }

    public static function refresh(): void
    {
        self::$user = null;
        self::$loaded = false;
    }

    private static function fingerprint(string $passwordHash): string
    {
        return hash('sha256', $passwordHash);
    }
}
