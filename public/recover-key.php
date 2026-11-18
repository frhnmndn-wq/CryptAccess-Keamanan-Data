<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/_layout.php';
use CryptAccess\Core\{DB,Csrf,Crypto,Log};

$error = null;
$pem = null;
$fileName = 'cryptaccess-private-key.pem';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::check();
    try {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $admin = DB::one('SELECT a.id,a.display_name,k.encrypted_private_key,k.kdf_salt,k.kdf_iterations,k.private_nonce,k.private_tag FROM administrators a JOIN cryptographic_keys k ON k.administrator_id=a.id AND k.status="active" WHERE a.email=?', [$email]);
        if (!$admin) {
            throw new RuntimeException('Rejected');
        }
        $unlock = Crypto::derive($_POST['passphrase'] ?? '', $admin['kdf_salt'], (int)$admin['kdf_iterations']);
        $pem = Crypto::unseal($admin['encrypted_private_key'], $unlock, $admin['private_nonce'], $admin['private_tag']);
        Crypto::fingerprint($pem);
        $fileName = 'cryptaccess-'.preg_replace('/[^a-z0-9]+/i', '-', strtolower($admin['display_name'])).'-private-key.pem';
        Log::add((int)$admin['id'], 'private_key_recovered');
    } catch (Throwable) {
        $error = 'Recovery rejected. Verify your email and passphrase.';
    }
}
page_head('Private Key Recovery'); ?>
<div class="login panel"><a class="brand" href="index.php"><i class="bi bi-shield-lock"></i> CryptAccess</a>
<?php if ($pem !== null): ?>
<p class="eyebrow mt-4">KEY RECOVERY COMPLETE</p><h2>Download your RSA private key</h2><p class="notice">Simpan file ini di lokasi aman. Jangan membagikannya atau mengunggahkannya ke penyimpanan publik.</p>
<a class="btn btn-primary mt-2" download="<?=htmlspecialchars($fileName)?>" href="data:application/x-pem-file;base64,<?=base64_encode($pem)?>"><i class="bi bi-download"></i> Download PEM key</a>
<p class="muted small mt-4">Setelah mengunduh, gunakan file ini bersama passphrase Anda pada halaman login.</p><a class="btn btn-outline-light" href="login.php">Back to login</a>
<?php else: ?>
<p class="eyebrow mt-4">CONTROLLED RECOVERY</p><h2>Recover RSA private key</h2><p class="muted">Masukkan kredensial yang sama saat provisioning. Kunci hanya dibuka sementara untuk diunduh dan tidak dicatat di log.</p>
<?php if ($error): ?><p class="notice danger"><?=htmlspecialchars($error)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=Csrf::token()?>"><label class="form-label">Administrator email</label><input required type="email" name="email" class="form-control"><label class="form-label mt-3">Symmetric passphrase</label><input required type="password" name="passphrase" class="form-control"><button class="btn btn-primary mt-4">Verify and prepare download</button></form><p class="muted small mt-4"><a class="text-info" href="login.php">Back to login</a></p>
<?php endif; ?></div><?php page_foot();
