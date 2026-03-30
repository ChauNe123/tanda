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
    die("LỖI 404: Danh mục không tồn tại hoặc đã bị ẩn.");
}

// 2. Kéo Sản Phẩm thuộc danh mục này
$stmtProds = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat_code AND status = 1 ORDER BY sort_order ASC, sku DESC");
$stmtProds->execute(['cat_code' => $category['cat_code']]);
$products = $stmtProds->fetchAll();

include 'includes/header.php';
?>

<main class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <div class="breadcrumb" style="margin-bottom: 25px; color: #666; font-size: 14px;">
        <a href="index.php" style="color: #ff5722; font-weight: bold;">Trang chủ</a> / 
        <span>Danh mục</span> / 
        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
    </div>

    <div class="block-section">
        <div class="ribbon-header">
            <div class="ribbon-title"><?php echo htmlspecialchars($category['name']); ?></div>
        </div>

        <?php if (count($products) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px; padding-top: 10px;">
                <?php foreach($products as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Hiện chưa có sản phẩm nào trong danh mục này.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>