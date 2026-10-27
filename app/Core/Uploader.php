<?php

declare(strict_types=1);

namespace App\Core;

use finfo;
use RuntimeException;

final class Uploader
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Menyimpan gambar unggahan dengan nama acak dan validasi ketat.
     *
     * @param array{error?:int|array,size?:int,tmp_name?:string,name?:string} $file
     * @return string|null Path relatif terhadap folder public, atau null jika tidak ada file.
     */
    public static function image(array $file, string $folder = 'uploads/covers'): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (!is_int($error)) {
            throw new RuntimeException('Data berkas unggahan tidak valid.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'Ukuran berkas melebihi batas server.',
                UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas terlalu besar.',
                UPLOAD_ERR_PARTIAL => 'Berkas hanya terunggah sebagian.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak tersedia.',
                UPLOAD_ERR_CANT_WRITE => 'Berkas gagal disimpan.',
                UPLOAD_ERR_EXTENSION => 'Unggahan dihentikan oleh server.',
            ];
            throw new RuntimeException($messages[$error] ?? 'Berkas gagal diunggah.');
        }

        $tmp = $file['tmp_name'] ?? '';
        if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Berkas unggahan tidak valid.');
        }

        $maxKb = max(1, (int) config('uploads.max_kb', 2048));
        $size = @filesize($tmp);
        if ($size === false || $size <= 0) {
            throw new RuntimeException('Berkas kosong atau tidak valid.');
        }
        if ($size > $maxKb * 1024) {
            throw new RuntimeException('Ukuran gambar maksimal ' . $maxKb . ' KB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!is_string($mime) || !isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
        }

        $info = @getimagesize($tmp);
        $expectedType = [
            'image/jpeg' => IMAGETYPE_JPEG,
            'image/png' => IMAGETYPE_PNG,
            'image/webp' => IMAGETYPE_WEBP,
        ];
        if ($info === false || ($info[2] ?? null) !== $expectedType[$mime]) {
            throw new RuntimeException('Isi berkas tidak cocok dengan format gambar.');
        }
        if (($info[0] ?? 0) < 200 || ($info[1] ?? 0) < 200) {
            throw new RuntimeException('Resolusi gambar minimal 200 x 200 piksel.');
        }

        $folder = trim(str_replace('\\', '/', $folder), '/');
        if ($folder === '' || !preg_match('#^[a-zA-Z0-9_/-]+$#', $folder) || str_contains($folder, '..')) {
            throw new RuntimeException('Folder unggahan tidak valid.');
        }
        $directory = BASE_PATH . '/public/' . $folder;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder unggahan tidak dapat dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];
        $destination = $directory . '/' . $filename;
        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('Gambar gagal disimpan. Coba lagi.');
        }
        @chmod($destination, 0644);

        return $folder . '/' . $filename;
    }

    public static function delete(?string $relativePath): void
    {
        if ($relativePath === null) {
            return;
        }
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if (!str_starts_with($relativePath, 'uploads/') || str_contains($relativePath, '..')) {
            return;
        }
        $full = BASE_PATH . '/public/' . $relativePath;
        $real = realpath($full);
        $uploadsRoot = realpath(BASE_PATH . '/public/uploads');
        if ($real === false || $uploadsRoot === false) {
            return;
        }
        $uploadsPrefix = rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($real !== $uploadsRoot && !str_starts_with($real, $uploadsPrefix)) {
            return;
        }
        if (is_file($real)) {
            @unlink($real);
        }
    }
}
