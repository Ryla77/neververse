<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class NovelModel
{
    private const PUBLIC_FILTER = "n.publish_status = 'published'
        AND EXISTS (SELECT 1 FROM chapters pc WHERE pc.novel_id = n.id AND pc.status = 'published')";

    private static function selectSql(): string
    {
        return "SELECT n.*, u.display_name AS author_name, u.email AS author_email,
            (SELECT COUNT(*) FROM chapters c WHERE c.novel_id = n.id AND c.status = 'published') AS chapter_count,
            (SELECT COUNT(*) FROM chapters ca WHERE ca.novel_id = n.id) AS chapter_total,
            (SELECT ROUND(AVG(r.rating), 1) FROM ratings r WHERE r.novel_id = n.id) AS rating_avg,
            (SELECT COUNT(*) FROM ratings r WHERE r.novel_id = n.id) AS rating_count,
            (SELECT GROUP_CONCAT(cat.name ORDER BY cat.name SEPARATOR ', ')
                FROM novel_categories nc JOIN categories cat ON cat.id = nc.category_id
                WHERE nc.novel_id = n.id) AS genre_names,
            (SELECT GROUP_CONCAT(cat.slug ORDER BY cat.slug SEPARATOR ',')
                FROM novel_categories nc JOIN categories cat ON cat.id = nc.category_id
                WHERE nc.novel_id = n.id) AS genre_slugs
            FROM novels n
            INNER JOIN users u ON u.id = n.author_id";
    }

    private static function like(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($value)) . '%';
    }

    public static function listPublic(array $filters, int $page, int $perPage = 9): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(48, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = [self::PUBLIC_FILTER];
        $params = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $like = self::like($query);
            $where[] = '(n.title LIKE ? OR u.display_name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $genre = trim((string) ($filters['genre'] ?? ''));
        if ($genre !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM novel_categories nc2
                JOIN categories cat2 ON cat2.id = nc2.category_id
                WHERE nc2.novel_id = n.id AND cat2.slug = ?)';
            $params[] = $genre;
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM novels n INNER JOIN users u ON u.id = n.author_id WHERE ' . $whereSql,
            $params
        );

        $order = match ((string) ($filters['sort'] ?? 'latest')) {
            'popular' => 'n.views_count DESC, n.updated_at DESC',
            'rating' => 'rating_avg DESC, rating_count DESC, n.updated_at DESC',
            'updated' => 'n.updated_at DESC',
            default => 'n.created_at DESC',
        };

        $items = Database::fetchAll(
            self::selectSql() . " WHERE {$whereSql} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}",
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

    public static function collection(string $order, int $limit = 6, string $extra = ''): array
    {
        $limit = max(1, min(24, $limit));
        $orderSql = match ($order) {
            'popular' => 'n.views_count DESC, n.updated_at DESC',
            'updated' => 'n.updated_at DESC',
            'rating' => 'rating_avg DESC, rating_count DESC, n.updated_at DESC',
            default => 'n.created_at DESC',
        };
        $extraSql = $extra !== '' ? ' AND ' . $extra : '';
        return Database::fetchAll(
            self::selectSql() . ' WHERE ' . self::PUBLIC_FILTER . $extraSql . " ORDER BY {$orderSql} LIMIT {$limit}"
        );
    }

    public static function featured(): ?array
    {
        return Database::fetch(
            self::selectSql() . ' WHERE ' . self::PUBLIC_FILTER . '
             AND n.is_featured = 1
             ORDER BY n.updated_at DESC LIMIT 1'
        );
    }

    public static function findPublicBySlug(string $slug): ?array
    {
        return Database::fetch(
            self::selectSql() . ' WHERE n.slug = ? AND ' . self::PUBLIC_FILTER . ' LIMIT 1',
            [$slug]
        );
    }

    public static function related(int $novelId, int $limit = 3): array
    {
        $limit = max(1, min(12, $limit));
        $rows = Database::fetchAll(
            self::selectSql() . ' WHERE ' . self::PUBLIC_FILTER . ' AND n.id <> ?
             AND EXISTS (
                 SELECT 1 FROM novel_categories nc
                 WHERE nc.novel_id = n.id
                   AND nc.category_id IN (SELECT category_id FROM novel_categories WHERE novel_id = ?)
             )
             ORDER BY rating_avg DESC, n.views_count DESC, n.updated_at DESC
             LIMIT ' . $limit,
            [$novelId, $novelId]
        );

        if ($rows === []) {
            $rows = self::collection('rating', $limit + 1);
        }

        $rows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => (int) $row['id'] !== $novelId
        ));
        return array_slice($rows, 0, $limit);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE n.slug = ? LIMIT 1', [$slug]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE n.id = ? LIMIT 1', [$id]);
    }

    public static function findOwned(int $id, int $authorId): ?array
    {
        return Database::fetch(
            self::selectSql() . ' WHERE n.id = ? AND n.author_id = ? LIMIT 1',
            [$id, $authorId]
        );
    }

    public static function byAuthor(int $authorId): array
    {
        return Database::fetchAll(
            self::selectSql() . ' WHERE n.author_id = ? ORDER BY n.updated_at DESC',
            [$authorId]
        );
    }

    public static function adminList(string $query, string $status, int $page, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        $query = trim($query);
        if ($query !== '') {
            $like = self::like($query);
            $where[] = '(n.title LIKE ? OR u.display_name LIKE ? OR u.email LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (in_array($status, ['draft', 'published'], true)) {
            $where[] = 'n.publish_status = ?';
            $params[] = $status;
        }
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM novels n INNER JOIN users u ON u.id = n.author_id' . $whereSql,
            $params
        );
        $items = Database::fetchAll(
            self::selectSql() . $whereSql . " ORDER BY n.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
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

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = slugify($title);
        $slug = $base;
        $suffix = 2;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM novels WHERE slug = ?';
            $params = [$slug];
            if ($ignoreId !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $ignoreId;
            }
            if ((int) Database::fetchColumn($sql, $params) === 0) {
                return $slug;
            }
            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }

    public static function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO novels
             (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())',
            [
                (int) $data['author_id'],
                $data['title'],
                $data['slug'],
                $data['synopsis'],
                $data['cover_path'] ?? null,
                $data['status'],
                $data['publish_status'],
                (int) ($data['is_featured'] ?? 0),
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE novels
             SET title = ?, slug = ?, synopsis = ?, status = ?, publish_status = ?, updated_at = NOW()
             WHERE id = ?',
            [
                $data['title'],
                $data['slug'],
                $data['synopsis'],
                $data['status'],
                $data['publish_status'],
                $id,
            ]
        );
    }

    public static function setCover(int $id, ?string $coverPath): void
    {
        Database::execute('UPDATE novels SET cover_path = ?, updated_at = NOW() WHERE id = ?', [$coverPath, $id]);
    }

    public static function setPublishStatus(int $id, string $status): void
    {
        Database::execute('UPDATE novels SET publish_status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
    }

    public static function setFeatured(int $id, bool $featured): void
    {
        Database::execute('UPDATE novels SET is_featured = ?, updated_at = NOW() WHERE id = ?', [$featured ? 1 : 0, $id]);
    }

    public static function touch(int $id): void
    {
        Database::execute('UPDATE novels SET updated_at = NOW() WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM novels WHERE id = ?', [$id]);
    }

    public static function incrementViews(int $id): void
    {
        Database::execute('UPDATE novels SET views_count = views_count + 1 WHERE id = ?', [$id]);
    }

    public static function countAll(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM novels');
    }

    public static function countPublished(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM novels WHERE publish_status = 'published'");
    }

    public static function countFeatured(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM novels WHERE is_featured = 1');
    }
}
