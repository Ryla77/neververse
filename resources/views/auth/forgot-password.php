<?php

declare(strict_types=1);

use App\Core\View;
?>
<section class="auth-page">
    <div class="auth-shell">
        <aside class="auth-aside">
            <span class="eyebrow">Pemulihan akun</span>
            <h1>Kembali ke rak bacaanmu dalam beberapa langkah.</h1>
            <p>Masukkan email akun NovelVerse. Kami akan mengirim tautan aman untuk membuat kata sandi baru.</p>
            <ul class="auth-benefits">
                <li><?= icon('mail', 'icon icon--sm') ?> Tautan dikirim ke email terdaftar</li>
                <li><?= icon('clock', 'icon icon--sm') ?> Berlaku terbatas dan sekali pakai</li>
                <li><?= icon('shield', 'icon icon--sm') ?> Kata sandi baru disimpan sebagai hash</li>
            </ul>
            <div class="auth-aside__quote">
                <p>“Pintu yang terkunci bukan akhir dari cerita — selalu ada kunci cadangan.”</p>
                <span>— Catatan redaksi NovelVerse</span>
            </div>
        </aside>

        <div class="auth-card">
            <div class="auth-card__head">
                <span class="eyebrow">Akun</span>
                <h2>Lupa kata sandi</h2>
                <p class="small muted">Kami akan mengirim tautan reset ke email yang terdaftar.</p>
            </div>

            <?php View::partial('partials/form-errors'); ?>

            <form class="form-stack" action="<?= e(url('lupa-sandi')) ?>" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label class="label" for="forgot-email">Email</label>
                    <div class="input-group">
                        <span class="input-group__icon"><?= icon('mail', 'icon icon--sm') ?></span>
                        <input class="input" type="email" id="forgot-email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required maxlength="190" placeholder="nama@email.com">
                    </div>
                    <?php if (form_error('email') !== null): ?><span class="field-error"><?= e(form_error('email')) ?></span><?php endif; ?>
                </div>

                <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('mail', 'icon icon--sm') ?>Kirim tautan reset</button>
            </form>

            <p class="auth-switch"><a href="<?= e(url('masuk')) ?>">Kembali ke halaman masuk</a></p>
            <p class="auth-note"><?= icon('info', 'icon icon--xs') ?> Demi keamanan, halaman ini tidak memberi tahu apakah sebuah email terdaftar.</p>
        </div>
    </div>
</section>
