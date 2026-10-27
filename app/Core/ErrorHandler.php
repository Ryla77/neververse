<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;
use Throwable;

final class ErrorHandler
{
    public static function handle(Throwable $e): void
    {
        $reference = strtoupper(bin2hex(random_bytes(4)));
        self::log($e, $reference);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, '[NovelVerse][' . $reference . '] ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code($e instanceof PDOException ? 503 : 500);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            if ($e instanceof PDOException) {
                View::render('errors/database', ['title' => 'Koneksi Database Bermasalah'], 'layouts/minimal');
            } else {
                View::render(
                    'errors/500',
                    ['title' => 'Terjadi Kesalahan', 'reference' => $reference],
                    'layouts/minimal'
                );
            }
        } catch (Throwable) {
            echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Terjadi Kesalahan</title>'
                . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
                . '<body style="font-family:system-ui,sans-serif;background:#0a0d1a;color:#e6e8f2;padding:3rem">'
                . '<h1>Terjadi kesalahan pada server</h1><p>Silakan coba lagi beberapa saat lagi.</p>'
                . '<p>Referensi: ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
        }
        exit;
    }

    private static function log(Throwable $e, string $reference): void
    {
        $directory = BASE_PATH . '/storage/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $userId = 'guest';
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $userId = (string) (int) $_SESSION['user_id'];
        }

        $lines = [
            '[' . date('Y-m-d H:i:s') . '] ref=' . $reference . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-')
                . ' user=' . $userId . ' uri=' . ($_SERVER['REQUEST_URI'] ?? '-'),
            get_class($e) . ': ' . $e->getMessage(),
            'at ' . $e->getFile() . ':' . $e->getLine(),
            $e->getTraceAsString(),
            str_repeat('-', 80),
        ];
        @file_put_contents($directory . '/app.log', implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
