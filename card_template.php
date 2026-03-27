<style>
    /* CSS cho thẻ sản phẩm (Giữ nguyên form cũ đẹp của bạn) */
    .product-card { background: var(--white-bg); border: 1px solid #eee; border-radius: 6px; padding: 12px; position: relative; transition: all 0.3s ease; display: flex; flex-direction: column; min-width: 220px; max-width: 220px; flex: 0 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .product-card:hover { border-color: var(--orange-brand); box-shadow: 0 8px 25px rgba(0,0,0,0.12); transform: translateY(-5px); z-index: 2; }
    
    /* Ảnh luôn sắc nét, căn ngang, kéo dài chân ảnh */
    .card-img { width: 100%; height: 190px; position: relative; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; overflow: hidden; background: #fff; padding-bottom: 10px; }
    .card-img img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.4s ease; image-rendering: -webkit-optimize-contrast; }
    .product-card:hover .card-img img { transform: scale(1.12); } 
    
    .discount-badge { position: absolute; top: 0; left: 0; background: var(--orange-brand); color: #fff; font-size: 12px; font-weight: bold; padding: 4px 8px; border-radius: 4px; z-index: 3; clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%); }

    .card-title { font-size: 14px; color: var(--black-text); line-height: 1.4; margin-bottom: 8px; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; text-align: left; }
    .product-card:hover .card-title { color: var(--orange-brand); }

    .price-box { margin-bottom: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: flex-end; }
    .price-new { display: block; color: var(--danger-color, #d70018); font-size: 19px; font-weight: 800; margin-bottom: 2px; }
    .price-old { display: block; color: #999; font-size: 13px; text-decoration: line-through; }

    .btn-buy { width: 100%; background: #fff; color: var(--orange-brand); border: 2px solid var(--orange-brand); padding: 9px 0; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.2s ease; display: flex; justify-content: center; align-items: center; gap: 8px; text-transform: uppercase; }
    .product-card:hover .btn-buy:not(.btn-disabled) { background: var(--orange-brand); color: #fff; }
    .btn-buy:active:not(.btn-disabled) { transform: scale(0.95); }

    .status-badge { position: absolute; bottom: 22px; right: 12px; font-size: 11px; font-weight: 600; pointer-events: none; }

    /* --- GIAO DIỆN KHI HẾT HÀNG (STATUS = 0) --- */
    .out-of-stock { border-color: #e0e0e0; }
    .out-of-stock .card-img img { opacity: 0.5; filter: grayscale(80%); } /* Làm mờ ảnh */
    .out-of-stock:hover .card-img img { transform: none; } /* Tắt hiệu ứng zoom nếu hết hàng */
    .btn-disabled { background: #f0f0f0 !important; color: #888 !important; border-color: #ddd !important; cursor: not-allowed; }
</style>

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
    
    <div class="price-box">
        <?php if($p['sale_price'] > 0): ?>
            <span class="price-new"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</span>
            <span class="price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
        <?php else: ?>
            <span class="price-new"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
        <?php endif; ?>
    </div>

    <?php if($p['status'] == 1): ?>
        <button class="btn-buy"><i class="fas fa-cart-plus"></i> CHỐT ĐƠN</button>
        <span class="status-badge" style="color: #28a745;">Còn hàng</span>
    <?php else: ?>
        <button class="btn-buy btn-disabled" disabled><i class="fas fa-phone-slash"></i> ĐẶT TRƯỚC</button>
        <span class="status-badge" style="color: #d70018;">Tạm hết hàng</span>
    <?php endif; ?>
</div>