<?php 
// Tiền xử lý dữ liệu
// Tiền xử lý dữ liệu - Ép kiểu số để tránh lỗi Fatal trên PHP 8
$p_price = floatval($p['price'] ?? 0);
$p_sale_price = floatval($p['sale_price'] ?? 0);

$outOfStock = ($p['status'] == 0);
$chot_gia = ($p_sale_price > 0) ? $p_sale_price : $p_price;
$hasDiscount = ($p_sale_price > 0 && $p_price > $p_sale_price);
$pct = $hasDiscount ? round((($p_price - $p_sale_price) / $p_price) * 100) : 0;

// Ưu tiên lấy ảnh từ cột image_1
$display_img = 'placeholder.png';
if (!empty($p['image_1'])) {
    $image_files = explode(',', $p['image_1']); // Các ảnh được lưu cách nhau bởi dấu phẩy
    $first_image = trim($image_files[0]);
    if (!empty($first_image) && file_exists('uploads/' . $first_image)) {
        $display_img = $first_image;
    }
}

?>
<div class="tgdd-product-card <?php echo $outOfStock ? 'out-of-stock' : ''; ?>">
    
    <!-- Badge Góc (Trả góp / Giảm giá) -->
    <div class="tgdd-badge-wrap">
        <?php if($hasDiscount): ?>
            <span class="tgdd-badge badge-discount">Giảm <?php echo $pct; ?>%</span>
        <?php else: ?>
        <?php endif; ?>
    </div>

    <!-- Hình ảnh nguyên bản -->
    <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" class="tgdd-card-img">
        <img src="uploads/<?php echo htmlspecialchars($display_img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
    </a>
    
    <!-- Tên Sản Phẩm Giới Hạn 2 Dòng -->
    <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" style="text-decoration: none;">
        <h3 class="tgdd-card-title" title="<?php echo htmlspecialchars($p['name']); ?>">
            <?php echo htmlspecialchars($p['name']); ?>
        </h3>
    </a>

    <!-- Group Giá TGDD -->
    <div class="tgdd-card-price">
        <span class="tgdd-price-new"><?php echo number_format($chot_gia, 0, ',', '.'); ?>đ</span>
        <?php if($hasDiscount): ?>
            <span class="tgdd-price-old"><?php echo number_format($p_price, 0, ',', '.'); ?>đ</span>
            <span class="tgdd-price-percent">-<?php echo $pct; ?>%</span>
        <?php endif; ?>
    </div>
    
    <!-- Promo text (Giả lập để không gian đỡ trống) -->
    <div class="tgdd-card-promo">
        <?php echo $outOfStock ? '<span class="tgdd-status-label tgdd-status-out">Tạm hết hàng</span>' : '<span class="tgdd-status-label tgdd-status-in">Còn hàng</span>'; ?>
    </div>

    <!-- Cụm Hành Động (Chỉ hiện khi Hover) -->
    <div class="tgdd-card-actions">
        <?php if(!$outOfStock): ?>
            <button type="button" class="tgdd-btn-add" onclick="addToCart('<?php echo $p['sku']; ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo $display_img; ?>')">
                THÊM VÀO GIỎ
            </button>
        <?php else: ?>
            <button type="button" class="tgdd-btn-add" disabled style="opacity:0.5; cursor:not-allowed;">
                ĐẶT TRƯỚC
            </button>
        <?php endif; ?>
    </div>
</div>