<?php

declare(strict_types=1);

use App\Core\View;

$isEdit = $mode === 'edit';
$action = $isEdit
    ? url('tulis/bab/' . $chapter['id'])
    : url('tulis/novel/' . $novel['id'] . '/bab');
$status = (string) old('status', $isEdit ? $chapter['status'] : 'draft');
?>
<div class="dashboard-page">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('tulis')) ?>">Dashboard penulis</a>
            <span aria-hidden="true">/</span>
            <a href="<?= e(url('tulis/novel/' . $novel['id'] . '/bab')) ?>"><?= e($novel['title']) ?></a>
            <span aria-hidden="true">/</span>
            <span><?= $isEdit ? 'Edit bab ' . (int) $chapter['chapter_number'] : 'Bab baru' ?></span>
        </nav>

        <header class="dashboard-head">
            <div>
                <span class="eyebrow"><?= $isEdit ? 'Edit bab' : 'Bab baru' ?></span>
                <h1><?= $isEdit ? 'Bab ' . (int) $chapter['chapter_number'] : 'Tulis bab baru' ?></h1>
                <p class="lead">Untuk <strong><?= e($novel['title']) ?></strong>. Simpan sebagai draf atau langsung terbitkan.</p>
            </div>
            <div class="dashboard-head__actions">
                <a class="btn btn--ghost" href="<?= e(url('tulis/novel/' . $novel['id'] . '/bab')) ?>"><?= icon('arrow-left', 'icon icon--sm') ?>Daftar bab</a>
                <?php if ($isEdit && $chapter['status'] === 'published' && $novel['publish_status'] === 'published'): ?>
                    <a class="btn btn--soft" href="<?= e(url('novel/' . $novel['slug'] . '/baca/' . $chapter['chapter_number'])) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon--sm') ?>Lihat bab</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <?php View::partial('partials/form-errors'); ?>

                <form class="panel form-stack" method="post" action="<?= e($action) ?>">
                    <?= csrf_field() ?>

                    <div class="panel__head">
                        <div>
                            <span class="eyebrow">Isi bab</span>
                            <h2><?= $isEdit ? 'Perbarui bab' : 'Detail bab' ?></h2>
                        </div>
                        <?php if ($isEdit): ?>
                            <span class="badge badge--<?= $status === 'published' ? 'published' : 'draft' ?>"><?= $status === 'published' ? 'Terbit' : 'Draf' ?></span>
                        <?php endif; ?>
                    </div>

                    <label class="field">
                        <span class="label">Judul bab <span class="required" aria-hidden="true">*</span></span>
                        <input class="input" type="text" name="title" value="<?= e((string) old('title', $isEdit ? $chapter['title'] : '')) ?>" maxlength="180" required autocomplete="off" placeholder="Contoh: Jejak di Balik Kabut">
                        <?php if ($error = form_error('title')): ?><span class="field-error"><?= e($error) ?></span><?php endif; ?>
                        <span class="form-hint">3–180 karakter. Nomor bab ditambahkan otomatis.</span>
                    </label>

                    <label class="field">
                        <span class="label">Isi bab <span class="required" aria-hidden="true">*</span></span>
                        <textarea class="textarea textarea--chapter" name="content" rows="18" maxlength="100000" required spellcheck="true" data-chapter-content placeholder="Tulis isi bab di sini. Pisahkan paragraf dengan satu baris kosong."><?= e((string) old('content', $isEdit ? $chapter['content'] : '')) ?></textarea>
                        <?php if ($error = form_error('content')): ?><span class="field-error"><?= e($error) ?></span><?php endif; ?>
                        <span class="form-hint">Minimal 50 karakter, maksimal 100.000 karakter. <span data-word-count>0 kata</span> · <span data-char-count>0 karakter</span></span>
                    </label>

                    <fieldset class="field">
                        <legend class="label">Status bab</legend>
                        <div class="radio-cards">
                            <label class="radio-card<?= $status === 'draft' ? ' is-selected' : '' ?>">
                                <input type="radio" name="status" value="draft" <?= $status === 'draft' ? 'checked' : '' ?>>
                                <span class="radio-card__body">
                                    <strong>Simpan sebagai draf</strong>
                                    <span class="small muted">Hanya kamu yang dapat melihat bab ini.</span>
                                </span>
                            </label>
                            <label class="radio-card<?= $status === 'published' ? ' is-selected' : '' ?>">
                                <input type="radio" name="status" value="published" <?= $status === 'published' ? 'checked' : '' ?>>
                                <span class="radio-card__body">
                                    <strong>Terbitkan sekarang</strong>
                                    <span class="small muted">Bab langsung dapat dibaca jika novel sudah terbit.</span>
                                </span>
                            </label>
                        </div>
                        <?php if ($error = form_error('status')): ?><span class="field-error"><?= e($error) ?></span><?php endif; ?>
                    </fieldset>

                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit"><?= icon('check', 'icon icon--sm') ?><?= $isEdit ? 'Simpan perubahan' : 'Simpan bab' ?></button>
                        <a class="btn btn--ghost" href="<?= e(url('tulis/novel/' . $novel['id'] . '/bab')) ?>">Batal</a>
                    </div>
                </form>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('info', 'icon icon--sm') ?>Tips menulis bab</h2>
                    </div>
                    <ul class="check-list">
                        <li>Gunakan satu baris kosong di antara paragraf agar nyaman dibaca.</li>
                        <li>Simpan draf lebih dulu jika isi bab belum selesai.</li>
                        <li>Menerbitkan bab pada novel draf tidak membuat novel otomatis terbit; publikasikan novelnya dari halaman edit novel.</li>
                        <?php if ($isEdit): ?>
                            <li>Menghapus bab dapat dilakukan dari daftar bab. Nomor bab yang sudah dihapus tidak digunakan ulang.</li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
