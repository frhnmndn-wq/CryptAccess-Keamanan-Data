<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/_layout.php';
use CryptAccess\Core\{DB,Csrf,Crypto,Log};

$exists = DB::one('SELECT id FROM administrators LIMIT 1');
$error = null;
$pem = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$exists) {
    Csrf::check();
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass = $_POST['passphrase'] ?? '';
    if (!$email || mb_strlen($name) < 2 || strlen($pass) < 14 || $pass !== ($_POST['confirm'] ?? '')) {
        $error = 'Gunakan nama, email valid, dan passphrase yang sama minimal 14 karakter.';
    } else {
        try {
            [$priv,$pub] = Crypto::pair();
            $salt = random_bytes(32);
            $key = Crypto::derive($pass, $salt);
            [$cipher,$nonce,$tag] = Crypto::aes($priv, $key);
            DB::pdo()->beginTransaction();
            DB::exec('INSERT INTO administrators(display_name,email,public_key_fingerprint) VALUES(?,?,?)', [$name,$email,Crypto::fingerprint($pub)]);
            $id = (int)DB::pdo()->lastInsertId();
            DB::exec('INSERT INTO cryptographic_keys(administrator_id,key_version,public_key_pem,encrypted_private_key,kdf_salt,kdf_iterations,private_nonce,private_tag) VALUES(?,?,?,?,?,?,?,?)', [$id,1,$pub,$cipher,$salt,Crypto::PBKDF2_ITERATIONS,$nonce,$tag]);
            Log::add($id, 'administrator_provisioned');
            DB::pdo()->commit();
            $pem = $priv;
        } catch (Throwable $e) {
            if (DB::pdo()->inTransaction()) {
                DB::pdo()->rollBack();
            }$error = 'Provisioning gagal diselesaikan.';
        }
    }
}
page_head('Initial Provisioning'); ?>
<div class="login panel"><?php if ($exists): ?><h2>Administrator sudah tersedia</h2><p class="muted">Lanjut ke halaman akses aman.</p><a class="btn btn-primary" href="login.php">Administrator Access</a><?php elseif ($pem): ?><p class="eyebrow">PROVISIONING COMPLETE</p><h2>Simpan private key ini sekarang.</h2><p class="notice">Kunci hanya ditampilkan sekali dan tidak disimpan plaintext di database.</p><textarea class="form-control mt-3" rows="12" readonly><?= htmlspecialchars($pem) ?></textarea><br><a class="btn btn-primary mt-3" download="cryptaccess-private-key.pem" href="data:application/x-pem-file;base64,<?= base64_encode($pem) ?>">Download PEM key</a> <a class="btn btn-outline-light mt-3" href="login.php">Continue to login</a><?php else: ?><p class="eyebrow">FIRST-RUN SETUP</p><h2>Provision the first administrator</h2><p class="muted">RSA 2048-bit akan dibuat dan private key dienkripsi oleh passphrase.</p><?php if ($error): ?><p class="notice danger"><?=htmlspecialchars($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=Csrf::token()?>"><label class="form-label mt-2">Display name</label><input required class="form-control" name="name"><label class="form-label mt-3">Email</label><input required type="email" class="form-control" name="email"><label class="form-label mt-3">Passphrase</label><input required type="password" minlength="14" class="form-control" name="passphrase"><label class="form-label mt-3">Confirm passphrase</label><input required type="password" class="form-control" name="confirm"><button class="btn btn-primary mt-4">Generate and provision keys</button></form><?php endif; ?></div><?php page_foot();
