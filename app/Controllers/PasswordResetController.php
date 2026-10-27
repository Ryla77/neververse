<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\PasswordResetModel;
use App\Models\UserModel;
use Throwable;

final class PasswordResetController extends Controller
{
    private const GENERIC_MESSAGE = 'Jika email tersebut terdaftar, kami telah mengirim tautan untuk mengatur ulang kata sandi. Periksa kotak masuk atau folder spam Anda.';

    public function requestForm(Request $request): void
    {
        $this->render('auth/forgot-password', [
            'title' => 'Lupa Kata Sandi — NovelVerse',
            'metaDescription' => 'Minta tautan reset kata sandi akun NovelVerse melalui email.',
        ], 'layouts/minimal');
    }

    public function send(Request $request): void
    {
        $email = mb_strtolower($request->string('email'));

        if (RateLimiter::tooMany('password-reset-ip', $request->ip(), 5, 900)
            || ($email !== '' && RateLimiter::tooMany('password-reset-email', $email, 3, 900))) {
            $this->flashError('Terlalu banyak permintaan reset kata sandi. Silakan coba lagi dalam 15 menit.');
            Response::redirect('lupa-sandi');
        }

        $validator = (new Validator(['email' => $email]))
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->maxLength('email', 'Email', 190);

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), ['email' => $email]);
            Response::redirect('lupa-sandi');
        }

        RateLimiter::hit('password-reset-ip', $request->ip());
        RateLimiter::hit('password-reset-email', $email);

        $this->deliverResetLink($email);

        $this->flashInfo(self::GENERIC_MESSAGE);
        Response::redirect('lupa-sandi');
    }

    public function resetForm(Request $request, string $token): void
    {
        $tokenHash = $this->hashToken($token);
        $reset = $tokenHash !== null ? PasswordResetModel::findValid($tokenHash) : null;

        $this->render('auth/reset-password', [
            'title' => 'Atur Kata Sandi Baru — NovelVerse',
            'metaDescription' => 'Buat kata sandi baru untuk akun NovelVerse Anda.',
            'token' => $tokenHash !== null ? $token : '',
            'valid' => $reset !== null,
            'expiresAt' => $reset['expires_at'] ?? null,
            'ttlMinutes' => (int) config('password_reset.ttl_minutes', 60),
        ], 'layouts/minimal');
    }

    public function reset(Request $request): void
    {
        $token = $request->string('token');
        $tokenHash = $this->hashToken($token);

        if ($tokenHash === null) {
            $this->flashError('Tautan reset kata sandi tidak valid atau sudah kedaluwarsa.');
            Response::redirect('lupa-sandi');
        }

        if (RateLimiter::tooMany('password-reset-confirm-ip', $request->ip(), 10, 900)) {
            $this->flashError('Terlalu banyak percobaan. Silakan minta tautan reset baru.');
            Response::redirect('lupa-sandi');
        }

        $reset = PasswordResetModel::findValid($tokenHash);
        if ($reset === null) {
            RateLimiter::hit('password-reset-confirm-ip', $request->ip());
            $this->flashError('Tautan reset kata sandi tidak valid, sudah digunakan, atau kedaluwarsa.');
            Response::redirect('lupa-sandi');
        }

        $data = [
            'password' => $request->string('password'),
            'password_confirm' => $request->string('password_confirm'),
        ];

        $validator = (new Validator($data))
            ->required('password', 'Kata sandi baru')
            ->length('password', 'Kata sandi baru', 8, 72)
            ->required('password_confirm', 'Konfirmasi kata sandi')
            ->same('password_confirm', 'Konfirmasi kata sandi', 'password');

        if ($validator->fails()) {
            $this->flashValidation($validator->errors());
            Response::redirect('reset-sandi/' . $token);
        }

        $userId = (int) $reset['user_id'];
        $updated = Database::transaction(static function () use ($reset, $userId, $data): bool {
            if (!PasswordResetModel::consume((int) $reset['id'])) {
                return false;
            }
            UserModel::updatePassword($userId, password_hash($data['password'], PASSWORD_DEFAULT));
            PasswordResetModel::deleteForUser($userId);
            return true;
        });

        if (!$updated) {
            RateLimiter::hit('password-reset-confirm-ip', $request->ip());
            $this->flashError('Tautan reset kata sandi sudah digunakan. Silakan minta tautan baru.');
            Response::redirect('lupa-sandi');
        }

        RateLimiter::clear('login', $reset['email'] . '|' . $request->ip());
        RateLimiter::clear('login-ip', $request->ip());
        $this->flashSuccess('Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.');
        Response::redirect('masuk');
    }

    private function deliverResetLink(string $email): void
    {
        $user = UserModel::findByEmail($email);
        if ($user === null || (int) $user['is_active'] !== 1) {
            return;
        }

        $ttl = (int) config('password_reset.ttl_minutes', 60);
        $token = bin2hex(random_bytes(32));
        PasswordResetModel::issue((int) $user['id'], hash('sha256', $token), $ttl);

        try {
            Mailer::sendPasswordReset(
                (string) $user['email'],
                (string) $user['display_name'],
                absolute_url('reset-sandi/' . $token),
                $ttl
            );
        } catch (Throwable $e) {
            error_log('[NovelVerse] Gagal mengirim email reset kata sandi: ' . $e->getMessage());
        }
    }

    private function hashToken(string $token): ?string
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        return hash('sha256', $token);
    }
}
