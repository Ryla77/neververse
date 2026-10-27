<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $content = self::capture($template, $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = self::path($layout);
        $data['content'] = $content;
        echo self::captureFile($layoutFile, $data);
    }

    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        return self::captureFile(self::path($template), $data);
    }

    private static function captureFile(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new RuntimeException('Template tidak ditemukan: ' . basename($file));
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    private static function path(string $template): string
    {
        return BASE_PATH . '/resources/views/' . str_replace(['..', '\\'], ['', '/'], $template) . '.php';
    }
}
