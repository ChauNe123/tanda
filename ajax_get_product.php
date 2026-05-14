<?php
// ajax_get_product.php — AJAX endpoint: trả về chi tiết 1 sản phẩm theo SKU
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/cores/db_config.php';

$sku = trim($_GET['sku'] ?? '');

if (empty($sku)) {
    echo json_encode(['error' => 'Missing SKU']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE sku = :sku LIMIT 1");
    $stmt->execute([':sku' => $sku]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    echo json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
