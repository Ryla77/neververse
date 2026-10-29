# NovelVerse

NovelVerse adalah platform membaca dan menulis novel online berbahasa Indonesia. Aplikasi ini dibangun sebagai aplikasi PHP 8.2 + MySQL/MariaDB yang benar-benar berjalan, bukan sekadar rancangan. Seluruh cerita pada data contoh adalah karya orisinal yang ditulis khusus untuk proyek ini.

Fitur utama:

- Beranda dengan banner unggulan, novel populer, rilis terbaru, sedang diperbarui, daftar genre, dan rekomendasi.
- Katalog novel dengan pencarian judul/penulis, filter genre, pengurutan, dan paginasi.
- Halaman detail novel: sampul, sinopsis, genre, status, jumlah bab, rating, komentar, dan daftar bab.
- Pembaca (reader) dengan pengaturan tema baca, ukuran huruf, lebar kolom, jarak paragraf, daftar isi, navigasi bab, dan penyimpanan progres.
- Registrasi, login, logout, verifikasi email, ubah profil, ubah kata sandi, peningkatan peran menjadi penulis, serta lupa/reset kata sandi melalui email dengan transport `file`, `mail()`, atau SMTP ber-TLS. Komentar, rating, dan akses penulis baru aktif setelah email terverifikasi. Verifikasi dua langkah (2FA) berbasis TOTP tersedia sebagai perlindungan tambahan saat login.
- Perpustakaan pribadi: novel tersimpan, riwayat baca, lanjutkan membaca, dan hapus dari perpustakaan.
- Dashboard penulis: kelola novel milik sendiri, unggah sampul, tulis/edit bab, draf, publikasi, dan status ongoing/completed.
- Dashboard admin: statistik, pengguna dan peran, novel, bab, genre, moderasi komentar, laporan, serta log audit tindakan admin.
- Tema gelap/terang, desain responsif untuk desktop, tablet, dan ponsel.
- Keamanan dasar: `password_hash()`/`password_verify()`, CSRF, session yang diperketat, prepared statement, escaping output, kontrol peran di backend, pembatasan percobaan, validasi unggahan, dan 2FA TOTP dengan rahasia terenkripsi serta kode pemulihan sekali pakai.

## Teknologi

| Komponen | Keterangan |
| --- | --- |
| Bahasa | PHP 8.2.12 (diuji dengan XAMPP) |
| Database | MariaDB 10.4.32 (kompatibel MySQL 8.0+) |
| Web server | PHP built-in server (dipakai saat pengujian) atau Apache XAMPP dengan `mod_rewrite` |
| Frontend | HTML5, CSS3, JavaScript vanilla, tanpa framework wajib |
| Arsitektur | MVC ringan buatan sendiri, tanpa Composer |
| Ekstensi PHP | `pdo_mysql`, `fileinfo`, `mbstring`; `curl` untuk tes integrasi; `openssl` untuk transport SMTP/TLS |

Tidak ada dependensi Composer atau build frontend. Aset CSS/JS tersedia langsung di `public/assets`.

## Struktur Folder

```text
novelverse/
├── app/
│   ├── Controllers/        # Controller halaman dan aksi
│   ├── Core/               # Router, Request, Response, Database, Auth, CSRF, dsb.
│   ├── Middleware/         # Auth, Guest, Role
│   ├── Models/             # Akses data per tabel
│   ├── Services/           # Layanan pendukung
│   └── bootstrap.php       # Bootstrap aplikasi
├── bin/
│   ├── install.php         # Installer CLI (database, akun, seed)
│   └── generate-covers.php # Pembuat sampul SVG data contoh
├── config/
│   ├── config.php          # Konfigurasi aplikasi
│   └── helpers.php         # Helper e(), url(), asset(), csrf_field(), dsb.
├── database/
│   ├── schema.sql          # Skema tabel
│   └── seed.sql            # Data contoh orisinal
├── public/                 # Satu-satunya folder yang boleh menjadi DocumentRoot
│   ├── index.php           # Front controller
│   ├── router.php          # Router server bawaan PHP
│   ├── .htaccess           # Rewrite + header keamanan untuk Apache
│   ├── assets/
│   │   ├── css/app.css
│   │   ├── js/app.js
│   │   └── images/         # Ikon, favicon, sampul SVG orisinal
│   └── uploads/covers/     # Sampul unggahan pengguna
├── resources/views/        # Layout dan view per halaman
├── routes/web.php          # Peta rute
├── storage/                # Log, kredensial instalasi, kotak surat lokal, penanda instalasi
├── tests/
│   ├── run.php             # Tes integrasi HTTP
│   ├── smtp-run.php        # Tes end-to-end transport SMTP
│   ├── fake-smtp.php       # Server SMTP lokal untuk pengujian
│   ├── smtp-send.php       # Pengirim CLI untuk pengujian transport
│   └── tmp/                # Artefak tes (tidak di-commit)
├── .env.example            # Contoh konfigurasi
├── .gitignore
└── README.md
```

Folder `app`, `config`, `database`, `resources`, `routes`, dan `storage` berada di luar DocumentRoot agar tidak dapat diakses langsung dari browser.

## Kebutuhan Sistem

1. PHP 8.2 atau lebih baru, dengan ekstensi `pdo_mysql`, `fileinfo`, `mbstring`, serta `openssl` untuk transport SMTP/TLS. Ekstensi `curl` diperlukan untuk tes integrasi.
2. MySQL 8.0+ atau MariaDB 10.4+. XAMPP dengan MariaDB 10.4 sudah cukup.
3. Apache dengan `mod_rewrite` dan `AllowOverride All` jika memakai Apache. Alternatif tanpa Apache: server bawaan PHP (lihat bagian Menjalankan).
4. Browser modern (Chrome, Edge, Firefox, atau Safari).
5. Terminal/Command Prompt untuk instalasi dan menjalankan server.

Gambar sampul tidak memerlukan GD karena unggahan divalidasi dengan `finfo` dan `getimagesize`, lalu disimpan apa adanya.

## Instalasi Cepat (Disarankan)

Cara ini membuat database, user database khusus, file `.env`, seluruh tabel, akun demo, dan data contoh secara otomatis. Tidak ada kata sandi permanen yang ditanam di source code.

1. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
2. Buka terminal di folder proyek `novelverse`.
3. Jalankan installer:

   ```bash
   php bin/install.php --db-root-user=root --db-root-password=
   ```

   Jika root MySQL memiliki kata sandi:

   ```bash
   php bin/install.php --db-root-user=root --db-root-password="SANDI_ROOT_ANDA"
   ```

   Opsi lain:

   ```text
   --admin-email=EMAIL   Email akun admin pertama (default admin@novelverse.local)
   --force               Pasang ulang; PERINGATAN: database novelverse dihapus
   --help                Tampilkan bantuan
   ```

4. Installer akan:
   - membuat database `novelverse` dengan `utf8mb4`;
   - membuat user database `novelverse` dengan kata sandi acak 24 karakter;
   - menulis `.env` (di-gitignore, tidak boleh dibagikan);
   - mengimpor `database/schema.sql`;
   - membuat 1 admin, 4 penulis, dan 3 pembaca dengan kata sandi acak;
   - mengimpor `database/seed.sql` berisi 12 novel terbit (plus 1 draf), 37 bab terbit, 6 genre, komentar, rating, dan data perpustakaan contoh;
   - menyimpan kredensial sekali ke `storage/install-credentials.txt` (izin `0600`, di-gitignore).
5. Catat kredensial dari `storage/install-credentials.txt`, lalu hapus berkas tersebut setelah aman disimpan.
6. Jalankan aplikasi (lihat bagian Menjalankan) dan masuk melalui `/masuk`.

Perintah `--force` akan **menghapus database** `novelverse` sebelum membuat ulang. Jangan gunakan pada data produksi.

### Instalasi Manual dan Impor SQL

Gunakan jalur ini jika ingin menyiapkan database sendiri. Data contoh `seed.sql` bergantung pada akun demo yang dibuat installer, jadi untuk instalasi manual sebaiknya mulai dari database kosong tanpa seed.

1. Buat database dan user aplikasi:

   ```sql
   CREATE DATABASE novelverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'novelverse'@'localhost' IDENTIFIED BY 'SANDI_DATABASE_KUAT';
   GRANT ALL PRIVILEGES ON novelverse.* TO 'novelverse'@'localhost';
   GRANT ALL PRIVILEGES ON novelverse.* TO 'novelverse'@'127.0.0.1';
   FLUSH PRIVILEGES;
   ```

2. Impor skema melalui phpMyAdmin atau CLI:

   ```bash
   mysql -u novelverse -p novelverse < database/schema.sql
   ```

3. Salin `.env.example` menjadi `.env`, lalu isi `DB_USER` dan `DB_PASS` sesuai user yang dibuat.

4. Buat admin pertama tanpa kata sandi permanen di source. Buat hash dari terminal:

   ```bash
   php -r "echo password_hash(readline('Sandi admin: '), PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Lalu masukkan hash tersebut melalui phpMyAdmin:

   ```sql
   INSERT INTO users (email, password_hash, display_name, role, is_active, created_at)
   VALUES ('admin@domain-anda.id', '<HASH_DARI_LANGKAH_SEBELUMNYA>', 'Administrator', 'admin', 1, NOW());
   ```

   Jangan menyimpan hash/kata sandi di repository. Setelah login, ganti kata sandi melalui halaman profil.

5. Untuk memakai data contoh, jalankan installer pada database kosong; installer yang menyiapkan akun demo yang dibutuhkan `seed.sql`. Membuat ulang 7 akun demo secara manual dengan email yang sama juga memungkinkan, tetapi tidak disarankan.

## Konfigurasi

Konfigurasi dibaca dari `.env` di root proyek. Berkas ini **tidak boleh** di-commit atau diletakkan di folder publik.

| Kunci | Fungsi | Contoh |
| --- | --- | --- |
| `APP_NAME` | Nama aplikasi | `NovelVerse` |
| `APP_ENV` | Lingkungan aplikasi | `local` atau `production` |
| `APP_DEBUG` | Detail error di browser | `false` untuk produksi |
| `APP_TIMEZONE` | Zona waktu aplikasi | `Asia/Jakarta` |
| `APP_URL` | URL publik untuk tautan email; wajib di produksi dan harus menyertakan subfolder bila ada | `https://novelverse.example.com` |
| `APP_KEY` | Kunci AES-256-GCM (base64, 32 byte) untuk mengenkripsi rahasia 2FA; wajib agar 2FA dapat diaktifkan | hasil `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"` |
| `DB_HOST` | Host database | `127.0.0.1` |
| `DB_PORT` | Port database | `3306` |
| `DB_NAME` | Nama database | `novelverse` |
| `DB_USER` | User database aplikasi | `novelverse` |
| `DB_PASS` | Kata sandi user database | kata sandi acak dari installer |
| `DB_ROOT_USER` | User root untuk installer | `root` |
| `DB_ROOT_PASS` | Kata sandi root untuk installer | kosong pada XAMPP bawaan |
| `UPLOAD_MAX_KB` | Batas ukuran sampul (KB) | `2048` |
| `MAIL_TRANSPORT` | Pengiriman email: `file` (lokal), `mail` (MTA server), atau `smtp` | `file` |
| `MAIL_FROM_ADDRESS` | Alamat pengirim email | `no-reply@novelverse.local` |
| `MAIL_FROM_NAME` | Nama pengirim email | `NovelVerse` |
| `MAIL_HOST` | Host server SMTP (untuk `smtp`) | `smtp.example.com` |
| `MAIL_PORT` | Port SMTP; default 587 untuk `tls`, 465 untuk `ssl` | `587` |
| `MAIL_ENCRYPTION` | `tls` (STARTTLS), `ssl` (TLS implisit), atau `none` | `tls` |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Kredensial SMTP; hanya dikirim melalui TLS | diisi bila server meminta autentikasi |
| `MAIL_TIMEOUT` | Batas waktu koneksi SMTP dalam detik (3–60) | `15` |
| `MAIL_HELO` | Nama HELO yang dikirim ke server SMTP | `localhost` |
| `MAIL_CAFILE` | Path file CA kustom untuk memverifikasi sertifikat SMTP | kosong (CA sistem) |
| `PASSWORD_RESET_TTL` | Masa berlaku tautan reset (menit, minimal 5) | `60` |
| `EMAIL_VERIFICATION_TTL` | Masa berlaku tautan verifikasi email (menit, minimal 5) | `1440` |

Kredensial database sebaiknya hanya dimiliki user aplikasi dengan hak pada database `novelverse`, bukan root. Installer sudah melakukan ini.

`APP_KEY` dibuat otomatis oleh installer. Jangan mengganti nilainya setelah ada pengguna mengaktifkan 2FA: rahasia TOTP dienkripsi dengan kunci ini dan tidak dapat didekripsi lagi jika kunci berubah. Cadangkan `.env` bersama database.

## Setup SMTP

SMTP dipakai untuk mengirim tautan verifikasi email dan reset kata sandi. Aplikasi memakai klien SMTP bawaan tanpa library eksternal, mendukung STARTTLS (`tls`), TLS implisit (`ssl`), serta AUTH PLAIN/LOGIN. Autentikasi hanya dikirim setelah TLS aktif dan sertifikat server selalu diverifikasi.

### 1. Siapkan prasyarat

- Ekstensi PHP `openssl` aktif (periksa dengan `php -m`).
- Server dapat membuka koneksi keluar ke port SMTP penyedia (umumnya 587 atau 465).
- Host, port, mode enkripsi, dan kredensial SMTP dari penyedia email.
- `APP_URL` menunjuk URL publik aplikasi, misalnya `https://novelverse.example.com`, agar tautan email tidak bergantung pada header `Host`.

### 2. Isi `.env`

Ubah nilai berikut di `.env` (bukan `.env.example`, dan jangan commit `.env`):

```dotenv
APP_URL=https://novelverse.example.com

MAIL_TRANSPORT=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=akun-smtp
MAIL_PASSWORD=ganti-dengan-kata-sandi-smtp
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="NovelVerse"

# Opsional
MAIL_TIMEOUT=15
MAIL_HELO=localhost
EMAIL_VERIFICATION_TTL=1440
PASSWORD_RESET_TTL=60
# MAIL_CAFILE=C:/path/ke/ca.pem
```

`MAIL_FROM_ADDRESS` sebaiknya memakai domain yang Anda kontrol dan sesuai kebijakan penyedia. Beberapa penyedia menolak `MAIL_HELO=localhost`; ganti dengan hostname FQDN server Anda bila diperlukan.

### 3. Pilih port dan mode enkripsi

| `MAIL_ENCRYPTION` | Port umum | Perilaku |
| --- | --- | --- |
| `tls` | 587 | STARTTLS wajib. Pengiriman dibatalkan bila server tidak menawarkannya. |
| `ssl` | 465 | TLS implisit sejak koneksi pertama. |
| `none` | 25/1025 | Tanpa TLS. Hanya untuk relay lokal tepercaya. Kredensial ditolak pada mode ini. |

Jika ragu, mulai dengan `tls` + 587. Jika penyedia memakai port 465, gunakan `ssl`.

### 4. Sesuaikan dengan penyedia

| Penyedia | Contoh host | Port/mode | Catatan |
| --- | --- | --- | --- |
| Gmail | `smtp.gmail.com` | 587 `tls` atau 465 `ssl` | Aktifkan verifikasi 2 langkah dan buat kata sandi aplikasi. Gunakan alamat Gmail atau alias terverifikasi sebagai `MAIL_FROM_ADDRESS`. |
| Microsoft 365 | `smtp.office365.com` | 587 `tls` | SMTP AUTH harus diaktifkan oleh admin tenant. Tenant yang mewajibkan OAuth2 tidak didukung. |
| Relay lokal (Mailpit/MailHog/Postfix) | host internal | 1025/25, umumnya `none` | Hanya untuk pengembangan. Jangan mengirim kredensial pada koneksi tanpa TLS. |
| Penyedia transaksional | host dari dashboard penyedia | 587 `tls` atau 465 `ssl` | Verifikasi domain pengirim dan gunakan kredensial SMTP, bukan API key, kecuali penyedia menyediakan kredensial SMTP. |

Contoh di atas dapat berubah; ikuti dokumentasi penyedia. Klien ini mendukung AUTH PLAIN dan LOGIN, belum mendukung OAuth2/XOAUTH2.

### 5. Uji pengiriman

**Tes transport lokal** (tanpa internet):

```bash
php tests/smtp-run.php
```

Tes ini memakai server SMTP palsu dengan STARTTLS/AUTH dan memeriksa alur verifikasi email serta reset end-to-end. Hasil sukses tidak membuktikan kredensial penyedia Anda benar; lihat bagian **Tes SMTP end-to-end** untuk prasyaratnya.

**Tes pengiriman nyata:**

1. Jalankan aplikasi, lalu daftar akun baru dengan alamat email yang benar-benar dapat Anda terima.
2. Periksa kotak masuk/spam, buka tautan verifikasi, dan pastikan status akun menjadi terverifikasi.
3. Uji juga `/lupa-sandi` dengan email tersebut, lalu buka tautan reset.
4. Jika email tidak datang, periksa `storage/logs/app.log` dan nilai `MAIL_*` di `.env`. Halaman aplikasi tetap menampilkan pesan umum demi keamanan.

### 6. Checklist produksi

- `APP_ENV=production`, `APP_DEBUG=false`, dan HTTPS aktif.
- `APP_URL` memakai HTTPS dan domain publik yang benar.
- Domain `MAIL_FROM_ADDRESS` memiliki SPF/DKIM (dan DMARC bila ada) sesuai penyedia.
- Port keluar 587/465 diizinkan firewall.
- `.env` memiliki izin baca terbatas dan tidak berada di folder publik.
- Jangan memakai `MAIL_ENCRYPTION=none` bersama kredensial.
- Gunakan kata sandi aplikasi/kredensial SMTP khusus, bukan kata sandi akun utama, dan rotasikan secara berkala.
- Setelah mengubah `.env`, restart PHP-FPM/Apache bila konfigurasi lingkungan di-cache. Server bawaan PHP membaca `.env` per permintaan.

Jika terjadi kegagalan, lihat tabel **Pemecahan Masalah** pada bagian koneksi, STARTTLS, sertifikat, dan autentikasi 535.

## Menjalankan Aplikasi

### Opsi A: Server Bawaan PHP (tanpa konfigurasi Apache)

Dari folder proyek:

```bash
php -S 127.0.0.1:8080 -t public public/router.php
```

Buka `http://127.0.0.1:8080`.

Di Windows dengan XAMPP:

```bat
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public public/router.php
```

Hentikan dengan `Ctrl+C`. Router `public/router.php` melayani aset statis dan meneruskan rute lain ke `public/index.php`.

### Opsi B: Apache XAMPP

DocumentRoot **harus** menunjuk ke folder `public`, bukan root proyek. Jika DocumentRoot menunjuk ke root proyek, `.env`, `storage`, dan kode internal berisiko terekspos.

Contoh VirtualHost:

```apache
<VirtualHost *:80>
    ServerName novelverse.local
    DocumentRoot "C:/path/ke/novelverse/public"

    <Directory "C:/path/ke/novelverse/public">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Langkah:

1. Aktifkan `mod_rewrite` di `httpd.conf` (`LoadModule rewrite_module modules/mod_rewrite.so`).
2. Pastikan `AllowOverride All` berlaku untuk folder `public`.
3. Tambahkan `127.0.0.1 novelverse.local` ke `C:\Windows\System32\drivers\etc\hosts` jika memakai VirtualHost.
4. Restart Apache.
5. Buka `http://novelverse.local`.

Jika aplikasi dipasang di subfolder (misalnya `http://localhost/novelverse`), arahkan alias/DocumentRoot agar tetap berakhir di folder `public`. Helper `url()` mendeteksi base path secara otomatis.

## Halaman dan Hak Akses

| URL | Halaman | Akses |
| --- | --- | --- |
| `/` | Beranda | Publik |
| `/jelajahi` | Katalog + pencarian/filter/urut/paginasi | Publik |
| `/novel/{slug}` | Detail novel, komentar, rating | Publik |
| `/novel/{slug}/baca/{nomor}` | Reader | Publik (progres tamu di localStorage) |
| `/masuk`, `/daftar` | Login / registrasi | Tamu |
| `/masuk/2fa` | Tantangan kode autentikator/kode pemulihan setelah kata sandi benar | Tamu dengan sesi pending |
| `/lupa-sandi` | Minta tautan reset kata sandi via email | Tamu |
| `/reset-sandi/{token}` | Formulir kata sandi baru dari tautan email | Tamu |
| `/verifikasi-email/{token}` | Verifikasi email dari tautan | Publik (token) |
| `/verifikasi-email/kirim-ulang` | Kirim ulang tautan verifikasi (POST) | Login |
| `/perpustakaan` | Favorit, riwayat, lanjutkan membaca | Login |
| `/profil` | Profil, ubah nama, kata sandi, jadi penulis, status 2FA | Login |
| `/profil/2fa` | Aktivasi 2FA: tampilkan kunci TOTP dan konfirmasi kode | Login |
| `/profil/2fa/nonaktif` | Nonaktifkan 2FA dengan kata sandi + kode (POST) | Login |
| `/tulis` | Dashboard penulis | Penulis/Admin |
| `/admin` | Dashboard admin | Admin |
| `/admin/audit` | Log audit tindakan admin (filter aksi/pencarian) | Admin |

Aksi yang mengubah data selalu diperiksa di backend: middleware peran untuk area dashboard, ditambah pemeriksaan kepemilikan record untuk novel/bab penulis. Menyembunyikan tombol di UI bukan satu-satunya kontrol.

Akun yang sudah masuk tetapi emailnya belum terverifikasi tetap dapat membaca dan memakai perpustakaan. Komentar, rating, dan peningkatan peran menjadi penulis ditolak di backend sampai email diverifikasi; banner di bagian atas halaman menyediakan tombol kirim ulang.

Peran:

- **Pembaca (`reader`)**: membaca, menyimpan novel, memberi komentar/rating, menyimpan progres, melaporkan konten.
- **Penulis (`author`)**: semua hak pembaca, ditambah mengelola novel dan bab miliknya sendiri.
- **Admin (`admin`)**: mengelola pengguna, novel, bab, genre, komentar, laporan, dan meninjau log audit.

## Admin Pertama dan Pengelolaan Akun

- Installer membuat admin pertama dengan kata sandi **acak** dan menuliskannya ke `storage/install-credentials.txt`. Tidak ada email/kata sandi admin permanen di source code.
- Setelah mencatat kredensial, hapus berkas tersebut. Berkas ini di-gitignore, tetapi tetap sensitif.
- Login sebagai admin melalui `/masuk`, lalu buka `/admin`.
- Admin dapat mengubah peran pengguna dan menonaktifkan akun melalui menu **Pengguna**.
- Tombol **Reset sandi** membuat kata sandi sementara acak dan menampilkannya sekali di pesan flash. Sampaikan kepada pengguna dan minta segera mengganti. Reset oleh admin otomatis mengakhiri seluruh sesi aktif pengguna tersebut; perubahan sandi mandiri tetap mempertahankan sesi saat ini.
- Tombol **Reset 2FA** muncul untuk pengguna dengan 2FA aktif. Aksi ini hanya tersedia untuk peran admin (diperiksa di backend), menghapus kunci TOTP beserta seluruh kode pemulihan, dan tidak mengubah kata sandi.
- Sistem mencegah admin menonaktifkan/menurunkan perannya sendiri dan mencegah hilangnya admin aktif terakhir.

## Log Audit Admin

Menu **Log audit** di `/admin/audit` mencatat tindakan admin yang mengubah data, sehingga perubahan peran, moderasi, dan publikasi dapat ditelusuri.

- Aksi yang dicatat: perubahan peran/status pengguna, reset kata sandi, reset 2FA, publikasi/unggulan/penghapusan novel, publikasi/draf/penghapusan bab, tambah/ubah/hapus genre, sembunyikan/tampilkan/hapus komentar, serta penyelesaian/penolakan laporan.
- Setiap entri menyimpan waktu, nama admin (snapshot saat aksi), jenis aksi, target, ringkasan, dan alamat IP. Entri hanya ditulis setelah perubahan berhasil; aksi yang ditolak tidak menghasilkan entri.
- Halaman menyediakan filter berdasarkan jenis aksi dan pencarian nama admin/ringkasan, dengan paginasi.
- Log tidak menyimpan kata sandi, kata sandi sementara hasil reset, kunci/rahasia 2FA, kode pemulihan, atau isi komentar.
- Nama admin disimpan sebagai snapshot, jadi riwayat tetap terbaca setelah akun admin dihapus (kolom `actor_id` menjadi `NULL` dan ditandai “Akun dihapus”).
- Entri tidak dapat diubah atau dihapus dari antarmuka. Retensi dan pembersihan dilakukan langsung di database.

Log ini adalah jejak audit tingkat aplikasi, **bukan** bukti anti-manipulasi: pengelola database tetap dapat mengubah atau menghapus baris. Untuk kebutuhan kepatuhan, alirkan tabel `admin_audit_logs` ke penyimpanan log eksternal yang append-only.

Jika memperbarui instalasi lama, terapkan tabel `admin_audit_logs` dari `database/schema.sql` (aman dijalankan berulang karena memakai `CREATE TABLE IF NOT EXISTS`). Log mulai terisi setelah tabel tersedia; aksi lama tidak di-backfill.

## Lupa dan Reset Kata Sandi

1. Buka `/lupa-sandi` dari tautan **Lupa kata sandi?** di halaman masuk, lalu masukkan email akun.
2. Aplikasi selalu menampilkan pesan umum, baik email terdaftar maupun tidak, untuk menghindari penebakan akun.
3. Jika akun aktif ditemukan, tautan reset dibuat dengan masa berlaku `PASSWORD_RESET_TTL` (default 60 menit) dan hanya dapat dipakai sekali.
4. Buka tautan, isi kata sandi baru (8–72 karakter) dan konfirmasinya. Setelah berhasil, hash kata sandi diperbarui, token dihapus, dan seluruh sesi lama pengguna tidak berlaku.

Pengiriman email diatur oleh `MAIL_TRANSPORT`:

- **`file`** (default saat `APP_ENV=local`): email ditulis sebagai `.eml` di `storage/mail/` dengan izin `0600`, sehingga alur dapat diuji tanpa server email. Berkas lebih lama dari tujuh hari dibersihkan otomatis saat pengiriman berikutnya. Folder ini berada di luar `public` dan di-gitignore.
- **`mail`**: memakai fungsi `mail()` milik server. Di produksi, pastikan server email/MTA, DNS (SPF/DKIM), dan `MAIL_FROM_ADDRESS` yang valid sudah dikonfigurasi, serta setel `APP_URL` ke URL publik agar tautan email tidak bergantung pada header `Host`.
- **`smtp`**: terhubung langsung ke server SMTP tanpa library eksternal. Nilai `MAIL_*`, pilihan port/mode enkripsi, contoh penyedia, dan checklist produksi dijelaskan di [Setup SMTP](#setup-smtp).

Pesan kesalahan SMTP tidak memuat kredensial. `MAIL_PASSWORD` hanya berada di `.env`; jangan commit nilainya.

Token disimpan sebagai hash SHA-256, bukan teks asli. Permintaan dibatasi per IP dan per email, dan percobaan memakai token yang salah juga dibatasi.

Jika memperbarui instalasi lama, terapkan tabel `password_resets` dari `database/schema.sql`. Seluruh pernyataan skema memakai `CREATE TABLE IF NOT EXISTS`, sehingga aman dijalankan pada database yang sudah berisi data.

## Verifikasi Email

1. Setelah registrasi, akun langsung masuk tetapi ditandai **belum terverifikasi**. Aplikasi mengirim tautan `/verifikasi-email/{token}` melalui transport email yang aktif.
2. Tautan berlaku selama `EMAIL_VERIFICATION_TTL` (default 24 jam), hanya dapat dipakai sekali, dan menampilkan halaman hasil yang jelas untuk status berhasil, tidak valid/kedaluwarsa, atau terlalu banyak percobaan.
3. Banner **Verifikasi email** tampil di bagian atas halaman untuk akun yang belum terverifikasi, lengkap dengan tombol kirim ulang. Kirim ulang dibatasi 3 permintaan per 15 menit per akun dan 10 per jam per IP; akun yang sudah terverifikasi hanya mendapat pesan informasi tanpa pengiriman baru.
4. Pembatasan ditegakkan di backend: pengguna belum terverifikasi tidak dapat berkomentar, memberi rating, atau mengaktifkan akses penulis. Membaca, perpustakaan, dan progres tetap tersedia.
5. Mengubah alamat email di profil otomatis membatalkan status verifikasi, menghapus tautan reset kata sandi yang tertunda, dan mengirim tautan verifikasi baru ke alamat tersebut. Perubahan profil lain tidak memengaruhi status verifikasi.
6. Demo dan akun yang dibuat installer langsung ditandai terverifikasi. Akun yang dinonaktifkan tidak dapat memakai tautan verifikasi.

Token dibuat acak 256-bit dan disimpan hanya sebagai hash SHA-256 di tabel `email_verifications` (satu baris per pengguna). Baris lama yang sudah kedaluwarsa dibersihkan saat token baru diterbitkan.

Jika memperbarui instalasi lama, terapkan tabel `email_verifications` dari `database/schema.sql`, lalu tandai akun yang sudah tepercaya sebagai terverifikasi:

```sql
INSERT INTO email_verifications (user_id, verified_at, created_at)
SELECT id, NOW(), NOW() FROM users
ON DUPLICATE KEY UPDATE verified_at = COALESCE(verified_at, NOW());
```

Lewati backfill tersebut bila pengguna lama memang harus memverifikasi ulang alamat emailnya.

## Verifikasi Dua Langkah (2FA)

2FA memakai TOTP (RFC 6238, SHA-1, 6 digit, periode 30 detik) sehingga dapat dipakai dengan Google Authenticator, Microsoft Authenticator, Aegis, 1Password, dan aplikasi serupa. Fitur ini opsional per akun dan memerlukan `APP_KEY` yang valid.

Aktivasi:

1. Masuk, buka `/profil`, lalu pilih **Aktifkan 2FA** pada panel **Verifikasi dua langkah**.
2. Halaman `/profil/2fa` menampilkan kunci base32 (32 karakter) dan tautan `otpauth://totp/...`. Pindai tautan atau masukkan kunci secara manual ke aplikasi autentikator.
3. Masukkan kode 6 digit dari aplikasi untuk mengonfirmasi. Setelah berhasil, 2FA aktif dan **8 kode pemulihan** ditampilkan satu kali. Simpan kode tersebut di tempat aman; kode tidak dapat ditampilkan lagi.
4. Login berikutnya meminta kata sandi lalu kode autentikator di `/masuk/2fa`. Sesi login baru dibuat hanya setelah faktor kedua valid.

Detail keamanan:

- Rahasia TOTP disimpan terenkripsi AES-256-GCM di `user_two_factor`; kunci enkripsi berasal dari `APP_KEY`. Nilai asli tidak pernah ditulis ke database atau log.
- Kode pemulihan berbentuk `XXXX-XXXX`, dibuat acak, disimpan hanya sebagai hash SHA-256 di `user_recovery_codes`, dan hangus setelah dipakai sekali.
- Kode TOTP yang sudah diterima tidak dapat dipakai ulang (anti-replay berbasis langkah waktu terakhir). Toleransi pergeseran jam ±1 langkah (30 detik).
- Sesi pending setelah kata sandi benar berlaku 10 menit dan belum memiliki hak akses apa pun. Percobaan kode yang gagal dibatasi 5 per 15 menit per akun dan 20 per 15 menit per IP; melewati batas menghapus sesi pending dan mengembalikan pengguna ke halaman masuk.
- Aktivasi dan penonaktifan juga dibatasi (masing-masing 5 percobaan per 15 menit). Menonaktifkan 2FA mewajibkan kata sandi saat ini **dan** kode autentikator atau kode pemulihan yang valid.
- Akun yang menonaktifkan 2FA menghapus rahasia TOTP beserta seluruh kode pemulihannya.
- Jika pengguna kehilangan aplikasi autentikator **dan** seluruh kode pemulihan, admin dapat memakai **Reset 2FA** di `/admin/pengguna`. Aksi ini dijaga middleware peran admin, menghapus rahasia TOTP dan kode pemulihan, tetapi tidak mengubah kata sandi; pengguna dapat masuk dengan kata sandi lalu mengaktifkan 2FA kembali.

Jika memperbarui instalasi lama, terapkan tabel `user_two_factor` dan `user_recovery_codes` dari `database/schema.sql`, lalu pastikan `APP_KEY` terisi di `.env`. Tanpa `APP_KEY`, panel 2FA menampilkan peringatan dan aktivasi ditolak di backend.

## Mengelola Novel dan Bab (Penulis/Admin)

1. Masuk dengan akun penulis atau admin.
2. Buka `/tulis`, lalu **Novel baru**.
3. Isi judul (3–180 karakter), sinopsis (30–5000 karakter), status `ongoing`/`completed`, dan minimal satu genre. Sampul opsional saat membuat novel.
4. Novel baru selalu tersimpan sebagai **draf**. Tambahkan minimal satu bab berstatus **terbit**, lalu gunakan aksi **Publikasikan** pada halaman edit novel.
5. Kelola bab melalui **Bab**: tambah, edit, ubah status draf/terbit, atau hapus.
6. Sampul dapat diganti dari halaman edit novel.

Ketentuan sampul:

- Format JPG, PNG, atau WebP.
- Maksimal 2 MB (dapat diubah melalui `UPLOAD_MAX_KB`).
- Dimensi minimal 200 × 200 piksel.
- Berkas disimpan dengan nama acak di `public/uploads/covers`; folder ini menonaktifkan eksekusi PHP.
- Unggahan divalidasi berdasarkan isi berkas (`finfo` + `getimagesize`), bukan hanya ekstensi.

Nomor bab dibuat otomatis dan unik per novel. Menghapus novel akan menghapus bab, komentar, rating, dan data terkait melalui foreign key `ON DELETE CASCADE`.

## Pengujian

### Tes integrasi HTTP

Tes ini benar-benar mengirim permintaan HTTP ke aplikasi, memakai cookie jar terpisah per aktor, memeriksa CSRF, membuat data sementara, lalu membersihkannya kembali.

Prasyarat:

- Aplikasi sudah terpasang dan `.env` ada.
- Server sedang berjalan.
- `storage/install-credentials.txt` masih ada (tes membaca kredensial demo tanpa mencetaknya).
- Folder `public/uploads/covers`, `storage/logs`, dan `storage/mail` dapat ditulis.

Jalankan:

```bash
php tests/run.php
```

Jika server memakai alamat lain:

```bash
TEST_BASE_URL=http://127.0.0.1:8080 php tests/run.php
```

Di Windows Command Prompt:

```bat
set TEST_BASE_URL=http://127.0.0.1:8080
C:\xampp\php\php.exe tests\run.php
```

Hasil terakhir pada lingkungan ini:

```text
Total pemeriksaan : 213
Lulus             : 213
Gagal             : 0
```

Ringkasan tersimpan di `tests/last-run.json`; keluaran lengkap di `tests/last-output.txt`. Keduanya artefak tes dan di-gitignore.

Cakupan tes antara lain: koneksi database, halaman publik, registrasi/login/logout, validasi, CSRF, pencarian/filter/urut/paginasi, detail novel, navigasi bab, progres baca, favorit tambah/hapus, komentar dan rating, laporan, unggahan sampul, alur draf hingga publikasi, pembatasan peran, kepemilikan novel, pengelolaan admin, output escaping, header keamanan, verifikasi email (email bertoken, banner, kirim ulang, pembatalan tautan lama, token sekali pakai, token kedaluwarsa, penolakan komentar/rating/akses penulis sebelum verifikasi, dan verifikasi ulang saat email berubah), alur lupa/reset kata sandi (pesan umum, email lokal, hash token, token sekali pakai, token kedaluwarsa, validasi sandi, login dengan sandi baru, dan pembatalan tautan saat sandi diubah), serta 2FA (aktivasi dengan kode TOTP, rahasia terenkripsi, kode pemulihan sekali tampil dan sekali pakai, sesi pending tanpa hak akses, penolakan kode salah, anti-replay, pergeseran langkah waktu, penonaktifan dengan kata sandi + kode, rate limit tantangan, status 2FA di daftar pengguna admin, penolakan reset oleh non-admin, dan reset 2FA oleh admin yang memulihkan akses masuk), serta log audit admin (penolakan akses non-admin, pencatatan perubahan peran, genre, komentar, laporan ditolak, novel/bab, reset kata sandi dan 2FA, filter aksi/pencarian, dan jaminan kata sandi sementara tidak bocor ke log).

### Tes SMTP end-to-end

Tes ini tidak mengirim email ke internet. Skrip membuat sertifikat TLS sementara, menjalankan server SMTP palsu dengan STARTTLS + AUTH, menjalankan server aplikasi dengan `MAIL_TRANSPORT=smtp`, lalu menguji alur nyata dari registrasi sampai login dengan kata sandi baru.

Prasyarat:

- Ekstensi PHP `openssl` dan `curl` aktif.
- Binary OpenSSL tersedia. Di lingkungan ini dipakai `C:\xampp\apache\bin\openssl.exe`; jika tidak ada, perintah `openssl` dari `PATH` digunakan.
- Database dan `.env` sudah siap.

Jalankan dari folder proyek:

```bash
php tests/smtp-run.php
```

Di Windows:

```bat
C:\xampp\php\php.exe tests\smtp-run.php
```

Yang diperiksa: STARTTLS sebelum autentikasi, TLS implisit (`ssl`), AUTH PLAIN dan AUTH LOGIN, verifikasi sertifikat, penolakan kata sandi salah tanpa kebocoran kredensial, pembatalan saat STARTTLS tidak tersedia, penolakan kredensial tanpa TLS, header/encoding pesan, dot-stuffing, email verifikasi beserta tautan yang menandai akun terverifikasi, tautan reset bertoken, penghapusan token setelah dipakai, dan isolasi dari kotak surat lokal.

Hasil terakhir pada lingkungan ini:

```text
Total pemeriksaan SMTP : 28
Lulus                  : 28
Gagal                  : 0
```

Ringkasan tersimpan di `tests/smtp-last-run.json`. Folder kerja sementara dihapus setelah tes; setel `SMTP_KEEP=1` untuk mempertahankan log dan pesan tangkapan. Tes ini memakai server SMTP lokal, bukan penyedia email eksternal.

### Pemeriksaan browser

Pemeriksaan visual dan interaksi dilakukan dengan Chrome headless (termasuk emulasi perangkat 390 px melalui CDP). Yang diperiksa mencakup tema gelap/terang, menu mobile, pengaturan reader, daftar isi, penyimpanan progres tamu, filter bab, dashboard penulis/admin, serta tabel admin pada layar sempit. Tangkapan layar tersimpan di `tests/tmp/` sebagai artefak lokal dan tidak di-commit.

Pemeriksaan browser tersebut bersifat manual/eksploratif, bukan rangkaian tes otomatis yang dapat dijalankan ulang dengan satu perintah.

## Pemecahan Masalah

| Gejala | Penyebab umum | Solusi |
| --- | --- | --- |
| Halaman 503 "Instalasi belum selesai" | `.env` tidak ada | Jalankan `php bin/install.php` |
| "Koneksi database gagal" | MySQL mati atau kredensial salah | Nyalakan MySQL; periksa `DB_*` di `.env` |
| Semua rute 404 di Apache | `mod_rewrite` mati / `AllowOverride` salah | Aktifkan modul dan izinkan `.htaccess` |
| Halaman tanpa CSS/JS | DocumentRoot salah | Arahkan DocumentRoot ke folder `public` |
| 419 saat mengirim form | Token CSRF kedaluwarsa/sesi berubah | Muat ulang halaman, lalu kirim lagi |
| 403 di `/admin` atau `/tulis` | Peran tidak sesuai | Masuk dengan akun berperan tepat; periksa peran di admin |
| 404 saat edit novel/bab | Bukan pemilik record | Penulis hanya dapat mengelola novelnya sendiri |
| 429 terlalu banyak percobaan | Rate limit login/komentar/rating/reset sandi | Tunggu jendela pembatasan berakhir, lalu coba lagi |
| Email reset tidak diterima | Transport belum dikonfigurasi, kredensial/port SMTP salah, atau email masuk spam | Periksa `MAIL_*`; untuk lokal gunakan `MAIL_TRANSPORT=file` dan lihat `storage/mail`; periksa log aplikasi |
| SMTP menolak koneksi | Host/port salah, firewall, atau port 465 dipakai dengan mode `tls` | Gunakan `tls` + 587 atau `ssl` + 465; pastikan server SMTP dapat dijangkau |
| STARTTLS tidak ditawarkan | Server memakai TLS implisit atau STARTTLS dimatikan | Setel `MAIL_ENCRYPTION=ssl` untuk port 465, atau aktifkan STARTTLS pada server |
| Sertifikat SMTP gagal diverifikasi | CA internal/self-signed atau nama host tidak cocok | Setel `MAIL_CAFILE` ke CA yang benar dan gunakan hostname yang tercantum di sertifikat |
| Autentikasi SMTP ditolak (535) | Kata sandi salah, butuh kata sandi aplikasi, atau kredensial dikirim tanpa TLS | Perbaiki kredensial; autentikasi hanya berjalan setelah TLS aktif |
| Tautan reset tidak valid | Sudah dipakai, kedaluwarsa, atau `APP_URL` salah | Minta tautan baru; pastikan `APP_URL` menunjuk ke instalasi yang benar |
| Tautan verifikasi tidak valid | Sudah dipakai, kedaluwarsa, atau diganti tautan yang lebih baru | Masuk, lalu gunakan tombol kirim ulang pada banner verifikasi |
| Komentar/rating/akses penulis ditolak | Email akun belum terverifikasi | Buka tautan verifikasi di email atau kirim ulang dari banner |
| Unggahan sampul ditolak | Format/ukuran/dimensi tidak memenuhi syarat | Gunakan JPG/PNG/WebP, ≤ 2 MB, ≥ 200×200 |
| Port 8080 dipakai | Proses lain memakai port | Jalankan di port lain, mis. `php -S 127.0.0.1:8081 -t public public/router.php` |
| Error izin tulis | Folder `storage`/`uploads` tidak writable | Beri izin tulis kepada user web server |

Log aplikasi berada di `storage/logs/app.log`. Log tidak diletakkan di folder publik. Jika `APP_DEBUG=false`, pengguna hanya melihat halaman error umum.

## Keamanan yang Diterapkan

- Kata sandi disimpan dengan `password_hash()` (`PASSWORD_DEFAULT`) dan diverifikasi dengan `password_verify()`; tidak ada kata sandi teks biasa.
- Semua query data memakai PDO prepared statement.
- Output halaman di-escape dengan `htmlspecialchars`; isi bab dirender melalui pemformat paragraf yang aman.
- CSRF token global untuk seluruh permintaan yang mengubah data, termasuk permintaan AJAX.
- Session memakai nama kustom, `HttpOnly`, `SameSite=Lax`, mode ketat, dan ID diregenerasi saat login/perubahan kata sandi; sidik jari sesi terikat pada hash kata sandi sehingga reset oleh admin otomatis membatalkan sesi lama; logout menghancurkan session.
- Otorisasi dicek di backend: middleware peran + kepemilikan record. Aksi admin/penulis tidak hanya disembunyikan di UI.
- Rate limit untuk login, registrasi, komentar, rating, laporan, perubahan kata sandi, permintaan/penggunaan tautan reset kata sandi, serta penggunaan dan pengiriman ulang tautan verifikasi email.
- Token reset kata sandi dan token verifikasi email dibuat acak 256-bit, disimpan hanya sebagai hash SHA-256, memiliki masa berlaku, dan tidak dapat dipakai ulang. Respons permintaan reset selalu generik agar keberadaan akun tidak dapat ditebak. Tautan reset yang belum dipakai dibatalkan saat kata sandi diubah (mandiri atau oleh admin), email diganti, atau akun dinonaktifkan.
- Verifikasi email ditegakkan di backend, bukan hanya lewat tampilan: komentar, rating, dan peningkatan peran penulis menolak akun yang belum terverifikasi. Mengganti email membatalkan status verifikasi dan memaksa verifikasi ulang alamat baru.
- 2FA TOTP: rahasia dienkripsi AES-256-GCM dengan `APP_KEY`, kode pemulihan disimpan sebagai hash SHA-256 dan sekali pakai, kode TOTP tidak dapat dipakai ulang, sesi pending berlaku 10 menit tanpa hak akses, dan tantangan/aktivasi/penonaktifan dibatasi di backend. Reset 2FA hanya tersedia untuk admin dan dijaga middleware peran.
- Transport SMTP memverifikasi sertifikat server (TLS 1.2/1.3, `verify_peer` dan `verify_peer_name` aktif), mewajibkan STARTTLS pada mode `tls`, menolak kredensial tanpa TLS, serta tidak menuliskan kata sandi ke pesan kesalahan atau log.
- Log audit mencatat aksi admin yang berhasil mengubah data (pelaku, aksi, target, ringkasan, IP, waktu) dan hanya dapat dibaca lewat halaman admin. Kata sandi, kata sandi sementara, rahasia/kode 2FA, dan isi komentar tidak pernah ditulis ke log.
- Unggahan divalidasi tipe isi, ukuran, dan dimensi; nama file diacak; eksekusi PHP di folder unggahan dinonaktifkan.
- Header keamanan: CSP, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, dan `Permissions-Policy`.
- Kredensial database hanya di `.env` yang di-gitignore; tidak ada kata sandi admin permanen di source.
- Folder internal berada di luar `public`; root proyek tidak boleh menjadi DocumentRoot.

Aplikasi belum diaudit oleh pihak ketiga. Pengujian yang dilakukan adalah tes integrasi HTTP dan pemeriksaan browser pada lingkungan ini, bukan jaminan keamanan menyeluruh.

## Batasan yang Diketahui

- 2FA memakai TOTP aplikasi autentikator; tidak ada OTP via SMS/email, WebAuthn/passkey, atau opsi "ingat perangkat ini". Kehilangan aplikasi autentikator dan seluruh kode pemulihan hanya dapat dipulihkan melalui reset 2FA oleh admin, bukan layanan mandiri. Verifikasi email tersedia, tetapi akun yang belum terverifikasi tetap dapat login dan membaca; hanya komentar, rating, dan peningkatan peran penulis yang dibatasi. Reset kata sandi mandiri melalui email tersedia; reset oleh admin tetap dapat dilakukan.
- Transport email `file` dan `smtp` diuji di lingkungan ini. SMTP diuji terhadap server lokal dengan STARTTLS dan AUTH, bukan terhadap penyedia eksternal seperti Gmail atau Outlook. Pengiriman nyata, reputasi domain, SPF/DKIM, bounce, dan spam filter tidak diuji di sini. Transport `mail()` tetap bergantung pada konfigurasi MTA server.
- Unggahan gambar tidak di-re-encode (GD tidak tersedia di lingkungan ini); validasi mengandalkan `finfo` dan `getimagesize`.
- CSP mengizinkan `style-src 'unsafe-inline'` karena beberapa gaya ditulis inline.
- Pengujian Apache/`mod_rewrite` tidak dijalankan di lingkungan ini; yang diuji adalah server bawaan PHP. Konfigurasi Apache disediakan, tetapi perlu diverifikasi pada pemasangan Apache masing-masing.
- Pemeriksaan visual terutama dilakukan di Chrome; belum ada rangkaian otomatis lintas browser atau audit Lighthouse formal.
- Data contoh berisi 3–4 bab per novel. Filter bab di reader diuji dengan data sementara.
- Aplikasi belum menyertakan konfigurasi HTTPS. Gunakan HTTPS dan `APP_DEBUG=false` saat produksi.
- Log audit bersifat aplikasi: belum ada ekspor, retensi otomatis, atau pengiriman ke penyimpanan append-only eksternal, sehingga pengelola database masih dapat mengubahnya.
- Tidak ada pipeline CI/CD atau Composer; dependensi bersifat bawaan PHP.

## Konten dan Lisensi Data Contoh

Seluruh novel, sinopsis, bab, nama penulis, dan sampul SVG pada `database/seed.sql` serta `public/assets/images/covers` dibuat khusus untuk NovelVerse. Tidak ada karya berhak cipta milik pihak lain yang disalin. Ganti data contoh sebelum digunakan sebagai situs produksi.

Kode aplikasi disediakan untuk keperluan proyek ini. Tidak ada lisensi pihak ketiga yang diperlukan karena tidak memakai library eksternal.
