<?php

declare(strict_types=1);
?>
<section class="error-page">
    <div class="error-page__card">
        <span class="error-page__code">500</span>
        <span class="eyebrow">Luminara</span>
        <h1>Terjadi kesalahan pada server</h1>
        <p class="lead">Permintaanmu tidak dapat diproses saat ini. Silakan coba lagi beberapa saat lagi.</p>
        <p class="error-page__reference">Referensi kesalahan: <code class="code"><?= e((string) ($reference ?? '—')) ?></code></p>
        <div class="form-actions form-actions--center">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon--sm') ?>Kembali ke beranda</a>
        </div>
    </div>
</section>
