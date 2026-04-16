<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/cores/db_config.php';

// 1. Kéo Banners
$stmtBanners = $conn->prepare("SELECT banner_code, image_file, target_link FROM banners WHERE status = 1");
$stmtBanners->execute();
$bannerList = $stmtBanners->fetchAll();
$banners = [];
foreach ($bannerList as $b) { 
    $banners[$b['banner_code']] = $b; 
}

// 2. Tối ưu Query Sản Phẩm (Dùng sku làm fallback sort, không dùng id)
$limit = 10;

// Deal Hot (Giảm giá)
$stmt = $conn->prepare("SELECT * FROM products WHERE sale_price > 0  ORDER BY sort_order ASC, sku DESC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$dealHotProds = $stmt->fetchAll();

// Camera Wifi (cat_code = 'CAM-WIFI')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'CAM-WIFI'  ORDER BY sort_order ASC, sku DESC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$camWifiProds = $stmt->fetchAll();

// Camera Trọn Bộ (cat_code = 'CAM-DAY')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'CAM-DAY'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$camBoProds = $stmt->fetchAll();

// Đầu Ghi Hình (cat_code = 'DAU-GHI')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'DAU-GHI'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$dauGhiProds = $stmt->fetchAll();

// Phụ Kiện (cat_code = 'PHU-KIEN')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'PHU-KIEN'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$phuKienProds = $stmt->fetchAll();

// Thiết Bị Mạng (cat_code = 'THIET-BI-MANG')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'THIET-BI-MANG'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$thietBiMangProds = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<main class="container">
    
    <section class="flash-sale-wrap">
        <div class="fs-title">Khuyến mãi online</div>
        
        <div class="fs-tabs">
            <div class="fs-tab active">FLASH SALE GIÁ SỐC</div>
            <div class="fs-tab">GIẢM ĐẾN 50%</div>
            <div class="fs-tab">Camera Wifi</div>
            <div class="fs-tab">Đầu Ghi</div>
            <div class="fs-tab">Phụ Kiện</div>
        </div>

        <div class="product-grid-5">
            <?php if(!empty($dealHotProds)): ?>
                <?php foreach($dealHotProds as $p): ?>
                    
                    <?php include 'card_template.php'; ?>

                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align:center; padding: 30px;">Hiện tại chưa có sản phẩm Flash Sale.</p>
            <?php endif; ?>
        </div>
        <div class="product-grid-5">
            <?php if(!empty($dealHotProds)): ?>
                <?php foreach($dealHotProds as $p): ?>
                    
                    <?php include 'card_template.php'; ?>

                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align:center; padding: 30px;">Hiện tại chưa có sản phẩm Flash Sale.</p>
            <?php endif; ?>
        </div>

        <?php if(!empty($dealHotProds)): ?>
        <div class="view-all-box">
            <a href="category.php?slug=flash-sale" class="btn-view-all">Xem tất cả khuyến mãi <i class="fas fa-caret-right"></i></a>
        </div>
        <?php endif; ?>
    </section>

</main>