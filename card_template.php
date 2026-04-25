<?php 
// Tiền xử lý dữ liệu
$outOfStock = ($p['status'] == 0);
$chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
$hasDiscount = ($p['sale_price'] > 0 && $p['price'] > $p['sale_price']);
$pct = $hasDiscount ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100) : 0;

// Ưu tiên lấy ảnh từ cột image_file
$display_img = 'placeholder.png';
if (!empty($p['image_file'])) {
    $image_files = explode(',', $p['image_file']); // Giả sử các ảnh được lưu cách nhau bởi dấu phẩy
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
            <span class="tgdd-badge badge-installment">Trả góp 0%</span>
        <?php endif; ?>
    </div>

    <!-- Hình ảnh nguyên bản -->
    <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" class="tgdd-card-img">
        <img src="uploads/<?php echo htmlspecialchars($display_img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
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
            <span class="tgdd-price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
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