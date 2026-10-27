<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class BookmarkModel
{
    public static function exists(int $userId, int $novelId): bool
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND novel_id = ?',
            [$userId, $novelId]
        ) > 0;
    }

    public static function add(int $userId, int $novelId): void
    {
        Database::execute(
            'INSERT IGNORE INTO bookmarks (user_id, novel_id, created_at) VALUES (?, ?, NOW())',
            [$userId, $novelId]
        );
    }

    public static function remove(int $userId, int $novelId): void
    {
        Database::execute('DELETE FROM bookmarks WHERE user_id = ? AND novel_id = ?', [$userId, $novelId]);
    }

    public static function toggle(int $userId, int $novelId): bool
    {
        if (self::exists($userId, $novelId)) {
            self::remove($userId, $novelId);
            return false;
        }
        self::add($userId, $novelId);
        return true;
    }

    public static function listForUser(int $userId): array
    {
        return Database::fetchAll(
            "SELECT b.created_at AS bookmarked_at,
                    n.id, n.title, n.slug, n.cover_path, n.status, n.views_count,
                    u.display_name AS author_name,
                    (SELECT COUNT(*) FROM chapters c WHERE c.novel_id = n.id AND c.status = 'published') AS chapter_count,
                    (SELECT ROUND(AVG(r.rating), 1) FROM ratings r WHERE r.novel_id = n.id) AS rating_avg,
                    CASE WHEN pc.id IS NULL THEN NULL ELSE p.progress_percent END AS progress_percent,
                    CASE WHEN pc.id IS NULL THEN NULL ELSE p.updated_at END AS last_read_at,
                    pc.chapter_number AS last_chapter
             FROM bookmarks b
             INNER JOIN novels n ON n.id = b.novel_id AND n.publish_status = 'published'
             INNER JOIN users u ON u.id = n.author_id
             LEFT JOIN reading_progress p ON p.user_id = b.user_id AND p.novel_id = n.id
             LEFT JOIN chapters pc ON pc.id = p.chapter_id AND pc.novel_id = n.id AND pc.status = 'published'
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC",
            [$userId]
        );
    }

    public static function countForUser(int $userId): int
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM bookmarks b
             INNER JOIN novels n ON n.id = b.novel_id AND n.publish_status = \'published\'
             WHERE b.user_id = ?',
            [$userId]
        );
    }
}
