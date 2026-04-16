<?php
require_once 'cores/db_config.php';
include 'includes/header.php';
?>

<div class="container">
    <div class="cart-wrap">
        <div class="cart-header">
            <a href="index.php"><i class="fas fa-chevron-left"></i> Tiếp tục mua sắm</a>
            <span>Giỏ hàng của bạn</span>
        </div>

        <div id="cart-render-area"></div>

        <div id="checkout-form-area" style="display: none;">
            <div class="cart-total-area">
                <span>Tạm tính:</span>
                <span class="cart-total-price" id="cart-total-price">0đ</span>
            </div>

            <div class="cart-form">
                <h4 style="margin-bottom: 15px; font-size: 15px;">Thông tin khách hàng</h4>
                <input type="text" id="cusName" placeholder="Họ và Tên (bắt buộc)" required>
                <input type="text" id="cusPhone" placeholder="Số điện thoại (bắt buộc)" required>
                <input type="text" id="cusAddress" placeholder="Địa chỉ giao hàng (bắt buộc)" required>
                <textarea id="cusNote" rows="2" placeholder="Ghi chú thêm (nếu có)"></textarea>
            </div>

            <button type="button" class="btn-buy-now" style="width: 100%; border-radius: 4px;" onclick="generateZaloMessage()">
                ĐẶT HÀNG NGAY
                <span>Shop sẽ liên hệ qua Zalo/SĐT để xác nhận</span>
            </button>
        </div>
    </div>
</div>

<div id="zaloModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px; text-align: center;">
        <h3 style="color: #0068ff; margin-bottom: 10px;"><i class="fas fa-check-circle"></i> Đơn hàng đã sẵn sàng!</h3>
        <p style="font-size: 14px; color: #555; margin-bottom: 15px;">Vui lòng copy tin nhắn bên dưới và gửi qua Zalo để nhân viên KB Tech xử lý nhanh nhất nhé.</p>
        <textarea id="zaloMessageContent" style="width: 100%; height: 150px; font-size: 13px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;" readonly></textarea>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="copyMessage()" style="flex: 1; padding: 10px; background: #e0e0e0; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">📋 Copy Tin Nhắn</button>
            <button onclick="window.open('https://zalo.me/0123456789', '_blank')" style="flex: 1; padding: 10px; background: #0068ff; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Mở Zalo Ngay 🚀</button>
        </div>
        <button onclick="closeModal()" style="margin-top: 15px; background: none; border: none; color: #999; text-decoration: underline; cursor: pointer;">Đóng lại</button>
    </div>
</div>

<script>
    function renderCartPage() {
        let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
        let area = document.getElementById('cart-render-area');
        let totalEl = document.getElementById('cart-total-price');
        let formArea = document.getElementById('checkout-form-area');
        
        if(cart.length === 0) {
            area.innerHTML = '<div style="text-align: center; padding: 40px 0; color: #888;"><i class="fas fa-shopping-cart" style="font-size: 40px; color: #ddd; margin-bottom: 10px;"></i><p>Không có sản phẩm nào trong giỏ hàng</p><a href="index.php" style="display:inline-block; margin-top:15px; padding:10px 20px; border:1px solid var(--primary-orange); color:var(--primary-orange); border-radius:4px; text-decoration:none;">Về trang chủ</a></div>';
            formArea.style.display = 'none';
            return;
        }

        let html = '';
        let total = 0;

        cart.forEach((item, index) => {
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            html += `
                <div class="cart-item">
                    <img src="uploads/${item.image}" class="cart-item-img">
                    <div class="cart-item-info">
                        <a href="#" class="cart-item-title">${item.name}</a>
                        <div class="cart-item-price">${item.price.toLocaleString('vi-VN')}đ</div>
                        <div class="cart-qty-ctrl">
                            <button onclick="changeQty(${index}, -1)">-</button>
                            <input type="text" readonly value="${item.qty}">
                            <button onclick="changeQty(${index}, 1)">+</button>
                        </div>
                    </div>
                    <button class="cart-btn-del" onclick="removeItem(${index})" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                </div>
            `;
        });
        
        area.innerHTML = html;
        totalEl.innerText = total.toLocaleString('vi-VN') + 'đ';
        formArea.style.display = 'block';
    }

    function changeQty(index, amount) {
        let cart = JSON.parse(localStorage.getItem('tanda_cart'));
        cart[index].qty += amount;
        if(cart[index].qty <= 0) cart.splice(index, 1);
        localStorage.setItem('tanda_cart', JSON.stringify(cart));
        renderCartPage();
        updateCartBadge();
    }

    function removeItem(index) {
        let cart = JSON.parse(localStorage.getItem('tanda_cart'));
        cart.splice(index, 1);
        localStorage.setItem('tanda_cart', JSON.stringify(cart));
        renderCartPage();
        updateCartBadge();
    }

    document.addEventListener("DOMContentLoaded", renderCartPage);

    function generateZaloMessage() {
        let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
        if(cart.length === 0) return alert('Giỏ hàng trống!');
        
        let name = document.getElementById('cusName').value.trim();
        let phone = document.getElementById('cusPhone').value.trim();
        let address = document.getElementById('cusAddress').value.trim();
        let note = document.getElementById('cusNote').value.trim();

        if(!name || !phone || !address) {
            alert('Vui lòng điền đủ Họ Tên, SĐT và Địa chỉ!'); return;
        }

        let total = 0;
        let msg = `🛒 ĐƠN HÀNG TỪ WEB TANDA:\n`;
        msg += `👤 Khách hàng: ${name}\n📞 SĐT: ${phone}\n📍 Địa chỉ: ${address}\n`;
        if(note) msg += `📝 Ghi chú: ${note}\n`;
        msg += `\n📦 SẢN PHẨM:\n`;
        
        cart.forEach(item => {
            total += item.price * item.qty;
            msg += `- ${item.qty}x ${item.name} (${item.price.toLocaleString('vi-VN')}đ)\n`;
        });
        msg += `💰 TỔNG TIỀN: ${total.toLocaleString('vi-VN')}đ\n`;

        document.getElementById('zaloMessageContent').value = msg;
        document.getElementById('zaloModal').style.display = 'flex';
    }

    function closeModal() { document.getElementById('zaloModal').style.display = 'none'; }
    function copyMessage() {
        let text = document.getElementById('zaloMessageContent');
        text.select(); document.execCommand('copy');
        alert('✅ Đã copy! Hãy bấm "Mở Zalo Ngay" và Dán để gửi shop nhé.');
    }
</script>

<?php include 'includes/footer.php'; ?>