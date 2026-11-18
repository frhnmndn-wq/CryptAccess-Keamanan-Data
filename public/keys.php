<?php require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/_layout.php';
use CryptAccess\Core\{Auth,DB};

$u = Auth::require();
$s = DB::pdo()->prepare('SELECT id,key_version,public_key_pem,status,created_at FROM cryptographic_keys WHERE administrator_id=? ORDER BY key_version DESC');
$s->execute([$u['id']]);
page_head('Manajemen Kunci', true);?><div class="content"><p class="eyebrow">KEY MANAGEMENT</p><h1 class="h2">Versi RSA key administrator</h1><p class="muted">Private key material is AES-256-GCM encrypted at rest and is never rendered by this interface. Retired keys must remain available while associated data exists.</p><div class="panel p-3 mt-4"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Version</th><th>Algorithm</th><th>Public-key fingerprint</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach ($s as $k):?><tr><td>v<?=$k['key_version']?></td><td>RSA-2048</td><td class="font-monospace small"><?=hash('sha256', $k['public_key_pem'])?></td><td><span class="badge <?=$k['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary'?>"><?=htmlspecialchars($k['status'])?></span></td><td class="muted"><?=htmlspecialchars($k['created_at'])?></td></tr><?php endforeach?></tbody></table></div></div><p class="notice mt-4">Key rotation is intentionally not automatic: rolling keys requires re-wrapping each retained DEK with the new public key after authorization. This avoids making historical data unrecoverable.</p></div><?php page_foot(true);
