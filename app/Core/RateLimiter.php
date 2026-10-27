<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    public static function tooMany(string $bucket, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        $identifier = self::identifier($identifier);
        $windowSeconds = max(1, $windowSeconds);
        $row = Database::fetch(
            'SELECT COUNT(*) AS total FROM rate_limits
             WHERE bucket = ? AND identifier = ? AND created_at > (NOW() - INTERVAL ' . $windowSeconds . ' SECOND)',
            [$bucket, $identifier]
        );
        return (int) ($row['total'] ?? 0) >= $maxAttempts;
    }

    public static function hit(string $bucket, string $identifier): void
    {
        Database::execute(
            'INSERT INTO rate_limits (bucket, identifier, created_at) VALUES (?, ?, NOW())',
            [$bucket, self::identifier($identifier)]
        );
        if (random_int(1, 50) === 1) {
            self::cleanup();
        }
    }

    public static function clear(string $bucket, string $identifier): void
    {
        Database::execute(
            'DELETE FROM rate_limits WHERE bucket = ? AND identifier = ?',
            [$bucket, self::identifier($identifier)]
        );
    }

    public static function cleanup(): void
    {
        Database::execute('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 2 DAY)');
    }

    private static function identifier(string $value): string
    {
        return hash('sha256', mb_strtolower(trim($value)));
    }
}
