<?php
use CryptAccess\Core\{Auth, Csrf};

function page_head(string $title, bool $admin = false): void
{
    $user = Auth::user();
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?> · CryptAccess</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<?php if ($admin): ?>
  <aside class="sidebar">
    <a class="brand" href="dashboard.php"><i class="bi bi-shield-lock"></i> CryptAccess</a>
    <small>DOKUMEN RAHASIA KAMPUS</small>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="records.php">Dokumen Terenkripsi</a>
      <a href="encrypt.php">Tambah Dokumen</a>
      <a href="records.php">Buka Dokumen</a>
      <a href="keys.php">Manajemen Kunci</a>
      <a href="logs.php">Audit Aktivitas</a>
    </nav>
    <form method="post" action="logout.php">
      <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">
      <button type="submit">Logout</button>
    </form>
  </aside>
  <main class="admin-main">
    <header class="topbar">
      <span><i class="bi bi-circle-fill text-info"></i> Proteksi dokumen aktif</span>
      <span><?= htmlspecialchars($user['name']) ?></span>
    </header>
<?php endif;
}

function page_foot(bool $admin = false): void
{
    if ($admin) {
        echo '</main>';
    }
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
}
