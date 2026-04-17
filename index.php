<?php include 'includes/header.php'; ?>

    <div class="container hero-banner">
        <div class="banner-main">
            <img src="banners/1.png" alt="Banner Chính">
        </div>
        <div class="banner-sub">
            <img src="banners/2.png" alt="Banner Phụ 1">
            <img src="banners/3.png" alt="Banner Phụ 2">
        </div>
    </div>

    <main class="container" id="mainContent">
        <?php
        // 1. Lấy danh sách danh mục đang hiển thị
        $stmtCats = $conn->query("SELECT * FROM categories WHERE status = 1");
        $categories = $stmtCats->fetchAll();

        foreach($categories as $cat) {
            // 2. Lấy 10 sản phẩm nổi bật của từng danh mục
            $stmtProds = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 10");
            $stmtProds->execute(['cat' => $cat['cat_code']]);
            $prods = $stmtProds->fetchAll();

            if (count($prods) > 0) {
                // Khối danh mục (TGDD style)
                echo '<div class="product-section fade-in">';
                echo '  <div class="section-header">';
                echo '      <h2 class="section-title">' . htmlspecialchars($cat['name']) . '</h2>';
                echo '  </div>';
                echo '  <div class="product-grid">';

                foreach($prods as $p) {
                    include 'card_template.php';
                }

                echo '  </div>';
                echo '</div>';
            }
        }
        ?>
    </main>

<?php include 'includes/footer.php'; ?>

<!-- Gọi trực tiếp CSS Card vào tạm để test (Sau này sẽ gộp main.css) -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/layout/grid.css">
<link rel="stylesheet" href="assets/css/components/product-card.css">