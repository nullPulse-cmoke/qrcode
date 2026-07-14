<?php
require '../includes/auth.php';
require '../includes/db.php';
guard();

// Handle actions
$msg = '';
if ($_GET['logout'] ?? false) { logout(); header('Location: index.php'); exit; }

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code'] ?: 'P-' . str_pad(rand(1,99999),5,'0',STR_PAD_LEFT)));
        $name = $_POST['name'] ?? '';
        $purchase = floatval($_POST['purchase'] ?? 0);
        $sale = floatval($_POST['sale'] ?? 0);
        $min = $_POST['min'] ? floatval($_POST['min']) : null;
        $max = $_POST['max'] ? floatval($_POST['max']) : null;
        $img = $_POST['img_url'] ?? ''; // For Vercel, use URL instead of file upload
        
        // Check code exists
        $chk = db()->prepare("SELECT id FROM products WHERE product_code=?");
        $chk->bind_param('s', $code);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $msg = '❌ Code already exists: ' . $code;
        } else {
            $st = db()->prepare("INSERT INTO products (product_code,name,purchase_price,sale_price,min_sale_price,max_sale_price,image_path) VALUES (?,?,?,?,?,?,?)");
            $st->bind_param('ssddss', $code, $name, $purchase, $sale, $min, $max, $img);
            if ($st->execute()) {
                $msg = '✅ Product added! Code: ' . $code;
            } else {
                $msg = '❌ Error: ' . db()->error;
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $st = db()->prepare("DELETE FROM products WHERE id=?");
        $st->bind_param('i', $id);
        $st->execute();
        $msg = '✅ Deleted';
    }
    
    if ($action === 'bulk_delete') {
        $ids = json_decode($_POST['ids'] ?? '[]');
        foreach ($ids as $id) {
            $st = db()->prepare("DELETE FROM products WHERE id=?");
            $st->bind_param('i', $id);
            $st->execute();
        }
        $msg = '✅ ' . count($ids) . ' product(s) deleted';
    }
    
    if ($action === 'update') {
        $id = intval($_POST['id']);
        $name = $_POST['name'];
        $purchase = floatval($_POST['purchase']);
        $sale = floatval($_POST['sale']);
        $min = $_POST['min'] ? floatval($_POST['min']) : null;
        $max = $_POST['max'] ? floatval($_POST['max']) : null;
        $st = db()->prepare("UPDATE products SET name=?,purchase_price=?,sale_price=?,min_sale_price=?,max_sale_price=? WHERE id=?");
        $st->bind_param('sddddi', $name, $purchase, $sale, $min, $max, $id);
        $st->execute();
        $msg = '✅ Updated!';
    }
}

// Get products
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$per = 20;
$offset = ($page-1)*$per;

$allowed = ['id','product_code','name','purchase_price','sale_price','created_at'];
if (!in_array($sort, $allowed)) $sort = 'id';
$order = ($order === 'ASC') ? 'ASC' : 'DESC';
$nextOrder = ($order === 'ASC') ? 'DESC' : 'ASC';

$where = '';
$params = [];
if ($search) {
    $where = "WHERE name LIKE ? OR product_code LIKE ?";
    $params = ['%'.$search.'%', '%'.$search.'%'];
}

$cnt = db()->prepare("SELECT COUNT(*) FROM products $where");
if ($params) $cnt->bind_param('ss', ...$params);
$cnt->execute();
$total = $cnt->get_result()->fetch_row()[0];
$pages = ceil($total / $per);

$sql = "SELECT * FROM products $where ORDER BY $sort $order LIMIT $per OFFSET $offset";
$q = db()->prepare($sql);
if ($params) $q->bind_param('ss', ...$params);
$q->execute();
$products = $q->get_result()->fetch_all(MYSQLI_ASSOC);

// For editing
$edit = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $eq = db()->prepare("SELECT * FROM products WHERE id=?");
    $eq->bind_param('i', $eid);
    $eq->execute();
    $edit = $eq->get_result()->fetch_assoc();
}
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
  <!-- Header -->
  <header class="header">
    <div><strong>👶 <?=STORE?></strong> <span style="color:#999;font-size:13px;">Product Management</span></div>
    <div class="flex gap-8">
      <a href="scan.php" class="btn btn-sm">📷 Scan</a>
      <a href="?logout=1" class="btn btn-sm btn-danger">🚪 Logout</a>
    </div>
  </header>

  <?php if ($msg): ?>
    <div class="alert alert-success"><?=h($msg)?> <button onclick="this.parentElement.remove()" style="float:right;background:none;border:none;cursor:pointer;">✕</button></div>
  <?php endif; ?>

  <div class="grid">
    <!-- Left: Add/Edit Form -->
    <div class="card">
      <h2><?=$edit?'✏️ Edit Product':'➕ Add Product'?></h2>
      <form method="post">
        <input type="hidden" name="action" value="<?=$edit?'update':'add'?>">
        <?php if ($edit): ?>
          <input type="hidden" name="id" value="<?=$edit['id']?>">
        <?php endif; ?>
        
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
          <label>Image URL (optional)</label>
          <input type="text" name="img_url" value="<?=$edit?h($edit['image_path']):''?>" placeholder="https://example.com/image.jpg">
        </div>
        <button type="submit" class="btn btn-primary btn-block"><?=$edit?'💾 Update':'💾 Add Product'?></button>
        <?php if ($edit): ?>
          <a href="dashboard.php" style="display:block;text-align:center;margin-top:8px;color:#666;">↩ Cancel</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Right: Product List -->
    <div class="card">
      <div class="flex-between mb-10">
        <h2>📋 Products (<?=$total?>)</h2>
        <form class="flex gap-8">
          <input type="text" name="search" value="<?=h($search)?>" placeholder="Search..." class="input-sm">
          <button class="btn btn-sm">🔍</button>
          <?php if ($search): ?><a href="dashboard.php" class="btn btn-sm btn-secondary">✕</a><?php endif; ?>
        </form>
      </div>

      <!-- Bulk actions -->
      <div id="bulkBar" class="bulk-bar" style="display:none">
        <span id="bulkCount"></span>
        <button onclick="bulkDelete()" class="btn btn-sm btn-danger">🗑 Delete</button>
        <button onclick="bulkQR()" class="btn btn-sm">📱 Download QR ZIP</button>
        <button onclick="deselectAll()" class="btn btn-sm btn-secondary">Deselect</button>
      </div>

      <!-- Table -->
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
              <td>
                <div class="qr-mini" id="qr-<?=$p['id']?>"></div>
                <script>
                  new QRCode(document.getElementById('qr-<?=$p['id']?>'), {
                    text: '<?=h($p['product_code'])?>',
                    width: 40,
                    height: 40,
                    correctLevel: QRCode.CorrectLevel.H
                  });
                </script>
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
            <?php if (!$products): ?>
            <tr><td colspan="10" style="text-align:center;padding:40px;color:#999;">📭 No products found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
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
// QR Download function
function downloadQR(code) {
  const c = document.createElement('canvas');
  new QRCode(c, { text: code, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.H });
  setTimeout(() => {
    c.querySelector('img').remove();
    const svg = c.innerHTML;
    const blob = new Blob([svg], {type: 'image/svg+xml'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'QR_' + code + '.svg';
    a.click();
    URL.revokeObjectURL(url);
  }, 100);
}

// Bulk actions
function toggleAll(master) {
  document.querySelectorAll('.cb').forEach(cb => cb.checked = master.checked);
  updateBulk();
}

function updateBulk() {
  const checked = document.querySelectorAll('.cb:checked');
  const bar = document.getElementById('bulkBar');
  if (checked.length > 0) {
    bar.style.display = 'flex';
    document.getElementById('bulkCount').textContent = checked.length + ' selected';
  } else {
    bar.style.display = 'none';
  }
}

function deselectAll() {
  document.querySelectorAll('.cb').forEach(cb => cb.checked = false);
  updateBulk();
  document.getElementById('selAll').checked = false;
}

function bulkDelete() {
  const checked = document.querySelectorAll('.cb:checked');
  if (!checked.length || !confirm('Delete ' + checked.length + ' products?')) return;
  const ids = Array.from(checked).map(cb => JSON.parse(cb.value).id);
  const f = document.createElement('form');
  f.method = 'post';
  f.innerHTML = `<input type="hidden" name="action" value="bulk_delete"><input type="hidden" name="ids" value='${JSON.stringify(ids)}'>`;
  document.body.appendChild(f);
  f.submit();
}

function bulkQR() {
  const checked = document.querySelectorAll('.cb:checked');
  if (!checked.length) return;
  
  const zip = new JSZip();
  let count = 0;
  
  checked.forEach(cb => {
    const data = JSON.parse(cb.value);
    const code = data.code;
    const div = document.createElement('div');
    new QRCode(div, { text: code, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.H });
    
    setTimeout(() => {
      const img = div.querySelector('img');
      if (img) {
        const svg = div.innerHTML.replace('<img', '<svg').replace('>', ' xmlns="http://www.w3.org/2000/svg">');
        zip.file('QR_' + code + '.svg', svg);
      }
      count++;
      if (count === checked.length) {
        zip.generateAsync({type: 'blob'}).then(blob => {
          saveAs(blob, 'qr_codes.zip');
        });
      }
    }, 200);
  });
}
</script>
</body>
</html>
