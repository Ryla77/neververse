<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Enkripsi simetris untuk data yang harus bisa dibaca ulang (mis. secret TOTP).
 * Kunci diambil dari APP_KEY dan tidak pernah disimpan di database.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_BYTES = 12;
    private const TAG_BYTES = 16;

    public static function configured(): bool
    {
        return self::key() !== null;
    }

    public static function encrypt(string $plaintext): string
    {
        $key = self::key();
        if ($key === null) {
            throw new RuntimeException('APP_KEY belum diset atau tidak valid.');
        }

        $iv = random_bytes(self::IV_BYTES);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_BYTES);
        if ($ciphertext === false) {
            throw new RuntimeException('Enkripsi data gagal.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $payload): ?string
    {
        $key = self::key();
        if ($key === null) {
            return null;
        }

        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) <= self::IV_BYTES + self::TAG_BYTES) {
            return null;
        }

        $plain = openssl_decrypt(
            substr($raw, self::IV_BYTES + self::TAG_BYTES),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            substr($raw, 0, self::IV_BYTES),
            substr($raw, self::IV_BYTES, self::TAG_BYTES)
        );

        return $plain === false ? null : $plain;
    }

    private static function key(): ?string
    {
        static $key = false;

        if ($key === false) {
            $configured = trim((string) config('security.app_key', ''));
            $decoded = $configured !== '' ? base64_decode($configured, true) : false;
            $key = is_string($decoded) && strlen($decoded) === 32 ? $decoded : null;
        }

        return $key;
    }
}
