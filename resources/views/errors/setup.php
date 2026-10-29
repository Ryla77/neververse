<?php

declare(strict_types=1);
?>
<section class="error-page">
    <div class="error-page__card">
        <span class="error-page__code">Setup</span>
        <span class="eyebrow">Luminara</span>
        <h1>Pemasangan diperlukan</h1>
        <p class="lead">Aplikasi belum memiliki berkas konfigurasi <code class="code">.env</code>. Jalankan pemasangan untuk membuat database, mengimpor data contoh, dan menyiapkan akun admin pertama.</p>
        <div class="error-page__steps">
            <p class="small muted">Jalankan dari direktori proyek:</p>
            <pre class="code-block"><code>php bin/install.php</code></pre>
            <p class="small muted">Atau salin <code class="code">.env.example</code> menjadi <code class="code">.env</code>, isi kredensial database, lalu impor <code class="code">database/schema.sql</code> dan <code class="code">database/seed.sql</code>. Lihat README untuk langkah lengkap.</p>
        </div>
    </div>
</section>
