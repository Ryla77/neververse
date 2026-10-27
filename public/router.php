<?php

declare(strict_types=1);

// Router untuk server pengembangan PHP:
// php -S 127.0.0.1:8080 -t public public/router.php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}
$decoded = rawurldecode($path);

if (str_contains($decoded, '..') || preg_match('#(^|/)\.#', $decoded)) {
    http_response_code(404);
    echo '404 Not Found';
    return true;
}

$root = realpath(__DIR__);
$file = realpath(__DIR__ . $path);
if ($file !== false && $root !== false && str_starts_with($file, $root) && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
return true;
