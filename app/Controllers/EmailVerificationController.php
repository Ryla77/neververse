<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Models\EmailVerificationModel;
use App\Services\EmailVerificationService;

final class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $token): void
    {
        if (RateLimiter::tooMany('email-verification-ip', $request->ip(), 20, 900)) {
            $this->renderResult('throttled');
            return;
        }

        $tokenHash = preg_match('/^[a-f0-9]{64}$/', $token) === 1 ? hash('sha256', $token) : null;
        $pending = $tokenHash !== null ? EmailVerificationModel::findValid($tokenHash) : null;

        if ($pending === null
            || !EmailVerificationModel::markVerified((int) $pending['user_id'], (string) $tokenHash)) {
            RateLimiter::hit('email-verification-ip', $request->ip());
            $this->renderResult('invalid');
            return;
        }

        RateLimiter::clear('email-verification-ip', $request->ip());
        $this->renderResult('verified', (string) $pending['email']);
    }

    public function resend(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::redirect('masuk');
        }

        $redirect = $request->safeRedirectPath($request->input('redirect'), '/');

        if (email_verified($user)) {
            $this->flashInfo('Email Anda sudah terverifikasi.');
            Response::redirect($redirect);
        }

        $userId = (string) $user['id'];
        if (RateLimiter::tooMany('email-verification-resend', $userId, 3, 900)
            || RateLimiter::tooMany('email-verification-resend-ip', $request->ip(), 10, 3600)) {
            $this->flashError('Terlalu banyak permintaan email verifikasi. Coba lagi dalam 15 menit.');
            Response::redirect($redirect);
        }
        RateLimiter::hit('email-verification-resend', $userId);
        RateLimiter::hit('email-verification-resend-ip', $request->ip());

        if (!EmailVerificationService::sendForUser($user)) {
            $this->flashError('Email verifikasi gagal dikirim. Periksa konfigurasi email atau coba lagi nanti.');
            Response::redirect($redirect);
        }

        $this->flashSuccess('Tautan verifikasi telah dikirim ke ' . $user['email'] . '. Periksa kotak masuk atau folder spam.');
        Response::redirect($redirect);
    }

    private function renderResult(string $status, ?string $email = null): void
    {
        $this->render('auth/verify-email', [
            'title' => 'Verifikasi Email — NovelVerse',
            'metaDescription' => 'Konfirmasi alamat email akun NovelVerse Anda.',
            'status' => $status,
            'email' => $email,
            'loggedIn' => Auth::check(),
        ], 'layouts/minimal');
    }
}
