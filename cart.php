<?php
require_once 'cores/db_config.php';
include 'includes/header.php';
?>


<script>
    // Hàm render Giỏ hàng
    function renderCartPage() {
        let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
        let area = document.getElementById('cart-render-area');
        let totalEl = document.getElementById('cart-total-price');
        
        if(cart.length === 0) {
            area.innerHTML = '<div style="text-align: center; padding: 40px 0; color: #888;"><i class="fas fa-shopping-basket" style="font-size: 50px; margin-bottom: 15px; color: #ddd;"></i><p>Giỏ hàng của bạn đang trống.</p><a href="index.php" style="color: #ff5722; font-weight: bold; margin-top:10px; display:inline-block;">Tiếp tục mua sắm</a></div>';
            totalEl.innerText = '0đ';
            return;
        }

        let html = '';
        let total = 0;

        cart.forEach((item, index) => {
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            html += `
                <div style="display: flex; align-items: center; border-bottom: 1px solid #eee; padding: 15px 0; gap: 15px;">
                    <img src="uploads/${item.image}" style="width: 80px; height: 80px; object-fit: contain; border: 1px solid #eee; border-radius: 6px;">
                    <div style="flex: 1;">
                        <h4 style="font-size: 15px; margin-bottom: 5px; color: #333;">${item.name}</h4>
                        <div style="color: #d70018; font-weight: bold;">${item.price.toLocaleString('vi-VN')}đ</div>
                    </div>
                    <div style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px;">
                        <button onclick="changeQty(${index}, -1)" style="padding: 5px 12px; background: #f9f9f9; border: none; border-right: 1px solid #ddd; cursor: pointer;">-</button>
                        <input type="text" readonly value="${item.qty}" style="width: 40px; text-align: center; border: none; outline: none; font-weight: bold;">
                        <button onclick="changeQty(${index}, 1)" style="padding: 5px 12px; background: #f9f9f9; border: none; border-left: 1px solid #ddd; cursor: pointer;">+</button>
                    </div>
                    <div style="font-weight: bold; color: #d70018; width: 100px; text-align: right;">
                        ${itemTotal.toLocaleString('vi-VN')}đ
                    </div>
                    <button onclick="removeItem(${index})" style="background: none; border: none; color: #aaa; cursor: pointer; font-size: 18px;" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                </div>
            `;
        });
        
        area.innerHTML = html;
        totalEl.innerText = total.toLocaleString('vi-VN') + 'đ';
    }

    // Tăng giảm số lượng
    function changeQty(index, amount) {
        let cart = JSON.parse(localStorage.getItem('tanda_cart'));
        cart[index].qty += amount;
        if(cart[index].qty <= 0) cart.splice(index, 1);
        localStorage.setItem('tanda_cart', JSON.stringify(cart));
        renderCartPage();
        updateCartBadge();
    }

    // Xóa sản phẩm
    function removeItem(index) {
        let cart = JSON.parse(localStorage.getItem('tanda_cart'));
        cart.splice(index, 1);
        localStorage.setItem('tanda_cart', JSON.stringify(cart));
        renderCartPage();
        updateCartBadge();
    }

    // Render ngay khi load file cart.php
    document.addEventListener("DOMContentLoaded", renderCartPage);

    // XỬ LÝ NÚT GỬI ZALO (TẠO TIN NHẮN)
    function generateZaloMessage() {
        let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
        if(cart.length === 0) return alert('Giỏ hàng trống!');
        
        let name = document.getElementById('cusName').value.trim();
        let phone = document.getElementById('cusPhone').value.trim();
        let address = document.getElementById('cusAddress').value.trim();
        let note = document.getElementById('cusNote').value.trim();

        if(!name || !phone || !address) {
            alert('Vui lòng điền đủ Họ Tên, SĐT và Địa chỉ!');
            return;
        }

        let total = 0;
        let msg = `🛒 ĐƠN HÀNG TỪ WEB TANDA:\n`;
        msg += `---------------------------\n`;
        msg += `👤 Khách hàng: ${name}\n`;
        msg += `📞 Số điện thoại: ${phone}\n`;
        msg += `📍 Địa chỉ: ${address}\n`;
        if(note) msg += `📝 Ghi chú: ${note}\n`;
        msg += `\n📦 DANH SÁCH SẢN PHẨM:\n`;
        
        cart.forEach(item => {
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            msg += `- ${item.qty}x ${item.name} (${item.price.toLocaleString('vi-VN')}đ)\n`;
        });
        
        msg += `---------------------------\n`;
        msg += `💰 TỔNG TIỀN: ${total.toLocaleString('vi-VN')}đ\n`;

        document.getElementById('zaloMessageContent').value = msg;
        document.getElementById('zaloModal').style.display = 'flex';
    }

    // Đóng Modal
    function closeModal() {
        document.getElementById('zaloModal').style.display = 'none';
    }

    // Copy nội dung
    function copyMessage() {
        let text = document.getElementById('zaloMessageContent');
        text.select();
        document.execCommand('copy');
        alert('✅ Đã copy tin nhắn! Hãy bấm "Mở Zalo Ngay" và Dán (Paste) để gửi cho shop nhé.');
    }
</script>

<?php include 'includes/footer.php'; ?>