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
                <h1>Bab</h1>
                <p class="lead">Kelola status dan hapus bab dari seluruh novel. Perubahan status bab langsung memengaruhi halaman baca.</p>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Seluruh bab</span>
                            <h2><?= number_format((int) $result['total'], 0, ',', '.') ?> bab</h2>
                        </div>
                        <form class="filter-bar filter-bar--compact" method="get" action="<?= e(url('admin/bab')) ?>" role="search">
                            <label class="field field--inline">
                                <span class="sr-only">Cari bab</span>
                                <span class="input-icon">
                                    <?= icon('search', 'icon icon--sm') ?>
                                    <input class="input input--sm" type="search" name="q" value="<?= e($query) ?>" placeholder="Judul bab atau novel…" maxlength="100">
                                </span>
                            </label>
                            <label class="field field--inline">
                                <span class="sr-only">Filter status</span>
                                <select class="select select--sm" name="status">
                                    <option value="">Semua status</option>
                                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Terbit</option>
                                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draf</option>
                                </select>
                            </label>
                            <button class="btn btn--soft btn--sm" type="submit">Terapkan</button>
                            <?php if ($query !== '' || $status !== ''): ?>
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/bab')) ?>">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if ($items === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'list',
                            'title' => 'Tidak ada bab',
                            'message' => 'Tidak ada bab yang cocok dengan filter saat ini.',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="cell-number">#</th>
                                        <th>Bab</th>
                                        <th>Novel</th>
                                        <th>Penulis</th>
                                        <th>Status</th>
                                        <th>Diperbarui</th>
                                        <th class="table__actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $chapter): ?>
                                        <?php
                                        $isPublished = $chapter['status'] === 'published';
                                        $publiclyReadable = $isPublished && $chapter['novel_publish_status'] === 'published';
                                        ?>
                                        <tr>
                                            <td class="cell-number"><?= (int) $chapter['chapter_number'] ?></td>
                                            <td><strong><?= e($chapter['title']) ?></strong></td>
                                            <td>
                                                <span class="small"><?= e($chapter['novel_title']) ?></span>
                                                <span class="badge badge--<?= $chapter['novel_publish_status'] === 'published' ? 'published' : 'draft' ?>"><?= $chapter['novel_publish_status'] === 'published' ? 'Novel terbit' : 'Novel draf' ?></span>
                                            </td>
                                            <td><span class="small"><?= e($chapter['author_name']) ?></span></td>
                                            <td><span class="badge badge--<?= $isPublished ? 'published' : 'draft' ?>"><?= $isPublished ? 'Terbit' : 'Draf' ?></span></td>
                                            <td><span class="small"><?= e(time_ago((string) $chapter['updated_at'])) ?></span></td>
                                            <td class="table__actions">
                                                <div class="action-row">
                                                    <?php if ($publiclyReadable): ?>
                                                        <a class="icon-btn icon-btn--sm" href="<?= e(url('novel/' . $chapter['novel_slug'] . '/baca/' . $chapter['chapter_number'])) ?>" title="Baca bab" aria-label="Baca bab <?= e($chapter['title']) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon--xs') ?></a>
                                                    <?php endif; ?>
                                                    <form method="post" action="<?= e(url('admin/bab/' . $chapter['id'] . '/status')) ?>">
                                                        <?= csrf_field() ?>
                                                        <?php if ($isPublished): ?>
                                                            <input type="hidden" name="action" value="draft">
                                                            <button class="btn btn--ghost btn--xs" type="submit">Jadikan draf</button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="action" value="publish">
                                                            <button class="btn btn--soft btn--xs" type="submit">Terbitkan</button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <form method="post" action="<?= e(url('admin/bab/' . $chapter['id'] . '/hapus')) ?>" data-confirm="Hapus bab “<?= e($chapter['title']) ?>” dari <?= e($chapter['novel_title']) ?>? Tindakan ini tidak dapat dibatalkan.">
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

                        <?php View::partial('partials/pagination', [
                            'result' => $result,
                            'pageBase' => 'admin/bab',
                            'query' => ['q' => $query, 'status' => $status],
                        ]); ?>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('info', 'icon icon--sm') ?>Tentang bab</h2>
                    </div>
                    <ul class="check-list">
                        <li>Bab draft tidak tampil di halaman publik meskipun novelnya sudah terbit.</li>
                        <li>Menghapus bab tidak dapat dibatalkan dan nomor bab tidak digunakan ulang.</li>
                        <li>Link baca hanya muncul jika bab dan novelnya sama-sama terbit.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
