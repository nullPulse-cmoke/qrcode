<?php
require '../includes/auth.php';
require '../includes/db.php';
guard();

if ($_GET['logout'] ?? false) { logout(); header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>QR Scanner - <?=STORE?></title>
<link rel="stylesheet" href="/css/style.css">
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="scan-page">
<div class="scan-container">
  <header class="scan-header">
    <a href="dashboard.php" class="btn btn-sm">← Dashboard</a>
    <h1>📷 Scanner</h1>
    <a href="?logout=1" class="btn btn-sm btn-danger">🚪</a>
  </header>

  <!-- Result area -->
  <div id="result"></div>

  <!-- Scanner -->
  <div class="scan-box">
    <div class="text-center mb-10">
      <button id="startBtn" class="btn btn-large" onclick="startScan()">📸 Start Camera</button>
      <button id="stopBtn" class="btn btn-large btn-danger" style="display:none" onclick="stopScan()">⏹ Stop</button>
    </div>
    <div id="reader" style="display:none"></div>
  </div>

  <!-- Manual input -->
  <div class="card mt-10">
    <p class="text-center" style="color:#999;">— or enter code —</p>
    <div class="flex gap-8 mt-10">
      <input type="text" id="manualCode" placeholder="P-12345" class="input" style="flex:1;text-transform:uppercase;">
      <button class="btn" onclick="lookup()">🔍 Search</button>
    </div>
  </div>

  <!-- Product card -->
  <div id="productCard" class="product-card" style="display:none"></div>
</div>

<script>
let scanner = null;
let scanning = false;

function startScan() {
  document.getElementById('reader').style.display = 'block';
  document.getElementById('startBtn').style.display = 'none';
  document.getElementById('stopBtn').style.display = 'inline-block';
  document.getElementById('result').innerHTML = '';
  document.getElementById('productCard').style.display = 'none';
  
  if (!scanner) scanner = new Html5Qrcode("reader");
  
  scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    onScan,
    () => {}
  ).catch(() => {
    alert('Camera access denied. Use manual input.');
    stopScan();
  });
  scanning = true;
}

function stopScan() {
  if (scanner && scanning) {
    scanner.stop().then(() => {
      scanning = false;
      document.getElementById('reader').style.display = 'none';
      document.getElementById('startBtn').style.display = 'inline-block';
      document.getElementById('stopBtn').style.display = 'none';
    });
  }
}

function onScan(text) {
  stopScan();
  lookupCode(text.trim());
}

function lookup() {
  const code = document.getElementById('manualCode').value.trim();
  if (code) lookupCode(code);
}

function lookupCode(code) {
  document.getElementById('result').innerHTML = '<div class="alert">🔍 Searching for ' + code + '...</div>';
  
  fetch('/api/get_product.php?code=' + encodeURIComponent(code))
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showProduct(data.data);
      } else {
        document.getElementById('result').innerHTML = '<div class="alert alert-error">❌ ' + data.message + '</div>';
      }
    })
    .catch(() => {
      document.getElementById('result').innerHTML = '<div class="alert alert-error">❌ Network error</div>';
    });
}

function showProduct(p) {
  document.getElementById('result').innerHTML = `
    <div class="alert alert-success">✅ Found: ${p.product_code}</div>
  `;
  
  document.getElementById('productCard').style.display = 'block';
  document.getElementById('productCard').innerHTML = `
    <div class="pc-header">
      <h2>${p.name}</h2>
      <span class="badge">${p.product_code}</span>
    </div>
    <div class="pc-body">
      ${p.image_path ? `<div class="pc-img"><img src="${p.image_path}" onerror="this.style.display='none'"></div>` : ''}
      <div class="price-grid">
        <div class="pi purchase">
          <span class="pi-label">💰 Purchase</span>
          <span class="pi-val">${formatPrice(p.purchase_price)} ₽</span>
        </div>
        <div class="pi sale">
          <span class="pi-label">🏷️ Sale</span>
          <span class="pi-val">${formatPrice(p.sale_price)} ₽</span>
        </div>
        <div class="pi min">
          <span class="pi-label">📉 Min</span>
          <span class="pi-val">${p.min_sale_price ? formatPrice(p.min_sale_price) + ' ₽' : '-'}</span>
        </div>
        <div class="pi max">
          <span class="pi-label">📈 Max</span>
          <span class="pi-val">${p.max_sale_price ? formatPrice(p.max_sale_price) + ' ₽' : '-'}</span>
        </div>
      </div>
    </div>
    <div class="pc-actions">
      <a href="dashboard.php?edit=${p.id}" class="btn btn-primary" style="flex:1">✏️ Edit</a>
      <button class="btn" style="flex:1" onclick="scanAgain()">📸 Scan Another</button>
    </div>
  `;
}

function scanAgain() {
  document.getElementById('productCard').style.display = 'none';
  document.getElementById('result').innerHTML = '';
  document.getElementById('manualCode').value = '';
  startScan();
}

function formatPrice(v) {
  if (v === null || v === undefined || v === '') return '-';
  return parseFloat(v).toFixed(2);
}

document.getElementById('manualCode').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') lookup();
});
</script>
</body>
</html>
