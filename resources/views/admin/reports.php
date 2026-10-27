<?php

declare(strict_types=1);

use App\Core\View;

$items = $result['items'] ?? [];
$tabs = [
    'open' => ['label' => 'Terbuka', 'count' => (int) $counts['open']],
    'resolved' => ['label' => 'Selesai', 'count' => (int) $counts['resolved']],
    'dismissed' => ['label' => 'Ditolak', 'count' => (int) $counts['dismissed']],
    'all' => ['label' => 'Semua', 'count' => (int) $counts['open'] + (int) $counts['resolved'] + (int) $counts['dismissed']],
];
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow">Administrasi</span>
                <h1>Laporan</h1>
                <p class="lead">Tinjau laporan konten dari pembaca, lalu tandai selesai atau tolak setelah diperiksa.</p>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <nav class="tab-bar" aria-label="Filter status laporan">
                    <?php foreach ($tabs as $key => $tab): ?>
                        <a class="tab-bar__link<?= $status === $key ? ' is-active' : '' ?>" href="<?= e(url('admin/laporan?status=' . $key)) ?>" <?= $status === $key ? 'aria-current="page"' : '' ?>>
                            <?= e($tab['label']) ?>
                            <span class="tab-bar__count"><?= $tab['count'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Antrean moderasi</span>
                            <h2><?= number_format((int) $result['total'], 0, ',', '.') ?> laporan</h2>
                        </div>
                    </div>

                    <?php if ($items === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'shield',
                            'title' => 'Tidak ada laporan',
                            'message' => 'Tidak ada laporan dengan status ini.',
                        ]); ?>
                    <?php else: ?>
                        <ul class="report-list">
                            <?php foreach ($items as $report): ?>
                                <?php
                                $isOpen = $report['status'] === 'open';
                                $statusLabel = ['open' => 'Terbuka', 'resolved' => 'Selesai', 'dismissed' => 'Ditolak'][$report['status']] ?? $report['status'];
                                $statusClass = ['open' => 'danger', 'resolved' => 'published', 'dismissed' => 'muted'][$report['status']] ?? 'muted';
                                $novelPublished = ($report['novel_publish_status'] ?? '') === 'published';
                                $commentNovelPublished = ($report['comment_novel_publish_status'] ?? '') === 'published';
                                ?>
                                <li class="report-card">
                                    <div class="report-card__head">
                                        <div>
                                            <span class="badge badge--<?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                                            <span class="badge badge--muted"><?= e(report_reason_label((string) $report['reason'])) ?></span>
                                        </div>
                                        <span class="small muted"><?= e(format_date((string) $report['created_at'], true)) ?></span>
                                    </div>

                                    <p class="small">
                                        Dilaporkan oleh <strong><?= e($report['reporter_name']) ?></strong>
                                        · <?= e(time_ago((string) $report['created_at'])) ?>
                                    </p>

                                    <?php if (!empty($report['novel_title'])): ?>
                                        <div class="report-card__target">
                                            <span class="small muted">Novel yang dilaporkan</span>
                                            <?php if ($novelPublished): ?>
                                                <a class="report-card__target-title" href="<?= e(url('novel/' . $report['novel_slug'])) ?>" target="_blank" rel="noopener"><?= e($report['novel_title']) ?> <?= icon('external', 'icon icon--xs') ?></a>
                                            <?php else: ?>
                                                <span class="report-card__target-title"><?= e($report['novel_title']) ?></span>
                                                <span class="badge badge--muted">Novel draf</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif (!empty($report['comment_id'])): ?>
                                        <div class="report-card__target">
                                            <span class="small muted">Komentar yang dilaporkan · oleh <?= e($report['comment_author'] ?? 'tanpa nama') ?> pada <?= e($report['comment_novel_title'] ?? 'novel') ?></span>
                                            <blockquote class="report-card__quote"><?= nl2br(e((string) ($report['comment_content'] ?? 'Komentar telah dihapus.'))) ?></blockquote>
                                            <span class="badge badge--<?= ($report['comment_status'] ?? '') === 'visible' ? 'published' : 'danger' ?>"><?= ($report['comment_status'] ?? '') === 'visible' ? 'Komentar tampil' : 'Komentar disembunyikan/dihapus' ?></span>
                                            <?php if (!empty($report['comment_novel_slug']) && $commentNovelPublished): ?>
                                                <a class="link-arrow small" href="<?= e(url('novel/' . $report['comment_novel_slug'] . '#komentar')) ?>" target="_blank" rel="noopener">Buka halaman novel <?= icon('arrow-right', 'icon icon--xs') ?></a>
                                            <?php elseif (!empty($report['comment_novel_slug'])): ?>
                                                <span class="badge badge--muted">Novel draf</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($report['details'])): ?>
                                        <div class="report-card__details">
                                            <span class="small muted">Keterangan pelapor</span>
                                            <p class="small"><?= nl2br(e((string) $report['details'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($isOpen): ?>
                                        <div class="action-row">
                                            <form method="post" action="<?= e(url('admin/laporan/' . $report['id'] . '/selesai')) ?>">
                                                <?= csrf_field() ?>
                                                <button class="btn btn--soft btn--sm" type="submit"><?= icon('check', 'icon icon--sm') ?>Tandai selesai</button>
                                            </form>
                                            <form method="post" action="<?= e(url('admin/laporan/' . $report['id'] . '/tolak')) ?>" data-confirm="Tolak laporan ini tanpa tindakan?">
                                                <?= csrf_field() ?>
                                                <button class="btn btn--ghost btn--sm" type="submit"><?= icon('x', 'icon icon--sm') ?>Tolak</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <p class="small muted">Ditindaklanjuti <?= $report['resolved_at'] ? e(time_ago((string) $report['resolved_at'])) : '—' ?>.</p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php View::partial('partials/pagination', [
                            'result' => $result,
                            'pageBase' => 'admin/laporan',
                            'query' => ['status' => $status],
                        ]); ?>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('shield', 'icon icon--sm') ?>Alur penanganan</h2>
                    </div>
                    <ul class="check-list">
                        <li>Buka target laporan untuk memeriksa konteks sebelum mengambil tindakan.</li>
                        <li>Sembunyikan atau hapus komentar melalui halaman Komentar, lalu tandai laporan selesai.</li>
                        <li>Tolak laporan jika setelah ditinjau tidak ditemukan pelanggaran.</li>
                        <li>Status laporan hanya dapat diubah dari terbuka; laporan yang sudah ditangani tidak diproses ulang.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
