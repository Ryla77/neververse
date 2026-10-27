<?php

declare(strict_types=1);

use App\Core\View;
?>
<div class="dashboard-page">
    <div class="container">
        <header class="dashboard-head">
            <div>
                <span class="eyebrow">Administrasi</span>
                <h1>Genre</h1>
                <p class="lead">Kelola genre yang digunakan untuk mengelompokkan novel. Genre yang masih dipakai tidak dapat dihapus.</p>
            </div>
        </header>

        <div class="dashboard-layout">
            <?php View::partial('partials/dashboard-nav'); ?>

            <div class="dashboard-main">
                <?php View::partial('partials/form-errors'); ?>

                <section class="panel">
                    <div class="panel__head">
                        <span class="eyebrow">Tambah</span>
                        <h2>Genre baru</h2>
                    </div>
                    <form class="form-grid form-grid--genre" method="post" action="<?= e(url('admin/genre')) ?>">
                        <?= csrf_field() ?>
                        <label class="field">
                            <span class="label">Nama genre <span class="required" aria-hidden="true">*</span></span>
                            <input class="input" type="text" name="name" value="<?= e((string) old('name')) ?>" maxlength="60" required placeholder="Contoh: Fantasi">
                            <?php if ($error = form_error('name')): ?><span class="field-error"><?= e($error) ?></span><?php endif; ?>
                        </label>
                        <label class="field">
                            <span class="label">Deskripsi</span>
                            <input class="input" type="text" name="description" value="<?= e((string) old('description')) ?>" maxlength="255" placeholder="Penjelasan singkat genre (opsional)">
                            <?php if ($error = form_error('description')): ?><span class="field-error"><?= e($error) ?></span><?php endif; ?>
                        </label>
                        <div class="form-actions form-actions--end">
                            <button class="btn btn--primary" type="submit"><?= icon('plus', 'icon icon--sm') ?>Tambah genre</button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <div class="panel__head panel__head--split">
                        <div>
                            <span class="eyebrow">Daftar</span>
                            <h2><?= count($genres) ?> genre</h2>
                        </div>
                        <a class="link-arrow" href="<?= e(url('jelajahi')) ?>">Lihat di katalog <?= icon('external', 'icon icon--xs') ?></a>
                    </div>

                    <?php if ($genres === []): ?>
                        <?php View::partial('partials/empty-state', [
                            'icon' => 'bookmark',
                            'title' => 'Belum ada genre',
                            'message' => 'Tambahkan genre pertama melalui formulir di atas.',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table table--genres">
                                <thead>
                                    <tr>
                                        <th>Genre</th>
                                        <th>Slug</th>
                                        <th>Novel</th>
                                        <th class="table__actions">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($genres as $genre): ?>
                                        <tr>
                                            <td>
                                                <form class="genre-form" method="post" action="<?= e(url('admin/genre/' . $genre['id'])) ?>" id="genre-<?= (int) $genre['id'] ?>">
                                                    <?= csrf_field() ?>
                                                    <label class="field field--inline">
                                                        <span class="sr-only">Nama genre</span>
                                                        <input class="input input--sm" type="text" name="name" value="<?= e($genre['name']) ?>" maxlength="60" required>
                                                    </label>
                                                    <label class="field field--inline genre-form__description">
                                                        <span class="sr-only">Deskripsi genre</span>
                                                        <input class="input input--sm" type="text" name="description" value="<?= e((string) ($genre['description'] ?? '')) ?>" maxlength="255" placeholder="Deskripsi (opsional)">
                                                    </label>
                                                </form>
                                            </td>
                                            <td><code class="code"><?= e($genre['slug']) ?></code></td>
                                            <td><span class="small"><?= (int) $genre['novel_count'] ?> novel</span></td>
                                            <td class="table__actions">
                                                <div class="action-row">
                                                    <button class="btn btn--soft btn--xs" type="submit" form="genre-<?= (int) $genre['id'] ?>">Simpan</button>
                                                    <form method="post" action="<?= e(url('admin/genre/' . $genre['id'] . '/hapus')) ?>" data-confirm="Hapus genre “<?= e($genre['name']) ?>”? Genre yang masih dipakai tidak akan terhapus.">
                                                        <?= csrf_field() ?>
                                                        <button class="icon-btn icon-btn--sm icon-btn--danger" type="submit" title="Hapus genre" aria-label="Hapus genre <?= e($genre['name']) ?>"><?= icon('trash', 'icon icon--xs') ?></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="panel panel--tips">
                    <div class="panel__head">
                        <h2><?= icon('info', 'icon icon--sm') ?>Tentang genre</h2>
                    </div>
                    <ul class="check-list">
                        <li>Nama genre harus unik. Slug dibuat otomatis dari nama dan dijaga tetap unik.</li>
                        <li>Menghapus genre hanya berhasil jika tidak ada novel yang memakainya.</li>
                        <li>Setiap novel wajib memiliki minimal satu genre saat dibuat atau diedit penulis.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>
