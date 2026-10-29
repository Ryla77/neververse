<?php

declare(strict_types=1);

$pageTitle = $title ?? 'Luminara';
$bodyClass = trim((string) ($bodyClass ?? ''));
?>
<!doctype html>
<html lang="id" data-theme="<?= e(theme()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="theme-color" content="#0a0d1a">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/images/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="minimal-body <?= e($bodyClass) ?>">
<a class="skip-link" href="#konten">Lewati ke konten utama</a>
<header class="minimal-header">
    <div class="container minimal-header__inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="Luminara, kembali ke beranda">
            <svg class="brand__mark" viewBox="0 0 36 36" aria-hidden="true" fill="none">
                <circle cx="18" cy="3.8" r="1.6" fill="currentColor"/>
                <path d="M18 5.4v3.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M11.5 9.2h13l2.3 6.2v13.4H9.2V15.4l2.3-6.2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M14 16.2h8v9h-8z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" opacity=".7"/>
                <circle cx="18" cy="20.6" r="2.6" fill="currentColor"/>
                <circle cx="18" cy="20.6" r="5.2" stroke="currentColor" stroke-width="1" opacity=".35"/>
                <path d="M12.4 31h11.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity=".65"/>
            </svg>
            <span class="brand__text">Lumi<em>nara</em><small>Ruang baca digital</small></span>
        </a>
        <div class="minimal-header__actions">
            <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Ganti mode gelap atau terang">
                <span class="theme-toggle__sun"><?= icon('sun') ?></span>
                <span class="theme-toggle__moon"><?= icon('moon') ?></span>
            </button>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('/')) ?>"><?= icon('arrow-left', 'icon icon--sm') ?>Beranda</a>
        </div>
    </div>
</header>
<main id="konten" class="minimal-main">
    <?php \App\Core\View::partial('partials/flash'); ?>
    <?= $content ?>
</main>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
