<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Mailer
{
    public static function sendPasswordReset(string $to, string $displayName, string $resetUrl, int $ttlMinutes): void
    {
        $name = trim(preg_replace('/[\r\n]+/u', ' ', $displayName) ?? '');
        if ($name === '') {
            $name = 'Pengguna';
        }
        $appName = (string) config('app.name', 'Luminara');
        $ttlMinutes = max(5, $ttlMinutes);

        $subject = 'Reset kata sandi ' . $appName;
        $body = "Halo {$name},\r\n\r\n"
            . "Kami menerima permintaan untuk mengatur ulang kata sandi akun {$appName} Anda.\r\n\r\n"
            . "Buka tautan berikut untuk membuat kata sandi baru. Tautan berlaku {$ttlMinutes} menit dan hanya dapat digunakan sekali:\r\n\r\n"
            . $resetUrl . "\r\n\r\n"
            . "Jika Anda tidak meminta reset kata sandi, abaikan email ini. Kata sandi Anda tidak akan berubah.\r\n\r\n"
            . "Salam,\r\n{$appName}\r\n";

        self::send($to, $subject, $body);
    }

    public static function sendEmailVerification(string $to, string $displayName, string $verifyUrl, int $ttlMinutes): void
    {
        $name = trim(preg_replace('/[\r\n]+/u', ' ', $displayName) ?? '');
        if ($name === '') {
            $name = 'Pengguna';
        }
        $appName = (string) config('app.name', 'Luminara');
        $ttlMinutes = max(5, $ttlMinutes);
        $duration = $ttlMinutes % 60 === 0
            ? intdiv($ttlMinutes, 60) . ' jam'
            : $ttlMinutes . ' menit';

        $subject = 'Verifikasi email ' . $appName;
        $body = "Halo {$name},\r\n\r\n"
            . "Terima kasih telah mendaftar di {$appName}. Konfirmasi alamat email Anda dengan membuka tautan berikut. Tautan berlaku {$duration} dan hanya dapat digunakan sekali:\r\n\r\n"
            . $verifyUrl . "\r\n\r\n"
            . "Setelah email terverifikasi, Anda dapat berkomentar, memberi rating, dan mengaktifkan akses penulis.\r\n\r\n"
            . "Jika Anda tidak membuat akun ini, abaikan email ini.\r\n\r\n"
            . "Salam,\r\n{$appName}\r\n";

        self::send($to, $subject, $body);
    }

    public static function send(string $to, string $subject, string $body): void
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Alamat email tujuan tidak valid.');
        }

        $fromAddress = trim((string) config('mail.from_address', 'no-reply@novelverse.local'));
        if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Alamat pengirim email tidak valid.');
        }
        $fromName = trim((string) config('mail.from_name', 'Luminara'));
        $fromName = trim(preg_replace('/[\r\n]+/u', ' ', $fromName) ?? '');
        if ($fromName === '') {
            $fromName = 'Luminara';
        }

        $headerBlock = [
            'Date: ' . date(DATE_RFC2822),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . self::messageIdDomain($fromAddress) . '>',
            'From: ' . self::encodeHeader($fromName) . ' <' . $fromAddress . '>',
            'To: ' . $to,
            'Subject: ' . self::encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $body = preg_replace('/\r\n|\r|\n/', "\r\n", $body) ?? $body;
        if (!self::endsWith($body, "\r\n")) {
            $body .= "\r\n";
        }

        $transport = strtolower(trim((string) config('mail.transport', 'mail')));

        if ($transport === 'file') {
            $raw = implode("\r\n", array_merge($headerBlock, [
                'Content-Transfer-Encoding: 8bit',
                'X-Mailer: Luminara',
            ])) . "\r\n\r\n" . $body;
            self::writeToDisk($raw);
            return;
        }

        if ($transport === 'smtp') {
            $raw = implode("\r\n", array_merge($headerBlock, [
                'Content-Transfer-Encoding: quoted-printable',
                'X-Mailer: Luminara',
            ])) . "\r\n\r\n" . quoted_printable_encode(rtrim($body, "\r\n")) . "\r\n";
            (new SmtpTransport((array) config('mail.smtp', [])))->send($fromAddress, $to, $raw);
            return;
        }

        if ($transport !== 'mail') {
            throw new RuntimeException('Transport email tidak dikenal: ' . $transport);
        }

        $mailHeaders = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . self::encodeHeader($fromName) . ' <' . $fromAddress . '>',
            'X-Mailer: Luminara',
        ];
        if (!function_exists('mail')) {
            throw new RuntimeException('Fungsi email server tidak tersedia.');
        }
        if (!@mail($to, self::encodeHeader($subject), $body, implode("\r\n", $mailHeaders))) {
            throw new RuntimeException('Email gagal dikirim oleh server.');
        }
    }

    private static function writeToDisk(string $content): void
    {
        $directory = (string) config('mail.path', BASE_PATH . '/storage/mail');
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder kotak surat lokal tidak dapat dibuat.');
        }

        foreach (glob($directory . '/*.eml') ?: [] as $old) {
            if (is_file($old) && filemtime($old) < time() - 604800) {
                @unlink($old);
            }
        }

        $file = $directory . '/' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.eml';
        if (file_put_contents($file, $content, LOCK_EX) === false) {
            throw new RuntimeException('Email gagal ditulis ke kotak surat lokal.');
        }
        @chmod($file, 0600);
    }

    private static function messageIdDomain(string $fromAddress): string
    {
        $domain = strrchr($fromAddress, '@');
        $domain = $domain !== false ? substr($domain, 1) : '';
        $domain = preg_replace('/[^A-Za-z0-9.\-]/', '', $domain) ?? '';

        return $domain !== '' ? $domain : 'novelverse.local';
    }

    private static function encodeHeader(string $value): string
    {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    private static function endsWith(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
