<?php
require_once 'cores/db_config.php';
try {
    // Tạo Index để tăng tốc truy vấn tìm kiếm và sắp xếp
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_status_sort ON products(status, sort_order)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_cat ON products(cat_code)");
    echo "✅ Đã tối ưu hóa bộ chỉ mục Database thành công!";
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
