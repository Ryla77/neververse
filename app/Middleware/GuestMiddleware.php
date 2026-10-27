<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class GuestMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            return;
        }
        Session::flash('info', 'Anda sudah masuk sebagai ' . (Auth::user()['display_name'] ?? 'pengguna') . '.');
        Response::redirect('/');
    }
}
