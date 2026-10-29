<?php

declare(strict_types=1);

use App\Core\View;

$user = current_user();
$firstChapter = $chapters[0] ?? null;
$readTarget = $continueChapter ?? $firstChapter;
$startUrl = $readTarget !== null
    ? url('novel/' . $novel['slug'] . '/baca/' . (int) $readTarget['chapter_number'])
    : '#bab';
$startLabel = $continueChapter !== null ? 'Lanjutkan membaca' : 'Mulai membaca';
if ($continueChapter !== null && $progressPercent !== null && $progressPercent > 0) {
    $startLabel .= ' · ' . number_format($progressPercent, 0, ',', '.') . '%';
}
$chapterUrlBase = url('novel/' . $novel['slug'] . '/baca/');
$genreNames = $novel['genre_names'] !== null && $novel['genre_names'] !== '' ? explode(', ', (string) $novel['genre_names']) : [];
$genreSlugs = $novel['genre_slugs'] !== null && $novel['genre_slugs'] !== '' ? explode(',', (string) $novel['genre_slugs']) : [];
$latestChapter = $chapters !== [] ? $chapters[count($chapters) - 1] : null;
?>
<section class="novel-hero">
    <div class="novel-hero__glow" aria-hidden="true"></div>
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('/')) ?>">Beranda</a>
            <span aria-hidden="true">/</span>
            <a href="<?= e(url('jelajahi')) ?>">Jelajahi</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($novel['title']) ?></span>
        </nav>

        <div class="novel-hero__grid">
            <div class="novel-hero__cover">
                <img src="<?= e(cover_url($novel['cover_path'])) ?>" alt="Sampul <?= e($novel['title']) ?>" width="360" height="540">
                <div class="novel-hero__cover-badges">
                    <span class="badge badge--<?= $novel['status'] === 'completed' ? 'gold' : 'muted' ?>"><?= e(novel_status_label((string) $novel['status'])) ?></span>
                    <?php if ($novel['rating_avg'] !== null): ?>
                        <span class="badge badge--dark"><?= icon('star-fill', 'icon icon--xs') ?> <?= e(number_format((float) $novel['rating_avg'], 1, ',', '.')) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="novel-hero__info">
                <div class="chapter-row__genre">
                    <?php foreach ($genreNames as $index => $name): ?>
                        <?php $genreSlug = $genreSlugs[$index] ?? ''; ?>
                        <?php if ($genreSlug !== ''): ?>
                            <a class="chip chip--sm" href="<?= e(url('jelajahi?genre=' . rawurlencode($genreSlug))) ?>"><?= e($name) ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <h1><?= e($novel['title']) ?></h1>
                <p class="novel-hero__author">
                    oleh <strong><?= e($novel['author_name']) ?></strong>
                    <span class="dot-sep" aria-hidden="true"></span>
                    <span>Diperbarui <?= e(time_ago((string) $novel['updated_at'])) ?></span>
                </p>

                <div class="novel-hero__stats">
                    <div class="stat-pill">
                        <?= icon('list', 'icon icon--sm') ?>
                        <span><strong><?= (int) $novel['chapter_count'] ?></strong> bab</span>
                    </div>
                    <div class="stat-pill">
                        <?= icon('eye', 'icon icon--sm') ?>
                        <span><strong><?= number_format((int) $novel['views_count'], 0, ',', '.') ?></strong> dibaca</span>
                    </div>
                    <div class="stat-pill">
                        <?= icon('star', 'icon icon--sm') ?>
                        <span><strong><?= $novel['rating_avg'] !== null ? e(number_format((float) $novel['rating_avg'], 1, ',', '.')) : '—' ?></strong> rating</span>
                    </div>
                    <div class="stat-pill">
                        <?= icon('calendar', 'icon icon--sm') ?>
                        <span><strong><?= (int) $novel['rating_count'] ?></strong> penilai</span>
                    </div>
                </div>

                <div class="synopsis-block">
                    <h2 class="synopsis-block__title">Sinopsis</h2>
                    <p><?= nl2br(e($novel['synopsis'])) ?></p>
                </div>

                <div class="novel-hero__actions">
                    <?php if ($readTarget !== null): ?>
                        <a class="btn btn--primary btn--lg" id="start-reading" href="<?= e($startUrl) ?>"
                           <?php if ($user === null): ?>data-guest-continue data-chapter-url-base="<?= e($chapterUrlBase) ?>" data-guest-label="Lanjutkan membaca"<?php endif; ?>>
                            <?= icon('book-open', 'icon icon--sm') ?> <?= e($startLabel) ?>
                        </a>
                    <?php endif; ?>
                    <form class="inline-form" action="<?= e(url('novel/' . $novel['slug'] . '/simpan')) ?>" method="post">
                        <?= csrf_field() ?>
                        <button class="btn <?= $bookmarked ? 'btn--soft' : 'btn--ghost' ?> btn--lg" type="submit">
                            <?= icon($bookmarked ? 'heart-fill' : 'heart', 'icon icon--sm') ?>
                            <?= $bookmarked ? 'Tersimpan di perpustakaan' : 'Tambah ke perpustakaan' ?>
                        </button>
                    </form>
                </div>

                <?php if ($continueChapter !== null && $progressPercent !== null): ?>
                    <div class="reading-progress">
                        <div class="reading-progress__head">
                            <span><?= icon('clock', 'icon icon--xs') ?> Terakhir di bab <?= (int) $continueChapter['chapter_number'] ?></span>
                            <strong><?= number_format($progressPercent, 0, ',', '.') ?>%</strong>
                        </div>
                        <div class="progress-track"><span style="width:<?= e((string) max(0, min(100, $progressPercent))) ?>%"></span></div>
                    </div>
                <?php endif; ?>

                <p class="novel-hero__note">
                    <?= icon('info', 'icon icon--xs') ?>
                    <?php if ($user !== null): ?>
                        Progres bacaan dan daftar bab yang sudah dibaca tersimpan di akunmu.
                    <?php else: ?>
                        Masuk untuk menyimpan progres ke akun. Tanpa akun, progres disimpan di perangkat ini.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="novel-detail">
    <div class="container detail-grid">
        <div class="detail-main">
            <section class="panel" id="bab">
                <div class="panel__head panel__head--split">
                    <div>
                        <span class="eyebrow">Daftar bab</span>
                        <h2><?= (int) $novel['chapter_count'] ?> bab tersedia</h2>
                    </div>
                    <?php if ($latestChapter !== null): ?>
                        <a class="link-arrow" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . (int) $latestChapter['chapter_number'])) ?>">
                            Bab terakhir <?= icon('arrow-right', 'icon icon--xs') ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (count($chapters) > 5): ?>
                    <div class="chapter-filter">
                        <?= icon('search', 'icon icon--sm') ?>
                        <input type="search" data-chapter-filter placeholder="Cari bab berdasarkan judul…" aria-label="Cari bab dalam daftar">
                    </div>
                <?php endif; ?>
                <ol class="chapter-list" data-chapter-list>
                    <?php foreach ($chapters as $chapter): ?>
                        <?php
                        $isRead = in_array((int) $chapter['id'], $readIds, true);
                        $isCurrent = $lastChapterId !== null && (int) $chapter['id'] === $lastChapterId;
                        ?>
                        <li class="chapter-row<?= $isRead ? ' is-read' : '' ?><?= $isCurrent ? ' is-current' : '' ?>" data-chapter-item>
                            <a href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . (int) $chapter['chapter_number'])) ?>">
                                <span class="chapter-row__num"><?= str_pad((string) $chapter['chapter_number'], 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="chapter-row__body">
                                    <strong><?= e($chapter['title']) ?></strong>
                                    <span class="chapter-row__meta"><?= e(format_date((string) ($chapter['published_at'] ?? $chapter['created_at']))) ?></span>
                                </span>
                                <?php if ($isRead): ?>
                                    <span class="badge badge--read"><?= icon('check', 'icon icon--xs') ?> Dibaca</span>
                                <?php endif; ?>
                                <?= icon('chevron-right', 'icon icon--sm chapter-row__chevron') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <p class="chapter-empty-filter small muted" data-chapter-empty hidden>Tidak ada bab yang cocok dengan pencarian.</p>
            </section>

            <section class="panel comments" id="komentar">
                <div class="panel__head panel__head--split">
                    <div>
                        <span class="eyebrow">Diskusi</span>
                        <h2>Komentar pembaca <span class="count-badge"><?= count($comments) ?></span></h2>
                    </div>
                    <a class="link-arrow" href="#rating">Beri rating <?= icon('star', 'icon icon--xs') ?></a>
                </div>

                <?php if ($user !== null): ?>
                    <?php View::partial('partials/form-errors'); ?>
                    <form class="comment-form" action="<?= e(url('novel/' . $novel['slug'] . '/komentar')) ?>" method="post">
                        <?= csrf_field() ?>
                        <span class="avatar comment-form__avatar"><?= e(initials((string) $user['display_name'])) ?></span>
                        <div class="comment-form__field">
                            <label class="sr-only" for="comment-content">Komentar</label>
                            <textarea class="textarea" id="comment-content" name="content" rows="3" minlength="3" maxlength="1000" required placeholder="Bagikan pendapatmu tentang novel ini…"><?= e(old('content')) ?></textarea>
                            <?php if (form_error('content') !== null): ?>
                                <span class="field-error"><?= e(form_error('content')) ?></span>
                            <?php endif; ?>
                            <div class="comment-form__foot">
                                <span class="small muted">Bersikap sopan. Komentar dapat dimoderasi.</span>
                                <button class="btn btn--primary btn--sm" type="submit"><?= icon('chat', 'icon icon--sm') ?>Kirim</button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="comment-login">
                        <span class="comment-login__icon"><?= icon('chat') ?></span>
                        <div>
                            <strong>Ingin ikut berdiskusi?</strong>
                            <p class="small muted">Masuk atau buat akun gratis untuk meninggalkan komentar dan rating.</p>
                        </div>
                        <div class="comment-login__actions">
                            <a class="btn btn--primary btn--sm" href="<?= e(url('masuk?redirect=' . rawurlencode('/novel/' . $novel['slug']))) ?>">Masuk</a>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('daftar')) ?>">Daftar</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="comment-list">
                    <?php if ($comments === []): ?>
                        <p class="comments-empty small muted">Belum ada komentar. Jadilah pembaca pertama yang membuka diskusi.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <?php $isOwner = $user !== null && (int) $comment['user_id'] === (int) $user['id']; ?>
                            <article class="comment" id="komentar-<?= (int) $comment['id'] ?>">
                                <span class="avatar"><?= e(initials((string) $comment['user_name'])) ?></span>
                                <div class="comment__body">
                                    <header class="comment__head">
                                        <div>
                                            <strong><?= e($comment['user_name']) ?></strong>
                                            <?php if ($comment['user_role'] !== 'reader'): ?>
                                                <span class="badge badge--<?= $comment['user_role'] === 'admin' ? 'gold' : 'muted' ?>"><?= e(role_label((string) $comment['user_role'])) ?></span>
                                            <?php endif; ?>
                                            <span class="comment__time"><?= e(time_ago((string) $comment['created_at'])) ?></span>
                                        </div>
                                    </header>
                                    <div class="comment__content"><?= nl2br(e($comment['content'])) ?></div>
                                    <footer class="comment__actions">
                                        <?php if ($isOwner || ($user !== null && $user['role'] === 'admin')): ?>
                                            <form class="inline-form" action="<?= e(url('komentar/' . (int) $comment['id'] . '/hapus')) ?>" method="post" data-confirm="Hapus komentar ini?">
                                                <?= csrf_field() ?>
                                                <button class="link-danger" type="submit"><?= icon('trash', 'icon icon--xs') ?> Hapus</button>
                                            </form>
                                        <?php elseif ($user !== null): ?>
                                            <?php View::partial('partials/report-form', ['targetType' => 'comment', 'targetId' => (int) $comment['id']]); ?>
                                        <?php endif; ?>
                                    </footer>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <aside class="detail-aside">
            <section class="panel rating-panel" id="rating">
                <div class="panel__head">
                    <h2>Rating pembaca</h2>
                </div>
                <div class="rating-summary">
                    <div class="rating-summary__score">
                        <strong><?= $ratingSummary['average'] !== null ? e(number_format($ratingSummary['average'], 1, ',', '.')) : '—' ?></strong>
                        <span class="small muted">dari 5</span>
                    </div>
                    <div>
                        <?= stars_html($ratingSummary['average']) ?>
                        <p class="small muted"><?= (int) $ratingSummary['total'] ?> penilaian</p>
                    </div>
                </div>

                <?php if ($user !== null): ?>
                    <form class="rating-form" action="<?= e(url('novel/' . $novel['slug'] . '/rating')) ?>" method="post">
                        <?= csrf_field() ?>
                        <p class="label"><?= $userRating !== null ? 'Ubah ratingmu' : 'Beri rating' ?></p>
                        <div class="rating-input" role="radiogroup" aria-label="Pilih rating 1 sampai 5">
                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                <input type="radio" id="rating-<?= $star ?>" name="rating" value="<?= $star ?>" <?= (int) $userRating === $star ? 'checked' : '' ?> required>
                                <label for="rating-<?= $star ?>" title="<?= $star ?> bintang"><?= icon('star-fill') ?><span class="sr-only"><?= $star ?> bintang</span></label>
                            <?php endfor; ?>
                        </div>
                        <button class="btn btn--soft btn--sm btn--block" type="submit">Simpan rating</button>
                    </form>
                <?php else: ?>
                    <p class="small muted">Masuk untuk memberi rating pada novel ini.</p>
                    <a class="btn btn--soft btn--sm btn--block" href="<?= e(url('masuk?redirect=' . rawurlencode('/novel/' . $novel['slug'] . '#rating'))) ?>">Masuk untuk menilai</a>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel__head">
                    <h2>Informasi novel</h2>
                </div>
                <dl class="meta-list">
                    <div><dt>Penulis</dt><dd><?= e($novel['author_name']) ?></dd></div>
                    <div><dt>Status</dt><dd><span class="badge badge--<?= $novel['status'] === 'completed' ? 'gold' : 'muted' ?>"><?= e(novel_status_label((string) $novel['status'])) ?></span></dd></div>
                    <div><dt>Genre</dt><dd><?= e($novel['genre_names'] ?: '—') ?></dd></div>
                    <div><dt>Total bab</dt><dd><?= (int) $novel['chapter_count'] ?></dd></div>
                    <div><dt>Total pembaca</dt><dd><?= number_format((int) $novel['views_count'], 0, ',', '.') ?></dd></div>
                    <div><dt>Rating</dt><dd><?= $novel['rating_avg'] !== null ? e(number_format((float) $novel['rating_avg'], 1, ',', '.')) . ' / 5' : 'Belum ada' ?></dd></div>
                    <div><dt>Diterbitkan</dt><dd><?= e(format_date((string) $novel['created_at'])) ?></dd></div>
                    <div><dt>Diperbarui</dt><dd><?= e(format_date((string) $novel['updated_at'])) ?></dd></div>
                </dl>
                <?php if ($user !== null): ?>
                    <?php View::partial('partials/report-form', ['targetType' => 'novel', 'targetId' => (int) $novel['id']]); ?>
                <?php else: ?>
                    <p class="small muted">Masuk untuk melaporkan konten yang bermasalah.</p>
                <?php endif; ?>
            </section>

            <section class="panel author-panel">
                <span class="avatar avatar--lg"><?= e(initials((string) $novel['author_name'])) ?></span>
                <div>
                    <span class="eyebrow">Penulis</span>
                    <h3><?= e($novel['author_name']) ?></h3>
                    <p class="small muted">Novel ini merupakan karya original yang diterbitkan melalui Luminara.</p>
                </div>
            </section>
        </aside>
    </div>
</section>

<?php if ($related !== []): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Masih satu suasana</span>
                <h2>Novel serupa</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('jelajahi')) ?>">Jelajahi semua <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <div class="novel-grid">
            <?php foreach ($related as $novelItem): ?>
                <?php View::partial('partials/novel-card', ['novel' => $novelItem]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
