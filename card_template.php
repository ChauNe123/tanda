<div class="product-card <?php echo ($p['status'] == 0) ? 'out-of-stock' : ''; ?>">
    <div class="card-img">
        <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>">
            <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
        </a>
        <?php if($p['sale_price'] > 0 && $p['price'] > $p['sale_price']): ?>
            <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
            <span class="discount-badge">-<?php echo $percent; ?>%</span>
        <?php endif; ?>
    </div>
    
    <div class="card-info">
        <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" class="card-title" title="<?php echo htmlspecialchars($p['name']); ?>">
            <?php echo htmlspecialchars($p['name']); ?>
        </a>
        
        <div class="card-price">
            <?php if($p['sale_price'] > 0): ?>
                <strong class="price-new"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>₫</strong>
                <span class="price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
            <?php else: ?>
                <strong class="price-new"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</strong>
            <?php endif; ?>
        </div>

        <?php if($p['status'] == 1): ?>
            <?php $chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price']; ?>
            <button type="button" class="btn-buy" onclick="addToCart('<?php echo $p['sku']; ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo $p['image_file']; ?>')">
                Thêm vào giỏ
            </button>
        <?php else: ?>
            <button type="button" class="btn-disabled" disabled>Tạm hết hàng</button>
        <?php endif; ?>
    </div>
</div>