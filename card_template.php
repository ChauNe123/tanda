<?php 
// Tiền xử lý dữ liệu
$outOfStock = ($p['status'] == 0);
$chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
$hasDiscount = ($p['sale_price'] > 0 && $p['price'] > $p['sale_price']);
$pct = $hasDiscount ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100) : 0;
?>
<div class="tgdd-product-card <?php echo $outOfStock ? 'out-of-stock' : ''; ?>">
    
    <!-- Badge Góc (Trả góp / Giảm giá) -->
    <div class="tgdd-badge-wrap">
        <?php if($hasDiscount): ?>
            <span class="tgdd-badge badge-discount">Giảm <?php echo $pct; ?>%</span>
        <?php endif; ?>
    </div>

    <!-- Hình ảnh nguyên bản -->
    <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" class="tgdd-card-img">
        <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
    </a>
    
    <!-- Tên Sản Phẩm Giới Hạn 2 Dòng -->
    <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" style="text-decoration: none;">
        <h3 class="tgdd-card-title" title="<?php echo htmlspecialchars($p['name']); ?>">
            <?php echo htmlspecialchars($p['name']); ?>
        </h3>
    </a>

    <!-- Group Giá TGDD -->
    <div class="tgdd-card-price">
        <span class="tgdd-price-new"><?php echo number_format($chot_gia, 0, ',', '.'); ?>₫</span>
        <div class="tgdd-price-old-wrap">
            <?php if($hasDiscount): ?>
                <span class="tgdd-price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                <span class="tgdd-price-percent">-<?php echo $pct; ?>%</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cụm Hành Động (Luôn hiển thị) -->
    <div class="tgdd-card-actions">
        <?php if(!$outOfStock): ?>
            <button type="button" class="tgdd-btn-add" onclick="addToCart('<?php echo $p['sku']; ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo $p['image_file']; ?>')">
                Thêm vào giỏ hàng
            </button>
        <?php else: ?>
            <button type="button" class="tgdd-btn-add" disabled style="opacity:0.5; cursor:not-allowed; color:#999; background:#eee;">
                Tạm hết hàng
            </button>
        <?php endif; ?>
    </div>
</div>