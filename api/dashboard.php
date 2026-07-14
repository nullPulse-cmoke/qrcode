<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
guard();

$msg = '';
if ($_GET['logout'] ?? false) { logout(); header('Location: login'); exit; }

if ($_POST) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code'] ?: 'P-' . str_pad(rand(1,99999),5,'0',STR_PAD_LEFT)));
        $name = $_POST['name'] ?? '';
        $purchase = floatval($_POST['purchase'] ?? 0);
        $sale = floatval($_POST['sale'] ?? 0);
        $min = $_POST['min'] ? floatval($_POST['min']) : null;
        $max = $_POST['max'] ? floatval($_POST['max']) : null;
        $img = $_POST['img_url'] ?? '';

        $exists = fetchOne("SELECT id FROM products WHERE product_code = ?", [$code]);
        if ($exists) {
            $msg = '❌ Code already exists: ' . $code;
        } else {
            query("INSERT INTO products (product_code, name, purchase_price, sale_price, min_sale_price, max_sale_price, image_path) VALUES (?,?,?,?,?,?,?)",
                [$code, $name, $purchase, $sale, $min, $max, $img]);
            $msg = '✅ Product added! Code: ' . $code;
        }
    }

    if ($action === 'delete') {
        query("DELETE FROM products WHERE id = ?", [intval($_POST['id'] ?? 0)]);
        $msg = '✅ Deleted';
    }

    if ($action === 'bulk_delete') {
        $ids = json_decode($_POST['ids'] ?? '[]');
        foreach ($ids as $id) query("DELETE FROM products WHERE id = ?", [intval($id)]);
        $msg = '✅ ' . count($ids) . ' deleted';
    }

    if ($action === 'update') {
        query("UPDATE products SET name=?, purchase_price=?, sale_price=?, min_sale_price=?, max_sale_price=? WHERE id=?",
            [$_POST['name'], floatval($_POST['purchase']), floatval($_POST['sale']),
             $_POST['min'] ? floatval($_POST['min']) : null,
             $_POST['max'] ? floatval($_POST['max']) : null,
             intval($_POST['id'])]);
        $msg = '✅ Updated!';
    }
}

// Products query
$search = $_GET['search'] ?? '';
$sort = in_array($_GET['sort'] ?? '', ['id','product_code','name','purchase_price','sale_price','created_at']) ? $_GET['sort'] : 'id';
$order = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';
$page = max(1, intval($_GET['page'] ?? 1));
$per = 20;

$where = '';
$params = [];
if ($search) {
    $where = "WHERE name ILIKE ? OR product_code ILIKE ?";
    $params = ['%'.$search.'%', '%'.$search.'%'];
}

$total = fetchOne("SELECT COUNT(*) as cnt FROM products $where", $params)['cnt'];
$pages = ceil($total / $per);
$products = fetchAll("SELECT * FROM products $where ORDER BY $sort $order LIMIT $per OFFSET " . (($page-1)*$per), $params);

$edit = isset($_GET['edit']) ? fetchOne("SELECT * FROM products WHERE id = ?", [intval($_GET['edit'])]) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard - <?=STORE?></title>
<link rel="stylesheet" href="/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
</head>
<body>
<div class="app">
  <header class="header">
    <div><strong>👶 <?=STORE?></strong> <span style="color:#999;font-size:13px;">Product Management</span></div>
    <div class="flex gap-8">
      <a href="scan" class="btn btn-sm">📷 Scan</a>
      <a href="?logout=1" class="btn btn-sm btn-danger">🚪 Logout</a>
    </div>
  </header>

  <?php if ($msg): ?>
    <div class="alert alert-success"><?=h($msg)?> <button onclick="this.parentElement.remove()" style="float:right;background:none;border:none;cursor:pointer;">✕</button></div>
  <?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2><?=$edit?'✏️ Edit Product':'➕ Add Product'?></h2>
      <form method="post">
        <input type="hidden" name="action" value="<?=$edit?'update':'add'?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?=$edit['id']?>"><?php endif; ?>

        <div class="form-group">
          <label>Product Code</label>
          <input type="text" name="code" value="<?=$edit?h($edit['product_code']):''?>" placeholder="Auto (P-XXXXX)">
        </div>
        <div class="form-group">
          <label>Name *</label>
          <input type="text" name="name" value="<?=$edit?h($edit['name']):''?>" required>
        </div>
        <div class="row">
          <div class="form-group">
            <label>Purchase Price *</label>
            <input type="number" step="0.01" name="purchase" value="<?=$edit?h($edit['purchase_price']):''?>" required>
          </div>
          <div class="form-group">
            <label>Sale Price *</label>
            <input type="number" step="0.01" name="sale" value="<?=$edit?h($edit['sale_price']):''?>" required>
          </div>
        </div>
        <div class="row">
          <div class="form-group">
            <label>Min Price</label>
            <input type="number" step="0.01" name="min" value="<?=$edit&&$edit['min_sale_price']?h($edit['min_sale_price']):''?>">
          </div>
          <div class="form-group">
            <label>Max Price</label>
            <input type="number" step="0.01" name="max" value="<?=$edit&&$edit['max_sale_price']?h($edit['max_sale_price']):''?>">
          </div>
        </div>
        <div class="form-group">
          <label>Image URL</label>
          <input type="text" name="img_url" value="<?=$edit?h($edit['image_path']):''?>" placeholder="https://...">
        </div>
        <button type="submit" class="btn btn-primary btn-block"><?=$edit?'💾 Update':'💾 Add Product'?></button>
        <?php if ($edit): ?>
          <a href="dashboard" style="display:block;text-align:center;margin-top:8px;color:#666;">↩ Cancel</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="card">
      <div class="flex-between mb-10">
        <h2>📋 Products (<?=$total?>)</h2>
        <form class="flex gap-8">
          <input type="text" name="search" value="<?=h($search)?>" placeholder="Search..." class="input-sm">
          <button class="btn btn-sm">🔍</button>
          <?php if ($search): ?><a href="dashboard" class="btn btn-sm btn-secondary">✕</a><?php endif; ?>
        </form>
      </div>

      <div id="bulkBar" class="bulk-bar" style="display:none">
        <span id="bulkCount"></span>
        <button onclick="bulkDelete()" class="btn btn-sm btn-danger">🗑 Delete</button>
        <button onclick="bulkQR()" class="btn btn-sm">📱 Download QR ZIP</button>
        <button onclick="deselectAll()" class="btn btn-sm btn-secondary">Deselect</button>
      </div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><input type="checkbox" id="selAll" onchange="toggleAll(this)"></th>
              <th>Img</th>
              <th><a href="?sort=product_code&order=<?=$nextOrder?>&search=<?=urlencode($search)?>">Code <?=$sort==='product_code'?($order==='ASC'?'↑':'↓'):''?></a></th>
              <th><a href="?sort=name&order=<?=$nextOrder?>&search=<?=urlencode($search)?>">Name <?=$sort==='name'?($order==='ASC'?'↑':'↓'):''?></a></th>
              <th>Purchase</th>
              <th>Sale</th>
              <th>Min</th>
              <th>Max</th>
              <th>QR</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
              <td><input type="checkbox" class="cb" value='<?=json_encode(['id'=>$p['id'],'code'=>$p['product_code']])?>' onchange="updateBulk()"></td>
              <td>
                <?php if ($p['image_path']): ?>
                  <img src="<?=h($p['image_path'])?>" class="thumb" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%23eee%22 width=%2240%22 height=%2240%22/><text x=%2220%22 y=%2225%22 text-anchor=%22middle%22 font-size=%2220%22>👕</text></svg>'">
                <?php else: ?>
                  <span class="thumb-placeholder">👕</span>
                <?php endif; ?>
              </td>
              <td><strong><?=h($p['product_code'])?></strong></td>
              <td><?=h($p['name'])?></td>
              <td><?=number_format($p['purchase_price'],2)?> ₽</td>
              <td><strong><?=number_format($p['sale_price'],2)?> ₽</strong></td>
              <td><?=$p['min_sale_price']?number_format($p['min_sale_price'],2).' ₽':'-'?></td>
              <td><?=$p['max_sale_price']?number_format($p['max_sale_price'],2).' ₽':'-'?></td>
              <td><div class="qr-mini" id="qr-<?=$p['id']?>"></div>
                <script>new QRCode(document.getElementById('qr-<?=$p['id']?>'),{text:'<?=h($p['product_code'])?>',width:40,height:40,correctLevel:QRCode.CorrectLevel.H});</script>
              </td>
              <td class="actions">
                <a href="?edit=<?=$p['id']?>" class="btn-icon" title="Edit">✏️</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?=$p['id']?>">
                  <button class="btn-icon" title="Delete">🗑️</button>
                </form>
                <button class="btn-icon" title="Download QR" onclick="downloadQR('<?=h($p['product_code'])?>')">⬇️</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?><tr><td colspan="10" style="text-align:center;padding:40px;color:#999;">📭 No products</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?>
          <a href="?page=<?=$i?>&sort=<?=$sort?>&order=<?=$order?>&search=<?=urlencode($search)?>" class="page <?=$i==$page?'active':''?>"><?=$i?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function downloadQR(code) {
  const c = document.createElement('div');
  new QRCode(c, { text: code, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.H });
  setTimeout(() => {
    const img = c.querySelector('img');
    if (!img) return;
    const svg = c.innerHTML.replace('<img','<svg').replace('>',' xmlns="http://www.w3.org/2000/svg">');
    const blob = new Blob([svg], {type: 'image/svg+xml'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'QR_' + code + '.svg';
    a.click();
  }, 200);
}
function toggleAll(m) { document.querySelectorAll('.cb').forEach(c => c.checked = m.checked); updateBulk(); }
function updateBulk() {
  const c = document.querySelectorAll('.cb:checked');
  document.getElementById('bulkBar').style.display = c.length ? 'flex' : 'none';
  if (c.length) document.getElementById('bulkCount').textContent = c.length + ' selected';
}
function deselectAll() { document.querySelectorAll('.cb').forEach(c => c.checked = false); updateBulk(); document.getElementById('selAll').checked = false; }
function bulkDelete() {
  const c = document.querySelectorAll('.cb:checked');
  if (!c.length || !confirm('Delete '+c.length+'?')) return;
  const ids = Array.from(c).map(cb => JSON.parse(cb.value).id);
  const f = document.createElement('form'); f.method = 'post';
  f.innerHTML = '<input type=hidden name=action value=bulk_delete><input type=hidden name=ids value=\''+JSON.stringify(ids)+'\'>';
  document.body.appendChild(f); f.submit();
}
function bulkQR() {
  const c = document.querySelectorAll('.cb:checked');
  if (!c.length) return;
  const zip = new JSZip(); let count = 0;
  c.forEach(cb => {
    const code = JSON.parse(cb.value).code;
    const d = document.createElement('div');
    new QRCode(d, { text: code, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.H });
    setTimeout(() => {
      const img = d.querySelector('img');
      if (img) zip.file('QR_'+code+'.svg', d.innerHTML.replace('<img','<svg').replace('>',' xmlns="http://www.w3.org/2000/svg">'));
      count++; if (count === c.length) zip.generateAsync({type:'blob'}).then(b => saveAs(b, 'qr_codes.zip'));
    }, 200);
  });
}
</script>
</body>
</html>
