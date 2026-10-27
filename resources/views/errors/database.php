<?php

declare(strict_types=1);
?>
<section class="error-page">
    <div class="error-page__card">
        <span class="error-page__code">503</span>
        <span class="eyebrow">NovelVerse</span>
        <h1>Koneksi database bermasalah</h1>
        <p class="lead">Aplikasi tidak dapat terhubung ke database. Pastikan MySQL/MariaDB berjalan dan konfigurasi <code class="code">.env</code> sudah benar.</p>
        <div class="error-page__steps">
            <p class="small muted">Langkah yang dapat dicoba:</p>
            <ul class="check-list">
                <li>Jalankan MySQL dari panel XAMPP.</li>
                <li>Periksa <code class="code">DB_HOST</code>, <code class="code">DB_PORT</code>, <code class="code">DB_NAME</code>, <code class="code">DB_USER</code>, dan <code class="code">DB_PASS</code>.</li>
                <li>Pastikan database dan pengguna sudah dibuat sesuai README.</li>
            </ul>
        </div>
        <div class="form-actions form-actions--center">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon--sm') ?>Coba lagi</a>
        </div>
    </div>
</section>
