<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\ErrorHandler;
use App\Core\View;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
header('X-Permitted-Cross-Domain-Policies: none');

if (!is_file(BASE_PATH . '/.env')) {
    http_response_code(503);
    View::render('errors/setup', ['title' => 'Pemasangan Diperlukan'], 'layouts/minimal');
    exit;
}

try {
    (new App())->run();
} catch (Throwable $e) {
    ErrorHandler::handle($e);
}
