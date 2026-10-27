<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Implementasi TOTP (RFC 6238, SHA-1, 6 digit, 30 detik) tanpa dependensi eksternal.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function step(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), self::PERIOD);
    }

    public static function code(string $secret, ?int $timestamp = null): string
    {
        $key = self::base32Decode($secret);
        if ($key === null || $key === '') {
            return '';
        }

        $step = self::step($timestamp);
        $hash = hash_hmac('sha1', pack('NN', 0, $step), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Mengembalikan langkah waktu yang cocok, atau null bila kode tidak valid.
     * Langkah yang sudah pernah dipakai (replay) dilewati.
     */
    public static function verify(
        string $secret,
        string $code,
        int $window = 1,
        ?int $timestamp = null,
        int $minStep = 0
    ): ?int {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== self::DIGITS) {
            return null;
        }

        $current = self::step($timestamp);
        for ($offset = -$window; $offset <= $window; $offset++) {
            $step = $current + $offset;
            if ($step <= $minStep) {
                continue;
            }
            if (hash_equals(self::code($secret, $step * self::PERIOD), $code)) {
                return $step;
            }
        }

        return null;
    }

    public static function provisioningUri(string $account, string $secret, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/' . $label . '?' . $query;
    }

    public static function normalizeSecret(string $secret): string
    {
        return strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');
    }

    public static function base32Encode(string $data): string
    {
        $bits = 0;
        $value = 0;
        $output = '';
        foreach (str_split($data) as $char) {
            $value = ($value << 8) | ord($char);
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $output .= self::ALPHABET[($value >> $bits) & 31];
            }
        }
        if ($bits > 0) {
            $output .= self::ALPHABET[($value << (5 - $bits)) & 31];
        }

        return $output;
    }

    public static function base32Decode(string $secret): ?string
    {
        $secret = self::normalizeSecret($secret);
        if ($secret === '') {
            return null;
        }

        $bits = 0;
        $value = 0;
        $output = '';
        foreach (str_split($secret) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                return null;
            }
            $value = ($value << 5) | $index;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($value >> $bits) & 0xFF);
            }
        }

        return $output;
    }
}
