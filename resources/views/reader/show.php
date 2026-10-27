<?php

declare(strict_types=1);

use App\Core\View;

$user = current_user();
$chapterTitleMap = [];
foreach ($chapters as $item) {
    $chapterTitleMap[(int) $item['chapter_number']] = $item['title'];
}
$prevTitle = $prev !== null ? ($chapterTitleMap[$prev] ?? '') : '';
$nextTitle = $next !== null ? ($chapterTitleMap[$next] ?? '') : '';
?>
<div class="reader" id="reader"
     data-authenticated="<?= $user !== null ? '1' : '0' ?>"
     data-progress-url="<?= e(url('novel/' . $novel['slug'] . '/baca/' . (int) $chapter['chapter_number'] . '/progres')) ?>"
     data-novel-slug="<?= e($novel['slug']) ?>"
     data-chapter-id="<?= (int) $chapter['id'] ?>"
     data-chapter-number="<?= (int) $chapter['chapter_number'] ?>"
     data-prev-url="<?= $prev !== null ? e(url('novel/' . $novel['slug'] . '/baca/' . $prev)) : '' ?>"
     data-next-url="<?= $next !== null ? e(url('novel/' . $novel['slug'] . '/baca/' . $next)) : '' ?>">
    <div class="reader-progress" aria-hidden="true"><span class="reader-progress__bar" data-reader-progress-bar></span></div>

    <header class="reader-topbar">
        <div class="container reader-topbar__inner">
            <div class="reader-topbar__left">
                <a class="reader-brand" href="<?= e(url('/')) ?>">Novel<span>Verse</span></a>
                <span class="reader-divider" aria-hidden="true"></span>
                <a class="reader-back" href="<?= e(url('novel/' . $novel['slug'])) ?>">
                    <?= icon('arrow-left', 'icon icon--sm') ?>
                    <span>Detail novel</span>
                </a>
            </div>

            <div class="reader-topbar__title">
                <span class="small muted"><?= e($novel['title']) ?></span>
                <strong>Bab <?= (int) $chapter['chapter_number'] ?></strong>
            </div>

            <div class="reader-topbar__actions">
                <?php if ($prev !== null): ?>
                    <a class="icon-btn reader-step" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $prev)) ?>" aria-label="Bab sebelumnya" data-reader-prev><?= icon('chevron-left') ?></a>
                <?php else: ?>
                    <span class="icon-btn reader-step is-disabled" aria-hidden="true"><?= icon('chevron-left') ?></span>
                <?php endif; ?>

                <button class="btn btn--ghost btn--sm reader-toc-toggle" type="button" data-reader-toc aria-expanded="false" aria-controls="reader-toc">
                    <?= icon('list', 'icon icon--sm') ?><span class="reader-btn-label">Daftar bab</span>
                </button>

                <details class="reader-settings">
                    <summary class="btn btn--ghost btn--sm" aria-label="Pengaturan tampilan bacaan">
                        <?= icon('sliders', 'icon icon--sm') ?><span class="reader-btn-label">Tampilan</span>
                    </summary>
                    <div class="reader-settings__panel">
                        <div class="reader-settings__head">
                            <strong>Pengaturan baca</strong>
                            <span class="small muted">Tersimpan di perangkat ini</span>
                        </div>

                        <fieldset class="reader-setting">
                            <legend>Tema baca</legend>
                            <div class="segmented" role="group" aria-label="Tema area baca">
                                <button type="button" data-reader-theme="auto">Ikuti situs</button>
                                <button type="button" data-reader-theme="light">Terang</button>
                                <button type="button" data-reader-theme="dark">Gelap</button>
                            </div>
                        </fieldset>

                        <div class="reader-setting">
                            <label for="reader-font">Ukuran huruf <output data-reader-font-value>19 px</output></label>
                            <input type="range" id="reader-font" min="16" max="28" step="1" value="19" data-reader-font>
                        </div>

                        <div class="reader-setting">
                            <label for="reader-width">Lebar area baca <output data-reader-width-value>720 px</output></label>
                            <input type="range" id="reader-width" min="560" max="960" step="20" value="720" data-reader-width>
                        </div>

                        <div class="reader-setting">
                            <label for="reader-spacing">Jarak antarbaris <output data-reader-spacing-value>1,9</output></label>
                            <input type="range" id="reader-spacing" min="1.5" max="2.4" step="0.1" value="1.9" data-reader-spacing>
                        </div>

                        <button class="btn btn--soft btn--sm btn--block" type="button" data-reader-reset>Atur ulang tampilan</button>
                    </div>
                </details>

                <?php if ($next !== null): ?>
                    <a class="icon-btn reader-step" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $next)) ?>" aria-label="Bab berikutnya" data-reader-next><?= icon('chevron-right') ?></a>
                <?php else: ?>
                    <span class="icon-btn reader-step is-disabled" aria-hidden="true"><?= icon('chevron-right') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container reader-layout">
        <aside class="reader-toc" id="reader-toc" hidden aria-label="Daftar bab">
            <div class="reader-toc__head">
                <div>
                    <span class="eyebrow">Daftar bab</span>
                    <strong><?= e($novel['title']) ?></strong>
                </div>
                <button class="icon-btn" type="button" data-reader-toc-close aria-label="Tutup daftar bab"><?= icon('x', 'icon icon--sm') ?></button>
            </div>
            <?php if (count($chapters) > 6): ?>
                <div class="chapter-filter chapter-filter--reader">
                    <?= icon('search', 'icon icon--sm') ?>
                    <input type="search" data-toc-filter placeholder="Cari bab…" aria-label="Cari bab">
                </div>
            <?php endif; ?>
            <ol class="reader-toc__list" data-toc-list>
                <?php foreach ($chapters as $item): ?>
                    <?php
                    $num = (int) $item['chapter_number'];
                    $isCurrent = $num === (int) $chapter['chapter_number'];
                    $isRead = in_array((int) $item['id'], $readIds, true);
                    ?>
                    <li data-toc-item>
                        <a class="reader-toc__link<?= $isCurrent ? ' is-current' : '' ?><?= $isRead ? ' is-read' : '' ?>"
                           href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $num)) ?>"
                           data-toc-chapter="<?= $num ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
                            <span class="reader-toc__num"><?= $num ?></span>
                            <span class="reader-toc__title"><?= e($item['title']) ?></span>
                            <?php if ($isCurrent): ?>
                                <span class="reader-toc__state">Dibaca</span>
                            <?php elseif ($isRead): ?>
                                <?= icon('check', 'icon icon--xs reader-toc__check') ?>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        </aside>

        <article class="reader-paper" id="reader-paper">
            <header class="reader-head">
                <span class="eyebrow"><?= e($novel['title']) ?> · Bab <?= (int) $chapter['chapter_number'] ?></span>
                <h1><?= e($chapter['title']) ?></h1>
                <div class="reader-meta">
                    <span><?= icon('calendar', 'icon icon--xs') ?> <?= e(format_date((string) ($chapter['published_at'] ?? $chapter['created_at']))) ?></span>
                    <span><?= icon('book-open', 'icon icon--xs') ?> <?= number_format($wordCount, 0, ',', '.') ?> kata</span>
                    <span><?= icon($user !== null ? 'check' : 'info', 'icon icon--xs') ?> <?= $user !== null ? 'Progres tersimpan ke akun' : 'Progres disimpan di perangkat' ?></span>
                    <?php if ($user !== null): ?>
                        <form class="inline-form" action="<?= e(url('novel/' . $novel['slug'] . '/simpan')) ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="link-action" type="submit">
                                <?= icon($bookmarked ? 'heart-fill' : 'heart', 'icon icon--xs') ?>
                                <?= $bookmarked ? 'Tersimpan' : 'Simpan novel' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </header>

            <div class="reader-content" data-reader-content>
                <?= chapter_html((string) $chapter['content']) ?>
            </div>

            <div class="reader-end">
                <span class="reader-end__mark"><?= icon('book-open') ?></span>
                <h2>Akhir bab <?= (int) $chapter['chapter_number'] ?></h2>
                <p class="small muted">Teruskan perjalananmu, atau kembali ke detail novel untuk melihat diskusi pembaca.</p>
                <div class="reader-end__actions">
                    <a class="btn btn--ghost" href="<?= e(url('novel/' . $novel['slug'])) ?>"><?= icon('arrow-left', 'icon icon--sm') ?>Detail novel</a>
                    <a class="btn btn--ghost" href="<?= e(url('novel/' . $novel['slug'] . '#komentar')) ?>"><?= icon('chat', 'icon icon--sm') ?>Komentar</a>
                    <?php if ($next !== null): ?>
                        <a class="btn btn--primary" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $next)) ?>">Bab berikutnya <?= icon('arrow-right', 'icon icon--sm') ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <nav class="reader-nav" aria-label="Navigasi bab">
                <?php if ($prev !== null): ?>
                    <a class="reader-nav__card" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $prev)) ?>" data-reader-prev>
                        <span class="reader-nav__label"><?= icon('arrow-left', 'icon icon--xs') ?> Sebelumnya</span>
                        <strong>Bab <?= $prev ?><?= $prevTitle !== '' ? ': ' . e($prevTitle) : '' ?></strong>
                    </a>
                <?php else: ?>
                    <span class="reader-nav__card is-disabled">
                        <span class="reader-nav__label">Bab pertama</span>
                        <strong>Tidak ada bab sebelumnya</strong>
                    </span>
                <?php endif; ?>

                <?php if ($next !== null): ?>
                    <a class="reader-nav__card reader-nav__card--next" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $next)) ?>" data-reader-next>
                        <span class="reader-nav__label">Berikutnya <?= icon('arrow-right', 'icon icon--xs') ?></span>
                        <strong>Bab <?= $next ?><?= $nextTitle !== '' ? ': ' . e($nextTitle) : '' ?></strong>
                    </a>
                <?php else: ?>
                    <span class="reader-nav__card reader-nav__card--next is-disabled">
                        <span class="reader-nav__label">Bab terakhir</span>
                        <strong>Cerita sejauh ini sudah kamu baca</strong>
                    </span>
                <?php endif; ?>
            </nav>

            <p class="reader-save-status small muted" data-reader-save-status aria-live="polite"></p>
        </article>
    </div>
</div>
