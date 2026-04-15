<?php
// product-detail.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Kết nối DB
require_once 'cores/db_config.php';

// 2. Bắt biến slug (Đã fix lỗi dấu /)
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: index.php"); 
    exit;
}

// 3. Query lấy SP bằng SLUG
$stmt = $conn->prepare("SELECT * FROM products WHERE slug = :slug AND status = 1 LIMIT 1");
$stmt->execute(['slug' => $slug]);
$p = $stmt->fetch();

if (!$p) {
    include 'includes/header.php';
    echo '<main class="container pd-container"><h2 style="text-align:center; padding: 100px 0; color: #888;">😥 Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.</h2></main>';
    include 'includes/footer.php';
    exit;
}

// 4. Require Header
include 'includes/header.php';
?>
    <?php
    $stmtRelated = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat AND sku != :sku AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 5");
    $stmtRelated->execute(['cat' => $p['cat_code'], 'sku' => $p['sku']]);
    $relatedProds = $stmtRelated->fetchAll();
    
    if(count($relatedProds) > 0): 
        // Xác định link xem tất cả theo danh mục
        $cat_slug = 'camera-wifi';
        if($p['cat_code'] === 'CAM-WIFI') $cat_slug = 'camera-wifi';
        elseif($p['cat_code'] === 'CAM-DAY') $cat_slug = 'camera-tron-bo';
        elseif($p['cat_code'] === 'DAU-GHI') $cat_slug = 'dau-ghi-hinh';
        elseif($p['cat_code'] === 'PHU-KIEN') $cat_slug = 'phu-kien';
        elseif($p['cat_code'] === 'THIET-BI-MANG') $cat_slug = 'thiet-bi-mang';
    ?>
    <?php endif; ?>
<?php include 'includes/footer.php'; ?>