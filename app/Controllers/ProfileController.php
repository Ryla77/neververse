<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\BookmarkModel;
use App\Models\PasswordResetModel;
use App\Models\ReadingProgressModel;
use App\Models\UserModel;

final class ProfileController extends Controller
{
    public function edit(Request $request): void
    {
        $userId = (int) Auth::id();

        $this->render('profile/edit', [
            'title' => 'Profil Saya — NovelVerse',
            'metaDescription' => 'Kelola informasi akun, kata sandi, dan lihat aktivitas membaca kamu.',
            'user' => UserModel::find($userId),
            'bookmarks' => BookmarkModel::listForUser($userId),
            'history' => ReadingProgressModel::history($userId, 8),
        ]);
    }

    public function update(Request $request): void
    {
        $userId = (int) Auth::id();
        $data = [
            'display_name' => $request->string('display_name'),
            'email' => mb_strtolower($request->string('email')),
            'bio' => $request->string('bio'),
        ];

        $validator = (new Validator($data))
            ->required('display_name', 'Nama tampilan')
            ->length('display_name', 'Nama tampilan', 3, 80)
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->maxLength('email', 'Email', 190)
            ->maxLength('bio', 'Bio', 500);

        $errors = $validator->errors();
        if (!isset($errors['email']) && UserModel::emailExists($data['email'], $userId)) {
            $errors['email'] = 'Email ini sudah digunakan akun lain.';
        }
        if ($errors !== []) {
            $this->flashValidation($errors, $data);
            Response::redirect('profil');
        }

        try {
            $current = UserModel::find($userId);
            UserModel::updateProfile($userId, $data['display_name'], $data['email'], $data['bio'] !== '' ? $data['bio'] : null);
            if ($current === null || mb_strtolower((string) $current['email']) !== $data['email']) {
                PasswordResetModel::deleteForUser($userId);
            }
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $this->flashValidation(['email' => 'Email ini sudah digunakan akun lain.'], $data);
                Response::redirect('profil');
            }
            throw $e;
        }

        Auth::refresh();
        $this->flashSuccess('Profil berhasil diperbarui.');
        Response::redirect('profil');
    }

    public function updatePassword(Request $request): void
    {
        $userId = (int) Auth::id();
        if (RateLimiter::tooMany('password-change', (string) $userId, 5, 900)) {
            $this->flashError('Terlalu banyak percobaan perubahan kata sandi. Coba lagi nanti.');
            Response::redirect('profil');
        }
        RateLimiter::hit('password-change', (string) $userId);

        $data = [
            'current_password' => $request->string('current_password'),
            'new_password' => $request->string('new_password'),
            'new_password_confirm' => $request->string('new_password_confirm'),
        ];

        $validator = (new Validator($data))
            ->required('current_password', 'Kata sandi saat ini')
            ->required('new_password', 'Kata sandi baru')
            ->length('new_password', 'Kata sandi baru', 8, 72)
            ->required('new_password_confirm', 'Konfirmasi kata sandi baru')
            ->same('new_password_confirm', 'Konfirmasi kata sandi baru', 'new_password');

        if ($validator->fails()) {
            $this->flashValidation($validator->errors());
            Response::redirect('profil#keamanan');
        }

        $user = UserModel::find($userId);
        if ($user === null || !password_verify($data['current_password'], (string) $user['password_hash'])) {
            Session::flash('_old', []);
            $this->flashError('Kata sandi saat ini tidak cocok.');
            Response::redirect('profil#keamanan');
        }

        UserModel::updatePassword($userId, password_hash($data['new_password'], PASSWORD_DEFAULT));
        PasswordResetModel::deleteForUser($userId);
        Session::regenerate();
        Auth::syncFingerprint();
        Csrf::rotate();
        $this->flashSuccess('Kata sandi berhasil diperbarui.');
        Response::redirect('profil#keamanan');
    }

    public function becomeAuthor(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::redirect('masuk');
        }
        if ($user['role'] !== 'reader') {
            $this->flashInfo('Akunmu sudah memiliki akses menulis.');
            Response::redirect('profil');
        }

        UserModel::setRole((int) $user['id'], 'author');
        Auth::refresh();
        $this->flashSuccess('Akses penulis aktif. Kamu sekarang dapat membuat novel dan bab.');
        Response::redirect('tulis');
    }
}
