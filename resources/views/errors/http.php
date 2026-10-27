<?php

declare(strict_types=1);
?>
<section class="error-page">
    <div class="error-page__card">
        <span class="error-page__code"><?= (int) $status ?></span>
        <span class="eyebrow">NovelVerse</span>
        <h1><?= e($title) ?></h1>
        <p class="lead"><?= e($message) ?></p>
        <div class="form-actions form-actions--center">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon--sm') ?>Kembali ke beranda</a>
            <a class="btn btn--ghost" href="<?= e(url('jelajahi')) ?>"><?= icon('compass', 'icon icon--sm') ?>Jelajahi novel</a>
        </div>
    </div>
</section>
