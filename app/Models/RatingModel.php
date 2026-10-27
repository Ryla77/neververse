<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RatingModel
{
    public static function summary(int $novelId): array
    {
        $row = Database::fetch(
            'SELECT ROUND(AVG(rating), 1) AS average, COUNT(*) AS total FROM ratings WHERE novel_id = ?',
            [$novelId]
        );
        return [
            'average' => $row !== null && $row['average'] !== null ? (float) $row['average'] : null,
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    public static function userRating(int $novelId, int $userId): ?int
    {
        $value = Database::fetchColumn(
            'SELECT rating FROM ratings WHERE novel_id = ? AND user_id = ? LIMIT 1',
            [$novelId, $userId]
        );
        return $value === null || $value === false ? null : (int) $value;
    }

    public static function upsert(int $novelId, int $userId, int $rating): void
    {
        Database::execute(
            'INSERT INTO ratings (novel_id, user_id, rating, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = NOW()',
            [$novelId, $userId, $rating]
        );
    }

    public static function countAll(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM ratings');
    }
}
