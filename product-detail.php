<?php
// product-detail.php
require_once 'cores/db_config.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

$stmt = $conn->prepare("SELECT * FROM products WHERE slug = :slug AND status = 1");
$stmt->execute(['slug' => $slug]);
$product = $stmt->fetch();

if (!$product) {
    die("Sản phẩm không tồn tại!");
}

include 'includes/header.php';
?>

<main class="container" style="margin-top: 30px;">
    <div class="breadcrumb" style="margin-bottom: 20px; color: #666; font-size: 14px;">
        <a href="index.php" style="color: #0056b3;">Trang chủ</a> / 
        <span>Sản phẩm</span> / 
        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
    </div>

    <div class="product-detail-layout" style="display: flex; gap: 30px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        
        <div class="detail-image" style="width: 40%; position: relative;">
            <div class="img-wrap" style="padding-top: 100%;">
                <img src="uploads/<?php echo htmlspecialchars($product['image_file']); ?>" class="sp-goc" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php if(!empty($product['frame_file'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($product['frame_file']); ?>" class="sp-vien" alt="khung">
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-info" style="width: 60%;">
            <h1 style="font-size: 24px; margin-bottom: 15px; line-height: 1.4;"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p style="color: #666; margin-bottom: 15px;">Mã SP: <strong><?php echo htmlspecialchars($product['sku']); ?></strong></p>

            <div class="detail-price-box" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php if($product['sale_price'] > 0): ?>
                    <span style="font-size: 28px; color: #d70018; font-weight: bold;"><?php echo number_format($product['sale_price'], 0, ',', '.'); ?>đ</span>
                    <span style="font-size: 16px; color: #707070; text-decoration: line-through; margin-left: 15px;"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                    <?php if(!empty($product['coupon_code'])): ?>
                        <div style="margin-top: 10px; font-weight: bold; color: #28a745;">🎁 Mã ưu đãi: <?php echo htmlspecialchars($product['coupon_code']); ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="font-size: 28px; color: #d70018; font-weight: bold;"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                <?php endif; ?>
            </div>

            <div class="specs-summary" style="margin-bottom: 25px; line-height: 1.6;">
                <h3 style="font-size: 16px; margin-bottom: 10px; text-transform: uppercase;">Thông số nổi bật:</h3>
                <p><?php echo nl2br(htmlspecialchars($product['specs_summary'])); ?></p>
            </div>

            <button class="btn-zalo" style="width: 100%; padding: 15px; font-size: 18px; border-radius: 8px;" onclick="orderViaZalo('<?php echo htmlspecialchars($product['name']); ?>', '<?php echo $product['sale_price'] > 0 ? $product['sale_price'] : $product['price']; ?>')">
                💬 LIÊN HỆ TƯ VẤN & LẮP ĐẶT QUA ZALO
                <span style="display: block; font-size: 13px; font-weight: normal; margin-top: 5px;">Chúng tôi sẽ phản hồi trong vòng 5 phút</span>
            </button>

            <div class="trust-badges" style="margin-top: 20px; display: flex; gap: 15px; font-size: 13px; color: #555;">
                <span>✅ Bảo hành chính hãng 24T</span>
                <span>✅ Hỗ trợ kỹ thuật 24/7</span>
                <span>✅ Khảo sát tận nơi</span>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>