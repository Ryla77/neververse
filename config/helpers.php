<?php

declare(strict_types=1);

/**
 * Memuat variabel dari file .env tanpa dependensi eksternal.
 * Variabel yang sudah ada di environment sistem tidak ditimpa.
 */
function load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $length = strlen($value);
        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
}

/**
 * Membaca konfigurasi aplikasi dengan notasi titik, mis. config('db.host').
 */
function config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require BASE_PATH . '/config/config.php';
    }
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/**
 * Path dasar aplikasi (mendukung document root langsung maupun subfolder XAMPP).
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $base = '';

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
    if ($docRoot !== '' && $scriptFile !== '') {
        $docRootReal = realpath($docRoot);
        $scriptReal = realpath($scriptFile);
        if ($docRootReal !== false && $scriptReal !== false
            && str_starts_with(str_replace('\\', '/', $scriptReal), str_replace('\\', '/', $docRootReal))) {
            $docNormalized = str_replace('\\', '/', $docRootReal);
            $relative = substr(str_replace('\\', '/', $scriptReal), strlen($docNormalized));
            $dir = str_replace('\\', '/', dirname($relative));
            $base = ($dir === '.' || $dir === '/') ? '' : rtrim($dir, '/');
            return $base;
        }
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptName !== '') {
        $dir = str_replace('\\', '/', dirname($scriptName));
        $base = ($dir === '.' || $dir === '/') ? '' : rtrim($dir, '/');
    }

    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = base_path();
    if ($path === '') {
        return $base . '/';
    }
    return $base . '/' . $path;
}

/**
 * URL absolut untuk tautan di email. Setel APP_URL di produksi agar tidak
 * bergantung pada header/host dari server.
 */
function absolute_url(string $path = ''): string
{
    $configured = rtrim((string) env('APP_URL', ''), '/');
    if ($configured !== '') {
        return $configured . '/' . ltrim($path, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        ? 'https'
        : 'http';
    $host = (string) ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $host = preg_replace('/[^A-Za-z0-9.\-]/', '', $host) ?? '';
    if ($host === '') {
        $host = 'localhost';
    }
    $port = (int) ($_SERVER['SERVER_PORT'] ?? 0);
    if ($port > 0 && !in_array($port, [80, 443], true)) {
        $host .= ':' . $port;
    }

    return $scheme . '://' . $host . url($path);
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $full = BASE_PATH . '/public/' . $path;
    $version = is_file($full) ? (string) filemtime($full) : APP_VERSION;
    return url($path) . '?v=' . substr($version, -8);
}

/**
 * Escape output HTML (perlindungan XSS).
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(\App\Core\Csrf::token()) . '">';
}

function flash(string $key, mixed $default = null): mixed
{
    return \App\Core\Session::flashData($key, $default);
}

/**
 * Mengambil input lama dari sesi (untuk mengisi ulang formulir).
 */
function old(string $key, mixed $default = ''): mixed
{
    $old = \App\Core\Session::flashData('_old', []);
    return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
}

function form_error(string $field): ?string
{
    $errors = \App\Core\Session::flashData('errors', []);
    return is_array($errors) && isset($errors[$field]) ? (string) $errors[$field] : null;
}

function has_errors(): bool
{
    $errors = \App\Core\Session::flashData('errors', []);
    return is_array($errors) && $errors !== [];
}

function request_path(): string
{
    return \App\Core\Request::capturePath();
}

function nav_active(string $prefix): bool
{
    $path = request_path();
    if ($prefix === '/') {
        return $path === '/';
    }
    return $path === $prefix || str_starts_with($path, $prefix . '/');
}

function theme(): string
{
    $theme = $_COOKIE['novelverse_theme'] ?? 'dark';
    return in_array($theme, ['dark', 'light'], true) ? $theme : 'dark';
}

function current_user(): ?array
{
    return \App\Core\Auth::user();
}

function format_date(?string $datetime, bool $withTime = false): string
{
    if ($datetime === null || $datetime === '' || str_starts_with($datetime, '0000-00-00')) {
        return '-';
    }
    try {
        $date = new DateTimeImmutable($datetime, new DateTimeZone(date_default_timezone_get()));
    } catch (Exception) {
        return '-';
    }
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $text = $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    if ($withTime) {
        $text .= ', ' . $date->format('H:i');
    }
    return $text;
}

function time_ago(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '-';
    }
    try {
        $date = new DateTimeImmutable($datetime, new DateTimeZone(date_default_timezone_get()));
    } catch (Exception) {
        return '-';
    }
    $now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));
    $diff = $now->getTimestamp() - $date->getTimestamp();

    if ($diff < 60) {
        return 'baru saja';
    }
    if ($diff < 3600) {
        return intdiv($diff, 60) . ' menit lalu';
    }
    if ($diff < 86400) {
        return intdiv($diff, 3600) . ' jam lalu';
    }
    if ($diff < 604800) {
        return intdiv($diff, 86400) . ' hari lalu';
    }
    return format_date($datetime);
}

function novel_status_label(string $status): string
{
    return $status === 'completed' ? 'Selesai' : 'Sedang Berjalan';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
    }
    return $letters !== '' ? $letters : 'NV';
}

function excerpt_text(string $text, int $limit = 140): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

function cover_url(?string $coverPath): string
{
    $path = $coverPath !== null ? trim($coverPath) : '';
    if ($path === '') {
        return asset('assets/images/covers/placeholder.svg');
    }
    return asset($path);
}

function stars_html(float|int|string|null $rating): string
{
    $value = $rating === null ? 0.0 : (float) $rating;
    $value = max(0.0, min(5.0, $value));
    $percent = ($value / 5) * 100;
    $label = number_format($value, 1, ',', '.');
    return '<span class="stars" role="img" aria-label="Rating ' . e($label) . ' dari 5">'
        . '<span class="stars__base">' . str_repeat(icon('star'), 5) . '</span>'
        . '<span class="stars__fill" style="width:' . rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%">'
        . str_repeat(icon('star-fill'), 5) . '</span></span>';
}

function icon(string $name, string $class = 'icon'): string
{
    static $icons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2.1 5-5 2.1 2.1-5 5-2.1Z"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4.5L6 21V3Z"/>',
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v18H6.5A2.5 2.5 0 0 0 4 23V5.5Z"/>',
        'book-open' => '<path d="M12 6c-2-1.5-4.5-2-8-2v15c3.5 0 6 .5 8 2 2-1.5 4.5-2 8-2V4c-3.5 0-6 .5-8 2Z"/><path d="M12 6v15"/>',
        'pencil' => '<path d="m4 20 1-5L16 4l4 4L9 19l-5 1Z"/><path d="m14 6 4 4"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.5 7.7 8 9 4.5-1.3 8-4 8-9V6l-8-3Z"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'star' => '<path d="m12 3 2.7 5.7 6.3.9-4.6 4.4 1.1 6.2L12 17.3l-5.5 2.9 1.1-6.2L3 9.6l6.3-.9L12 3Z"/>',
        'star-fill' => '<path d="m12 3 2.7 5.7 6.3.9-4.6 4.4 1.1 6.2L12 17.3l-5.5 2.9 1.1-6.2L3 9.6l6.3-.9L12 3Z" fill="currentColor" stroke="none"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7 3.5C19 15.6 12 20 12 20Z"/>',
        'heart-fill' => '<path d="M12 20s-7-4.4-7-9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7 3.5C19 15.6 12 20 12 20Z" fill="currentColor" stroke="none"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'sliders' => '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
        'list' => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-5.5 6.5-5.5s6.5 2 6.5 5.5"/><path d="M16 5a3 3 0 0 1 0 6M17.5 14.5c2.4.5 4 2.3 4 5.5"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-8 8H4l2-3a8 8 0 1 1 15-5Z"/>',
        'flag' => '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
        'check' => '<path d="m5 12 5 5L20 7"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14"/><path d="M10 11v6M14 11v6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'upload' => '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 20h16"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'login' => '<path d="M15 3h4v18h-4"/><path d="M10 8l4 4-4 4"/><path d="M14 12H3"/>',
        'logout' => '<path d="M9 3H5v18h4"/><path d="M14 8l4 4-4 4"/><path d="M18 12H7"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'home' => '<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'alert' => '<path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5M12 17h.01"/>',
        'folder' => '<path d="M3 6h6l2 2h10v12H3V6Z"/>',
        'external' => '<path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M20 14v6H4V4h6"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false" '
        . 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
        . $icons[$name] . '</svg>';
}

function abort(int $status): never
{
    throw new \App\Core\HttpException($status);
}

/**
 * Mengubah teks bab menjadi paragraf HTML yang sudah di-escape (aman XSS).
 */
function chapter_html(string $content): string
{
    $content = str_replace(["\r\n", "\r"], "\n", trim($content));
    if ($content === '') {
        return '<p>Bab ini belum memiliki isi.</p>';
    }
    $paragraphs = preg_split('/\n\s*\n/', $content) ?: [];
    $html = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim(preg_replace('/\s*\n\s*/', ' ', $paragraph) ?? '');
        if ($paragraph !== '') {
            $html .= '<p>' . e($paragraph) . '</p>';
        }
    }
    return $html;
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Admin',
        'author' => 'Penulis',
        default => 'Pembaca',
    };
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = str_replace(['&', '+'], ['dan', 'dan'], $text);
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'novelverse';
    }
    return mb_substr($text, 0, 180);
}

function report_reason_label(string $reason): string
{
    return match ($reason) {
        'spam' => 'Spam atau promosi',
        'harassment' => 'Pelecehan atau ujaran kebencian',
        'copyright' => 'Pelanggaran hak cipta',
        'explicit' => 'Konten tidak pantas',
        default => 'Lainnya',
    };
}
