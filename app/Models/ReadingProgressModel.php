<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ReadingProgressModel
{
    public static function upsert(int $userId, int $novelId, int $chapterId, float $percent): void
    {
        $percent = max(0.0, min(100.0, $percent));
        Database::execute(
            'INSERT INTO reading_progress (user_id, novel_id, chapter_id, progress_percent, updated_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                chapter_id = VALUES(chapter_id),
                progress_percent = VALUES(progress_percent),
                updated_at = NOW()',
            [$userId, $novelId, $chapterId, $percent]
        );
    }

    public static function find(int $userId, int $novelId): ?array
    {
        return Database::fetch(
            'SELECT * FROM reading_progress WHERE user_id = ? AND novel_id = ? LIMIT 1',
            [$userId, $novelId]
        );
    }

    public static function markChapterRead(int $userId, int $chapterId): void
    {
        Database::execute(
            'INSERT IGNORE INTO chapter_reads (user_id, chapter_id, read_at) VALUES (?, ?, NOW())',
            [$userId, $chapterId]
        );
    }

    public static function readChapterIds(int $userId, int $novelId): array
    {
        $rows = Database::fetchAll(
            'SELECT cr.chapter_id
             FROM chapter_reads cr
             INNER JOIN chapters c ON c.id = cr.chapter_id
             WHERE cr.user_id = ? AND c.novel_id = ?',
            [$userId, $novelId]
        );
        return array_map(static fn (array $row): int => (int) $row['chapter_id'], $rows);
    }

    public static function history(int $userId, int $limit = 20, ?int $offset = null): array
    {
        $limit = max(1, min(50, $limit));
        $offsetSql = $offset !== null ? ' OFFSET ' . max(0, $offset) : '';
        return Database::fetchAll(
            "SELECT CASE WHEN c.id IS NULL THEN NULL ELSE p.progress_percent END AS progress_percent,
                    p.updated_at AS last_read_at,
                    p.novel_id,
                    n.title, n.slug, n.cover_path, n.status, u.display_name AS author_name,
                    c.chapter_number, c.title AS chapter_title,
                    (SELECT COUNT(*) FROM chapters ch WHERE ch.novel_id = n.id AND ch.status = 'published') AS chapter_count
             FROM reading_progress p
             INNER JOIN novels n ON n.id = p.novel_id AND n.publish_status = 'published'
             INNER JOIN users u ON u.id = n.author_id
             LEFT JOIN chapters c ON c.id = p.chapter_id AND c.novel_id = n.id AND c.status = 'published'
             WHERE p.user_id = ?
             ORDER BY p.updated_at DESC
             LIMIT {$limit}{$offsetSql}",
            [$userId]
        );
    }

    public static function continueReading(int $userId): ?array
    {
        $rows = Database::fetchAll(
            "SELECT CASE WHEN c.id IS NULL THEN NULL ELSE p.progress_percent END AS progress_percent,
                    p.updated_at AS last_read_at,
                    p.novel_id,
                    n.title, n.slug, n.cover_path, n.status, u.display_name AS author_name,
                    c.chapter_number, c.title AS chapter_title
             FROM reading_progress p
             INNER JOIN novels n ON n.id = p.novel_id AND n.publish_status = 'published'
             INNER JOIN users u ON u.id = n.author_id
             LEFT JOIN chapters c ON c.id = p.chapter_id AND c.novel_id = n.id AND c.status = 'published'
             WHERE p.user_id = ?
             ORDER BY p.updated_at DESC
             LIMIT 1",
            [$userId]
        );
        return $rows[0] ?? null;
    }

    public static function countHistory(int $userId): int
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM reading_progress p
             INNER JOIN novels n ON n.id = p.novel_id AND n.publish_status = 'published'
             WHERE p.user_id = ?",
            [$userId]
        );
    }
}
