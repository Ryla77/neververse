<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;

final class PasswordResetModel
{
    public static function issue(int $userId, string $tokenHash, int $ttlMinutes): void
    {
        $expiresAt = (new DateTimeImmutable('now'))
            ->modify('+' . max(5, $ttlMinutes) . ' minutes')
            ->format('Y-m-d H:i:s');

        Database::transaction(static function () use ($userId, $tokenHash, $expiresAt): void {
            Database::execute('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
            Database::execute('DELETE FROM password_resets WHERE expires_at < (NOW() - INTERVAL 1 DAY)');
            Database::execute(
                'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())',
                [$userId, $tokenHash, $expiresAt]
            );
        });
    }

    public static function findValid(string $tokenHash): ?array
    {
        return Database::fetch(
            "SELECT r.id, r.user_id, r.expires_at, u.email, u.display_name
             FROM password_resets r
             INNER JOIN users u ON u.id = r.user_id AND u.is_active = 1
             WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()
             LIMIT 1",
            [$tokenHash]
        );
    }

    public static function consume(int $id): bool
    {
        $statement = Database::execute(
            'UPDATE password_resets SET used_at = NOW() WHERE id = ? AND used_at IS NULL AND expires_at > NOW()',
            [$id]
        );

        return $statement->rowCount() === 1;
    }

    public static function deleteForUser(int $userId): void
    {
        Database::execute('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
    }
}
