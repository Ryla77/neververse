<?php

declare(strict_types=1);

use App\Core\View;

$reportItems = $openReports['items'] ?? [];
$novelItems = $recentNovels['items'] ?? [];
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow">Administrasi</span>
                <h1>Ikhtisar platform</h1>
                <p class="lead">Pantau pengguna, konten, dan laporan moderasi Luminara dari satu tempat.</p>
            </div>
            <div class="dashboard-head__actions">
                <a class="btn btn--ghost" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon--sm') ?>Lihat situs</a>
                <a class="btn btn--primary" href="<?= e(url('admin/laporan')) ?>"><?= icon('flag', 'icon icon--sm') ?>Laporan<?php if ((int) $stats['open_reports'] > 0): ?> (<?= (int) $stats['open_reports'] ?>)<?php endif; ?></a>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <div class="stat-grid">
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('users') ?></span>
                        <div><strong><?= number_format((int) $stats['users'], 0, ',', '.') ?></strong><span>Pengguna</span></div>
                        <p class="small muted"><?= (int) $stats['admins'] ?> admin · <?= (int) $stats['authors'] ?> penulis · <?= (int) $stats['readers'] ?> pembaca</p>
                    </article>
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('book') ?></span>
                        <div><strong><?= number_format((int) $stats['novels'], 0, ',', '.') ?></strong><span>Novel</span></div>
                        <p class="small muted"><?= (int) $stats['published_novels'] ?> terbit · <?= (int) $stats['novels'] - (int) $stats['published_novels'] ?> draf</p>
                    </article>
                    <article class="stat-card">
                        <span class="stat-card__icon"><?= icon('list') ?></span>
                        <div><strong><?= number_format((int) $stats['chapters'], 0, ',', '.') ?></strong><span>Bab</span></div>
                        <p class="small muted"><?= (int) $stats['published_chapters'] ?> bab terbit</p>
                    </article>
                    <article class="stat-card stat-card--accent">
                        <span class="stat-card__icon"><?= icon('flag') ?></span>
                        <div><strong><?= (int) $stats['open_reports'] ?></strong><span>Laporan terbuka</span></div>
                        <p class="small muted"><?= (int) $stats['comments'] ?> komentar · <?= (int) $stats['ratings'] ?> rating</p>
                    </article>
                </div>

                <div class="admin-columns">
                    <section class="panel">
                        <div class="panel__head panel__head--split">
                            <div>
                                <span class="eyebrow">Moderasi</span>
                                <h2>Laporan terbaru</h2>
                            </div>
                            <a class="link-arrow" href="<?= e(url('admin/laporan')) ?>">Semua laporan <?= icon('arrow-right', 'icon icon--xs') ?></a>
                        </div>

                        <?php if ($reportItems === []): ?>
                            <?php View::partial('partials/empty-state', [
                                'icon' => 'shield',
                                'title' => 'Tidak ada laporan terbuka',
                                'message' => 'Tidak ada laporan yang menunggu tinjauan saat ini.',
                            ]); ?>
                        <?php else: ?>
                            <ul class="admin-feed">
                                <?php foreach ($reportItems as $report): ?>
                                    <li class="admin-feed__item">
                                        <span class="badge badge--danger"><?= e(report_reason_label((string) $report['reason'])) ?></span>
                                        <p class="small">
                                            Dilaporkan oleh <strong><?= e($report['reporter_name']) ?></strong>
                                            <?php if (!empty($report['novel_title'])): ?>
                                                pada novel <strong><?= e($report['novel_title']) ?></strong>
                                            <?php elseif (!empty($report['comment_content'])): ?>
                                                pada komentar <strong><?= e($report['comment_author'] ?? 'tanpa nama') ?></strong>
                                            <?php endif; ?>
                                            · <?= e(time_ago((string) $report['created_at'])) ?>
                                        </p>
                                        <a class="btn btn--soft btn--xs" href="<?= e(url('admin/laporan')) ?>">Tinjau</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>

                    <section class="panel">
                        <div class="panel__head panel__head--split">
                            <div>
                                <span class="eyebrow">Komunitas</span>
                                <h2>Pengguna baru</h2>
                            </div>
                            <a class="link-arrow" href="<?= e(url('admin/pengguna')) ?>">Kelola <?= icon('arrow-right', 'icon icon--xs') ?></a>
                        </div>
                        <ul class="admin-list">
                            <?php foreach ($recentUsers as $user): ?>
                                <li class="admin-list__item">
                                    <span class="avatar avatar--sm"><?= e(initials((string) $user['display_name'])) ?></span>
                                    <div class="admin-list__body">
                                        <strong><?= e($user['display_name']) ?></strong>
                                        <span class="small muted"><?= e($user['email']) ?></span>
                                    </div>
                                    <span class="badge badge--muted"><?= e(role_label((string) $user['role'])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>

                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Konten terbaru</span>
                            <h2>Novel yang baru dibuat</h2>
                        </div>
                        <a class="link-arrow" href="<?= e(url('admin/novel')) ?>">Semua novel <?= icon('arrow-right', 'icon icon--xs') ?></a>
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Novel</th>
                                    <th>Penulis</th>
                                    <th>Status</th>
                                    <th>Bab</th>
                                    <th>Diperbarui</th>
                                    <th class="table__actions">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($novelItems as $novel): ?>
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
                                            <span class="badge badge--<?= $novel['publish_status'] === 'published' ? 'published' : 'draft' ?>"><?= $novel['publish_status'] === 'published' ? 'Terbit' : 'Draf' ?></span>
                                        </td>
                                        <td><span class="small"><?= (int) $novel['chapter_count'] ?> / <?= (int) $novel['chapter_total'] ?></span></td>
                                        <td><span class="small"><?= e(time_ago((string) $novel['updated_at'])) ?></span></td>
                                        <td class="table__actions">
                                            <a class="btn btn--ghost btn--xs" href="<?= e(url('admin/novel')) ?>">Kelola</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="admin-columns">
                    <section class="panel">
                        <div class="panel__head panel__head--split">
                            <div>
                                <span class="eyebrow">Interaksi</span>
                                <h2>Komentar terbaru</h2>
                            </div>
                            <a class="link-arrow" href="<?= e(url('admin/komentar')) ?>">Moderasi <?= icon('arrow-right', 'icon icon--xs') ?></a>
                        </div>
                        <?php if ($recentComments === []): ?>
                            <p class="small muted">Belum ada komentar.</p>
                        <?php else: ?>
                            <ul class="admin-feed">
                                <?php foreach ($recentComments as $comment): ?>
                                    <li class="admin-feed__item admin-feed__item--column">
                                        <div class="admin-feed__meta">
                                            <strong class="small"><?= e($comment['user_name']) ?></strong>
                                            <span class="badge badge--<?= $comment['status'] === 'visible' ? 'published' : 'danger' ?>"><?= $comment['status'] === 'visible' ? 'Tampil' : 'Disembunyikan' ?></span>
                                            <span class="small muted"><?= e(time_ago((string) $comment['created_at'])) ?></span>
                                        </div>
                                        <p class="small"><?= e(excerpt_text((string) $comment['content'], 160)) ?></p>
                                        <span class="small muted">pada <?= e($comment['novel_title']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>

                    <section class="panel panel--tips">
                        <div class="panel__head">
                            <h2><?= icon('shield', 'icon icon--sm') ?>Pengingat admin</h2>
                        </div>
                        <ul class="check-list">
                            <li>Setiap tindakan admin diperiksa ulang di backend, bukan hanya disembunyikan di antarmuka.</li>
                            <li>Jangan menurunkan atau menonaktifkan admin terakhir yang aktif.</li>
                            <li>Tinjau laporan terbuka secara berkala dan tandai selesai setelah ditangani.</li>
                            <li>Kata sandi sementara dari reset harus segera diganti oleh pengguna.</li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
