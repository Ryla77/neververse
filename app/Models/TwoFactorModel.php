<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class TwoFactorModel
{
    public static function find(int $userId): ?array
    {
        return Database::fetch('SELECT * FROM user_two_factor WHERE user_id = ? LIMIT 1', [$userId]);
    }

    public static function isEnabled(int $userId): bool
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM user_two_factor WHERE user_id = ?',
            [$userId]
        ) > 0;
    }

    public static function save(int $userId, string $secretCiphertext, int $lastUsedStep): void
    {
        Database::execute(
            'INSERT INTO user_two_factor (user_id, secret_ciphertext, last_used_step, enabled_at, created_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                secret_ciphertext = VALUES(secret_ciphertext),
                last_used_step = VALUES(last_used_step),
                enabled_at = NOW(),
                last_verified_at = NULL,
                created_at = NOW()',
            [$userId, $secretCiphertext, $lastUsedStep]
        );
    }

    /**
     * Mencatat pemakaian kode sekaligus mencegah replay pada langkah waktu yang sama.
     */
    public static function recordUse(int $userId, int $step): bool
    {
        $statement = Database::execute(
            'UPDATE user_two_factor SET last_used_step = ?, last_verified_at = NOW()
             WHERE user_id = ? AND last_used_step < ?',
            [$step, $userId, $step]
        );

        return $statement->rowCount() === 1;
    }

    public static function delete(int $userId): void
    {
        Database::execute('DELETE FROM user_two_factor WHERE user_id = ?', [$userId]);
    }

    /**
     * @param list<string> $hashes
     */
    public static function replaceRecoveryCodes(int $userId, array $hashes): void
    {
        Database::transaction(static function () use ($userId, $hashes): void {
            Database::execute('DELETE FROM user_recovery_codes WHERE user_id = ?', [$userId]);
            foreach ($hashes as $hash) {
                Database::execute(
                    'INSERT INTO user_recovery_codes (user_id, code_hash, created_at) VALUES (?, ?, NOW())',
                    [$userId, $hash]
                );
            }
        });
    }

    public static function findUnusedRecoveryCode(int $userId, string $hash): ?array
    {
        return Database::fetch(
            'SELECT id FROM user_recovery_codes WHERE user_id = ? AND code_hash = ? AND used_at IS NULL LIMIT 1',
            [$userId, $hash]
        );
    }

    public static function consumeRecoveryCode(int $id, int $userId): bool
    {
        $statement = Database::execute(
            'UPDATE user_recovery_codes SET used_at = NOW() WHERE id = ? AND user_id = ? AND used_at IS NULL',
            [$id, $userId]
        );

        return $statement->rowCount() === 1;
    }

    public static function countUnusedRecoveryCodes(int $userId): int
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL',
            [$userId]
        );
    }

    public static function deleteRecoveryCodes(int $userId): void
    {
        Database::execute('DELETE FROM user_recovery_codes WHERE user_id = ?', [$userId]);
    }
}
