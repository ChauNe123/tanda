<?php
require_once 'cores/db_config.php';

try {
    $stmt = $conn->query("SELECT id, name, image_file FROM products LIMIT 10");
    $products = $stmt->fetchAll();

    echo "<pre>";
    foreach ($products as $product) {
        echo "ID: " . $product['id'] . "\n";
        echo "Name: " . $product['name'] . "\n";
        echo "Image File: " . $product['image_file'] . "\n";
        echo str_repeat("-", 40) . "\n";
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>