<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mailer;
use App\Models\EmailVerificationModel;
use Throwable;

final class EmailVerificationService
{
    public static function sendForUser(array $user): bool
    {
        $ttl = (int) config('email_verification.ttl_minutes', 1440);
        $token = bin2hex(random_bytes(32));
        EmailVerificationModel::issue((int) $user['id'], hash('sha256', $token), $ttl);

        try {
            Mailer::sendEmailVerification(
                (string) $user['email'],
                (string) $user['display_name'],
                absolute_url('verifikasi-email/' . $token),
                $ttl
            );

            return true;
        } catch (Throwable $e) {
            error_log('[Luminara] Gagal mengirim email verifikasi: ' . $e->getMessage());

            return false;
        }
    }
}
