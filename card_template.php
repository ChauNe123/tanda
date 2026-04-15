<?php
// card_template.php
?>
<div class="tgdd-card <?php echo ($p['status'] == 0) ? 'out-of-stock' : ''; ?>">
    <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>">
        <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="tgdd-card-img">
    </a>
    
    <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" class="tgdd-card-title" title="<?php echo htmlspecialchars($p['name']); ?>">
        <?php echo htmlspecialchars($p['name']); ?>
    </a>
    
    <div class="price-box">
        <?php if($p['sale_price'] > 0): ?>
            <span class="price-new"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>₫</span>
            <?php if($p['price'] > $p['sale_price']): ?>
                <span class="price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
                <span class="discount-badge">-<?php echo $percent; ?>%</span>
            <?php endif; ?>
        <?php else: ?>
            <span class="price-new"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
        <?php endif; ?>
    </div>

    <?php if($p['sale_price'] > 0): ?>
    <div class="fs-progress">
        <div class="fs-progress-bar" style="width: <?php echo rand(30, 90); ?>%;"></div>
        <text><i class="fas fa-fire"></i> Còn <?php echo rand(2, 18); ?>/20 suất</text>
    </div>
    <?php endif; ?>

    <?php if($p['status'] == 1): ?>
        <?php $chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price']; ?>
        <button type="button" class="btn-mua-ngay" onclick="addToCart('<?php echo $p['sku']; ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo $p['image_file']; ?>')">
            Mua ngay
        </button>
    <?php else: ?>
        <button type="button" class="btn-mua-ngay" style="background: #e0e0e0; color: #888; border-color: #ccc; cursor: not-allowed;" disabled>
            Tạm hết hàng
        </button>
    <?php endif; ?>
</div>