<?php

declare(strict_types=1);

$pages = (int) ($result['pages'] ?? 0);
$current = (int) ($result['page'] ?? 1);
if ($pages <= 1) {
    return;
}

$pageBase = $pageBase ?? request_path();
$query = $query ?? [];
$makeUrl = static function (int $page) use ($pageBase, $query): string {
    $params = array_filter(
        array_merge($query, ['page' => $page]),
        static fn (mixed $value): bool => $value !== '' && $value !== null
    );
    return url($pageBase . '?' . http_build_query($params));
};
$lastShown = 0;
?>
<nav class="pagination" aria-label="Navigasi halaman">
    <?php if ($current > 1): ?>
        <a class="pagination__arrow" href="<?= e($makeUrl($current - 1)) ?>" aria-label="Halaman sebelumnya"><?= icon('chevron-left') ?></a>
    <?php else: ?>
        <span class="pagination__arrow is-disabled" aria-hidden="true"><?= icon('chevron-left') ?></span>
    <?php endif; ?>

    <?php for ($page = 1; $page <= $pages; $page++): ?>
        <?php if ($page === 1 || $page === $pages || abs($page - $current) <= 2): ?>
            <a class="pagination__link<?= $page === $current ? ' is-current' : '' ?>" href="<?= e($makeUrl($page)) ?>"
               <?= $page === $current ? 'aria-current="page"' : '' ?>><?= $page ?></a>
            <?php $lastShown = $page; ?>
        <?php elseif ($page - $lastShown === 1): ?>
            <span class="pagination__gap" aria-hidden="true">…</span>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($current < $pages): ?>
        <a class="pagination__arrow" href="<?= e($makeUrl($current + 1)) ?>" aria-label="Halaman berikutnya"><?= icon('chevron-right') ?></a>
    <?php else: ?>
        <span class="pagination__arrow is-disabled" aria-hidden="true"><?= icon('chevron-right') ?></span>
    <?php endif; ?>
</nav>
