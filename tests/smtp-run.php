<?php

declare(strict_types=1);

/**
 * Uji end-to-end pengiriman email melalui SMTP.
 *
 * Server SMTP palsu (STARTTLS + AUTH) dan server aplikasi PHP dijalankan
 * otomatis oleh skrip ini. Tidak ada email yang keluar ke internet.
 *
 * Jalankan:
 *   php tests/smtp-run.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$root = BASE_PATH;
$tmp = __DIR__ . '/tmp';
if (!is_dir($tmp) && !mkdir($tmp, 0755, true) && !is_dir($tmp)) {
    fwrite(STDERR, "Tidak dapat membuat folder tests/tmp.\n");
    exit(1);
}

$workDir = $tmp . '/smtp-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
if (!mkdir($workDir, 0755, true) && !is_dir($workDir)) {
    fwrite(STDERR, "Tidak dapat membuat folder kerja SMTP.\n");
    exit(1);
}

$pass = 0;
$fail = 0;
$failures = [];
$fakes = [];
$appProcess = null;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $pass, $fail, $failures;
    if ($condition) {
        $pass++;
        echo "[PASS] {$name}\n";
        return;
    }
    $fail++;
    $failures[] = ['test' => $name, 'detail' => $detail];
    echo "[FAIL] {$name}" . ($detail !== '' ? ' — ' . $detail : '') . "\n";
}

function pathOf(?string $location): string
{
    if ($location === null || $location === '') {
        return '';
    }
    $path = parse_url($location, PHP_URL_PATH);

    return is_string($path) ? $path : '';
}

function freePort(): int
{
    $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($socket === false) {
        throw new RuntimeException('Tidak dapat mencari port kosong: ' . $errstr);
    }
    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);
    $position = strrpos($name, ':');

    return $position !== false ? (int) substr($name, $position + 1) : 0;
}

/**
 * @param array<string, string> $overrides
 * @return array<string, string>
 */
function processEnv(array $overrides): array
{
    $env = getenv();
    if (!is_array($env)) {
        $env = [];
    }
    foreach ($overrides as $key => $value) {
        $env[$key] = $value;
    }

    return $env;
}

/**
 * @param list<string> $command
 * @param array<string, string> $env
 * @return resource
 */
function startProcess(array $command, string $cwd, array $env, string $outFile, string $errFile)
{
    $descriptors = [
        0 => ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'r'],
        1 => ['file', $outFile, 'a'],
        2 => ['file', $errFile, 'a'],
    ];
    $options = PHP_OS_FAMILY === 'Windows' ? ['bypass_shell' => true] : [];

    $process = proc_open($command, $descriptors, $pipes, $cwd, $env, $options);
    if (!is_resource($process)) {
        throw new RuntimeException('Proses tidak dapat dijalankan: ' . $command[0]);
    }

    return $process;
}

function stopProcess($process): void
{
    if (!is_resource($process)) {
        return;
    }
    $status = proc_get_status($process);
    if (!empty($status['running'])) {
        proc_terminate($process);
        usleep(200000);
        $status = proc_get_status($process);
        if (!empty($status['running'])) {
            proc_terminate($process, 9);
        }
    }
    @proc_close($process);
}

function waitForPort(int $port, float $timeout = 10.0): bool
{
    $deadline = microtime(true) + $timeout;
    do {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $errstr, 0.3);
        if (is_resource($socket)) {
            fclose($socket);
            return true;
        }
        usleep(100000);
    } while (microtime(true) < $deadline);

    return false;
}

function waitForHttp(string $url, float $timeout = 10.0): bool
{
    $deadline = microtime(true) + $timeout;
    do {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
        ]);
        curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($status >= 200 && $status < 500) {
            return true;
        }
        usleep(100000);
    } while (microtime(true) < $deadline);

    return false;
}

function waitForFile(string $path, float $timeout = 5.0): bool
{
    $deadline = microtime(true) + $timeout;
    do {
        clearstatcache(true, $path);
        if (is_file($path)) {
            return true;
        }
        usleep(50000);
    } while (microtime(true) < $deadline);

    return false;
}

/**
 * @return list<string>
 */
function mailFiles(string $directory): array
{
    $files = glob($directory . '/mail-*.eml') ?: [];
    sort($files);

    return array_values($files);
}

/**
 * @return array{file:string, raw:string}
 */
function mailContaining(string $directory, string $needle, string $recipient, float $timeout = 8.0): array
{
    $deadline = microtime(true) + $timeout;
    do {
        foreach (mailFiles($directory) as $file) {
            $raw = (string) file_get_contents($file);
            if (str_contains($raw, $needle) && str_contains($raw, 'To: ' . $recipient)) {
                return ['file' => $file, 'raw' => $raw];
            }
        }
        usleep(100000);
    } while (microtime(true) < $deadline);

    return ['file' => '', 'raw' => ''];
}

/**
 * @return array{raw:string, headers:string, body:string}
 */
function parseMessage(string $file): array
{
    $raw = (string) file_get_contents($file);
    $parts = preg_split("/\r\n\r\n/", $raw, 2) ?: [];
    $headers = (string) ($parts[0] ?? '');
    $body = (string) ($parts[1] ?? '');

    return [
        'raw' => $raw,
        'headers' => $headers,
        'body' => quoted_printable_decode($body),
    ];
}

function removeTree(string $path): void
{
    if (!is_dir($path)) {
        @unlink($path);
        return;
    }
    $items = scandir($path) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $path . '/' . $item;
        if (is_dir($full)) {
            removeTree($full);
        } else {
            @unlink($full);
        }
    }
    @rmdir($path);
}

final class Http
{
    /** @var array<string, string> */
    private array $jars = [];

    /** @var array<string, string> */
    private array $tokens = [];

    public function __construct(private readonly string $base, private readonly string $tmp)
    {
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array{status:int, body:string, location:?string}
     */
    public function request(string $actor, string $method, string $path, ?array $data = null): array
    {
        $jar = $this->jar($actor);
        $url = preg_match('#^https?://#i', $path) === 1 ? $path : $this->base . '/' . ltrim($path, '/');

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_COOKIEJAR => $jar,
            CURLOPT_COOKIEFILE => $jar,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERAGENT => 'Luminara-SMTP-Test/1.0',
        ]);
        if ($data !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $location = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
        curl_close($curl);

        return [
            'status' => $status,
            'body' => $body === false ? '' : (string) $body,
            'location' => $location !== '' ? $location : null,
        ];
    }

    public function token(string $actor, string $path): string
    {
        $key = $actor . '|' . $path;
        if (isset($this->tokens[$key])) {
            return $this->tokens[$key];
        }
        $response = $this->request($actor, 'GET', $path);
        if (preg_match('/<meta name="csrf-token" content="([^"]+)">/', $response['body'], $match) === 1) {
            return $this->tokens[$key] = $match[1];
        }
        if (preg_match('/name="_token" value="([^"]+)"/', $response['body'], $match) === 1) {
            return $this->tokens[$key] = $match[1];
        }

        return '';
    }

    /**
     * @param array<string, mixed> $data
     * @return array{status:int, body:string, location:?string}
     */
    public function post(string $actor, string $path, array $data, string $tokenPath = '/'): array
    {
        $token = $this->token($actor, $tokenPath);
        if ($token !== '') {
            $data['_token'] = $token;
        }

        return $this->request($actor, 'POST', $path, $data);
    }

    private function jar(string $actor): string
    {
        if (!isset($this->jars[$actor])) {
            $file = $this->tmp . '/smtp-cookies-' . preg_replace('/[^a-z0-9_-]/i', '', $actor) . '.txt';
            if (!is_file($file)) {
                touch($file);
            }
            $this->jars[$actor] = $file;
        }

        return $this->jars[$actor];
    }
}

/**
 * @param array<string, string> $env
 * @return array{port:int, dir:string, log:string, process:resource}
 */
function startFake(
    string $root,
    string $workDir,
    string $cert,
    string $key,
    string $name,
    string $authMode,
    bool $startTls,
    string $smtpUser,
    string $smtpPass,
    bool $implicitTls = false
): array {
    $port = freePort();
    $dir = $workDir . '/' . $name;
    $mailDir = $dir . '/mail';
    if (!mkdir($mailDir, 0755, true) && !is_dir($mailDir)) {
        throw new RuntimeException('Folder tangkapan SMTP tidak dapat dibuat.');
    }
    $log = $dir . '/session.log';
    $ready = $dir . '/ready';
    touch($log);

    $command = [
        PHP_BINARY,
        $root . '/tests/fake-smtp.php',
        '--port=' . $port,
        '--cert=' . $cert,
        '--key=' . $key,
        '--dir=' . $mailDir,
        '--log=' . $log,
        '--ready=' . $ready,
        '--auth=' . $authMode,
    ];
    if (!$startTls) {
        $command[] = '--no-starttls';
    }
    if ($implicitTls) {
        $command[] = '--implicit-tls';
    }

    $process = startProcess(
        $command,
        $root,
        processEnv(['SMTP_TEST_USER' => $smtpUser, 'SMTP_TEST_PASS' => $smtpPass]),
        $dir . '/stdout.log',
        $dir . '/stderr.log'
    );

    if (!waitForFile($ready, 5.0) || !waitForPort($port, 5.0)) {
        stopProcess($process);
        throw new RuntimeException('Server SMTP uji gagal siap: ' . $name);
    }

    return ['port' => $port, 'dir' => $dir, 'log' => $log, 'process' => $process];
}

/**
 * @param array<string, string> $overrides
 * @return array{code:int, stdout:string, stderr:string}
 */
function runSend(string $root, string $workDir, string $name, array $overrides): array
{
    $out = $workDir . '/' . $name . '.out.log';
    $err = $workDir . '/' . $name . '.err.log';
    touch($out);
    touch($err);

    $process = startProcess(
        [PHP_BINARY, $root . '/tests/smtp-send.php'],
        $root,
        processEnv($overrides),
        $out,
        $err
    );
    $code = proc_close($process);

    return [
        'code' => $code,
        'stdout' => (string) file_get_contents($out),
        'stderr' => (string) file_get_contents($err),
    ];
}

/**
 * @return array<string, string>
 */
function smtpEnv(int $port, string $cert, string $smtpUser, string $smtpPass, string $encryption = 'tls'): array
{
    return [
        'MAIL_TRANSPORT' => 'smtp',
        'MAIL_HOST' => '127.0.0.1',
        'MAIL_PORT' => (string) $port,
        'MAIL_ENCRYPTION' => $encryption,
        'MAIL_USERNAME' => $smtpUser,
        'MAIL_PASSWORD' => $smtpPass,
        'MAIL_CAFILE' => $cert,
        'MAIL_FROM_ADDRESS' => 'no-reply@novelverse.test',
        'MAIL_FROM_NAME' => 'Luminara Test',
        'MAIL_TIMEOUT' => '5',
        'MAIL_HELO' => 'localhost',
        'SMTP_TO' => 'penerima@example.test',
        'SMTP_SUBJECT' => 'Uji transport SMTP',
        'SMTP_BODY' => "Halo,\r\n\r\n. Titik di awal baris harus utuh.\r\nBaris kedua pesan uji.\r\n",
        'APP_TIMEZONE' => 'Asia/Jakarta',
    ];
}

$smtpUser = 'smtp-uji@novelverse.test';
$smtpPass = 'UjiSmtp!' . bin2hex(random_bytes(12));
$suffix = date('ymdHis') . random_int(100, 999);
$testEmail = 'smtp.uji.' . $suffix . '@example.test';
$testDisplay = 'Pembaca SMTP ' . $suffix;
$initialPassword = 'RahasiaAwal!' . $suffix;
$newPassword = 'RahasiaBaru!' . $suffix;
$testUserId = null;
$appBase = '';

try {
    /* ----------------------------------------------------------------------
       Sertifikat TLS lokal untuk server SMTP palsu
       ---------------------------------------------------------------------- */

    $cert = $workDir . '/smtp-cert.pem';
    $key = $workDir . '/smtp-key.pem';
    $config = $workDir . '/openssl.cnf';
    file_put_contents($config, <<<CNF
[req]
distinguished_name = dn
x509_extensions = v3_req
prompt = no

[dn]
CN = localhost
O = Luminara Test

[v3_req]
subjectAltName = @alt_names
basicConstraints = critical,CA:TRUE
keyUsage = critical,digitalSignature,keyEncipherment,keyCertSign
extendedKeyUsage = serverAuth

[alt_names]
DNS.1 = localhost
IP.1 = 127.0.0.1
CNF
    );

    $openssl = 'openssl';
    if (is_file('C:\\xampp\\apache\\bin\\openssl.exe')) {
        $openssl = 'C:\\xampp\\apache\\bin\\openssl.exe';
    }
    $command = escapeshellarg($openssl)
        . ' req -x509 -newkey rsa:2048 -nodes -sha256 -days 2 -keyout ' . escapeshellarg($key)
        . ' -out ' . escapeshellarg($cert)
        . ' -config ' . escapeshellarg($config)
        . ' 2>&1';
    exec($command, $opensslOutput, $opensslCode);

    if ($opensslCode !== 0 || !is_file($cert) || !is_file($key)) {
        throw new RuntimeException('Sertifikat uji gagal dibuat: ' . implode(' ', array_slice($opensslOutput, -3)));
    }
    check('Sertifikat TLS lokal untuk pengujian tersedia', is_file($cert) && is_file($key));

    $storageMailBefore = count(glob($root . '/storage/mail/*.eml') ?: []);

    /* ----------------------------------------------------------------------
       Jalankan server SMTP palsu dan server aplikasi dengan MAIL_TRANSPORT=smtp
       ---------------------------------------------------------------------- */

    $fake = startFake($root, $workDir, $cert, $key, 'app-smtp', 'both', true, $smtpUser, $smtpPass);
    $fakes[] = $fake;

    $appPort = freePort();
    $appBase = 'http://127.0.0.1:' . $appPort;
    $appProcess = startProcess(
        [PHP_BINARY, '-S', '127.0.0.1:' . $appPort, '-t', 'public', 'public/router.php'],
        $root,
        processEnv([
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appBase,
            'MAIL_TRANSPORT' => 'smtp',
            'MAIL_HOST' => '127.0.0.1',
            'MAIL_PORT' => (string) $fake['port'],
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_USERNAME' => $smtpUser,
            'MAIL_PASSWORD' => $smtpPass,
            'MAIL_CAFILE' => $cert,
            'MAIL_FROM_ADDRESS' => 'no-reply@novelverse.test',
            'MAIL_FROM_NAME' => 'Luminara Test',
            'MAIL_TIMEOUT' => '10',
            'MAIL_HELO' => 'localhost',
            'PASSWORD_RESET_TTL' => '30',
        ]),
        $workDir . '/app-server.out.log',
        $workDir . '/app-server.err.log'
    );

    if (!waitForHttp($appBase . '/', 12.0)) {
        throw new RuntimeException('Server aplikasi uji tidak siap. Lihat app-server.err.log.');
    }
    check('Server aplikasi uji berjalan dengan konfigurasi SMTP', true);

    Database::execute('DELETE FROM rate_limits');

    /* ----------------------------------------------------------------------
       Alur nyata: registrasi -> lupa sandi -> email SMTP -> reset -> masuk
       ---------------------------------------------------------------------- */

    $http = new Http($appBase, $workDir);

    $register = $http->post('daftar', '/daftar', [
        'display_name' => $testDisplay,
        'email' => $testEmail,
        'password' => $initialPassword,
        'password_confirm' => $initialPassword,
    ], '/daftar');
    check(
        'Registrasi pengguna uji melalui server aplikasi berhasil',
        $register['status'] === 302 && pathOf($register['location']) === '/',
        'status=' . $register['status']
    );

    $row = Database::fetch('SELECT id FROM users WHERE email = ?', [$testEmail]);
    $testUserId = $row !== null ? (int) $row['id'] : null;

    $mailDir = $fake['dir'] . '/mail';
    $verificationMail = mailContaining($mailDir, '/verifikasi-email/', $testEmail);
    preg_match('#/verifikasi-email/([a-f0-9]{64})#', quoted_printable_decode($verificationMail['raw']), $verificationMatch);
    $verificationToken = $verificationMatch[1] ?? '';
    $verificationStored = $testUserId !== null
        ? Database::fetch('SELECT token_hash, verified_at FROM email_verifications WHERE user_id = ?', [$testUserId])
        : null;
    check(
        'Registrasi mengirim email verifikasi melalui SMTP',
        $verificationToken !== ''
            && $verificationStored !== null
            && $verificationStored['token_hash'] === hash('sha256', $verificationToken)
            && $verificationStored['verified_at'] === null
    );

    $verifyPage = $http->request('guest', 'GET', '/verifikasi-email/' . $verificationToken);
    $verificationAfter = $testUserId !== null
        ? Database::fetch('SELECT verified_at, token_hash FROM email_verifications WHERE user_id = ?', [$testUserId])
        : null;
    check(
        'Tautan verifikasi dari email SMTP menandai akun terverifikasi',
        $verifyPage['status'] === 200
            && str_contains($verifyPage['body'], 'Email berhasil diverifikasi')
            && $verificationAfter !== null
            && $verificationAfter['verified_at'] !== null
            && $verificationAfter['token_hash'] === null
    );

    $forgotPage = $http->request('guest', 'GET', '/lupa-sandi');
    check('Halaman lupa kata sandi dapat dimuat', $forgotPage['status'] === 200);

    $forgot = $http->post('guest', '/lupa-sandi', ['email' => $testEmail], '/lupa-sandi');
    check(
        'Permintaan reset diterima dengan pesan umum',
        $forgot['status'] === 302 && pathOf($forgot['location']) === '/lupa-sandi'
    );

    $resetMail = mailContaining($mailDir, '/reset-sandi/', $testEmail);
    $emailFile = $resetMail['file'];
    check('Server SMTP menerima email reset dari aplikasi', $emailFile !== '');

    $sessionLog = (string) file_get_contents($fake['log']);
    $tlsPosition = strpos($sessionLog, 'TLS-OK');
    $authPosition = strpos($sessionLog, 'AUTH PLAIN OK');
    $mailPosition = strpos($sessionLog, 'MAIL FROM');
    check(
        'Koneksi memakai STARTTLS lalu AUTH PLAIN sebelum MAIL FROM',
        $tlsPosition !== false && $authPosition !== false && $mailPosition !== false
            && $tlsPosition < $authPosition && $authPosition < $mailPosition,
        'urutan transkrip tidak sesuai'
    );

    $metadata = [];
    if ($emailFile !== '' && is_file($emailFile . '.json')) {
        $decodedMetadata = json_decode((string) file_get_contents($emailFile . '.json'), true);
        if (is_array($decodedMetadata)) {
            $metadata = $decodedMetadata;
        }
    }
    check(
        'Metadata SMTP mencatat TLS, autentikasi, dan mekanisme PLAIN',
        ($metadata['tls'] ?? false) === true
            && ($metadata['authenticated'] ?? false) === true
            && ($metadata['auth_mechanism'] ?? '') === 'PLAIN'
    );

    $parsed = $emailFile !== '' ? parseMessage($emailFile) : ['raw' => '', 'headers' => '', 'body' => ''];
    preg_match('#/reset-sandi/([a-f0-9]{64})#', $parsed['body'], $tokenMatch);
    $resetToken = $tokenMatch[1] ?? '';
    check(
        'Email memuat header standar dan tautan reset bertoken',
        $resetToken !== ''
            && str_contains($parsed['headers'], 'To: ' . $testEmail)
            && str_contains($parsed['headers'], 'Content-Type: text/plain')
            && str_contains($parsed['headers'], 'Content-Transfer-Encoding: quoted-printable')
            && preg_match('/Message-ID: <[a-f0-9]{32}@novelverse\.test>/', $parsed['headers']) === 1
            && str_contains($parsed['body'], $appBase . '/reset-sandi/')
    );

    $stored = $testUserId !== null
        ? Database::fetch('SELECT token_hash FROM password_resets WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$testUserId])
        : null;
    check(
        'Token reset dari email tetap disimpan sebagai hash',
        $resetToken !== '' && $stored !== null && $stored['token_hash'] === hash('sha256', $resetToken)
    );

    $resetForm = $http->request('guest', 'GET', '/reset-sandi/' . $resetToken);
    check(
        'Tautan email membuka formulir reset kata sandi',
        $resetForm['status'] === 200 && str_contains($resetForm['body'], 'Simpan kata sandi baru')
    );

    $reset = $http->post('guest', '/reset-sandi', [
        'token' => $resetToken,
        'password' => $newPassword,
        'password_confirm' => $newPassword,
    ], '/reset-sandi/' . $resetToken);
    check(
        'Kata sandi dapat diatur ulang melalui tautan email SMTP',
        $reset['status'] === 302 && pathOf($reset['location']) === '/masuk'
    );

    $resetFlash = $reset['location'] !== null ? $http->request('guest', 'GET', $reset['location']) : ['body' => ''];
    check(
        'Halaman masuk menampilkan konfirmasi reset',
        str_contains($resetFlash['body'], 'Kata sandi berhasil diperbarui')
    );

    $login = $http->post('login', '/masuk', [
        'email' => $testEmail,
        'password' => $newPassword,
    ], '/masuk');
    check(
        'Kata sandi baru hasil reset dapat digunakan untuk masuk',
        $login['status'] === 302 && pathOf($login['location']) === '/'
    );

    $remainingTokens = $testUserId !== null
        ? (int) Database::fetchColumn('SELECT COUNT(*) FROM password_resets WHERE user_id = ?', [$testUserId])
        : -1;
    check('Token reset dihapus setelah digunakan', $remainingTokens === 0);

    /* ----------------------------------------------------------------------
       Pemeriksaan transport langsung: AUTH LOGIN, kegagalan, dan TLS
       ---------------------------------------------------------------------- */

    $loginFake = startFake($root, $workDir, $cert, $key, 'login-smtp', 'login', true, $smtpUser, $smtpPass);
    $fakes[] = $loginFake;

    $loginSend = runSend($root, $workDir, 'login-send', smtpEnv($loginFake['port'], $cert, $smtpUser, $smtpPass));
    check(
        'Transport memakai AUTH LOGIN saat PLAIN tidak ditawarkan',
        $loginSend['code'] === 0
            && str_contains((string) file_get_contents($loginFake['log']), 'AUTH LOGIN OK'),
        'exit=' . $loginSend['code']
    );

    $loginFiles = mailFiles($loginFake['dir'] . '/mail');
    $loginMetadata = [];
    if ($loginFiles !== [] && is_file(end($loginFiles) . '.json')) {
        $decoded = json_decode((string) file_get_contents(end($loginFiles) . '.json'), true);
        $loginMetadata = is_array($decoded) ? $decoded : [];
    }
    check(
        'Metadata mencatat autentikasi LOGIN',
        ($loginMetadata['auth_mechanism'] ?? '') === 'LOGIN' && ($loginMetadata['tls'] ?? false) === true
    );

    $sslFake = startFake($root, $workDir, $cert, $key, 'ssl-smtp', 'both', true, $smtpUser, $smtpPass, true);
    $fakes[] = $sslFake;

    $sslSend = runSend($root, $workDir, 'ssl-send', smtpEnv($sslFake['port'], $cert, $smtpUser, $smtpPass, 'ssl'));
    $sslFiles = mailFiles($sslFake['dir'] . '/mail');
    $sslMetadata = [];
    if ($sslFiles !== [] && is_file(end($sslFiles) . '.json')) {
        $decoded = json_decode((string) file_get_contents(end($sslFiles) . '.json'), true);
        $sslMetadata = is_array($decoded) ? $decoded : [];
    }
    $sslLog = (string) file_get_contents($sslFake['log']);
    check(
        'TLS implisit (ssl) terhubung, terverifikasi, dan terautentikasi',
        $sslSend['code'] === 0
            && ($sslMetadata['tls'] ?? false) === true
            && ($sslMetadata['auth_mechanism'] ?? '') === 'PLAIN'
            && str_contains($sslLog, 'AUTH PLAIN OK')
            && str_contains($sslLog, 'TLS-OK'),
        'exit=' . $sslSend['code']
    );

    $directParsed = $loginFiles !== [] ? parseMessage(end($loginFiles)) : ['body' => '', 'headers' => ''];
    check(
        'Isi pesan dengan titik di awal baris tetap utuh setelah dot-stuffing',
        str_contains($directParsed['body'], '. Titik di awal baris harus utuh.')
    );
    check(
        'Email SMTP tidak ditulis ke kotak surat lokal',
        count(glob($root . '/storage/mail/*.eml') ?: []) === $storageMailBefore
    );

    $mailCountBefore = count(mailFiles($loginFake['dir'] . '/mail'));
    $wrongSend = runSend($root, $workDir, 'wrong-pass', smtpEnv($loginFake['port'], $cert, $smtpUser, $smtpPass . '-salah'));
    $mailCountAfter = count(mailFiles($loginFake['dir'] . '/mail'));
    check(
        'Kata sandi SMTP yang salah ditolak tanpa mengirim pesan',
        $wrongSend['code'] !== 0
            && $mailCountAfter === $mailCountBefore
            && str_contains($wrongSend['stderr'], '535')
            && str_contains($wrongSend['stderr'], 'ditolak')
    );
    check(
        'Pesan kesalahan tidak membocorkan kata sandi SMTP',
        !str_contains($wrongSend['stderr'], $smtpPass)
            && !str_contains($wrongSend['stderr'], base64_encode($smtpPass))
    );

    $noTlsFake = startFake($root, $workDir, $cert, $key, 'no-tls-smtp', 'both', false, $smtpUser, $smtpPass);
    $fakes[] = $noTlsFake;

    $noTlsSend = runSend($root, $workDir, 'no-starttls', smtpEnv($noTlsFake['port'], $cert, $smtpUser, $smtpPass));
    check(
        'Pengiriman dibatalkan bila server tidak menawarkan STARTTLS',
        $noTlsSend['code'] !== 0
            && str_contains($noTlsSend['stderr'], 'STARTTLS')
            && count(mailFiles($noTlsFake['dir'] . '/mail')) === 0
    );

    $plainCreds = runSend($root, $workDir, 'plain-creds', smtpEnv($noTlsFake['port'], $cert, $smtpUser, $smtpPass, 'none'));
    check(
        'Kredensial SMTP ditolak saat enkripsi dinonaktifkan',
        $plainCreds['code'] !== 0 && str_contains($plainCreds['stderr'], 'tanpa TLS')
    );

    $untrustedEnv = smtpEnv($fake['port'], $cert, $smtpUser, $smtpPass);
    unset($untrustedEnv['MAIL_CAFILE']);
    $untrusted = runSend($root, $workDir, 'untrusted-cert', $untrustedEnv);
    check(
        'Sertifikat TLS yang tidak dipercaya menggagalkan pengiriman',
        $untrusted['code'] !== 0
            && (str_contains($untrusted['stderr'], 'Negosiasi TLS') || str_contains($untrusted['stderr'], 'TLS'))
    );

    $unknownTransport = runSend(
        $root,
        $workDir,
        'unknown-transport',
        array_merge(smtpEnv($fake['port'], $cert, $smtpUser, $smtpPass), ['MAIL_TRANSPORT' => 'bogus'])
    );
    check(
        'Nama transport yang tidak dikenal ditolak',
        $unknownTransport['code'] !== 0 && str_contains($unknownTransport['stderr'], 'tidak dikenal')
    );
} catch (Throwable $exception) {
    check('Pengujian SMTP selesai tanpa kesalahan tak terduga', false, $exception->getMessage());
} finally {
    if (is_resource($appProcess)) {
        stopProcess($appProcess);
    }
    foreach ($fakes as $fake) {
        stopProcess($fake['process']);
    }

    try {
        if ($testUserId !== null) {
            Database::execute('DELETE FROM password_resets WHERE user_id = ?', [$testUserId]);
            Database::execute('DELETE FROM users WHERE id = ?', [$testUserId]);
        }
        Database::execute('DELETE FROM rate_limits');
    } catch (Throwable) {
        // Pembersihan terbaik; kegagalan di sini tidak mengubah hasil pengujian.
    }

    if ((string) getenv('SMTP_KEEP') !== '1') {
        removeTree($workDir);
    }
}

$total = $pass + $fail;
echo "\n========================================\n";
echo "Total pemeriksaan SMTP : {$total}\n";
echo "Lulus                  : {$pass}\n";
echo "Gagal                  : {$fail}\n";
if ($failures !== []) {
    echo "\nRincian kegagalan:\n";
    foreach ($failures as $failure) {
        echo '- ' . $failure['test'] . ($failure['detail'] !== '' ? ' — ' . $failure['detail'] : '') . "\n";
    }
}
echo "========================================\n";

$summary = [
    'ran_at' => date(DATE_ATOM),
    'total' => $total,
    'passed' => $pass,
    'failed' => $fail,
    'failures' => $failures,
];
file_put_contents(
    __DIR__ . '/smtp-last-run.json',
    json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    LOCK_EX
);

exit($fail === 0 ? 0 : 1);
