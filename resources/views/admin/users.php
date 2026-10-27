<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\View;

$currentId = (int) Auth::id();
$items = $result['items'] ?? [];
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow">Administrasi</span>
                <h1>Pengguna</h1>
                <p class="lead">Kelola peran dan status akun. Perubahan diperiksa kembali di server, termasuk perlindungan admin terakhir.</p>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Direktori akun</span>
                            <h2><?= number_format((int) $result['total'], 0, ',', '.') ?> pengguna</h2>
                        </div>
                        <form class="filter-bar filter-bar--compact" method="get" action="<?= e(url('admin/pengguna')) ?>" role="search">
                            <label class="field field--inline">
                                <span class="sr-only">Cari pengguna</span>
                                <span class="input-icon">
                                    <?= icon('search', 'icon icon--sm') ?>
                                    <input class="input input--sm" type="search" name="q" value="<?= e($query) ?>" placeholder="Nama atau email…" maxlength="100">
                                </span>
                            </label>
                            <button class="btn btn--soft btn--sm" type="submit">Cari</button>
                            <?php if ($query !== ''): ?>
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/pengguna')) ?>">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if ($items === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'users',
                            'title' => 'Tidak ada pengguna',
                            'message' => $query !== '' ? 'Tidak ada pengguna yang cocok dengan pencarian.' : 'Belum ada pengguna terdaftar.',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table table--users">
                                <thead>
                                    <tr>
                                        <th>Pengguna</th>
                                        <th>Peran &amp; status</th>
                                        <th>Terakhir masuk</th>
                                        <th>Bergabung</th>
                                        <th class="table__actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $user): ?>
                                        <?php $isSelf = (int) $user['id'] === $currentId; ?>
                                        <tr>
                                            <td>
                                                <div class="table-title">
                                                    <span class="avatar avatar--sm"><?= e(initials((string) $user['display_name'])) ?></span>
                                                    <div>
                                                        <strong><?= e($user['display_name']) ?><?= $isSelf ? ' <span class="badge badge--gold">Kamu</span>' : '' ?></strong>
                                                        <span class="small muted"><?= e($user['email']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge badge--muted"><?= e(role_label((string) $user['role'])) ?></span>
                                                    <span class="badge badge--published">Aktif</span>
                                                    <p class="small muted">Peran dan status akunmu tidak dapat diubah dari sini.</p>
                                                <?php else: ?>
                                                    <form class="inline-form" method="post" action="<?= e(url('admin/pengguna/' . $user['id'])) ?>">
                                                        <?= csrf_field() ?>
                                                        <label class="field field--inline">
                                                            <span class="sr-only">Peran <?= e($user['display_name']) ?></span>
                                                            <select class="select select--sm" name="role">
                                                                <?php foreach (['reader' => 'Pembaca', 'author' => 'Penulis', 'admin' => 'Admin'] as $value => $label): ?>
                                                                    <option value="<?= e($value) ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                        <label class="checkbox checkbox--sm">
                                                            <input type="checkbox" name="is_active" value="1" <?= (int) $user['is_active'] === 1 ? 'checked' : '' ?>>
                                                            <span>Aktif</span>
                                                        </label>
                                                        <button class="btn btn--soft btn--xs" type="submit">Simpan</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="small"><?= $user['last_login_at'] ? e(time_ago((string) $user['last_login_at'])) : 'Belum pernah' ?></span></td>
                                            <td><span class="small"><?= e(format_date((string) $user['created_at'])) ?></span></td>
                                            <td class="table__actions">
                                                <div class="action-row">
                                                    <form method="post" action="<?= e(url('admin/pengguna/' . $user['id'] . '/reset-sandi')) ?>" data-confirm="Reset kata sandi <?= e($user['display_name']) ?>? Kata sandi sementara akan ditampilkan sekali.">
                                                        <?= csrf_field() ?>
                                                        <button class="btn btn--ghost btn--xs" type="submit"><?= icon('lock', 'icon icon--xs') ?>Reset sandi</button>
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
                            'pageBase' => 'admin/pengguna',
                            'query' => ['q' => $query],
                        ]); ?>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('shield', 'icon icon--sm') ?>Aturan pengelolaan akun</h2>
                    </div>
                    <ul class="check-list">
                        <li>Admin tidak dapat mengubah peran atau menonaktifkan akunnya sendiri dari halaman ini.</li>
                        <li>Sistem menolak perubahan yang menghilangkan admin aktif terakhir.</li>
                        <li>Kata sandi sementara hanya ditampilkan sekali setelah reset; pengguna harus segera menggantinya.</li>
                        <li>Menonaktifkan akun membuat pengguna tidak dapat masuk, tetapi datanya tetap tersimpan.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
