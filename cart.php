<?php
require_once 'cores/db_config.php';
include 'includes/header.php';
?>

<main class="container" style="margin-top: 30px; margin-bottom: 60px; min-height: 60vh;">
    <div class="breadcrumb" style="margin-bottom: 25px; color: #666; font-size: 14px;">
        <a href="index.php" style="color: #ff5722; font-weight: bold;">Trang chủ</a> / <strong>Giỏ hàng của bạn</strong>
    </div>

    <div class="cart-layout" style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div class="cart-items-wrap" style="flex: 1; min-width: 60%; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h2 style="margin-bottom: 20px; font-size: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;">SẢN PHẨM TRONG GIỎ</h2>
            <div id="cart-render-area">
                </div>
        </div>

        <div class="cart-form-wrap" style="flex: 0 0 35%; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); align-self: flex-start;">
            <h2 style="margin-bottom: 20px; font-size: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;">THÔNG TIN ĐẶT HÀNG</h2>
            <form id="checkout-form">
                <input type="text" id="cusName" placeholder="Họ và tên của bạn" required style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
                <input type="tel" id="cusPhone" placeholder="Số điện thoại Zalo" required style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
                <input type="text" id="cusAddress" placeholder="Địa chỉ giao hàng / Lắp đặt" required style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
                <textarea id="cusNote" placeholder="Ghi chú thêm (Không bắt buộc)" style="width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; height: 80px;"></textarea>
                
                <div style="font-size: 18px; font-weight: bold; margin-bottom: 20px; display: flex; justify-content: space-between;">
                    <span>TỔNG CỘNG:</span>
                    <span id="cart-total-price" style="color: #d70018; font-size: 24px;">0đ</span>
                </div>

                <button type="button" onclick="generateZaloMessage()" style="width: 100%; padding: 15px; background: #0068ff; color: #fff; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px; transition: 0.3s;">
                    <i class="fas fa-paper-plane"></i> GỬI ĐƠN QUA ZALO
                </button>
            </form>
        </div>
    </div>
</main>

<div id="zaloModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: #fff; width: 90%; max-width: 500px; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative;">
        <i class="fas fa-times" onclick="closeModal()" style="position: absolute; top: 15px; right: 20px; font-size: 24px; color: #888; cursor: pointer;"></i>
        <h3 style="text-align: center; color: #0068ff; margin-bottom: 15px;"><i class="fas fa-check-circle"></i> TẠO ĐƠN THÀNH CÔNG!</h3>
        <p style="text-align: center; color: #555; margin-bottom: 20px;">Bạn vui lòng <b>Copy</b> nội dung bên dưới và gửi cho shop qua Zalo để được chốt đơn nhanh nhất nhé.</p>
        
        <textarea id="zaloMessageContent" readonly style="width: 100%; height: 200px; padding: 15px; border: 2px dashed #0068ff; border-radius: 8px; background: #f0f7ff; font-family: monospace; font-size: 14px; margin-bottom: 20px; resize: none;"></textarea>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="copyMessage()" style="flex: 1; padding: 12px; background: #ff5722; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px;">
                <i class="fas fa-copy"></i> COPY TIN NHẮN
            </button>
            <a href="https://zalo.me/0938440781" target="_blank" style="flex: 1; padding: 12px; background: #0068ff; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; text-decoration: none; text-align: center;">
                <i class="fas fa-external-link-alt"></i> MỞ ZALO NGAY
            </a>
        </div>
    </div>
</div>

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