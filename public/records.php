<?php require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/_layout.php';
use CryptAccess\Core\{Auth,DB,Csrf,Log};

$u = Auth::require();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::check();
    $id = (int)($_POST['id'] ?? 0);
    DB::exec('UPDATE encrypted_records SET status="deleted" WHERE id=? AND administrator_id=?', [$id,$u['id']]);
    Log::add($u['id'], 'record_deleted', 'Record #'.$id);
    header('Location: records.php');
    exit;
}$q = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$where = ' WHERE r.administrator_id=? AND r.status="encrypted"';
$params = [$u['id']];
if ($category) {
    $where .= ' AND r.category_id=?';
    $params[] = $category;
}$count = DB::one('SELECT COUNT(*) n FROM encrypted_records r'.$where, $params)['n'];
$s = DB::pdo()->prepare('SELECT r.id,r.category_id,r.created_at,c.name FROM encrypted_records r JOIN data_categories c ON c.id=r.category_id'.$where.' ORDER BY r.id DESC LIMIT 20 OFFSET '.(($page - 1) * 20));
$s->execute($params);
$cats = DB::pdo()->query('SELECT * FROM data_categories')->fetchAll();
page_head('Dokumen Terenkripsi', true);?><div class="content"><div class="d-flex justify-content-between align-items-end"><div><p class="eyebrow">MANAJEMEN DOKUMEN KAMPUS</p><h1 class="h2">Dokumen rahasia terenkripsi</h1></div><a class="btn btn-primary" href="encrypt.php">Tambah dokumen</a></div><form class="row g-2 my-4"><div class="col-md-4"><select class="form-select" name="category"><option value="">Semua kategori</option><?php foreach ($cats as $c):?><option <?=$category === $c['id'] ? 'selected' : ''?> value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach?></select></div><div class="col-auto"><button class="btn btn-outline-light">Filter</button></div></form><div class="panel p-3"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>ID</th><th>Category</th><th>Status enkripsi</th><th>Dibuat</th><th></th></tr></thead><tbody><?php foreach ($s as $r):?><tr><td>#<?=$r['id']?></td><td><?=htmlspecialchars($r['name'])?></td><td><span class="badge text-bg-success">AES-256-GCM</span></td><td class="muted"><?=htmlspecialchars($r['created_at'])?></td><td class="text-end"><a class="btn btn-sm btn-outline-info" href="decrypt.php?id=<?=$r['id']?>">Buka dokumen</a><form class="d-inline" method="post"><input type="hidden" name="csrf" value="<?=Csrf::token()?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Mark this encrypted record as deleted?')">Hapus</button></form></td></tr><?php endforeach;
if (!$s->rowCount()):?><tr><td colspan="5" class="muted">Belum ada dokumen terenkripsi.</td></tr><?php endif?></tbody></table></div><p class="muted small mt-3 mb-0"><?=$count?> dokumen ditemukan Â· page <?=$page?></p></div></div><?php page_foot(true);
