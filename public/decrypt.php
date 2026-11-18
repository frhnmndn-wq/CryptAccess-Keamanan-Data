<?php require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/_layout.php';
use CryptAccess\Core\{Auth,DB,Csrf,Crypto,Log};

$u = Auth::require();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$record = DB::one('SELECT r.*,ek.encrypted_dek,k.encrypted_private_key,k.kdf_salt,k.kdf_iterations,k.private_nonce,k.private_tag,k.public_key_pem FROM encrypted_records r JOIN encryption_keys ek ON ek.record_id=r.id JOIN cryptographic_keys k ON k.id=ek.cryptographic_key_id WHERE r.id=? AND r.administrator_id=? AND r.status="encrypted"', [$id,$u['id']]);
if (!$record) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}$error = null;
$plain = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::check();
    try {
        $f = $_FILES['private_key'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException();
        }$provided = file_get_contents($f['tmp_name']);
        if (Crypto::fingerprint($provided) !== Crypto::fingerprint($record['public_key_pem'])) {
            throw new RuntimeException();
        }$unlock = Crypto::derive($_POST['passphrase'] ?? '', $record['kdf_salt'], (int)$record['kdf_iterations']);
        $priv = Crypto::unseal($record['encrypted_private_key'], $unlock, $record['private_nonce'], $record['private_tag']);
        $dek = Crypto::unwrap($record['encrypted_dek'], $priv);
        $plain = ['title' => Crypto::unseal($record['title_ciphertext'], $dek, $record['title_nonce'], $record['title_tag']),'content' => Crypto::unseal($record['content_ciphertext'], $dek, $record['content_nonce'], $record['content_tag'])];
        Log::add($u['id'], 'record_decrypted', 'Record #'.$id);
    } catch (Throwable) {
        $error = 'Dekripsi ditolak. Kunci, passphrase, atau integritas dokumen tidak dapat diverifikasi.';
    }
}
page_head('Pusat Dekripsi Dokumen', true);?><div class="content"><p class="eyebrow">DECRYPTION CENTER</p><h1 class="h2">Buka dokumen #<?=$id?></h1><?php if ($plain):?><div class="panel p-4 mt-4"><p class="eyebrow">AUTHENTICATED PLAINTEXT</p><h2><?=htmlspecialchars($plain['title'])?></h2><pre class="text-light" style="white-space:pre-wrap;font-family:inherit"><?=htmlspecialchars($plain['content'])?></pre><a href="records.php" class="btn btn-outline-light">Close</a></div><?php else:?><p class="muted">Autentikasi AES-GCM harus berhasil sebelum isi dokumen ditampilkan.</p><?php if ($error):?><p class="notice danger"><?=htmlspecialchars($error)?></p><?php endif?><form method="post" enctype="multipart/form-data" class="panel p-4 mt-4 col-xl-7"><input type="hidden" name="csrf" value="<?=Csrf::token()?>"><input type="hidden" name="id" value="<?=$id?>"><label class="form-label">Symmetric passphrase</label><input required type="password" class="form-control" name="passphrase"><label class="form-label mt-3">RSA private key yang sesuai (PEM)</label><input required type="file" class="form-control" name="private_key" accept=".pem,.key,text/plain"><button class="btn btn-primary mt-4">Verifikasi dan buka dokumen</button></form><?php endif?></div><?php page_foot(true);
