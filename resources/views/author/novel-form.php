<?php

declare(strict_types=1);

use App\Core\View;

$isEdit = $mode === 'edit' && $novel !== null;
$action = $isEdit ? url('tulis/novel/' . $novel['id']) : url('tulis/novel');
$oldCategories = old('categories', null);
$checkedCategories = is_array($oldCategories)
    ? array_map(static fn (mixed $value): int => (int) $value, $oldCategories)
    : array_map(static fn (mixed $value): int => (int) $value, $selectedCategories);
$value = static function (string $key, string $default = '') use ($novel): string {
    $fallback = $novel !== null ? (string) ($novel[$key] ?? $default) : $default;
    return (string) old($key, $fallback);
};
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow"><?= $isEdit ? 'Edit karya' : 'Karya baru' ?></span>
                <h1><?= $isEdit ? 'Edit novel' : 'Novel baru' ?></h1>
                <p class="lead"><?= $isEdit ? 'Perbarui detail, sampul, genre, dan status publikasi novelmu.' : 'Isi detail dasar novel. Setelah dibuat, kamu dapat langsung menulis bab dan mengunggah sampul.' ?></p>
            </div>
            <div class="dashboard-head__actions">
                <a class="btn btn--ghost" href="<?= e(url('tulis')) ?>"><?= icon('arrow-left', 'icon icon--sm') ?>Dashboard</a>
                <?php if ($isEdit): ?>
                    <a class="btn btn--soft" href="<?= e(url('tulis/novel/' . $novel['id'] . '/bab')) ?>"><?= icon('list', 'icon icon--sm') ?>Kelola bab</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <?php View::partial('partials/form-errors'); ?>

                <?php if ($isEdit && $novel['publish_status'] === 'published'): ?>
                    <div class="notice notice--success">
                        <?= icon('check', 'icon icon--sm') ?>
                        <p>Novel ini sudah terbit dan dapat dibaca publik. <a href="<?= e(url('novel/' . $novel['slug'])) ?>" target="_blank" rel="noopener">Lihat halaman publik</a>.</p>
                    </div>
                <?php elseif ($isEdit): ?>
                    <div class="notice">
                        <?= icon('info', 'icon icon--sm') ?>
                        <p>Novel masih berupa draf. Publikasikan dari kartu <strong>Publikasi</strong> setelah memiliki minimal satu bab terbit.</p>
                    </div>
                <?php endif; ?>

                <div class="editor-grid">
                    <form class="panel form-stack" action="<?= e($action) ?>" method="post"<?= $isEdit ? '' : ' enctype="multipart/form-data"' ?> novalidate>
                        <?= csrf_field() ?>
                        <div class="panel__head">
                            <div>
                                <span class="eyebrow">Detail novel</span>
                                <h2>Informasi utama</h2>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="novel-title">Judul novel</label>
                            <input class="input" type="text" id="novel-title" name="title" value="<?= e($value('title')) ?>" required minlength="3" maxlength="180" placeholder="Contoh: Lentera di Ujung Musim">
                            <?php if (form_error('title') !== null): ?><span class="field-error"><?= e(form_error('title')) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label class="label" for="novel-synopsis">Sinopsis</label>
                            <textarea class="textarea" id="novel-synopsis" name="synopsis" rows="8" required minlength="30" maxlength="5000" placeholder="Tuliskan gambaran cerita, konflik utama, dan suasana novel…"><?= e($value('synopsis')) ?></textarea>
                            <span class="form-hint">Minimal 30 karakter, maksimal 5.000 karakter. Jangan mengungkap seluruh akhir cerita.</span>
                            <?php if (form_error('synopsis') !== null): ?><span class="field-error"><?= e(form_error('synopsis')) ?></span><?php endif; ?>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="label" for="novel-status">Status penulisan</label>
                                <select class="select" id="novel-status" name="status" required>
                                    <option value="ongoing"<?= $value('status', 'ongoing') === 'ongoing' ? ' selected' : '' ?>>Sedang berjalan</option>
                                    <option value="completed"<?= $value('status', 'ongoing') === 'completed' ? ' selected' : '' ?>>Selesai</option>
                                </select>
                                <?php if (form_error('status') !== null): ?><span class="field-error"><?= e(form_error('status')) ?></span><?php endif; ?>
                            </div>
                            <div class="field">
                                <span class="label">Visibilitas</span>
                                <div class="static-field">
                                    <?= icon($isEdit && $novel['publish_status'] === 'published' ? 'eye' : 'lock', 'icon icon--sm') ?>
                                    <?= $isEdit ? ($novel['publish_status'] === 'published' ? 'Terbit untuk publik' : 'Draf — hanya kamu yang melihat') : 'Draf — novel baru selalu dimulai sebagai draf' ?>
                                </div>
                            </div>
                        </div>

                        <fieldset class="field">
                            <legend class="label">Genre <span class="label__hint">pilih minimal satu</span></legend>
                            <div class="checkbox-grid">
                                <?php foreach ($categories as $category): ?>
                                    <label class="checkbox">
                                        <input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>" <?= in_array((int) $category['id'], $checkedCategories, true) ? 'checked' : '' ?>>
                                        <span><?= e($category['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (form_error('categories') !== null): ?><span class="field-error"><?= e(form_error('categories')) ?></span><?php endif; ?>
                        </fieldset>

                        <?php if (!$isEdit): ?>
                            <div class="field">
                                <label class="label" for="novel-cover">Sampul <span class="label__hint">opsional</span></label>
                                <input class="input input--file" type="file" id="novel-cover" name="cover" accept="image/jpeg,image/png,image/webp" data-image-input data-preview-target="#novel-cover-preview">
                                <span class="form-hint">JPG, PNG, atau WEBP · maksimal 2 MB · minimal 200 × 200 piksel. Tanpa sampul, placeholder menarik akan digunakan.</span>
                                <div class="cover-preview">
                                    <img id="novel-cover-preview" src="<?= e(asset('assets/images/covers/placeholder.svg')) ?>" alt="Pratinjau sampul" width="120" height="180">
                                </div>
                                <?php if (form_error('cover') !== null): ?><span class="field-error"><?= e(form_error('cover')) ?></span><?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button class="btn btn--primary" type="submit"><?= $isEdit ? 'Simpan perubahan' : 'Buat draf novel' ?></button>
                            <a class="btn btn--ghost" href="<?= e(url('tulis')) ?>">Batal</a>
                        </div>
                    </form>

                    <aside class="editor-aside">
                        <?php if ($isEdit): ?>
                            <section class="panel">
                                <div class="panel__head">
                                    <h2>Sampul novel</h2>
                                </div>
                                <img class="cover-current" src="<?= e(cover_url($novel['cover_path'])) ?>" alt="Sampul <?= e($novel['title']) ?>" width="180" height="270">
                                <form class="form-stack" action="<?= e(url('tulis/novel/' . $novel['id'] . '/sampul')) ?>" method="post" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <div class="field">
                                        <label class="label" for="cover-file">Ganti sampul</label>
                                        <input class="input input--file" type="file" id="cover-file" name="cover" accept="image/jpeg,image/png,image/webp" required>
                                        <span class="form-hint">JPG, PNG, atau WEBP · maks. 2 MB.</span>
                                    </div>
                                    <button class="btn btn--soft btn--sm btn--block" type="submit"><?= icon('upload', 'icon icon--sm') ?>Unggah sampul</button>
                                </form>
                            </section>

                            <section class="panel">
                                <div class="panel__head">
                                    <h2>Publikasi</h2>
                                </div>
                                <dl class="meta-list">
                                    <div><dt>Status</dt><dd><span class="badge badge--<?= $novel['publish_status'] === 'published' ? 'published' : 'draft' ?>"><?= $novel['publish_status'] === 'published' ? 'Terbit' : 'Draf' ?></span></dd></div>
                                    <div><dt>Bab terbit</dt><dd><?= (int) $novel['chapter_count'] ?> dari <?= (int) $novel['chapter_total'] ?></dd></div>
                                </dl>
                                <?php if ($novel['publish_status'] === 'published'): ?>
                                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('novel/' . $novel['slug'])) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon--sm') ?>Lihat halaman publik</a>
                                    <form class="mt-2" action="<?= e(url('tulis/novel/' . $novel['id'] . '/publikasi')) ?>" method="post" data-confirm="Kembalikan novel menjadi draf? Pembaca tidak akan dapat mengaksesnya sampai diterbitkan kembali.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="unpublish">
                                        <button class="btn btn--ghost btn--sm btn--block" type="submit">Kembalikan ke draf</button>
                                    </form>
                                <?php else: ?>
                                    <p class="form-hint">Minimal satu bab harus berstatus terbit sebelum novel dapat dipublikasikan.</p>
                                    <form action="<?= e(url('tulis/novel/' . $novel['id'] . '/publikasi')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="publish">
                                        <button class="btn btn--primary btn--sm btn--block" type="submit"><?= icon('eye', 'icon icon--sm') ?>Publikasikan novel</button>
                                    </form>
                                <?php endif; ?>
                                <a class="btn btn--ghost btn--sm btn--block mt-2" href="<?= e(url('tulis/novel/' . $novel['id'] . '/bab')) ?>"><?= icon('list', 'icon icon--sm') ?>Kelola bab</a>
                            </section>

                            <section class="panel panel--danger">
                                <div class="panel__head">
                                    <h2>Zona berbahaya</h2>
                                </div>
                                <p class="small muted">Menghapus novel akan menghapus seluruh bab, komentar, rating, dan data perpustakaan yang terkait. Tindakan ini tidak dapat dibatalkan.</p>
                                <form action="<?= e(url('tulis/novel/' . $novel['id'] . '/hapus')) ?>" method="post" data-confirm="Hapus novel “<?= e($novel['title']) ?>” beserta seluruh babnya? Tindakan ini tidak dapat dibatalkan.">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm btn--block" type="submit"><?= icon('trash', 'icon icon--sm') ?>Hapus novel</button>
                                </form>
                            </section>
                        <?php else: ?>
                            <section class="panel panel--tips">
                                <div class="panel__head">
                                    <h2><?= icon('info', 'icon icon--sm') ?>Sebelum menulis</h2>
                                </div>
                                <ul class="check-list">
                                    <li>Judul yang jelas membantu pembaca menemukan ceritamu.</li>
                                    <li>Sinopsis yang baik memperkenalkan konflik tanpa membocorkan akhir.</li>
                                    <li>Pilih genre yang paling menggambarkan cerita.</li>
                                    <li>Novel baru tersimpan sebagai draf dan belum tampil di katalog.</li>
                                    <li>Sampul dapat diunggah sekarang atau nanti melalui halaman edit.</li>
                                </ul>
                            </section>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</div>
