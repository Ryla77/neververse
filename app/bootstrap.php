<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_VERSION', '1.0.0');
define('APP_START', microtime(true));

require BASE_PATH . '/config/helpers.php';

load_env(BASE_PATH . '/.env');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Jakarta'));
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '0');

set_exception_handler(static function (Throwable $e): void {
    \App\Core\ErrorHandler::handle($e);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (in_array($error['type'], $fatal, true)) {
        \App\Core\ErrorHandler::handle(
            new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
        );
    }
});
