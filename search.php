<?php
// search.php
require_once 'cores/db_config.php';

// Nhận dữ liệu từ form
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';

// Xử lý truy vấn
$sql = "SELECT * FROM products WHERE (name LIKE :keyword OR sku LIKE :keyword) AND status = 1";
$params = [':keyword' => '%' . $keyword . '%'];

// Nếu khách có chọn danh mục cụ thể
if (!empty($cat)) {
    $sql .= " AND cat_code = :cat";
    $params[':cat'] = $cat;
}

$sql .= " ORDER BY sort_order ASC, sku DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>
<?php include 'includes/footer.php'; ?>