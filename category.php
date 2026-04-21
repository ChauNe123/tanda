<?php
// category.php
require_once 'cores/db_config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header("Location: index.php");
    exit;
}

// 1. Kéo thông tin Danh Mục từ bảng `categories`
$stmtCat = $conn->prepare("SELECT * FROM categories WHERE slug = :slug AND status = 1 LIMIT 1");
$stmtCat->execute(['slug' => $slug]);
$category = $stmtCat->fetch();

if (!$category) {
    die("LỖI 404: Danh mục không tồn tại hoặc đã bị ẩn. (Sếp nhớ chạy lệnh SQL thêm danh mục nhé!)");
}

// 2. Xử lý logic nút "Sắp xếp theo"
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$orderSql = "ORDER BY sort_order ASC, sku DESC"; // Mặc định

if ($sort === 'price_asc') {
    // Giá tăng dần (Ưu tiên lấy giá sale nếu có, không có thì lấy giá gốc)
    $orderSql = "ORDER BY CASE WHEN sale_price > 0 THEN sale_price ELSE price END ASC";
} elseif ($sort === 'price_desc') {
    // Giá giảm dần
    $orderSql = "ORDER BY CASE WHEN sale_price > 0 THEN sale_price ELSE price END DESC";
} elseif ($sort === 'newest') {
    // Mới nhất (Dựa vào mã SKU mới thêm)
    $orderSql = "ORDER BY sku DESC";
}

// 3. Kéo Sản Phẩm thuộc danh mục này kèm theo điều kiện sắp xếp
$stmtProds = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat_code AND status = 1 $orderSql");
$stmtProds->execute(['cat_code' => $category['cat_code']]);
$products = $stmtProds->fetchAll();

include 'includes/header.php';
?>

<style>
.sort-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 15px;
    background: #f1f1f1;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    font-size: 13px;
    transition: all 0.2s;
    border: 1px solid #e0e0e0;
}
.sort-btn:hover {
    background: #e0e0e0;
}
.sort-btn.active {
    background: #eaf4fc;
    color: #288ad6;
    border-color: #288ad6;
    font-weight: bold;
}
.sort-btn.active i {
    color: #288ad6;
}
</style>

<main class="container" style="margin-top: 20px; margin-bottom: 60px; min-height: 50vh;">
    <div class="breadcrumb" style="margin-bottom: 15px; color: #288ad6; font-size: 13px;">
        <a href="index.php" style="color: #288ad6; text-decoration: none;"><i class="fas fa-home"></i> Trang chủ</a> 
        <span style="color:#999; margin: 0 5px;">&rsaquo;</span> 
        <strong style="color:#333;"><?php echo htmlspecialchars($category['name']); ?></strong>
    </div>

    <!-- BOX LỌC VÀ TIÊU ĐỀ -->
    <div class="category-filter-box" style="background: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
        <h1 style="font-size: 20px; color: #333; margin: 0 0 15px 0; text-transform: uppercase;">
            <?php echo htmlspecialchars($category['name']); ?> 
            <span style="font-size: 14px; font-weight: normal; color: #888; text-transform: none;">(<?php echo count($products); ?> sản phẩm)</span>
        </h1>
        
        <div class="sort-buttons" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <span style="font-size: 14px; color: #666; font-weight: bold; margin-right: 5px;">Sắp xếp theo:</span>
            <a href="?slug=<?php echo $slug; ?>&sort=newest" class="sort-btn <?php echo ($sort=='newest' || $sort=='default') ? 'active' : ''; ?>">
                <i class="fas fa-sort-amount-down"></i> Mới nhất
            </a>
            <a href="?slug=<?php echo $slug; ?>&sort=price_asc" class="sort-btn <?php echo ($sort=='price_asc') ? 'active' : ''; ?>">
                <i class="fas fa-sort-numeric-down"></i> Giá Thấp - Cao
            </a>
            <a href="?slug=<?php echo $slug; ?>&sort=price_desc" class="sort-btn <?php echo ($sort=='price_desc') ? 'active' : ''; ?>">
                <i class="fas fa-sort-numeric-down-alt"></i> Giá Cao - Thấp
            </a>
        </div>
    </div>

    <?php if (count($products) > 0): ?>
        <div class="product-grid">
            <?php foreach($products as $p): ?>
                <?php include 'card_template.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-product">
            <i class="fas fa-box-open"></i>
            <p>Hiện chưa có sản phẩm nào trong danh mục này.</p>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>