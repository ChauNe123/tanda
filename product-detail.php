<?php
// product-detail.php
require_once 'cores/db_config.php';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM products WHERE slug = :slug AND status = 1 LIMIT 1");
$stmt->execute(['slug' => $slug]);
$p = $stmt->fetch();

if (!$p) {
    include 'includes/header.php';
    echo '<main class="container"><h2 style="text-align:center; padding: 100px 0;">Sản phẩm không tồn tại.</h2></main>';
    include 'includes/footer.php'; exit;
}

// Xử lý biến cat_name để làm Breadcrumb
$cat_name = 'Danh mục';
if($p['cat_code'] === 'CAM-WIFI') $cat_name = 'Camera Wifi';
elseif($p['cat_code'] === 'CAM-DAY') $cat_name = 'Trọn bộ Camera';
elseif($p['cat_code'] === 'DAU-GHI') $cat_name = 'Đầu Ghi Hình';
elseif($p['cat_code'] === 'PHU-KIEN') $cat_name = 'Phụ Kiện';
elseif($p['cat_code'] === 'THIET-BI-MANG') $cat_name = 'Thiết Bị Mạng';

$chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
include 'includes/header.php';
?>

<main class="container pd-container">
    <ul class="tgdd-breadcrumb">
        <li><a href="index.php">Trang chủ</a></li>
        <li><a href="category.php?slug=<?php echo strtolower(str_replace(' ', '-', $cat_name)); ?>"><?php echo $cat_name; ?></a></li>
        <li><?php echo htmlspecialchars($p['name']); ?></li>
    </ul>

    <div class="pd-main">
        <div class="pd-left">
            <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
        </div>
        
        <div class="pd-right">
            <h1 class="pd-title"><?php echo htmlspecialchars($p['name']); ?></h1>
            
            <div class="pd-price-box">
                <?php if($p['sale_price'] > 0): ?>
                    <span class="pd-price-new"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>₫</span>
                    <?php if($p['price'] > $p['sale_price']): ?>
                        <span class="pd-price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="pd-price-new"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                <?php endif; ?>
            </div>

            <?php if(!empty($p['specs_summary'])): ?>
            <div class="pd-desc">
                <strong>Thông số nổi bật:</strong><br><br>
                <?php echo nl2br(htmlspecialchars($p['specs_summary'])); ?>
            </div>
            <?php endif; ?>

            <div class="pd-actions">
                <?php if($p['status'] == 1): ?>
                    <button type="button" class="btn-buy-now" onclick="addToCart('<?php echo $p['sku']; ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo $p['image_file']; ?>'); window.location.href='cart.php';">
                        MUA NGAY
                        <span>Giao tận nơi hoặc nhận tại cửa hàng</span>
                    </button>
                    <button type="button" class="btn-add-cart" onclick="addToCart('<?php echo $p['sku']; ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo $p['image_file']; ?>')" title="Thêm vào giỏ hàng">
                        <i class="fas fa-cart-plus"></i>
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-buy-now" style="background: #ccc; cursor: not-allowed;" disabled>TẠM HẾT HÀNG</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    $stmtRelated = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat AND sku != :sku AND status = 1 ORDER BY sort_order ASC LIMIT 5");
    $stmtRelated->execute(['cat' => $p['cat_code'], 'sku' => $p['sku']]);
    $relatedProds = $stmtRelated->fetchAll();
    if(count($relatedProds) > 0): 
    ?>
    <div style="margin-top: 40px; border-top: 2px solid #f1f1f1; padding-top: 20px;">
        <h3 style="font-size: 18px; text-transform: uppercase; margin-bottom: 15px; color: #333;">Sản phẩm cùng danh mục</h3>
        <div class="product-grid-5">
            <?php foreach($relatedProds as $p): ?>
                <?php include 'card_template.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>