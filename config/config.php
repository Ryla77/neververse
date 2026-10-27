<?php

declare(strict_types=1);

$smtpEncryption = strtolower(trim((string) env('MAIL_ENCRYPTION', 'tls')));
$smtpPort = (int) env('MAIL_PORT', $smtpEncryption === 'ssl' ? 465 : 587);

return [
    'app' => [
        'name' => (string) env('APP_NAME', 'NovelVerse'),
        'env' => (string) env('APP_ENV', 'production'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
        'timezone' => (string) env('APP_TIMEZONE', 'Asia/Jakarta'),
    ],
    'db' => [
        'host' => (string) env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'name' => (string) env('DB_NAME', 'novelverse'),
        'user' => (string) env('DB_USER', 'novelverse'),
        'pass' => (string) env('DB_PASS', ''),
    ],
    'uploads' => [
        'max_kb' => (int) env('UPLOAD_MAX_KB', 2048),
        'covers_dir' => BASE_PATH . '/public/uploads/covers',
        'covers_relative' => 'uploads/covers',
    ],
    'mail' => [
        'transport' => (string) env(
            'MAIL_TRANSPORT',
            env('APP_ENV', 'production') === 'local' ? 'file' : 'mail'
        ),
        'from_address' => (string) env('MAIL_FROM_ADDRESS', 'no-reply@novelverse.local'),
        'from_name' => (string) env('MAIL_FROM_NAME', env('APP_NAME', 'NovelVerse')),
        'path' => BASE_PATH . '/storage/mail',
        'smtp' => [
            'host' => (string) env('MAIL_HOST', 'localhost'),
            'port' => $smtpPort > 0 ? $smtpPort : ($smtpEncryption === 'ssl' ? 465 : 587),
            'encryption' => $smtpEncryption,
            'username' => (string) env('MAIL_USERNAME', ''),
            'password' => (string) env('MAIL_PASSWORD', ''),
            'timeout' => (int) env('MAIL_TIMEOUT', 15),
            'helo' => (string) env('MAIL_HELO', 'localhost'),
            'cafile' => (string) env('MAIL_CAFILE', ''),
        ],
    ],
    'password_reset' => [
        'ttl_minutes' => max(5, (int) env('PASSWORD_RESET_TTL', 60)),
    ],
    'email_verification' => [
        'ttl_minutes' => max(5, (int) env('EMAIL_VERIFICATION_TTL', 1440)),
    ],
];
