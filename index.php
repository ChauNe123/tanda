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

<style>
/* CSS cho bố cục Trang chủ mới */
.section-flash-sale {
    background: #d70018; /* Màu đỏ nổi bật cho Flash Sale */
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}
.section-flash-sale .section-title {
    color: #fff;
    font-size: 22px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Thanh bộ lọc nhanh */
.quick-filter-row {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    overflow-x: auto;
    padding-bottom: 5px;
}
.quick-filter-row::-webkit-scrollbar {
    height: 4px;
}
.quick-filter-row::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}
.filter-pill {
    background: #fff;
    border: 1px solid #e0e0e0;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    color: #333;
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
}
.filter-pill:hover, .filter-pill.active {
    background: #d70018;
    color: #fff;
    border-color: #d70018;
}

.btn-view-more-wrap {
    text-align: center;
    margin-top: 20px;
}
.btn-view-more {
    display: inline-block;
    padding: 10px 40px;
    background: #fff;
    border: 1px solid #d70018;
    color: #d70018;
    border-radius: 4px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s;
}
.btn-view-more:hover {
    background: #d70018;
    color: #fff;
}
</style>

<main class="container" id="mainContent">

    <?php
    function fitGridBySix($items) {
        $perRow = 6;
        $visibleCount = (int)(floor(count($items) / $perRow) * $perRow);
        return array_slice($items, 0, $visibleCount);
    }
    ?>

    <!-- KHỐI 1: KHUYẾN MÃI HOT -->
    <?php
    // Lấy TỐI ĐA 16 sản phẩm (2 hàng) có KHUYẾN MÃI (sale_price > 0 và sale_price < price)
    $stmtSale = $conn->prepare("SELECT * FROM products WHERE sale_price > 0 AND price > sale_price AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 16");
    $stmtSale->execute();
    $saleProds = $stmtSale->fetchAll();
    $saleProdsGrid = fitGridBySix($saleProds);

    if(count($saleProdsGrid) > 0):
    ?>
    <div class="product-section section-flash-sale">
        <h2 class="section-title"><i class="fas fa-bolt" style="color: #ff9f00;"></i> KHUYẾN MÃI HOT</h2>
        <div class="product-grid">
            <?php 
            foreach($saleProdsGrid as $p) {
                include 'card_template.php';
            }
            ?>
        </div>
        <div class="btn-view-more-wrap">
            <a href="search.php" class="btn-view-more" style="background:#fff; color:#d70018;">XEM TẤT CẢ KHUYẾN MÃI</a>
        </div>
    </div>
    <?php endif; ?>


    <!-- KHỐI 2: GỢI Ý CHO BẠN -->
    <div class="product-section" style="background: #fff; padding: 20px; border-radius: 8px;">
        <h2 class="section-title" style="font-size: 20px; margin-bottom: 15px; color: #333; text-transform: uppercase;">Gợi ý cho bạn</h2>
        
        <!-- Thanh bộ lọc nhanh -->
        <div class="quick-filter-row">
            <button onclick="loadProducts('', this)" class="filter-pill active">Tất cả</button>
            <?php
            $stmtCats = $conn->query("SELECT * FROM categories WHERE status = 1");
            $categories = $stmtCats->fetchAll();
            foreach($categories as $c):
            ?>
                <button onclick="loadProducts('<?php echo urlencode($c['cat_code']); ?>', this)" class="filter-pill">
                    <?php echo htmlspecialchars($c['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Lưới sản phẩm gợi ý -->
        <div class="product-grid" id="suggest-grid" style="transition: opacity 0.3s; min-height: 400px;">
            <?php
            // Khởi tạo luôn load 16 sản phẩm mới nhất
            $stmtSuggest = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 16");
            $stmtSuggest->execute();
            
            $suggestProds = $stmtSuggest->fetchAll();
            $suggestProdsGrid = fitGridBySix($suggestProds);
            
            if(count($suggestProdsGrid) > 0) {
                foreach($suggestProdsGrid as $p) {
                    include 'card_template.php';
                }
            } else {
                echo '<p style="padding: 20px; color: #888;">Chưa có sản phẩm nào trong mục này.</p>';
            }
            ?>
        </div>
        
        <div class="btn-view-more-wrap" id="suggest-btn-wrap">
            <?php if(count($suggestProds) > count($suggestProdsGrid) || count($suggestProds) >= 16): ?>
                <a href="search.php" class="btn-view-more">XEM TẤT CẢ SẢN PHẨM</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KHỐI 3: CAMERA BÁN CHẠY -->
    <div class="product-section" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 30px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #288ad6; padding-bottom: 10px; margin-bottom: 20px;">
            <h2 class="section-title" style="font-size: 20px; margin: 0; color: #333; text-transform: uppercase;">
                <i class="fas fa-chart-line" style="color: #ff9800; margin-right: 5px;"></i> CAMERA BÁN CHẠY NHẤT
            </h2>
            <a href="category.php?slug=camera-wifi" style="color: #288ad6; font-size: 14px; text-decoration: none; font-weight: 500;">Xem tất cả &raquo;</a>
        </div>
        
        <div class="product-grid">
            <?php
            // Lấy 16 sản phẩm giá rẻ/hot nhất
            $stmtTop = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY price ASC LIMIT 16");
            $stmtTop->execute();
            $topProds = $stmtTop->fetchAll();
            $topProdsGrid = fitGridBySix($topProds);
            if(count($topProdsGrid) > 0) {
                foreach($topProdsGrid as $p) {
                    include 'card_template.php';
                }
            } else {
                echo '<p style="padding: 20px; color: #888;">Chưa có sản phẩm nào.</p>';
            }
            ?>
        </div>
    </div>

    <!-- KHỐI 4: PHỤ KIỆN KHUYÊN DÙNG -->
    <div class="product-section" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 30px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; margin-bottom: 20px;">
            <h2 class="section-title" style="font-size: 20px; margin: 0; color: #333; text-transform: uppercase;">
                <i class="fas fa-sd-card" style="color: #4CAF50; margin-right: 5px;"></i> PHỤ KIỆN KHUYÊN DÙNG
            </h2>
            <a href="category.php?slug=phu-kien" style="color: #4CAF50; font-size: 14px; text-decoration: none; font-weight: 500;">Xem tất cả &raquo;</a>
        </div>
        
        <div class="product-grid">
            <?php
            // Lấy các sản phẩm thuộc nhóm Phụ kiện hoặc Thiết bị mạng
            $stmtAcc = $conn->prepare("SELECT * FROM products WHERE cat_code IN ('PHU-KIEN', 'THIET-BI-MANG') AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 16");
            $stmtAcc->execute();
            $accProds = $stmtAcc->fetchAll();
            $accProdsGrid = fitGridBySix($accProds);
            if(count($accProdsGrid) > 0) {
                foreach($accProdsGrid as $p) {
                    include 'card_template.php';
                }
            } else {
                echo '<p style="padding: 20px; color: #888;">Chưa có sản phẩm nào.</p>';
            }
            ?>
        </div>
    </div>

</main>

<script>
function loadProducts(catCode, btn) {
    // Đổi màu nút đang chọn
    document.querySelectorAll('.filter-pill').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');

    // Mờ lưới sản phẩm đi để chờ AJAX (hiệu ứng loading)
    const grid = document.getElementById('suggest-grid');
    grid.style.opacity = '0.3';
    grid.style.pointerEvents = 'none';

    // Gọi API lấy cục HTML
    fetch(`ajax_get_products.php?cat=${catCode}`)
        .then(res => res.json())
        .then(data => {
            // Thay thế cục HTML
            grid.innerHTML = data.html;
            document.getElementById('suggest-btn-wrap').innerHTML = data.btnHtml;
            
            // Xóa mờ
            grid.style.opacity = '1';
            grid.style.pointerEvents = 'auto';
        })
        .catch(err => {
            console.error(err);
            grid.style.opacity = '1';
            grid.style.pointerEvents = 'auto';
        });
}
</script>

<?php include 'includes/footer.php'; ?>