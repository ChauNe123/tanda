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

// 2. Query Sản Phẩm
$limit = 10;
$stmt = $conn->prepare("SELECT * FROM products WHERE sale_price > 0  ORDER BY sort_order ASC, sku DESC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->execute();
$dealHotProds = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'CAM-WIFI'  ORDER BY sort_order ASC, sku DESC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->execute();
$camWifiProds = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'CAM-DAY'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->execute();
$camBoProds = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'DAU-GHI'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->execute();
$dauGhiProds = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'PHU-KIEN'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->execute();
$phuKienProds = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="container" style="margin-top: 15px; margin-bottom: 30px;">
    <div class="banner-wrapper">
        <div class="banner-main">
            <a href="<?php echo isset($banners['BANNER-CHINH']) ? htmlspecialchars($banners['BANNER-CHINH']['target_link']) : '#'; ?>">
                <?php if(isset($banners['BANNER-CHINH'])): ?>
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-CHINH']['image_file']); ?>" alt="Banner Main">
                <?php else: ?>
                    <img src="https://via.placeholder.com/800x400/ff5722/fff?text=Banner+Chính">
                <?php endif; ?>
            </a>
        </div>
        <div class="banner-side">
            <?php for($i=1; $i<=2; $i++): $code = "BANNER-PHU-$i"; ?>
                <a href="<?php echo isset($banners[$code]) ? htmlspecialchars($banners[$code]['target_link']) : '#'; ?>">
                    <?php if(isset($banners[$code])): ?>
                        <img src="banners/<?php echo htmlspecialchars($banners[$code]['image_file']); ?>">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/400x195/e64a19/fff?text=Banner+Phụ+<?php echo $i; ?>">
                    <?php endif; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<div class="container box-product deal-box">
    <div class="box-header">
        <h2 class="box-title">🔥 DEAL HOT MỖI NGÀY</h2>
    </div>
    <div class="product-grid">
        <?php foreach($dealHotProds as $p) { include 'card_template.php'; } ?>
    </div>
</div>

<div class="container box-product">
    <div class="box-header">
        <h2 class="box-title">CAMERA WIFI CHÍNH HÃNG</h2>
        <a href="category.php?slug=camera-wifi" class="view-all">Xem tất cả ></a>
    </div>
    <div class="product-grid">
        <?php foreach($camWifiProds as $p) { include 'card_template.php'; } ?>
    </div>
</div>

<div class="container box-product">
    <div class="box-header">
        <h2 class="box-title">CAMERA TRỌN BỘ SIÊU NÉT</h2>
        <a href="category.php?slug=camera-tron-bo" class="view-all">Xem tất cả ></a>
    </div>
    <div class="product-grid">
        <?php foreach($camBoProds as $p) { include 'card_template.php'; } ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>