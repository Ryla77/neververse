<?php

declare(strict_types=1);

use App\Core\View;
?>
<section class="auth-page">
    <div class="auth-shell">
        <aside class="auth-aside">
            <span class="eyebrow">Selamat datang kembali</span>
            <h1>Lanjutkan cerita tepat di tempat kamu berhenti.</h1>
            <p>Masuk untuk menyinkronkan perpustakaan, riwayat, dan progres membaca di semua perangkatmu.</p>
            <ul class="auth-benefits">
                <li><?= icon('bookmark', 'icon icon--sm') ?> Simpan novel favorit</li>
                <li><?= icon('book-open', 'icon icon--sm') ?> Lanjutkan bab terakhir</li>
                <li><?= icon('chat', 'icon icon--sm') ?> Beri komentar dan rating</li>
            </ul>
            <div class="auth-aside__quote">
                <p>“Sebuah cerita tidak pernah benar-benar selesai selama masih ada pembaca yang kembali ke halaman pertama.”</p>
                <span>— Catatan redaksi Luminara</span>
            </div>
        </aside>

        <div class="auth-card">
            <div class="auth-card__head">
                <span class="eyebrow">Akun</span>
                <h2>Masuk</h2>
                <p class="small muted">Gunakan email dan kata sandi yang terdaftar.</p>
            </div>

            <?php View::partial('partials/form-errors'); ?>

            <form class="form-stack" action="<?= e(url('masuk')) ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e(old('redirect', $redirect)) ?>">

                <div class="field">
                    <label class="label" for="login-email">Email</label>
                    <div class="input-group">
                        <span class="input-group__icon"><?= icon('mail', 'icon icon--sm') ?></span>
                        <input class="input" type="email" id="login-email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required maxlength="190" placeholder="nama@email.com">
                    </div>
                    <?php if (form_error('email') !== null): ?><span class="field-error"><?= e(form_error('email')) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="login-password">Kata sandi</label>
                    <div class="input-group">
                        <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                        <input class="input" type="password" id="login-password" name="password" autocomplete="current-password" required maxlength="72" placeholder="Masukkan kata sandi">
                        <button class="input-group__button" type="button" data-password-toggle aria-controls="login-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                    </div>
                    <?php if (form_error('password') !== null): ?><span class="field-error"><?= e(form_error('password')) ?></span><?php endif; ?>
                </div>

                <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('login', 'icon icon--sm') ?>Masuk ke akun</button>
            </form>

            <p class="auth-switch"><a href="<?= e(url('lupa-sandi')) ?>">Lupa kata sandi?</a></p>
            <p class="auth-switch">Belum punya akun? <a href="<?= e(url('daftar')) ?>">Daftar gratis</a></p>
            <p class="auth-note"><?= icon('shield', 'icon icon--xs') ?> Kata sandi disimpan dalam bentuk hash dan sesi dilindungi token CSRF.</p>
        </div>
    </div>
</section>
