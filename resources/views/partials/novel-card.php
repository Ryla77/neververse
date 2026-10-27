<?php

declare(strict_types=1);

if (empty($novel)) {
    return;
}

$featured = !empty($novel['is_featured']);
?>
<article class="novel-card">
    <a class="novel-card__cover" href="<?= e(url('novel/' . $novel['slug'])) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e(cover_url($novel['cover_path'])) ?>" alt="" width="300" height="450" loading="lazy" decoding="async">
        <?php if ($featured): ?>
            <span class="novel-card__flag"><?= icon('star-fill', 'icon icon--xs') ?> Unggulan</span>
        <?php endif; ?>
        <span class="novel-card__status novel-card__status--<?= e($novel['status']) ?>"><?= e(novel_status_label((string) $novel['status'])) ?></span>
    </a>
    <div class="novel-card__body">
        <p class="novel-card__genre"><?= e($novel['genre_names'] ?: 'Tanpa genre') ?></p>
        <h3 class="novel-card__title"><a href="<?= e(url('novel/' . $novel['slug'])) ?>"><?= e($novel['title']) ?></a></h3>
        <p class="novel-card__author">oleh <?= e($novel['author_name']) ?></p>
        <?php if (!empty($editorial)): ?>
            <p class="novel-card__excerpt"><?= e(excerpt_text((string) $novel['synopsis'], 110)) ?></p>
        <?php endif; ?>
        <div class="novel-card__meta">
            <span class="rating-inline">
                <?= stars_html($novel['rating_avg'] ?? null) ?>
                <span><?= $novel['rating_avg'] !== null ? e(number_format((float) $novel['rating_avg'], 1, ',', '.')) : 'Baru' ?></span>
            </span>
            <span class="dot-sep" aria-hidden="true"></span>
            <span><?= (int) $novel['chapter_count'] ?> bab</span>
        </div>
    </div>
</article>
