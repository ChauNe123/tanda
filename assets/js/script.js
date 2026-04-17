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
    
    // Bật Popup dấu tick xanh báo thành công
    let popup = document.getElementById('cartPopup');
    if (popup) {
        popup.classList.remove('show');
        void popup.offsetWidth; // Force reflow
        popup.classList.add('show');
        setTimeout(() => popup.classList.remove('show'), 1500);
    }
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

// === INIT KHI LOAD XONG ===
window.addEventListener('DOMContentLoaded', () => {
    updateCart();
    initScrollAnim();
});