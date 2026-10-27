<?php

declare(strict_types=1);

use App\Core\View;

$buildUrl = static function (array $overrides = []) use ($filters): string {
    $params = array_merge([
        'q' => $filters['q'],
        'genre' => $filters['genre'],
        'sort' => $filters['sort'],
    ], $overrides);
    $params = array_filter(
        $params,
        static fn (mixed $value): bool => $value !== '' && $value !== null
    );
    return url('jelajahi' . ($params !== [] ? '?' . http_build_query($params) : ''));
};
$pageBase = '/jelajahi';
$pageQuery = array_filter(
    ['q' => $filters['q'], 'genre' => $filters['genre'], 'sort' => $filters['sort']],
    static fn (mixed $value): bool => $value !== '' && $value !== null
);
$sortLabels = [
    'latest' => 'Terbaru',
    'popular' => 'Terpopuler',
    'rating' => 'Rating tertinggi',
    'updated' => 'Baru diperbarui',
];
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('/')) ?>">Beranda</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Jelajahi</span>
        </nav>
        <div class="page-hero__inner">
            <div>
                <span class="eyebrow">Katalog novel</span>
                <h1>Jelajahi dunia baru</h1>
                <p class="lead">Cari berdasarkan judul atau penulis, saring genre, dan urutkan sesuai seleramu.</p>
            </div>
            <div class="page-hero__count">
                <strong><?= number_format((int) $result['total'], 0, ',', '.') ?></strong>
                <span>novel ditemukan</span>
            </div>
        </div>
    </div>
</section>

<section class="section section--top">
    <div class="container">
        <form class="filter-bar" action="<?= e(url('jelajahi')) ?>" method="get" role="search">
            <div class="filter-bar__search">
                <?= icon('search', 'icon icon--sm') ?>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari judul atau penulis…" aria-label="Cari novel" maxlength="100">
            </div>
            <input type="hidden" name="genre" value="<?= e($filters['genre']) ?>">
            <div class="filter-bar__select">
                <label class="sr-only" for="sort">Urutkan</label>
                <select class="select" id="sort" name="sort">
                    <?php foreach ($sortLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $filters['sort'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn--primary" type="submit">Terapkan</button>
            <?php if ($filters['q'] !== '' || $filters['genre'] !== '' || $filters['sort'] !== 'latest'): ?>
                <a class="btn btn--ghost" href="<?= e(url('jelajahi')) ?>">Reset</a>
            <?php endif; ?>
        </form>

        <div class="chip-row chip-row--filter" aria-label="Filter genre">
            <a class="chip<?= $filters['genre'] === '' ? ' is-active' : '' ?>" href="<?= e($buildUrl(['genre' => ''])) ?>">Semua genre</a>
            <?php foreach ($categories as $category): ?>
                <a class="chip<?= $filters['genre'] === $category['slug'] ? ' is-active' : '' ?>" href="<?= e($buildUrl(['genre' => $category['slug']])) ?>">
                    <?= e($category['name']) ?> <span><?= (int) $category['novel_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($activeGenre !== null || $filters['q'] !== ''): ?>
            <div class="active-filter">
                <span class="small muted">Menampilkan</span>
                <?php if ($filters['q'] !== ''): ?>
                    <span class="badge badge--gold">“<?= e($filters['q']) ?>”</span>
                <?php endif; ?>
                <?php if ($activeGenre !== null): ?>
                    <span class="badge badge--gold"><?= e($activeGenre['name']) ?></span>
                <?php endif; ?>
                <a class="link-inline" href="<?= e(url('jelajahi')) ?>">Hapus filter</a>
            </div>
        <?php endif; ?>

        <?php if ($result['items'] === []): ?>
            <?php View::partial('partials/empty-state', [
                'icon' => 'search',
                'title' => 'Novel tidak ditemukan',
                'message' => 'Coba kata kunci lain, pilih genre berbeda, atau hapus filter yang aktif.',
                'actionUrl' => url('jelajahi'),
                'actionLabel' => 'Tampilkan semua novel',
            ]); ?>
        <?php else: ?>
            <div class="novel-grid novel-grid--catalog">
                <?php foreach ($result['items'] as $novel): ?>
                    <?php View::partial('partials/novel-card', ['novel' => $novel, 'editorial' => true]); ?>
                <?php endforeach; ?>
            </div>

            <?php View::partial('partials/pagination', [
                'result' => $result,
                'pageBase' => $pageBase,
                'query' => $pageQuery,
            ]); ?>
        <?php endif; ?>
    </div>
</section>
