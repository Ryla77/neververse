<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditLogModel
{
    /** @var array<string, string> */
    public const ACTIONS = [
        'user.update' => 'Ubah pengguna',
        'user.password_reset' => 'Reset kata sandi',
        'user.two_factor_reset' => 'Reset 2FA',
        'novel.publish' => 'Terbitkan novel',
        'novel.unpublish' => 'Kembalikan novel ke draf',
        'novel.featured' => 'Tandai unggulan',
        'novel.unfeatured' => 'Hapus unggulan',
        'novel.delete' => 'Hapus novel',
        'chapter.publish' => 'Terbitkan bab',
        'chapter.draft' => 'Kembalikan bab ke draf',
        'chapter.delete' => 'Hapus bab',
        'genre.create' => 'Tambah genre',
        'genre.update' => 'Ubah genre',
        'genre.delete' => 'Hapus genre',
        'comment.hide' => 'Sembunyikan komentar',
        'comment.show' => 'Tampilkan komentar',
        'comment.delete' => 'Hapus komentar',
        'report.resolve' => 'Selesaikan laporan',
        'report.dismiss' => 'Tolak laporan',
    ];

    /** @var array<string, string> */
    public const TARGETS = [
        'user' => 'Pengguna',
        'novel' => 'Novel',
        'chapter' => 'Bab',
        'genre' => 'Genre',
        'comment' => 'Komentar',
        'report' => 'Laporan',
    ];

    public static function record(
        ?int $actorId,
        string $actorName,
        string $action,
        ?string $targetType,
        ?int $targetId,
        string $summary,
        string $ipAddress
    ): void {
        Database::insert(
            'INSERT INTO admin_audit_logs
                (actor_id, actor_name, action, target_type, target_id, summary, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $actorId,
                mb_substr($actorName, 0, 80),
                mb_substr($action, 0, 40),
                $targetType,
                $targetId,
                mb_substr($summary, 0, 255),
                $ipAddress !== '' ? mb_substr($ipAddress, 0, 45) : null,
            ]
        );
    }

    public static function list(string $action, string $query, int $page, int $perPage = 30): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];
        if ($action !== '' && isset(self::ACTIONS[$action])) {
            $conditions[] = 'l.action = ?';
            $params[] = $action;
        }
        if ($query !== '') {
            $conditions[] = '(l.actor_name LIKE ? OR l.summary LIKE ?)';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }
        $where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $total = (int) Database::fetchColumn('SELECT COUNT(*) FROM admin_audit_logs l ' . $where, $params);
        $items = Database::fetchAll(
            "SELECT l.id, l.actor_id, l.actor_name, l.action, l.target_type, l.target_id,
                    l.summary, l.ip_address, l.created_at
             FROM admin_audit_logs l
             {$where}
             ORDER BY l.created_at DESC, l.id DESC
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

    public static function label(string $action): string
    {
        return self::ACTIONS[$action] ?? $action;
    }

    public static function targetLabel(?string $type, ?int $id): string
    {
        if ($type === null || $id === null) {
            return '—';
        }
        return (self::TARGETS[$type] ?? $type) . ' #' . $id;
    }

    public static function tone(string $action): string
    {
        return match ($action) {
            'user.password_reset', 'user.two_factor_reset',
            'novel.delete', 'chapter.delete', 'genre.delete', 'comment.delete' => 'danger',
            'novel.publish', 'chapter.publish', 'genre.create', 'comment.show', 'report.resolve' => 'published',
            'novel.featured' => 'gold',
            default => 'muted',
        };
    }
}
