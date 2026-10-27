<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RoleMiddleware;
use RuntimeException;

final class App
{
    public function run(): void
    {
        Session::start();

        $request = new Request();
        $router = new Router();
        $register = require BASE_PATH . '/routes/web.php';
        $register($router);

        try {
            [$handler, $params, $middleware] = $router->match($request->method(), $request->path());

            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $token = $request->input('_token');
                if (!is_string($token) || $token === '') {
                    $token = $request->header('X-CSRF-Token');
                }
                if (!Csrf::verify(is_string($token) ? $token : null)) {
                    if ($request->wantsJson()) {
                        Response::json(['ok' => false, 'message' => 'Sesi formulir berakhir. Muat ulang halaman.'], 419);
                    }
                    throw new HttpException(419);
                }
            }

            $this->runMiddleware($middleware, $request);

            [$class, $method] = $handler;
            if (!class_exists($class)) {
                throw new RuntimeException('Controller tidak ditemukan: ' . $class);
            }
            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new RuntimeException('Aksi controller tidak ditemukan: ' . $method);
            }
            $controller->{$method}($request, ...array_values($params));
        } catch (HttpException $e) {
            $this->renderError($e->status);
        }
    }

    private function runMiddleware(array $middleware, Request $request): void
    {
        foreach ($middleware as $name) {
            if ($name === 'auth') {
                (new AuthMiddleware())->handle($request);
                continue;
            }
            if ($name === 'guest') {
                (new GuestMiddleware())->handle($request);
                continue;
            }
            if (str_starts_with($name, 'role:')) {
                $roles = array_values(array_filter(explode('|', substr($name, 5))));
                (new RoleMiddleware($roles))->handle($request);
            }
        }
    }

    private function renderError(int $status): void
    {
        http_response_code($status);
        $titles = [
            403 => 'Akses Ditolak',
            404 => 'Halaman Tidak Ditemukan',
            419 => 'Sesi Formulir Berakhir',
        ];
        $messages = [
            403 => 'Anda tidak memiliki izin untuk membuka halaman atau melakukan tindakan ini.',
            404 => 'Halaman yang Anda cari tidak tersedia, telah dipindahkan, atau belum dipublikasikan.',
            419 => 'Halaman terlalu lama dibuka. Silakan kembali dan minta formulir baru.',
        ];
        View::render('errors/http', [
            'title' => $titles[$status] ?? 'Terjadi Kesalahan',
            'status' => $status,
            'message' => $messages[$status] ?? 'Permintaan tidak dapat diproses.',
        ], 'layouts/minimal');
    }
}
