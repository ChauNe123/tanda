// assets/js/script.js

// Khai báo số Zalo tiếp nhận đơn hàng của công ty
const ZALO_PHONE = "0123456789"; 

function orderViaZalo(productName, price) {
    // Định dạng lại giá tiền cho đẹp (VD: 1500000 -> 1.500.000)
    let formattedPrice = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    
    // Soạn sẵn nội dung tin nhắn
    let message = `Chào bộ phận kinh doanh KB Tech, mình muốn tư vấn mua sản phẩm:\n\n`;
    message += `👉 Tên SP: ${productName}\n`;
    message += `💰 Giá tham khảo: ${formattedPrice}\n\n`;
    message += `Nhờ shop báo giá và lên lịch lắp đặt giúp mình nhé!`;

    // Mã hóa tin nhắn thành định dạng URL
    let encodedMessage = encodeURIComponent(message);

    // Tạo link mở app Zalo (hoạt động cả trên Mobile và PC)
    let zaloLink = `https://zalo.me/${ZALO_PHONE}?text=${encodedMessage}`;

    // Mở Zalo trong tab mới
    window.open(zaloLink, '_blank');
}
document.addEventListener("DOMContentLoaded", function() {
    const header = document.querySelector('.main-header');
    let lastScrollTop = 0;
    
    // Khoảng cách bắt đầu tính hiệu ứng (qua khỏi top banner)
    const scrollThreshold = 100; 

    if (header) {
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > scrollThreshold) {
                // Thêm class sticky và đệm cho body
                header.classList.add('sticky');
                document.body.classList.add('has-sticky-header');
                
                // Xác định chiều lăn chuột
                if (scrollTop > lastScrollTop) {
                    // Lăn CẮM XUỐNG DƯỚI -> Ẩn menu để xem SP
                    header.classList.add('hidden');
                } else {
                    // Lăn NGƯỢC LÊN TRÊN -> Hiện menu lại để tìm đồ khác
                    header.classList.remove('hidden');
                }
            } else {
                // Về sát mép trên cùng -> Trả lại như cũ
                header.classList.remove('sticky');
                header.classList.remove('hidden');
                document.body.classList.remove('has-sticky-header');
            }
            
            lastScrollTop = scrollTop;
        });
    }
});

/* ================= LOGIC GIỎ HÀNG (LOCALSTORAGE) ================= */
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
    
    // Tạo thông báo xịn sò góc màn hình (Toast)
    alert('✅ Đã thêm "' + name + '" vào giỏ hàng!'); 
}

function updateCartBadge() {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    let badge = document.querySelector('.cart-box .count');
    if (badge) badge.innerText = '(' + totalQty + ')';
}

// Chạy hàm đếm số lượng ngay khi load web
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