<?php

declare(strict_types=1);

use App\Core\Auth;

$navUser = Auth::user();
$isAdmin = $navUser !== null && $navUser['role'] === 'admin';
$isAuthor = $navUser !== null && in_array($navUser['role'], ['author', 'admin'], true);
$openReports = $isAdmin ? \App\Models\ReportModel::countOpen() : 0;
?>
<aside class="dashboard-nav">
    <div class="dashboard-nav__user">
        <span class="avatar"><?= e(initials((string) ($navUser['display_name'] ?? 'NV'))) ?></span>
        <div>
            <strong><?= e($navUser['display_name'] ?? 'Pengguna') ?></strong>
            <span class="badge badge--gold"><?= e(role_label((string) ($navUser['role'] ?? 'reader'))) ?></span>
        </div>
    </div>

    <?php if ($isAuthor): ?>
        <div class="dashboard-nav__group">
            <p class="dashboard-nav__label">Ruang penulis</p>
            <a class="dashboard-nav__link<?= nav_active('/tulis') && request_path() === '/tulis' ? ' is-active' : '' ?>" href="<?= e(url('tulis')) ?>">
                <?= icon('grid', 'icon icon--sm') ?>Ringkasan
            </a>
            <a class="dashboard-nav__link<?= request_path() === '/tulis/novel/baru' ? ' is-active' : '' ?>" href="<?= e(url('tulis/novel/baru')) ?>">
                <?= icon('plus', 'icon icon--sm') ?>Novel baru
            </a>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <div class="dashboard-nav__group">
            <p class="dashboard-nav__label">Administrasi</p>
            <a class="dashboard-nav__link<?= nav_active('/admin') && request_path() === '/admin' ? ' is-active' : '' ?>" href="<?= e(url('admin')) ?>">
                <?= icon('shield', 'icon icon--sm') ?>Ikhtisar
            </a>
            <a class="dashboard-nav__link<?= nav_active('/admin/pengguna') ? ' is-active' : '' ?>" href="<?= e(url('admin/pengguna')) ?>">
                <?= icon('users', 'icon icon--sm') ?>Pengguna
            </a>
            <a class="dashboard-nav__link<?= nav_active('/admin/novel') ? ' is-active' : '' ?>" href="<?= e(url('admin/novel')) ?>">
                <?= icon('book', 'icon icon--sm') ?>Novel
            </a>
            <a class="dashboard-nav__link<?= nav_active('/admin/bab') ? ' is-active' : '' ?>" href="<?= e(url('admin/bab')) ?>">
                <?= icon('list', 'icon icon--sm') ?>Bab
            </a>
            <a class="dashboard-nav__link<?= nav_active('/admin/genre') ? ' is-active' : '' ?>" href="<?= e(url('admin/genre')) ?>">
                <?= icon('bookmark', 'icon icon--sm') ?>Genre
            </a>
            <a class="dashboard-nav__link<?= nav_active('/admin/komentar') ? ' is-active' : '' ?>" href="<?= e(url('admin/komentar')) ?>">
                <?= icon('chat', 'icon icon--sm') ?>Komentar
            </a>
            <a class="dashboard-nav__link<?= nav_active('/admin/laporan') ? ' is-active' : '' ?>" href="<?= e(url('admin/laporan')) ?>">
                <?= icon('flag', 'icon icon--sm') ?>Laporan
                <?php if ($openReports > 0): ?><span class="nav-count"><?= $openReports ?></span><?php endif; ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="dashboard-nav__group">
        <p class="dashboard-nav__label">Situs</p>
        <a class="dashboard-nav__link" href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon--sm') ?>Beranda</a>
        <a class="dashboard-nav__link" href="<?= e(url('perpustakaan')) ?>"><?= icon('folder', 'icon icon--sm') ?>Perpustakaan</a>
        <a class="dashboard-nav__link" href="<?= e(url('profil')) ?>"><?= icon('user', 'icon icon--sm') ?>Profil</a>
    </div>
</aside>
