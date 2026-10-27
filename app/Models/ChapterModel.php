<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ChapterModel
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM chapters WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findByNumber(int $novelId, int $number): ?array
    {
        return Database::fetch(
            'SELECT * FROM chapters WHERE novel_id = ? AND chapter_number = ? LIMIT 1',
            [$novelId, $number]
        );
    }

    public static function publishedByNovel(int $novelId): array
    {
        return Database::fetchAll(
            "SELECT id, chapter_number, title, published_at, created_at
             FROM chapters WHERE novel_id = ? AND status = 'published'
             ORDER BY chapter_number ASC",
            [$novelId]
        );
    }

    public static function allByNovel(int $novelId): array
    {
        return Database::fetchAll(
            'SELECT id, chapter_number, title, status, published_at, created_at, updated_at,
                    CHAR_LENGTH(content) AS content_length
             FROM chapters WHERE novel_id = ? ORDER BY chapter_number ASC',
            [$novelId]
        );
    }

    public static function countByNovel(int $novelId): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM chapters WHERE novel_id = ?', [$novelId]);
    }

    public static function countPublishedByNovel(int $novelId): int
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM chapters WHERE novel_id = ? AND status = 'published'",
            [$novelId]
        );
    }

    public static function countAll(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM chapters');
    }

    public static function countPublished(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM chapters WHERE status = 'published'");
    }

    public static function previousNumber(int $novelId, int $number): ?int
    {
        $value = Database::fetchColumn(
            "SELECT MAX(chapter_number) FROM chapters
             WHERE novel_id = ? AND status = 'published' AND chapter_number < ?",
            [$novelId, $number]
        );
        return $value === null || $value === false ? null : (int) $value;
    }

    public static function nextNumber(int $novelId, int $number): ?int
    {
        $value = Database::fetchColumn(
            "SELECT MIN(chapter_number) FROM chapters
             WHERE novel_id = ? AND status = 'published' AND chapter_number > ?",
            [$novelId, $number]
        );
        return $value === null || $value === false ? null : (int) $value;
    }

    public static function firstPublishedNumber(int $novelId): ?int
    {
        $value = Database::fetchColumn(
            "SELECT MIN(chapter_number) FROM chapters WHERE novel_id = ? AND status = 'published'",
            [$novelId]
        );
        return $value === null || $value === false ? null : (int) $value;
    }

    public static function create(int $novelId, string $title, string $content, string $status): int
    {
        return Database::transaction(static function () use ($novelId, $title, $content, $status): int {
            Database::fetchColumn('SELECT id FROM novels WHERE id = ? FOR UPDATE', [$novelId]);
            $next = (int) Database::fetchColumn(
                'SELECT COALESCE(MAX(chapter_number), 0) + 1 FROM chapters WHERE novel_id = ?',
                [$novelId]
            );
            return Database::insert(
                'INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $novelId,
                    $next,
                    $title,
                    $content,
                    $status,
                    $status === 'published' ? date('Y-m-d H:i:s') : null,
                ]
            );
        });
    }

    public static function update(int $id, string $title, string $content, string $status): void
    {
        Database::execute(
            'UPDATE chapters
             SET title = ?, content = ?, status = ?,
                 published_at = CASE
                     WHEN ? = \'published\' AND published_at IS NULL THEN NOW()
                     ELSE published_at
                 END,
                 updated_at = NOW()
             WHERE id = ?',
            [$title, $content, $status, $status, $id]
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::execute(
            'UPDATE chapters
             SET status = ?,
                 published_at = CASE WHEN ? = \'published\' AND published_at IS NULL THEN NOW() ELSE published_at END,
                 updated_at = NOW()
             WHERE id = ?',
            [$status, $status, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM chapters WHERE id = ?', [$id]);
    }

    public static function adminList(string $query, string $status, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        $query = trim($query);
        if ($query !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';
            $where[] = '(c.title LIKE ? OR n.title LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        if (in_array($status, ['draft', 'published'], true)) {
            $where[] = 'c.status = ?';
            $params[] = $status;
        }
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM chapters c INNER JOIN novels n ON n.id = c.novel_id' . $whereSql,
            $params
        );
        $items = Database::fetchAll(
            "SELECT c.id, c.chapter_number, c.title, c.status, c.published_at, c.created_at, c.updated_at,
                    n.id AS novel_id, n.title AS novel_title, n.slug AS novel_slug,
                    n.publish_status AS novel_publish_status, u.display_name AS author_name
             FROM chapters c
             INNER JOIN novels n ON n.id = c.novel_id
             INNER JOIN users u ON u.id = n.author_id
             {$whereSql}
             ORDER BY c.updated_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function latestPublished(int $limit = 5): array
    {
        $limit = max(1, $limit);
        return Database::fetchAll(
            "SELECT c.id, c.chapter_number, c.title, c.published_at, n.title AS novel_title, n.slug AS novel_slug
             FROM chapters c
             INNER JOIN novels n ON n.id = c.novel_id AND n.publish_status = 'published'
             WHERE c.status = 'published'
             ORDER BY c.published_at DESC
             LIMIT {$limit}"
        );
    }
}
