<?php
// Hiển thị lỗi để debug (tắt khi deploy production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Không hiện lỗi ra HTML, chỉ ghi log
ini_set('log_errors', 1);

// Kết nối DB trước khi gọi header (đồng bộ với tất cả các trang khác)
require_once __DIR__ . '/cores/db_config.php';
include 'includes/header.php';

// Hàm helper: render 1 khối sản phẩm an toàn (HIỆN ĐỦ TẤT CẢ, KHÔNG CẮT)
function renderProductBlock($conn, $sql, $params = [], $limit = 0) {
    try {
        // Nếu có limit, thêm vào cuối câu SQL (đảm bảo không bị injection vì limit là int)
        $finalSql = $sql;
        if ($limit > 0) {
            // Kiểm tra nếu sql đã có LIMIT thì không thêm nữa
            if (stripos($sql, 'LIMIT') === false) {
                $finalSql = $sql . ' LIMIT ' . intval($limit);
            }
        }
        $stmt = $conn->prepare($finalSql);
        $stmt->execute($params);
        $prods = $stmt->fetchAll();
        if (count($prods) > 0) {
            foreach ($prods as $p) {
                include 'card_template.php';
            }
        } else {
            echo '<p style="padding: 20px; color: #888; grid-column: 1 / -1; text-align: center;">Chưa có sản phẩm nào trong mục này.</p>';
        }
    } catch (Exception $e) {
        echo '<p style="padding: 20px; color: #c00; grid-column: 1 / -1; text-align: center;">⚠ Lỗi tải sản phẩm, vui lòng thử lại sau.</p>';
        error_log("TANDA index renderProductBlock: " . $e->getMessage());
    }
}

// Hàm helper: lấy danh mục an toàn
function getCategoriesSafe($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM categories WHERE status = 1");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("TANDA index getCategoriesSafe: " . $e->getMessage());
        return [];
    }
}

// Hàm helper: lấy danh mục nổi bật an toàn
function getFeatureCatSafe($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC LIMIT 1");
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("TANDA index getFeatureCatSafe: " . $e->getMessage());
        return false;
    }
}

// Hàm helper: upper case an toàn (fallback nếu thiếu mbstring)
function safeUpper($str) {
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($str, 'UTF-8');
    }
    return strtoupper($str);
}
?>

<!-- CSS cho trang chủ -->
<link rel="stylesheet" href="assets/css/pages/index.css?v=<?php echo time(); ?>">

<main class="container" id="mainContent">

    <!-- KHỐI 1: KHUYẾN MÃI HOT -->
    <?php
    $saleProds = [];
    try {
        $stmtSale = $conn->prepare("SELECT * FROM products WHERE sale_price > 0 AND price > sale_price AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 24");
        $stmtSale->execute();
        $saleProds = $stmtSale->fetchAll();
    } catch (Exception $e) {
        error_log("TANDA index sale query: " . $e->getMessage());
    }
    if(count($saleProds) > 0):
    ?>
    <div class="product-section section-flash-sale">
        <h2 class="section-title"><i class="fas fa-bolt" style="color: #ff9f00;"></i> KHUYẾN MÃI HOT</h2>
        <div class="product-grid">
            <?php 
            foreach($saleProds as $p) {
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
            $categories = getCategoriesSafe($conn);
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
            renderProductBlock($conn, "SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC, sku DESC", [], 24);
            ?>
        </div>
        
        <div class="btn-view-more-wrap" id="suggest-btn-wrap">
            <a href="search.php" class="btn-view-more">XEM TẤT CẢ SẢN PHẨM</a>
        </div>
    </div>

    <!-- KHỐI 3: SẢN PHẨM GIÁ TỐT NHẤT -->
    <div class="product-section" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 30px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #288ad6; padding-bottom: 10px; margin-bottom: 20px;">
            <h2 class="section-title" style="font-size: 20px; margin: 0; color: #333; text-transform: uppercase;">
                <i class="fas fa-chart-line" style="color: #ff9800; margin-right: 5px;"></i> SẢN PHẨM GIÁ TỐT NHẤT
            </h2>
            <a href="search.php" style="color: #288ad6; font-size: 14px; text-decoration: none; font-weight: 500;">Xem tất cả &raquo;</a>
        </div>
        
        <div class="product-grid">
            <?php
            renderProductBlock($conn, "SELECT * FROM products WHERE status = 1 ORDER BY price ASC", [], 24);
            ?>
        </div>
    </div>

    <!-- KHỐI 4: DANH MỤC NỔI BẬT -->
    <?php
    $featureCat = getFeatureCatSafe($conn);
    if ($featureCat): 
        $fCatCode = $featureCat['cat_code'];
        $fCatName = safeUpper($featureCat['name']);
        $fCatSlug = $featureCat['slug'];
    ?>
    <div class="product-section" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 30px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; margin-bottom: 20px;">
            <h2 class="section-title" style="font-size: 20px; margin: 0; color: #333; text-transform: uppercase;">
                <i class="fas fa-gem" style="color: #4CAF50; margin-right: 5px;"></i> <?php echo htmlspecialchars($fCatName); ?> NỔI BẬT
            </h2>
            <a href="category.php?slug=<?php echo htmlspecialchars($fCatSlug); ?>" style="color: #4CAF50; font-size: 14px; text-decoration: none; font-weight: 500;">Xem tất cả &raquo;</a>
        </div>
        
        <div class="product-grid">
            <?php
            renderProductBlock($conn, "SELECT * FROM products WHERE cat_code = :cat AND status = 1 ORDER BY sort_order ASC, sku DESC", [':cat' => $fCatCode], 24);
            ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<script>
function loadProducts(catCode, btn) {
    document.querySelectorAll('.filter-pill').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');

    const grid = document.getElementById('suggest-grid');
    if (!grid) return;
    grid.style.opacity = '0.3';
    grid.style.pointerEvents = 'none';

    fetch('ajax_get_products.php?cat=' + encodeURIComponent(catCode))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if(data.success) {
                grid.innerHTML = data.html;
                var btnWrap = document.getElementById('suggest-btn-wrap');
                if (btnWrap) btnWrap.innerHTML = data.btnHtml;
            } else {
                grid.innerHTML = '<p style="color:red; padding:20px; grid-column:1/-1; text-align:center;">Lỗi nạp dữ liệu: ' + data.error + '</p>';
            }
            grid.style.opacity = '1';
            grid.style.pointerEvents = 'auto';
        })
        .catch(function(err) {
            grid.style.opacity = '1';
            grid.style.pointerEvents = 'auto';
        });
}
</script>

<?php include 'includes/footer.php'; ?>