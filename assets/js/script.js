// Khai báo số Zalo tiếp nhận đơn hàng của công ty
const ZALO_PHONE = "0123456789"; 

function orderViaZalo(productName, price) {
    let formattedPrice = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    let message = `Chào bộ phận kinh doanh KB Tech, mình muốn tư vấn mua sản phẩm:\n\n👉 Tên SP: ${productName}\n💰 Giá tham khảo: ${formattedPrice}\n\nNhờ shop báo giá và lên lịch lắp đặt giúp mình nhé!`;
    let encodedMessage = encodeURIComponent(message);
    window.open(`https://zalo.me/${ZALO_PHONE}?text=${encodedMessage}`, '_blank');
}

// Sticky header behavior removed (handled by new header/menu CSS/JS).

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
    updateCartBadge(); // Cho số giỏ hàng nhảy lên
    
    // --- TẠO/HIỆN POPUP (markup mới, style nhẹ) ---
    let notifyEl = document.getElementById('cart-notification');

    if (!notifyEl) {
        if (!document.getElementById('tanda-cart-notify-styles')) {
            const css = `
#cart-notification { position: fixed; right: 20px; bottom: 20px; z-index: 10000; display: none; }
#cart-notification .tanda-cart-notify__box { background: #27ae60; color: #fff; display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.12); min-width:240px; max-width:360px; }
#cart-notification .tanda-cart-notify__icon { width:40px; height:40px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.12); border-radius:8px; }
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
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="tanda-cart-notify__body">
      <div class="tanda-cart-notify__title">Đã thêm vào giỏ hàng</div>
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
    let badge = document.querySelector('.tgdd-action-btn .count') || document.querySelector('.cart-box .count');
    if (badge) badge.innerText = '(' + totalQty + ')';
}

function closeCartNotify() {
    let notifyEl = document.getElementById('cart-notification');
    if (notifyEl) notifyEl.style.display = 'none';
}

let cartButtons = document.querySelectorAll('.tgdd-action-btn, .cart-box');
