<?php
require_once 'cores/db_config.php';

try {
    // 1. Thêm cột sort_order nếu chưa có
    $conn->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0 AFTER status");
    echo "✅ Đã thêm cột sort_order thành công.<br>";

    // 2. Kiểm tra cột image_1 (đã có nhưng đảm bảo nó là TEXT để lưu nhiều ảnh)
    $conn->exec("ALTER TABLE products MODIFY COLUMN image_1 TEXT");
    echo "✅ Đã tối ưu cột image_1 thành công.<br>";

    echo "<h3>Hệ thống đã sẵn sàng! Hãy quay lại trang chủ F5 nhé.</h3>";
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
