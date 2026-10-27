<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    protected function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::render($template, $data, $layout);
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function flashInfo(string $message): void
    {
        Session::flash('info', $message);
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    protected function flashValidation(array $errors, array $old = []): void
    {
        foreach (['password', 'password_confirm', 'current_password', 'new_password'] as $sensitive) {
            unset($old[$sensitive]);
        }
        Session::flash('errors', $errors);
        Session::flash('_old', $old);
        Session::flash('error', 'Periksa kembali data yang Anda masukkan.');
    }
}
