<?php

declare(strict_types=1);

/**
 * Server SMTP lokal untuk pengujian. Mendukung STARTTLS, AUTH PLAIN/LOGIN,
 * dan menyimpan pesan yang diterima ke folder tangkapan.
 *
 * Dijalankan otomatis oleh tests/smtp-run.php; bukan bagian aplikasi produksi.
 */

$options = getopt('', ['port:', 'cert:', 'key:', 'dir:', 'log:', 'ready:', 'auth:', 'no-starttls', 'implicit-tls']);

$port = (int) ($options['port'] ?? 0);
$cert = (string) ($options['cert'] ?? '');
$key = (string) ($options['key'] ?? '');
$captureDir = (string) ($options['dir'] ?? '');
$logFile = (string) ($options['log'] ?? '');
$readyFile = (string) ($options['ready'] ?? '');
$authMode = (string) ($options['auth'] ?? 'both');
$startTlsEnabled = !isset($options['no-starttls']);
$implicitTls = isset($options['implicit-tls']);
$username = (string) (getenv('SMTP_TEST_USER') ?: '');
$password = (string) (getenv('SMTP_TEST_PASS') ?: '');

if ($port < 1 || $captureDir === '' || $logFile === '') {
    fwrite(STDERR, "Argumen server SMTP uji tidak lengkap.\n");
    exit(2);
}
if (($startTlsEnabled || $implicitTls) && ($cert === '' || !is_file($cert) || $key === '' || !is_file($key))) {
    fwrite(STDERR, "Sertifikat TLS untuk server SMTP uji tidak tersedia.\n");
    exit(2);
}
if (!is_dir($captureDir) && !mkdir($captureDir, 0755, true) && !is_dir($captureDir)) {
    fwrite(STDERR, "Folder tangkapan SMTP tidak dapat dibuat.\n");
    exit(2);
}

function smtpLog(string $logFile, string $event): void
{
    file_put_contents($logFile, $event . "\n", FILE_APPEND | LOCK_EX);
}

function smtpLine($client): ?string
{
    $line = fgets($client, 8192);
    if ($line === false) {
        return null;
    }

    return rtrim($line, "\r\n");
}

function smtpWrite($client, string $data): void
{
    fwrite($client, $data);
    fflush($client);
}

function smtpReply($client, string $reply): void
{
    smtpWrite($client, $reply . "\r\n");
}

function smtpCapabilityReply(bool $tls, bool $startTlsEnabled, string $authMode): string
{
    $lines = ['250-fake.local'];
    if (!$tls && $startTlsEnabled) {
        $lines[] = '250-STARTTLS';
    }
    $mechanisms = match ($authMode) {
        'plain' => 'PLAIN',
        'login' => 'LOGIN',
        'both' => 'PLAIN LOGIN',
        default => '',
    };
    if ($mechanisms !== '') {
        $lines[] = '250-AUTH ' . $mechanisms;
    }
    $lines[] = '250-8BITMIME';
    $lines[] = '250 SIZE 52428800';

    return implode("\r\n", $lines);
}

function smtpTlsMethod(): int
{
    $method = STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_SERVER')) {
        $method |= STREAM_CRYPTO_METHOD_TLSv1_3_SERVER;
    }

    return $method;
}

function smtpHandle($client, string $captureDir, string $logFile, bool $startTlsEnabled, string $authMode, string $cert, string $key, string $username, string $password, bool $tlsInitially = false): void
{
    smtpReply($client, '220 fake.local ESMTP Luminara Test');
    smtpLog($logFile, 'CONNECT');

    $tls = $tlsInitially;
    $authenticated = false;
    $authMechanism = 'none';
    $mailFrom = '';
    $recipients = [];

    while (true) {
        $command = smtpLine($client);
        if ($command === null) {
            smtpLog($logFile, 'CLOSE');
            return;
        }
        if (strlen($command) > 2000) {
            smtpReply($client, '500 5.5.2 Line too long');
            continue;
        }

        $upper = strtoupper($command);

        if (str_starts_with($upper, 'EHLO')) {
            smtpLog($logFile, 'EHLO');
            smtpWrite($client, smtpCapabilityReply($tls, $startTlsEnabled, $authMode) . "\r\n");
            continue;
        }

        if (str_starts_with($upper, 'HELO')) {
            smtpLog($logFile, 'HELO');
            smtpReply($client, '250 fake.local');
            continue;
        }

        if ($upper === 'STARTTLS') {
            if (!$startTlsEnabled || $tls) {
                smtpReply($client, '454 4.7.0 TLS not available');
                continue;
            }
            smtpReply($client, '220 2.0.0 Ready to start TLS');
            $ssl = [
                'local_cert' => $cert,
                'local_pk' => $key,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => smtpTlsMethod(),
            ];
            stream_context_set_option($client, ['ssl' => $ssl]);
            $result = @stream_socket_enable_crypto($client, true, smtpTlsMethod());
            if ($result !== true) {
                smtpLog($logFile, 'TLS-FAIL');
                return;
            }
            $tls = true;
            smtpLog($logFile, 'TLS-OK');
            continue;
        }

        if (str_starts_with($upper, 'AUTH ')) {
            if (!$tls) {
                smtpLog($logFile, 'AUTH-REJECT-PLAINTEXT');
                smtpReply($client, '538 5.7.11 Encryption required for requested authentication mechanism');
                continue;
            }

            $parts = preg_split('/\s+/', trim($command)) ?: [];
            $mechanism = strtoupper($parts[1] ?? '');
            $initial = $parts[2] ?? '';

            if ($mechanism === 'PLAIN') {
                if ($initial === '') {
                    smtpReply($client, '334 ');
                    $initial = smtpLine($client) ?? '';
                }
                $decoded = base64_decode($initial, true);
                $fields = $decoded !== false ? explode("\0", $decoded) : [];
                $givenUser = $fields[1] ?? '';
                $givenPass = $fields[2] ?? '';
                $ok = $username !== '' && hash_equals($username, $givenUser) && hash_equals($password, $givenPass);
                smtpLog($logFile, $ok ? 'AUTH PLAIN OK' : 'AUTH PLAIN FAIL');
                if ($ok) {
                    $authenticated = true;
                    $authMechanism = 'PLAIN';
                    smtpReply($client, '235 2.7.0 Authentication successful');
                } else {
                    smtpReply($client, '535 5.7.8 Authentication credentials invalid');
                }
                continue;
            }

            if ($mechanism === 'LOGIN') {
                smtpReply($client, '334 VXNlcm5hbWU6');
                $rawUser = smtpLine($client);
                if ($rawUser === null) {
                    return;
                }
                smtpReply($client, '334 UGFzc3dvcmQ6');
                $rawPass = smtpLine($client);
                if ($rawPass === null) {
                    return;
                }
                $givenUser = base64_decode(trim($rawUser), true);
                $givenPass = base64_decode(trim($rawPass), true);
                $ok = $username !== ''
                    && $givenUser !== false
                    && $givenPass !== false
                    && hash_equals($username, $givenUser)
                    && hash_equals($password, $givenPass);
                smtpLog($logFile, $ok ? 'AUTH LOGIN OK' : 'AUTH LOGIN FAIL');
                if ($ok) {
                    $authenticated = true;
                    $authMechanism = 'LOGIN';
                    smtpReply($client, '235 2.7.0 Authentication successful');
                } else {
                    smtpReply($client, '535 5.7.8 Authentication credentials invalid');
                }
                continue;
            }

            smtpLog($logFile, 'AUTH UNSUPPORTED');
            smtpReply($client, '504 5.5.4 Unrecognized authentication type');
            continue;
        }

        if (str_starts_with($upper, 'MAIL FROM:')) {
            if ($username !== '' && !$authenticated) {
                smtpLog($logFile, 'MAIL REJECT UNAUTHENTICATED');
                smtpReply($client, '530 5.7.0 Authentication required');
                continue;
            }
            $mailFrom = trim(substr($command, 10));
            smtpLog($logFile, 'MAIL FROM ' . $mailFrom);
            smtpReply($client, '250 2.1.0 Ok');
            continue;
        }

        if (str_starts_with($upper, 'RCPT TO:')) {
            $recipient = trim(substr($command, 8));
            $recipients[] = $recipient;
            smtpLog($logFile, 'RCPT TO ' . $recipient);
            smtpReply($client, '250 2.1.5 Ok');
            continue;
        }

        if ($upper === 'DATA') {
            if ($mailFrom === '' || $recipients === []) {
                smtpReply($client, '503 5.5.1 Need MAIL and RCPT first');
                continue;
            }
            smtpReply($client, '354 End data with <CR><LF>.<CR><LF>');
            $message = '';
            $tooLarge = false;
            while (true) {
                $line = smtpLine($client);
                if ($line === null) {
                    return;
                }
                if ($line === '.') {
                    break;
                }
                if (str_starts_with($line, '..')) {
                    $line = substr($line, 1);
                }
                $message .= $line . "\r\n";
                if (strlen($message) > 5242880) {
                    $tooLarge = true;
                    break;
                }
            }
            if ($tooLarge) {
                smtpLog($logFile, 'MESSAGE TOO LARGE');
                smtpReply($client, '552 5.3.4 Message too big');
                continue;
            }

            $name = 'mail-' . date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.eml';
            file_put_contents($captureDir . '/' . $name, $message, LOCK_EX);
            $metadata = [
                'file' => $name,
                'mail_from' => $mailFrom,
                'recipients' => $recipients,
                'tls' => $tls,
                'authenticated' => $authenticated,
                'auth_mechanism' => $authMechanism,
                'received_at' => date(DATE_ATOM),
            ];
            file_put_contents(
                $captureDir . '/' . $name . '.json',
                json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
                LOCK_EX
            );
            smtpLog($logFile, 'MESSAGE ' . $name);
            smtpReply($client, '250 2.0.0 Ok queued as fake-' . bin2hex(random_bytes(4)));
            $mailFrom = '';
            $recipients = [];
            continue;
        }

        if ($upper === 'RSET') {
            $mailFrom = '';
            $recipients = [];
            smtpReply($client, '250 2.0.0 Ok');
            continue;
        }

        if ($upper === 'NOOP') {
            smtpReply($client, '250 2.0.0 Ok');
            continue;
        }

        if ($upper === 'QUIT') {
            smtpLog($logFile, 'QUIT');
            smtpReply($client, '221 2.0.0 Bye');
            return;
        }

        smtpLog($logFile, 'UNKNOWN ' . substr($upper, 0, 40));
        smtpReply($client, '500 5.5.2 Command unrecognized');
    }
}

$ssl = [
    'local_cert' => $cert,
    'local_pk' => $key,
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true,
    'crypto_method' => smtpTlsMethod(),
];
$context = stream_context_create(['ssl' => $ssl]);
$server = @stream_socket_server(
    'tcp://127.0.0.1:' . $port,
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    $context
);
if ($server === false) {
    fwrite(STDERR, 'Server SMTP uji gagal dimulai: ' . $errstr . "\n");
    exit(1);
}

if ($readyFile !== '') {
    file_put_contents($readyFile, (string) getmypid());
}

while (true) {
    $client = @stream_socket_accept($server, 60);
    if ($client === false) {
        continue;
    }
    stream_set_timeout($client, 10);
    $ready = true;
    if ($implicitTls) {
        stream_context_set_option($client, ['ssl' => $ssl]);
        $ready = @stream_socket_enable_crypto($client, true, smtpTlsMethod()) === true;
        smtpLog($logFile, $ready ? 'TLS-OK' : 'TLS-FAIL');
    }
    if ($ready) {
        smtpHandle($client, $captureDir, $logFile, $startTlsEnabled && !$implicitTls, $authMode, $cert, $key, $username, $password, $implicitTls);
    }
    @fclose($client);
}
