<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

page_head('Manajemen Dokumen Rahasia Kampus');
?>
<nav class="nav-public">
  <div class="container d-flex justify-content-between align-items-center">
    <a class="brand" href="index.php"><i class="bi bi-shield-lock"></i> CryptAccess</a>
    <div class="d-none d-md-flex gap-4 muted">
      <a href="#security">Keamanan</a>
      <a href="#technology">Teknologi</a>
    </div>
    <a class="btn btn-primary btn-sm" href="login.php">Akses Administrator</a>
  </div>
</nav>
<section class="hero">
  <div class="container">
    <p class="eyebrow">MANAJEMEN DOKUMEN RAHASIA KAMPUS</p>
    <h1>LINDUNGI DOKUMEN<br>STRATEGIS KAMPUS.</h1>
    <p class="muted fs-5 mt-4 col-lg-7">CryptAccess membantu pimpinan dan staf berwenang menyimpan dokumen internal kampus dalam bentuk terenkripsi, terverifikasi, dan tercatat.</p>
    <div class="mt-4"><a class="btn btn-primary px-4 py-3" href="login.php">Masuk sebagai administrator <i class="bi bi-arrow-right ms-2"></i></a></div>
  </div>
</section>
<section id="security" class="container py-5">
  <p class="eyebrow">PERLINDUNGAN DOKUMEN</p>
  <h2 class="mb-4">Dokumen internal terlindungi dari penyimpanan hingga akses.</h2>
  <div class="row g-3">
    <div class="col-md-3"><div class="panel feature"><i class="bi bi-lock"></i><h5 class="mt-3">AES-256-GCM</h5><p class="muted mb-0">Isi setiap dokumen dienkripsi dan diverifikasi integritasnya.</p></div></div>
    <div class="col-md-3"><div class="panel feature"><i class="bi bi-key"></i><h5 class="mt-3">RSA-2048</h5><p class="muted mb-0">Kunci dokumen dilindungi oleh RSA key administrator.</p></div></div>
    <div class="col-md-3"><div class="panel feature"><i class="bi bi-diagram-3"></i><h5 class="mt-3">Hybrid Encryption</h5><p class="muted mb-0">Setiap dokumen mempunyai DEK acak yang berbeda.</p></div></div>
    <div class="col-md-3"><div class="panel feature"><i class="bi bi-journal-lock"></i><h5 class="mt-3">Audit Akses</h5><p class="muted mb-0">Enkripsi dan dekripsi tercatat pada audit trail.</p></div></div>
  </div>
</section>
<section id="technology" class="container py-5">
  <div class="panel p-4 p-md-5">
    <p class="eyebrow">TEKNOLOGI</p>
    <h2>Kontrol akses berlapis untuk dokumen kampus.</h2>
    <p class="muted col-lg-8 mb-0">Passphrase membuka private key yang tersimpan terenkripsi. Private key tersebut hanya digunakan untuk mengakses Data Encryption Key dari dokumen yang berwenang dibuka.</p>
  </div>
</section>
<footer class="container py-5 muted small">&copy; <?= date('Y') ?> CryptAccess &middot; Sistem Manajemen Dokumen Rahasia Kampus</footer>
<?php page_foot(); ?>