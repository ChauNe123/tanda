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