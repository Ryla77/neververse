<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Session;
use App\Core\Totp;
use App\Models\TwoFactorModel;

final class TwoFactorService
{
    private const SETUP_KEY = 'two_factor_setup_secret';

    public static function available(): bool
    {
        return Crypto::configured();
    }

    public static function enabled(int $userId): bool
    {
        return TwoFactorModel::isEnabled($userId);
    }

    public static function startSetup(): string
    {
        $secret = Totp::generateSecret();
        Session::set(self::SETUP_KEY, $secret);

        return $secret;
    }

    public static function pendingSecret(): ?string
    {
        $secret = Session::get(self::SETUP_KEY);

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    public static function clearSetup(): void
    {
        Session::forget(self::SETUP_KEY);
    }

    public static function provisioningUri(string $email, string $secret): string
    {
        return Totp::provisioningUri($email, $secret, (string) config('two_factor.issuer', 'Luminara'));
    }

    /**
     * Memverifikasi kode dari aplikasi autentikator, mendaftarkan secret, dan
     * menghasilkan kode pemulihan sekali pakai.
     *
     * @return list<string>|null daftar kode pemulihan, atau null bila kode salah
     */
    public static function activate(int $userId, string $secret, string $code): ?array
    {
        $step = Totp::verify($secret, $code);
        if ($step === null) {
            return null;
        }

        $ciphertext = Crypto::encrypt($secret);
        $codes = self::generateRecoveryCodes((int) config('two_factor.recovery_code_count', 8));

        Database::transaction(static function () use ($userId, $ciphertext, $codes): void {
            TwoFactorModel::save($userId, $ciphertext, 0);
            TwoFactorModel::replaceRecoveryCodes(
                $userId,
                array_map(static fn (string $code): string => self::recoveryHash($code), $codes)
            );
        });

        return $codes;
    }

    public static function verifyTotp(int $userId, string $code): bool
    {
        $row = TwoFactorModel::find($userId);
        if ($row === null) {
            return false;
        }

        $secret = Crypto::decrypt((string) $row['secret_ciphertext']);
        if ($secret === null) {
            return false;
        }

        $step = Totp::verify($secret, $code, 1, null, (int) $row['last_used_step']);
        if ($step === null) {
            return false;
        }

        return TwoFactorModel::recordUse($userId, $step);
    }

    public static function verifyRecoveryCode(int $userId, string $code): bool
    {
        $normalized = self::normalizeCode($code);
        if (strlen($normalized) !== 8) {
            return false;
        }

        $row = TwoFactorModel::findUnusedRecoveryCode($userId, self::recoveryHash($normalized));
        if ($row === null) {
            return false;
        }

        return TwoFactorModel::consumeRecoveryCode((int) $row['id'], $userId);
    }

    public static function disable(int $userId): void
    {
        Database::transaction(static function () use ($userId): void {
            TwoFactorModel::delete($userId);
            TwoFactorModel::deleteRecoveryCodes($userId);
        });
    }

    public static function remainingRecoveryCodes(int $userId): int
    {
        return TwoFactorModel::countUnusedRecoveryCodes($userId);
    }

    /**
     * @return list<string>
     */
    public static function generateRecoveryCodes(int $count): array
    {
        $codes = [];
        while (count($codes) < $count) {
            $raw = Totp::base32Encode(random_bytes(5));
            $codes[$raw] = substr($raw, 0, 4) . '-' . substr($raw, 4);
        }

        return array_values($codes);
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    private static function recoveryHash(string $code): string
    {
        return hash('sha256', self::normalizeCode($code));
    }
}
