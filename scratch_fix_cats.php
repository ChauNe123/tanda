<?php
include 'cores/db_config.php';
try {
    // 1. Thêm cột icon_class nếu chưa có
    $conn->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS icon_class VARCHAR(50) DEFAULT 'fas fa-tag'");
    
    // 2. Cập nhật Icon chuẩn cho các danh mục phổ biến
    $updates = [
        'CAMERA-WIFI' => 'fas fa-video',
        'CAMERA-TRON-BO' => 'fas fa-server',
        'DAU-GHI-HINH' => 'fas fa-hdd',
        'THIET-BI-MANG' => 'fas fa-network-wired',
        'PHU-KIEN' => 'fas fa-headphones'
    ];
    
    foreach($updates as $code => $icon) {
        $stmt = $conn->prepare("UPDATE categories SET icon_class = ? WHERE cat_code = ?");
        $stmt->execute([$icon, $code]);
    }
    
    echo "SUCCESS: Database structure updated and icons assigned.";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
