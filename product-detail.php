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

<main class="container pd-container">
    <div class="pd-breadcrumb">
        <a href="index.php">Trang chủ</a> <span>/</span>
        <strong><?php echo htmlspecialchars($p['name']); ?></strong>
    </div>

    <div class="pd-layout">
        <div class="pd-left">
            <div class="pd-img-wrap">
                <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="pd-img-main">
                
                <?php if(!empty($p['frame_file'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($p['frame_file']); ?>" class="pd-img-frame" alt="Frame product">
                <?php endif; ?>
            </div>
        </div>

        <div class="pd-right">
            <?php 
            // KHAI BÁO GIÁ CHỐT NGAY TỪ ĐẦU ĐỂ KHÔNG BỊ LỖI
            $chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price']; 
            ?>

            <h1 class="pd-title"><?php echo htmlspecialchars($p['name']); ?></h1>
            
            <div class="pd-meta">
                Mã SP: <strong><?php echo htmlspecialchars($p['sku']); ?></strong> 
                <span style="margin: 0 10px; color: #ddd">|</span>
                Tình trạng: <span class="pd-status-on">Còn hàng</span>
            </div>

            <div class="pd-price-box premium-price-box">
                <div class="price-header"><i class="fas fa-fire"></i> GIÁ ƯU ĐÃI ĐẶC BIỆT</div>
                <div class="price-content">
                    <span class="pd-price-main"><?php echo number_format($chot_gia, 0, ',', '.'); ?>đ</span>
                    
                    <?php if ($p['sale_price'] > 0 && $p['price'] > $chot_gia): ?>
                        <div class="old-price-group">
                            <span class="pd-price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
                            <span class="discount-percent">-<?php echo round((($p['price'] - $chot_gia) / $p['price']) * 100); ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if(!empty($p['coupon_code'])): ?>
                    <div class="pd-coupon-tag" style="color: #d70018; border-color: #d70018;"><i class="fas fa-gift"></i> Mã giảm giá: <?php echo htmlspecialchars($p['coupon_code']); ?></div>
                <?php else: ?>
                    <div class="pd-coupon-tag"><i class="fas fa-shipping-fast"></i> Miễn phí giao hàng toàn quốc (Freeship)</div>
                <?php endif; ?>
            </div>

            <?php if(!empty($p['specs_summary'])): ?>
            <div class="pd-summary modern-summary">
                <h3><i class="fas fa-info-circle"></i> THÔNG SỐ CƠ BẢN</h3>
                <div class="pd-summary-content">
                    <?php echo nl2br(htmlspecialchars($p['specs_summary'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="pd-action-box modern-actions">
                <button type="button" class="btn-add-cart-pro" onclick="addToCart('<?php echo $p['sku']; ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo $p['image_file']; ?>')">
                    <i class="fas fa-cart-plus"></i>
                    <div class="btn-text">
                        <strong>THÊM VÀO GIỎ HÀNG</strong>
                        <span>Tiến hành thanh toán ngay</span>
                    </div>
                </button>
                
                <button type="button" class="btn-zalo-pro" onclick="orderViaZalo('<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>)">
                    <i class="fas fa-comment-dots"></i>
                    <div class="btn-text">
                        <strong>TƯ VẤN QUA ZALO</strong>
                        <span>Hỗ trợ kỹ thuật 24/7</span>
                    </div>
                </button>
            </div>

            <div class="pd-trust modern-trust">
                <div class="trust-item"><i class="fas fa-shield-alt"></i> Hàng chính hãng 100%</div>
                <div class="trust-item"><i class="fas fa-tools"></i> Bảo hành 24 tháng</div>
                <div class="trust-item"><i class="fas fa-exchange-alt"></i> Lỗi 1 đổi 1 (30 ngày)</div>
                <div class="trust-item"><i class="fas fa-headset"></i> Hỗ trợ trọn đời</div>
            </div>
        </div>
    </div>

    <div class="block-section" style="margin-top: 40px; padding: 30px;">
        <div class="ribbon-header">
            <div class="ribbon-title">MÔ TẢ CHI TIẾT SẢN PHẨM</div>
        </div>
        
        <div class="pd-full-specs-content">
            <?php 
                if(!empty($p['description'])) {
                    $text = trim($p['description']);
                    if (strpos($text, '<') !== false && strpos($text, '>') !== false) {
                        echo $text;
                    } else {
                        $lines = explode("\n", $text);
                        $inList = false;

                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line)) continue;

                            if (preg_match('/^[-*]\s+(.*)$/', $line, $matches)) {
                                if (!$inList) { echo '<ul>'; $inList = true; }
                                $itemText = htmlspecialchars($matches[1]);
                                if (strpos($itemText, ':') !== false) {
                                    $parts = explode(':', $itemText, 2);
                                    echo '<li><strong>' . trim($parts[0]) . ':</strong>' . $parts[1] . '</li>';
                                } else {
                                    echo '<li>' . $itemText . '</li>';
                                }
                            } else {
                                if ($inList) { echo '</ul>'; $inList = false; }

                                if (mb_strtoupper($line, 'UTF-8') === $line && mb_strlen($line, 'UTF-8') > 5) {
                                    echo '<h3>' . htmlspecialchars($line) . '</h3>';
                                } elseif (substr($line, -1) === ':') {
                                    echo '<h3>' . htmlspecialchars($line) . '</h3>';
                                } else {
                                    echo '<p>' . htmlspecialchars($line) . '</p>';
                                }
                            }
                        }
                        if ($inList) { echo '</ul>'; }
                    }
                } else {
                    echo '<p style="color:#888; font-style:italic; text-align: center; padding: 30px 0;">Nội dung chi tiết đang được cập nhật...</p>';
                }
            ?>
        </div>
    </div>

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
    <div class="block-section" style="margin-top: 40px; padding: 30px;">
        <div class="ribbon-header">
            <div class="ribbon-title">CÓ THỂ BẠN CŨNG THÍCH</div>
            <a href="category.php?slug=<?php echo $cat_slug; ?>" class="view-all-link">Xem thêm &raquo;</a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 15px; padding-top: 10px;">
            <?php 
            $p_backup = $p; 
            foreach($relatedProds as $p) {
                include 'card_template.php'; 
            }
            $p = $p_backup; 
            ?>
        </div>
    </div>
    <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>