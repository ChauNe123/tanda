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