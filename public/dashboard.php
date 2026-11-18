<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';
use CryptAccess\Core\{Auth, DB};

$user = Auth::require();
$documents = (int) DB::one('SELECT COUNT(*) n FROM encrypted_records WHERE status="encrypted"')['n'];
$files = (int) DB::one('SELECT COUNT(*) n FROM encrypted_files WHERE status="encrypted"')['n'];
$activities = (int) DB::one('SELECT COUNT(*) n FROM activity_logs WHERE administrator_id=? AND created_at>=DATE_SUB(NOW(), INTERVAL 7 DAY)', [$user['id']])['n'];
$recent = DB::pdo()->prepare('SELECT event_type, detail, created_at FROM activity_logs WHERE administrator_id=? OR administrator_id IS NULL ORDER BY id DESC LIMIT 8');
$recent->execute([$user['id']]);
page_head('Dashboard Dokumen Kampus', true);
?>
<div class="content">
  <p class="eyebrow">PUSAT KENDALI DOKUMEN KAMPUS</p>
  <h1 class="h2 mb-4">Dashboard administrator</h1>
  <div class="row g-3">
    <div class="col-md-4"><div class="panel stat"><div class="muted">Dokumen terenkripsi</div><div class="number"><?= $documents ?></div></div></div>
    <div class="col-md-4"><div class="panel stat"><div class="muted">Lampiran terlindungi</div><div class="number"><?= $files ?></div></div></div>
    <div class="col-md-4"><div class="panel stat"><div class="muted">Aktivitas Anda, 7 hari</div><div class="number"><?= $activities ?></div></div></div>
  </div>
  <div class="panel mt-4 p-3">
    <h2 class="h5">Aktivitas terbaru</h2>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aktivitas</th><th>Detail</th><th>Waktu</th></tr></thead><tbody>
    <?php foreach ($recent as $item): ?><tr><td><?= htmlspecialchars(str_replace('_', ' ', $item['event_type'])) ?></td><td class="muted"><?= htmlspecialchars($item['detail'] ?? '—') ?></td><td class="muted"><?= htmlspecialchars($item['created_at']) ?></td></tr><?php endforeach; ?>
    <?php if (!$recent->rowCount()): ?><tr><td colspan="3" class="muted">Belum ada aktivitas yang dicatat.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</div>
<?php page_foot(true); ?>