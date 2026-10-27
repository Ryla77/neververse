<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ReportModel
{
    public static function create(int $reporterId, ?int $novelId, ?int $commentId, string $reason, ?string $details): int
    {
        return Database::insert(
            'INSERT INTO reports (reporter_id, novel_id, comment_id, reason, details, status, created_at)
             VALUES (?, ?, ?, ?, ?, \'open\', NOW())',
            [$reporterId, $novelId, $commentId, $reason, $details]
        );
    }

    public static function existsOpen(int $reporterId, ?int $novelId, ?int $commentId): bool
    {
        if ($novelId !== null) {
            return (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM reports WHERE reporter_id = ? AND novel_id = ? AND status = 'open'",
                [$reporterId, $novelId]
            ) > 0;
        }
        if ($commentId !== null) {
            return (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM reports WHERE reporter_id = ? AND comment_id = ? AND status = 'open'",
                [$reporterId, $commentId]
            ) > 0;
        }
        return false;
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT r.*,
                    rep.display_name AS reporter_name,
                    n.title AS novel_title, n.slug AS novel_slug,
                    n.publish_status AS novel_publish_status,
                    c.content AS comment_content, c.status AS comment_status,
                    cu.display_name AS comment_author,
                    cn.title AS comment_novel_title, cn.slug AS comment_novel_slug,
                    cn.publish_status AS comment_novel_publish_status
             FROM reports r
             INNER JOIN users rep ON rep.id = r.reporter_id
             LEFT JOIN novels n ON n.id = r.novel_id
             LEFT JOIN comments c ON c.id = r.comment_id
             LEFT JOIN users cu ON cu.id = c.user_id
             LEFT JOIN novels cn ON cn.id = c.novel_id
             WHERE r.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function list(string $status, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if (in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            $where = 'WHERE r.status = ?';
            $params[] = $status;
        }

        $total = (int) Database::fetchColumn('SELECT COUNT(*) FROM reports r ' . $where, $params);
        $items = Database::fetchAll(
            "SELECT r.id, r.reason, r.details, r.status, r.created_at, r.resolved_at,
                    rep.display_name AS reporter_name,
                    n.title AS novel_title, n.slug AS novel_slug,
                    n.publish_status AS novel_publish_status,
                    c.id AS comment_id, c.content AS comment_content, c.status AS comment_status,
                    cu.display_name AS comment_author,
                    cn.title AS comment_novel_title, cn.slug AS comment_novel_slug,
                    cn.publish_status AS comment_novel_publish_status
             FROM reports r
             INNER JOIN users rep ON rep.id = r.reporter_id
             LEFT JOIN novels n ON n.id = r.novel_id
             LEFT JOIN comments c ON c.id = r.comment_id
             LEFT JOIN users cu ON cu.id = c.user_id
             LEFT JOIN novels cn ON cn.id = c.novel_id
             {$where}
             ORDER BY r.created_at DESC
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

    public static function setStatus(int $id, string $status, int $adminId): void
    {
        Database::execute(
            'UPDATE reports SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE id = ?',
            [$status, $adminId, $id]
        );
    }

    public static function countOpen(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM reports WHERE status = 'open'");
    }

    public static function countByStatus(string $status): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM reports WHERE status = ?', [$status]);
    }
}
