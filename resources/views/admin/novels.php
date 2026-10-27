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
                <h1>Novel</h1>
                <p class="lead">Tinjau semua novel, atur publikasi dan unggulan, serta hapus konten yang melanggar aturan.</p>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Katalog</span>
                            <h2><?= number_format((int) $result['total'], 0, ',', '.') ?> novel</h2>
                        </div>
                        <form class="filter-bar filter-bar--compact" method="get" action="<?= e(url('admin/novel')) ?>" role="search">
                            <label class="field field--inline">
                                <span class="sr-only">Cari novel</span>
                                <span class="input-icon">
                                    <?= icon('search', 'icon icon--sm') ?>
                                    <input class="input input--sm" type="search" name="q" value="<?= e($query) ?>" placeholder="Judul atau penulis…" maxlength="100">
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
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/novel')) ?>">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if ($items === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'book',
                            'title' => 'Tidak ada novel',
                            'message' => 'Tidak ada novel yang cocok dengan filter saat ini.',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Novel</th>
                                        <th>Penulis</th>
                                        <th>Status</th>
                                        <th>Bab</th>
                                        <th>Rating</th>
                                        <th>Dibaca</th>
                                        <th>Diperbarui</th>
                                        <th class="table__actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $novel): ?>
                                        <?php $isPublished = $novel['publish_status'] === 'published'; ?>
                                        <tr>
                                            <td>
                                                <div class="table-title">
                                                    <img class="table-cover" src="<?= e(cover_url($novel['cover_path'])) ?>" alt="" width="36" height="54" loading="lazy">
                                                    <div>
                                                        <strong><?= e($novel['title']) ?></strong>
                                                        <span class="small muted"><?= e($novel['genre_names'] ?: 'Tanpa genre') ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="small"><?= e($novel['author_name']) ?></span></td>
                                            <td>
                                                <span class="badge badge--<?= $isPublished ? 'published' : 'draft' ?>"><?= $isPublished ? 'Terbit' : 'Draf' ?></span>
                                                <span class="badge badge--muted"><?= e(novel_status_label((string) $novel['status'])) ?></span>
                                                <?php if ((int) $novel['is_featured'] === 1): ?>
                                                    <span class="badge badge--gold"><?= icon('star-fill', 'icon icon--xs') ?>Unggulan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="small"><?= (int) $novel['chapter_count'] ?> / <?= (int) $novel['chapter_total'] ?></span></td>
                                            <td><span class="small"><?= $novel['rating_avg'] !== null ? e(number_format((float) $novel['rating_avg'], 1, ',', '.')) : '—' ?> <span class="muted">(<?= (int) $novel['rating_count'] ?>)</span></span></td>
                                            <td><span class="small"><?= number_format((int) $novel['views_count'], 0, ',', '.') ?></span></td>
                                            <td><span class="small"><?= e(time_ago((string) $novel['updated_at'])) ?></span></td>
                                            <td class="table__actions">
                                                <div class="action-row">
                                                    <?php if ($isPublished): ?>
                                                        <a class="icon-btn icon-btn--sm" href="<?= e(url('novel/' . $novel['slug'])) ?>" title="Lihat halaman publik" aria-label="Lihat <?= e($novel['title']) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon--xs') ?></a>
                                                    <?php endif; ?>
                                                    <form method="post" action="<?= e(url('admin/novel/' . $novel['id'] . '/publikasi')) ?>">
                                                        <?= csrf_field() ?>
                                                        <?php if ($isPublished): ?>
                                                            <input type="hidden" name="action" value="unpublish">
                                                            <button class="btn btn--ghost btn--xs" type="submit">Tarik ke draf</button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="action" value="publish">
                                                            <button class="btn btn--soft btn--xs" type="submit">Terbitkan</button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <?php if ($isPublished): ?>
                                                        <form method="post" action="<?= e(url('admin/novel/' . $novel['id'] . '/unggulan')) ?>">
                                                            <?= csrf_field() ?>
                                                            <button class="icon-btn icon-btn--sm<?= (int) $novel['is_featured'] === 1 ? ' is-active' : '' ?>" type="submit" title="<?= (int) $novel['is_featured'] === 1 ? 'Hapus unggulan' : 'Jadikan unggulan' ?>" aria-label="<?= (int) $novel['is_featured'] === 1 ? 'Hapus unggulan' : 'Jadikan unggulan' ?>"><?= icon((int) $novel['is_featured'] === 1 ? 'star-fill' : 'star', 'icon icon--xs') ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="post" action="<?= e(url('admin/novel/' . $novel['id'] . '/hapus')) ?>" data-confirm="Hapus novel “<?= e($novel['title']) ?>” beserta seluruh bab dan interaksinya? Tindakan ini tidak dapat dibatalkan.">
                                                        <?= csrf_field() ?>
                                                        <button class="icon-btn icon-btn--sm icon-btn--danger" type="submit" title="Hapus novel" aria-label="Hapus novel <?= e($novel['title']) ?>"><?= icon('trash', 'icon icon--xs') ?></button>
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
                            'pageBase' => 'admin/novel',
                            'query' => ['q' => $query, 'status' => $status],
                        ]); ?>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('info', 'icon icon--sm') ?>Catatan moderasi novel</h2>
                    </div>
                    <ul class="check-list">
                        <li>Novel hanya bisa diterbitkan jika memiliki minimal satu bab berstatus terbit.</li>
                        <li>Menarik novel menjadi draf otomatis menghapus tanda unggulan.</li>
                        <li>Tanda unggulan hanya tersedia untuk novel terbit dan tampil di beranda.</li>
                        <li>Penghapusan novel bersifat permanen dan menghapus bab, komentar, rating, bookmark, serta laporan terkait.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
