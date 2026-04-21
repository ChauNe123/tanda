<?php
require_once 'cores/db_config.php';
$stmt = $conn->query("SELECT * FROM categories");
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "CATEGORIES:\n";
print_r($cats);

$stmt2 = $conn->query("SELECT COUNT(*) FROM products");
$count = $stmt2->fetchColumn();
echo "\nTOTAL PRODUCTS: $count\n";

$stmt3 = $conn->query("SELECT cat_code, COUNT(*) as c FROM products GROUP BY cat_code");
$catCounts = $stmt3->fetchAll(PDO::FETCH_ASSOC);
echo "\nPRODUCT CATEGORY COUNTS:\n";
print_r($catCounts);
?>
