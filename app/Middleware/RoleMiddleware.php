<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class RoleMiddleware
{
    /** @param array<int, string> $roles */
    public function __construct(private readonly array $roles)
    {
    }

    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Silakan masuk terlebih dahulu.');
            Response::redirect('masuk?redirect=' . rawurlencode($request->path()));
        }

        if (!in_array((string) Auth::role(), $this->roles, true)) {
            abort(403);
        }
    }
}
