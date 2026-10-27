<?php

declare(strict_types=1);

use App\Core\View;

$totalChapters = count($chapters);
$draftCount = $totalChapters - (int) $publishedCount;
?>
<div class="dashboard-page">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('tulis')) ?>">Dashboard penulis</a>
            <span aria-hidden="true">/</span>
            <span><?= e($novel['title']) ?></span>
        </nav>

        <header class="dashboard-head">
            <div class="dashboard-head__identity">
                <img class="dashboard-head__cover" src="<?= e(cover_url($novel['cover_path'])) ?>" alt="Sampul <?= e($novel['title']) ?>" width="72" height="104">
                <div>
                    <span class="eyebrow">Kelola bab</span>
                    <h1><?= e($novel['title']) ?></h1>
                    <p class="lead">
                        <span class="badge badge--<?= $novel['publish_status'] === 'published' ? 'published' : 'draft' ?>"><?= $novel['publish_status'] === 'published' ? 'Novel terbit' : 'Novel draf' ?></span>
                        <span class="badge badge--muted"><?= e(novel_status_label((string) $novel['status'])) ?></span>
                    </p>
                </div>
            </div>
            <div class="dashboard-head__actions">
                <a class="btn btn--ghost" href="<?= e(url('tulis/novel/' . $novel['id'] . '/edit')) ?>"><?= icon('pencil', 'icon icon--sm') ?>Edit novel</a>
                <a class="btn btn--primary" href="<?= e(url('tulis/novel/' . $novel['id'] . '/bab/baru')) ?>"><?= icon('plus', 'icon icon--sm') ?>Bab baru</a>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <div class="stat-grid stat-grid--three">
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('list') ?></span>
                        <div><strong><?= $totalChapters ?></strong><span>Total bab</span></div>
                    </article>
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('check') ?></span>
                        <div><strong><?= (int) $publishedCount ?></strong><span>Bab terbit</span></div>
                    </article>
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('pencil') ?></span>
                        <div><strong><?= max(0, $draftCount) ?></strong><span>Draf</span></div>
                    </article>
                </div>

                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Daftar bab</span>
                            <h2>Bab novel</h2>
                        </div>
                        <?php if ($chapters !== []): ?>
                            <label class="field field--inline">
                                <span class="sr-only">Cari bab</span>
                                <input class="input input--sm" type="search" placeholder="Cari judul bab…" data-chapter-filter aria-label="Cari bab">
                            </label>
                        <?php endif; ?>
                    </div>

                    <?php if ($chapters === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'list',
                            'title' => 'Belum ada bab',
                            'message' => 'Tulis bab pertama untuk novel ini. Kamu bisa menyimpannya sebagai draf dan menerbitkannya nanti.',
                            'actionUrl' => url('tulis/novel/' . $novel['id'] . '/bab/baru'),
                            'actionLabel' => 'Tulis bab pertama',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table" data-chapter-list>
                                <thead>
                                    <tr>
                                        <th class="cell-number">#</th>
                                        <th>Judul</th>
                                        <th>Status</th>
                                        <th>Panjang</th>
                                        <th>Diperbarui</th>
                                        <th class="table__actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chapters as $chapter): ?>
                                        <?php $isPublished = $chapter['status'] === 'published'; ?>
                                        <tr data-chapter-item data-search="<?= e(mb_strtolower($chapter['title'])) ?>">
                                            <td class="cell-number"><?= (int) $chapter['chapter_number'] ?></td>
                                            <td>
                                                <div class="table-title">
                                                    <div>
                                                        <strong><?= e($chapter['title']) ?></strong>
                                                        <span class="small muted">Dibuat <?= e(format_date((string) $chapter['created_at'])) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge--<?= $isPublished ? 'published' : 'draft' ?>"><?= $isPublished ? 'Terbit' : 'Draf' ?></span>
                                            </td>
                                            <td><span class="small"><?= number_format((int) $chapter['content_length'], 0, ',', '.') ?> karakter</span></td>
                                            <td><span class="small"><?= e(time_ago((string) $chapter['updated_at'])) ?></span></td>
                                            <td class="table__actions">
                                                <div class="action-row">
                                                    <a class="btn btn--ghost btn--xs" href="<?= e(url('tulis/bab/' . $chapter['id'] . '/edit')) ?>">Edit</a>
                                                    <?php if ($isPublished && $novel['publish_status'] === 'published'): ?>
                                                        <a class="icon-btn icon-btn--sm" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $chapter['chapter_number'])) ?>" title="Baca bab" aria-label="Baca bab <?= e($chapter['title']) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon--xs') ?></a>
                                                    <?php endif; ?>
                                                    <form method="post" action="<?= e(url('tulis/bab/' . $chapter['id'] . '/status')) ?>">
                                                        <?= csrf_field() ?>
                                                        <?php if ($isPublished): ?>
                                                            <input type="hidden" name="action" value="draft">
                                                            <button class="btn btn--ghost btn--xs" type="submit">Jadikan draf</button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="action" value="publish">
                                                            <button class="btn btn--soft btn--xs" type="submit">Terbitkan</button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <form method="post" action="<?= e(url('tulis/bab/' . $chapter['id'] . '/hapus')) ?>" data-confirm="Hapus bab “<?= e($chapter['title']) ?>”? Tindakan ini tidak dapat dibatalkan.">
                                                        <?= csrf_field() ?>
                                                        <button class="icon-btn icon-btn--sm icon-btn--danger" type="submit" title="Hapus bab" aria-label="Hapus bab <?= e($chapter['title']) ?>"><?= icon('trash', 'icon icon--xs') ?></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="table-empty small muted" data-chapter-empty hidden>Tidak ada bab yang cocok dengan pencarian.</p>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('info', 'icon icon--sm') ?>Tentang publikasi bab</h2>
                    </div>
                    <ul class="check-list">
                        <li>Nomor bab ditentukan otomatis dan tidak dapat diubah, sehingga tidak ada nomor ganda dalam satu novel.</li>
                        <li>Bab draf tidak terlihat oleh pembaca. Terbitkan bab agar muncul di halaman novel dan pembaca.</li>
                        <li>Novel hanya dapat dipublikasikan jika memiliki minimal satu bab terbit.</li>
                        <li>Link baca hanya muncul untuk bab terbit pada novel yang sudah terbit.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
