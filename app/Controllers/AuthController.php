<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\UserModel;
use App\Services\EmailVerificationService;
use App\Services\TwoFactorService;

final class AuthController extends Controller
{
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function loginForm(Request $request): void
    {
        $this->render('auth/login', [
            'title' => 'Masuk — NovelVerse',
            'metaDescription' => 'Masuk ke akun NovelVerse untuk menyimpan perpustakaan dan progres membaca.',
            'redirect' => $request->safeRedirectPath($request->input('redirect'), '/'),
        ], 'layouts/minimal');
    }

    public function login(Request $request): void
    {
        $email = mb_strtolower($request->string('email'));
        $password = $request->string('password');
        $redirect = $request->safeRedirectPath($request->input('redirect'), '/');

        $identifier = $email . '|' . $request->ip();
        if (RateLimiter::tooMany('login', $identifier, 5, 900)
            || RateLimiter::tooMany('login-ip', $request->ip(), 20, 900)) {
            $this->flashError('Terlalu banyak percobaan masuk. Silakan coba lagi dalam 15 menit.');
            Response::redirect('masuk?redirect=' . rawurlencode($redirect));
        }

        $validator = (new Validator(['email' => $email, 'password' => $password]))
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Kata sandi');

        if ($validator->fails()) {
            RateLimiter::hit('login', $identifier);
            RateLimiter::hit('login-ip', $request->ip());
            $this->flashValidation($validator->errors(), ['email' => $email, 'redirect' => $redirect]);
            Response::redirect('masuk?redirect=' . rawurlencode($redirect));
        }

        $user = UserModel::findByEmail($email);
        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        $validPassword = password_verify($password, $hash);

        if ($user === null || !$validPassword || (int) $user['is_active'] !== 1) {
            RateLimiter::hit('login', $identifier);
            RateLimiter::hit('login-ip', $request->ip());
            Session::flash('_old', ['email' => $email, 'redirect' => $redirect]);
            $this->flashError('Email atau kata sandi salah, atau akun tidak dapat digunakan.');
            Response::redirect('masuk?redirect=' . rawurlencode($redirect));
        }

        RateLimiter::clear('login', $identifier);
        RateLimiter::clear('login-ip', $request->ip());

        if (TwoFactorService::enabled((int) $user['id'])) {
            Session::regenerate();
            Session::set('two_factor_pending', [
                'user_id' => (int) $user['id'],
                'email' => (string) $user['email'],
                'redirect' => $redirect,
                'started_at' => time(),
            ]);
            Response::redirect('masuk/2fa');
        }

        Auth::login($user);
        $this->flashSuccess('Selamat datang kembali, ' . $user['display_name'] . '.');
        Response::redirect($redirect);
    }

    public function registerForm(Request $request): void
    {
        $this->render('auth/register', [
            'title' => 'Daftar — NovelVerse',
            'metaDescription' => 'Buat akun NovelVerse gratis dan mulai bangun perpustakaan ceritamu.',
        ], 'layouts/minimal');
    }

    public function register(Request $request): void
    {
        $data = [
            'display_name' => $request->string('display_name'),
            'email' => mb_strtolower($request->string('email')),
            'password' => $request->string('password'),
            'password_confirm' => $request->string('password_confirm'),
        ];

        if (RateLimiter::tooMany('register-ip', $request->ip(), 10, 3600)) {
            $this->flashError('Terlalu banyak percobaan pendaftaran dari jaringan ini. Coba lagi nanti.');
            Response::redirect('daftar');
        }
        RateLimiter::hit('register-ip', $request->ip());

        $validator = (new Validator($data))
            ->required('display_name', 'Nama tampilan')
            ->length('display_name', 'Nama tampilan', 3, 80)
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->maxLength('email', 'Email', 190)
            ->required('password', 'Kata sandi')
            ->length('password', 'Kata sandi', 8, 72)
            ->required('password_confirm', 'Konfirmasi kata sandi')
            ->same('password_confirm', 'Konfirmasi kata sandi', 'password');

        $errors = $validator->errors();
        if (!isset($errors['email']) && UserModel::emailExists($data['email'])) {
            $errors['email'] = 'Email ini sudah terdaftar. Gunakan email lain atau masuk.';
        }

        if ($errors !== []) {
            $this->flashValidation($errors, [
                'display_name' => $data['display_name'],
                'email' => $data['email'],
            ]);
            Response::redirect('daftar');
        }

        try {
            $userId = UserModel::create(
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['display_name'],
                'reader'
            );
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $this->flashValidation(
                    ['email' => 'Email ini sudah terdaftar. Gunakan email lain atau masuk.'],
                    ['display_name' => $data['display_name'], 'email' => $data['email']]
                );
                Response::redirect('daftar');
            }
            throw $e;
        }

        $user = UserModel::find($userId);
        if ($user === null) {
            $this->flashError('Akun gagal dibuat. Silakan coba lagi.');
            Response::redirect('daftar');
        }

        Auth::login($user);
        $verificationSent = EmailVerificationService::sendForUser($user);
        if ($verificationSent) {
            $this->flashSuccess('Akun berhasil dibuat. Kami mengirim tautan verifikasi ke ' . $user['email'] . '. Periksa kotak masuk atau folder spam.');
        } else {
            $this->flashInfo('Akun berhasil dibuat, tetapi email verifikasi belum terkirim. Gunakan tombol kirim ulang pada banner verifikasi.');
        }
        Response::redirect('/');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::redirect('/');
    }
}
