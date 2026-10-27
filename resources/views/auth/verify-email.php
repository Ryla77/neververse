<?php

declare(strict_types=1);
?>
<section class="auth-page">
    <div class="auth-shell auth-shell--single">
        <div class="auth-card">
            <?php if ($status === 'verified'): ?>
                <div class="auth-card__head">
                    <span class="eyebrow">Verifikasi selesai</span>
                    <h2>Email berhasil diverifikasi</h2>
                    <p class="small muted">Alamat <strong><?= e($email) ?></strong> kini terverifikasi. Anda dapat berkomentar, memberi rating, dan mengaktifkan akses penulis.</p>
                </div>
                <?php if ($loggedIn): ?>
                    <a class="btn btn--primary btn--block btn--lg" href="<?= e(url('profil')) ?>"><?= icon('user', 'icon icon--sm') ?>Buka profil</a>
                <?php else: ?>
                    <a class="btn btn--primary btn--block btn--lg" href="<?= e(url('masuk')) ?>"><?= icon('login', 'icon icon--sm') ?>Masuk ke akun</a>
                <?php endif; ?>
                <p class="auth-switch"><a href="<?= e(url('/')) ?>">Kembali ke beranda</a></p>
            <?php elseif ($status === 'throttled'): ?>
                <div class="auth-card__head">
                    <span class="eyebrow">Verifikasi email</span>
                    <h2>Terlalu banyak percobaan</h2>
                    <p class="small muted">Tunggu sekitar 15 menit, lalu buka kembali tautan verifikasi dari email Anda.</p>
                </div>
                <a class="btn btn--soft btn--block" href="<?= e(url('/')) ?>"><?= icon('home', 'icon icon--sm') ?>Kembali ke beranda</a>
            <?php else: ?>
                <div class="auth-card__head">
                    <span class="eyebrow">Verifikasi email</span>
                    <h2>Tautan tidak dapat digunakan</h2>
                    <p class="small muted">Tautan verifikasi tidak valid, sudah digunakan, atau kedaluwarsa.</p>
                </div>
                <p class="form-hint"><?= icon('info', 'icon icon--xs') ?> Masuk ke akun Anda, lalu gunakan tombol <strong>Kirim ulang email verifikasi</strong> pada banner di bagian atas halaman.</p>
                <a class="btn btn--primary btn--block" href="<?= e(url('masuk')) ?>"><?= icon('login', 'icon icon--sm') ?>Masuk ke akun</a>
                <p class="auth-switch"><a href="<?= e(url('/')) ?>">Kembali ke beranda</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>
