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

<main class="container" style="margin-top: 20px; margin-bottom: 60px; min-height: 50vh;">
    <div class="breadcrumb" style="margin-bottom: 20px; color: #666; font-size: 14px;">
        <a href="index.php" style="color: var(--orange-brand); font-weight: bold;">Trang chủ</a> / 
        <span>Danh mục</span> / 
        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
    </div>

    <div class="category-banner">
        <h1 class="category-main-title"><?php echo mb_strtoupper($category['name'], 'UTF-8'); ?></h1>
    </div>

    <div class="category-toolbar">
        <div class="cat-count">Hiển thị <strong><?php echo count($products); ?></strong> sản phẩm</div>
        <div class="cat-sort">
            <label>Sắp xếp theo:</label>
            <select onchange="location = this.value;">
                <option value="?slug=<?php echo $slug; ?>&sort=default" <?php echo $sort=='default'?'selected':''; ?>>Mặc định</option>
                <option value="?slug=<?php echo $slug; ?>&sort=price_asc" <?php echo $sort=='price_asc'?'selected':''; ?>>Giá tăng dần</option>
                <option value="?slug=<?php echo $slug; ?>&sort=price_desc" <?php echo $sort=='price_desc'?'selected':''; ?>>Giá giảm dần</option>
                <option value="?slug=<?php echo $slug; ?>&sort=newest" <?php echo $sort=='newest'?'selected':''; ?>>Mới nhất</option>
            </select>
        </div>
    </div>

    <?php if (count($products) > 0): ?>
        <div class="category-product-grid">
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