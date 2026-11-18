<?php require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/_layout.php';
use CryptAccess\Core\{DB,Csrf,Crypto,Auth,Log};

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::check();
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $now = time();
    $rate = $_SESSION['login_rate'] ?? ['n' => 0,'t' => $now];
    if ($now - $rate['t'] > 900) {
        $rate = ['n' => 0,'t' => $now];
    }if ($rate['n'] >= 5) {
        $error = 'Too many attempts. Try again later.';
    } else {
        try {
            $a = DB::one('SELECT a.*,k.* FROM administrators a JOIN cryptographic_keys k ON k.administrator_id=a.id AND k.status="active" WHERE a.email=?', [$email]);
            $upload = $_FILES['private_key'] ?? null;
            if (!$a || !$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 1000000) {
                throw new RuntimeException();
            }$provided = file_get_contents($upload['tmp_name']);
            if (!is_string($provided) || !hash_equals($a['public_key_fingerprint'], Crypto::fingerprint($provided))) {
                throw new RuntimeException();
            }$unlock = Crypto::derive($_POST['passphrase'] ?? '', $a['kdf_salt'], (int)$a['kdf_iterations']);
            $private = Crypto::unseal($a['encrypted_private_key'], $unlock, $a['private_nonce'], $a['private_tag']);
            $challenge = random_bytes(32);
            $signature = Crypto::sign($challenge, $private);
            if (!Crypto::verify($challenge, $signature, $a['public_key_pem'])) {
                throw new RuntimeException();
            }Auth::login($a);
            DB::exec('UPDATE administrators SET last_login_at=NOW() WHERE id=?', [$a['id']]);
            Log::add((int)$a['id'], 'login_success');
            header('Location: dashboard.php');
            exit;
        } catch (Throwable) {
            $rate['n']++;
            $_SESSION['login_rate'] = $rate;
            Log::add(null, 'login_failed', 'Authentication rejected');
            $error = 'Access denied. Verify your passphrase and private key.';
        }
    }
}
page_head('Administrator Access');?><div class="login panel"><a class="brand" href="index.php"><i class="bi bi-shield-lock"></i> CryptAccess</a><p class="eyebrow mt-4">CRYPTOGRAPHIC ACCESS</p><h2>Administrator authentication</h2><p class="muted">Your passphrase unlocks the encrypted private key; a RSA-PSS challenge verifies its authority.</p><?php if ($error):?><p class="notice danger"><?=htmlspecialchars($error)?></p><?php endif?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=Csrf::token()?>"><label class="form-label">Administrator email</label><input required type="email" class="form-control" name="email"><label class="form-label mt-3">Symmetric passphrase</label><input required type="password" class="form-control" name="passphrase" autocomplete="current-password"><label class="form-label mt-3">RSA private key (PEM)</label><input required type="file" accept=".pem,.key,text/plain" class="form-control" name="private_key"><p class="muted small mt-2">The uploaded key is used only for verification and is never persisted.</p><button class="btn btn-primary mt-2">Verify and enter <i class="bi bi-arrow-right"></i></button></form><p class="muted small mt-4">First installation? <a class="text-info" href="setup.php">Provision administrator</a><br>PEM belum tersimpan? <a class="text-info" href="recover-key.php">Recover private key</a></p></div><?php page_foot();
