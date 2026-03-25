<div class="product-card">
    <div class="card-img">
        <a href="san-pham/<?php echo htmlspecialchars($p['slug']); ?>">
            <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
        </a>
        <?php if($p['sale_price'] > 0 && $p['price'] > $p['sale_price']): ?>
            <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
            <span class="discount-badge">-<?php echo $percent; ?>%</span>
        <?php endif; ?>
    </div>
    
    <a href="san-pham/<?php echo htmlspecialchars($p['slug']); ?>">
        <div class="card-title" title="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?></div>
    </a>
    
    <div class="price-box">
        <?php if($p['sale_price'] > 0): ?>
            <span class="price-new"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</span>
            <span class="price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
        <?php else: ?>
            <span class="price-new"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
        <?php endif; ?>
    </div>

    <button class="btn-buy"><i class="fas fa-cart-plus"></i> THÊM VÀO GIỎ</button>
    <span class="status-badge">Còn hàng</span>
</div>