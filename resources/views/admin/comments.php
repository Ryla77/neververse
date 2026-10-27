<?php

declare(strict_types=1);

use App\Core\View;

$items = $result['items'] ?? [];
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow">Administrasi</span>
                <h1>Komentar</h1>
                <p class="lead">Moderasi komentar pembaca. Komentar yang disembunyikan tidak tampil di halaman novel.</p>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Moderasi</span>
                            <h2><?= number_format((int) $result['total'], 0, ',', '.') ?> komentar</h2>
                        </div>
                        <form class="filter-bar filter-bar--compact" method="get" action="<?= e(url('admin/komentar')) ?>" role="search">
                            <label class="field field--inline">
                                <span class="sr-only">Cari komentar</span>
                                <span class="input-icon">
                                    <?= icon('search', 'icon icon--sm') ?>
                                    <input class="input input--sm" type="search" name="q" value="<?= e($query) ?>" placeholder="Isi, pengguna, atau novel…" maxlength="100">
                                </span>
                            </label>
                            <label class="field field--inline">
                                <span class="sr-only">Filter status</span>
                                <select class="select select--sm" name="status">
                                    <option value="">Semua status</option>
                                    <option value="visible" <?= $status === 'visible' ? 'selected' : '' ?>>Tampil</option>
                                    <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Disembunyikan</option>
                                </select>
                            </label>
                            <button class="btn btn--soft btn--sm" type="submit">Terapkan</button>
                            <?php if ($query !== '' || $status !== ''): ?>
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/komentar')) ?>">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if ($items === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'chat',
                            'title' => 'Tidak ada komentar',
                            'message' => 'Tidak ada komentar yang cocok dengan filter saat ini.',
                        ]); ?>
                    <?php else: ?>
                        <ul class="comment-moderation">
                            <?php foreach ($items as $comment): ?>
                                <?php $isVisible = $comment['status'] === 'visible'; ?>
                                <li class="comment-moderation__item<?= $isVisible ? '' : ' is-hidden' ?>">
                                    <div class="comment-moderation__head">
                                        <div class="table-title">
                                            <span class="avatar avatar--sm"><?= e(initials((string) $comment['user_name'])) ?></span>
                                            <div>
                                                <strong><?= e($comment['user_name']) ?></strong>
                                                <span class="small muted">pada <?php if ($comment['novel_publish_status'] === 'published'): ?><a href="<?= e(url('novel/' . $comment['novel_slug'])) ?>" target="_blank" rel="noopener"><?= e($comment['novel_title']) ?></a><?php else: ?><span><?= e($comment['novel_title']) ?></span><?php endif; ?> · <?= e(time_ago((string) $comment['created_at'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="comment-moderation__badges">
                                            <span class="badge badge--<?= $isVisible ? 'published' : 'danger' ?>"><?= $isVisible ? 'Tampil' : 'Disembunyikan' ?></span>
                                            <?php if ($comment['novel_publish_status'] !== 'published'): ?>
                                                <span class="badge badge--muted">Novel draf</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="comment-moderation__content"><?= nl2br(e((string) $comment['content'])) ?></p>
                                    <div class="action-row">
                                        <form method="post" action="<?= e(url('admin/komentar/' . $comment['id'] . '/status')) ?>">
                                            <?= csrf_field() ?>
                                            <?php if ($isVisible): ?>
                                                <input type="hidden" name="action" value="hide">
                                                <button class="btn btn--ghost btn--xs" type="submit"><?= icon('eye', 'icon icon--xs') ?>Sembunyikan</button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="show">
                                                <button class="btn btn--soft btn--xs" type="submit"><?= icon('eye', 'icon icon--xs') ?>Tampilkan</button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="post" action="<?= e(url('admin/komentar/' . $comment['id'] . '/hapus')) ?>" data-confirm="Hapus komentar ini secara permanen?">
                                            <?= csrf_field() ?>
                                            <button class="btn btn--danger btn--xs" type="submit"><?= icon('trash', 'icon icon--xs') ?>Hapus permanen</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php View::partial('partials/pagination', [
                            'result' => $result,
                            'pageBase' => 'admin/komentar',
                            'query' => ['q' => $query, 'status' => $status],
                        ]); ?>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('shield', 'icon icon--sm') ?>Panduan moderasi</h2>
                    </div>
                    <ul class="check-list">
                        <li>Sembunyikan komentar yang melanggar sebelum memutuskan penghapusan permanen.</li>
                        <li>Penulis komentar juga dapat menghapus komentarnya sendiri; admin dapat menghapus komentar siapa pun.</li>
                        <li>Laporan terkait komentar ditinjau dari halaman Laporan.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
