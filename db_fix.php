<?php
require_once 'cores/db_config.php';

try {
    $conn->exec("TRUNCATE TABLE categories");
    $sql = "INSERT INTO categories (cat_code, name, slug, status) VALUES
    ('KB-WIFI', 'Camera WiFi', 'camera-wifi', 1),
    ('KB-DAY', 'Camera Trọn Bộ', 'camera-tron-bo', 1),
    ('KB-REC', 'Đầu Ghi Hình', 'dau-ghi-hinh', 1),
    ('KB-PHU', 'Phụ Kiện', 'phu-kien', 1),
    ('THIET-BI-MANG', 'Thiết Bị Mạng', 'thiet-bi-mang', 1)";
    
    $conn->exec($sql);
    echo "Inserted categories successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
