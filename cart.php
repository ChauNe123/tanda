<?php
require_once 'cores/db_config.php';
include 'includes/header.php';
?>

<main class="container cart-page-container">
    <div class="breadcrumb">
        <a href="index.php" class="home-link">Trang chủ</a> / <strong>Giỏ hàng của bạn</strong>
    </div>

    <div class="cart-layout">
        <div class="cart-items-wrap">
            <h2 class="cart-box-title">SẢN PHẨM TRONG GIỎ</h2>
            <div id="cart-render-area">
                </div>
        </div>

        <div class="cart-form-wrap">
            <h2 class="cart-box-title">THÔNG TIN ĐẶT HÀNG</h2>
            <form id="checkout-form">
                <input type="text" id="cusName" class="form-input" placeholder="Họ và tên của bạn" required>
                <input type="tel" id="cusPhone" class="form-input" placeholder="Số điện thoại Zalo" required>
                <input type="text" id="cusAddress" class="form-input" placeholder="Địa chỉ giao hàng / Lắp đặt" required>
                <textarea id="cusNote" class="form-input form-textarea" placeholder="Ghi chú thêm (Không bắt buộc)"></textarea>
                
                <div class="cart-total-box">
                    <span>TỔNG CỘNG:</span>
                    <span id="cart-total-price" class="total-price-text">0đ</span>
                </div>

                <button type="button" onclick="generateZaloMessage()" class="btn-zalo-submit">
                    <i class="fas fa-paper-plane"></i> GỬI ĐƠN QUA ZALO
                </button>
            </form>
        </div>
    </div>
</main>

<div id="zaloModal" class="zalo-modal" style="display: none;">
    <div class="zalo-modal-content">
        <i class="fas fa-times zalo-modal-close" onclick="closeModal()"></i>
        <h3 class="zalo-modal-title"><i class="fas fa-check-circle"></i> TẠO ĐƠN THÀNH CÔNG!</h3>
        <p class="zalo-modal-desc">Bạn vui lòng <b>Copy</b> nội dung bên dưới và gửi cho shop qua Zalo để được chốt đơn nhanh nhất nhé.</p>
        
        <textarea id="zaloMessageContent" class="zalo-textarea" readonly></textarea>
        
        <div class="zalo-modal-actions">
            <button onclick="copyMessage()" class="btn-copy">
                <i class="fas fa-copy"></i> COPY TIN NHẮN
            </button>
            <a href="https://zalo.me/0938440781" target="_blank" class="btn-open-zalo">
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
            area.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-basket cart-empty-icon"></i><p>Giỏ hàng của bạn đang trống.</p><a href="index.php" class="cart-empty-link">Tiếp tục mua sắm</a></div>';
            totalEl.innerText = '0đ';
            return;
        }

        let html = '';
        let total = 0;

        cart.forEach((item, index) => {
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            html += `
                <div class="cart-item">
                    <img src="uploads/${item.image}" class="cart-item-img" alt="${item.name}">
                    <div class="cart-item-info">
                        <h4 class="cart-item-name">${item.name}</h4>
                        <div class="cart-item-price">${item.price.toLocaleString('vi-VN')}đ</div>
                    </div>
                    <div class="cart-item-qty-control">
                        <button onclick="changeQty(${index}, -1)" class="btn-qty">-</button>
                        <input type="text" readonly value="${item.qty}" class="input-qty">
                        <button onclick="changeQty(${index}, 1)" class="btn-qty">+</button>
                    </div>
                    <div class="cart-item-total">
                        ${itemTotal.toLocaleString('vi-VN')}đ
                    </div>
                    <button onclick="removeItem(${index})" class="btn-remove-item" title="Xóa"><i class="fas fa-trash-alt"></i></button>
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