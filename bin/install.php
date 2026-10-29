<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Instalasi hanya dapat dijalankan melalui terminal.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$options = getopt('', ['db-root-user:', 'db-root-password:', 'admin-email:', 'force', 'help']);

if (isset($options['help'])) {
    echo <<<TXT
Luminara Installer

Penggunaan:
  php bin/install.php [opsi]

Opsi:
  --db-root-user=USER      User MySQL dengan hak membuat database (default: root)
  --db-root-password=PASS  Password user MySQL tersebut (default: kosong)
  --admin-email=EMAIL      Email akun admin pertama (default: admin@novelverse.local)
  --force                  Pasang ulang. PERINGATAN: database novelverse akan dihapus.
  --help                   Tampilkan bantuan ini.

Setelah selesai, kredensial akun demo disimpan di storage/install-credentials.txt.
Hapus berkas itu setelah Anda mencatat kata sandinya.
TXT;
    echo PHP_EOL;
    exit(0);
}

$rootUser = isset($options['db-root-user']) ? (string) $options['db-root-user'] : (string) env('DB_ROOT_USER', 'root');
$rootPass = isset($options['db-root-password']) ? (string) $options['db-root-password'] : (string) env('DB_ROOT_PASS', '');
$adminEmail = isset($options['admin-email']) ? strtolower(trim((string) $options['admin-email'])) : 'admin@novelverse.local';
$force = isset($options['force']);

if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Email admin tidak valid.\n");
    exit(1);
}

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (int) env('DB_PORT', 3306);
$dbName = (string) env('DB_NAME', 'novelverse');
$dbUser = (string) env('DB_USER', 'novelverse');

foreach (['nama database' => $dbName, 'user database' => $dbUser] as $label => $value) {
    if (!preg_match('/^[A-Za-z0-9_]{1,48}$/', $value)) {
        fwrite(STDERR, "Nilai {$label} tidak valid. Gunakan huruf, angka, dan garis bawah.\n");
        exit(1);
    }
}

echo "== Instalasi Luminara ==\n\n";
echo "Host database : {$host}:{$port}\n";
echo "Nama database : {$dbName}\n";
echo "User aplikasi : {$dbUser}\n\n";

$envPath = BASE_PATH . '/.env';
if (is_file($envPath) && !$force) {
    fwrite(STDERR, "File .env sudah ada. Jika ingin memasang ulang, jalankan dengan --force (data database akan dihapus).\n");
    exit(1);
}

function random_password(int $length = 16): string
{
    $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $max)];
    }
    return $password;
}

function split_sql(string $sql): array
{
    $statements = [];
    $current = '';
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
                $current .= $char;
            }
            continue;
        }
        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }
        if ($inSingle) {
            $current .= $char;
            if ($char === '\\' && $next !== '') {
                $current .= $next;
                $i++;
                continue;
            }
            if ($char === "'") {
                if ($next === "'") {
                    $current .= $next;
                    $i++;
                    continue;
                }
                $inSingle = false;
            }
            continue;
        }
        if ($inDouble) {
            $current .= $char;
            if ($char === '\\' && $next !== '') {
                $current .= $next;
                $i++;
                continue;
            }
            if ($char === '"') {
                if ($next === '"') {
                    $current .= $next;
                    $i++;
                    continue;
                }
                $inDouble = false;
            }
            continue;
        }
        if ($inBacktick) {
            $current .= $char;
            if ($char === '`') {
                $inBacktick = false;
            }
            continue;
        }

        if (($char === '-' && $next === '-') || $char === '#') {
            $inLineComment = true;
            if ($char === '-') {
                $i++;
            }
            continue;
        }
        if ($char === '/' && $next === '*') {
            $inBlockComment = true;
            $i++;
            continue;
        }
        if ($char === "'") {
            $inSingle = true;
            $current .= $char;
            continue;
        }
        if ($char === '"') {
            $inDouble = true;
            $current .= $char;
            continue;
        }
        if ($char === '`') {
            $inBacktick = true;
            $current .= $char;
            continue;
        }
        if ($char === ';') {
            $statement = trim($current);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $current = '';
            continue;
        }
        $current .= $char;
    }

    $statement = trim($current);
    if ($statement !== '') {
        $statements[] = $statement;
    }
    return $statements;
}

function run_sql_file(PDO $pdo, string $path): int
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Berkas SQL tidak dapat dibaca: ' . basename($path));
    }
    $statements = split_sql($sql);
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Gagal menjalankan pernyataan SQL ke-' . ($index + 1) . ' dari ' . basename($path) . ': ' . $e->getMessage()
            );
        }
    }
    return count($statements);
}

function write_env(string $path, array $values): void
{
    $lines = [
        '# Dibuat otomatis oleh bin/install.php pada ' . date('Y-m-d H:i:s'),
        '# File ini berisi kredensial. Jangan dibagikan atau di-commit.',
        '',
        'APP_NAME="Luminara"',
        'APP_ENV=local',
        'APP_DEBUG=false',
        'APP_TIMEZONE=Asia/Jakarta',
        'APP_KEY="' . $values['app_key'] . '"',
        '',
        'DB_HOST=' . $values['host'],
        'DB_PORT=' . $values['port'],
        'DB_NAME=' . $values['name'],
        'DB_USER=' . $values['user'],
        'DB_PASS="' . $values['pass'] . '"',
        '',
        'DB_ROOT_USER=root',
        'DB_ROOT_PASS=',
        '',
        'UPLOAD_MAX_KB=2048',
        '',
    ];
    if (file_put_contents($path, implode(PHP_EOL, $lines)) === false) {
        throw new RuntimeException('File .env tidak dapat ditulis. Periksa izin folder proyek.');
    }
}

try {
    $root = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $rootUser,
        $rootPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $check = $root->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
    $check->execute([$dbName]);
    $exists = (int) $check->fetchColumn() > 0;

    if ($exists && !$force) {
        fwrite(STDERR, "Database {$dbName} sudah ada. Gunakan --force untuk memasang ulang (data akan dihapus).\n");
        exit(1);
    }
    if ($exists && $force) {
        echo "Menghapus database {$dbName} yang lama karena --force...\n";
        $root->exec('DROP DATABASE `' . $dbName . '`');
    }

    $root->exec('CREATE DATABASE `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $appPass = random_password(24);
    $quotedPass = $root->quote($appPass);
    foreach (['localhost', '127.0.0.1'] as $appHost) {
        $root->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'{$appHost}' IDENTIFIED BY {$quotedPass}");
        $root->exec("ALTER USER '{$dbUser}'@'{$appHost}' IDENTIFIED BY {$quotedPass}");
        $root->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$appHost}'");
    }
    $root->exec('FLUSH PRIVILEGES');

    write_env($envPath, [
        'host' => $host,
        'port' => $port,
        'name' => $dbName,
        'user' => $dbUser,
        'pass' => $appPass,
        'app_key' => base64_encode(random_bytes(32)),
    ]);
    echo "File .env dibuat.\n";

    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbName),
        $dbUser,
        $appPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $offset = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('P');
    $pdo->exec("SET time_zone = '{$offset}'");

    echo "Membuat tabel dari database/schema.sql...\n";
    $schemaCount = run_sql_file($pdo, BASE_PATH . '/database/schema.sql');
    echo "  {$schemaCount} pernyataan tabel dijalankan.\n";

    $accounts = [
        ['email' => $adminEmail, 'name' => 'Administrator', 'role' => 'admin', 'password' => random_password(20)],
        ['email' => 'rania@novelverse.local', 'name' => 'Rania Prameswari', 'role' => 'author', 'password' => random_password(14)],
        ['email' => 'aditya@novelverse.local', 'name' => 'Aditya Nurwenda', 'role' => 'author', 'password' => random_password(14)],
        ['email' => 'sekar@novelverse.local', 'name' => 'Sekar Ayu Lestari', 'role' => 'author', 'password' => random_password(14)],
        ['email' => 'bimo@novelverse.local', 'name' => 'Bimo Prasetyo', 'role' => 'author', 'password' => random_password(14)],
        ['email' => 'dinda@novelverse.local', 'name' => 'Dinda Puspita', 'role' => 'reader', 'password' => random_password(14)],
        ['email' => 'fajar@novelverse.local', 'name' => 'Fajar Ramadhan', 'role' => 'reader', 'password' => random_password(14)],
        ['email' => 'nadia@novelverse.local', 'name' => 'Nadia Aulia', 'role' => 'reader', 'password' => random_password(14)],
    ];

    $insert = $pdo->prepare(
        'INSERT INTO users (email, password_hash, display_name, role, is_active, created_at)
         VALUES (?, ?, ?, ?, 1, NOW())'
    );
    $markVerified = $pdo->prepare(
        'INSERT INTO email_verifications (user_id, verified_at, created_at) VALUES (?, NOW(), NOW())'
    );
    foreach ($accounts as $account) {
        $insert->execute([
            $account['email'],
            password_hash($account['password'], PASSWORD_DEFAULT),
            $account['name'],
            $account['role'],
        ]);
        $markVerified->execute([(int) $pdo->lastInsertId()]);
    }
    echo "Akun admin dan akun demo dibuat.\n";

    echo "Mengimpor cerita contoh dari database/seed.sql...\n";
    $pdo->beginTransaction();
    try {
        $seedCount = run_sql_file($pdo, BASE_PATH . '/database/seed.sql');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    echo "  {$seedCount} pernyataan data contoh dijalankan.\n";

    $lines = [
        'KREDENSIAL NOVELVERSE',
        'Dibuat: ' . date('Y-m-d H:i:s'),
        '',
        'PENTING: Simpan kata sandi ini di tempat aman, lalu hapus berkas ini.',
        'Semua akun memakai kata sandi acak yang dibuat saat instalasi.',
        '',
        'PERAN   | EMAIL                     | KATA SANDI',
        str_repeat('-', 70),
    ];
    $roleLabels = ['admin' => 'ADMIN', 'author' => 'PENULIS', 'reader' => 'PEMBACA'];
    foreach ($accounts as $account) {
        $lines[] = str_pad($roleLabels[$account['role']], 7) . ' | '
            . str_pad($account['email'], 25) . ' | ' . $account['password'];
    }
    $lines[] = '';
    $lines[] = 'Dashboard admin: /admin';
    $lines[] = 'Dashboard penulis: /tulis';
    $lines[] = '';
    $credentialsPath = BASE_PATH . '/storage/install-credentials.txt';
    if (file_put_contents($credentialsPath, implode(PHP_EOL, $lines) . PHP_EOL) === false) {
        throw new RuntimeException('Kredensial tidak dapat disimpan ke storage/install-credentials.txt');
    }
    @chmod($credentialsPath, 0600);
    file_put_contents(BASE_PATH . '/storage/.installed.lock', date('c'));

    echo "\nInstalasi selesai.\n\n";
    echo "Akun dibuat (kata sandi acak, tersimpan di storage/install-credentials.txt):\n";
    foreach ($accounts as $account) {
        echo '  - ' . $roleLabels[$account['role']] . ' ' . $account['email'] . ' / ' . $account['password'] . PHP_EOL;
    }
    echo "\nLangkah berikutnya:\n";
    echo "  1. Jalankan server: php -S 127.0.0.1:8080 -t public public/router.php\n";
    echo "  2. Buka http://127.0.0.1:8080\n";
    echo "  3. Masuk sebagai admin melalui /masuk, lalu buka /admin\n";
    echo "  4. Hapus storage/install-credentials.txt setelah mencatat kata sandi.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nInstalasi gagal: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Pastikan MySQL XAMPP berjalan dan kredensial root benar.\n");
    exit(1);
}
