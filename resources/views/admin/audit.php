<?php

declare(strict_types=1);

use App\Core\View;
use App\Models\AuditLogModel;

$items = $result['items'] ?? [];
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow">Administrasi</span>
                <h1>Log audit</h1>
                <p class="lead">Riwayat tindakan admin yang mengubah pengguna, konten, dan moderasi. Entri hanya dapat dibaca, bukan diubah dari antarmuka.</p>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Jejak tindakan</span>
                            <h2><?= number_format((int) $result['total'], 0, ',', '.') ?> entri</h2>
                        </div>
                        <form class="filter-bar filter-bar--compact" method="get" action="<?= e(url('admin/audit')) ?>" role="search">
                            <label class="field field--inline">
                                <span class="sr-only">Cari admin atau ringkasan</span>
                                <span class="input-icon">
                                    <?= icon('search', 'icon icon--sm') ?>
                                    <input class="input input--sm" type="search" name="q" value="<?= e($query) ?>" placeholder="Admin atau ringkasan…" maxlength="100">
                                </span>
                            </label>
                            <label class="field field--inline">
                                <span class="sr-only">Filter aksi</span>
                                <select class="select select--sm" name="action">
                                    <option value="">Semua aksi</option>
                                    <?php foreach ($actions as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $action === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button class="btn btn--soft btn--sm" type="submit">Terapkan</button>
                            <?php if ($query !== '' || $action !== ''): ?>
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/audit')) ?>">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if ($items === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'shield',
                            'title' => 'Belum ada entri',
                            'message' => $query !== '' || $action !== ''
                                ? 'Tidak ada entri yang cocok dengan filter.'
                                : 'Belum ada tindakan admin yang tercatat.',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table table--audit">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Admin</th>
                                        <th>Aksi</th>
                                        <th>Target</th>
                                        <th>Ringkasan</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $entry): ?>
                                        <tr>
                                            <td><span class="small"><?= e(format_date((string) $entry['created_at'], true)) ?></span></td>
                                            <td>
                                                <strong><?= e($entry['actor_name']) ?></strong>
                                                <?php if ($entry['actor_id'] === null): ?>
                                                    <span class="badge badge--muted">Akun dihapus</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge--<?= e(AuditLogModel::tone((string) $entry['action'])) ?>">
                                                    <?= e(AuditLogModel::label((string) $entry['action'])) ?>
                                                </span>
                                            </td>
                                            <td><span class="small muted"><?= e(AuditLogModel::targetLabel($entry['target_type'] !== null ? (string) $entry['target_type'] : null, $entry['target_id'] !== null ? (int) $entry['target_id'] : null)) ?></span></td>
                                            <td><span class="small"><?= e($entry['summary']) ?></span></td>
                                            <td><span class="small muted"><?= e((string) ($entry['ip_address'] ?? '—')) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php View::partial('partials/pagination', [
                            'result' => $result,
                            'pageBase' => 'admin/audit',
                            'query' => ['q' => $query, 'action' => $action],
                        ]); ?>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('shield', 'icon icon--sm') ?>Tentang log audit</h2>
                    </div>
                    <ul class="check-list">
                        <li>Mencatat aksi admin pada pengguna, novel, bab, genre, komentar, dan laporan.</li>
                        <li>Kata sandi, kunci 2FA, dan isi komentar tidak disimpan di log.</li>
                        <li>Entri tidak dapat diubah atau dihapus dari antarmuka; retensi diatur langsung di database.</li>
                        <li>Log ini bukan bukti anti-manipulasi: pengelola database tetap dapat mengubahnya.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
