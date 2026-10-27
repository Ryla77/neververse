<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (Auth::check()) {
            return;
        }
        Session::flash('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.');
        Response::redirect('masuk?redirect=' . rawurlencode($request->path()));
    }
}
