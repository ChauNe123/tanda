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
    const scrollThreshold = 150; 

    if (header) {
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > scrollThreshold) {
                // Thêm class sticky và đệm cho body
                header.classList.add('sticky');
                document.body.classList.add('has-sticky-header');
                
                // Xác định chiều lăn chuột
                if (scrollTop > lastScrollTop) {
                    // 1. Lăn CẮM XUỐNG DƯỚI -> Hiện menu đi theo
                    header.classList.remove('hidden');
                } else if (scrollTop < lastScrollTop - 5) { 
                    // 2. Lăn NGƯỢC LÊN TRÊN (nhích nhẹ 5px) -> Giấu menu đi ngay lập tức
                    header.classList.add('hidden');
                }
            } else {
                // Về sát mép trên cùng -> Trả lại như cũ
                header.classList.remove('sticky');
                header.classList.remove('hidden');
                document.body.classList.remove('has-sticky-header');
            }
            
            // Cập nhật vị trí cuộn
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });
    }
});

function updateCartBadge() {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    // Tìm đúng ID em vừa đặt ở Bước 1
    let badge = document.getElementById('cart-count-display');
    if (badge) {
        badge.innerText = '(' + totalQty + ')';
    }
}

// Gọi hàm này ngay khi trang web vừa tải xong
document.addEventListener("DOMContentLoaded", function() {
    updateCartBadge();
});