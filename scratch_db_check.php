<?php
require_once 'cores/db_config.php';
$stmt = $conn->query("SELECT sku, name, status, cat_code FROM products LIMIT 20");
$prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "TONG SO SAN PHAM: " . count($prods) . "\n";
print_r($prods);
?>
