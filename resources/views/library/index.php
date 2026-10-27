<?php

declare(strict_types=1);

use App\Core\View;

$historyUrl = static function (array $item): string {
    if (!empty($item['chapter_number'])) {
        return url('novel/' . $item['slug'] . '/baca/' . (int) $item['chapter_number']);
    }
    return url('novel/' . $item['slug']);
};
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('/')) ?>">Beranda</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Perpustakaan</span>
        </nav>
        <div class="page-hero__inner">
            <div>
                <span class="eyebrow">Koleksi pribadi</span>
                <h1>Perpustakaan saya</h1>
                <p class="lead">Semua novel yang kamu simpan dan riwayat bacaan terakhir, dalam satu tempat.</p>
            </div>
            <div class="page-hero__actions">
                <a class="btn btn--primary" href="<?= e(url('jelajahi')) ?>"><?= icon('compass', 'icon icon--sm') ?>Temukan novel</a>
            </div>
        </div>
    </div>
</section>

<section class="section section--top">
    <div class="container">
        <?php if ($continueReading !== null): ?>
            <article class="continue-card">
                <div class="continue-card__glow" aria-hidden="true"></div>
                <a class="continue-card__cover" href="<?= e($historyUrl($continueReading)) ?>">
                    <img src="<?= e(cover_url($continueReading['cover_path'])) ?>" alt="Sampul <?= e($continueReading['title']) ?>" width="160" height="240">
                </a>
                <div class="continue-card__body">
                    <span class="eyebrow"><?= icon('clock', 'icon icon--sm') ?> Lanjutkan bacaan</span>
                    <h2><?= e($continueReading['title']) ?></h2>
                    <p class="small muted">oleh <?= e($continueReading['author_name']) ?> · <?= e(time_ago((string) $continueReading['last_read_at'])) ?></p>
                    <p class="continue-card__chapter">
                        <?php if (!empty($continueReading['chapter_number'])): ?>
                            Bab <?= (int) $continueReading['chapter_number'] ?><?= !empty($continueReading['chapter_title']) ? ': ' . e($continueReading['chapter_title']) : '' ?>
                        <?php else: ?>
                            Buka kembali novel ini
                        <?php endif; ?>
                    </p>
                    <div class="reading-progress">
                        <div class="reading-progress__head">
                            <span>Progres bab</span>
                            <strong><?= number_format((float) $continueReading['progress_percent'], 0, ',', '.') ?>%</strong>
                        </div>
                        <div class="progress-track"><span style="width:<?= e((string) max(0, min(100, (float) $continueReading['progress_percent']))) ?>%"></span></div>
                    </div>
                    <a class="btn btn--primary" href="<?= e($historyUrl($continueReading)) ?>">Lanjutkan membaca <?= icon('arrow-right', 'icon icon--sm') ?></a>
                </div>
            </article>
        <?php endif; ?>

        <div class="section-head section-head--spaced">
            <div>
                <span class="eyebrow">Disimpan</span>
                <h2>Novel favorit <span class="count-badge"><?= count($bookmarks) ?></span></h2>
            </div>
            <?php if ($bookmarks !== []): ?>
                <a class="link-arrow" href="<?= e(url('jelajahi')) ?>">Tambah koleksi <?= icon('plus', 'icon icon--xs') ?></a>
            <?php endif; ?>
        </div>

        <?php if ($bookmarks === []): ?>
            <?php View::partial('partials/empty-state', [
                'icon' => 'bookmark',
                'title' => 'Rak favoritmu masih kosong',
                'message' => 'Buka sebuah novel lalu tekan “Tambah ke perpustakaan” untuk menyimpannya di sini.',
                'actionUrl' => url('jelajahi'),
                'actionLabel' => 'Jelajahi novel',
            ]); ?>
        <?php else: ?>
            <div class="library-grid">
                <?php foreach ($bookmarks as $bookmark): ?>
                    <?php $continueUrl = !empty($bookmark['last_chapter']) ? url('novel/' . $bookmark['slug'] . '/baca/' . (int) $bookmark['last_chapter']) : url('novel/' . $bookmark['slug']); ?>
                    <article class="library-card">
                        <a class="library-card__cover" href="<?= e(url('novel/' . $bookmark['slug'])) ?>">
                            <img src="<?= e(cover_url($bookmark['cover_path'])) ?>" alt="Sampul <?= e($bookmark['title']) ?>" width="120" height="180" loading="lazy">
                        </a>
                        <div class="library-card__body">
                            <p class="novel-card__genre"><?= e(novel_status_label((string) $bookmark['status'])) ?></p>
                            <h3><a href="<?= e(url('novel/' . $bookmark['slug'])) ?>"><?= e($bookmark['title']) ?></a></h3>
                            <p class="small muted">oleh <?= e($bookmark['author_name']) ?></p>
                            <div class="novel-card__meta">
                                <?= stars_html($bookmark['rating_avg'] ?? null) ?>
                                <span><?= (int) $bookmark['chapter_count'] ?> bab</span>
                            </div>
                            <?php if (!empty($bookmark['progress_percent']) || !empty($bookmark['last_chapter'])): ?>
                                <div class="reading-progress reading-progress--sm">
                                    <div class="reading-progress__head">
                                        <span><?= !empty($bookmark['last_chapter']) ? 'Bab ' . (int) $bookmark['last_chapter'] : 'Belum dibaca' ?></span>
                                        <strong><?= number_format((float) ($bookmark['progress_percent'] ?? 0), 0, ',', '.') ?>%</strong>
                                    </div>
                                    <div class="progress-track"><span style="width:<?= e((string) max(0, min(100, (float) ($bookmark['progress_percent'] ?? 0)))) ?>%"></span></div>
                                </div>
                            <?php endif; ?>
                            <div class="library-card__actions">
                                <a class="btn btn--soft btn--sm" href="<?= e($continueUrl) ?>"><?= icon('book-open', 'icon icon--sm') ?>Baca</a>
                                <form class="inline-form" action="<?= e(url('perpustakaan/' . $bookmark['slug'] . '/hapus')) ?>" method="post" data-confirm="Hapus novel ini dari perpustakaan?">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn icon-btn--danger" type="submit" aria-label="Hapus <?= e($bookmark['title']) ?> dari perpustakaan" title="Hapus dari perpustakaan"><?= icon('trash', 'icon icon--sm') ?></button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-head section-head--spaced">
            <div>
                <span class="eyebrow">Jejak membaca</span>
                <h2>Riwayat bacaan <span class="count-badge"><?= (int) $historyTotal ?></span></h2>
            </div>
        </div>

        <?php if ($history === []): ?>
            <?php View::partial('partials/empty-state', [
                'icon' => 'clock',
                'title' => 'Belum ada riwayat membaca',
                'message' => 'Setelah kamu membuka sebuah bab, riwayat dan progresmu akan muncul di sini.',
                'actionUrl' => url('jelajahi'),
                'actionLabel' => 'Mulai membaca',
            ]); ?>
        <?php else: ?>
            <div class="history-list">
                <?php foreach ($history as $item): ?>
                    <article class="history-item">
                        <a class="history-item__cover" href="<?= e($historyUrl($item)) ?>">
                            <img src="<?= e(cover_url($item['cover_path'])) ?>" alt="Sampul <?= e($item['title']) ?>" width="72" height="108" loading="lazy">
                        </a>
                        <div class="history-item__body">
                            <h3><a href="<?= e($historyUrl($item)) ?>"><?= e($item['title']) ?></a></h3>
                            <p class="small muted">oleh <?= e($item['author_name']) ?> · <?= e((int) $item['chapter_count']) ?> bab</p>
                            <p class="history-item__chapter">
                                <?php if (!empty($item['chapter_number'])): ?>
                                    Bab <?= (int) $item['chapter_number'] ?><?= !empty($item['chapter_title']) ? ': ' . e($item['chapter_title']) : '' ?>
                                <?php else: ?>
                                    Novel dibuka
                                <?php endif; ?>
                            </p>
                            <div class="progress-track progress-track--sm"><span style="width:<?= e((string) max(0, min(100, (float) $item['progress_percent']))) ?>%"></span></div>
                        </div>
                        <div class="history-item__meta">
                            <span class="small muted"><?= e(time_ago((string) $item['last_read_at'])) ?></span>
                            <a class="btn btn--ghost btn--sm" href="<?= e($historyUrl($item)) ?>">Lanjutkan <?= icon('arrow-right', 'icon icon--xs') ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
