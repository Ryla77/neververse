<?php

declare(strict_types=1);

/**
 * Uji integrasi NovelVerse melalui HTTP nyata.
 *
 * Jalankan server lebih dulu:
 *   php -S 127.0.0.1:8080 -t public public/router.php
 * Lalu:
 *   php tests/run.php
 *
 * Kredensial demo dibaca dari storage/install-credentials.txt (tidak dicetak).
 * Data uji dibersihkan kembali di akhir.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Totp;
use App\Models\BookmarkModel;
use App\Models\ReadingProgressModel;

final class Http
{
    /** @var array<string, string> */
    private array $jars = [];

    public function __construct(private readonly string $base, private readonly string $tmp)
    {
    }

    public function reset(): void
    {
        foreach (glob($this->tmp . '/cookies-*.txt') ?: [] as $file) {
            @unlink($file);
        }
        $this->jars = [];
    }

    /**
     * @param array<string, mixed>|null $data
     * @param array<int, string> $headers
     * @return array{status:int, body:string, headers:array<string, array<int, string>>, location:?string}
     */
    public function request(
        string $actor,
        string $method,
        string $path,
        ?array $data = null,
        array $headers = [],
        bool $multipart = false,
        bool $follow = false
    ): array {
        $jar = $this->jar($actor);
        $url = preg_match('#^https?://#i', $path) === 1
            ? $path
            : $this->base . '/' . ltrim($path, '/');

        $curl = curl_init($url);
        $responseHeaders = [];
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIEJAR => $jar,
            CURLOPT_COOKIEFILE => $jar,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERAGENT => 'NovelVerse-Test/1.0',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, 'HTTP/')) {
                    return strlen($line);
                }
                $parts = explode(':', $trimmed, 2);
                if (count($parts) === 2) {
                    $name = strtolower(trim($parts[0]));
                    $responseHeaders[$name][] = trim($parts[1]);
                }
                return strlen($line);
            },
        ]);

        if ($data !== null) {
            if ($multipart) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $body = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $location = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
        curl_close($curl);
        if ($body === false) {
            $body = '';
            $status = 0;
            $responseHeaders['x-test-error'][] = $error;
        }

        return [
            'status' => $status,
            'body' => (string) $body,
            'headers' => $responseHeaders,
            'location' => is_string($location) && $location !== '' ? $location : null,
        ];
    }

    private function jar(string $actor): string
    {
        if (!isset($this->jars[$actor])) {
            $file = $this->tmp . '/cookies-' . preg_replace('/[^a-z0-9_-]/i', '', $actor) . '.txt';
            if (!is_file($file)) {
                touch($file);
            }
            $this->jars[$actor] = $file;
        }
        return $this->jars[$actor];
    }
}

$base = rtrim((string) (getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8080'), '/');
$tmp = __DIR__ . '/tmp';
if (!is_dir($tmp) && !mkdir($tmp, 0755, true) && !is_dir($tmp)) {
    fwrite(STDERR, "Tidak dapat membuat folder tests/tmp.\n");
    exit(1);
}

$http = new Http($base, $tmp);
$http->reset();

$pass = 0;
$fail = 0;
$failures = [];
$tokens = [];

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

function tokenFor(Http $http, string $actor, string $page = '/'): string
{
    global $tokens;
    $key = $actor . '|' . $page;
    if (isset($tokens[$key]) && $tokens[$key] !== '') {
        return $tokens[$key];
    }
    $response = $http->request($actor, 'GET', $page);
    if (preg_match('/<meta name="csrf-token" content="([^"]+)">/', $response['body'], $match) === 1) {
        $tokens[$key] = $match[1];
        return $match[1];
    }
    if (preg_match('/name="_token" value="([^"]+)"/', $response['body'], $match) === 1) {
        $tokens[$key] = $match[1];
        return $match[1];
    }
    return '';
}

/** @param array<string, mixed> $data */
function postWith(
    Http $http,
    string $actor,
    string $path,
    array $data,
    bool $multipart = false
): array {
    $token = tokenFor($http, $actor);
    if ($token !== '') {
        $data['_token'] = $token;
    }
    return $http->request($actor, 'POST', $path, $data, [], $multipart);
}

function follow(Http $http, string $actor, array $response): array
{
    if ($response['location'] === null) {
        return $response;
    }
    return $http->request($actor, 'GET', $response['location']);
}

function locationIs(array $response, string $expectedPath): bool
{
    return pathOf($response['location']) === $expectedPath;
}

function dbCount(string $sql, array $params = []): int
{
    return (int) Database::fetchColumn($sql, $params);
}

/**
 * @return array{file:string, raw:string}
 */
function mailFor(string $directory, string $email, string $needle): array
{
    $files = glob($directory . '/*.eml') ?: [];
    rsort($files);
    foreach ($files as $file) {
        $raw = (string) file_get_contents($file);
        if (str_contains($raw, 'To: ' . $email) && str_contains($raw, $needle)) {
            return ['file' => $file, 'raw' => $raw];
        }
    }

    return ['file' => '', 'raw' => ''];
}

/**
 * @return list<string>
 */
function verificationTokensFor(string $directory, string $email): array
{
    $tokens = [];
    foreach (glob($directory . '/*.eml') ?: [] as $file) {
        $raw = (string) file_get_contents($file);
        if (!str_contains($raw, 'To: ' . $email)) {
            continue;
        }
        if (preg_match('#/verifikasi-email/([a-f0-9]{64})#', $raw, $match) === 1) {
            $tokens[] = $match[1];
        }
    }

    return array_values(array_unique($tokens));
}

function makePng(string $path, int $width, int $height): void
{
    $chunk = static function (string $type, string $data): string {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    };
    $raw = '';
    for ($y = 0; $y < $height; $y++) {
        $raw .= "\x00";
        for ($x = 0; $x < $width; $x++) {
            $raw .= chr(38 + ($x % 60)) . chr(22 + ($y % 40)) . chr(90);
        }
    }
    $png = "\x89PNG\r\n\x1a\n"
        . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
        . $chunk('IDAT', gzcompress($raw, 6))
        . $chunk('IEND', '');
    file_put_contents($path, $png);
}

/* --------------------------------------------------------------------------
   Persiapan: kredensial demo, data acuan, dan pembersihan rate limit
   -------------------------------------------------------------------------- */

$credentialsFile = BASE_PATH . '/storage/install-credentials.txt';
$demo = [];
if (is_file($credentialsFile)) {
    foreach (file($credentialsFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (preg_match('/^(ADMIN|PENULIS|PEMBACA)\s*\|\s*(\S+)\s*\|\s*(\S+)$/u', $line, $match) === 1) {
            $demo[strtolower($match[1])][] = ['email' => $match[2], 'password' => $match[3]];
        }
    }
}

check('Kredensial demo tersedia untuk pengujian', isset($demo['admin'][0], $demo['penulis'][0], $demo['pembaca'][0]));

Database::execute("DELETE FROM rate_limits");

$suffix = date('ymdHis') . random_int(100, 999);
$testEmail = 'uji.' . $suffix . '@example.test';
$testPassword = 'RahasiaUji!' . $suffix;
$newPassword = 'RahasiaBaru!' . $suffix;
$testDisplay = 'Pembaca Uji ' . $suffix;
$pendingEmail = 'tertunda.' . $suffix . '@example.test';
$pendingEmail2 = 'tertunda2.' . $suffix . '@example.test';

$admin = $demo['admin'][0] ?? ['email' => '', 'password' => ''];
$author = $demo['penulis'][0] ?? ['email' => '', 'password' => ''];
$otherAuthor = $demo['penulis'][1] ?? $author;
$reader = $demo['pembaca'][0] ?? ['email' => '', 'password' => ''];

$publishedNovel = Database::fetch("SELECT id, slug, title, author_id FROM novels WHERE publish_status = 'published' ORDER BY id LIMIT 1");
$draftNovel = Database::fetch("SELECT id, slug, title FROM novels WHERE publish_status = 'draft' ORDER BY id LIMIT 1");
$ownNovel = Database::fetch('SELECT id, slug, title FROM novels WHERE author_id = (SELECT id FROM users WHERE email = ?) ORDER BY id LIMIT 1', [$author['email']]);
$otherNovel = Database::fetch('SELECT id, slug, title FROM novels WHERE author_id = (SELECT id FROM users WHERE email = ?) ORDER BY id LIMIT 1', [$otherAuthor['email']]);
$firstChapter = $publishedNovel !== null
    ? Database::fetch("SELECT id, chapter_number, title FROM chapters WHERE novel_id = ? AND status = 'published' ORDER BY chapter_number LIMIT 1", [(int) $publishedNovel['id']])
    : null;

check('Database terhubung dan data seed tersedia', $publishedNovel !== null && $draftNovel !== null && $ownNovel !== null && $otherNovel !== null && $firstChapter !== null);
check('Seed memiliki minimal 12 novel terbit', dbCount("SELECT COUNT(*) FROM novels WHERE publish_status = 'published'") >= 12);
check('Seed memiliki enam genre', dbCount('SELECT COUNT(*) FROM categories') >= 6);
check('Seed memiliki banyak bab terbit', dbCount("SELECT COUNT(*) FROM chapters WHERE status = 'published'") >= 30);

$testNovelId = null;
$testNovelSlug = null;
$testCoverPath = null;
$commentId = null;
$draftCommentId = null;
$reportId = null;
$genreId = null;
$testUserId = null;
$pendingUserId = null;
$mailFilesCreated = [];

try {
    /* ----------------------------------------------------------------------
       Halaman publik, pencarian, filter, dan detail
       ---------------------------------------------------------------------- */

    $home = $http->request('anon', 'GET', '/');
    check('Beranda merespons 200', $home['status'] === 200, 'status ' . $home['status']);
    check('Beranda menampilkan judul situs', str_contains($home['body'], 'NovelVerse'));
    check('Beranda menampilkan novel dari database', $publishedNovel !== null && str_contains($home['body'], (string) $publishedNovel['title']));
    check('Beranda memuat CSS dan JS lokal', str_contains($home['body'], 'assets/css/app.css') && str_contains($home['body'], 'assets/js/app.js'));

    $explore = $http->request('anon', 'GET', '/jelajahi');
    check('Katalog merespons 200', $explore['status'] === 200);
    check('Katalog menampilkan novel terbit', $publishedNovel !== null && str_contains($explore['body'], (string) $publishedNovel['title']));
    check('Novel draf tidak tampil di katalog', $draftNovel !== null && !str_contains($explore['body'], (string) $draftNovel['title']));

    $search = $http->request('anon', 'GET', '/jelajahi?q=' . rawurlencode('Lentera'));
    check('Pencarian judul mengembalikan hasil relevan', $search['status'] === 200 && str_contains($search['body'], 'Lentera di Ujung Musim'));
    check('Pencarian mengecualikan novel tak relevan', !str_contains($search['body'], 'Bayang-Bayang Pulau Kelabu'));

    $authorSearch = $http->request('anon', 'GET', '/jelajahi?q=' . rawurlencode('Rania'));
    check('Pencarian berdasarkan nama penulis bekerja', $authorSearch['status'] === 200 && str_contains($authorSearch['body'], (string) $ownNovel['title']));

    $genreRow = Database::fetch('SELECT c.slug, c.name, n.title FROM categories c JOIN novel_categories nc ON nc.category_id = c.id JOIN novels n ON n.id = nc.novel_id WHERE n.publish_status = \'published\' ORDER BY c.id LIMIT 1');
    $genreFilter = $http->request('anon', 'GET', '/jelajahi?genre=' . rawurlencode((string) ($genreRow['slug'] ?? '')));
    check('Filter genre bekerja', $genreFilter['status'] === 200 && $genreRow !== null && str_contains($genreFilter['body'], (string) $genreRow['title']));

    $invalidGenre = $http->request('anon', 'GET', '/jelajahi?genre=genre-yang-tidak-ada');
    check(
        'Genre tidak dikenal diabaikan dan katalog tetap tampil',
        $invalidGenre['status'] === 200 && str_contains($invalidGenre['body'], (string) $publishedNovel['title'])
    );

    $popular = $http->request('anon', 'GET', '/jelajahi?sort=popular');
    $ratingSort = $http->request('anon', 'GET', '/jelajahi?sort=rating');
    check('Pengurutan populer dan rating tersedia', $popular['status'] === 200 && $ratingSort['status'] === 200);
    check('Pagination katalog dirender', str_contains($explore['body'], 'pagination'));

    $xssSearch = $http->request('anon', 'GET', '/jelajahi?q=' . rawurlencode('<script>alert(1)</script>'));
    check('Input pencarian di-escape (XSS)', $xssSearch['status'] === 200 && !str_contains($xssSearch['body'], '<script>alert(1)</script>') && str_contains($xssSearch['body'], '&lt;script&gt;'));

    $detail = $http->request('anon', 'GET', '/novel/' . $publishedNovel['slug']);
    check('Detail novel merespons 200', $detail['status'] === 200);
    check('Detail menampilkan sinopsis dan daftar bab', str_contains($detail['body'], 'Sinopsis') && str_contains($detail['body'], 'Daftar bab') && str_contains($detail['body'], (string) $firstChapter['title']));
    check('Detail menampilkan rating dan komentar', str_contains($detail['body'], 'Rating pembaca') && str_contains($detail['body'], 'Komentar pembaca'));
    check('Detail memiliki tombol mulai membaca', str_contains($detail['body'], 'id="start-reading"'));

    $readerPage = $http->request('anon', 'GET', '/novel/' . $publishedNovel['slug'] . '/baca/' . (int) $firstChapter['chapter_number']);
    check('Halaman baca merespons 200', $readerPage['status'] === 200);
    check('Konten bab tampil di halaman baca', str_contains($readerPage['body'], (string) $firstChapter['title']) && str_contains($readerPage['body'], 'reader-content'));
    check('Navigasi bab berikutnya tersedia', str_contains($readerPage['body'], 'data-reader-next'));
    check('Pembaca tamu memakai progres perangkat', str_contains($readerPage['body'], 'data-authenticated="0"') && str_contains($readerPage['body'], 'Progres disimpan di perangkat'));

    $chapterTwo = Database::fetch("SELECT chapter_number FROM chapters WHERE novel_id = ? AND status = 'published' AND chapter_number > ? ORDER BY chapter_number LIMIT 1", [(int) $publishedNovel['id'], (int) $firstChapter['chapter_number']]);
    if ($chapterTwo !== null) {
        $readerTwo = $http->request('anon', 'GET', '/novel/' . $publishedNovel['slug'] . '/baca/' . (int) $chapterTwo['chapter_number']);
        check('Navigasi bab sebelumnya tersedia', $readerTwo['status'] === 200 && str_contains($readerTwo['body'], 'data-reader-prev'));
    }

    check('Bab tidak valid mengembalikan 404', $http->request('anon', 'GET', '/novel/' . $publishedNovel['slug'] . '/baca/9999')['status'] === 404);
    check('Novel draf tidak dapat diakses publik', $draftNovel !== null && $http->request('anon', 'GET', '/novel/' . $draftNovel['slug'])['status'] === 404);
    check('Novel tidak ada mengembalikan 404', $http->request('anon', 'GET', '/novel/tidak-ada-novel-ini')['status'] === 404);

    /* ----------------------------------------------------------------------
       Keamanan dasar: header, cookie, CSRF, file sensitif
       ---------------------------------------------------------------------- */

    $csp = implode(' ', $home['headers']['content-security-policy'] ?? []);
    check('Header CSP membatasi script ke origin sendiri', str_contains($csp, "script-src 'self'"));
    check('Header keamanan dasar terpasang', in_array('DENY', $home['headers']['x-frame-options'] ?? [], true) && in_array('nosniff', $home['headers']['x-content-type-options'] ?? [], true));
    $setCookie = implode('; ', $home['headers']['set-cookie'] ?? []);
    check('Cookie sesi HttpOnly dan SameSite=Lax', stripos($setCookie, 'HttpOnly') !== false && stripos($setCookie, 'SameSite=Lax') !== false);

    $envProbe = $http->request('anon', 'GET', '/.env');
    check('File .env tidak dapat diakses publik', $envProbe['status'] === 404 && !str_contains($envProbe['body'], 'DB_PASS'));
    $storageProbe = $http->request('anon', 'GET', '/storage/install-credentials.txt');
    check('Folder storage tidak dapat diakses publik', $storageProbe['status'] === 404 && !str_contains($storageProbe['body'], $testEmail));
    check('Berkas schema database tidak dapat diakses publik', $http->request('anon', 'GET', '/database/schema.sql')['status'] === 404);

    $csrfMissing = $http->request('anon', 'POST', '/masuk', ['email' => $admin['email'], 'password' => $admin['password']]);
    check('POST tanpa token CSRF ditolak 419', $csrfMissing['status'] === 419);

    foreach (['/perpustakaan', '/profil', '/tulis', '/admin'] as $protected) {
        $response = $http->request('anon', 'GET', $protected);
        check('Tamu dialihkan dari ' . $protected . ' ke login', $response['status'] === 302 && str_starts_with(pathOf($response['location']), '/masuk'));
    }

    /* ----------------------------------------------------------------------
       Registrasi, validasi, dan login
       ---------------------------------------------------------------------- */

    $invalidRegister = postWith($http, 'newreader', '/daftar', [
        'display_name' => 'U',
        'email' => 'bukan-email',
        'password' => 'pendek',
        'password_confirm' => 'beda',
    ]);
    check('Registrasi tidak valid ditolak', $invalidRegister['status'] === 302 && locationIs($invalidRegister, '/daftar'));
    $registerForm = $http->request('newreader', 'GET', '/daftar');
    check('Pesan validasi registrasi tampil', str_contains($registerForm['body'], 'minimal') && str_contains($registerForm['body'], 'Email'));

    $preLoginToken = tokenFor($http, 'newreader', '/');
    $register = postWith($http, 'newreader', '/daftar', [
        'display_name' => $testDisplay,
        'email' => $testEmail,
        'password' => $testPassword,
        'password_confirm' => $testPassword,
    ]);
    check('Registrasi valid berhasil dan mengalihkan ke beranda', $register['status'] === 302 && locationIs($register, '/'));

    $postLoginHome = $http->request('newreader', 'GET', '/');
    preg_match('/<meta name="csrf-token" content="([^"]+)">/', $postLoginHome['body'], $loginTokenMatch);
    $postLoginToken = $loginTokenMatch[1] ?? '';
    check(
        'Token CSRF diperbarui setelah login',
        $preLoginToken !== '' && $postLoginToken !== '' && !hash_equals($preLoginToken, $postLoginToken)
    );
    $oldTokenPost = $http->request('newreader', 'POST', '/lapor', [
        '_token' => $preLoginToken,
        'target_type' => 'novel',
        'target_id' => (string) $publishedNovel['id'],
        'reason' => 'spam',
    ]);
    check('Token CSRF lama ditolak setelah login', $oldTokenPost['status'] === 419);
    $tokens = [];

    $testUser = Database::fetch('SELECT id, email, password_hash, role, display_name FROM users WHERE email = ?', [$testEmail]);
    $testUserId = $testUser !== null ? (int) $testUser['id'] : null;
    check('Akun baru tersimpan sebagai reader', $testUser !== null && $testUser['role'] === 'reader');
    check('Kata sandi disimpan sebagai hash password_hash', $testUser !== null && str_starts_with((string) $testUser['password_hash'], '$2y$') && $testUser['password_hash'] !== $testPassword && password_verify($testPassword, (string) $testUser['password_hash']));

    $duplicateRegister = postWith($http, 'dupemail', '/daftar', [
        'display_name' => 'Duplikat ' . $suffix,
        'email' => $testEmail,
        'password' => $testPassword . 'x',
        'password_confirm' => $testPassword . 'x',
    ]);
    $duplicatePage = follow($http, 'dupemail', $duplicateRegister);
    check(
        'Email yang sudah terdaftar ditolak dengan pesan',
        $duplicateRegister['status'] === 302
            && locationIs($duplicateRegister, '/daftar')
            && str_contains($duplicatePage['body'], 'sudah terdaftar')
    );
    $tokens = [];

    $loggedHome = $http->request('newreader', 'GET', '/');
    check('Sesi login aktif setelah registrasi', str_contains($loggedHome['body'], $testDisplay) && str_contains($loggedHome['body'], 'Keluar'));

    /* ----------------------------------------------------------------------
       Verifikasi email: pengiriman, banner, kirim ulang, dan batas backend
       ---------------------------------------------------------------------- */

    $mailDir = BASE_PATH . '/storage/mail';
    $registrationMail = mailFor($mailDir, $testEmail, '/verifikasi-email/');
    if ($registrationMail['file'] !== '') {
        $mailFilesCreated[] = $registrationMail['file'];
    }
    preg_match('#/verifikasi-email/([a-f0-9]{64})#', $registrationMail['raw'], $verificationMatch);
    $verificationToken = $verificationMatch[1] ?? '';
    $verificationRow = Database::fetch('SELECT token_hash, verified_at FROM email_verifications WHERE user_id = ?', [$testUserId]);
    check(
        'Registrasi mengirim email verifikasi bertoken',
        $verificationToken !== ''
            && str_contains($registrationMail['raw'], 'To: ' . $testEmail)
            && $verificationRow !== null
            && $verificationRow['token_hash'] === hash('sha256', $verificationToken)
            && $verificationRow['verified_at'] === null
    );
    check(
        'Pesan sukses registrasi menyebut tautan verifikasi',
        str_contains($postLoginHome['body'], 'Kami mengirim tautan verifikasi')
    );
    check(
        'Banner verifikasi tampil untuk akun yang belum terverifikasi',
        str_contains($loggedHome['body'], 'verifikasi-email/kirim-ulang')
    );

    $unverifiedComment = postWith($http, 'newreader', '/novel/' . $publishedNovel['slug'] . '/komentar', ['content' => 'Komentar sebelum verifikasi ' . $suffix]);
    $unverifiedCommentPage = follow($http, 'newreader', $unverifiedComment);
    check(
        'Komentar ditolak sebelum email terverifikasi',
        $unverifiedComment['status'] === 302
            && locationIs($unverifiedComment, '/novel/' . $publishedNovel['slug'])
            && str_contains($unverifiedCommentPage['body'], 'Verifikasi email Anda terlebih dahulu')
            && dbCount('SELECT COUNT(*) FROM comments WHERE user_id = ?', [$testUserId]) === 0
    );

    $unverifiedRating = postWith($http, 'newreader', '/novel/' . $publishedNovel['slug'] . '/rating', ['rating' => '5']);
    check(
        'Rating ditolak sebelum email terverifikasi',
        $unverifiedRating['status'] === 302
            && locationIs($unverifiedRating, '/novel/' . $publishedNovel['slug'])
            && dbCount('SELECT COUNT(*) FROM ratings WHERE user_id = ?', [$testUserId]) === 0
    );

    $unverifiedAuthor = postWith($http, 'newreader', '/profil/jadi-penulis', []);
    $unverifiedAuthorPage = follow($http, 'newreader', $unverifiedAuthor);
    $roleBeforeVerification = Database::fetch('SELECT role FROM users WHERE id = ?', [$testUserId]);
    check(
        'Akses penulis ditolak sebelum email terverifikasi',
        $unverifiedAuthor['status'] === 302
            && locationIs($unverifiedAuthor, '/profil')
            && str_contains($unverifiedAuthorPage['body'], 'Verifikasi email Anda terlebih dahulu')
            && $roleBeforeVerification['role'] === 'reader'
    );

    $tokensBeforeResend = verificationTokensFor($mailDir, $testEmail);
    $resendVerification = postWith($http, 'newreader', '/verifikasi-email/kirim-ulang', ['redirect' => '/']);
    $resendPage = follow($http, 'newreader', $resendVerification);
    $tokensAfterResend = verificationTokensFor($mailDir, $testEmail);
    $resentToken = array_values(array_diff($tokensAfterResend, $tokensBeforeResend))[0] ?? '';
    $resentRow = Database::fetch('SELECT token_hash FROM email_verifications WHERE user_id = ?', [$testUserId]);
    check(
        'Kirim ulang menerbitkan tautan baru dan membatalkan tautan lama',
        $resendVerification['status'] === 302
            && locationIs($resendVerification, '/')
            && str_contains($resendPage['body'], 'Tautan verifikasi telah dikirim')
            && $resentToken !== ''
            && $resentToken !== $verificationToken
            && $resentRow !== null
            && $resentRow['token_hash'] === hash('sha256', $resentToken)
    );

    $staleTokenPage = $http->request('newreader', 'GET', '/verifikasi-email/' . $verificationToken);
    check(
        'Tautan verifikasi lama tidak dapat digunakan',
        $staleTokenPage['status'] === 200 && str_contains($staleTokenPage['body'], 'Tautan tidak dapat digunakan')
    );

    $verifiedPage = $http->request('newreader', 'GET', '/verifikasi-email/' . $resentToken);
    $verifiedRow = Database::fetch('SELECT verified_at, token_hash FROM email_verifications WHERE user_id = ?', [$testUserId]);
    check(
        'Tautan verifikasi menandai email terverifikasi',
        $verifiedPage['status'] === 200
            && str_contains($verifiedPage['body'], 'Email berhasil diverifikasi')
            && $verifiedRow !== null
            && $verifiedRow['verified_at'] !== null
            && $verifiedRow['token_hash'] === null
    );
    check(
        'Banner verifikasi hilang setelah email terverifikasi',
        !str_contains($http->request('newreader', 'GET', '/')['body'], 'verifikasi-email/kirim-ulang')
    );
    check(
        'Profil menampilkan status email terverifikasi',
        str_contains($http->request('newreader', 'GET', '/profil')['body'], 'Terverifikasi')
    );

    $reuseVerification = $http->request('newreader', 'GET', '/verifikasi-email/' . $resentToken);
    check(
        'Tautan verifikasi hanya dapat dipakai sekali',
        $reuseVerification['status'] === 200 && str_contains($reuseVerification['body'], 'Tautan tidak dapat digunakan')
    );

    $mailCountBeforeVerifiedResend = count(glob($mailDir . '/*.eml') ?: []);
    $verifiedResend = postWith($http, 'newreader', '/verifikasi-email/kirim-ulang', ['redirect' => '/profil']);
    $verifiedResendPage = follow($http, 'newreader', $verifiedResend);
    check(
        'Kirim ulang diabaikan untuk akun yang sudah terverifikasi',
        $verifiedResend['status'] === 302
            && locationIs($verifiedResend, '/profil')
            && str_contains($verifiedResendPage['body'], 'Email Anda sudah terverifikasi')
            && count(glob($mailDir . '/*.eml') ?: []) === $mailCountBeforeVerifiedResend
    );

    /* ----------------------------------------------------------------------
       Verifikasi ulang saat email berubah dan tautan kedaluwarsa
       ---------------------------------------------------------------------- */

    $pendingDisplay = 'Pembaca Tertunda ' . $suffix;
    $pendingPassword = 'RahasiaTertunda!' . $suffix;
    $pendingRegister = postWith($http, 'pendingreader', '/daftar', [
        'display_name' => $pendingDisplay,
        'email' => $pendingEmail,
        'password' => $pendingPassword,
        'password_confirm' => $pendingPassword,
    ]);
    $pendingUser = Database::fetch('SELECT id FROM users WHERE email = ?', [$pendingEmail]);
    $pendingUserId = $pendingUser !== null ? (int) $pendingUser['id'] : null;
    $pendingMail = mailFor($mailDir, $pendingEmail, '/verifikasi-email/');
    if ($pendingMail['file'] !== '') {
        $mailFilesCreated[] = $pendingMail['file'];
    }
    preg_match('#/verifikasi-email/([a-f0-9]{64})#', $pendingMail['raw'], $pendingMatch);
    $pendingToken = $pendingMatch[1] ?? '';
    check(
        'Registrasi akun kedua mengirim tautan verifikasi terpisah',
        $pendingRegister['status'] === 302
            && locationIs($pendingRegister, '/')
            && $pendingUserId !== null
            && $pendingToken !== ''
    );

    $pendingVerifiedPage = $http->request('pendingreader', 'GET', '/verifikasi-email/' . $pendingToken);
    check(
        'Akun kedua dapat memverifikasi email melalui tautan',
        $pendingVerifiedPage['status'] === 200
            && str_contains($pendingVerifiedPage['body'], 'Email berhasil diverifikasi')
            && Database::fetch('SELECT verified_at FROM email_verifications WHERE user_id = ?', [$pendingUserId])['verified_at'] !== null
    );

    $tokens = [];
    $pendingProfileToken = tokenFor($http, 'pendingreader', '/profil');
    $changeEmail = $http->request('pendingreader', 'POST', '/profil', [
        '_token' => $pendingProfileToken,
        'display_name' => $pendingDisplay,
        'email' => $pendingEmail2,
        'bio' => 'Uji verifikasi ulang.',
    ]);
    $pendingMail2 = mailFor($mailDir, $pendingEmail2, '/verifikasi-email/');
    if ($pendingMail2['file'] !== '') {
        $mailFilesCreated[] = $pendingMail2['file'];
    }
    preg_match('#/verifikasi-email/([a-f0-9]{64})#', $pendingMail2['raw'], $pendingMatch2);
    $pendingToken2 = $pendingMatch2[1] ?? '';
    $pendingRow2 = Database::fetch('SELECT verified_at, token_hash FROM email_verifications WHERE user_id = ?', [$pendingUserId]);
    $pendingEmailRow = Database::fetch('SELECT email FROM users WHERE id = ?', [$pendingUserId]);
    check(
        'Mengubah email membatalkan status verifikasi dan mengirim tautan baru',
        $changeEmail['status'] === 302
            && locationIs($changeEmail, '/profil')
            && $pendingToken2 !== ''
            && $pendingRow2 !== null
            && $pendingRow2['verified_at'] === null
            && $pendingRow2['token_hash'] === hash('sha256', $pendingToken2)
            && $pendingEmailRow !== null
            && (string) $pendingEmailRow['email'] === $pendingEmail2
    );

    Database::execute('UPDATE email_verifications SET expires_at = (NOW() - INTERVAL 1 MINUTE) WHERE user_id = ?', [$pendingUserId]);
    $expiredVerificationPage = $http->request('pendingreader', 'GET', '/verifikasi-email/' . $pendingToken2);
    check(
        'Tautan verifikasi kedaluwarsa ditolak',
        $expiredVerificationPage['status'] === 200
            && str_contains($expiredVerificationPage['body'], 'Tautan tidak dapat digunakan')
            && Database::fetch('SELECT verified_at FROM email_verifications WHERE user_id = ?', [$pendingUserId])['verified_at'] === null
    );

    $pendingTokensBefore = verificationTokensFor($mailDir, $pendingEmail2);
    $pendingResend = postWith($http, 'pendingreader', '/verifikasi-email/kirim-ulang', ['redirect' => '/profil']);
    $pendingTokensAfter = verificationTokensFor($mailDir, $pendingEmail2);
    $pendingToken3 = array_values(array_diff($pendingTokensAfter, $pendingTokensBefore))[0] ?? '';
    $pendingVerifiedAgain = $http->request('pendingreader', 'GET', '/verifikasi-email/' . $pendingToken3);
    check(
        'Kirim ulang memulihkan verifikasi setelah tautan kedaluwarsa',
        $pendingResend['status'] === 302
            && $pendingToken3 !== ''
            && $pendingToken3 !== $pendingToken2
            && $pendingVerifiedAgain['status'] === 200
            && str_contains($pendingVerifiedAgain['body'], 'Email berhasil diverifikasi')
            && Database::fetch('SELECT verified_at FROM email_verifications WHERE user_id = ?', [$pendingUserId])['verified_at'] !== null
    );

    /* ----------------------------------------------------------------------
       Peran dan batas akses pembaca
       ---------------------------------------------------------------------- */

    $readerTulis = $http->request('newreader', 'GET', '/tulis');
    check('Pembaca tidak dapat membuka dashboard penulis', $readerTulis['status'] === 403);
    $readerAdmin = $http->request('newreader', 'GET', '/admin');
    check('Pembaca tidak dapat membuka dashboard admin', $readerAdmin['status'] === 403);

    /* ----------------------------------------------------------------------
       Perpustakaan, progres, rating, komentar, laporan
       ---------------------------------------------------------------------- */

    $readerLoginPage = $http->request('newreader', 'GET', '/novel/' . $publishedNovel['slug'] . '/baca/' . (int) $firstChapter['chapter_number']);
    check('Halaman baca mengenali pengguna masuk', str_contains($readerLoginPage['body'], 'data-authenticated="1"') && str_contains($readerLoginPage['body'], 'Progres tersimpan ke akun'));

    $bookmark = postWith($http, 'newreader', '/novel/' . $publishedNovel['slug'] . '/simpan', []);
    check('Tambah novel ke perpustakaan berhasil', $bookmark['status'] === 302 && dbCount('SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND novel_id = ?', [$testUserId, (int) $publishedNovel['id']]) === 1);

    $library = $http->request('newreader', 'GET', '/perpustakaan');
    check('Perpustakaan menampilkan novel favorit', $library['status'] === 200 && str_contains($library['body'], (string) $publishedNovel['title']));

    $chapterId = (int) $firstChapter['id'];
    $progressToken = tokenFor($http, 'newreader', '/');
    $progressResponse = $http->request('newreader', 'POST', '/novel/' . $publishedNovel['slug'] . '/baca/' . (int) $firstChapter['chapter_number'] . '/progres', [
        'percent' => '42',
        '_token' => $progressToken,
    ], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
    $progressJson = json_decode($progressResponse['body'], true);
    check('Progres AJAX tersimpan dengan JSON', $progressResponse['status'] === 200 && is_array($progressJson) && ($progressJson['ok'] ?? false) === true);
    $progressRow = Database::fetch('SELECT chapter_id, progress_percent FROM reading_progress WHERE user_id = ? AND novel_id = ?', [$testUserId, (int) $publishedNovel['id']]);
    check('Progres tersimpan di database', $progressRow !== null && (int) $progressRow['chapter_id'] === $chapterId && abs((float) $progressRow['progress_percent'] - 42) < 0.01);
    check('Bab tercatat sudah dibaca', dbCount('SELECT COUNT(*) FROM chapter_reads WHERE user_id = ? AND chapter_id = ?', [$testUserId, $chapterId]) === 1);

    $progressInvalid = $http->request('newreader', 'POST', '/novel/' . $publishedNovel['slug'] . '/baca/' . (int) $firstChapter['chapter_number'] . '/progres', [
        'percent' => '150',
        '_token' => $progressToken,
    ], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
    check('Progres di luar 0–100 ditolak', $progressInvalid['status'] === 422);

    $libraryStory = $http->request('newreader', 'GET', '/perpustakaan');
    check('Riwayat membaca tampil di perpustakaan', str_contains($libraryStory['body'], (string) $publishedNovel['title']) && str_contains($libraryStory['body'], '42'));

    $removeBookmark = postWith($http, 'newreader', '/perpustakaan/' . $publishedNovel['slug'] . '/hapus', []);
    check('Hapus novel dari perpustakaan bekerja', $removeBookmark['status'] === 302 && dbCount('SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND novel_id = ?', [$testUserId, (int) $publishedNovel['id']]) === 0);

    $emptyComment = postWith($http, 'newreader', '/novel/' . $publishedNovel['slug'] . '/komentar', ['content' => '']);
    $emptyCommentPage = follow($http, 'newreader', $emptyComment);
    check('Komentar kosong ditolak dengan pesan validasi', $emptyComment['status'] === 302 && str_contains($emptyCommentPage['body'], 'Komentar wajib diisi'));

    $xssComment = '<script>alert("xss")</script> Komentar uji ' . $suffix;
    $commentResponse = postWith($http, 'newreader', '/novel/' . $publishedNovel['slug'] . '/komentar', ['content' => $xssComment]);
    $commentRow = Database::fetch('SELECT id FROM comments WHERE user_id = ? AND novel_id = ? ORDER BY id DESC LIMIT 1', [$testUserId, (int) $publishedNovel['id']]);
    $commentId = $commentRow !== null ? (int) $commentRow['id'] : null;
    check('Komentar valid tersimpan', $commentResponse['status'] === 302 && $commentId !== null);
    $commentPage = $http->request('newreader', 'GET', '/novel/' . $publishedNovel['slug'] . '#komentar');
    check('Komentar berbahaya di-escape saat ditampilkan', !str_contains($commentPage['body'], '<script>alert("xss")</script>') && str_contains($commentPage['body'], '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'));

    $ratingResponse = postWith($http, 'newreader', '/novel/' . $publishedNovel['slug'] . '/rating', ['rating' => '4']);
    $ratingRow = Database::fetch('SELECT rating FROM ratings WHERE user_id = ? AND novel_id = ?', [$testUserId, (int) $publishedNovel['id']]);
    check('Rating pembaca tersimpan', $ratingResponse['status'] === 302 && $ratingRow !== null && (int) $ratingRow['rating'] === 4);

    $invalidRating = postWith($http, 'newreader', '/novel/' . $publishedNovel['slug'] . '/rating', ['rating' => '9']);
    $invalidRatingPage = follow($http, 'newreader', $invalidRating);
    $ratingAfter = Database::fetch('SELECT rating FROM ratings WHERE user_id = ? AND novel_id = ?', [$testUserId, (int) $publishedNovel['id']]);
    check('Rating tidak valid ditolak dan tidak mengubah data', $invalidRating['status'] === 302 && str_contains($invalidRatingPage['body'], 'Pilih rating antara 1 sampai 5') && (int) $ratingAfter['rating'] === 4);

    $reportResponse = postWith($http, 'newreader', '/lapor', [
        'target_type' => 'novel',
        'target_id' => (string) $publishedNovel['id'],
        'reason' => 'spam',
        'details' => 'Laporan uji otomatis ' . $suffix,
    ]);
    $reportRow = Database::fetch('SELECT id, status FROM reports WHERE reporter_id = ? ORDER BY id DESC LIMIT 1', [$testUserId]);
    $reportId = $reportRow !== null ? (int) $reportRow['id'] : null;
    check('Laporan pembaca tersimpan sebagai open', $reportResponse['status'] === 302 && $reportRow !== null && $reportRow['status'] === 'open');

    $draftCommentId = Database::insert(
        'INSERT INTO comments (novel_id, user_id, content, status, created_at) VALUES (?, ?, ?, \'visible\', NOW())',
        [(int) $draftNovel['id'], $testUserId, 'Komentar pada novel draf untuk pengujian.']
    );
    $draftCommentReport = postWith($http, 'newreader', '/lapor', [
        'target_type' => 'comment',
        'target_id' => (string) $draftCommentId,
        'reason' => 'spam',
    ]);
    check(
        'Komentar pada novel draf tidak dapat dilaporkan',
        $draftCommentReport['status'] === 404
            && dbCount('SELECT COUNT(*) FROM reports WHERE comment_id = ?', [$draftCommentId]) === 0
    );
    Database::execute('DELETE FROM comments WHERE id = ?', [$draftCommentId]);
    $draftCommentId = null;

    /* ----------------------------------------------------------------------
       Profil dan perubahan kata sandi
       ---------------------------------------------------------------------- */

    $profileToken = tokenFor($http, 'newreader', '/profil');
    $profileUpdate = $http->request('newreader', 'POST', '/profil', [
        '_token' => $profileToken,
        'display_name' => $testDisplay . ' Baru',
        'email' => $testEmail,
        'bio' => 'Bio uji otomatis.',
    ]);
    $profileRow = Database::fetch('SELECT display_name, bio, email FROM users WHERE id = ?', [$testUserId]);
    check('Profil berhasil diperbarui', $profileUpdate['status'] === 302 && $profileRow['display_name'] === $testDisplay . ' Baru' && $profileRow['bio'] === 'Bio uji otomatis.');

    Database::insert(
        'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, (NOW() + INTERVAL 1 HOUR), NOW())',
        [$testUserId, hash('sha256', bin2hex(random_bytes(32)))]
    );
    $passwordToken = tokenFor($http, 'newreader', '/profil');
    $passwordUpdate = $http->request('newreader', 'POST', '/profil/kata-sandi', [
        '_token' => $passwordToken,
        'current_password' => $testPassword,
        'new_password' => $newPassword,
        'new_password_confirm' => $newPassword,
    ]);
    $passwordRow = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$testUserId]);
    check('Kata sandi berhasil diubah dan di-hash ulang', $passwordUpdate['status'] === 302 && password_verify($newPassword, (string) $passwordRow['password_hash']) && !password_verify($testPassword, (string) $passwordRow['password_hash']));
    check(
        'Perubahan kata sandi mandiri membatalkan tautan reset yang tertunda',
        dbCount('SELECT COUNT(*) FROM password_resets WHERE user_id = ?', [$testUserId]) === 0
    );
    $tokens = [];

    $logoutToken = tokenFor($http, 'newreader', '/');
    $logout = $http->request('newreader', 'POST', '/keluar', ['_token' => $logoutToken]);
    check('Logout berhasil mengakhiri sesi', $logout['status'] === 302 && $http->request('newreader', 'GET', '/profil')['status'] === 302);
    $tokens = [];

    $oldLogin = postWith($http, 'newreader', '/masuk', ['email' => $testEmail, 'password' => $testPassword]);
    check('Kata sandi lama tidak dapat digunakan', $oldLogin['status'] === 302 && locationIs($oldLogin, '/masuk'));
    $newLogin = postWith($http, 'newreader', '/masuk', ['email' => $testEmail, 'password' => $newPassword]);
    check('Kata sandi baru dapat digunakan untuk masuk', $newLogin['status'] === 302 && locationIs($newLogin, '/'));
    $tokens = [];

    $becomeAuthor = postWith($http, 'newreader', '/profil/jadi-penulis', []);
    $roleRow = Database::fetch('SELECT role FROM users WHERE id = ?', [$testUserId]);
    check('Pembaca dapat mengaktifkan akses penulis', $becomeAuthor['status'] === 302 && $roleRow['role'] === 'author');
    check('Akses dashboard penulis aktif setelah upgrade', $http->request('newreader', 'GET', '/tulis')['status'] === 200);

    $newreaderLogout = $http->request('newreader', 'POST', '/keluar', ['_token' => tokenFor($http, 'newreader', '/')]);
    check('Sesi pengguna uji baru ditutup', $newreaderLogout['status'] === 302);

    /* ----------------------------------------------------------------------
       Penulis: dashboard, isolasi kepemilikan, tulis, publikasi, sampul
       ---------------------------------------------------------------------- */

    $authorLogin = postWith($http, 'author', '/masuk', ['email' => $author['email'], 'password' => $author['password']]);
    check('Login penulis berhasil', $authorLogin['status'] === 302 && locationIs($authorLogin, '/'));
    $tokens = [];

    $authorDashboard = $http->request('author', 'GET', '/tulis');
    check('Dashboard penulis merespons 200', $authorDashboard['status'] === 200);
    check('Dashboard penulis menampilkan novel miliknya', str_contains($authorDashboard['body'], (string) $ownNovel['title']));
    check('Penulis dapat membuka edit novel miliknya', $http->request('author', 'GET', '/tulis/novel/' . (int) $ownNovel['id'] . '/edit')['status'] === 200);
    check('Penulis tidak dapat membuka edit novel penulis lain', $http->request('author', 'GET', '/tulis/novel/' . (int) $otherNovel['id'] . '/edit')['status'] === 404);

    $foreignUpdate = postWith($http, 'author', '/tulis/novel/' . (int) $otherNovel['id'], [
        'title' => 'Percobaan Ambil Alih',
        'synopsis' => str_repeat('Percobaan mengubah novel milik penulis lain. ', 2),
        'status' => 'ongoing',
        'categories' => ['1'],
    ]);
    check('POST edit novel penulis lain ditolak backend', $foreignUpdate['status'] === 404 && Database::fetch('SELECT title FROM novels WHERE id = ?', [(int) $otherNovel['id']])['title'] === $otherNovel['title']);

    $otherChapter = Database::fetch('SELECT id FROM chapters WHERE novel_id = ? ORDER BY id LIMIT 1', [(int) $otherNovel['id']]);
    if ($otherChapter !== null) {
        check('Penulis tidak dapat mengedit bab novel lain', $http->request('author', 'GET', '/tulis/bab/' . (int) $otherChapter['id'] . '/edit')['status'] === 404);
    }

    $createNovel = postWith($http, 'author', '/tulis/novel', [
        'title' => 'Uji Integrasi ' . $suffix,
        'synopsis' => 'Novel ini dibuat oleh pengujian otomatis untuk memverifikasi alur penulis, publikasi bab, sampul, dan pembersihan data. Seluruh isi bersifat orisinal dan hanya untuk pengujian.',
        'status' => 'ongoing',
        'categories' => ['1', '2'],
    ]);
    $testNovel = Database::fetch('SELECT id, slug, title, publish_status, cover_path FROM novels WHERE title = ?', ['Uji Integrasi ' . $suffix]);
    $testNovelId = $testNovel !== null ? (int) $testNovel['id'] : null;
    $testNovelSlug = $testNovel !== null ? (string) $testNovel['slug'] : null;
    check('Novel baru dibuat sebagai draf', $createNovel['status'] === 302 && $testNovel !== null && $testNovel['publish_status'] === 'draft');
    check('Genre novel baru tersimpan', $testNovelId !== null && dbCount('SELECT COUNT(*) FROM novel_categories WHERE novel_id = ?', [$testNovelId]) === 2);

    $publishWithoutChapter = postWith($http, 'author', '/tulis/novel/' . $testNovelId . '/publikasi', ['action' => 'publish']);
    check('Publikasi ditolak sebelum ada bab terbit', $publishWithoutChapter['status'] === 302 && Database::fetch('SELECT publish_status FROM novels WHERE id = ?', [$testNovelId])['publish_status'] === 'draft');

    $chapterContent = "Langit sore itu berwarna tembaga. Aira menutup buku catatan dan melangkah ke tepi dermaga, tempat perahu tua bergoyang pelan. "
        . "Ia menghitung ombak yang datang, satu per satu, seperti menghitung alasan untuk tetap tinggal. Di kejauhan, mercusuar mulai menyala. "
        . "Cahayanya jatuh ke air dan pecah menjadi serpihan emas yang tidak pernah bisa digenggam. Malam ini, ia berjanji akan mendengarkan cerita ayahnya sampai selesai.";
    $createChapter = postWith($http, 'author', '/tulis/novel/' . $testNovelId . '/bab', [
        'title' => 'Bab Uji: Mercusuar',
        'content' => $chapterContent,
        'status' => 'draft',
    ]);
    $testChapter = Database::fetch('SELECT id, chapter_number, status FROM chapters WHERE novel_id = ? ORDER BY id DESC LIMIT 1', [$testNovelId]);
    check('Bab baru tersimpan sebagai draf', $createChapter['status'] === 302 && $testChapter !== null && $testChapter['status'] === 'draft' && (int) $testChapter['chapter_number'] === 1);

    $editChapter = postWith($http, 'author', '/tulis/bab/' . (int) $testChapter['id'], [
        'title' => 'Bab Uji: Mercusuar dan Ombak',
        'content' => $chapterContent . ' Angin membawa aroma garam dan kayu basah.',
        'status' => 'published',
    ]);
    $editedChapter = Database::fetch('SELECT title, status FROM chapters WHERE id = ?', [(int) $testChapter['id']]);
    check('Bab dapat diedit dan diterbitkan', $editChapter['status'] === 302 && $editedChapter['status'] === 'published' && str_contains((string) $editedChapter['title'], 'Ombak'));

    $publishNovel = postWith($http, 'author', '/tulis/novel/' . $testNovelId . '/publikasi', ['action' => 'publish']);
    $publishedTestNovel = Database::fetch('SELECT publish_status, slug FROM novels WHERE id = ?', [$testNovelId]);
    $testNovelSlug = (string) $publishedTestNovel['slug'];
    check('Novel dapat dipublikasikan setelah bab terbit', $publishNovel['status'] === 302 && $publishedTestNovel['publish_status'] === 'published');
    check('Novel uji dapat dibaca publik', $http->request('anon', 'GET', '/novel/' . $testNovelSlug)['status'] === 200);
    check('Bab novel uji dapat dibaca publik', $http->request('anon', 'GET', '/novel/' . $testNovelSlug . '/baca/1')['status'] === 200);

    $draftChapterResponse = postWith($http, 'author', '/tulis/novel/' . $testNovelId . '/bab', [
        'title' => 'Bab Uji: Draf Tersembunyi',
        'content' => str_repeat('Isi bab draf ini hanya untuk menguji riwayat membaca. ', 3),
        'status' => 'draft',
    ]);
    $draftChapterRow = Database::fetch(
        'SELECT id, chapter_number, status FROM chapters WHERE novel_id = ? ORDER BY chapter_number DESC LIMIT 1',
        [$testNovelId]
    );
    check(
        'Bab draf tambahan tersimpan dengan nomor berikutnya',
        $draftChapterResponse['status'] === 302
            && $draftChapterRow !== null
            && $draftChapterRow['status'] === 'draft'
            && (int) $draftChapterRow['chapter_number'] === 2
    );

    if ($draftChapterRow !== null) {
        $draftChapterId = (int) $draftChapterRow['id'];
        Database::execute(
            'INSERT INTO reading_progress (user_id, novel_id, chapter_id, progress_percent, updated_at)
             VALUES (?, ?, ?, 55, NOW())
             ON DUPLICATE KEY UPDATE chapter_id = VALUES(chapter_id), progress_percent = VALUES(progress_percent), updated_at = NOW()',
            [$testUserId, $testNovelId, $draftChapterId]
        );
        BookmarkModel::add($testUserId, $testNovelId);

        $historyRows = ReadingProgressModel::history($testUserId, 20);
        $historyItem = null;
        foreach ($historyRows as $row) {
            if ((int) $row['novel_id'] === $testNovelId) {
                $historyItem = $row;
                break;
            }
        }
        check(
            'Riwayat tidak menautkan bab draf',
            $historyItem !== null && $historyItem['chapter_number'] === null && $historyItem['progress_percent'] === null
        );

        $continueRow = ReadingProgressModel::continueReading($testUserId);
        check(
            'Lanjutkan bacaan mengabaikan bab draf',
            $continueRow !== null
                && (int) $continueRow['novel_id'] === $testNovelId
                && $continueRow['chapter_number'] === null
        );

        $bookmarkRows = BookmarkModel::listForUser($testUserId);
        $bookmarkItem = null;
        foreach ($bookmarkRows as $row) {
            if ((int) $row['id'] === $testNovelId) {
                $bookmarkItem = $row;
                break;
            }
        }
        check(
            'Kartu perpustakaan mengabaikan bab draf',
            $bookmarkItem !== null && $bookmarkItem['last_chapter'] === null && $bookmarkItem['progress_percent'] === null
        );

        Database::execute('DELETE FROM reading_progress WHERE user_id = ? AND novel_id = ?', [$testUserId, $testNovelId]);
        BookmarkModel::remove($testUserId, $testNovelId);
    }

    $pngPath = $tmp . '/cover-' . $suffix . '.png';
    makePng($pngPath, 240, 360);
    $coverToken = tokenFor($http, 'author', '/tulis/novel/' . $testNovelId . '/edit');
    $coverUpload = $http->request('author', 'POST', '/tulis/novel/' . $testNovelId . '/sampul', [
        '_token' => $coverToken,
        'cover' => new CURLFile($pngPath, 'image/png', 'sampul.png'),
    ], [], true);
    $coverRow = Database::fetch('SELECT cover_path FROM novels WHERE id = ?', [$testNovelId]);
    $testCoverPath = $coverRow !== null ? (string) $coverRow['cover_path'] : null;
    check('Sampul PNG valid berhasil diunggah', $coverUpload['status'] === 302 && $testCoverPath !== null && str_starts_with($testCoverPath, 'uploads/covers/'));
    check('Berkas sampul tersimpan di luar source', $testCoverPath !== null && is_file(BASE_PATH . '/public/' . $testCoverPath));
    $coverFetch = $http->request('anon', 'GET', '/' . $testCoverPath);
    $coverType = implode(' ', $coverFetch['headers']['content-type'] ?? []);
    check('Sampul terunggah dapat diambil kembali sebagai gambar', $coverFetch['status'] === 200 && stripos($coverType, 'image/png') !== false);

    $invalidCoverPath = $tmp . '/bukan-gambar-' . $suffix . '.png';
    file_put_contents($invalidCoverPath, 'ini bukan gambar');
    $invalidCover = $http->request('author', 'POST', '/tulis/novel/' . $testNovelId . '/sampul', [
        '_token' => $coverToken,
        'cover' => new CURLFile($invalidCoverPath, 'image/png', 'palsu.png'),
    ], [], true);
    $coverAfterInvalid = Database::fetch('SELECT cover_path FROM novels WHERE id = ?', [$testNovelId]);
    check('Berkas bukan gambar ditolak dan sampul lama dipertahankan', $invalidCover['status'] === 302 && $coverAfterInvalid['cover_path'] === $testCoverPath);

    /* ----------------------------------------------------------------------
       Admin: dashboard, pembatasan, moderasi, genre, laporan
       ---------------------------------------------------------------------- */

    $adminLogin = postWith($http, 'admin', '/masuk', ['email' => $admin['email'], 'password' => $admin['password']]);
    check('Login admin berhasil', $adminLogin['status'] === 302);
    $tokens = [];

    $adminIds = Database::fetch('SELECT id, role FROM users WHERE email = ?', [$admin['email']]);
    $adminDashboard = $http->request('admin', 'GET', '/admin');
    check('Dashboard admin merespons 200', $adminDashboard['status'] === 200 && str_contains($adminDashboard['body'], 'Ringkasan'));
    foreach (['/admin/pengguna', '/admin/novel', '/admin/bab', '/admin/genre', '/admin/komentar', '/admin/laporan'] as $adminPage) {
        $response = $http->request('admin', 'GET', $adminPage);
        check('Halaman admin ' . $adminPage . ' merespons 200', $response['status'] === 200);
    }
    check(
        'Daftar pengguna admin menampilkan status verifikasi',
        str_contains($http->request('admin', 'GET', '/admin/pengguna')['body'], 'Terverifikasi')
    );

    check('Penulis tidak dapat membuka dashboard admin', $http->request('author', 'GET', '/admin')['status'] === 403);

    $adminCsrf = $http->request('admin', 'POST', '/admin/genre', ['name' => 'Tanpa Token']);
    check('Aksi admin tanpa CSRF ditolak', $adminCsrf['status'] === 419);

    $selfDemote = postWith($http, 'admin', '/admin/pengguna/' . (int) $adminIds['id'], [
        'role' => 'reader',
        'is_active' => '1',
    ]);
    $selfPage = follow($http, 'admin', $selfDemote);
    $adminAfter = Database::fetch('SELECT role FROM users WHERE id = ?', [(int) $adminIds['id']]);
    check('Admin tidak dapat menurunkan perannya sendiri', $selfDemote['status'] === 302 && str_contains($selfPage['body'], 'tidak dapat mengubah peran') && $adminAfter['role'] === 'admin');

    $demoteTestUser = postWith($http, 'admin', '/admin/pengguna/' . $testUserId, [
        'role' => 'reader',
        'is_active' => '1',
    ]);
    check('Admin dapat mengubah peran pengguna lain', $demoteTestUser['status'] === 302 && Database::fetch('SELECT role FROM users WHERE id = ?', [$testUserId])['role'] === 'reader');

    $genreName = 'Genre Uji ' . $suffix;
    $genreCreate = postWith($http, 'admin', '/admin/genre', ['name' => $genreName, 'description' => 'Genre untuk pengujian otomatis.']);
    $genreRow = Database::fetch('SELECT id FROM categories WHERE name = ?', [$genreName]);
    $genreId = $genreRow !== null ? (int) $genreRow['id'] : null;
    check('Admin dapat menambah genre', $genreCreate['status'] === 302 && $genreId !== null);

    $genreUpdate = postWith($http, 'admin', '/admin/genre/' . $genreId, ['name' => $genreName . ' Baru', 'description' => 'Diperbarui.']);
    check('Admin dapat memperbarui genre', $genreUpdate['status'] === 302 && dbCount('SELECT COUNT(*) FROM categories WHERE name = ?', [$genreName . ' Baru']) === 1);

    $genreDelete = postWith($http, 'admin', '/admin/genre/' . $genreId . '/hapus', []);
    check('Admin dapat menghapus genre yang tidak dipakai', $genreDelete['status'] === 302 && dbCount('SELECT COUNT(*) FROM categories WHERE id = ?', [$genreId]) === 0);
    if (dbCount('SELECT COUNT(*) FROM categories WHERE id = ?', [$genreId]) === 0) {
        $genreId = null;
    }

    $hideComment = postWith($http, 'admin', '/admin/komentar/' . $commentId . '/status', ['action' => 'hide']);
    check('Admin dapat menyembunyikan komentar', $hideComment['status'] === 302 && Database::fetch('SELECT status FROM comments WHERE id = ?', [$commentId])['status'] === 'hidden');
    check('Komentar tersembunyi tidak tampil publik', !str_contains($http->request('anon', 'GET', '/novel/' . $publishedNovel['slug'] . '#komentar')['body'], 'Komentar uji ' . $suffix));

    $showComment = postWith($http, 'admin', '/admin/komentar/' . $commentId . '/status', ['action' => 'show']);
    check('Admin dapat menampilkan kembali komentar', $showComment['status'] === 302 && Database::fetch('SELECT status FROM comments WHERE id = ?', [$commentId])['status'] === 'visible');

    $deleteComment = postWith($http, 'admin', '/admin/komentar/' . $commentId . '/hapus', []);
    check('Admin dapat menghapus komentar permanen', $deleteComment['status'] === 302 && dbCount('SELECT COUNT(*) FROM comments WHERE id = ?', [$commentId]) === 0);
    $commentId = null;

    $reportsPage = $http->request('admin', 'GET', '/admin/laporan?status=open');
    check('Laporan pembaca tampil di moderasi admin', $reportsPage['status'] === 200 && str_contains($reportsPage['body'], 'Laporan uji otomatis ' . $suffix));
    $resolve = postWith($http, 'admin', '/admin/laporan/' . $reportId . '/selesai', []);
    $reportAfter = Database::fetch('SELECT status, resolved_by FROM reports WHERE id = ?', [$reportId]);
    check('Admin dapat menyelesaikan laporan', $resolve['status'] === 302 && $reportAfter['status'] === 'resolved' && (int) $reportAfter['resolved_by'] === (int) $adminIds['id']);
    $reportId = null;

    $featured = postWith($http, 'admin', '/admin/novel/' . $testNovelId . '/unggulan', []);
    check('Admin dapat menandai novel unggulan', $featured['status'] === 302 && (int) Database::fetch('SELECT is_featured FROM novels WHERE id = ?', [$testNovelId])['is_featured'] === 1);

    $unpublish = postWith($http, 'admin', '/admin/novel/' . $testNovelId . '/publikasi', ['action' => 'unpublish']);
    check('Admin dapat mengembalikan novel ke draf', $unpublish['status'] === 302 && Database::fetch('SELECT publish_status FROM novels WHERE id = ?', [$testNovelId])['publish_status'] === 'draft' && (int) Database::fetch('SELECT is_featured FROM novels WHERE id = ?', [$testNovelId])['is_featured'] === 0);
    check('Novel draf kembali tidak dapat diakses publik', $http->request('anon', 'GET', '/novel/' . $testNovelSlug)['status'] === 404);

    $republish = postWith($http, 'admin', '/admin/novel/' . $testNovelId . '/publikasi', ['action' => 'publish']);
    check('Admin dapat menerbitkan kembali novel', $republish['status'] === 302 && Database::fetch('SELECT publish_status FROM novels WHERE id = ?', [$testNovelId])['publish_status'] === 'published');

    $resetVictimLogin = postWith($http, 'resetvictim', '/masuk', ['email' => $testEmail, 'password' => $newPassword]);
    check('Sesi pengguna aktif sebelum admin mereset kata sandi', $resetVictimLogin['status'] === 302 && locationIs($resetVictimLogin, '/') && $http->request('resetvictim', 'GET', '/profil')['status'] === 200);

    Database::insert(
        'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, (NOW() + INTERVAL 1 HOUR), NOW())',
        [$testUserId, hash('sha256', bin2hex(random_bytes(32)))]
    );
    $reset = postWith($http, 'admin', '/admin/pengguna/' . $testUserId . '/reset-sandi', []);
    $resetPage = follow($http, 'admin', $reset);
    preg_match('/Kata sandi sementara untuk ' . preg_quote($testEmail, '/') . ': ([A-Za-z0-9]+)\./', $resetPage['body'], $resetMatch);
    $temporaryPassword = $resetMatch[1] ?? '';
    $resetHash = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$testUserId]);
    check('Admin dapat mereset kata sandi menjadi nilai acak', $reset['status'] === 302 && $temporaryPassword !== '' && password_verify($temporaryPassword, (string) $resetHash['password_hash']));
    check('Kata sandi sementara tidak disimpan sebagai teks biasa', str_contains((string) $resetHash['password_hash'], $temporaryPassword) === false);
    check(
        'Reset kata sandi oleh admin membatalkan tautan reset mandiri',
        dbCount('SELECT COUNT(*) FROM password_resets WHERE user_id = ?', [$testUserId]) === 0
    );

    $victimAfterReset = $http->request('resetvictim', 'GET', '/profil');
    check('Reset kata sandi admin mengakhiri sesi pengguna yang masih aktif', $victimAfterReset['status'] === 302 && locationIs($victimAfterReset, '/masuk'));

    $tempLogin = postWith($http, 'templogin', '/masuk', ['email' => $testEmail, 'password' => $temporaryPassword]);
    check('Kata sandi sementara hasil reset dapat digunakan', $tempLogin['status'] === 302 && locationIs($tempLogin, '/'));
    $tokens = [];
    $tempLogout = $http->request('templogin', 'POST', '/keluar', ['_token' => tokenFor($http, 'templogin', '/')]);
    check('Sesi hasil reset ditutup', $tempLogout['status'] === 302);

    /* ----------------------------------------------------------------------
       Lupa dan reset kata sandi melalui email
       ---------------------------------------------------------------------- */

    $mailDir = BASE_PATH . '/storage/mail';
    $mailBefore = glob($mailDir . '/*.eml') ?: [];

    $forgotPage = $http->request('resetflow', 'GET', '/lupa-sandi');
    check(
        'Halaman lupa kata sandi merespons 200',
        $forgotPage['status'] === 200 && str_contains($forgotPage['body'], 'Kirim tautan reset')
    );
    $loginWithForgot = $http->request('resetguest', 'GET', '/masuk');
    check(
        'Halaman masuk menautkan lupa kata sandi',
        $loginWithForgot['status'] === 200 && str_contains($loginWithForgot['body'], '/lupa-sandi')
    );

    $guestResetRedirect = $http->request('admin', 'GET', '/lupa-sandi');
    check(
        'Pengguna yang sudah masuk dialihkan dari halaman lupa sandi',
        $guestResetRedirect['status'] === 302 && locationIs($guestResetRedirect, '/')
    );

    $sendReset = postWith($http, 'resetflow', '/lupa-sandi', ['email' => $testEmail]);
    $sendResetPage = follow($http, 'resetflow', $sendReset);
    check(
        'Permintaan reset selalu memberi pesan umum',
        $sendReset['status'] === 302 && locationIs($sendReset, '/lupa-sandi')
            && str_contains($sendResetPage['body'], 'Jika email tersebut terdaftar')
    );

    $mailAfter = glob($mailDir . '/*.eml') ?: [];
    $newMailFiles = array_values(array_diff($mailAfter, $mailBefore));
    $mailFilesCreated = array_merge($mailFilesCreated, $newMailFiles);
    $resetMailBody = '';
    foreach ($newMailFiles as $mailFile) {
        $content = (string) file_get_contents($mailFile);
        if (str_contains($content, 'To: ' . $testEmail)) {
            $resetMailBody = $content;
            break;
        }
    }
    check(
        'Email reset ditulis ke kotak surat lokal',
        $resetMailBody !== '' && str_contains($resetMailBody, 'Content-Type: text/plain')
    );
    preg_match('#/reset-sandi/([a-f0-9]{64})#', $resetMailBody, $resetTokenMatch);
    $resetToken = $resetTokenMatch[1] ?? '';
    check('Email reset memuat tautan bertoken', $resetToken !== '');

    $resetStored = Database::fetch('SELECT token_hash FROM password_resets WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$testUserId]);
    check(
        'Token reset disimpan sebagai hash dan bukan teks asli',
        $resetStored !== null
            && $resetStored['token_hash'] === hash('sha256', $resetToken)
            && $resetStored['token_hash'] !== $resetToken
    );

    $resetFormPage = $http->request('resetflow', 'GET', '/reset-sandi/' . $resetToken);
    check(
        'Halaman reset menampilkan formulir kata sandi baru',
        $resetFormPage['status'] === 200
            && str_contains($resetFormPage['body'], 'Simpan kata sandi baru')
            && str_contains($resetFormPage['body'], $resetToken)
    );

    $invalidResetPage = $http->request('resetflow', 'GET', '/reset-sandi/' . str_repeat('a', 64));
    check(
        'Token yang tidak dikenal menampilkan halaman tautan tidak valid',
        $invalidResetPage['status'] === 200
            && str_contains($invalidResetPage['body'], 'Tautan tidak dapat digunakan')
            && !str_contains($invalidResetPage['body'], 'Simpan kata sandi baru')
    );

    $weakReset = postWith($http, 'resetflow', '/reset-sandi', [
        'token' => $resetToken,
        'password' => 'pendek',
        'password_confirm' => 'beda',
    ]);
    $weakResetPage = follow($http, 'resetflow', $weakReset);
    check(
        'Kata sandi baru yang lemah atau tidak cocok ditolak',
        $weakReset['status'] === 302 && locationIs($weakReset, '/reset-sandi/' . $resetToken)
            && str_contains($weakResetPage['body'], 'minimal 8 karakter')
    );

    $resetPassword = 'SandiPulih!' . $suffix;
    $resetSubmit = postWith($http, 'resetflow', '/reset-sandi', [
        'token' => $resetToken,
        'password' => $resetPassword,
        'password_confirm' => $resetPassword,
    ]);
    $resetSubmitPage = follow($http, 'resetflow', $resetSubmit);
    $resetHash = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$testUserId]);
    check(
        'Reset kata sandi memperbarui hash dan mengalihkan ke halaman masuk',
        $resetSubmit['status'] === 302 && locationIs($resetSubmit, '/masuk')
            && password_verify($resetPassword, (string) ($resetHash['password_hash'] ?? ''))
            && str_contains($resetSubmitPage['body'], 'Kata sandi berhasil diperbarui')
    );
    check(
        'Token reset dihapus setelah berhasil dipakai',
        dbCount('SELECT COUNT(*) FROM password_resets WHERE user_id = ?', [$testUserId]) === 0
    );

    $oldResetLogin = postWith($http, 'resetold', '/masuk', ['email' => $testEmail, 'password' => $temporaryPassword]);
    check(
        'Kata sandi lama tidak berlaku setelah reset email',
        $oldResetLogin['status'] === 302 && locationIs($oldResetLogin, '/masuk')
    );
    $newResetLogin = postWith($http, 'resetflow', '/masuk', ['email' => $testEmail, 'password' => $resetPassword]);
    check(
        'Kata sandi hasil reset dapat digunakan untuk masuk',
        $newResetLogin['status'] === 302 && locationIs($newResetLogin, '/')
    );
    $tokens = [];

    $reuseReset = postWith($http, 'resetreuse', '/reset-sandi', [
        'token' => $resetToken,
        'password' => 'SandiLain!' . $suffix,
        'password_confirm' => 'SandiLain!' . $suffix,
    ]);
    $reuseResetPage = follow($http, 'resetreuse', $reuseReset);
    check(
        'Token reset tidak dapat digunakan dua kali',
        $reuseReset['status'] === 302 && locationIs($reuseReset, '/lupa-sandi')
            && str_contains($reuseResetPage['body'], 'sudah digunakan')
    );

    $expiredToken = bin2hex(random_bytes(32));
    Database::insert(
        'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, (NOW() - INTERVAL 1 MINUTE), NOW())',
        [$testUserId, hash('sha256', $expiredToken)]
    );
    $expiredReset = postWith($http, 'resetexpired', '/reset-sandi', [
        'token' => $expiredToken,
        'password' => 'SandiKedaluwarsa!' . $suffix,
        'password_confirm' => 'SandiKedaluwarsa!' . $suffix,
    ]);
    $expiredResetPage = follow($http, 'resetexpired', $expiredReset);
    $hashAfterExpired = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$testUserId]);
    check(
        'Token kedaluwarsa ditolak dan kata sandi tidak berubah',
        $expiredReset['status'] === 302 && locationIs($expiredReset, '/lupa-sandi')
            && str_contains($expiredResetPage['body'], 'kedaluwarsa')
            && password_verify($resetPassword, (string) ($hashAfterExpired['password_hash'] ?? ''))
    );

    $unknownBefore = count(glob($mailDir . '/*.eml') ?: []);
    $unknownSend = postWith($http, 'resetunknown', '/lupa-sandi', ['email' => 'tidak.ada.' . $suffix . '@example.test']);
    $unknownPage = follow($http, 'resetunknown', $unknownSend);
    $unknownAfter = count(glob($mailDir . '/*.eml') ?: []);
    check(
        'Email tak terdaftar mendapat pesan umum tanpa pengiriman email',
        $unknownSend['status'] === 302 && locationIs($unknownSend, '/lupa-sandi')
            && str_contains($unknownPage['body'], 'Jika email tersebut terdaftar')
            && $unknownAfter === $unknownBefore
    );

    /* ----------------------------------------------------------------------
       Pembersihan novel uji oleh penulis + validasi database
       ---------------------------------------------------------------------- */

    $deleteNovel = postWith($http, 'author', '/tulis/novel/' . $testNovelId . '/hapus', []);
    check('Penulis dapat menghapus novel miliknya', $deleteNovel['status'] === 302 && dbCount('SELECT COUNT(*) FROM novels WHERE id = ?', [$testNovelId]) === 0);
    if ($testCoverPath !== null) {
        clearstatcache(true, BASE_PATH . '/public/' . $testCoverPath);
    }
    check('Sampul novel uji ikut dihapus dari disk', $testCoverPath === null || !is_file(BASE_PATH . '/public/' . $testCoverPath));
    $testNovelId = null;

    $pdo = Database::pdo();
    $pdo->beginTransaction();
    $duplicateBlocked = false;
    try {
        $pdo->prepare('INSERT INTO chapters (novel_id, chapter_number, title, content, status) VALUES (?, ?, ?, ?, ?)')
            ->execute([(int) $publishedNovel['id'], (int) $firstChapter['chapter_number'], 'Duplikat', 'Isi uji duplikat.', 'draft']);
    } catch (PDOException $exception) {
        $duplicateBlocked = true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
    check('Nomor bab duplikat dalam satu novel ditolak database', $duplicateBlocked);

    $adminHash = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [(int) $adminIds['id']]);
    check('Kata sandi admin tidak disimpan sebagai teks biasa', $adminHash !== null && str_contains((string) $adminHash['password_hash'], $admin['password']) === false && str_starts_with((string) $adminHash['password_hash'], '$2y$'));

    /* ----------------------------------------------------------------------
       Verifikasi dua langkah (2FA)
       ---------------------------------------------------------------------- */

    Database::execute("DELETE FROM rate_limits WHERE bucket IN ('login', 'login-ip', 'two-factor', 'two-factor-ip', 'two-factor-setup', 'two-factor-disable')");

    $twoFactorEmail = 'dua.' . $suffix . '@example.test';
    $twoFactorPassword = 'DuaFaktor!' . $suffix;
    $twoFactorDisplay = 'Pembaca 2FA ' . $suffix;
    $twoFactorUserId = null;

    postWith($http, 'twofactor', '/daftar', [
        'display_name' => $twoFactorDisplay,
        'email' => $twoFactorEmail,
        'password' => $twoFactorPassword,
        'password_confirm' => $twoFactorPassword,
    ]);
    $tokens = [];
    $twoFactorUser = Database::fetch('SELECT id FROM users WHERE email = ?', [$twoFactorEmail]);
    $twoFactorUserId = $twoFactorUser !== null ? (int) $twoFactorUser['id'] : null;
    $twoFactorMail = mailFor($mailDir, $twoFactorEmail, '/verifikasi-email/');
    if ($twoFactorMail['file'] !== '') {
        $mailFilesCreated[] = $twoFactorMail['file'];
    }
    preg_match('#/verifikasi-email/([a-f0-9]{64})#', $twoFactorMail['raw'], $twoFactorMatch);
    if (($twoFactorMatch[1] ?? '') !== '') {
        $http->request('twofactor', 'GET', '/verifikasi-email/' . $twoFactorMatch[1]);
    }
    check('Akun uji 2FA terdaftar dan terverifikasi', $twoFactorUserId !== null && Database::fetch('SELECT verified_at FROM email_verifications WHERE user_id = ?', [$twoFactorUserId])['verified_at'] !== null);

    $setupPage = $http->request('twofactor', 'GET', '/profil/2fa');
    preg_match('/class="secret-key"[^>]*>\s*<code>([^<]+)<\/code>/', $setupPage['body'], $secretMatch);
    $twoFactorSecret = preg_replace('/\s+/', '', $secretMatch[1] ?? '') ?? '';
    check(
        'Halaman aktivasi menampilkan kunci TOTP dan tautan otpauth',
        $setupPage['status'] === 200
            && preg_match('/^[A-Z2-7]{32}$/', $twoFactorSecret) === 1
            && str_contains($setupPage['body'], 'otpauth://totp/')
    );

    $enableResponse = postWith($http, 'twofactor', '/profil/2fa', ['code' => Totp::code($twoFactorSecret)]);
    $twoFactorRow = Database::fetch('SELECT secret_ciphertext, last_used_step FROM user_two_factor WHERE user_id = ?', [$twoFactorUserId]);
    check(
        'Aktivasi 2FA menyimpan secret terenkripsi, bukan teks asli',
        $enableResponse['status'] === 302
            && locationIs($enableResponse, '/profil')
            && $twoFactorRow !== null
            && $twoFactorRow['secret_ciphertext'] !== $twoFactorSecret
            && Crypto::decrypt((string) $twoFactorRow['secret_ciphertext']) === $twoFactorSecret
    );

    $profileAfterEnable = $http->request('twofactor', 'GET', '/profil');
    preg_match('/recovery-codes__list.*?<\/ul>/s', $profileAfterEnable['body'], $recoveryBlock);
    preg_match_all('/<code>([A-Z2-7]{4}-[A-Z2-7]{4})<\/code>/', $recoveryBlock[0] ?? '', $recoveryMatches);
    $twoFactorRecoveryCodes = array_values(array_unique($recoveryMatches[1] ?? []));
    check(
        'Profil menampilkan status aktif dan delapan kode pemulihan sekali tampil',
        str_contains($profileAfterEnable['body'], 'Verifikasi dua langkah')
            && str_contains($profileAfterEnable['body'], '>Aktif<')
            && count($twoFactorRecoveryCodes) === 8
            && dbCount('SELECT COUNT(*) FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL', [$twoFactorUserId]) === 8
    );

    $tokens = [];
    $logoutTwoFactor = $http->request('twofactor', 'POST', '/keluar', ['_token' => tokenFor($http, 'twofactor', '/')]);
    $tokens = [];
    check('Logout akun 2FA berhasil', $logoutTwoFactor['status'] === 302);

    $twoFactorLogin = postWith($http, 'twofactor', '/masuk', ['email' => $twoFactorEmail, 'password' => $twoFactorPassword]);
    check(
        'Login akun 2FA dialihkan ke tantangan kode dan belum membuat sesi',
        $twoFactorLogin['status'] === 302
            && locationIs($twoFactorLogin, '/masuk/2fa')
            && $http->request('twofactor', 'GET', '/profil')['status'] === 302
    );

    $challengePage = $http->request('twofactor', 'GET', '/masuk/2fa');
    check(
        'Halaman tantangan menampilkan kode autentikator dan opsi pemulihan',
        $challengePage['status'] === 200
            && str_contains($challengePage['body'], 'Masukkan kode keamanan')
            && str_contains($challengePage['body'], 'name="recovery_code"')
    );

    $wrongCode = Totp::code($twoFactorSecret) === '000000' ? '000001' : '000000';
    $wrongChallenge = postWith($http, 'twofactor', '/masuk/2fa', ['code' => $wrongCode]);
    $wrongChallengePage = follow($http, 'twofactor', $wrongChallenge);
    check(
        'Kode autentikator salah ditolak tanpa membuat sesi',
        $wrongChallenge['status'] === 302
            && locationIs($wrongChallenge, '/masuk/2fa')
            && str_contains($wrongChallengePage['body'], 'Kode tidak valid')
            && $http->request('twofactor', 'GET', '/profil')['status'] === 302
    );

    $validChallenge = postWith($http, 'twofactor', '/masuk/2fa', ['code' => Totp::code($twoFactorSecret)]);
    check(
        'Kode autentikator benar menyelesaikan login',
        $validChallenge['status'] === 302
            && $http->request('twofactor', 'GET', '/profil')['status'] === 200
    );
    $tokens = [];

    $http->request('twofactor', 'POST', '/keluar', ['_token' => tokenFor($http, 'twofactor', '/')]);
    $tokens = [];
    postWith($http, 'twofactor', '/masuk', ['email' => $twoFactorEmail, 'password' => $twoFactorPassword]);
    $tokens = [];
    $recoveryLogin = postWith($http, 'twofactor', '/masuk/2fa', ['recovery_code' => $twoFactorRecoveryCodes[0] ?? '']);
    check(
        'Kode pemulihan dapat menggantikan kode autentikator',
        $recoveryLogin['status'] === 302
            && $http->request('twofactor', 'GET', '/profil')['status'] === 200
            && dbCount('SELECT COUNT(*) FROM user_recovery_codes WHERE user_id = ? AND used_at IS NOT NULL', [$twoFactorUserId]) === 1
    );
    $tokens = [];

    $http->request('twofactor', 'POST', '/keluar', ['_token' => tokenFor($http, 'twofactor', '/')]);
    $tokens = [];
    postWith($http, 'twofactor', '/masuk', ['email' => $twoFactorEmail, 'password' => $twoFactorPassword]);
    $tokens = [];
    $reuseRecovery = postWith($http, 'twofactor', '/masuk/2fa', ['recovery_code' => $twoFactorRecoveryCodes[0] ?? '']);
    check(
        'Kode pemulihan tidak dapat dipakai dua kali',
        $reuseRecovery['status'] === 302
            && locationIs($reuseRecovery, '/masuk/2fa')
            && $http->request('twofactor', 'GET', '/profil')['status'] === 302
    );

    $replayTotp = postWith($http, 'twofactor', '/masuk/2fa', ['code' => Totp::code($twoFactorSecret)]);
    check(
        'Kode autentikator yang sudah dipakai ditolak (anti-replay)',
        $replayTotp['status'] === 302
            && locationIs($replayTotp, '/masuk/2fa')
            && $http->request('twofactor', 'GET', '/profil')['status'] === 302
    );

    $nextStepCode = Totp::code($twoFactorSecret, time() + 30);
    $nextStepChallenge = postWith($http, 'twofactor', '/masuk/2fa', ['code' => $nextStepCode]);
    check(
        'Kode dari langkah waktu berikutnya diterima',
        $nextStepChallenge['status'] === 302 && $http->request('twofactor', 'GET', '/profil')['status'] === 200
    );
    $tokens = [];

    $disableWithoutCode = postWith($http, 'twofactor', '/profil/2fa/nonaktif', [
        'current_password' => $twoFactorPassword,
        'code' => '000000',
    ]);
    check(
        'Nonaktifkan 2FA menolak kode yang salah',
        $disableWithoutCode['status'] === 302
            && locationIs($disableWithoutCode, '/profil')
            && dbCount('SELECT COUNT(*) FROM user_two_factor WHERE user_id = ?', [$twoFactorUserId]) === 1
    );

    $disableResponse = postWith($http, 'twofactor', '/profil/2fa/nonaktif', [
        'current_password' => $twoFactorPassword,
        'code' => $twoFactorRecoveryCodes[1] ?? '',
    ]);
    check(
        'Nonaktifkan 2FA dengan sandi dan kode pemulihan menghapus secret',
        $disableResponse['status'] === 302
            && dbCount('SELECT COUNT(*) FROM user_two_factor WHERE user_id = ?', [$twoFactorUserId]) === 0
            && dbCount('SELECT COUNT(*) FROM user_recovery_codes WHERE user_id = ?', [$twoFactorUserId]) === 0
    );

    $tokens = [];
    $reenablePage = $http->request('twofactor', 'GET', '/profil/2fa');
    preg_match('/class="secret-key"[^>]*>\s*<code>([^<]+)<\/code>/', $reenablePage['body'], $reenableMatch);
    $reenableSecret = preg_replace('/\s+/', '', $reenableMatch[1] ?? '') ?? '';
    postWith($http, 'twofactor', '/profil/2fa', ['code' => Totp::code($reenableSecret)]);
    $http->request('twofactor', 'POST', '/keluar', ['_token' => tokenFor($http, 'twofactor', '/')]);
    $tokens = [];
    postWith($http, 'twofactor', '/masuk', ['email' => $twoFactorEmail, 'password' => $twoFactorPassword]);
    $tokens = [];
    Database::execute("DELETE FROM rate_limits WHERE bucket IN ('two-factor', 'two-factor-ip')");
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        postWith($http, 'twofactor', '/masuk/2fa', ['code' => '000000']);
    }
    $throttledChallenge = postWith($http, 'twofactor', '/masuk/2fa', ['code' => '000000']);
    $throttledPage = follow($http, 'twofactor', $throttledChallenge);
    check(
        'Rate limit tantangan 2FA memblokir percobaan berulang dan menghapus sesi pending',
        $throttledChallenge['status'] === 302
            && locationIs($throttledChallenge, '/masuk')
            && str_contains($throttledPage['body'], 'Terlalu banyak percobaan kode')
    );

    $adminUserRow = $http->request('admin', 'GET', '/admin/pengguna?q=' . urlencode($twoFactorEmail));
    check(
        'Daftar pengguna admin menampilkan status 2FA aktif',
        $adminUserRow['status'] === 200
            && str_contains($adminUserRow['body'], '2FA aktif')
            && str_contains($adminUserRow['body'], 'reset-2fa')
    );

    $tokens = [];
    $unauthorizedReset = postWith($http, 'twofactor', '/admin/pengguna/' . $twoFactorUserId . '/reset-2fa', []);
    check(
        'Reset 2FA ditolak untuk pengguna yang bukan admin',
        $unauthorizedReset['status'] === 302
            && dbCount('SELECT COUNT(*) FROM user_two_factor WHERE user_id = ?', [$twoFactorUserId]) === 1
    );

    $tokens = [];
    $adminReset = postWith($http, 'admin', '/admin/pengguna/' . $twoFactorUserId . '/reset-2fa', []);
    check(
        'Admin dapat mereset 2FA dan menghapus kode pemulihan',
        $adminReset['status'] === 302
            && locationIs($adminReset, '/admin/pengguna')
            && dbCount('SELECT COUNT(*) FROM user_two_factor WHERE user_id = ?', [$twoFactorUserId]) === 0
            && dbCount('SELECT COUNT(*) FROM user_recovery_codes WHERE user_id = ?', [$twoFactorUserId]) === 0
    );

    $tokens = [];
    $loginAfterReset = postWith($http, 'twofactor', '/masuk', ['email' => $twoFactorEmail, 'password' => $twoFactorPassword]);
    check(
        'Setelah reset admin pengguna dapat masuk hanya dengan kata sandi',
        $loginAfterReset['status'] === 302
            && !locationIs($loginAfterReset, '/masuk/2fa')
            && $http->request('twofactor', 'GET', '/profil')['status'] === 200
    );
    $tokens = [];

    /* ----------------------------------------------------------------------
       Rate limit login (kemudian dibersihkan agar pengujian dapat diulang)
       ---------------------------------------------------------------------- */

    $rateEmail = 'rate.' . $suffix . '@example.test';
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        postWith($http, 'rate', '/masuk', ['email' => $rateEmail, 'password' => 'salah-sekali']);
    }
    $blocked = postWith($http, 'rate', '/masuk', ['email' => $rateEmail, 'password' => 'salah-lagi']);
    $blockedPage = follow($http, 'rate', $blocked);
    check('Rate limit login memblokir percobaan berulang', $blocked['status'] === 302 && str_contains($blockedPage['body'], 'Terlalu banyak percobaan masuk'));

    $logFile = BASE_PATH . '/storage/logs/app.log';
    $logContent = is_file($logFile) ? (string) file_get_contents($logFile) : '';
    check('Tidak ada error server tercatat selama pengujian', $logContent === '', $logContent !== '' ? 'app.log berisi ' . substr_count($logContent, 'ref=') . ' entri' : '');
} finally {
    /* Pembersihan data uji. */
    if ($testNovelId !== null) {
        $novel = Database::fetch('SELECT cover_path FROM novels WHERE id = ?', [$testNovelId]);
        if ($novel !== null && is_string($novel['cover_path']) && $novel['cover_path'] !== '') {
            @unlink(BASE_PATH . '/public/' . $novel['cover_path']);
        }
        Database::execute('DELETE FROM novels WHERE id = ?', [$testNovelId]);
    }
    if ($genreId !== null) {
        Database::execute('DELETE FROM categories WHERE id = ? AND NOT EXISTS (SELECT 1 FROM novel_categories WHERE category_id = ?)', [$genreId, $genreId]);
    }
    if ($draftCommentId !== null) {
        Database::execute('DELETE FROM comments WHERE id = ?', [$draftCommentId]);
    }
    if ($testUserId !== null) {
        Database::execute('DELETE FROM password_resets WHERE user_id = ?', [$testUserId]);
        Database::execute('DELETE FROM users WHERE id = ?', [$testUserId]);
    }
    if ($pendingUserId !== null) {
        Database::execute('DELETE FROM users WHERE id = ?', [$pendingUserId]);
    }
    if (isset($twoFactorUserId) && $twoFactorUserId !== null) {
        Database::execute('DELETE FROM users WHERE id = ?', [$twoFactorUserId]);
    }
    $testRecipients = ['To: ' . $testEmail, 'To: ' . $pendingEmail, 'To: ' . $pendingEmail2];
    if (isset($twoFactorEmail)) {
        $testRecipients[] = 'To: ' . $twoFactorEmail;
    }
    foreach (glob(BASE_PATH . '/storage/mail/*.eml') ?: [] as $mailFile) {
        $mailContent = (string) file_get_contents($mailFile);
        foreach ($testRecipients as $recipient) {
            if (str_contains($mailContent, $recipient)) {
                @unlink($mailFile);
                break;
            }
        }
    }
    foreach ($mailFilesCreated as $mailFile) {
        @unlink($mailFile);
    }
    if (isset($pngPath)) {
        @unlink($pngPath);
    }
    if (isset($invalidCoverPath)) {
        @unlink($invalidCoverPath);
    }
    Database::execute('DELETE FROM rate_limits');
}

$total = $pass + $fail;
echo "\n========================================\n";
echo "HASIL: {$pass}/{$total} lulus, {$fail} gagal\n";
if ($failures !== []) {
    echo "----------------------------------------\n";
    foreach ($failures as $failure) {
        echo '- ' . $failure['test'] . ($failure['detail'] !== '' ? ' (' . $failure['detail'] . ')' : '') . "\n";
    }
}
echo "========================================\n";

$lastRun = [
    'ran_at' => date('c'),
    'base_url' => $base,
    'total' => $total,
    'passed' => $pass,
    'failed' => $fail,
    'failures' => $failures,
];
@file_put_contents(__DIR__ . '/last-run.json', json_encode($lastRun, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

exit($fail > 0 ? 1 : 0);
