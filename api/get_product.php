<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$code = strtoupper(trim($_GET['code'] ?? ''));
if (!$code) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code required']);
    exit;
}

$p = fetchOne("SELECT * FROM products WHERE product_code = ?", [$code]);

if ($p) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => (int)$p['id'],
            'product_code' => $p['product_code'],
            'name' => $p['name'],
            'purchase_price' => (float)$p['purchase_price'],
            'sale_price' => (float)$p['sale_price'],
            'min_sale_price' => $p['min_sale_price'] !== null ? (float)$p['min_sale_price'] : null,
            'max_sale_price' => $p['max_sale_price'] !== null ? (float)$p['max_sale_price'] : null,
            'image_path' => $p['image_path'],
            'created_at' => $p['created_at'],
            'updated_at' => $p['updated_at']
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found: ' . $code]);
}
