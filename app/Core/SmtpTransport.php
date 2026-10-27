<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Klien SMTP minimal tanpa dependensi. Mendukung STARTTLS, TLS implisit,
 * AUTH PLAIN/LOGIN, dan selalu memverifikasi sertifikat server.
 */
final class SmtpTransport
{
    /** @var resource|null */
    private $stream = null;

    /** @var list<string> */
    private array $capabilities = [];

    private bool $tlsActive = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function send(string $fromAddress, string $toAddress, string $message): void
    {
        $host = trim((string) ($this->config['host'] ?? ''));
        if ($host === '' || preg_match('/[\r\n\s]/', $host) === 1) {
            throw new RuntimeException('Host SMTP tidak valid.');
        }

        $encryption = $this->encryption();
        $port = (int) ($this->config['port'] ?? ($encryption === 'ssl' ? 465 : 587));
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('Port SMTP tidak valid.');
        }

        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');
        if (($username === '') !== ($password === '')) {
            throw new RuntimeException('Nama pengguna dan kata sandi SMTP harus diisi bersamaan.');
        }
        if ($username !== '' && $encryption === 'none') {
            throw new RuntimeException('Kredensial SMTP tidak boleh dikirim tanpa TLS.');
        }
        if (preg_match('/[\r\n<>]/', $fromAddress . $toAddress) === 1) {
            throw new RuntimeException('Alamat email SMTP tidak valid.');
        }

        $helo = $this->heloName();
        $message = $this->normalizeMessage($message);
        $timeout = max(3, min(60, (int) ($this->config['timeout'] ?? 15)));

        try {
            $this->connect($host, $port, $encryption, $timeout);
            $this->expect([220], 'sambutan server');

            $response = $this->command('EHLO ' . $helo, []);
            if ($response[0] !== 250) {
                if ($encryption === 'tls') {
                    throw new RuntimeException('Server SMTP tidak menerima EHLO sehingga STARTTLS tidak dapat dilakukan.');
                }
                $this->command('HELO ' . $helo, [250]);
                $this->capabilities = [];
            } else {
                $this->capabilities = $this->parseCapabilities($response[1]);
            }

            if ($encryption === 'tls') {
                $this->enableStartTls($helo);
            }

            if ($username !== '') {
                $this->authenticate($username, $password);
            }

            $this->command('MAIL FROM:<' . $fromAddress . '>', [250]);
            $this->command('RCPT TO:<' . $toAddress . '>', [250, 251]);
            $this->command('DATA', [354]);
            $this->writeData($message);
            $this->expect([250], 'penerimaan pesan');
            $this->quit();
        } finally {
            $this->close();
        }
    }

    private function encryption(): string
    {
        $encryption = strtolower(trim((string) ($this->config['encryption'] ?? 'tls')));
        if ($encryption === '' || $encryption === 'starttls') {
            $encryption = 'tls';
        }
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            throw new RuntimeException('Mode enkripsi SMTP tidak dikenal. Gunakan tls, ssl, atau none.');
        }

        return $encryption;
    }

    private function heloName(): string
    {
        $helo = trim((string) ($this->config['helo'] ?? 'localhost'));
        if ($helo === '') {
            return 'localhost';
        }
        if (preg_match('/^[A-Za-z0-9._:\-\[\]]{1,255}$/', $helo) !== 1) {
            throw new RuntimeException('Nama HELO SMTP tidak valid.');
        }

        return $helo;
    }

    private function connect(string $host, int $port, string $encryption, int $timeout): void
    {
        $options = $this->sslOptions($host);
        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create(['ssl' => $options]);

        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if ($stream === false) {
            $reason = trim(preg_replace('/[\r\n]+/', ' ', $errstr) ?? '');
            $reason = $reason !== '' ? ': ' . substr($reason, 0, 200) : '';
            throw new RuntimeException(sprintf('Tidak dapat terhubung ke server SMTP %s:%d%s', $host, $port, $reason));
        }

        $this->stream = $stream;
        $this->tlsActive = $encryption === 'ssl';
        stream_set_timeout($stream, $timeout);
    }

    /**
     * @return array<string, mixed>
     */
    private function sslOptions(string $host): array
    {
        $options = [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => $host,
            'crypto_method' => $this->cryptoMethod(),
        ];

        $cafile = trim((string) ($this->config['cafile'] ?? ''));
        if ($cafile !== '') {
            if (!is_file($cafile) || !is_readable($cafile)) {
                throw new RuntimeException('File CA untuk verifikasi SMTP tidak dapat dibaca.');
            }
            $options['cafile'] = $cafile;
        }

        return $options;
    }

    private function cryptoMethod(): int
    {
        $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        return $method;
    }

    private function enableStartTls(string $helo): void
    {
        if (!$this->hasCapability('STARTTLS')) {
            throw new RuntimeException('Server SMTP tidak menawarkan STARTTLS; pengiriman dibatalkan demi keamanan.');
        }

        $this->command('STARTTLS', [220]);
        $result = @stream_socket_enable_crypto($this->stream, true, $this->cryptoMethod());
        if ($result !== true) {
            throw new RuntimeException('Negosiasi TLS dengan server SMTP gagal. Periksa sertifikat dan file CA.');
        }
        $this->tlsActive = true;

        $response = $this->command('EHLO ' . $helo, [250]);
        $this->capabilities = $this->parseCapabilities($response[1]);
    }

    private function authenticate(string $username, string $password): void
    {
        if (!$this->tlsActive) {
            throw new RuntimeException('Autentikasi SMTP hanya dilakukan melalui koneksi TLS.');
        }

        $mechanisms = $this->authMechanisms();
        if (in_array('PLAIN', $mechanisms, true)) {
            $this->command('AUTH PLAIN ' . base64_encode("\0" . $username . "\0" . $password), [235], true);
            return;
        }

        if (in_array('LOGIN', $mechanisms, true)) {
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode($username), [334], true);
            $this->command(base64_encode($password), [235], true);
            return;
        }

        throw new RuntimeException('Server SMTP tidak menawarkan AUTH PLAIN atau LOGIN.');
    }

    /**
     * @param list<string> $lines
     * @return list<string>
     */
    private function parseCapabilities(array $lines): array
    {
        $capabilities = [];
        foreach ($lines as $line) {
            $line = strtoupper(trim($line));
            if ($line !== '') {
                $capabilities[] = $line;
            }
        }

        return $capabilities;
    }

    private function hasCapability(string $name): bool
    {
        foreach ($this->capabilities as $capability) {
            if ($capability === $name || str_starts_with($capability, $name . ' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function authMechanisms(): array
    {
        $mechanisms = [];
        foreach ($this->capabilities as $capability) {
            if (preg_match('/^AUTH[ =](.+)$/', $capability, $matches) === 1) {
                foreach (preg_split('/\s+/', trim($matches[1])) ?: [] as $mechanism) {
                    if ($mechanism !== '') {
                        $mechanisms[] = $mechanism;
                    }
                }
            }
        }

        return $mechanisms;
    }

    /**
     * @param list<int> $expected
     * @return array{0: int, 1: list<string>}
     */
    private function command(string $command, array $expected, bool $sensitive = false): array
    {
        if (preg_match('/[\r\n]/', $command) === 1) {
            throw new RuntimeException('Perintah SMTP tidak valid.');
        }

        $this->write($command . "\r\n");
        [$code, $lines] = $this->readResponse();
        if ($expected !== [] && !in_array($code, $expected, true)) {
            if ($sensitive) {
                throw new RuntimeException('Autentikasi SMTP ditolak server (kode ' . $code . ').');
            }
            $text = trim((string) ($lines[0] ?? ''));
            $text = substr(preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '', 0, 200);
            throw new RuntimeException(sprintf(
                'Server SMTP menolak perintah %s (kode %d)%s',
                $command,
                $code,
                $text !== '' ? ': ' . $text : '.'
            ));
        }

        return [$code, $lines];
    }

    /**
     * @param list<int> $expected
     */
    private function expect(array $expected, string $stage): void
    {
        [$code, $lines] = $this->readResponse();
        if (!in_array($code, $expected, true)) {
            $text = trim((string) ($lines[0] ?? ''));
            $text = substr(preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '', 0, 200);
            throw new RuntimeException(sprintf(
                'Server SMTP menolak %s (kode %d)%s',
                $stage,
                $code,
                $text !== '' ? ': ' . $text : '.'
            ));
        }
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    private function readResponse(): array
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Koneksi SMTP tidak tersedia.');
        }

        $lines = [];
        $code = null;
        for ($i = 0; $i < 100; $i++) {
            $line = fgets($this->stream, 4096);
            if ($line === false) {
                $meta = stream_get_meta_data($this->stream);
                throw new RuntimeException(
                    !empty($meta['timed_out'])
                        ? 'Server SMTP tidak merespons tepat waktu.'
                        : 'Koneksi SMTP terputus sebelum balasan lengkap diterima.'
                );
            }

            $line = rtrim($line, "\r\n");
            if (strlen($line) < 3 || !ctype_digit(substr($line, 0, 3))) {
                throw new RuntimeException('Balasan server SMTP tidak dapat dibaca.');
            }

            $current = (int) substr($line, 0, 3);
            if ($code === null) {
                $code = $current;
            } elseif ($current !== $code) {
                throw new RuntimeException('Balasan server SMTP tidak konsisten.');
            }

            $lines[] = substr($line, 4);
            if (($line[3] ?? ' ') !== '-') {
                return [$code, $lines];
            }
        }

        throw new RuntimeException('Balasan server SMTP terlalu panjang.');
    }

    private function writeData(string $message): void
    {
        $data = preg_replace('/^\./m', '..', $message) ?? $message;
        if (!str_ends_with($data, "\r\n")) {
            $data .= "\r\n";
        }
        $this->write($data . ".\r\n");
    }

    private function write(string $data): void
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Koneksi SMTP tidak tersedia.');
        }

        $length = strlen($data);
        $offset = 0;
        while ($offset < $length) {
            $written = @fwrite($this->stream, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Gagal mengirim data ke server SMTP.');
            }
            $offset += $written;
        }
    }

    private function normalizeMessage(string $message): string
    {
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = str_replace("\n", "\r\n", $message);

        return rtrim($message, "\r\n") . "\r\n";
    }

    private function quit(): void
    {
        if (!is_resource($this->stream)) {
            return;
        }

        @fwrite($this->stream, "QUIT\r\n");
        @fgets($this->stream, 4096);
    }

    private function close(): void
    {
        if (is_resource($this->stream)) {
            @fclose($this->stream);
        }
        $this->stream = null;
        $this->tlsActive = false;
    }
}
