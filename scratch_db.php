<?php
require 'cores/db_config.php';

echo "CATEGORIES:\n";
$stmt = $conn->query("SELECT * FROM categories LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "PRODUCTS LIMIT 1:\n";
$stmt = $conn->query("SELECT * FROM products LIMIT 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
