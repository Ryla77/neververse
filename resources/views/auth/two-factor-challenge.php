<?php

declare(strict_types=1);

use App\Core\View;
?>
<section class="auth-page">
    <div class="auth-shell auth-shell--single">
        <div class="auth-card">
            <div class="auth-card__head">
                <span class="eyebrow">Verifikasi dua langkah</span>
                <h2>Masukkan kode keamanan</h2>
                <p class="small muted">Buka aplikasi autentikator untuk akun <strong><?= e($email) ?></strong>, lalu masukkan kode 6 digit yang tampil. Kode berubah setiap 30 detik.</p>
            </div>

            <?php View::partial('partials/form-errors'); ?>

            <form class="form-stack" action="<?= e(url('masuk/2fa')) ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label class="label" for="two-factor-code">Kode autentikator</label>
                    <div class="input-group">
                        <span class="input-group__icon"><?= icon('shield', 'icon icon--sm') ?></span>
                        <input class="input input--code" type="text" id="two-factor-code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="123456">
                    </div>
                </div>

                <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('login', 'icon icon--sm') ?>Verifikasi dan masuk</button>

                <details class="recovery-toggle">
                    <summary>Kehilangan perangkat? Gunakan kode pemulihan</summary>
                    <div class="field">
                        <label class="label" for="two-factor-recovery">Kode pemulihan</label>
                        <div class="input-group">
                            <span class="input-group__icon"><?= icon('key', 'icon icon--sm') ?></span>
                            <input class="input input--code" type="text" id="two-factor-recovery" name="recovery_code" autocomplete="off" maxlength="9" placeholder="XXXX-XXXX">
                        </div>
                        <p class="form-hint"><?= icon('info', 'icon icon--xs') ?> Isi salah satu kode 8 karakter yang Anda simpan saat mengaktifkan verifikasi dua langkah. Setiap kode hanya dapat dipakai sekali.</p>
                    </div>
                </details>
            </form>

            <p class="auth-switch"><a href="<?= e(url('masuk')) ?>">Kembali ke halaman masuk</a></p>
            <p class="auth-note"><?= icon('clock', 'icon icon--xs') ?> Sesi verifikasi berlaku 10 menit. Setelah itu Anda perlu masuk kembali.</p>
        </div>
    </div>
</section>
