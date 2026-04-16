// Khai báo số Zalo tiếp nhận đơn hàng của công ty
const ZALO_PHONE = "0123456789"; 

function orderViaZalo(productName, price) {
    let formattedPrice = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    let message = `Chào bộ phận kinh doanh KB Tech, mình muốn tư vấn mua sản phẩm:\n\n👉 Tên SP: ${productName}\n💰 Giá tham khảo: ${formattedPrice}\n\nNhờ shop báo giá và lên lịch lắp đặt giúp mình nhé!`;
    let encodedMessage = encodeURIComponent(message);
    window.open(`https://zalo.me/${ZALO_PHONE}?text=${encodedMessage}`, '_blank');
}

/* ================= LOGIC GIỎ HÀNG & POP-UP TỰ BIẾN MẤT 1.5s ================= */
function addToCart(sku, name, price, image) {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let existingItem = cart.find(item => item.sku === sku);
    
    if (existingItem) {
        existingItem.qty += 1;
    } else {
        cart.push({ sku: sku, name: name, price: price, image: image, qty: 1 });
    }
    
    localStorage.setItem('tanda_cart', JSON.stringify(cart));
    updateCartBadge();
    
    // --- TẠO/HIỆN POPUP (markup mới, style nhẹ) ---
    let notifyEl = document.getElementById('cart-notification');

    if (!notifyEl) {
        if (!document.getElementById('tanda-cart-notify-styles')) {
            const css = `
#cart-notification { position: fixed; right: 20px; bottom: 20px; z-index: 10000; display: none; }
#cart-notification .tanda-cart-notify__box { background: var(--primary-orange); color: #fff; display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.12); min-width:240px; max-width:360px; }
#cart-notification .tanda-cart-notify__icon { width:40px; height:40px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.2); border-radius:8px; }
#cart-notification .tanda-cart-notify__title { font-weight:700; font-size:14px; }
#cart-notification .tanda-cart-notify__product { font-size:13px; opacity:0.95; margin-top:2px; }
#cart-notification .tanda-cart-notify__close { border: none; background: transparent; color: #fff; font-size:18px; cursor:pointer; margin-left:8px; }
@media (max-width:480px) { #cart-notification { right:12px; left:12px; bottom:12px; } }
    `;
            const style = document.createElement('style');
            style.id = 'tanda-cart-notify-styles';
            style.appendChild(document.createTextNode(css));
            document.head.appendChild(style);
        }

        const popupHTML = `
<div id="cart-notification" class="tanda-cart-notify" aria-live="polite">
  <div class="tanda-cart-notify__box" role="status" aria-atomic="true">
    <div class="tanda-cart-notify__icon" aria-hidden="true">
      <i class="fas fa-check" style="color: #fff; font-size: 20px;"></i>
    </div>
    <div class="tanda-cart-notify__body">
      <div class="tanda-cart-notify__title">✓ Đã thêm vào giỏ hàng</div>
      <div id="added-product-name" class="tanda-cart-notify__product"></div>
    </div>
    <button type="button" class="tanda-cart-notify__close" aria-label="Đóng">&times;</button>
  </div>
</div>`;
        document.body.insertAdjacentHTML('beforeend', popupHTML);
        notifyEl = document.getElementById('cart-notification');
        const closeBtn = notifyEl.querySelector('.tanda-cart-notify__close');
        if (closeBtn) closeBtn.addEventListener('click', closeCartNotify);
    }

    const nameEl = document.getElementById('added-product-name');
    if (notifyEl && nameEl) {
        nameEl.innerText = name;
        notifyEl.style.display = 'flex';
        setTimeout(() => { if (notifyEl) notifyEl.style.display = 'none'; }, 1500);
    }
}

function updateCartBadge() {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    
    // Update new cart-box badge
    let cartBox = document.querySelector('.cart-box span');
    if (cartBox) cartBox.innerText = `Giỏ hàng (${totalQty})`;
    
    // Update legacy badge (if exists)
    let badge = document.querySelector('.tgdd-action-btn .count');
    if (badge) badge.innerText = totalQty;
}

function closeCartNotify() {
    let notifyEl = document.getElementById('cart-notification');
    if (notifyEl) notifyEl.style.display = 'none';
}

/* ================= SCROLL REVEAL ANIMATION ================= */
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                revealObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => {
        revealObserver.observe(el);
    });

    // Initialize cart badge on page load
    updateCartBadge();
});
