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
<main class="container" style="margin-top: 20px; margin-bottom: 40px;">
    <div style="background: #fff; padding: 15px; border-radius: 8px;">
        <h2 style="font-size: 20px; text-transform: uppercase; margin-bottom: 15px; color: #ff5722;">
            <?php echo htmlspecialchars($category['name']); ?>
        </h2>
        
        <?php if (count($products) > 0): ?>
            <div class="product-grid-5">
                <?php foreach($products as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 50px 0; color: #888;">Chưa có sản phẩm nào trong danh mục này.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
    <?php if (count($products) > 0): ?>
            <?php foreach($products as $p): ?>
                <?php include 'card_template.php'; ?>
            <?php endforeach; ?>
    <?php else: ?>
    <?php endif; ?>
<?php include 'includes/footer.php'; ?>