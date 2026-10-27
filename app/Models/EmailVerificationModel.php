<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;

final class EmailVerificationModel
{
    public static function issue(int $userId, string $tokenHash, int $ttlMinutes): void
    {
        $expiresAt = (new DateTimeImmutable('now'))
            ->modify('+' . max(5, $ttlMinutes) . ' minutes')
            ->format('Y-m-d H:i:s');

        Database::transaction(static function () use ($userId, $tokenHash, $expiresAt): void {
            Database::execute(
                'DELETE FROM email_verifications
                 WHERE verified_at IS NULL AND expires_at IS NOT NULL AND expires_at < (NOW() - INTERVAL 1 DAY)'
            );
            Database::execute(
                'INSERT INTO email_verifications (user_id, token_hash, expires_at, verified_at, created_at)
                 VALUES (?, ?, ?, NULL, NOW())
                 ON DUPLICATE KEY UPDATE
                    token_hash = VALUES(token_hash),
                    expires_at = VALUES(expires_at),
                    verified_at = NULL,
                    created_at = NOW()',
                [$userId, $tokenHash, $expiresAt]
            );
        });
    }

    public static function findValid(string $tokenHash): ?array
    {
        return Database::fetch(
            'SELECT ev.user_id, ev.expires_at, u.email, u.display_name
             FROM email_verifications ev
             INNER JOIN users u ON u.id = ev.user_id AND u.is_active = 1
             WHERE ev.token_hash = ? AND ev.verified_at IS NULL AND ev.expires_at > NOW()
             LIMIT 1',
            [$tokenHash]
        );
    }

    public static function markVerified(int $userId, string $tokenHash): bool
    {
        $statement = Database::execute(
            'UPDATE email_verifications
             SET verified_at = NOW(), token_hash = NULL, expires_at = NULL
             WHERE user_id = ? AND token_hash = ? AND verified_at IS NULL AND expires_at > NOW()',
            [$userId, $tokenHash]
        );

        return $statement->rowCount() === 1;
    }

    public static function isVerified(int $userId): bool
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM email_verifications WHERE user_id = ? AND verified_at IS NOT NULL',
            [$userId]
        ) > 0;
    }

    public static function deleteForUser(int $userId): void
    {
        Database::execute('DELETE FROM email_verifications WHERE user_id = ?', [$userId]);
    }
}
