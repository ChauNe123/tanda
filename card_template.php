<div class="product-card <?php echo ($p['status'] == 0) ? 'out-of-stock' : ''; ?>">
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
    
    <div class="price-box" style="position: relative; margin-bottom: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: flex-end;">
        <div>
            <?php if($p['sale_price'] > 0): ?>
                <span class="price-new" style="display: block; color: #d70018; font-size: 19px; font-weight: 800; margin-bottom: 2px;"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</span>
                <span class="price-old" style="display: block; color: #999; font-size: 13px; text-decoration: line-through;"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
            <?php else: ?>
                <span class="price-new" style="display: block; color: #d70018; font-size: 19px; font-weight: 800; margin-bottom: 2px;"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
            <?php endif; ?>
        </div>

        <?php if($p['status'] == 1): ?>
            <span style="position: absolute; right: 0; top: 0; background: #e8f5e9; color: #28a745; font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 4px;">Còn hàng</span>
        <?php else: ?>
            <span style="position: absolute; right: 0; top: 0; background: #ffebee; color: #d70018; font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 4px;">Tạm hết</span>
        <?php endif; ?>
    </div>

    <?php if($p['status'] == 1): ?>
        <button class="btn-buy" style="width: 100%; background: #fff; color: #ff5722; border: 2px solid #ff5722; padding: 9px 0; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;"><i class="fas fa-cart-plus"></i> CHỐT ĐƠN</button>
    <?php else: ?>
        <button class="btn-buy btn-disabled" disabled style="width: 100%; background: #f0f0f0; color: #888; border: 2px solid #ddd; padding: 9px 0; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: not-allowed; display: flex; justify-content: center; align-items: center; gap: 8px;"><i class="fas fa-phone-slash"></i> ĐẶT TRƯỚC</button>
    <?php endif; ?>
</div>