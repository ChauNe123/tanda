<?php
// category.php
require_once 'cores/db_config.php';

// Lấy slug từ URL (do .htaccess truyền vào)
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Tìm thông tin danh mục
$stmtCat = $conn->prepare("SELECT * FROM categories WHERE slug = :slug AND status = 1");
$stmtCat->execute(['slug' => $slug]);
$category = $stmtCat->fetch();

if (!$category) {
    die("Danh mục không tồn tại hoặc đã bị ẩn!"); // Có thể thay bằng trang 404 sau
}

// Lấy sản phẩm thuộc danh mục này
$stmtProd = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat_code AND status = 1 ORDER BY sku DESC");
$stmtProd->execute(['cat_code' => $category['cat_code']]);
$products = $stmtProd->fetchAll();

include 'includes/header.php';
?>

<main class="container">
    <div class="category-header" style="margin: 20px 0; border-bottom: 2px solid #0056b3; padding-bottom: 10px;">
        <h2>Danh mục: <?php echo htmlspecialchars($category['name']); ?></h2>
        <p>Hiển thị <?php echo count($products); ?> sản phẩm</p>
    </div>

    <section class="product-section">
        <div class="product-grid">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $p): ?>
                <div class="product-card">
                    <div class="img-wrap">
                        <a href="san-pham/<?php echo $p['slug']; ?>">
                            <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" class="sp-goc" alt="<?php echo htmlspecialchars($p['name']); ?>">
                            <?php if(!empty($p['frame_file'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($p['frame_file']); ?>" class="sp-vien" alt="khung">
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="info">
                        <a href="san-pham/<?php echo $p['slug']; ?>">
                            <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                        </a>
                        <div class="price-area">
                            <?php if($p['sale_price'] > 0): ?>
                                <span class="price-new"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</span>
                                <span class="price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
                            <?php else: ?>
                                <span class="price-new"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-zalo" onclick="orderViaZalo('<?php echo htmlspecialchars($p['name']); ?>', '<?php echo $p['sale_price'] > 0 ? $p['sale_price'] : $p['price']; ?>')">💬 Chốt đơn qua Zalo</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Hiện chưa có sản phẩm nào trong danh mục này.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>