<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CategoryModel
{
    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM categories ORDER BY name ASC');
    }

    public static function allWithCounts(bool $publicOnly = true): array
    {
        $novelFilter = $publicOnly
            ? "AND n.publish_status = 'published'
               AND EXISTS (SELECT 1 FROM chapters pc WHERE pc.novel_id = n.id AND pc.status = 'published')"
            : '';
        return Database::fetchAll(
            "SELECT cat.*,
                (SELECT COUNT(DISTINCT n.id) FROM novel_categories nc
                 JOIN novels n ON n.id = nc.novel_id
                 WHERE nc.category_id = cat.id {$novelFilter}) AS novel_count
             FROM categories cat
             ORDER BY cat.name ASC"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM categories WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    public static function idsForNovel(int $novelId): array
    {
        $rows = Database::fetchAll(
            'SELECT category_id FROM novel_categories WHERE novel_id = ?',
            [$novelId]
        );
        return array_map(static fn (array $row): int => (int) $row['category_id'], $rows);
    }

    public static function usageCount(int $categoryId): int
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM novel_categories WHERE category_id = ?',
            [$categoryId]
        );
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = slugify($name);
        $slug = $base;
        $suffix = 2;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM categories WHERE slug = ?';
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

    public static function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE name = ?';
        $params = [trim($name)];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        return (int) Database::fetchColumn($sql, $params) > 0;
    }

    public static function create(string $name, string $slug, ?string $description): int
    {
        return Database::insert(
            'INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)',
            [trim($name), $slug, $description]
        );
    }

    public static function update(int $id, string $name, string $slug, ?string $description): void
    {
        Database::execute(
            'UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?',
            [trim($name), $slug, $description, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM categories WHERE id = ?', [$id]);
    }

    /**
     * @param array<int, mixed> $categoryIds
     */
    public static function syncForNovel(int $novelId, array $categoryIds): void
    {
        $ids = [];
        foreach ($categoryIds as $value) {
            if (is_scalar($value) && ctype_digit((string) $value)) {
                $ids[] = (int) $value;
            }
        }
        $ids = array_values(array_unique($ids));

        Database::transaction(static function () use ($novelId, $ids): void {
            Database::execute('DELETE FROM novel_categories WHERE novel_id = ?', [$novelId]);
            foreach ($ids as $categoryId) {
                Database::execute(
                    'INSERT IGNORE INTO novel_categories (novel_id, category_id)
                     SELECT ?, id FROM categories WHERE id = ?',
                    [$novelId, $categoryId]
                );
            }
        });
    }
}
