<?php

declare(strict_types=1);

use App\Core\View;

$historyUrl = static function (array $item): string {
    if (!empty($item['chapter_number'])) {
        return url('novel/' . $item['slug'] . '/baca/' . (int) $item['chapter_number']);
    }
    return url('novel/' . $item['slug']);
};
?>
<section class="profile-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('/')) ?>">Beranda</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Profil</span>
        </nav>
        <div class="profile-hero__inner">
            <span class="avatar profile-avatar"><?= e(initials((string) $user['display_name'])) ?></span>
            <div class="profile-hero__body">
                <div class="profile-hero__title">
                    <h1><?= e($user['display_name']) ?></h1>
                    <span class="badge badge--gold"><?= e(role_label((string) $user['role'])) ?></span>
                </div>
                <p class="small muted"><?= e($user['email']) ?> · Bergabung <?= e(format_date((string) $user['created_at'])) ?></p>
                <p class="profile-hero__bio"><?= $user['bio'] !== null && $user['bio'] !== '' ? e($user['bio']) : 'Belum ada bio. Tambahkan sedikit tentang dirimu di bawah.' ?></p>
            </div>
            <div class="profile-hero__stats">
                <div><strong><?= count($bookmarks) ?></strong><span>favorit</span></div>
                <div><strong><?= count($history) ?></strong><span>riwayat</span></div>
                <div><strong><?= $user['last_login_at'] !== null ? e(time_ago((string) $user['last_login_at'])) : 'Baru' ?></strong><span>login terakhir</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section section--top">
    <div class="container profile-grid">
        <div class="profile-main">
            <section class="panel" id="informasi">
                <div class="panel__head">
                    <div>
                        <span class="eyebrow">Informasi akun</span>
                        <h2>Detail profil</h2>
                    </div>
                </div>
                <?php View::partial('partials/form-errors'); ?>
                <form class="form-stack" action="<?= e(url('profil')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="field">
                            <label class="label" for="profile-name">Nama tampilan</label>
                            <input class="input" type="text" id="profile-name" name="display_name" value="<?= e(old('display_name', $user['display_name'])) ?>" required minlength="3" maxlength="80" autocomplete="nickname">
                            <?php if (form_error('display_name') !== null): ?><span class="field-error"><?= e(form_error('display_name')) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="label" for="profile-email">Email</label>
                            <input class="input" type="email" id="profile-email" name="email" value="<?= e(old('email', $user['email'])) ?>" required maxlength="190" autocomplete="email">
                            <?php if (form_error('email') !== null): ?><span class="field-error"><?= e(form_error('email')) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="profile-bio">Bio <span class="label__hint">opsional</span></label>
                        <textarea class="textarea" id="profile-bio" name="bio" rows="4" maxlength="500" placeholder="Ceritakan genre favoritmu atau sedang membaca apa…"><?= e(old('bio', $user['bio'] ?? '')) ?></textarea>
                        <span class="form-hint">Maksimal 500 karakter. Tampil di halaman profilmu.</span>
                        <?php if (form_error('bio') !== null): ?><span class="field-error"><?= e(form_error('bio')) ?></span><?php endif; ?>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit">Simpan perubahan</button>
                    </div>
                </form>
            </section>

            <section class="panel" id="keamanan">
                <div class="panel__head">
                    <div>
                        <span class="eyebrow">Keamanan</span>
                        <h2>Ubah kata sandi</h2>
                    </div>
                </div>
                <form class="form-stack" action="<?= e(url('profil/kata-sandi')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label class="label" for="current-password">Kata sandi saat ini</label>
                        <div class="input-group">
                            <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                            <input class="input" type="password" id="current-password" name="current_password" autocomplete="current-password" required maxlength="72">
                            <button class="input-group__button" type="button" data-password-toggle aria-controls="current-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                        </div>
                        <?php if (form_error('current_password') !== null): ?><span class="field-error"><?= e(form_error('current_password')) ?></span><?php endif; ?>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label class="label" for="new-password">Kata sandi baru</label>
                            <div class="input-group">
                                <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                                <input class="input" type="password" id="new-password" name="new_password" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Minimal 8 karakter">
                                <button class="input-group__button" type="button" data-password-toggle aria-controls="new-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                            </div>
                            <?php if (form_error('new_password') !== null): ?><span class="field-error"><?= e(form_error('new_password')) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="label" for="confirm-password">Ulangi kata sandi baru</label>
                            <div class="input-group">
                                <span class="input-group__icon"><?= icon('lock', 'icon icon--sm') ?></span>
                                <input class="input" type="password" id="confirm-password" name="new_password_confirm" autocomplete="new-password" required minlength="8" maxlength="72">
                                <button class="input-group__button" type="button" data-password-toggle aria-controls="confirm-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'icon icon--sm') ?></button>
                            </div>
                            <?php if (form_error('new_password_confirm') !== null): ?><span class="field-error"><?= e(form_error('new_password_confirm')) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <p class="form-hint"><?= icon('shield', 'icon icon--xs') ?> Setelah kata sandi diubah, sesi akan diperbarui. Gunakan kata sandi unik yang tidak dipakai di situs lain.</p>
                    <div class="form-actions">
                        <button class="btn btn--soft" type="submit">Perbarui kata sandi</button>
                    </div>
                </form>
            </section>

            <section class="panel">
                <div class="panel__head panel__head--split">
                    <div>
                        <span class="eyebrow">Koleksi</span>
                        <h2>Novel favorit</h2>
                    </div>
                    <a class="link-arrow" href="<?= e(url('perpustakaan')) ?>">Buka perpustakaan <?= icon('arrow-right', 'icon icon--xs') ?></a>
                </div>
                <?php if ($bookmarks === []): ?>
                    <p class="small muted">Belum ada novel favorit. Simpan novel dari halaman detail untuk melihatnya di sini.</p>
                <?php else: ?>
                    <div class="mini-library">
                        <?php foreach (array_slice($bookmarks, 0, 4) as $bookmark): ?>
                            <a class="mini-book" href="<?= e(url('novel/' . $bookmark['slug'])) ?>">
                                <img src="<?= e(cover_url($bookmark['cover_path'])) ?>" alt="Sampul <?= e($bookmark['title']) ?>" width="64" height="96" loading="lazy">
                                <span>
                                    <strong><?= e($bookmark['title']) ?></strong>
                                    <span class="small muted"><?= e($bookmark['author_name']) ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="profile-aside">
            <?php if ($user['role'] === 'reader'): ?>
                <section class="panel upgrade-card" id="jadi-penulis">
                    <span class="upgrade-card__icon"><?= icon('pencil') ?></span>
                    <span class="eyebrow">Untuk pembaca yang menulis</span>
                    <h2>Jadi penulis NovelVerse</h2>
                    <p class="small">Aktifkan akses penulis untuk membuat novel, mengunggah sampul, menulis bab, dan mengelola draf. Akses ini gratis dan dapat dimulai kapan saja.</p>
                    <form action="<?= e(url('profil/jadi-penulis')) ?>" method="post">
                        <?= csrf_field() ?>
                        <button class="btn btn--primary btn--block" type="submit">Aktifkan akses penulis <?= icon('arrow-right', 'icon icon--sm') ?></button>
                    </form>
                </section>
            <?php else: ?>
                <section class="panel upgrade-card">
                    <span class="upgrade-card__icon"><?= icon('pencil') ?></span>
                    <span class="eyebrow">Ruang penulis</span>
                    <h2>Kelola karyamu</h2>
                    <p class="small">Lanjutkan menulis, atur bab, dan pantau novel yang sudah kamu terbitkan.</p>
                    <a class="btn btn--soft btn--block" href="<?= e(url('tulis')) ?>">Buka dashboard penulis <?= icon('arrow-right', 'icon icon--sm') ?></a>
                </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel__head">
                    <h2>Riwayat terakhir</h2>
                </div>
                <?php if ($history === []): ?>
                    <p class="small muted">Belum ada riwayat membaca.</p>
                <?php else: ?>
                    <ul class="mini-history">
                        <?php foreach ($history as $item): ?>
                            <li>
                                <a href="<?= e($historyUrl($item)) ?>">
                                    <span>
                                        <strong><?= e($item['title']) ?></strong>
                                        <span class="small muted"><?= !empty($item['chapter_number']) ? 'Bab ' . (int) $item['chapter_number'] : 'Novel dibuka' ?> · <?= number_format((float) $item['progress_percent'], 0, ',', '.') ?>%</span>
                                    </span>
                                    <span class="small muted"><?= e(time_ago((string) $item['last_read_at'])) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a class="btn btn--ghost btn--block btn--sm" href="<?= e(url('perpustakaan')) ?>">Lihat semua riwayat</a>
            </section>

            <section class="panel">
                <div class="panel__head">
                    <h2>Ringkasan akun</h2>
                </div>
                <dl class="meta-list">
                    <div><dt>Peran</dt><dd><span class="badge badge--gold"><?= e(role_label((string) $user['role'])) ?></span></dd></div>
                    <div><dt>Status</dt><dd><?= (int) $user['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></dd></div>
                    <div><dt>Bergabung</dt><dd><?= e(format_date((string) $user['created_at'])) ?></dd></div>
                    <div><dt>Login terakhir</dt><dd><?= $user['last_login_at'] !== null ? e(format_date((string) $user['last_login_at'], true)) : '—' ?></dd></div>
                </dl>
            </section>
        </aside>
    </div>
</section>
