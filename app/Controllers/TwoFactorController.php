<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\UserModel;
use App\Services\TwoFactorService;

final class TwoFactorController extends Controller
{
    private const PENDING_KEY = 'two_factor_pending';

    public function challengeForm(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        $pending = $this->pending();
        if ($pending === null) {
            $this->flashError('Sesi verifikasi dua langkah tidak ditemukan atau sudah berakhir. Silakan masuk kembali.');
            Response::redirect('masuk');
        }

        $this->render('auth/two-factor-challenge', [
            'title' => 'Verifikasi dua langkah — Luminara',
            'metaDescription' => 'Masukkan kode dari aplikasi autentikator untuk menyelesaikan proses masuk.',
            'email' => (string) $pending['email'],
        ], 'layouts/minimal');
    }

    public function challenge(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        $pending = $this->pending();
        if ($pending === null) {
            $this->flashError('Sesi verifikasi dua langkah tidak ditemukan atau sudah berakhir. Silakan masuk kembali.');
            Response::redirect('masuk');
        }

        $userId = (int) $pending['user_id'];
        if (RateLimiter::tooMany('two-factor', (string) $userId, 5, 900)
            || RateLimiter::tooMany('two-factor-ip', $request->ip(), 20, 900)) {
            $this->clearPending();
            $this->flashError('Terlalu banyak percobaan kode. Silakan masuk kembali untuk mencoba lagi.');
            Response::redirect('masuk');
        }

        $code = $request->string('code');
        $recoveryCode = $request->string('recovery_code');
        $valid = $code !== ''
            ? TwoFactorService::verifyTotp($userId, $code)
            : ($recoveryCode !== '' && TwoFactorService::verifyRecoveryCode($userId, $recoveryCode));

        if (!$valid) {
            RateLimiter::hit('two-factor', (string) $userId);
            RateLimiter::hit('two-factor-ip', $request->ip());
            $this->flashError('Kode tidak valid, sudah dipakai, atau kedaluwarsa. Periksa aplikasi autentikator Anda.');
            Response::redirect('masuk/2fa');
        }

        RateLimiter::clear('two-factor', (string) $userId);
        RateLimiter::clear('two-factor-ip', $request->ip());

        $user = UserModel::find($userId);
        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->clearPending();
            $this->flashError('Akun tidak dapat digunakan.');
            Response::redirect('masuk');
        }

        $redirect = (string) ($pending['redirect'] ?? '/');
        $this->clearPending();
        Auth::login($user);
        $this->flashSuccess('Verifikasi dua langkah berhasil. Selamat datang kembali, ' . $user['display_name'] . '.');
        Response::redirect($redirect);
    }

    public function setup(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::redirect('masuk');
        }

        $userId = (int) $user['id'];
        if (TwoFactorService::enabled($userId)) {
            $this->flashInfo('Verifikasi dua langkah sudah aktif pada akun Anda.');
            Response::redirect('profil#keamanan');
        }
        if (!TwoFactorService::available()) {
            $this->flashError('Verifikasi dua langkah belum dapat diaktifkan karena APP_KEY belum diset di .env.');
            Response::redirect('profil#keamanan');
        }

        $secret = TwoFactorService::pendingSecret() ?? TwoFactorService::startSetup();
        $secretLines = array_map(
            static fn (array $groups): string => implode(' ', $groups),
            array_chunk(str_split($secret, 4), 4)
        );

        $this->render('auth/two-factor-setup', [
            'title' => 'Aktifkan verifikasi dua langkah — Luminara',
            'metaDescription' => 'Tambahkan kunci rahasia ke aplikasi autentikator, lalu konfirmasi kode 6 digit.',
            'email' => (string) $user['email'],
            'secretLines' => $secretLines,
            'otpauthUri' => TwoFactorService::provisioningUri((string) $user['email'], $secret),
        ], 'layouts/minimal');
    }

    public function enable(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::redirect('masuk');
        }

        $userId = (int) $user['id'];
        if (TwoFactorService::enabled($userId)) {
            $this->flashInfo('Verifikasi dua langkah sudah aktif pada akun Anda.');
            Response::redirect('profil#keamanan');
        }
        if (!TwoFactorService::available()) {
            $this->flashError('Verifikasi dua langkah belum dapat diaktifkan karena APP_KEY belum diset di .env.');
            Response::redirect('profil#keamanan');
        }
        if (RateLimiter::tooMany('two-factor-setup', (string) $userId, 5, 900)) {
            $this->flashError('Terlalu banyak percobaan aktivasi. Tunggu 15 menit lalu coba lagi.');
            Response::redirect('profil#keamanan');
        }

        $secret = TwoFactorService::pendingSecret();
        if ($secret === null) {
            $this->flashError('Sesi aktivasi berakhir. Mulai lagi dari profil Anda.');
            Response::redirect('profil/2fa');
        }

        $codes = TwoFactorService::activate($userId, $secret, $request->string('code'));
        if ($codes === null) {
            RateLimiter::hit('two-factor-setup', (string) $userId);
            $this->flashError('Kode tidak cocok. Pastikan jam perangkat benar, lalu masukkan kode terbaru.');
            Response::redirect('profil/2fa');
        }

        RateLimiter::clear('two-factor-setup', (string) $userId);
        TwoFactorService::clearSetup();
        Session::flash('two_factor_recovery_codes', $codes);
        $this->flashSuccess('Verifikasi dua langkah aktif. Simpan kode pemulihan di tempat aman.');
        Response::redirect('profil#keamanan');
    }

    public function disable(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::redirect('masuk');
        }

        $userId = (int) $user['id'];
        if (!TwoFactorService::enabled($userId)) {
            $this->flashInfo('Verifikasi dua langkah belum aktif pada akun Anda.');
            Response::redirect('profil#keamanan');
        }
        if (RateLimiter::tooMany('two-factor-disable', (string) $userId, 5, 900)) {
            $this->flashError('Terlalu banyak percobaan. Tunggu 15 menit lalu coba lagi.');
            Response::redirect('profil#keamanan');
        }

        $password = $request->string('current_password');
        $validPassword = password_verify($password, (string) $user['password_hash']);
        $code = $request->string('code');
        $validCode = $validPassword
            && (TwoFactorService::verifyTotp($userId, $code) || TwoFactorService::verifyRecoveryCode($userId, $code));
        if (!$validPassword || !$validCode) {
            RateLimiter::hit('two-factor-disable', (string) $userId);
            $this->flashError('Kata sandi atau kode keamanan tidak cocok. Verifikasi dua langkah tetap aktif.');
            Response::redirect('profil#keamanan');
        }

        RateLimiter::clear('two-factor-disable', (string) $userId);
        TwoFactorService::disable($userId);
        $this->flashSuccess('Verifikasi dua langkah dinonaktifkan.');
        Response::redirect('profil#keamanan');
    }

    /**
     * @return array{user_id:int, email:string, redirect:string, started_at:int}|null
     */
    private function pending(): ?array
    {
        $pending = Session::get(self::PENDING_KEY);
        if (!is_array($pending) || !isset($pending['user_id'], $pending['started_at'])) {
            return null;
        }

        $ttl = (int) config('two_factor.pending_ttl_seconds', 600);
        if (time() - (int) $pending['started_at'] > $ttl) {
            $this->clearPending();
            return null;
        }

        return [
            'user_id' => (int) $pending['user_id'],
            'email' => (string) ($pending['email'] ?? ''),
            'redirect' => (string) ($pending['redirect'] ?? '/'),
            'started_at' => (int) $pending['started_at'],
        ];
    }

    private function clearPending(): void
    {
        Session::forget(self::PENDING_KEY);
    }
}
