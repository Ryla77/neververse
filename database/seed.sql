-- NovelVerse - Data Contoh
-- Jalankan setelah bin/install.php membuat akun demo (admin, penulis, pembaca).
-- Seluruh cerita di bawah ini adalah karya orisinal untuk pengujian.

SET NAMES utf8mb4;

SET @rania := (SELECT id FROM users WHERE email = 'rania@novelverse.local');
SET @aditya := (SELECT id FROM users WHERE email = 'aditya@novelverse.local');
SET @sekar := (SELECT id FROM users WHERE email = 'sekar@novelverse.local');
SET @bimo := (SELECT id FROM users WHERE email = 'bimo@novelverse.local');
SET @dinda := (SELECT id FROM users WHERE email = 'dinda@novelverse.local');
SET @fajar := (SELECT id FROM users WHERE email = 'fajar@novelverse.local');
SET @nadia := (SELECT id FROM users WHERE email = 'nadia@novelverse.local');

INSERT INTO categories (name, slug, description) VALUES
('Romansa', 'romansa', 'Kisah cinta, pertemuan kembali, dan perasaan yang tumbuh perlahan.'),
('Fantasi', 'fantasi', 'Dunia dengan sihir, gerbang misterius, dan makhluk yang tidak biasa.'),
('Reinkarnasi', 'reinkarnasi', 'Kehidupan yang berulang dan ingatan dari masa yang telah lewat.'),
('Kerajaan', 'kerajaan', 'Intrik istana, mahkota, dan perjuangan memperebutkan kekuasaan.'),
('Petualangan', 'petualangan', 'Perjalanan melintasi laut, pulau, dan tempat yang belum ada di peta.'),
('Misteri', 'misteri', 'Teka-teki lama, arsip hilang, dan kebenaran yang disembunyikan.');

-- 1. Lentera di Ujung Musim
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@rania, 'Lentera di Ujung Musim', 'lentera-di-ujung-musim',
'Enam tahun berlalu sejak Langit pergi tanpa penjelasan. Kini ia kembali ke kota kecil untuk merenovasi stasiun tua, tepat ketika Aluna berjuang mempertahankan toko buku warisan neneknya. Di antara aroma kopi dan hujan yang tak berkesudahan, keduanya harus menyelesaikan peta yang dulu mereka tinggalkan.',
'assets/images/covers/lentera.svg', 'ongoing', 'published', 1, 4820,
DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_SUB(NOW(), INTERVAL 3 HOUR));
SET @n1 := LAST_INSERT_ID();

-- 2. Bayang-Bayang Pulau Kelabu
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@aditya, 'Bayang-Bayang Pulau Kelabu', 'bayang-bayang-pulau-kelabu',
'Surat tanpa nama membawa Ilyas ke Pulau Kelabu, tempat mercusuar padam pada malam yang tidak seharusnya. Di balik kabut dan catatan harian lama, ia menemukan rahasia yang menghubungkannya dengan pulau itu lebih dalam daripada yang ia sangka.',
'assets/images/covers/pulau-kelabu.svg', 'completed', 'published', 0, 3910,
DATE_SUB(NOW(), INTERVAL 120 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY));
SET @n2 := LAST_INSERT_ID();

-- 3. Putri yang Menolak Takdir
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@sekar, 'Putri yang Menolak Takdir', 'putri-yang-menolak-takdir',
'Putri Aruna tidak ingin pernikahannya ditentukan dalam satu jamuan makan malam. Dengan bantuan pangeran yang juga enggan menikah, ia menyusun perjanjian yang mengubah aturan istana, tetapi takdir kerajaan tidak pernah sederhana.',
'assets/images/covers/putri-takdir.svg', 'ongoing', 'published', 0, 4550,
DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));
SET @n3 := LAST_INSERT_ID();

-- 4. Arunika: Jejak Sang Pengembara
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@bimo, 'Arunika: Jejak Sang Pengembara', 'arunika-jejak-sang-pengembara',
'Peta tua peninggalan ayahnya menunjukkan pulau yang tidak ada di peta resmi dan seorang kakak yang tidak pernah diceritakan. Arunika bergabung dengan kapal Makara untuk menemukan kebenaran, meskipun laut menyimpan badai yang lebih besar dari dugaannya.',
'assets/images/covers/arunika.svg', 'ongoing', 'published', 0, 2760,
DATE_SUB(NOW(), INTERVAL 32 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));
SET @n4 := LAST_INSERT_ID();

-- 5. Sembilan Nyawa Terakhir
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@rania, 'Sembilan Nyawa Terakhir', 'sembilan-nyawa-terakhir',
'Aksara mengingat delapan kematian yang tidak seharusnya ia alami. Di kehidupan kesembilan, seorang pasien datang membawa jawaban dan pertanyaan yang lebih berat: bagaimana cara hidup tanpa terus melarikan diri dari masa lalu.',
'assets/images/covers/sembilan-nyawa.svg', 'completed', 'published', 0, 5120,
DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY));
SET @n5 := LAST_INSERT_ID();

-- 6. Gerbang Kabut Utara
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@aditya, 'Gerbang Kabut Utara', 'gerbang-kabut-utara',
'Nara mewarisi rumah kakek, tiga kambing, dan kunci besi yang tidak cocok dengan pintu mana pun. Ketika kabut turun dan suara dari bukit memanggil namanya, ia mulai memahami mengapa kakeknya menghabiskan seluruh hidup menjaga sebuah gerbang.',
'assets/images/covers/gerbang-kabut.svg', 'ongoing', 'published', 0, 3300,
DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 5 HOUR));
SET @n6 := LAST_INSERT_ID();

-- 7. Rumah Kopi di Bawah Hujan
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@sekar, 'Rumah Kopi di Bawah Hujan', 'rumah-kopi-di-bawah-hujan',
'Mira kembali ke kota kecil untuk menjual rumah kopi warisan neneknya. Namun resep yang belum selesai, seorang tukang kayu bernama Bara, dan hujan yang tak pernah berhenti membuat rencananya berubah satu per satu.',
'assets/images/covers/rumah-kopi.svg', 'completed', 'published', 0, 2980,
DATE_SUB(NOW(), INTERVAL 75 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));
SET @n7 := LAST_INSERT_ID();

-- 8. Arsip Kota yang Hilang
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@bimo, 'Arsip Kota yang Hilang', 'arsip-kota-yang-hilang',
'Kiran menemukan katalog berisi nama jalan yang tidak ada di peta kota. Semakin dalam ia menelusuri arsip, semakin jelas bahwa kota pernah menghapus satu permukiman dari ingatannya, dan keluarganya sendiri terlibat di dalamnya.',
'assets/images/covers/arsip-kota.svg', 'ongoing', 'published', 0, 2140,
DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY));
SET @n8 := LAST_INSERT_ID();

-- 9. Mahkota dari Debu
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@rania, 'Mahkota dari Debu', 'mahkota-dari-debu',
'Laras, pandai besi tanpa nama, menempa mahkota dari logam hitam yang tidak dikenal. Ketika raja ditemukan tewas mengenakan mahkota itu, satu-satunya orang yang bisa membersihkan namanya adalah putra mahkota yang juga kehilangan ayahnya.',
'assets/images/covers/mahkota-debu.svg', 'ongoing', 'published', 0, 3630,
DATE_SUB(NOW(), INTERVAL 55 DAY), DATE_SUB(NOW(), INTERVAL 8 HOUR));
SET @n9 := LAST_INSERT_ID();

-- 10. Penjaga Api Terakhir
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@sekar, 'Penjaga Api Terakhir', 'penjaga-api-terakhir',
'Kalis menjaga api abadi di puncak Gunung Arundel sendirian. Ketika api memperingatkannya tentang sesuatu di bawah gunung, ia harus memilih antara percaya pada tradisi keluarganya atau membuka rahasia yang bisa menyelamatkan seluruh kota.',
'assets/images/covers/penjaga-api.svg', 'ongoing', 'published', 0, 1890,
DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 12 HOUR));
SET @n10 := LAST_INSERT_ID();

-- 11. Kembalinya Sang Penenun Waktu
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@aditya, 'Kembalinya Sang Penenun Waktu', 'kembalinya-sang-penenun-waktu',
'Daru, seorang tukang jam, terbangun dengan tangan penuh luka benang dan ingatan tentang menara tanpa dinding. Ketika seorang pelanggan membawa jam pasir yang berjalan mundur, ia harus memutuskan apakah memperbaiki waktu atau membiarkan masa lalunya tetap utuh.',
'assets/images/covers/penenun-waktu.svg', 'ongoing', 'published', 0, 2480,
DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 6 HOUR));
SET @n11 := LAST_INSERT_ID();

-- 12. Ekspedisi Langit Selatan
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@bimo, 'Ekspedisi Langit Selatan', 'ekspedisi-langit-selatan',
'Ketika balon kapal udara Melati bocor di atas laut selatan, Kapten Sena menemukan pulau melayang yang tidak tercatat di peta. Di sana, sebuah stasiun cuaca tua dan seorang penjaga yang tertinggal menanti mereka.',
'assets/images/covers/langit-selatan.svg', 'completed', 'published', 0, 3120,
DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY));
SET @n12 := LAST_INSERT_ID();

-- 13. Catatan Musim Kemarau (draf, tidak tampil untuk pembaca)
INSERT INTO novels (author_id, title, slug, synopsis, cover_path, status, publish_status, is_featured, views_count, created_at, updated_at)
VALUES (@rania, 'Catatan Musim Kemarau', 'catatan-musim-kemarau',
'Draf novel tentang seorang guru musik yang kembali ke kampung halaman selama musim kemarau. Cerita belum dipublikasikan dan hanya terlihat oleh penulis serta admin.',
'assets/images/covers/catatan-kemarau.svg', 'ongoing', 'draft', 0, 0,
DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));
SET @n13 := LAST_INSERT_ID();

INSERT INTO novel_categories (novel_id, category_id)
SELECT @n1, id FROM categories WHERE slug = 'romansa';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n2, id FROM categories WHERE slug = 'misteri';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n3, id FROM categories WHERE slug = 'kerajaan';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n3, id FROM categories WHERE slug = 'romansa';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n4, id FROM categories WHERE slug = 'petualangan';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n4, id FROM categories WHERE slug = 'fantasi';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n5, id FROM categories WHERE slug = 'reinkarnasi';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n6, id FROM categories WHERE slug = 'fantasi';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n6, id FROM categories WHERE slug = 'misteri';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n7, id FROM categories WHERE slug = 'romansa';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n8, id FROM categories WHERE slug = 'misteri';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n9, id FROM categories WHERE slug = 'kerajaan';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n9, id FROM categories WHERE slug = 'misteri';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n10, id FROM categories WHERE slug = 'fantasi';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n11, id FROM categories WHERE slug = 'reinkarnasi';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n11, id FROM categories WHERE slug = 'fantasi';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n12, id FROM categories WHERE slug = 'petualangan';
INSERT INTO novel_categories (novel_id, category_id)
SELECT @n13, id FROM categories WHERE slug = 'romansa';

-- Bab 1: Lentera di Ujung Musim
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n1, 1, 'Hujan Pertama di Jalan Kenanga',
'Hujan turun tepat saat Aluna selesai menurunkan kotak buku dari rak paling atas. Aroma kertas tua bercampur wangi kayu membuat toko kecil di Jalan Kenanga terasa seperti rumah yang tidak pernah berubah. Di luar, payung-payung berwarna melintas seperti bunga yang tumbuh di atas aspal basah.

Bel telah berbunyi tiga kali hari itu, tetapi belum ada yang benar-benar membeli. Aluna tidak keberatan. Ia menyalakan lampu kuning di sudut baca, lalu membuka buku catatan pelanggan yang sudah setengah penuh.

Pintu terbuka lagi. Seorang pria berdiri di ambang, rambutnya basah dan jaket abu-abunya gelap oleh hujan. Ia memandang rak-rak buku seolah mencari sesuatu yang sudah lama hilang.

"Masih menyimpan buku peta lama?" tanyanya. Suaranya membuat Aluna berhenti bernapas. Enam tahun lalu, suara yang sama berjanji akan kembali sebelum musim hujan berakhir.

Aluna menutup buku catatan. "Tergantung. Kau ingin membeli atau hanya berteduh, Langit?"

Pria itu tersenyum tipis. Di luar, hujan semakin deras, seolah sengaja menahan mereka berdua di ruangan yang sama.',
'published', DATE_SUB(NOW(), INTERVAL 44 DAY), DATE_SUB(NOW(), INTERVAL 44 DAY), DATE_SUB(NOW(), INTERVAL 44 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n1, 2, 'Kopi Tanpa Gula',
'Aluna menyeduh dua cangkir kopi. Satu ia beri gula, satu tidak. Ia masih ingat Langit tidak pernah menyukai manis, dan ia membenci dirinya karena masih mengingat hal sekecil itu.

Langit duduk di meja dekat jendela, tempat yang dulu biasa mereka pakai untuk menggambar peta khayalan. Sekarang meja itu penuh tumpukan buku diskon dan lilin aromaterapi.

"Stasiun tua akan direnovasi," kata Langit. "Aku ikut proyeknya. Mungkin tinggal di sini beberapa bulan."

Aluna menaruh cangkir agak keras. "Kau kembali untuk pekerjaan."

"Tidak sepenuhnya." Langit menatap cangkirnya. "Aku kembali karena ada janji yang belum selesai."

Percakapan berhenti di situ. Hujan mengetuk jendela, dan dentingnya terasa lebih jujur daripada kata-kata mereka. Aluna pura-pura merapikan rak, meskipun buku-buku di sana sudah tersusun sempurna.

"Toko ini masih memakai nama yang sama," kata Langit kemudian.

"Kenapa harus diganti?"

"Karena dulu kau bilang, lentera hanya berguna kalau ada yang menyalakannya." Ia mengangkat cangkir. "Aku berharap kau masih menyalakannya."

Aluna tidak menjawab. Namun ketika Langit hendak pergi, ia menyelipkan payung kedua ke tangan pria itu.',
'published', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n1, 3, 'Peta yang Tidak Selesai',
'Langit kembali tiga hari kemudian dengan gulungan kertas di tangan. Ia membuka peta denah stasiun tua di meja baca, memperlihatkan lorong-lorong yang sudah tidak dipakai dan ruang tunggu yang atapnya runtuh.

"Ada satu ruangan yang tidak tercantum di arsip kota," katanya. "Aku menemukannya di balik dinding lama."

Aluna mencondongkan tubuh. Di sudut peta, ada sketsa kecil yang sangat ia kenal. Dua orang anak duduk di bangku, menggambar matahari dan kereta yang tidak pernah datang.

"Kau masih menyimpan sketsa itu?" tanyanya.

"Aku menyimpan semuanya." Langit mengeluarkan buku tipis dari tasnya. "Termasuk peta yang tidak selesai ini. Dulu kita berhenti karena bertengkar."

Aluna menyentuh garis pensil yang sudah pudar. Pertengkaran itu bukan tentang peta, melainkan tentang keberangkatan Langit ke kota lain. Mereka terlalu muda untuk tahu cara meminta satu sama lain bertahan.

"Mari selesaikan," kata Aluna pelan.

Langit mengangkat pensil. "Kau masih ingat jalan menuju danau di ujung garis?"

"Tentu." Aluna menarik kursi. "Kau yang selalu lupa arah pulang."

Mereka menggambar hingga lampu toko berkedip, dan untuk pertama kalinya setelah enam tahun, tawa Aluna terdengar ringan.',
'published', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n1, 4, 'Cahaya di Ujung Musim',
'Festival lentera diadakan setiap akhir musim hujan. Penduduk kota menulis harapan pada kertas tipis, lalu melepaskannya ke langit yang masih basah. Aluna datang tahun ini bukan sebagai penonton.

Langit menunggu di depan stasiun yang kini berdiri megah dengan cat baru. Di tangannya ada lentera kecil bergambar kereta dan dua sosok yang duduk di bangku.

"Aku tidak pandai membuat pidato," katanya. "Jadi aku membuat gambar."

Aluna menerima lentera itu. Gambar di permukaannya tidak sempurna, tetapi cukup jelas untuk membuat matanya hangat.

"Kau akan tinggal setelah proyek selesai?" tanyanya.

"Kalau kau mengizinkan, aku ingin tinggal lebih lama dari itu."

Aluna menatap langit. Lentera-lentera lain mulai naik, membawa harapan orang-orang yang tidak ia kenal. Ia menulis satu kalimat di kertas kecil, lalu menyelipkannya ke dalam lentera Langit.

"Apa yang kau tulis?" tanya Langit.

Aluna tersenyum. "Bahwa musim hujan tahun ini tidak boleh berakhir terlalu cepat."

Lentera mereka naik paling akhir, tetapi cahayanya bertahan paling lama di antara semua yang terbang malam itu.',
'published', DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Bab 1-3: Bayang-Bayang Pulau Kelabu
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n2, 1, 'Surat dari Pulau Kelabu',
'Surat itu tiba tanpa nama pengirim. Hanya ada alamat Pulau Kelabu, tinta biru yang luntur, dan satu kalimat: "Mercusuar padam pada tanggal yang tidak seharusnya."

Ilyas membacanya tiga kali di meja redaksi. Sebagai jurnalis muda, ia terbiasa menerima surat aneh. Namun amplop ini berisi foto dirinya saat masih kecil, berdiri di depan mercusuar yang belum pernah ia kunjungi.

Ia meminta izin liputan selama satu minggu. Editornya menolak, lalu berubah pikiran setelah melihat foto yang sama. "Jangan melakukan apa pun yang bodoh," pesan editornya.

Pulau Kelabu hanya dapat dicapai dengan perahu nelayan dari pelabuhan kecil. Sepanjang perjalanan, air laut berwarna seperti timah cair. Nelayan yang mengantarnya tidak mau berbicara banyak.

"Siapa yang menyalakan mercusuar sekarang?" tanya Ilyas.

Nelayan itu menatap cakrawala. "Tidak ada. Katanya penjaga lama sudah pergi. Tapi setiap malam, ada cahaya di menara."

Ilyas tiba saat senja. Desa di pulau itu sunyi, jendela-jendela tertutup, dan di kejauhan mercusuar berdiri seperti jari batu yang menunjuk langit gelap.',
'published', DATE_SUB(NOW(), INTERVAL 110 DAY), DATE_SUB(NOW(), INTERVAL 110 DAY), DATE_SUB(NOW(), INTERVAL 110 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n2, 2, 'Mercusuar yang Padam',
'Rumah penjaga mercusuar berisi debu, buku catatan, dan satu kursi yang menghadap jendela. Ilyas menemukan catatan harian bertanggal dua puluh tahun lalu. Halaman terakhir ditulis dengan tulisan tergesa-gesa.

"Mereka datang lagi malam ini. Jika cahaya mati, jangan menyalakannya."

Di luar, angin berubah menjadi suara seperti orang menangis. Ilyas menyalakan lampu senter dan menaiki tangga spiral. Di puncak menara, lampu besar itu dingin dan mati.

Ia mencoba sakelar. Tidak ada listrik. Namun ketika ia mengarahkan senter ke cermin pemantul, cahaya kecil memantul ke laut, dan untuk sesaat ia melihat sesuatu di antara ombak.

Perahu-perahu tanpa layar bergerak menuju pulau.

Ilyas berlari turun. Di dapur, ia menemukan radio tua yang masih menyala. Suara parau memanggil nama yang tidak ia kenal, lalu berhenti. Di ambang pintu, seorang perempuan tua berdiri dengan lentera.

"Kau menyalakan mercusuar," katanya. "Sekarang mereka tahu kau ada di sini."

Ilyas ingin bertanya, tetapi perempuan itu sudah berbalik menuju pantai. Langkahnya tidak meninggalkan jejak di pasir basah.',
'published', DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 100 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n2, 3, 'Nama di Balik Kabut',
'Kabut turun sebelum fajar. Ilyas mengikuti perempuan tua itu ke pantai, tetapi yang ia temukan hanya deretan batu dan sebuah perahu rusak. Di dalam perahu, ada buku catatan yang sama dengan yang ia baca di menara.

Halaman-halamannya berisi nama-nama penduduk pulau. Di bagian paling bawah, namanya sendiri tertulis dengan tinta biru.

"Kau anak yang dikirim pergi," kata perempuan tua itu dari belakang. "Dua puluh tahun lalu, ketika cahaya mati, kami mengirim satu anak agar tidak semua hilang."

Ilyas menoleh. Wajah perempuan itu berubah di antara kabut, seperti gambar yang basah oleh air. Ia mengingat sesuatu yang seharusnya tidak ia ingat: tangannya kecil, memegang lentera, dan suara ibunya menyuruhnya berlari.

"Mercusuar bukan untuk kapal," lanjutnya. "Mercusuar untuk mengingatkan kami bahwa pagi masih ada."

Ilyas menyalakan lentera. Cahaya kecil itu menyebar ke seluruh pantai, dan kabut mulai surut. Ketika matahari terbit, desa itu tampak seperti desa biasa. Perempuan tua telah hilang.

Di tangan Ilyas tinggal buku catatan, dan pada halaman terakhir muncul tulisan baru: "Terima kasih sudah kembali."',
'published', DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 90 DAY));

-- Bab 1-3: Putri yang Menolak Takdir
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n3, 1, 'Mahkota yang Terlalu Berat',
'Aruna menghitung tiga belas kancing di lengan gaunnya, lalu berhenti. Di depan cermin, mahkota kecil di kepalanya terlihat seperti barang pinjaman yang tidak pernah pas.

"Ayah akan mengumumkan pertunanganmu malam ini," kata dayangnya. "Pangeran Edran sudah tiba."

Aruna menatap pantulan dirinya. Ia tidak membenci Pangeran Edran. Ia hanya membenci gagasan bahwa masa depannya ditentukan dalam satu jamuan makan malam.

Di balai utama, lilin-lilin menyala seperti pasukan kecil. Raja Valen mengangkat cangkir dan menyebut nama putrinya. Seluruh aula menoleh.

Aruna berdiri. "Aku berterima kasih atas kehormatan ini," katanya. "Namun aku memilih mempelajari hukum kerajaan sebelum memutuskan hal yang lebih besar."

Suasana membeku. Pangeran Edran tersenyum tipis, seolah sudah menduga. Raja Valen tidak tersenyum.

"Kita bicara setelah jamuan," katanya.

Aruna membungkuk. Di dalam dadanya, jantung berdebar seperti burung yang baru pertama kali melihat langit. Malam itu, ia mengerti satu hal: mahkota bukan hanya emas. Mahkota adalah suara yang harus ia temukan sendiri.',
'published', DATE_SUB(NOW(), INTERVAL 39 DAY), DATE_SUB(NOW(), INTERVAL 39 DAY), DATE_SUB(NOW(), INTERVAL 39 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n3, 2, 'Penyamaran di Balai Kitab',
'Balai Kitab berada di sayap barat istana. Hanya sarjana, juru tulis, dan penjaga yang boleh masuk. Aruna masuk dengan rambut diikat rapi dan jubah abu-abu milik sepupunya.

Rak-rak menjulang sampai plafon. Aroma kertas membuatnya merasa lebih dekat dengan kebebasan daripada taman mana pun. Ia mencari kitab hukum tentang pernikahan kerajaan, lalu menemukan catatan tambahan di margin.

Catatan itu ditulis oleh seorang perempuan empat generasi lalu. "Seorang putri dapat menolak perjodohan jika ia mengajukan syarat yang menguntungkan kerajaan."

Aruna hampir tertawa. Ia menyalin halaman itu ke buku kecil, lalu menyembunyikannya di balik jubah.

Saat hendak keluar, suara langkah menghentikannya. Pangeran Edran berdiri di ujung lorong, memegang lilin. Ia tidak membawa pengawal.

"Kau membaca lebih cepat dari yang kuduga," katanya.

Aruna mengangkat dagu. "Apakah kau akan melaporkanku?"

Edran melihat sekeliling. "Tidak, kalau kau mau mendengar tawaranku. Aku juga tidak ingin menikah karena perintah."

Untuk pertama kalinya malam itu, Aruna merasa jalan di depannya tidak sepenuhnya tertutup.',
'published', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n3, 3, 'Perjanjian di Taman Malam',
'Mereka bertemu di taman belakang istana, di bawah pohon yang daunnya berbisik setiap kali angin lewat. Edran membawa peta perdagangan, Aruna membawa salinan hukum lama.

"Kalau kita menolak perjodohan, ayahku akan menganggapnya penghinaan," kata Edran.

"Kalau kita menerimanya tanpa syarat, kita akan menghabiskan sisa hidup saling menyalahkan," jawab Aruna.

Mereka menyusun perjanjian: aliansi dagang antara dua kerajaan, sekolah hukum untuk perempuan bangsawan, dan masa satu tahun untuk menentukan apakah mereka memang ingin menikah. Jika tidak, aliansi tetap berjalan.

"Kau menyebut ini perjanjian," kata Edran. "Ayahmu mungkin menyebutnya pemberontakan."

"Biarlah. Setidaknya itu pemberontakan yang punya pasal."

Pagi berikutnya, Aruna meletakkan dokumen di meja raja. Ia menjelaskan setiap pasal tanpa gemetar. Raja Valen membaca lama, lalu mengangkat wajah.

"Kau belajar ini dari mana?"

"Dari perpustakaan, Ayah. Tempat yang selalu kau kira hanya berisi debu."

Raja menatap putrinya seperti baru mengenalnya. Di luar jendela, matahari naik di atas menara, dan Aruna tahu takdirnya belum selesai. Ia baru saja mulai menulisnya.',
'published', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Bab 1-3: Arunika
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n4, 1, 'Peta yang Berbicara',
'Arunika menemukan peta itu di antara barang ayahnya yang sudah meninggal. Kertasnya tebal, warnanya seperti kulit jeruk kering, dan di sudut kanan bawah ada tanda tangan yang tidak ia kenal.

Peta itu menunjukkan jalan laut menuju pulau yang tidak ada di peta resmi. Di sebelah pulau, seseorang menulis: "Kakakmu masih menunggu di sini."

Arunika tidak punya kakak. Setidaknya, tidak ada yang pernah ia ingat. Namun ketika ia menyentuh tulisan itu, ujung jarinya terasa panas.

Ia bertanya kepada ibunya. Ibunya menutup wajah dengan celemek dan pergi ke dapur tanpa menjawab. Malam itu, Arunika mendengar percakapan di balik pintu: "Sudah waktunya ia tahu."

Keesokan paginya, Arunika mendaftar sebagai juru peta di kapal Makara. Kaptennya perempuan tua bernama Nusa, yang mengenal ayah Arunika dan menyimpan banyak rahasia.

"Peta itu bukan untuk orang yang takut kehilangan arah," kata Nusa.

Arunika mengikat rambutnya. "Aku sudah kehilangan arah sejak peta ini masuk ke tanganku."

Kapal berangkat saat air pasang, membawa sepuluh pelaut dan satu peta yang seolah bernapas di bawah dek.',
'published', DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 31 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n4, 2, 'Badai di Selat Perak',
'Badai datang tanpa tanda. Langit yang tadinya biru berubah hitam dalam beberapa menit, dan gelombang naik lebih tinggi dari tiang kapal. Arunika diikat di tiang agar tidak terlempar.

"Peta!" teriak Nusa. "Apa katanya?"

Arunika membuka peta dengan tangan gemetar. Garis-garis di kertas itu bergerak, membentuk jalur baru yang tidak ada sebelumnya. Ia berteriak arah, dan juru mudi mengikuti.

Untuk sesaat, kapal melaju di antara dinding air. Arunika melihat sesuatu di dalam gelombang: bayangan kapal lain, lebih tua, berlayar di arah yang berlawanan.

Ketika badai mereda, mereka berada di laut yang tenang. Matahari bersinar seperti tidak pernah ada badai. Para pelaut mengucapkan syukur, tetapi Nusa menatap Arunika dengan heran.

"Peta itu memilih kau," katanya.

Arunika menatap kertas di tangannya. Di dekat tanda pulau, muncul satu kata baru: "Hampir."

Ia menggulung peta. Jantungnya masih berdebar, tetapi untuk pertama kalinya ia merasa bukan hanya mengikuti garis. Ia mulai memahami bahwa petanya berbicara, dan ia harus belajar mendengarkan.',
'published', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n4, 3, 'Desa yang Tidak Ada di Peta',
'Pulau itu muncul saat kabut tipis. Tidak ada pelabuhan, hanya pantai berbatu dan jalan setapak yang dipenuhi rumput. Arunika turun lebih dulu, membawa peta dan sebilah belati.

Di tengah pulau, ada desa dengan rumah-rumah kayu. Penduduknya menyambut mereka seperti tamu yang sudah lama ditunggu. Seorang perempuan tua memegang lengan Arunika.

"Kau mirip ayahmu," katanya.

Arunika mengikuti perempuan itu ke sebuah rumah. Di dindingnya tergantung peta-peta yang sama, semuanya bertanda tangan ayahnya. Di sudut ruangan, seorang pria duduk membelakangi pintu.

Pria itu menoleh. Wajahnya lebih tua, tetapi mata dan senyumnya tidak asing. "Aku menunggu kau menyelesaikan petanya," katanya.

"Kau ... kakakku?"

"Kakak yang tidak pernah ada di cerita keluargamu. Itu satu-satunya cara ayah melindungi kami."

Di luar, Nusa memanggil. Kabut mulai turun lagi, dan peta di tangan Arunika menghangat. Ia belum mendapatkan semua jawaban, tetapi ia sudah menemukan awal dari pertanyaan yang benar.',
'published', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Bab 1-3: Sembilan Nyawa Terakhir
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n5, 1, 'Ingatan yang Tidak Seharusnya Ada',
'Aksara terbangun dengan rasa asin di bibir, seperti baru saja menelan air laut. Ia tidak pernah ke laut. Ia tinggal di kota pegunungan, bekerja sebagai peracik obat di rumah kecil dekat pasar.

Namun setiap pagi, ia mengingat kematian yang berbeda. Terbakar di menara. Tertikam di hutan. Tenggelam di sungai yang tidak ia kenal. Semuanya berakhir dengan suara yang sama: "Sampai jumpa di nyawa berikutnya."

Aksara menyembunyikan ingatan itu dari semua orang. Ia belajar bahwa orang tidak suka mendengar cerita tentang nyawa yang berulang. Mereka lebih suka menyebutnya mimpi buruk.

Suatu sore, seorang pasien datang dengan luka di telapak tangan. Luka itu berbentuk lingkaran dengan garis di tengah, sama seperti bekas luka di dada Aksara sejak lahir.

"Kau pernah menolongku," kata pasien itu. "Di kehidupan yang ketujuh."

Aksara menjatuhkan botol ramuan. Kaca pecah di lantai, dan untuk sesaat ia melihat api yang tidak ada di ruangan itu.

"Kalau kau tahu siapa aku," katanya pelan, "maka kau juga tahu apa yang terjadi di akhir."

Pasien itu tersenyum sedih. "Itu sebabnya aku datang. Nyawa kesembilan belum berakhir."',
'published', DATE_SUB(NOW(), INTERVAL 85 DAY), DATE_SUB(NOW(), INTERVAL 85 DAY), DATE_SUB(NOW(), INTERVAL 85 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n5, 2, 'Sisa Api Masa Lalu',
'Aksara dan pasien itu, yang bernama Danu, duduk di belakang rumah sampai malam. Danu menceritakan hidup yang tidak pernah diceritakan orang lain.

Di kehidupan pertama, Aksara adalah penjaga kota. Di kehidupan ketiga, ia seorang pembohong. Di kehidupan keenam, ia membakar rumahnya sendiri untuk menyelamatkan seseorang. Setiap kematian membawa satu ingatan, dan ingatan itu semakin berat.

"Kenapa aku harus mengulang?" tanya Aksara.

"Karena kau selalu mati sebelum menyelesaikan satu hal," kata Danu. "Kau selalu menyelamatkan orang lain, tetapi tidak pernah memaafkan dirimu sendiri."

Aksara ingin menolak. Namun ketika ia menutup mata, ia melihat semua wajah yang pernah ia tinggalkan. Tidak semuanya tersenyum. Beberapa memanggilnya pengecut.

Danu meletakkan sekantong benang merah di meja. "Ini bukan sihir. Ini hanya pengingat. Setiap kali kau ingin mengorbankan diri tanpa berpikir, ikat satu simpul dan tanya siapa yang akan kaubawa bersamamu."

Malam itu, untuk pertama kalinya, Aksara tidur tanpa mimpi terbakar. Ia bermimpi tentang rumah kecil, pasar, dan seseorang yang menunggu pulang.',
'published', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n5, 3, 'Nyawa Kesembilan',
'Kota terbakar di hari keempat puluh. Aksara tidak tahu siapa yang menyalakan api, tetapi ia mengenali pola asapnya. Sama seperti di kehidupan keenam.

Orang-orang berlarian menuju sungai. Aksara berdiri di tengah jalan, mendengar suara Danu di kejauhan. Di tangannya ada benang merah dengan delapan simpul.

"Kau tidak harus menjadi pahlawan lagi," kata Danu.

"Kalau bukan aku, siapa?"

"Kita."

Aksara mengikat simpul kesembilan. Ia tidak berlari ke menara. Ia tidak menahan atap yang runtuh. Ia membantu orang-orang lewat jalan kecil di belakang pasar, satu per satu, sambil memanggil nama mereka.

Ketika api akhirnya padam, rumah Aksara ikut terbakar. Ia duduk di tanah, lelah dan hidup.

"Mengapa kau masih di sini?" tanya Danu.

Aksara melihat telapak tangannya. Benang merah itu telah berubah menjadi benang biasa. "Karena nyawa kesembilan bukan tentang mati dengan benar. Ini tentang hidup tanpa melarikan diri."

Di kejauhan, matahari terbit. Tidak ada suara yang berjanji sampai jumpa. Untuk pertama kalinya, Aksara mendengar dirinya sendiri bernapas.',
'published', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY));

-- Bab 1-3: Gerbang Kabut Utara
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n6, 1, 'Kunci dari Kakek',
'Nara mewarisi rumah kakeknya, tiga kambing, dan sebuah kunci besi yang tidak cocok dengan pintu mana pun. Kakeknya berpesan satu hal sebelum meninggal: "Jangan pernah membuka gerbang saat kabut turun."

Desa Kabut Utara hidup dari ladang dan cerita. Setiap malam, kabut turun dari bukit dan menyelimuti jalan. Orang yang keluar saat kabut tebal kadang kembali dengan ingatan yang berbeda.

Nara tidak percaya cerita. Ia baru pindah dari kota, tempat kabut hanya berarti cuaca. Namun pada malam kelima, ia melihat cahaya di antara pohon-pohon dan mendengar suara kakeknya memanggil dari arah bukit.

Ia membawa kunci itu. Di ujung jalan, sebuah gerbang batu berdiri di tempat yang siang tadi hanya ada ilalang. Lubang kunci di gerbang itu berbentuk sama dengan kunci di tangannya.

Nara tidak membukanya. Ia pulang, menutup semua jendela, dan duduk di depan perapian sampai pagi.

Keesokan harinya, tetangganya bertanya apakah ia mendengar suara. Nara menggeleng. Namun di cermin, ia melihat bayangan kakeknya berdiri di belakangnya, mengangkat tangan seperti memberi peringatan.',
'published', DATE_SUB(NOW(), INTERVAL 24 DAY), DATE_SUB(NOW(), INTERVAL 24 DAY), DATE_SUB(NOW(), INTERVAL 24 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n6, 2, 'Suara di Balik Kabut',
'Suara itu tidak berhenti. Setiap malam, Nara mendengar namanya dipanggil dari arah bukit. Penduduk desa mulai menatapnya dengan curiga, seperti menunggu ia melakukan sesuatu.

Seorang perempuan tua bernama Ibu Sarinah datang membawa roti dan cerita. Ia dulu bekerja di rumah kakek Nara.

"Gerbang itu tidak dibuka dengan kunci," katanya. "Kunci hanya memberitahu gerbang siapa yang berani."

"Berani apa?"

"Menghadapi apa yang kau tinggalkan di baliknya."

Nara ingat sesuatu yang lama terkubur. Ketika kecil, ia pernah tersesat di bukit. Kakeknya menemukannya tiga hari kemudian, tetapi Nara selalu merasa ada bagian dirinya yang tidak ikut pulang.

Malam itu, kabut turun lebih tebal dari sebelumnya. Nara berdiri di depan gerbang, memegang kunci. Suara di baliknya bukan suara kakek. Itu suaranya sendiri, lebih kecil, memanggil minta tolong.

Ia memasukkan kunci. Gerbang berderit, dan kabut di depannya membuka jalan seperti napas panjang yang akhirnya dilepaskan.',
'published', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n6, 3, 'Penjaga Gerbang',
'Di balik gerbang ada desa yang sama, tetapi suram. Rumah-rumah kosong, ladang kering, dan di tengah jalan berdiri seorang anak kecil dengan mantel kebiruan.

Anak itu menatap Nara. "Kau terlambat," katanya.

"Aku tidak tahu harus kembali ke sini."

Anak itu tersenyum. "Kau tidak pernah pergi. Kau hanya berhenti."

Nara menyadari bahwa anak itu adalah dirinya yang tertinggal, bagian yang menyimpan rasa takut dan kesepian selama bertahun-tahun. Kakeknya telah menjaga gerbang agar bagian itu tidak hilang, tetapi juga tidak bisa keluar.

"Kalau aku membawamu pulang, apa yang terjadi pada gerbang?" tanya Nara.

"Kau menjadi penjaganya."

Nara menoleh. Kabut mulai menipis. Di kejauhan, Ibu Sarinah menunggu dengan lentera. Nara mengulurkan tangan kepada anak kecil itu.

"Mari pulang," katanya. "Kali ini kita berjalan bersama."

Ketika mereka melewati gerbang, batu-batu itu runtuh tanpa suara. Desa di balik kabut menghilang, tetapi Nara tidak merasa kehilangan. Di dadanya, sesuatu yang lama kosong akhirnya terisi.',
'published', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- Bab 1-3: Rumah Kopi di Bawah Hujan
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n7, 1, 'Meja di Dekat Jendela',
'Mira kembali ke kota kecil itu dengan satu koper dan resep neneknya yang ditulis di kertas lusuh. Rumah kopi keluarga sudah lama tutup, jendelanya pucat, dan papan namanya miring.

Ia berencana menjual bangunan itu. Setidaknya, itu rencana yang ia tulis di buku catatan tiga bulan lalu. Namun ketika kunci berputar dan pintu terbuka, aroma kayu manis seolah menyambutnya dari masa lalu.

Di dalam, semua perabot masih tertutup kain. Mira membuka jendela, dan hujan yang turun sejak pagi menyelinap masuk.

Bel pintu berbunyi. Seorang pria berdiri di ambang dengan payung patah dan kotak perkakas. "Aku dengar rumah ini akan dibuka lagi," katanya.

Bara. Teman masa kecil yang dulu selalu duduk di meja dekat jendela, menggambar kapal di serbet kertas. Kini ia tukang kayu, dengan luka kecil di tangannya dan senyum yang tidak banyak berubah.

"Aku belum memutuskan apakah akan membukanya," kata Mira.

Bara meletakkan kotak perkakas. "Kalau begitu, izinkan aku memperbaiki jendela yang rusak. Kau bisa memutuskan sambil membuat kopi."

Hujan di luar semakin deras. Untuk pertama kalinya setelah bertahun-tahun, rumah itu terdengar hidup.',
'published', DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 70 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n7, 2, 'Resep yang Tertinggal',
'Resep nenek Mira tidak lengkap. Di halaman belakang ada catatan: "Untuk hujan pertama, tambahkan satu hal yang tidak tertulis." Mira membaca kalimat itu sambil mengaduk kopi.

Bara datang setiap sore, membawa perkakas dan cerita. Ia memperbaiki meja, mengganti papan nama, dan membersihkan saluran air. Mira tidak pernah memintanya, tetapi ia juga tidak pernah mengusirnya.

"Kau masih suka kopi tanpa gula?" tanya Mira suatu hari.

"Masih. Tapi sekarang aku minum teh."

"Berubah selera?"

Bara tersenyum. "Dulu aku minum kopi karena kau membuatnya. Sekarang aku minum teh karena aku tidak mau menunggu sesuatu yang mungkin tidak kembali."

Mira berhenti mengaduk. Kalimat itu terlalu jujur untuk percakapan sore biasa.

Malamnya, ia mencoba resep neneknya. Kopi terasa pahit, lalu manis, lalu hangat di dada. Ia menambahkan sedikit kayu manis dan sejumput garam, seperti yang selalu dilakukan nenek tanpa pernah menjelaskan.

Keesokan pagi, ia menulis di papan: "Rumah Kopi Kenanga dibuka kembali. Hanya untuk hujan pertama."

Bara adalah pelanggan pertama. Ia memesan teh, tetapi Mira memberinya kopi tanpa gula.',
'published', DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n7, 3, 'Hujan yang Sama',
'Tiga minggu kemudian, rumah kopi itu mulai dikenal. Orang-orang datang untuk kopi, tetapi juga untuk bercerita. Mira mendengarkan tanpa banyak bicara, seperti neneknya dulu.

Suatu malam, saat hujan turun paling deras, Bara datang dengan pakaian basah. Ia membawa surat dari perusahaan kayu di kota besar. Tawaran pekerjaan yang bagus, cukup jauh untuk membuatnya pergi lagi.

"Kau akan menerimanya?" tanya Mira.

"Aku belum tahu. Aku ingin mendengar pendapatmu."

Mira menuangkan kopi. "Aku tidak berhak menahanmu. Dulu aku pergi karena tidak mau tinggal. Sekarang aku tahu, tinggal juga pilihan yang berani."

Bara menatap cangkirnya. "Kalau aku tinggal, apakah ini akan menjadi sesuatu?"

Mira melihat jendela. Meja di dekatnya sudah diperbaiki, tetapi masih ada bekas gambar kapal di permukaannya.

"Kapal yang kau gambar dulu tidak pernah selesai," katanya.

Bara mengangkat kepala. "Kita bisa menyelesaikannya."

Mira tersenyum. Di luar, hujan terus turun, seolah memberi mereka waktu yang cukup untuk memulai lagi.',
'published', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

-- Bab 1-3: Arsip Kota yang Hilang
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n8, 1, 'Katalog Tanpa Nama',
'Kiran bekerja sebagai penjaga arsip kota selama tujuh tahun. Ia mengenal setiap rak, setiap kotak, dan setiap bau kertas yang berubah sesuai musim. Ia menyukai pekerjaan itu karena dokumen tidak pernah berbohong, hanya kadang menghilang.

Suatu pagi, ia menemukan laci yang tidak pernah ia buka. Di dalamnya ada katalog berisi nama jalan yang tidak ada di peta kota. Jalan Kenari Tua, Gang Seribu Lampu, Jembatan Mulut Air.

Kiran memeriksa arsip penduduk. Nama jalan itu muncul di akta kelahiran beberapa warga, tetapi alamatnya selalu berakhir di tanah kosong.

Ia bertanya kepada kepala arsip. Kepala arsip menyuruhnya mengembalikan katalog dan berhenti mencari. "Dokumen lama hanya membuat kepalamu pusing," katanya.

Kiran tidak berhenti. Ia membawa katalog itu pulang dan membuka peta kota lama. Di sudut peta, ada area kosong berbentuk lingkaran. Di dalamnya tertulis satu kata: "ditarik."

Malam itu, Kiran bermimpi berjalan di jalan yang tidak ada. Lampu-lampu menyala di atasnya, dan seseorang memanggil namanya dari rumah tanpa pintu.',
'published', DATE_SUB(NOW(), INTERVAL 17 DAY), DATE_SUB(NOW(), INTERVAL 17 DAY), DATE_SUB(NOW(), INTERVAL 17 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n8, 2, 'Jalan yang Tidak Pernah Ada',
'Kiran menemukan nama jalan itu di koran lama. Pada tahun tertentu, seluruh jalan menghilang dari berita, hanya tersisa satu berita tentang kebakaran besar. Ia menelusuri arsip kebakaran dan menemukan daftar korban yang tidak pernah dikuburkan.

Di daftar itu ada nama kakeknya.

Kiran selalu diberitahu bahwa kakeknya meninggal di kota lain. Ia menelepon ayahnya. Ayahnya diam lama, lalu berkata, "Jangan buka luka yang sudah ditutup pemerintah."

Kalimat itu membuat Kiran semakin yakin ada sesuatu yang disembunyikan.

Ia kembali ke arsip malam hari. Menggunakan kunci cadangan, ia membuka ruang bawah tanah tempat dokumen pembangunan kota disimpan. Di sana ia menemukan peta lain: rencana memindahkan seluruh permukiman untuk proyek pelabuhan.

Rumah-rumah di Jalan Kenari Tua tidak dihancurkan. Mereka dilupakan.

Kiran duduk di lantai. Di antara dokumen, ada foto kakeknya berdiri di depan rumah dengan papan nama yang kini tidak ada.',
'published', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n8, 3, 'Pintu di Ujung Rak',
'Rak paling ujung di ruang bawah tanah selalu terasa dingin. Kiran mendekatinya dengan senter. Di balik rak, ada pintu kayu yang dicat warna yang sama dengan dinding.

Pintu itu tidak terkunci. Di belakangnya, tangga menurun ke lorong yang tidak tergambar di denah. Di ujung lorong, Kiran melihat cahaya lampu jalan.

Ia melangkah keluar, dan udara terasa berbeda. Jalan Kenari Tua membentang di depannya, rumah-rumah utuh, jendela menyala. Seorang perempuan tua menyapu di teras.

"Kau cucu Pak Arman, ya?" tanyanya. "Kami menunggu seseorang dari arsip."

Kiran mengikuti perempuan itu ke rumah. Di dalam, dinding penuh peta dan dokumen. Penduduk jalan itu menyimpan salinan arsip yang dihapus dari kota.

"Kami tidak hilang," kata perempuan itu. "Kami hanya dipindahkan sebelum mereka bisa menghapus kami."

Kiran mengeluarkan katalog dari tasnya. Ia belum tahu bagaimana mengembalikan jalan itu ke peta kota. Namun ia tahu satu hal: arsip yang hilang bukan milik pemerintah. Arsip itu milik orang-orang yang tinggal di dalamnya.',
'published', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Bab 1-3: Mahkota dari Debu
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n9, 1, 'Tangan yang Menempa Mahkota',
'Laras menempa logam sejak usia dua belas tahun. Tangannya kuat, lengan penuhnya bekas luka kecil, dan ia dapat mengenali suhu api dari warnanya. Orang-orang memanggilnya pandai besi terbaik di kota, meskipun mereka tidak pernah mengucapkan namanya dengan hormat.

Suatu hari, penjaga istana datang ke bengkelnya. Raja memesan mahkota baru untuk upacara penobatan putra mahkota. Logamnya bukan emas, melainkan besi hitam yang tidak pernah ia lihat sebelumnya.

"Apakah ini tidak terlalu berat untuk mahkota?" tanya Laras.

"Raja ingin mahkota yang tidak bisa dilupakan," jawab penjaga itu.

Laras bekerja selama tujuh malam. Setiap pukulan palu terasa aneh, seperti logam itu menjawab. Pada malam terakhir, ia melihat ukiran muncul sendiri di permukaan mahkota: sebuah mata dengan rantai di sekelilingnya.

Ia ingin melaporkan hal itu, tetapi penjaga istana sudah datang mengambil mahkota. Keesokan paginya, seluruh kota mendengar kabar: Raja ditemukan tewas di singgasananya, mengenakan mahkota yang baru selesai ditempa.',
'published', DATE_SUB(NOW(), INTERVAL 54 DAY), DATE_SUB(NOW(), INTERVAL 54 DAY), DATE_SUB(NOW(), INTERVAL 54 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n9, 2, 'Tuduhan di Aula Batu',
'Laras diseret ke istana sebelum matahari terbit. Aula batu penuh bangsawan, dan semua mata menuduhnya.

"Kau satu-satunya orang yang menyentuh mahkota itu," kata penasihat raja.

"Aku menempa, bukan membunuh," jawab Laras.

"Logam hitam itu berasal dari tambang terlarang. Kau pasti tahu kekuatannya."

Laras tidak tahu. Ia hanya tahu logam itu terasa hidup, dan bahwa uang pembayaran mahkota telah dikirim ke bengkelnya dengan nama yang tidak dikenal.

Di tengah tuduhan, putra mahkota berdiri. Pangeran Adran, yang akan dinobatkan hari itu, meminta mahkota diperiksa oleh pandai besi lain. Hasilnya: tidak ada racun, tidak ada jebakan. Mahkota itu hanya logam.

"Kalau begitu, bagaimana Ayah meninggal?" tanya Adran.

Tidak ada yang menjawab. Laras melihat penasihat raja menyembunyikan sesuatu di balik lengan jubahnya.

Malam itu, Laras dipenjara, bukan karena terbukti bersalah, tetapi karena raja baru belum ingin kehilangan satu-satunya orang yang bisa menjelaskan logam hitam.',
'published', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n9, 3, 'Rahasia Logam Hitam',
'Pangeran Adran datang ke sel ketika istana sudah sunyi. Ia membawa mahkota dan sebuah palu kecil.

"Kau satu-satunya yang bisa membuka ini," katanya.

Laras memeriksa mahkota. Di bagian dalam, ada lapisan tipis yang tidak ia buat. Saat diketuk, lapisan itu terbuka dan memperlihatkan ruang kecil berisi serbuk putih.

"Bukan sihir," kata Laras. "Racun. Dimasukkan setelah mahkota selesai."

"Siapa yang bisa melakukannya?"

"Orang yang memberiku logam ini."

Adran menatapnya. "Kalau kau membantuku, kau akan diampuni. Kalau kau menolak, aku tidak bisa melindungimu."

Laras mengambil palu. "Aku tidak butuh perlindungan. Aku butuh nama orang yang membuat tanganku dianggap pembunuh."

Mereka menemukan nama itu di catatan pengiriman: penasihat raja. Bukti itu cukup untuk membuka persidangan baru. Ketika Laras keluar dari istana, langit berwarna seperti besi yang baru dipanaskan. Ia tidak lagi menjadi pandai besi tanpa nama. Ia menjadi perempuan yang menempa kebenaran dari debu.',
'published', DATE_SUB(NOW(), INTERVAL 8 HOUR), DATE_SUB(NOW(), INTERVAL 8 HOUR), DATE_SUB(NOW(), INTERVAL 8 HOUR));

-- Bab 1-3: Penjaga Api Terakhir
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n10, 1, 'Api yang Tak Mau Padam',
'Kota Arundel dibangun di atas gunung berapi yang sudah tidur selama tiga ratus tahun. Di puncaknya, sebuah api abadi dijaga oleh satu keluarga. Kini hanya tinggal Kalis, perempuan dua puluh tahun yang belum pernah turun ke kota.

Api itu tidak besar. Warnanya biru pucat, dan tidak menghasilkan asap. Setiap malam, Kalis memberi makan api dengan kayu khusus dan membaca doa yang diajarkan ibunya.

Suatu malam, api menolak kayu yang diberikan. Nyala biru berubah merah, lalu memanjang seperti tangan.

Kalis mundur. Di dinding gua, bayangan api membentuk wajah seseorang. "Kau penjaga terakhir," kata suara itu. "Ketika kau mati, gunung akan bangun."

Kalis tidak menjawab. Ia mengambil air dan memadamkan api. Untuk pertama kalinya setelah tiga ratus tahun, puncak Arundel gelap.

Di bawah, kota mulai bergetar.

Keesokan pagi, seorang utusan datang dari kota. Ia membawa surat dari dewan: api harus dinyalakan kembali sebelum matahari terbenam, atau seluruh warga akan mengungsi. Kalis menatap abu di tangannya. Ia tidak tahu apakah api itu melindungi kota atau justru menahan sesuatu di bawahnya.',
'published', DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n10, 2, 'Utusan dari Kegelapan',
'Utusan itu bernama Ren, seorang pemuda dengan jubah kota dan tatapan yang terlalu waspada. Ia menginap di rumah penjaga, meskipun Kalis tidak menawarkan.

"Kau tidak boleh memadamkan api," kata Ren.

"Kau tidak tahu apa yang kudengar dari api."

"Aku tahu apa yang terjadi jika gunung bangun."

Malam itu, mereka duduk di sisi api yang sudah dinyalakan kembali. Kalis menjelaskan wajah di dinding, suara yang mengancam. Ren mendengarkan tanpa menyela.

"Di kota, ada cerita lain," katanya. "Api itu bukan penjaga. Api itu segel."

Kalis menoleh. Ibunya tidak pernah menyebut kata segel.

"Kalau begitu, apa yang ada di bawah?"

Ren membuka peta tua. Di bawah gunung, ada jaringan gua dan kota kuno yang tertimbun. Dewan kota tahu. Mereka mengirim utusan bukan untuk menyelamatkan warga, tetapi untuk memastikan segel tidak terbuka.

Kalis melihat api biru di depannya. Selama ini ia menganggap api itu miliknya. Ternyata ia yang milik api.',
'published', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n10, 3, 'Perjalanan ke Gunung Abu',
'Kalis dan Ren turun ke kota untuk pertama kalinya. Jalan-jalan ramai, tetapi orang-orang menghindari mereka. Dewan kota menyambut dengan jamuan, sambil mengawasi setiap gerakan.

Di perpustakaan kota, Kalis menemukan catatan penjaga pertama. Api dinyalakan bukan untuk menahan gunung, melainkan untuk menjaga pintu ke kota kuno. Penduduk kota kuno meminta bantuan, dan penjaga pertama berjanji tidak akan membiarkan mereka terlupakan.

"Jadi api itu kenangan," kata Kalis.

Ren membaca halaman berikutnya. "Dan kenangan bisa padam kalau tidak ada yang percaya."

Malamnya, gempa mengguncang kota. Api di puncak membesar, warnanya berubah putih. Kalis berlari menaiki gunung, diikuti Ren. Di gua, dinding-dindingnya terbuka memperlihatkan tangga menuju bawah.

Kalis berhenti di ambang. Dari kegelapan, suara-suara memanggil. Ia menoleh kepada Ren.

"Aku tidak akan memadamkan api," katanya. "Aku akan membawanya turun."

Ren mengangguk. Mereka menyalakan obor dari api abadi dan melangkah ke kota yang selama tiga ratus tahun menunggu untuk diingat.',
'published', DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR));

-- Bab 1-3: Kembalinya Sang Penenun Waktu
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n11, 1, 'Jam yang Berjalan Mundur',
'Daru memperbaiki jam sejak kecil. Ia dapat mendengar detak yang salah sebelum pelanggannya menyadari jam mereka berhenti. Bengkelnya kecil, penuh gear dan bandul, dan ia menyukai kesunyian yang hanya diisi bunyi waktu.

Suatu pagi, ia terbangun dengan tangan penuh luka benang. Di lantai bengkel ada alat tenun kayu yang tidak pernah ia miliki. Ketika ia menyentuhnya, ia melihat dirinya di masa lalu, berdiri di menara tinggi dan menenun cahaya.

Daru menarik tangan. Alat tenun itu menghilang.

Ia menganggapnya mimpi. Namun sepanjang hari, waktu terasa aneh. Jam pelanggan berjalan mundur, lalu maju lagi. Bayangan di dinding bergerak lebih cepat dari dirinya.

Menjelang tutup, seorang perempuan datang membawa jam pasir retak. "Bisa diperbaiki?" tanyanya.

Daru memeriksa. Pasir di dalamnya tidak jatuh ke bawah. Pasir itu bergerak ke atas.

"Aku tidak bisa memperbaiki ini," katanya.

Perempuan itu menatapnya lama. "Kau dulu bisa. Sebelum kau memilih dilahirkan sebagai manusia biasa."

Daru merasakan sesuatu di dadanya retak, seperti gear yang akhirnya lepas setelah bertahun-tahun menahan.',
'published', DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n11, 2, 'Benang yang Terputus',
'Perempuan itu bernama Asri. Ia duduk di bengkel sampai malam, menjelaskan hal-hal yang tidak masuk akal. Daru dulu adalah penenun waktu, penjaga benang yang menghubungkan masa lalu dan masa depan. Ia meninggalkan tugasnya karena lelah melihat manusia mengulang kesalahan yang sama.

"Kau memilih dilupakan," kata Asri. "Tapi benang yang kau tinggalkan belum selesai."

Daru ingin menolak. Namun setiap kali ia menutup mata, ia melihat alat tenun dan benang yang putus. Ia juga melihat wajah-wajah yang pernah ia tolong, dan wajah-wajah yang ia biarkan terluka.

"Kenapa aku harus kembali?" tanya Daru.

"Karena waktu tidak bisa diperbaiki sendiri."

Malam itu, Daru membuka laci rahasia di meja kerjanya. Di dalamnya ada benang emas yang ia temukan saat kecil, benang yang tidak pernah ia berani buang. Ketika disentuh, bengkel menghilang.

Ia berdiri di menara tanpa dinding, dikelilingi aliran waktu. Asri menunggu di ujung benang. "Kita hanya punya satu malam," katanya.',
'published', DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n11, 3, 'Pelanggan dari Masa Lalu',
'Daru menenun dengan tangan yang tidak ia kenal, tetapi terasa seperti miliknya. Setiap simpul menarik satu kenangan, setiap tarikan memperbaiki satu kejadian kecil. Seorang anak tidak jatuh dari tangga. Sebuah surat sampai tepat waktu. Seorang kakek sempat mengucapkan selamat tinggal.

Namun setiap perbaikan meminta harga. Waktu di sekitarnya menipis, dan Daru mulai melupakan bengkelnya sendiri.

"Berhenti," kata Asri. "Kau tidak harus menyelamatkan semuanya."

Daru melihat benang terakhir. Benang itu terhubung kepada jam pasir retak milik Asri. Jika ia memperbaikinya, ia harus kembali ke tugas lama dan melupakan hidup yang ia pilih.

"Jam pasir ini untuk siapa?" tanya Daru.

"Untukku. Aku menunggu kau sejak lama."

Daru menatap benang, lalu menatap wajah Asri. Ia memotong benang itu dengan tangannya sendiri. Jam pasir pecah, dan pasirnya berhenti bergerak.

Ketika Daru membuka mata, ia kembali di bengkel. Jam di dinding berdetak normal. Asri tidak ada, tetapi di meja ada benang emas yang sudah terurai.

Ia menyimpannya di laci. Ia tidak tahu apakah itu pilihan yang benar. Namun untuk pertama kalinya, ia tidak mendengar waktu berteriak.',
'published', DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR));

-- Bab 1-3: Ekspedisi Langit Selatan
INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n12, 1, 'Balon yang Bocor',
'Kapten Sena tidak pernah kehilangan balon. Ia telah menerbangkan kapal udara Melati selama sepuluh tahun, melewati badai, gunung, dan perang kecil di perbatasan. Ketika Melati bocor di atas laut selatan, ia menganggapnya kesalahan teknis biasa.

Balon itu mulai turun perlahan, cukup lambat untuk membuat penumpang panik tanpa benar-benar jatuh. Sena memerintahkan kru mencari pulau terdekat. Di peta, tidak ada pulau.

Namun seorang penumpang bernama Jalu menunjuk awan di barat. "Ada tanah di sana. Aku bisa melihatnya."

Sena melihat hanya awan. Jalu adalah ahli cuaca muda yang membayar perjalanan dengan uang tabungan. Ia memiliki mata yang aneh, seperti dapat melihat sesuatu yang tidak terlihat orang lain.

Melati terus turun. Ketika ketinggian tinggal seratus meter, awan terbuka dan memperlihatkan pulau melayang di atas laut, ditumbuhi pohon-pohon perak dan dikelilingi air terjun yang jatuh ke awan.

"Pulau itu yang kau lihat?" tanya Sena.

Jalu mengangguk. "Pulau yang tidak ada di peta mana pun."

Sena memegang kemudi. "Bagus. Mungkin kali ini peta yang salah."',
'published', DATE_SUB(NOW(), INTERVAL 95 DAY), DATE_SUB(NOW(), INTERVAL 95 DAY), DATE_SUB(NOW(), INTERVAL 95 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n12, 2, 'Pulau di Atas Awan',
'Melati mendarat di tepi pulau. Rumputnya lembut, udaranya tipis, dan air terjun di sekeliling mengeluarkan suara seperti hujan tanpa henti. Kru turun dengan tali dan perlengkapan.

Di tengah pulau, ada menara batu dengan roda besar yang berputar lambat. Jalu menghampirinya dan menemukan lempengan tembaga bertuliskan angka-angka cuaca.

"Pulau ini stasiun pemantau," katanya. "Dibuat untuk mengatur angin di selatan."

Sena memeriksa roda. Beberapa gigi patah, dan balon Melati mungkin bocor karena angin yang tidak stabil. Jika stasiun ini rusak sejak lama, seluruh rute penerbangan selatan akan berbahaya.

Mereka memperbaiki roda sampai malam. Ketika roda berputar normal, awan di sekitar pulau mengendur. Melati bergoyang, siap terbang.

Namun Jalu menemukan ruang di bawah menara. Di dalamnya ada kursi, buku catatan, dan seorang pria tua yang sudah lama menunggu.

"Kalau kalian bisa sampai ke sini," kata pria itu, "berarti peta dunia sudah berubah."',
'published', DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 70 DAY));

INSERT INTO chapters (novel_id, chapter_number, title, content, status, published_at, created_at, updated_at)
VALUES (@n12, 3, 'Jangkar Terakhir',
'Pria tua itu bernama Pak Ilham, penjaga stasiun yang tertinggal ketika pulau melayang menjauh dari jalur. Ia telah hidup sendirian selama dua puluh tahun, mengukur angin dan menulis buku tentang pulau.

"Saya tidak bisa turun," katanya. "Jangkar saya patah."

Sena memeriksa jangkar. Tali-tali pulau terikat pada batu besar yang menggantung di bawah, tetapi salah satunya hampir putus. Melati tidak cukup kuat membawa pria itu dan seluruh kru sekaligus.

"Kita bisa kembali dengan balon bantuan," kata Jalu.

"Melati tidak akan bertahan dua perjalanan tanpa perbaikan besar," jawab Sena.

Mereka membuat keputusan. Kru tinggal di pulau, sementara Sena dan Jalu terbang ke pelabuhan terdekat. Selama perjalanan, Jalu menulis peta baru berdasarkan pengamatan, menandai pulau melayang dan rute angin yang aman.

Ketika mereka kembali seminggu kemudian, Melati sudah diperbaiki dan pulau itu masih di tempatnya. Pak Ilham berdiri di tepi, melambaikan tangan.

"Jangkar terakhir sudah kuperbaiki," katanya. "Sekarang pulau ini punya nama."

Sena tersenyum. Di peta baru Jalu, pulau itu ditandai dengan satu kata: "Rumah."',
'published', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY));

-- Bab draf: Catatan Musim Kemarau (tidak tampil untuk pembaca)
INSERT INTO chapters (novel_id, chapter_number, title, content, status, created_at, updated_at)
VALUES (@n13, 1, 'Debu di Halaman Sekolah',
'Musim kemarau membuat halaman sekolah berdebu. Ario memarkir sepedanya di bawah pohon yang daunnya sudah rontok sejak bulan lalu, lalu membuka ruang musik yang selama lima tahun hanya dipakai untuk menyimpan kursi rusak.

Ia kembali ke kota ini bukan karena rindu, melainkan karena kontrak kerjanya berakhir. Namun melihat piano tua di sudut ruangan, ia merasa ada bagian dari dirinya yang ikut berdebu.

Nanti ia akan membersihkan piano itu. Nanti ia akan menelepon seseorang. Nanti. Untuk sekarang, ia membuka jendela dan membiarkan angin masuk.',
'draft', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Rating pembaca (dihitung otomatis di aplikasi)
INSERT INTO ratings (novel_id, user_id, rating) VALUES
(@n1, @dinda, 5), (@n1, @fajar, 4), (@n1, @nadia, 5),
(@n2, @dinda, 4), (@n2, @fajar, 5), (@n2, @nadia, 4),
(@n3, @dinda, 5), (@n3, @fajar, 4), (@n3, @nadia, 5),
(@n4, @dinda, 4), (@n4, @fajar, 4), (@n4, @nadia, 5),
(@n5, @dinda, 5), (@n5, @fajar, 5), (@n5, @nadia, 4),
(@n6, @dinda, 4), (@n6, @fajar, 5), (@n6, @nadia, 4),
(@n7, @dinda, 5), (@n7, @fajar, 4), (@n7, @nadia, 5),
(@n8, @dinda, 4), (@n8, @fajar, 4), (@n8, @nadia, 4),
(@n9, @dinda, 5), (@n9, @fajar, 4), (@n9, @nadia, 5),
(@n10, @dinda, 4), (@n10, @fajar, 4), (@n10, @nadia, 5),
(@n11, @dinda, 5), (@n11, @fajar, 4), (@n11, @nadia, 4),
(@n12, @dinda, 4), (@n12, @fajar, 5), (@n12, @nadia, 4);

-- Komentar pembaca
INSERT INTO comments (novel_id, user_id, content, created_at) VALUES
(@n1, @dinda, 'Hangat sekali. Rasanya seperti duduk di toko buku saat hujan turun.', DATE_SUB(NOW(), INTERVAL 40 DAY)),
(@n1, @fajar, 'Langit dan Aluna bikin geregetan, tapi tulisannya enak diikuti.', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(@n1, @nadia, 'Bab empat bikin senyum sendiri. Lanjutkan, penulis!', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@n2, @fajar, 'Misterinya rapi dan ending-nya memuaskan. Cocok dibaca sekali duduk.', DATE_SUB(NOW(), INTERVAL 80 DAY)),
(@n3, @dinda, 'Suka karakter Aruna. Perempuan yang berpikir sebelum bertindak.', DATE_SUB(NOW(), INTERVAL 18 DAY)),
(@n3, @nadia, 'Promosi: kunjungi situs anonim untuk cerita lain yang katanya lebih seru.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(@n5, @nadia, 'Konsep reinkarnasi dipakai dengan segar. Bab tiga menyentuh.', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(@n6, @dinda, 'Kabutnya terasa nyata. Aku membaca sambil menyalakan lampu.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(@n7, @dinda, 'Cocok dibaca sambil hujan. Resep kopinya bikin lapar.', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(@n8, @fajar, 'Penasaran lanjutannya. Arsip kota memang selalu punya rahasia.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(@n9, @nadia, 'Laras keren. Adegan di aula batu tegang sekali.', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Satu laporan contoh untuk diuji di dashboard admin
INSERT INTO reports (reporter_id, comment_id, reason, details, created_at)
SELECT @dinda, c.id, 'spam', 'Komentar berisi promosi situs lain dan tidak relevan dengan cerita.', DATE_SUB(NOW(), INTERVAL 5 DAY)
FROM comments c
WHERE c.novel_id = @n3 AND c.user_id = @nadia
ORDER BY c.id DESC
LIMIT 1;

-- Perpustakaan dan riwayat pembaca demo
INSERT INTO bookmarks (user_id, novel_id, created_at) VALUES
(@dinda, @n1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(@dinda, @n5, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(@dinda, @n7, DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO reading_progress (user_id, novel_id, chapter_id, progress_percent, updated_at)
SELECT @dinda, @n1, c.id, 42.50, DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM chapters c WHERE c.novel_id = @n1 AND c.chapter_number = 2;

INSERT INTO reading_progress (user_id, novel_id, chapter_id, progress_percent, updated_at)
SELECT @dinda, @n5, c.id, 88.00, DATE_SUB(NOW(), INTERVAL 6 DAY)
FROM chapters c WHERE c.novel_id = @n5 AND c.chapter_number = 3;

INSERT INTO reading_progress (user_id, novel_id, chapter_id, progress_percent, updated_at)
SELECT @fajar, @n8, c.id, 15.00, DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM chapters c WHERE c.novel_id = @n8 AND c.chapter_number = 1;

INSERT INTO chapter_reads (user_id, chapter_id)
SELECT @dinda, c.id FROM chapters c WHERE c.novel_id = @n1 AND c.chapter_number IN (1, 2);

INSERT INTO chapter_reads (user_id, chapter_id)
SELECT @dinda, c.id FROM chapters c WHERE c.novel_id = @n5 AND c.chapter_number IN (1, 2, 3);

INSERT INTO chapter_reads (user_id, chapter_id)
SELECT @fajar, c.id FROM chapters c WHERE c.novel_id = @n8 AND c.chapter_number = 1;
