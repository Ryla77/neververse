<?php

declare(strict_types=1);

/**
 * Pengirim email CLI untuk pengujian transport SMTP.
 * Konfigurasi diambil dari environment proses (MAIL_* dan SMTP_TO).
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Mailer;

$to = (string) (getenv('SMTP_TO') ?: 'penerima@example.test');
$subject = (string) (getenv('SMTP_SUBJECT') ?: 'Uji SMTP Luminara');
$body = (string) (getenv('SMTP_BODY') ?: "Halo,\r\n\r\nIni pesan uji transport SMTP Luminara.\r\n");

try {
    Mailer::send($to, $subject, $body);
    fwrite(STDOUT, "SENT\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
