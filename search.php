<?php
// search.php
require_once 'cores/db_config.php';

// Nhận dữ liệu từ form
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';

// Xử lý truy vấn
$sql = "SELECT * FROM products WHERE status = 1";
$params = [];

if (!empty($keyword)) {
    // Tách từ khóa theo khoảng trắng để lọc chính xác (Ví dụ: "Ezviz Trong Nhà")
    $words = explode(' ', $keyword);
    $sql .= " AND (";
    $wordConditions = [];
    foreach ($words as $index => $word) {
        $word = trim($word);
        if ($word !== '') {
            $wordConditions[] = "(name LIKE :kw_$index OR sku LIKE :kw_$index OR specs_summary LIKE :kw_$index)";
            $params[":kw_$index"] = '%' . $word . '%';
        }
    }
    // Gộp các điều kiện bằng AND để đảm bảo sản phẩm chứa TẤT CẢ từ khóa
    if (count($wordConditions) > 0) {
        $sql .= implode(' AND ', $wordConditions);
    } else {
        $sql .= " 1=1 ";
    }
    $sql .= ")";
}

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

<main class="container" style="margin-top: 30px; margin-bottom: 60px; min-height: 50vh;">
    <div class="breadcrumb" style="margin-bottom: 25px; color: #666; font-size: 14px;">
        <a href="index.php" style="color: #ff5722; font-weight: bold;">Trang chủ</a> / 
        <span>Kết quả tìm kiếm cho: </span> 
        <strong>"<?php echo htmlspecialchars($keyword); ?>"</strong>
    </div>

    <div class="block-section">
        <div class="ribbon-header">
            <div class="ribbon-title">TÌM THẤY <?php echo count($products); ?> SẢN PHẨM</div>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="product-grid" style="padding-top: 10px;">
                <?php foreach($products as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px 0;">
                <h3 style="color: #888;">😥 Không tìm thấy sản phẩm nào khớp với từ khóa của bạn.</h3>
                <p style="margin-top: 10px;"><a href="index.php" style="color: #ff5722; font-weight: bold;">Quay lại trang chủ</a></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>