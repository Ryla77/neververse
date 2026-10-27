<?php

declare(strict_types=1);

use App\Core\View;

$user = current_user();
$spotlight = $featured ?? ($popular[0] ?? null);
$genreIcons = [
    'romansa' => 'heart',
    'fantasi' => 'compass',
    'reinkarnasi' => 'clock',
    'kerajaan' => 'shield',
    'petualangan' => 'compass',
    'misteri' => 'search',
];
?>
<section class="hero">
    <div class="hero__orb hero__orb--one" aria-hidden="true"></div>
    <div class="hero__orb hero__orb--two" aria-hidden="true"></div>
    <div class="container hero__inner">
        <div class="hero__content">
            <span class="eyebrow"><?= icon('book-open', 'icon icon--sm') ?> Ruang baca digital</span>
            <h1>Setiap cerita punya lentera yang menuntun pulang.</h1>
            <p class="lead">Temukan romansa yang hangat, dunia fantasi yang luas, dan misteri yang membuatmu membaca satu bab lagi. Semua di satu tempat, nyaman di mata.</p>

            <form class="searchbar" action="<?= e(url('jelajahi')) ?>" method="get" role="search">
                <?= icon('search') ?>
                <input type="search" name="q" placeholder="Cari judul atau nama penulis…" aria-label="Cari novel berdasarkan judul atau penulis" maxlength="100">
                <button class="btn btn--primary" type="submit">Cari novel</button>
            </form>

            <div class="hero__chips">
                <span class="small muted">Populer:</span>
                <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                    <a class="chip chip--sm" href="<?= e(url('jelajahi?genre=' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a>
                <?php endforeach; ?>
            </div>

            <dl class="hero__stats">
                <div><dt>Novel</dt><dd><?= number_format((int) $stats['novels'], 0, ',', '.') ?></dd></div>
                <div><dt>Bab</dt><dd><?= number_format((int) $stats['chapters'], 0, ',', '.') ?></dd></div>
                <div><dt>Penulis</dt><dd><?= number_format((int) $stats['authors'], 0, ',', '.') ?></dd></div>
                <div><dt>Pembaca</dt><dd><?= number_format((int) $stats['readers'], 0, ',', '.') ?></dd></div>
            </dl>
        </div>

        <div class="hero__visual">
            <?php if ($spotlight !== null): ?>
                <div class="spotlight">
                    <div class="spotlight__glow" aria-hidden="true"></div>
                    <a class="spotlight__cover" href="<?= e(url('novel/' . $spotlight['slug'])) ?>" aria-label="Lihat <?= e($spotlight['title']) ?>">
                        <img src="<?= e(cover_url($spotlight['cover_path'])) ?>" alt="Sampul <?= e($spotlight['title']) ?>" width="300" height="450">
                    </a>
                    <div class="spotlight__body">
                        <span class="badge badge--gold"><?= icon('star-fill', 'icon icon--xs') ?> Pilihan editor</span>
                        <h2><?= e($spotlight['title']) ?></h2>
                        <p class="spotlight__author">oleh <?= e($spotlight['author_name']) ?></p>
                        <p class="spotlight__excerpt"><?= e(excerpt_text((string) $spotlight['synopsis'], 160)) ?></p>
                        <div class="spotlight__meta">
                            <?= stars_html($spotlight['rating_avg'] ?? null) ?>
                            <span><?= $spotlight['rating_avg'] !== null ? e(number_format((float) $spotlight['rating_avg'], 1, ',', '.')) : 'Baru' ?> · <?= (int) $spotlight['chapter_count'] ?> bab</span>
                        </div>
                        <a class="btn btn--primary" href="<?= e(url('novel/' . $spotlight['slug'])) ?>">Lihat novel <?= icon('arrow-right', 'icon icon--sm') ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Mulai dari sini</span>
                <h2>Telusuri genre</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('jelajahi')) ?>">Semua novel <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <div class="genre-grid">
            <?php foreach ($categories as $index => $category): ?>
                <a class="genre-card" href="<?= e(url('jelajahi?genre=' . rawurlencode($category['slug']))) ?>">
                    <span class="genre-card__icon"><?= icon($genreIcons[$category['slug']] ?? 'book-open') ?></span>
                    <span class="genre-card__body">
                        <strong><?= e($category['name']) ?></strong>
                        <span><?= (int) $category['novel_count'] ?> novel</span>
                    </span>
                    <?= icon('arrow-right', 'icon icon--xs genre-card__arrow') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($popular !== []): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Paling banyak dibuka</span>
                <h2>Sedang populer</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('jelajahi?sort=popular')) ?>">Lihat semua <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <div class="novel-grid">
            <?php foreach ($popular as $novel): ?>
                <?php View::partial('partials/novel-card', ['novel' => $novel]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($latest !== []): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Baru hadir</span>
                <h2>Novel terbaru</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('jelajahi?sort=latest')) ?>">Lihat semua <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <div class="novel-grid">
            <?php foreach ($latest as $novel): ?>
                <?php View::partial('partials/novel-card', ['novel' => $novel]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Selalu bergerak</span>
                <h2>Baru diperbarui</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('jelajahi?sort=updated')) ?>">Lihat semua <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <div class="home-split">
            <div class="novel-grid novel-grid--compact">
                <?php foreach (array_slice($updating, 0, 4) as $novel): ?>
                    <?php View::partial('partials/novel-card', ['novel' => $novel]); ?>
                <?php endforeach; ?>
            </div>
            <aside class="panel update-panel">
                <div class="panel__head">
                    <h3><?= icon('clock', 'icon icon--sm') ?> Bab terbaru</h3>
                </div>
                <?php if ($latestChapters === []): ?>
                    <p class="muted small">Belum ada bab yang dipublikasikan.</p>
                <?php else: ?>
                    <ol class="timeline">
                        <?php foreach ($latestChapters as $item): ?>
                            <li>
                                <a href="<?= e(url('novel/' . $item['novel_slug'] . '/baca/' . (int) $item['chapter_number'])) ?>">
                                    <span class="timeline__title"><?= e($item['novel_title']) ?></span>
                                    <span class="timeline__meta">Bab <?= (int) $item['chapter_number'] ?> · <?= e($item['title']) ?></span>
                                </a>
                                <span class="timeline__time"><?= e(time_ago((string) $item['published_at'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<?php if ($recommended !== []): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Dipilih pembaca</span>
                <h2>Rekomendasi untukmu</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('jelajahi?sort=rating')) ?>">Rating tertinggi <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <div class="recommend-grid">
            <?php foreach ($recommended as $novel): ?>
                <article class="recommend-card">
                    <a class="recommend-card__cover" href="<?= e(url('novel/' . $novel['slug'])) ?>">
                        <img src="<?= e(cover_url($novel['cover_path'])) ?>" alt="Sampul <?= e($novel['title']) ?>" width="160" height="240" loading="lazy">
                    </a>
                    <div class="recommend-card__body">
                        <p class="novel-card__genre"><?= e($novel['genre_names'] ?: 'Tanpa genre') ?></p>
                        <h3><a href="<?= e(url('novel/' . $novel['slug'])) ?>"><?= e($novel['title']) ?></a></h3>
                        <p class="small muted">oleh <?= e($novel['author_name']) ?></p>
                        <p class="recommend-card__excerpt"><?= e(excerpt_text((string) $novel['synopsis'], 130)) ?></p>
                        <div class="spotlight__meta">
                            <?= stars_html($novel['rating_avg'] ?? null) ?>
                            <span><?= $novel['rating_avg'] !== null ? e(number_format((float) $novel['rating_avg'], 1, ',', '.')) : 'Baru' ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="cta-band">
            <div>
                <span class="eyebrow">Ruang untuk ceritamu</span>
                <h2>Punya cerita yang ingin dibaca orang lain?</h2>
                <p>Buat novel, tulis bab per bab, simpan sebagai draf, lalu terbitkan saat kamu siap. Pembaca dapat menyimpan dan melanjutkan bacaan mereka.</p>
            </div>
            <div class="cta-band__actions">
                <?php if ($user === null): ?>
                    <a class="btn btn--primary btn--lg" href="<?= e(url('daftar')) ?>">Daftar gratis <?= icon('arrow-right', 'icon icon--sm') ?></a>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url('masuk')) ?>">Sudah punya akun</a>
                <?php elseif ($user['role'] === 'reader'): ?>
                    <a class="btn btn--primary btn--lg" href="<?= e(url('profil')) ?>#jadi-penulis">Jadi penulis <?= icon('arrow-right', 'icon icon--sm') ?></a>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url('perpustakaan')) ?>">Buka perpustakaan</a>
                <?php elseif ($user['role'] === 'admin'): ?>
                    <a class="btn btn--primary btn--lg" href="<?= e(url('admin')) ?>">Buka dashboard admin <?= icon('arrow-right', 'icon icon--sm') ?></a>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url('tulis')) ?>">Ruang penulis</a>
                <?php else: ?>
                    <a class="btn btn--primary btn--lg" href="<?= e(url('tulis/novel/baru')) ?>">Tulis novel baru <?= icon('arrow-right', 'icon icon--sm') ?></a>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url('tulis')) ?>">Dashboard penulis</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
