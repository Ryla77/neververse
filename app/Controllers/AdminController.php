<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Core\Validator;
use App\Models\CategoryModel;
use App\Models\ChapterModel;
use App\Models\CommentModel;
use App\Models\NovelModel;
use App\Models\PasswordResetModel;
use App\Models\RatingModel;
use App\Models\ReportModel;
use App\Models\UserModel;
use App\Services\TwoFactorService;

final class AdminController extends Controller
{
    public function dashboard(Request $request): void
    {
        $this->render('admin/dashboard', [
            'title' => 'Dashboard Admin — NovelVerse',
            'metaDescription' => 'Ringkasan pengguna, konten, dan moderasi NovelVerse.',
            'stats' => [
                'users' => UserModel::countAll(),
                'admins' => UserModel::countByRole('admin'),
                'authors' => UserModel::countByRole('author'),
                'readers' => UserModel::countByRole('reader'),
                'novels' => NovelModel::countAll(),
                'published_novels' => NovelModel::countPublished(),
                'chapters' => ChapterModel::countAll(),
                'published_chapters' => ChapterModel::countPublished(),
                'comments' => CommentModel::countAll(),
                'ratings' => RatingModel::countAll(),
                'open_reports' => ReportModel::countOpen(),
            ],
            'recentUsers' => UserModel::recent(5),
            'recentComments' => CommentModel::recent(5),
            'openReports' => ReportModel::list('open', 1, 5),
            'recentNovels' => NovelModel::adminList('', '', 1, 5),
        ]);
    }

    public function users(Request $request): void
    {
        $query = mb_substr($request->string('q'), 0, 100);
        $page = max(1, $request->int('page', 1));

        $this->render('admin/users', [
            'title' => 'Pengguna — Admin NovelVerse',
            'metaDescription' => 'Kelola pengguna, peran, dan status akun NovelVerse.',
            'result' => UserModel::adminList($query, $page),
            'query' => $query,
        ]);
    }

    public function updateUser(Request $request, string $id): void
    {
        $targetId = (int) $id;
        $user = UserModel::find($targetId);
        if ($user === null) {
            abort(404);
        }

        $role = $request->string('role');
        if (!in_array($role, ['admin', 'author', 'reader'], true)) {
            $this->flashError('Peran pengguna tidak valid.');
            Response::back('admin/pengguna');
        }

        $active = $request->input('is_active') !== null;
        $isActive = (bool) $user['is_active'];
        if ($targetId === (int) Auth::id() && ($role !== $user['role'] || $active !== $isActive)) {
            $this->flashError('Kamu tidak dapat mengubah peran atau menonaktifkan akunmu sendiri.');
            Response::back('admin/pengguna');
        }

        $wasActiveAdmin = $user['role'] === 'admin' && $isActive;
        $willBeActiveAdmin = $role === 'admin' && $active;
        if ($wasActiveAdmin && !$willBeActiveAdmin && UserModel::countActiveAdmins() <= 1) {
            $this->flashError('Minimal harus ada satu admin aktif. Angkat admin lain terlebih dahulu.');
            Response::back('admin/pengguna');
        }

        if ($role !== $user['role']) {
            UserModel::setRole($targetId, $role);
        }
        if ($active !== $isActive) {
            UserModel::setActive($targetId, $active);
            if (!$active) {
                PasswordResetModel::deleteForUser($targetId);
            }
        }

        $this->flashSuccess('Data pengguna ' . $user['display_name'] . ' berhasil diperbarui.');
        Response::back('admin/pengguna');
    }

    public function resetPassword(Request $request, string $id): void
    {
        $user = UserModel::find((int) $id);
        if ($user === null) {
            abort(404);
        }

        $password = $this->randomPassword(16);
        UserModel::updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        PasswordResetModel::deleteForUser((int) $user['id']);
        $this->flashSuccess(
            'Kata sandi sementara untuk ' . $user['email'] . ': ' . $password
            . '. Tampilkan sekali ini dan minta pengguna segera menggantinya.'
        );
        Response::back('admin/pengguna');
    }

    public function resetTwoFactor(Request $request, string $id): void
    {
        $user = UserModel::find((int) $id);
        if ($user === null) {
            abort(404);
        }

        if (!TwoFactorService::enabled((int) $user['id'])) {
            $this->flashInfo('Verifikasi dua langkah tidak aktif pada akun ' . $user['display_name'] . '.');
            Response::back('admin/pengguna');
        }

        TwoFactorService::disable((int) $user['id']);
        $this->flashSuccess(
            'Verifikasi dua langkah untuk ' . $user['display_name'] . ' telah direset. '
            . 'Pengguna dapat masuk dengan kata sandi dan mengaktifkannya kembali dari profil.'
        );
        Response::back('admin/pengguna');
    }

    public function novels(Request $request): void
    {
        $query = mb_substr($request->string('q'), 0, 100);
        $status = $request->string('status');
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = '';
        }
        $page = max(1, $request->int('page', 1));

        $this->render('admin/novels', [
            'title' => 'Novel — Admin NovelVerse',
            'metaDescription' => 'Kelola publikasi, unggulan, dan penghapusan novel.',
            'result' => NovelModel::adminList($query, $status, $page),
            'query' => $query,
            'status' => $status,
        ]);
    }

    public function novelPublish(Request $request, string $id): void
    {
        $novel = NovelModel::find((int) $id);
        if ($novel === null) {
            abort(404);
        }

        $action = $request->string('action');
        if ($action === 'publish') {
            if (ChapterModel::countPublishedByNovel((int) $novel['id']) < 1) {
                $this->flashError('Novel belum memiliki bab terbit sehingga tidak dapat dipublikasikan.');
                Response::back('admin/novel');
            }
            NovelModel::setPublishStatus((int) $novel['id'], 'published');
            $this->flashSuccess('Novel dipublikasikan.');
        } elseif ($action === 'unpublish') {
            NovelModel::setPublishStatus((int) $novel['id'], 'draft');
            NovelModel::setFeatured((int) $novel['id'], false);
            $this->flashInfo('Novel dikembalikan menjadi draf.');
        } else {
            abort(404);
        }

        Response::back('admin/novel');
    }

    public function novelFeatured(Request $request, string $id): void
    {
        $novel = NovelModel::find((int) $id);
        if ($novel === null) {
            abort(404);
        }
        if ($novel['publish_status'] !== 'published') {
            $this->flashError('Hanya novel terbit yang dapat dijadikan unggulan.');
            Response::back('admin/novel');
        }

        $featured = (int) $novel['is_featured'] !== 1;
        NovelModel::setFeatured((int) $novel['id'], $featured);
        $this->flashSuccess($featured ? 'Novel ditandai sebagai unggulan.' : 'Tanda unggulan dihapus.');
        Response::back('admin/novel');
    }

    public function novelDestroy(Request $request, string $id): void
    {
        $novel = NovelModel::find((int) $id);
        if ($novel === null) {
            abort(404);
        }

        NovelModel::delete((int) $novel['id']);
        Uploader::delete($novel['cover_path']);
        $this->flashInfo('Novel beserta bab dan interaksinya telah dihapus.');
        Response::back('admin/novel');
    }

    public function chapters(Request $request): void
    {
        $query = mb_substr($request->string('q'), 0, 100);
        $status = $request->string('status');
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = '';
        }
        $page = max(1, $request->int('page', 1));

        $this->render('admin/chapters', [
            'title' => 'Bab — Admin NovelVerse',
            'metaDescription' => 'Kelola status dan hapus bab di seluruh novel.',
            'result' => ChapterModel::adminList($query, $status, $page),
            'query' => $query,
            'status' => $status,
        ]);
    }

    public function chapterStatus(Request $request, string $id): void
    {
        $chapter = ChapterModel::find((int) $id);
        if ($chapter === null) {
            abort(404);
        }

        $action = $request->string('action');
        if (!in_array($action, ['publish', 'draft'], true)) {
            abort(404);
        }

        ChapterModel::setStatus((int) $chapter['id'], $action === 'publish' ? 'published' : 'draft');
        NovelModel::touch((int) $chapter['novel_id']);
        $this->flashSuccess($action === 'publish' ? 'Bab dipublikasikan.' : 'Bab disimpan sebagai draf.');
        Response::back('admin/bab');
    }

    public function chapterDestroy(Request $request, string $id): void
    {
        $chapter = ChapterModel::find((int) $id);
        if ($chapter === null) {
            abort(404);
        }

        ChapterModel::delete((int) $chapter['id']);
        NovelModel::touch((int) $chapter['novel_id']);
        $this->flashInfo('Bab dihapus.');
        Response::back('admin/bab');
    }

    public function genres(Request $request): void
    {
        $this->render('admin/genres', [
            'title' => 'Genre — Admin NovelVerse',
            'metaDescription' => 'Kelola daftar genre yang digunakan novel.',
            'genres' => CategoryModel::allWithCounts(false),
        ]);
    }

    public function storeGenre(Request $request): void
    {
        $name = $request->string('name');
        $description = $request->string('description');

        $validator = (new Validator(['name' => $name, 'description' => $description]))
            ->required('name', 'Nama genre')
            ->length('name', 'Nama genre', 2, 60)
            ->maxLength('description', 'Deskripsi', 255);

        $errors = $validator->errors();
        if (!isset($errors['name']) && CategoryModel::nameExists($name)) {
            $errors['name'] = 'Nama genre sudah ada.';
        }
        if ($errors !== []) {
            $this->flashValidation($errors, ['name' => $name, 'description' => $description]);
            Response::redirect('admin/genre');
        }

        CategoryModel::create($name, CategoryModel::uniqueSlug($name), $description !== '' ? $description : null);
        $this->flashSuccess('Genre baru ditambahkan.');
        Response::redirect('admin/genre');
    }

    public function updateGenre(Request $request, string $id): void
    {
        $genre = CategoryModel::find((int) $id);
        if ($genre === null) {
            abort(404);
        }

        $name = $request->string('name');
        $description = $request->string('description');
        $validator = (new Validator(['name' => $name, 'description' => $description]))
            ->required('name', 'Nama genre')
            ->length('name', 'Nama genre', 2, 60)
            ->maxLength('description', 'Deskripsi', 255);

        $errors = $validator->errors();
        if (!isset($errors['name']) && CategoryModel::nameExists($name, (int) $genre['id'])) {
            $errors['name'] = 'Nama genre sudah digunakan.';
        }
        if ($errors !== []) {
            $this->flashValidation($errors);
            Response::redirect('admin/genre');
        }

        CategoryModel::update(
            (int) $genre['id'],
            $name,
            CategoryModel::uniqueSlug($name, (int) $genre['id']),
            $description !== '' ? $description : null
        );
        $this->flashSuccess('Genre diperbarui.');
        Response::redirect('admin/genre');
    }

    public function destroyGenre(Request $request, string $id): void
    {
        $genre = CategoryModel::find((int) $id);
        if ($genre === null) {
            abort(404);
        }

        $statement = Database::execute(
            'DELETE FROM categories
             WHERE id = ? AND NOT EXISTS (SELECT 1 FROM novel_categories WHERE category_id = ?)',
            [(int) $genre['id'], (int) $genre['id']]
        );
        if ($statement->rowCount() === 0) {
            $this->flashError('Genre masih dipakai oleh novel. Pindahkan novel ke genre lain terlebih dahulu.');
        } else {
            $this->flashInfo('Genre dihapus.');
        }
        Response::redirect('admin/genre');
    }

    public function comments(Request $request): void
    {
        $query = mb_substr($request->string('q'), 0, 100);
        $status = $request->string('status');
        if (!in_array($status, ['visible', 'hidden'], true)) {
            $status = '';
        }
        $page = max(1, $request->int('page', 1));

        $this->render('admin/comments', [
            'title' => 'Komentar — Admin NovelVerse',
            'metaDescription' => 'Moderasi komentar pembaca NovelVerse.',
            'result' => CommentModel::adminList($query, $status, $page),
            'query' => $query,
            'status' => $status,
        ]);
    }

    public function commentStatus(Request $request, string $id): void
    {
        $comment = CommentModel::find((int) $id);
        if ($comment === null) {
            abort(404);
        }
        $action = $request->string('action');
        if (!in_array($action, ['hide', 'show'], true)) {
            abort(404);
        }

        CommentModel::setStatus((int) $comment['id'], $action === 'hide' ? 'hidden' : 'visible');
        $this->flashSuccess($action === 'hide' ? 'Komentar disembunyikan.' : 'Komentar ditampilkan kembali.');
        Response::back('admin/komentar');
    }

    public function commentDestroy(Request $request, string $id): void
    {
        $comment = CommentModel::find((int) $id);
        if ($comment === null) {
            abort(404);
        }
        CommentModel::delete((int) $comment['id']);
        $this->flashInfo('Komentar dihapus permanen.');
        Response::back('admin/komentar');
    }

    public function reports(Request $request): void
    {
        $status = $request->string('status', 'open');
        if (!in_array($status, ['open', 'resolved', 'dismissed', 'all'], true)) {
            $status = 'open';
        }
        $page = max(1, $request->int('page', 1));
        $filter = $status === 'all' ? '' : $status;

        $this->render('admin/reports', [
            'title' => 'Laporan — Admin NovelVerse',
            'metaDescription' => 'Tinjau laporan konten dan kelola moderasi.',
            'result' => ReportModel::list($filter, $page),
            'status' => $status,
            'counts' => [
                'open' => ReportModel::countByStatus('open'),
                'resolved' => ReportModel::countByStatus('resolved'),
                'dismissed' => ReportModel::countByStatus('dismissed'),
            ],
        ]);
    }

    public function resolveReport(Request $request, string $id): void
    {
        $report = ReportModel::find((int) $id);
        if ($report === null) {
            abort(404);
        }
        if ($report['status'] !== 'open') {
            $this->flashInfo('Laporan ini sudah ditinju.');
            Response::back('admin/laporan');
        }

        ReportModel::setStatus((int) $report['id'], 'resolved', (int) Auth::id());
        $this->flashSuccess('Laporan ditandai selesai.');
        Response::back('admin/laporan');
    }

    public function dismissReport(Request $request, string $id): void
    {
        $report = ReportModel::find((int) $id);
        if ($report === null) {
            abort(404);
        }
        if ($report['status'] !== 'open') {
            $this->flashInfo('Laporan ini sudah ditinju.');
            Response::back('admin/laporan');
        }

        ReportModel::setStatus((int) $report['id'], 'dismissed', (int) Auth::id());
        $this->flashInfo('Laporan ditolak tanpa tindakan.');
        Response::back('admin/laporan');
    }

    private function randomPassword(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }
        return $password;
    }
}
