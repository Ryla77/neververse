<?php

declare(strict_types=1);

use App\Core\View;
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow">Ruang penulis</span>
                <h1>Dashboard penulis</h1>
                <p class="lead">Kelola novel, tulis bab baru, dan atur kapan karyamu siap dibaca.</p>
            </div>
            <div class="dashboard-head__actions">
                <a class="btn btn--ghost" href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon--sm') ?>Lihat situs</a>
                <a class="btn btn--primary" href="<?= e(url('tulis/novel/baru')) ?>"><?= icon('plus', 'icon icon--sm') ?>Novel baru</a>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <div class="stat-grid">
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('book') ?></span>
                        <div><strong><?= (int) $summary['novels'] ?></strong><span>Total novel</span></div>
                        <p class="small muted"><?= (int) $summary['published'] ?> terbit · <?= (int) $summary['drafts'] ?> draf</p>
                    </article>
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('list') ?></span>
                        <div><strong><?= (int) $summary['chapters'] ?></strong><span>Total bab</span></div>
                        <p class="small muted"><?= (int) $summary['published_chapters'] ?> bab terbit</p>
                    </article>
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('eye') ?></span>
                        <div><strong><?= number_format((int) $summary['views'], 0, ',', '.') ?></strong><span>Total dibaca</span></div>
                        <p class="small muted">Akumulasi seluruh novel</p>
                    </article>
                    <article class="stat-card stat-card--accent">
                        <span class="stat-card__icon"><?= icon('pencil') ?></span>
                        <div><strong><?= $summary['drafts'] > 0 ? 'Lanjutkan' : 'Ide baru' ?></strong><span>Langkah berikutnya</span></div>
                        <p class="small muted"><?= $summary['drafts'] > 0 ? 'Ada draf yang menunggu diselesaikan.' : 'Mulai novel pertamamu hari ini.' ?></p>
                    </article>
                </div>

                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Karya kamu</span>
                            <h2>Daftar novel</h2>
                        </div>
                        <?php if ($novels !== []): ?>
                            <a class="link-arrow" href="<?= e(url('tulis/novel/baru')) ?>">Tambah novel <?= icon('plus', 'icon icon--xs') ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($novels === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'pencil',
                            'title' => 'Belum ada novel',
                            'message' => 'Mulai dengan membuat novel baru. Novel tersimpan sebagai draf sampai kamu mempublikasikannya.',
                            'actionUrl' => url('tulis/novel/baru'),
                            'actionLabel' => 'Buat novel pertama',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Novel</th>
                                        <th>Status</th>
                                        <th>Bab</th>
                                        <th>Dibaca</th>
                                        <th>Diperbarui</th>
                                        <th class="table__actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($novels as $novel): ?>
                                        <tr>
                                            <td>
                                                <div class="table-title">
                                                    <img class="table-cover" src="<?= e(cover_url($novel['cover_path'])) ?>" alt="" width="36" height="54" loading="lazy">
                                                    <div>
                                                        <strong><?= e($novel['title']) ?></strong>
                                                        <span class="small muted"><?= e($novel['genre_names'] ?: 'Belum ada genre') ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge--<?= $novel['publish_status'] === 'published' ? 'published' : 'draft' ?>"><?= $novel['publish_status'] === 'published' ? 'Terbit' : 'Draf' ?></span>
                                                <span class="badge badge--muted"><?= e(novel_status_label((string) $novel['status'])) ?></span>
                                            </td>
                                            <td><?= (int) $novel['chapter_total'] ?> <span class="small muted">(<?= (int) $novel['chapter_count'] ?> terbit)</span></td>
                                            <td><?= number_format((int) $novel['views_count'], 0, ',', '.') ?></td>
                                            <td><span class="small"><?= e(time_ago((string) $novel['updated_at'])) ?></span></td>
                                            <td class="table__actions">
                                                <div class="action-row">
                                                    <a class="btn btn--ghost btn--xs" href="<?= e(url('tulis/novel/' . $novel['id'] . '/edit')) ?>">Edit</a>
                                                    <a class="btn btn--soft btn--xs" href="<?= e(url('tulis/novel/' . $novel['id'] . '/bab')) ?>">Bab</a>
                                                    <?php if ($novel['publish_status'] === 'published'): ?>
                                                        <a class="icon-btn icon-btn--sm" href="<?= e(url('novel/' . $novel['slug'])) ?>" title="Lihat halaman publik" aria-label="Lihat halaman publik <?= e($novel['title']) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon--xs') ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('info', 'icon icon--sm') ?>Catatan menulis</h2>
                    </div>
                    <ul class="check-list">
                        <li>Novel selalu dibuat sebagai draf. Publikasikan setelah minimal satu bab berstatus terbit.</li>
                        <li>Bab dapat disimpan sebagai draf terlebih dahulu, lalu diterbitkan saat siap.</li>
                        <li>Pembaca hanya melihat novel dan bab yang sudah dipublikasikan.</li>
                        <li>Sampul menerima JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB dan minimal 200 × 200 piksel.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
