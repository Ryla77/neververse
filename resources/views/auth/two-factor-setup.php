<?php

declare(strict_types=1);

use App\Core\View;
?>
<section class="auth-page">
    <div class="auth-shell auth-shell--single">
        <div class="auth-card">
            <div class="auth-card__head">
                <span class="eyebrow">Keamanan akun</span>
                <h2>Aktifkan verifikasi dua langkah</h2>
                <p class="small muted">Setelah aktif, setiap kali masuk Anda perlu memasukkan kata sandi dan kode 6 digit dari aplikasi autentikator.</p>
            </div>

            <?php View::partial('partials/form-errors'); ?>

            <ol class="two-factor-steps">
                <li>Buka aplikasi autentikator (Google Authenticator, Microsoft Authenticator, 1Password, Authy, atau sejenisnya).</li>
                <li>Tambahkan akun baru, lalu masukkan kunci berikut secara manual:</li>
            </ol>

            <p class="secret-key" aria-label="Kunci rahasia verifikasi dua langkah"><code><?= e(implode("\n", $secretLines)) ?></code></p>
            <p class="form-hint"><?= icon('info', 'icon icon--xs') ?> Akun: <strong><?= e($email) ?></strong>. Jenis: berbasis waktu (TOTP), 6 digit, 30 detik. Kunci ini hanya ditampilkan sekali di halaman ini.</p>
            <p class="form-hint"><?= icon('external', 'icon icon--xs') ?> Bila perangkat mendukung, <a href="<?= e($otpauthUri) ?>">buka tautan otpauth://</a> untuk menambahkan akun secara otomatis.</p>

            <form class="form-stack" action="<?= e(url('profil/2fa')) ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label class="label" for="two-factor-confirm">Kode 6 digit dari aplikasi</label>
                    <div class="input-group">
                        <span class="input-group__icon"><?= icon('shield', 'icon icon--sm') ?></span>
                        <input class="input input--code" type="text" id="two-factor-confirm" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required placeholder="123456">
                    </div>
                </div>
                <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('check', 'icon icon--sm') ?>Aktifkan verifikasi dua langkah</button>
            </form>

            <p class="auth-switch"><a href="<?= e(url('profil#keamanan')) ?>">Batal dan kembali ke profil</a></p>
            <p class="auth-note"><?= icon('lock', 'icon icon--xs') ?> Simpan kunci ini di tempat aman. Kunci dan kode pemulihan tidak dapat ditampilkan ulang setelah halaman ditutup.</p>
        </div>
    </div>
</section>
