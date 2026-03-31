// Khai báo số Zalo tiếp nhận đơn hàng của công ty
const ZALO_PHONE = "0123456789"; 

function orderViaZalo(productName, price) {
    let formattedPrice = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    let message = `Chào bộ phận kinh doanh KB Tech, mình muốn tư vấn mua sản phẩm:\n\n👉 Tên SP: ${productName}\n💰 Giá tham khảo: ${formattedPrice}\n\nNhờ shop báo giá và lên lịch lắp đặt giúp mình nhé!`;
    let encodedMessage = encodeURIComponent(message);
    window.open(`https://zalo.me/${ZALO_PHONE}?text=${encodedMessage}`, '_blank');
}

// Xử lý hiệu ứng Sticky Header
document.addEventListener("DOMContentLoaded", function() {
    const header = document.querySelector('.main-header');
    let lastScrollTop = 0;
    const scrollThreshold = 100; 

    if (header) {
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > scrollThreshold) {
                header.classList.add('sticky');
                document.body.classList.add('has-sticky-header');
                if (scrollTop > lastScrollTop) {
                    header.classList.add('hidden');
                } else {
                    header.classList.remove('hidden');
                }
            } else {
                header.classList.remove('sticky', 'hidden');
                document.body.classList.remove('has-sticky-header');
            }
            lastScrollTop = scrollTop;
        });
    }
});

/* ================= LOGIC GIỎ HÀNG (LOCALSTORAGE) & POP-UP ================= */
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
    
    // BẬT POPUP THÔNG BÁO (THAY VÌ DÙNG ALERT)
    let notifyEl = document.getElementById('cart-notification');
    let nameEl = document.getElementById('added-product-name');
    
    if (notifyEl && nameEl) {
        nameEl.innerText = name;
        notifyEl.style.display = 'flex'; // Hiển thị popup
        
        // Tự động đóng popup sau 4 giây
        setTimeout(closeCartNotify, 4000);
    } else {
        // Dự phòng nếu lỗi HTML
        alert('✅ Đã thêm "' + name + '" vào giỏ hàng!');
    }
}

function updateCartBadge() {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    let badge = document.querySelector('.cart-box .count');
    if (badge) badge.innerText = '(' + totalQty + ')';
}

function closeCartNotify() {
    let notifyEl = document.getElementById('cart-notification');
    if (notifyEl) notifyEl.style.display = 'none';
}

document.addEventListener("DOMContentLoaded", function() {
    updateCartBadge();
});

/* ================= ÉP CHUYỂN TRANG GIỎ HÀNG CHO TOÀN WEB ================= */
document.addEventListener("DOMContentLoaded", function() {
    // Tìm TẤT CẢ các nút giỏ hàng trên mọi trang
    let cartButtons = document.querySelectorAll('.cart-box');
    
    cartButtons.forEach(function(btn) {
        // Đổi con trỏ chuột thành hình bàn tay cho khách biết là bấm được
        btn.style.cursor = 'pointer'; 
        
        // Ép lệnh click chuyển trang
        btn.addEventListener('click', function() {
            window.location.href = 'cart.php';
        });
    });
});

