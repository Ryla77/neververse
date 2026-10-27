<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\AuthorController;
use App\Controllers\BookmarkController;
use App\Controllers\CommentController;
use App\Controllers\EmailVerificationController;
use App\Controllers\HomeController;
use App\Controllers\LibraryController;
use App\Controllers\NovelController;
use App\Controllers\PasswordResetController;
use App\Controllers\ProfileController;
use App\Controllers\RatingController;
use App\Controllers\ReaderController;
use App\Controllers\ReportController;
use App\Controllers\TwoFactorController;
use App\Core\Router;

return static function (Router $router): void {
    // Publik
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/jelajahi', [NovelController::class, 'index']);
    $router->get('/novel/{slug}', [NovelController::class, 'show']);
    $router->get('/novel/{slug}/baca/{chapter}', [ReaderController::class, 'show']);
    $router->post('/novel/{slug}/baca/{chapter}/progres', [ReaderController::class, 'progress']);

    // Interaksi pembaca
    $router->post('/novel/{slug}/simpan', [BookmarkController::class, 'toggle'], ['auth']);
    $router->post('/novel/{slug}/komentar', [CommentController::class, 'store'], ['auth']);
    $router->post('/komentar/{id}/hapus', [CommentController::class, 'destroy'], ['auth']);
    $router->post('/novel/{slug}/rating', [RatingController::class, 'store'], ['auth']);
    $router->post('/lapor', [ReportController::class, 'store'], ['auth']);

    // Autentikasi
    $router->get('/masuk', [AuthController::class, 'loginForm'], ['guest']);
    $router->post('/masuk', [AuthController::class, 'login'], ['guest']);
    $router->get('/masuk/2fa', [TwoFactorController::class, 'challengeForm']);
    $router->post('/masuk/2fa', [TwoFactorController::class, 'challenge']);
    $router->get('/daftar', [AuthController::class, 'registerForm'], ['guest']);
    $router->post('/daftar', [AuthController::class, 'register'], ['guest']);
    $router->post('/keluar', [AuthController::class, 'logout'], ['auth']);

    // Lupa dan reset kata sandi
    $router->get('/lupa-sandi', [PasswordResetController::class, 'requestForm'], ['guest']);
    $router->post('/lupa-sandi', [PasswordResetController::class, 'send'], ['guest']);
    $router->get('/reset-sandi/{token}', [PasswordResetController::class, 'resetForm'], ['guest']);
    $router->post('/reset-sandi', [PasswordResetController::class, 'reset'], ['guest']);

    // Verifikasi email
    $router->get('/verifikasi-email/{token}', [EmailVerificationController::class, 'verify']);
    $router->post('/verifikasi-email/kirim-ulang', [EmailVerificationController::class, 'resend'], ['auth']);

    // Perpustakaan
    $router->get('/perpustakaan', [LibraryController::class, 'index'], ['auth']);
    $router->post('/perpustakaan/{slug}/hapus', [BookmarkController::class, 'remove'], ['auth']);

    // Profil
    $router->get('/profil', [ProfileController::class, 'edit'], ['auth']);
    $router->post('/profil', [ProfileController::class, 'update'], ['auth']);
    $router->post('/profil/kata-sandi', [ProfileController::class, 'updatePassword'], ['auth']);
    $router->post('/profil/jadi-penulis', [ProfileController::class, 'becomeAuthor'], ['auth']);
    $router->get('/profil/2fa', [TwoFactorController::class, 'setup'], ['auth']);
    $router->post('/profil/2fa', [TwoFactorController::class, 'enable'], ['auth']);
    $router->post('/profil/2fa/nonaktif', [TwoFactorController::class, 'disable'], ['auth']);

    // Dashboard penulis
    $router->get('/tulis', [AuthorController::class, 'dashboard'], ['role:author|admin']);
    $router->get('/tulis/novel/baru', [AuthorController::class, 'create'], ['role:author|admin']);
    $router->post('/tulis/novel', [AuthorController::class, 'store'], ['role:author|admin']);
    $router->get('/tulis/novel/{id}/edit', [AuthorController::class, 'edit'], ['role:author|admin']);
    $router->post('/tulis/novel/{id}', [AuthorController::class, 'update'], ['role:author|admin']);
    $router->post('/tulis/novel/{id}/publikasi', [AuthorController::class, 'publish'], ['role:author|admin']);
    $router->post('/tulis/novel/{id}/sampul', [AuthorController::class, 'updateCover'], ['role:author|admin']);
    $router->post('/tulis/novel/{id}/hapus', [AuthorController::class, 'destroy'], ['role:author|admin']);
    $router->get('/tulis/novel/{id}/bab', [AuthorController::class, 'chapters'], ['role:author|admin']);
    $router->get('/tulis/novel/{id}/bab/baru', [AuthorController::class, 'createChapter'], ['role:author|admin']);
    $router->post('/tulis/novel/{id}/bab', [AuthorController::class, 'storeChapter'], ['role:author|admin']);
    $router->get('/tulis/bab/{id}/edit', [AuthorController::class, 'editChapter'], ['role:author|admin']);
    $router->post('/tulis/bab/{id}', [AuthorController::class, 'updateChapter'], ['role:author|admin']);
    $router->post('/tulis/bab/{id}/status', [AuthorController::class, 'chapterStatus'], ['role:author|admin']);
    $router->post('/tulis/bab/{id}/hapus', [AuthorController::class, 'destroyChapter'], ['role:author|admin']);

    // Dashboard admin
    $router->get('/admin', [AdminController::class, 'dashboard'], ['role:admin']);
    $router->get('/admin/pengguna', [AdminController::class, 'users'], ['role:admin']);
    $router->post('/admin/pengguna/{id}', [AdminController::class, 'updateUser'], ['role:admin']);
    $router->post('/admin/pengguna/{id}/reset-sandi', [AdminController::class, 'resetPassword'], ['role:admin']);
    $router->get('/admin/novel', [AdminController::class, 'novels'], ['role:admin']);
    $router->post('/admin/novel/{id}/publikasi', [AdminController::class, 'novelPublish'], ['role:admin']);
    $router->post('/admin/novel/{id}/unggulan', [AdminController::class, 'novelFeatured'], ['role:admin']);
    $router->post('/admin/novel/{id}/hapus', [AdminController::class, 'novelDestroy'], ['role:admin']);
    $router->get('/admin/bab', [AdminController::class, 'chapters'], ['role:admin']);
    $router->post('/admin/bab/{id}/status', [AdminController::class, 'chapterStatus'], ['role:admin']);
    $router->post('/admin/bab/{id}/hapus', [AdminController::class, 'chapterDestroy'], ['role:admin']);
    $router->get('/admin/genre', [AdminController::class, 'genres'], ['role:admin']);
    $router->post('/admin/genre', [AdminController::class, 'storeGenre'], ['role:admin']);
    $router->post('/admin/genre/{id}', [AdminController::class, 'updateGenre'], ['role:admin']);
    $router->post('/admin/genre/{id}/hapus', [AdminController::class, 'destroyGenre'], ['role:admin']);
    $router->get('/admin/komentar', [AdminController::class, 'comments'], ['role:admin']);
    $router->post('/admin/komentar/{id}/status', [AdminController::class, 'commentStatus'], ['role:admin']);
    $router->post('/admin/komentar/{id}/hapus', [AdminController::class, 'commentDestroy'], ['role:admin']);
    $router->get('/admin/laporan', [AdminController::class, 'reports'], ['role:admin']);
    $router->post('/admin/laporan/{id}/selesai', [AdminController::class, 'resolveReport'], ['role:admin']);
    $router->post('/admin/laporan/{id}/tolak', [AdminController::class, 'dismissReport'], ['role:admin']);
};
