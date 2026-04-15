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
    updateCartBadge(); // Cho số giỏ hàng nhảy lên
    
    // --- TỰ TẠO HTML POPUP NẾU THIẾU ---
    let notifyEl = document.getElementById('cart-notification');
    
    if (!notifyEl) {
        const popupHTML = `
        <div id="cart-notification" class="cart-msg-overlay" style="display: none;">
            <div class="cart-msg-box" style="padding-bottom: 20px;">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
                <div class="cart-msg-content">
                    <h4>Thêm vào giỏ hàng thành công!</h4>
                    <p id="added-product-name" style="margin-bottom: 0; color: #d70018; font-weight: bold;"></p>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', popupHTML);
        notifyEl = document.getElementById('cart-notification'); 
    }
    
    // --- GỌI POP-UP HIỆN RA RỒI TỰ TẮT ---
    let nameEl = document.getElementById('added-product-name');
    if (notifyEl && nameEl) {
        notifyEl.style.display = 'none'; // Tắt đi trước để reset animation
        setTimeout(() => {
            nameEl.innerText = name; 
            notifyEl.style.display = 'flex'; // Hiện Pop-up
            
            // ĐỒNG HỒ ĐẾM NGƯỢC: Đúng 1.5 giây (1500ms) là tự động giấu đi
            setTimeout(() => {
                notifyEl.style.display = 'none';
            }, 1500);
            
        }, 10);
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

document.addEventListener("DOMContentLoaded", function() {
    // 1. CHỨC NĂNG ẨN/HIỆN MENU KHI SCROLL (HỤT VÔ 1 PHẦN)
    const header = document.getElementById('mainHeader');
    let lastScrollTop = 0;
    const delta = 10; // Cần cuộn ít nhất 10px mới kích hoạt để tránh giật lag

    window.addEventListener('scroll', function() {
        // Lấy vị trí cuộn hiện tại
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Nếu cuộn chưa qua 10px thì bỏ qua
        if (Math.abs(lastScrollTop - scrollTop) <= delta) return;

        // Kiểm tra xem đã cuộn qua 65px (chiều cao của phần nền trắng) chưa
        if (scrollTop > 65) {
            if (scrollTop > lastScrollTop) {
                // Đang cuộn xuống -> Ẩn phần trắng (bơm class hide-top vào)
                header.classList.add('hide-top');
            } else {
                // Đang vuốt lên -> Hiện lại phần trắng
                header.classList.remove('hide-top');
            }
        } else {
            // Đang ở tuốt trên đỉnh trang -> Trả về trạng thái gốc
            header.classList.remove('hide-top');
        }

        lastScrollTop = scrollTop;
    });

    // 2. TỰ ĐỘNG CẬP NHẬT SỐ LƯỢNG GIỎ HÀNG
    updateCartBadge();
});

function updateCartBadge() {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    let badge = document.querySelector('.action-btn.cart .count');
    if (badge) badge.innerText = '(' + totalQty + ')';
}

// Giữ lại hàm đặt hàng qua Zalo của bạn
function orderViaZalo(productName, price) {
    let formattedPrice = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    let message = `Chào bộ phận kinh doanh KB Tech, mình muốn mua:\n\n👉 ${productName}\n💰 Giá: ${formattedPrice}`;
    window.open(`https://zalo.me/0123456789?text=${encodeURIComponent(message)}`, '_blank');
}