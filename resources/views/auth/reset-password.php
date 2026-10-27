<?php

declare(strict_types=1);

use App\Core\View;

$isValid = (bool) ($valid ?? false);
$tokenValue = (string) ($token ?? '');
$expiresAt = $expiresAt ?? null;
$ttlMinutes = (int) ($ttlMinutes ?? 60);
?>
<section class="auth-page">
    <div class="auth-shell">
        <aside class="auth-aside">
            <span class="eyebrow">Kata sandi baru</span>
            <h1>Amankan kembali akunmu.</h1>
            <p>Buat kata sandi baru yang kuat. Setelah berhasil, semua sesi lama otomatis tidak berlaku.</p>
            <ul class="auth-benefits">
                <li><?= icon('lock', 'icon icon--sm') ?> Minimal 8 karakter</li>
                <li><?= icon('shield', 'icon icon--sm') ?> Disimpan sebagai hash bcrypt</li>
                <li><?= icon('logout', 'icon icon--sm') ?> Sesi lama diakhiri</li>
            </ul>
            <div class="auth-aside__quote">
                <p>“Kata sandi yang baik adalah kata sandi yang tidak pernah kamu bagikan.”</p>
                <span>— Catatan redaksi NovelVerse</span>
            </div>
        </aside>

        <div class="auth-card">
            <div class="auth-card__head">
                <span class="eyebrow">Pemulihan akun</span>
                <h2>Atur kata sandi baru</h2>
                <p class="small muted">Masukkan kata sandi baru untuk akunmu.</p>
            </div>

            <?php if (!$isValid): ?>
                <div class="form-errors" role="alert">
                    <strong><?= icon('alert', 'icon icon--sm') ?> Tautan tidak dapat digunakan</strong>
                    <ul>
                        <li>Tautan reset tidak valid, sudah digunakan, atau sudah kedaluwarsa.</li>
                    </ul>
                </div>
                <p class="auth-switch"><a href="<?= e(url('lupa-sandi')) ?>">Minta tautan reset baru</a></p>
                <p class="auth-switch"><a href="<?= e(url('masuk')) ?>">Kembali ke halaman masuk</a></p>
            <?php else: ?>
                <?php View::partial('partials/form-errors'); ?>

                <form class="form-stack" action="<?= e(url('reset-sandi')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($tokenValue) ?>">

                    <div class="field">
                        <label class="label" for="reset-password">Kata sandi baru</label>
                        <div class="input-group">
                            <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                            <input class="input" type="password" id="reset-password" name="password" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Minimal 8 karakter">
                            <button class="input-group__button" type="button" data-password-toggle aria-controls="reset-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                        </div>
                        <?php if (form_error('password') !== null): ?><span class="field-error"><?= e(form_error('password')) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label class="label" for="reset-confirm">Ulangi kata sandi baru</label>
                        <div class="input-group">
                            <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                            <input class="input" type="password" id="reset-confirm" name="password_confirm" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Ketik ulang kata sandi">
                            <button class="input-group__button" type="button" data-password-toggle aria-controls="reset-confirm" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                        </div>
                        <?php if (form_error('password_confirm') !== null): ?><span class="field-error"><?= e(form_error('password_confirm')) ?></span><?php endif; ?>
                    </div>

                    <p class="form-hint"><?= icon('clock', 'icon icon--xs') ?> Tautan ini hanya dapat digunakan sekali<?php if (is_string($expiresAt) && $expiresAt !== ''): ?> dan berlaku hingga <?= e(format_date($expiresAt, true)) ?><?php else: ?> dan berlaku selama <?= e((string) $ttlMinutes) ?> menit<?php endif; ?>.</p>

                    <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('shield', 'icon icon--sm') ?>Simpan kata sandi baru</button>
                </form>

                <p class="auth-switch"><a href="<?= e(url('masuk')) ?>">Batal dan kembali ke halaman masuk</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>
