<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Models\CategoryModel;

$user = current_user();
$currentTheme = theme();
$navCategories = CategoryModel::all();
$pageTitle = $title ?? 'NovelVerse';
$description = $metaDescription ?? 'NovelVerse — baca novel original berbahasa Indonesia secara gratis.';
$bodyClass = trim((string) ($bodyClass ?? ''));
$hideFooter = (bool) ($hideFooter ?? false);
?>
<!doctype html>
<html lang="id" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="#0a0d1a">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/images/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#konten">Lewati ke konten utama</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="NovelVerse, kembali ke beranda">
            <svg class="brand__mark" viewBox="0 0 36 36" aria-hidden="true" fill="none">
                <circle cx="18" cy="3.8" r="1.6" fill="currentColor"/>
                <path d="M18 5.4v3.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M11.5 9.2h13l2.3 6.2v13.4H9.2V15.4l2.3-6.2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M14 16.2h8v9h-8z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" opacity=".7"/>
                <circle cx="18" cy="20.6" r="2.6" fill="currentColor"/>
                <circle cx="18" cy="20.6" r="5.2" stroke="currentColor" stroke-width="1" opacity=".35"/>
                <path d="M12.4 31h11.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity=".65"/>
            </svg>
            <span class="brand__text">Novel<em>Verse</em><small>Ruang baca digital</small></span>
        </a>

        <nav class="nav-links" aria-label="Navigasi utama">
            <a class="nav-link<?= nav_active('/') ? ' is-active' : '' ?>" href="<?= e(url('/')) ?>">Beranda</a>
            <a class="nav-link<?= nav_active('/jelajahi') ? ' is-active' : '' ?>" href="<?= e(url('jelajahi')) ?>">Jelajahi</a>
            <?php if ($user !== null): ?>
                <a class="nav-link<?= nav_active('/perpustakaan') ? ' is-active' : '' ?>" href="<?= e(url('perpustakaan')) ?>">Perpustakaan</a>
            <?php endif; ?>
            <details class="menu nav-menu">
                <summary class="nav-link nav-link--menu">Genre <?= icon('chevron-down', 'icon icon--xs') ?></summary>
                <div class="menu__panel menu__panel--wide">
                    <p class="menu__eyebrow">Telusuri genre</p>
                    <div class="menu__grid">
                        <?php foreach ($navCategories as $category): ?>
                            <a class="menu__item" href="<?= e(url('jelajahi?genre=' . rawurlencode($category['slug']))) ?>">
                                <?= icon('bookmark', 'icon icon--sm') ?>
                                <span><?= e($category['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        </nav>

        <div class="nav-actions">
            <form class="nav-search" action="<?= e(url('jelajahi')) ?>" method="get" role="search">
                <?= icon('search', 'icon icon--sm') ?>
                <input type="search" name="q" placeholder="Cari novel…" aria-label="Cari novel berdasarkan judul atau penulis" maxlength="100">
            </form>
            <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Ganti mode gelap atau terang" title="Ganti tema">
                <span class="theme-toggle__sun"><?= icon('sun') ?></span>
                <span class="theme-toggle__moon"><?= icon('moon') ?></span>
            </button>

            <?php if ($user !== null): ?>
                <details class="menu user-menu">
                    <summary class="user-menu__summary" aria-label="Buka menu akun">
                        <span class="avatar avatar--sm"><?= e(initials((string) $user['display_name'])) ?></span>
                        <span class="user-menu__name"><?= e($user['display_name']) ?></span>
                        <?= icon('chevron-down', 'icon icon--xs') ?>
                    </summary>
                    <div class="menu__panel user-menu__panel">
                        <div class="user-menu__head">
                            <span class="avatar"><?= e(initials((string) $user['display_name'])) ?></span>
                            <div>
                                <strong><?= e($user['display_name']) ?></strong>
                                <span class="small muted"><?= e($user['email']) ?></span>
                                <span class="badge badge--gold"><?= e(role_label((string) $user['role'])) ?></span>
                            </div>
                        </div>
                        <div class="menu__list">
                            <a class="menu__link" href="<?= e(url('perpustakaan')) ?>"><?= icon('folder', 'icon icon--sm') ?>Perpustakaan</a>
                            <a class="menu__link" href="<?= e(url('profil')) ?>"><?= icon('user', 'icon icon--sm') ?>Profil saya</a>
                            <?php if (in_array($user['role'], ['author', 'admin'], true)): ?>
                                <a class="menu__link" href="<?= e(url('tulis')) ?>"><?= icon('pencil', 'icon icon--sm') ?>Dashboard penulis</a>
                            <?php endif; ?>
                            <?php if ($user['role'] === 'admin'): ?>
                                <a class="menu__link" href="<?= e(url('admin')) ?>"><?= icon('shield', 'icon icon--sm') ?>Dashboard admin</a>
                            <?php endif; ?>
                        </div>
                        <form class="menu__logout" action="<?= e(url('keluar')) ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="btn btn--ghost btn--block" type="submit"><?= icon('logout', 'icon icon--sm') ?>Keluar</button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <a class="btn btn--ghost nav-auth" href="<?= e(url('masuk')) ?>">Masuk</a>
                <a class="btn btn--primary nav-auth" href="<?= e(url('daftar')) ?>">Daftar</a>
            <?php endif; ?>

            <button class="icon-btn nav-toggle" type="button" data-nav-toggle aria-controls="mobile-menu" aria-expanded="false" aria-label="Buka menu navigasi">
                <?= icon('menu') ?>
            </button>
        </div>
    </div>

    <div class="mobile-menu" id="mobile-menu" hidden>
        <div class="container">
            <form class="mobile-search" action="<?= e(url('jelajahi')) ?>" method="get" role="search">
                <?= icon('search', 'icon icon--sm') ?>
                <input type="search" name="q" placeholder="Cari judul atau penulis…" aria-label="Cari novel" maxlength="100">
                <button class="btn btn--primary btn--sm" type="submit">Cari</button>
            </form>
            <nav class="mobile-nav" aria-label="Navigasi seluler">
                <a href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon--sm') ?>Beranda</a>
                <a href="<?= e(url('jelajahi')) ?>"><?= icon('compass', 'icon icon--sm') ?>Jelajahi novel</a>
                <?php if ($user !== null): ?>
                    <a href="<?= e(url('perpustakaan')) ?>"><?= icon('folder', 'icon icon--sm') ?>Perpustakaan</a>
                    <a href="<?= e(url('profil')) ?>"><?= icon('user', 'icon icon--sm') ?>Profil saya</a>
                    <?php if (in_array($user['role'], ['author', 'admin'], true)): ?>
                        <a href="<?= e(url('tulis')) ?>"><?= icon('pencil', 'icon icon--sm') ?>Dashboard penulis</a>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="<?= e(url('admin')) ?>"><?= icon('shield', 'icon icon--sm') ?>Dashboard admin</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= e(url('masuk')) ?>"><?= icon('login', 'icon icon--sm') ?>Masuk</a>
                    <a href="<?= e(url('daftar')) ?>"><?= icon('user', 'icon icon--sm') ?>Daftar gratis</a>
                <?php endif; ?>
            </nav>
            <div class="mobile-genres">
                <p class="menu__eyebrow">Genre</p>
                <div class="chip-row">
                    <?php foreach ($navCategories as $category): ?>
                        <a class="chip chip--sm" href="<?= e(url('jelajahi?genre=' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($user !== null): ?>
                <form class="mobile-logout" action="<?= e(url('keluar')) ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="btn btn--ghost btn--block" type="submit"><?= icon('logout', 'icon icon--sm') ?>Keluar dari akun</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="konten" class="site-main">
    <?php \App\Core\View::partial('partials/flash'); ?>
    <?php if ($user !== null && !email_verified($user)): ?>
        <div class="container">
            <div class="alert alert--info verify-banner" role="status">
                <span class="alert__icon"><?= icon('mail') ?></span>
                <div class="alert__body">
                    <span class="alert__label">Verifikasi email</span>
                    <p>Kami mengirim tautan verifikasi ke <strong><?= e($user['email']) ?></strong>. Verifikasi email untuk mengaktifkan komentar, rating, dan akses penulis.</p>
                    <form class="verify-banner__form" action="<?= e(url('verifikasi-email/kirim-ulang')) ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= e(request_path()) ?>">
                        <button class="btn btn--soft btn--xs" type="submit">Kirim ulang email verifikasi</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<?php if (!$hideFooter): ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand--footer" href="<?= e(url('/')) ?>">
                <span class="brand__text">Novel<em>Verse</em></span>
            </a>
            <p>Ruang baca digital untuk cerita original berbahasa Indonesia. Temukan dunia baru, satu bab setiap waktu.</p>
            <div class="footer-badges">
                <span class="badge badge--muted">PHP 8.2</span>
                <span class="badge badge--muted">MySQL</span>
                <span class="badge badge--muted">Responsif</span>
            </div>
        </div>
        <div>
            <h2 class="footer-title">Navigasi</h2>
            <ul class="footer-links">
                <li><a href="<?= e(url('/')) ?>">Beranda</a></li>
                <li><a href="<?= e(url('jelajahi')) ?>">Jelajahi novel</a></li>
                <?php if ($user !== null): ?>
                    <li><a href="<?= e(url('perpustakaan')) ?>">Perpustakaan saya</a></li>
                    <li><a href="<?= e(url('profil')) ?>">Profil</a></li>
                <?php else: ?>
                    <li><a href="<?= e(url('masuk')) ?>">Masuk</a></li>
                    <li><a href="<?= e(url('daftar')) ?>">Daftar gratis</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div>
            <h2 class="footer-title">Genre pilihan</h2>
            <ul class="footer-links">
                <?php foreach (array_slice($navCategories, 0, 6) as $category): ?>
                    <li><a href="<?= e(url('jelajahi?genre=' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h2 class="footer-title">Tentang</h2>
            <p class="footer-note">Selamat datang di dunia tempat imajinasi menjadi nyata dan setiap kata menyimpan cerita.</p>
            <p class="footer-note">Kami hadir sebagai ruang bagi para pecinta cerita untuk menjelajahi berbagai kisah penuh cinta, petualangan, misteri, dan takdir. Melalui setiap halaman, kami ingin menghadirkan pengalaman membaca yang bukan sekadar hiburan, tetapi juga perjalanan emosional yang meninggalkan kesan mendalam.</p>
            <p class="footer-note">Kami percaya bahwa setiap cerita memiliki keistimewaannya sendiri, setiap karakter memiliki perjalanan yang berarti, dan setiap pembaca berhak menemukan kisah yang menyentuh hatinya.</p>
            <p class="footer-note"><strong>Karena bagi kami, cerita yang indah bukan hanya untuk dibaca, tetapi juga untuk dirasakan.</strong></p>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> NovelVerse. Dibuat untuk pembelajaran.</span>
        <span>Baca dengan tenang. Tulis dengan jujur.</span>
    </div>
</footer>
<?php endif; ?>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
