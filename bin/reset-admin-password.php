<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Perintah ini hanya dapat dijalankan melalui terminal.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\PasswordResetModel;
use App\Models\UserModel;

if (isset($argv[1]) && in_array($argv[1], ['-h', '--help'], true)) {
    echo <<<TXT
Reset Kata Sandi Admin Luminara

Penggunaan:
  php bin/reset-admin-password.php [email]

Argumen:
  email   Email akun admin (default: admin@novelverse.local)

Kata sandi baru dibuat acak dan ditampilkan satu kali. Kata sandi tidak
diterima sebagai argumen agar tidak tersimpan di riwayat shell. Sesi lama
otomatis tidak berlaku dan token reset yang tertunda dihapus. 2FA tidak
dinonaktifkan. Bila ada, baris ADMIN pada storage/install-credentials.txt
ikut diperbarui.
TXT;
    echo PHP_EOL;
    exit(0);
}

$email = strtolower(trim($argv[1] ?? 'admin@novelverse.local'));
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Email admin tidak valid.\n");
    exit(1);
}

$user = UserModel::findByEmail($email);
if ($user === null || $user['role'] !== 'admin') {
    fwrite(STDERR, "Akun admin dengan email {$email} tidak ditemukan.\n");
    $admins = Database::fetchAll("SELECT email, display_name FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id");
    if ($admins !== []) {
        fwrite(STDERR, "Admin aktif yang tersedia:\n");
        foreach ($admins as $admin) {
            fwrite(STDERR, '  - ' . $admin['email'] . ' (' . $admin['display_name'] . ")\n");
        }
    }
    exit(1);
}

if ((int) $user['is_active'] !== 1) {
    fwrite(STDERR, "Akun {$email} nonaktif. Aktifkan dulu dari dashboard admin.\n");
    exit(1);
}

$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
$password = '';
for ($i = 0; $i < 16; $i++) {
    $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}

UserModel::updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
PasswordResetModel::deleteForUser((int) $user['id']);

$synced = syncCredentialsFile($email, $password);

echo "Kata sandi admin direset.\n\n";
echo "Email        : {$email}\n";
echo "Sandi baru   : {$password}\n\n";
echo "Sesi lama otomatis tidak berlaku karena sidik jari sesi terikat pada hash kata sandi.\n";
echo "Ganti kata sandi ini melalui /profil setelah masuk. 2FA (jika aktif) tidak diubah.\n";
if ($synced) {
    echo "Baris ADMIN di storage/install-credentials.txt ikut diperbarui.\n";
} else {
    echo "storage/install-credentials.txt tidak diperbarui (tidak ada atau tidak cocok).\n";
}

function syncCredentialsFile(string $email, string $password): bool
{
    $path = dirname(__DIR__) . '/storage/install-credentials.txt';
    if (!is_file($path) || !is_readable($path) || !is_writable($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return false;
    }

    $changed = false;
    foreach ($lines as $index => $line) {
        if (preg_match('/^(ADMIN\s*\|\s*)(\S+)(\s*\|\s*)(\S+)\s*$/u', $line, $match) !== 1) {
            continue;
        }
        if (strcasecmp($match[2], $email) !== 0) {
            continue;
        }
        $lines[$index] = $match[1] . $match[2] . $match[3] . $password;
        $changed = true;
    }

    if (!$changed) {
        return false;
    }

    $content = implode(PHP_EOL, $lines) . PHP_EOL;
    if (file_put_contents($path, $content, LOCK_EX) === false) {
        return false;
    }
    @chmod($path, 0600);

    return true;
}
