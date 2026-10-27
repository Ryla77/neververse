<?php

declare(strict_types=1);

/**
 * Membuat sampul SVG orisinal untuk data contoh.
 * Jalankan: php bin/generate-covers.php
 */

$outputDir = dirname(__DIR__) . '/public/assets/images/covers';
if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "Folder sampul tidak dapat dibuat.\n");
    exit(1);
}

$novels = [
    ['file' => 'lentera', 'title' => ['Lentera', 'di Ujung', 'Musim'], 'author' => 'Rania Prameswari', 'genre' => 'ROMANSA', 'from' => '#1b1440', 'to' => '#3b1d5e', 'accent' => '#f6c453', 'motif' => 'lantern'],
    ['file' => 'pulau-kelabu', 'title' => ['Bayang-Bayang', 'Pulau', 'Kelabu'], 'author' => 'Aditya Nurwenda', 'genre' => 'MISTERI', 'from' => '#08131f', 'to' => '#16304a', 'accent' => '#7fd1e0', 'motif' => 'beacon'],
    ['file' => 'putri-takdir', 'title' => ['Putri yang', 'Menolak', 'Takdir'], 'author' => 'Sekar Ayu Lestari', 'genre' => 'KERAJAAN', 'from' => '#2a1027', 'to' => '#4a1b3d', 'accent' => '#e8b563', 'motif' => 'crown'],
    ['file' => 'arunika', 'title' => ['Arunika', 'Jejak Sang', 'Pengembara'], 'author' => 'Bimo Prasetyo', 'genre' => 'PETUALANGAN', 'from' => '#08202b', 'to' => '#0f4347', 'accent' => '#f2b544', 'motif' => 'compass'],
    ['file' => 'sembilan-nyawa', 'title' => ['Sembilan', 'Nyawa', 'Terakhir'], 'author' => 'Rania Prameswari', 'genre' => 'REINKARNASI', 'from' => '#1a0f1f', 'to' => '#38123a', 'accent' => '#d97b6c', 'motif' => 'rings'],
    ['file' => 'gerbang-kabut', 'title' => ['Gerbang', 'Kabut', 'Utara'], 'author' => 'Aditya Nurwenda', 'genre' => 'FANTASI', 'from' => '#101b2c', 'to' => '#25324f', 'accent' => '#9fb8ff', 'motif' => 'gate'],
    ['file' => 'rumah-kopi', 'title' => ['Rumah Kopi', 'di Bawah', 'Hujan'], 'author' => 'Sekar Ayu Lestari', 'genre' => 'ROMANSA', 'from' => '#241612', 'to' => '#4a2a1e', 'accent' => '#e3b07a', 'motif' => 'rain'],
    ['file' => 'arsip-kota', 'title' => ['Arsip Kota', 'yang', 'Hilang'], 'author' => 'Bimo Prasetyo', 'genre' => 'MISTERI', 'from' => '#151a22', 'to' => '#2c3644', 'accent' => '#b9c6a8', 'motif' => 'archive'],
    ['file' => 'mahkota-debu', 'title' => ['Mahkota', 'dari', 'Debu'], 'author' => 'Rania Prameswari', 'genre' => 'KERAJAAN', 'from' => '#1c1420', 'to' => '#3d2430', 'accent' => '#d9a441', 'motif' => 'forge'],
    ['file' => 'penjaga-api', 'title' => ['Penjaga', 'Api', 'Terakhir'], 'author' => 'Sekar Ayu Lestari', 'genre' => 'FANTASI', 'from' => '#1b0d12', 'to' => '#451322', 'accent' => '#ff9d5c', 'motif' => 'flame'],
    ['file' => 'penenun-waktu', 'title' => ['Kembalinya', 'Sang Penenun', 'Waktu'], 'author' => 'Aditya Nurwenda', 'genre' => 'REINKARNASI', 'from' => '#131126', 'to' => '#2c2350', 'accent' => '#c9a6ff', 'motif' => 'clock'],
    ['file' => 'langit-selatan', 'title' => ['Ekspedisi', 'Langit', 'Selatan'], 'author' => 'Bimo Prasetyo', 'genre' => 'PETUALANGAN', 'from' => '#0a1b2a', 'to' => '#1d3d5c', 'accent' => '#f0c674', 'motif' => 'cloud'],
    ['file' => 'catatan-kemarau', 'title' => ['Catatan', 'Musim', 'Kemarau'], 'author' => 'Rania Prameswari', 'genre' => 'DRAF', 'from' => '#2a1c12', 'to' => '#5a3a1e', 'accent' => '#f2c079', 'motif' => 'sun'],
];

function svg_text(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function motif_svg(string $motif, string $accent): string
{
    $a = svg_text($accent);
    return match ($motif) {
        'lantern' => <<<SVG
            <circle cx="300" cy="300" r="150" fill="none" stroke="{$a}" stroke-opacity=".22" stroke-width="1.5"/>
            <circle cx="300" cy="300" r="112" fill="none" stroke="{$a}" stroke-opacity=".35" stroke-width="1"/>
            <path d="M300 178v34" stroke="{$a}" stroke-width="2" stroke-linecap="round" stroke-opacity=".8"/>
            <rect x="262" y="212" width="76" height="104" rx="14" fill="{$a}" fill-opacity=".14" stroke="{$a}" stroke-width="2"/>
            <path d="M276 316h48M278 226h44" stroke="{$a}" stroke-width="2" stroke-linecap="round" stroke-opacity=".7"/>
            <circle cx="300" cy="264" r="18" fill="{$a}" fill-opacity=".55"/>
            <circle cx="300" cy="264" r="34" fill="{$a}" fill-opacity=".12"/>
            <path d="M300 336v22" stroke="{$a}" stroke-width="2" stroke-linecap="round" stroke-opacity=".5"/>
            SVG,
        'beacon' => <<<SVG
            <circle cx="300" cy="300" r="150" fill="none" stroke="{$a}" stroke-opacity=".18"/>
            <path d="M284 364h32l-6-112h-20Z" fill="{$a}" fill-opacity=".16" stroke="{$a}" stroke-width="2"/>
            <path d="M272 252h56" stroke="{$a}" stroke-width="2" stroke-linecap="round"/>
            <path d="M294 224h12v28h-12Z" fill="{$a}" fill-opacity=".5"/>
            <path d="M300 238 150 190M300 238l150 48" stroke="{$a}" stroke-opacity=".28" stroke-width="2"/>
            <path d="M180 380c40-14 80-14 120 0s80 14 120 0" fill="none" stroke="{$a}" stroke-opacity=".4" stroke-width="1.5"/>
            <path d="M170 402c43-14 86-14 130 0s87 14 130 0" fill="none" stroke="{$a}" stroke-opacity=".22" stroke-width="1.5"/>
            SVG,
        'crown' => <<<SVG
            <circle cx="300" cy="300" r="148" fill="none" stroke="{$a}" stroke-opacity=".2"/>
            <path d="M214 348l-14-106 66 48 34-62 34 62 66-48-14 106Z" fill="{$a}" fill-opacity=".12" stroke="{$a}" stroke-width="2.5" stroke-linejoin="round"/>
            <path d="M214 348h172" stroke="{$a}" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="300" cy="326" r="9" fill="{$a}"/>
            <circle cx="247" cy="318" r="5" fill="{$a}" fill-opacity=".8"/>
            <circle cx="353" cy="318" r="5" fill="{$a}" fill-opacity=".8"/>
            <path d="M300 176v22" stroke="{$a}" stroke-opacity=".5" stroke-width="2" stroke-linecap="round"/>
            SVG,
        'compass' => <<<SVG
            <circle cx="300" cy="300" r="148" fill="none" stroke="{$a}" stroke-opacity=".22"/>
            <circle cx="300" cy="300" r="112" fill="none" stroke="{$a}" stroke-opacity=".35"/>
            <circle cx="300" cy="300" r="76" fill="none" stroke="{$a}" stroke-opacity=".2" stroke-dasharray="4 8"/>
            <path d="M330 220l-18 62-52 18 18-62Z" fill="{$a}" fill-opacity=".22" stroke="{$a}" stroke-width="2" stroke-linejoin="round"/>
            <circle cx="300" cy="300" r="7" fill="{$a}"/>
            <path d="M300 152v-20M300 448v20M152 300h-20M448 300h20" stroke="{$a}" stroke-opacity=".5" stroke-width="2" stroke-linecap="round"/>
            <path d="M396 204l-14 14M204 396l-14 14M396 396l-14-14M204 204l-14-14" stroke="{$a}" stroke-opacity=".35" stroke-width="2"/>
            SVG,
        'rings' => <<<SVG
            <circle cx="300" cy="300" r="150" fill="none" stroke="{$a}" stroke-opacity=".16"/>
            <circle cx="300" cy="300" r="120" fill="none" stroke="{$a}" stroke-opacity=".24"/>
            <circle cx="300" cy="300" r="90" fill="none" stroke="{$a}" stroke-opacity=".32"/>
            <circle cx="300" cy="300" r="60" fill="none" stroke="{$a}" stroke-opacity=".42"/>
            <circle cx="300" cy="300" r="30" fill="{$a}" fill-opacity=".18" stroke="{$a}" stroke-width="1.5"/>
            <path d="M300 150v42M300 408v42M150 300h42M408 300h42" stroke="{$a}" stroke-opacity=".5" stroke-width="2" stroke-linecap="round"/>
            <circle cx="300" cy="300" r="6" fill="{$a}"/>
            SVG,
        'gate' => <<<SVG
            <path d="M204 392V288a96 96 0 0 1 192 0v104" fill="{$a}" fill-opacity=".08" stroke="{$a}" stroke-width="2.5"/>
            <path d="M228 392V292a72 72 0 0 1 144 0v100" fill="none" stroke="{$a}" stroke-opacity=".45" stroke-width="1.5"/>
            <path d="M204 392h192" stroke="{$a}" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="300" cy="278" r="9" fill="{$a}" fill-opacity=".75"/>
            <path d="M300 288v30" stroke="{$a}" stroke-width="2" stroke-linecap="round"/>
            <path d="M170 340h-22M452 340h-22" stroke="{$a}" stroke-opacity=".3" stroke-width="2" stroke-linecap="round"/>
            SVG,
        'rain' => <<<SVG
            <circle cx="300" cy="300" r="148" fill="none" stroke="{$a}" stroke-opacity=".18"/>
            <path d="M232 286h136a34 34 0 0 0 0-68 52 52 0 0 0-98-14 40 40 0 0 0-38 82Z" fill="{$a}" fill-opacity=".1" stroke="{$a}" stroke-width="2"/>
            <path d="M248 316l-14 34M282 316l-14 34M316 316l-14 34M350 316l-14 34" stroke="{$a}" stroke-opacity=".55" stroke-width="2" stroke-linecap="round"/>
            <path d="M246 382h108l-10 30h-88Z" fill="{$a}" fill-opacity=".16" stroke="{$a}" stroke-width="1.5"/>
            <path d="M282 362c0-8 14-8 14 0M300 362c0-8 14-8 14 0" fill="none" stroke="{$a}" stroke-opacity=".5" stroke-width="1.5"/>
            SVG,
        'archive' => <<<SVG
            <rect x="196" y="188" width="208" height="224" rx="8" fill="{$a}" fill-opacity=".05" stroke="{$a}" stroke-width="2"/>
            <path d="M196 262h208M196 338h208M300 188v224" stroke="{$a}" stroke-opacity=".45" stroke-width="1.5"/>
            <path d="M216 232h62M216 308h62M216 384h62M324 232h62M324 308h62M324 384h62" stroke="{$a}" stroke-opacity=".55" stroke-width="3" stroke-linecap="round"/>
            <circle cx="300" cy="300" r="150" fill="none" stroke="{$a}" stroke-opacity=".12"/>
            <path d="M392 176l14-14" stroke="{$a}" stroke-opacity=".5" stroke-width="2"/>
            SVG,
        'forge' => <<<SVG
            <circle cx="300" cy="300" r="148" fill="none" stroke="{$a}" stroke-opacity=".18"/>
            <path d="M252 352h96l16 30H236Z" fill="{$a}" fill-opacity=".18" stroke="{$a}" stroke-width="2" stroke-linejoin="round"/>
            <path d="M270 352V312h60v40" fill="{$a}" fill-opacity=".1" stroke="{$a}" stroke-width="2"/>
            <path d="M286 240h28l-6 72h-16Z" fill="{$a}" fill-opacity=".3" stroke="{$a}" stroke-width="2" stroke-linejoin="round"/>
            <path d="M300 210v22" stroke="{$a}" stroke-opacity=".6" stroke-width="2" stroke-linecap="round"/>
            <circle cx="356" cy="238" r="3" fill="{$a}"/><circle cx="238" cy="258" r="3" fill="{$a}"/>
            <circle cx="370" cy="276" r="2" fill="{$a}" fill-opacity=".8"/><circle cx="230" cy="214" r="2" fill="{$a}" fill-opacity=".8"/>
            SVG,
        'flame' => <<<SVG
            <circle cx="300" cy="300" r="148" fill="none" stroke="{$a}" stroke-opacity=".2"/>
            <path d="M300 186c28 52 74 70 74 122a74 74 0 0 1-148 0c0-34 20-54 36-78 10 18 18 26 26 30-4-28-2-50 12-74Z" fill="{$a}" fill-opacity=".14" stroke="{$a}" stroke-width="2.5" stroke-linejoin="round"/>
            <path d="M300 272c14 26 32 36 32 60a32 32 0 0 1-64 0c0-18 12-28 20-40 4 8 8 12 12 14-2-14 0-24 0-34Z" fill="{$a}" fill-opacity=".45"/>
            <path d="M300 136v18M196 220l14 12M404 220l-14 12" stroke="{$a}" stroke-opacity=".5" stroke-width="2" stroke-linecap="round"/>
            SVG,
        'clock' => <<<SVG
            <circle cx="300" cy="300" r="148" fill="none" stroke="{$a}" stroke-opacity=".18"/>
            <circle cx="300" cy="300" r="104" fill="none" stroke="{$a}" stroke-width="2.5" stroke-opacity=".75"/>
            <circle cx="300" cy="300" r="86" fill="none" stroke="{$a}" stroke-opacity=".22" stroke-dasharray="2 10"/>
            <path d="M300 232v70l48 30" fill="none" stroke="{$a}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="300" cy="300" r="6" fill="{$a}"/>
            <path d="M300 196v-16M404 300h16M300 404v16M196 300h-16" stroke="{$a}" stroke-opacity=".5" stroke-width="2" stroke-linecap="round"/>
            <path d="M160 360c50-34 90-20 120 6s70 34 120-20" fill="none" stroke="{$a}" stroke-opacity=".25" stroke-width="1.5"/>
            SVG,
        'cloud' => <<<SVG
            <circle cx="300" cy="300" r="148" fill="none" stroke="{$a}" stroke-opacity=".16"/>
            <path d="M190 336h150a40 40 0 0 0 0-80 60 60 0 0 0-112-16 46 46 0 0 0-38 96Z" fill="{$a}" fill-opacity=".08" stroke="{$a}" stroke-width="1.8"/>
            <path d="M312 318c40-4 66 10 82 34M360 300c30-2 52 8 66 26" stroke="{$a}" stroke-opacity=".4" stroke-width="2" stroke-linecap="round"/>
            <path d="M286 238l-18 34h24l-12 30 46-44h-26l14-20Z" fill="{$a}" fill-opacity=".65"/>
            <path d="M300 420c-42-8-78-28-104-58M300 420c42-8 78-28 104-58" stroke="{$a}" stroke-opacity=".2" stroke-width="1.5"/>
            SVG,
        'sun' => <<<SVG
            <circle cx="300" cy="292" r="82" fill="{$a}" fill-opacity=".16" stroke="{$a}" stroke-width="2"/>
            <circle cx="300" cy="292" r="54" fill="{$a}" fill-opacity=".14"/>
            <path d="M300 160v-30M300 452v-30M168 292h-30M462 292h-30M207 199l-21-21M414 406l-21-21M393 199l21-21M186 406l21-21" stroke="{$a}" stroke-opacity=".45" stroke-width="2.5" stroke-linecap="round"/>
            <path d="M180 400h240M196 424h208" stroke="{$a}" stroke-opacity=".3" stroke-width="1.5"/>
            SVG,
        default => '',
    };
}

foreach ($novels as $novel) {
    $from = svg_text($novel['from']);
    $to = svg_text($novel['to']);
    $accent = svg_text($novel['accent']);
    $genre = svg_text($novel['genre']);
    $author = svg_text(mb_strtoupper($novel['author']));
    $motif = motif_svg($novel['motif'], $novel['accent']);

    $titleLines = '';
    $y = 566;
    foreach ($novel['title'] as $index => $line) {
        $line = svg_text($line);
        $size = $index === 0 ? 54 : 54;
        $titleLines .= '<text x="300" y="' . $y . '" text-anchor="middle" font-family="Georgia, Times New Roman, serif" font-size="' . $size . '" font-weight="700" fill="#f7f2e8" letter-spacing="0.5">' . $line . '</text>';
        $y += 66;
    }

    $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="900" viewBox="0 0 600 900" role="img" aria-label="Sampul novel {$genre}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$from}"/>
      <stop offset="1" stop-color="{$to}"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="34%" r="55%">
      <stop offset="0" stop-color="{$accent}" stop-opacity=".22"/>
      <stop offset="1" stop-color="{$accent}" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="spine" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#000" stop-opacity=".45"/>
      <stop offset=".18" stop-color="#000" stop-opacity="0"/>
    </linearGradient>
    <filter id="grain">
      <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" stitchTiles="stitch"/>
      <feColorMatrix type="saturate" values="0"/>
    </filter>
  </defs>
  <rect width="600" height="900" fill="url(#bg)"/>
  <rect width="600" height="900" fill="url(#glow)"/>
  <rect width="600" height="900" filter="url(#grain)" opacity=".045"/>
  <rect width="600" height="900" fill="url(#spine)"/>
  <rect x="26" y="26" width="548" height="848" rx="18" fill="none" stroke="{$accent}" stroke-opacity=".28"/>
  <path d="M26 130h548" stroke="{$accent}" stroke-opacity=".22"/>
  <text x="300" y="92" text-anchor="middle" font-family="Segoe UI, Helvetica, sans-serif" font-size="15" letter-spacing="7" fill="{$accent}" fill-opacity=".85">{$genre}</text>
  <text x="300" y="152" text-anchor="middle" font-family="Segoe UI, Helvetica, sans-serif" font-size="12" letter-spacing="5" fill="#f7f2e8" fill-opacity=".55">NOVELVERSE</text>
  {$motif}
  {$titleLines}
  <path d="M250 838h100" stroke="{$accent}" stroke-opacity=".55" stroke-width="2" stroke-linecap="round"/>
  <text x="300" y="868" text-anchor="middle" font-family="Segoe UI, Helvetica, sans-serif" font-size="13" letter-spacing="2.5" fill="#f7f2e8" fill-opacity=".78">{$author}</text>
</svg>
SVG;

    $path = $outputDir . '/' . $novel['file'] . '.svg';
    file_put_contents($path, $svg);
    echo "Sampul dibuat: {$path}" . PHP_EOL;
}

$placeholder = <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="900" viewBox="0 0 600 900" role="img" aria-label="Sampul belum tersedia">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#111426"/>
      <stop offset="1" stop-color="#2a2350"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="40%" r="60%">
      <stop offset="0" stop-color="#8b5cf6" stop-opacity=".25"/>
      <stop offset="1" stop-color="#8b5cf6" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="600" height="900" fill="url(#bg)"/>
  <rect width="600" height="900" fill="url(#glow)"/>
  <rect x="26" y="26" width="548" height="848" rx="18" fill="none" stroke="#f5c451" stroke-opacity=".25"/>
  <g fill="none" stroke="#f5c451" stroke-opacity=".55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M300 336c-24-18-54-24-96-24v180c42 0 72 6 96 24 24-18 54-24 96-24V312c-42 0-72 6-96 24Z"/>
    <path d="M300 336v180"/>
  </g>
  <text x="300" y="590" text-anchor="middle" font-family="Georgia, serif" font-size="38" fill="#f7f2e8">Sampul</text>
  <text x="300" y="638" text-anchor="middle" font-family="Georgia, serif" font-size="38" fill="#f7f2e8">Belum Tersedia</text>
  <text x="300" y="820" text-anchor="middle" font-family="Segoe UI, sans-serif" font-size="13" letter-spacing="3" fill="#f5c451" fill-opacity=".75">NOVELVERSE</text>
</svg>
SVG;
file_put_contents($outputDir . '/placeholder.svg', $placeholder);
echo "Placeholder dibuat." . PHP_EOL;
