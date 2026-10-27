<?php

declare(strict_types=1);

use App\Core\View;
?>
<section class="auth-page">
    <div class="auth-shell">
        <aside class="auth-aside auth-aside--register">
            <span class="eyebrow">Mulai perjalananmu</span>
            <h1>Rak buku pribadi, tanpa batas jumlah cerita.</h1>
            <p>Buat akun gratis untuk menyimpan novel, melanjutkan bacaan, dan berdiskusi dengan pembaca lain.</p>
            <ul class="auth-benefits">
                <li><?= icon('folder', 'icon icon--sm') ?> Perpustakaan pribadi</li>
                <li><?= icon('clock', 'icon icon--sm') ?> Progres otomatis</li>
                <li><?= icon('pencil', 'icon icon--sm') ?> Akses menjadi penulis</li>
            </ul>
            <div class="auth-aside__quote">
                <p>“Setiap pembaca adalah penulis yang sedang mencari kalimat pertamanya.”</p>
                <span>— NovelVerse</span>
            </div>
        </aside>

        <div class="auth-card">
            <div class="auth-card__head">
                <span class="eyebrow">Akun baru</span>
                <h2>Daftar</h2>
                <p class="small muted">Isi data berikut. Kamu dapat menambahkan akses penulis nanti dari profil.</p>
            </div>

            <?php View::partial('partials/form-errors'); ?>

            <form class="form-stack" action="<?= e(url('daftar')) ?>" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label class="label" for="register-name">Nama tampilan</label>
                    <div class="input-group">
                        <span class="input-group__icon"><?= icon('user', 'icon icon--sm') ?></span>
                        <input class="input" type="text" id="register-name" name="display_name" value="<?= e(old('display_name')) ?>" autocomplete="nickname" required minlength="3" maxlength="80" placeholder="Nama yang tampil di komentar">
                    </div>
                    <?php if (form_error('display_name') !== null): ?><span class="field-error"><?= e(form_error('display_name')) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="register-email">Email</label>
                    <div class="input-group">
                        <span class="input-group__icon"><?= icon('mail', 'icon icon--sm') ?></span>
                        <input class="input" type="email" id="register-email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required maxlength="190" placeholder="nama@email.com">
                    </div>
                    <?php if (form_error('email') !== null): ?><span class="field-error"><?= e(form_error('email')) ?></span><?php endif; ?>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="register-password">Kata sandi</label>
                        <div class="input-group">
                            <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                            <input class="input" type="password" id="register-password" name="password" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Minimal 8 karakter">
                            <button class="input-group__button" type="button" data-password-toggle aria-controls="register-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                        </div>
                        <?php if (form_error('password') !== null): ?><span class="field-error"><?= e(form_error('password')) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label class="label" for="register-confirm">Ulangi kata sandi</label>
                        <div class="input-group">
                            <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                            <input class="input" type="password" id="register-confirm" name="password_confirm" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Ketik ulang">
                            <button class="input-group__button" type="button" data-password-toggle aria-controls="register-confirm" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                        </div>
                        <?php if (form_error('password_confirm') !== null): ?><span class="field-error"><?= e(form_error('password_confirm')) ?></span><?php endif; ?>
                    </div>
                </div>

                <p class="form-hint"><?= icon('info', 'icon icon--xs') ?> Gunakan minimal 8 karakter. Jangan gunakan kata sandi yang sama dengan situs lain.</p>

                <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('user', 'icon icon--sm') ?>Buat akun</button>
            </form>

            <p class="auth-switch">Sudah punya akun? <a href="<?= e(url('masuk')) ?>">Masuk di sini</a></p>
        </div>
    </div>
</section>
