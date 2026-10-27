<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        $defaults = [
            403 => 'Akses ditolak.',
            404 => 'Halaman tidak ditemukan.',
            419 => 'Sesi formulir telah berakhir.',
        ];
        parent::__construct($message !== '' ? $message : ($defaults[$status] ?? 'Terjadi kesalahan.'));
    }
}
