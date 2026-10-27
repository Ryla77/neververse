<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UserModel
{
    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [mb_strtolower(trim($email))]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public static function create(string $email, string $passwordHash, string $displayName, string $role): int
    {
        return Database::insert(
            'INSERT INTO users (email, password_hash, display_name, role, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, NOW())',
            [mb_strtolower(trim($email)), $passwordHash, $displayName, $role]
        );
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [mb_strtolower(trim($email))];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::fetchColumn($sql, $params) > 0;
    }

    public static function updateProfile(int $id, string $displayName, string $email, ?string $bio): void
    {
        Database::execute(
            'UPDATE users SET display_name = ?, email = ?, bio = ?, updated_at = NOW() WHERE id = ?',
            [$displayName, mb_strtolower(trim($email)), $bio, $id]
        );
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        Database::execute(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [$passwordHash, $id]
        );
    }

    public static function setRole(int $id, string $role): void
    {
        Database::execute('UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?', [$role, $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::execute('UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    public static function countAll(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM users');
    }

    public static function countByRole(string $role): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM users WHERE role = ?', [$role]);
    }

    public static function countActiveAdmins(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
    }

    public static function adminList(string $query, int $page, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = '';
        $params = [];
        $query = trim($query);
        if ($query !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';
            $where = 'WHERE u.email LIKE ? OR u.display_name LIKE ?';
            $params = [$like, $like];
        }
        $total = (int) Database::fetchColumn('SELECT COUNT(*) FROM users u ' . $where, $params);
        $items = Database::fetchAll(
            "SELECT u.id, u.email, u.display_name, u.role, u.bio, u.is_active, u.last_login_at, u.created_at,
                    (ev.verified_at IS NOT NULL) AS is_verified
             FROM users u
             LEFT JOIN email_verifications ev ON ev.user_id = u.id
             {$where}
             ORDER BY u.created_at DESC
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
            "SELECT id, email, display_name, role, created_at FROM users ORDER BY created_at DESC LIMIT {$limit}"
        );
    }
}
