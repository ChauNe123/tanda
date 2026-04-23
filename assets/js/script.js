// Hàm format tiền
const formatMoney = (num) => new Intl.NumberFormat('vi-VN').format(num) + '₫';

// === GIỎ HÀNG (LocalStorage) ===
function updateCart() {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let badge = document.getElementById('cartBadge');
    if (badge) badge.innerText = cart.reduce((sum, i) => sum + i.qty, 0);
}

// Thêm vào giỏ (Nhận đủ thông tin để hiển thị bên trang cart.php)
function addToCart(sku, name, price, image) {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let item = cart.find(i => i.sku === sku);
    
    if (item) {
        item.qty++;
    } else {
        cart.push({
            sku: sku, 
            name: name, 
            price: price, 
            image: image, 
            qty: 1
        });
    }
    
    localStorage.setItem('tanda_cart', JSON.stringify(cart));
    updateCart();
    
    // Bật Dialog thông báo thành công
    let modal = document.getElementById('addToCartModal');
    let nameEl = document.getElementById('addedProductName');
    if (modal && nameEl) {
        nameEl.innerText = name;
        modal.style.display = 'flex';
    }
}

// Mở Zalo để tư vấn/mua ngay không thông qua giỏ hàng (Áp dụng cho trang chi tiết)
function orderViaZalo(productName, price) {
    let msg = `Chào shop, mình cần tư vấn/đặt hàng sản phẩm:\n- ${productName}\n- Giá: ${formatMoney(price)}\n(Từ website TANDA)`;
    let encodedMsg = encodeURIComponent(msg);
    // Thay số điện thoại Zalo của shop tại đây
    window.location.href = `https://zalo.me/0938440781?text=${encodedMsg}`;
}

// === OBSERVER ANIMATION (Cuộn đến đâu hiện ra đến đó) ===
function initScrollAnim() {
    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(e.isIntersecting) { e.target.classList.add('show'); obs.unobserve(e.target); }
        });
    }, {threshold: 0.1});
    document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));
}

// === HEADER SCROLL SHRINK ===
function initStickyHeader() {
    const header = document.querySelector('.tgdd-header');
    if (!header) return;
    const threshold = 80;

    const onScroll = () => {
        if (window.scrollY > threshold) {
            header.classList.add('shrink');
        } else {
            header.classList.remove('shrink');
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

// === INIT KHI LOAD XONG ===
window.addEventListener('DOMContentLoaded', () => {
    updateCart();
    initScrollAnim();
    initStickyHeader();
});