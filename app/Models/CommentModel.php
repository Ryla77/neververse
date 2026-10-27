<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CommentModel
{
    public static function visibleForNovel(int $novelId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        return Database::fetchAll(
            "SELECT c.*, u.display_name AS user_name, u.role AS user_role
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.novel_id = ? AND c.status = 'visible'
             ORDER BY c.created_at DESC
             LIMIT {$limit}",
            [$novelId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT c.*, u.display_name AS user_name, n.title AS novel_title, n.slug AS novel_slug,
                    n.publish_status AS novel_publish_status
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             INNER JOIN novels n ON n.id = c.novel_id
             WHERE c.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function create(int $novelId, int $userId, string $content): int
    {
        return Database::insert(
            'INSERT INTO comments (novel_id, user_id, content, status, created_at)
             VALUES (?, ?, ?, \'visible\', NOW())',
            [$novelId, $userId, $content]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM comments WHERE id = ?', [$id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::execute('UPDATE comments SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
    }

    public static function countForNovel(int $novelId, bool $visibleOnly = true): int
    {
        $sql = 'SELECT COUNT(*) FROM comments WHERE novel_id = ?';
        if ($visibleOnly) {
            $sql .= " AND status = 'visible'";
        }
        return (int) Database::fetchColumn($sql, [$novelId]);
    }

    public static function countAll(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM comments');
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
            $where[] = '(c.content LIKE ? OR u.display_name LIKE ? OR n.title LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (in_array($status, ['visible', 'hidden'], true)) {
            $where[] = 'c.status = ?';
            $params[] = $status;
        }
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             INNER JOIN novels n ON n.id = c.novel_id' . $whereSql,
            $params
        );
        $items = Database::fetchAll(
            "SELECT c.id, c.content, c.status, c.created_at,
                    u.id AS user_id, u.display_name AS user_name,
                    n.id AS novel_id, n.title AS novel_title, n.slug AS novel_slug,
                    n.publish_status AS novel_publish_status
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             INNER JOIN novels n ON n.id = c.novel_id
             {$whereSql}
             ORDER BY c.created_at DESC
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

    public static function recent(int $limit = 5): array
    {
        $limit = max(1, $limit);
        return Database::fetchAll(
            "SELECT c.id, c.content, c.status, c.created_at, u.display_name AS user_name, n.title AS novel_title
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             INNER JOIN novels n ON n.id = c.novel_id
             ORDER BY c.created_at DESC
             LIMIT {$limit}"
        );
    }
}
