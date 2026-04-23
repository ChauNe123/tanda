<?php
require_once 'cores/db_config.php';
include 'includes/header.php';
?>

<!-- Link CSS cho Giỏ Hàng -->
<link rel="stylesheet" href="assets/css/pages/cart.css?v=<?php echo time(); ?>">

<main class="container cart-page-container">
    <div class="cart-tgdd-wrapper">
        <div class="cart-tgdd-header">
            <a href="index.php" class="back-link"><i class="fas fa-chevron-left"></i> Mua thêm sản phẩm khác</a>
            <span>Giỏ hàng của bạn</span>
        </div>

        <div id="cart-render-area">
            <!-- JS renders items here -->
        </div>

        <div class="cart-tgdd-footer" id="cart-footer" style="display: none;">
            <div class="cart-total-box">
                <span>Tạm tính:</span>
                <span id="cart-total-price" class="total-price-text">0đ</span>
            </div>

            <div class="cart-form-wrap">
                <h3 style="font-size: 14px; margin-bottom:15px; color: #333; text-transform: uppercase;">THÔNG TIN KHÁCH HÀNG</h3>
                <form id="checkout-form">
                    <div class="form-group-tgdd">
                        <input type="text" id="cusName" class="form-input" placeholder="Họ và tên (Bắt buộc)" required>
                        <input type="tel" id="cusPhone" class="form-input" placeholder="Số điện thoại (Bắt buộc)" required>
                    </div>
                    <input type="text" id="cusAddress" class="form-input" placeholder="Địa chỉ nhận hàng (Bắt buộc)" required>
                    <textarea id="cusNote" class="form-input form-textarea" placeholder="Yêu cầu khác (Không bắt buộc)"></textarea>
                    
                    <p id="checkout-error" style="color: #d70018; font-size: 14px; margin-bottom: 15px; display: none; font-weight: 500;"><i class="fas fa-exclamation-triangle"></i> <span></span></p>
                    <button type="button" onclick="generateZaloMessage()" class="tgdd-btn-order">
                        ĐẶT HÀNG QUA ZALO
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<div id="zaloModal" class="zalo-modal" style="display: none;">
    <div class="zalo-modal-content">
        <i class="fas fa-times zalo-modal-close" onclick="closeModal()"></i>
        <h3 class="zalo-modal-title"><i class="fas fa-check-circle"></i> TẠO ĐƠN THÀNH CÔNG!</h3>
        <p class="zalo-modal-desc">Chỉ cần bấm nút bên dưới, hệ thống sẽ tự động <b>Copy</b> đơn hàng và mở Zalo để bạn gửi cho shop chốt đơn.</p>
        
        <textarea id="zaloMessageContent" class="zalo-textarea" readonly></textarea>
        
        <div class="zalo-modal-actions">
            <button onclick="copyAndOpenZalo()" class="btn-open-zalo" style="width: 100%; font-size: 16px; padding: 15px; background: #0068ff; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-paper-plane"></i> COPY TIN NHẮN & MỞ ZALO NGAY
            </button>
        </div>
    </div>
</div>

<script>
    function formatCurrency(num) {
        return num.toLocaleString('vi-VN') + 'đ';
    }

    function renderCartPage() {
        let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
        let area = document.getElementById('cart-render-area');
        let footer = document.getElementById('cart-footer');
        let totalEl = document.getElementById('cart-total-price');
        
        if(cart.length === 0) {
            area.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-cart" style="font-size: 60px; color:#ccc; margin-bottom:15px;"></i><p>Không có sản phẩm nào trong giỏ hàng</p><a href="index.php" class="cart-empty-link">VỀ TRANG CHỦ</a></div>';
            footer.style.display = 'none';
            return;
        } else {
            footer.style.display = 'block';
        }

        let html = '';
        let total = 0;

        cart.forEach((item, index) => {
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            html += `
                <div class="cart-item">
                    <div class="cart-item-img-wrap">
                        <img src="uploads/${item.image}" class="cart-item-img" alt="${item.name}" onerror="this.src='https://via.placeholder.com/80'">
                        <button onclick="removeItem(${index})" class="btn-remove-item"><i class="fas fa-times-circle"></i> Xóa</button>
                    </div>
                    <div class="cart-item-info">
                        <h4 class="cart-item-name">${item.name}</h4>
                        <div class="cart-item-price">${formatCurrency(item.price)}</div>
                        <div class="cart-item-qty-control">
                            <button onclick="changeQty(${index}, -1)" class="btn-qty">-</button>
                            <input type="text" readonly value="${item.qty}" class="input-qty">
                            <button onclick="changeQty(${index}, 1)" class="btn-qty">+</button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        area.innerHTML = html;
        totalEl.innerText = formatCurrency(total);
    }

    function changeQty(index, amount) {
        let cart = JSON.parse(localStorage.getItem('tanda_cart'));
        cart[index].qty += amount;
        if(cart[index].qty <= 0) cart.splice(index, 1);
        localStorage.setItem('tanda_cart', JSON.stringify(cart));
        renderCartPage();
        if(typeof updateCartBadge === 'function') updateCartBadge();
    }

    function removeItem(index) {
        let cart = JSON.parse(localStorage.getItem('tanda_cart'));
        cart.splice(index, 1);
        localStorage.setItem('tanda_cart', JSON.stringify(cart));
        renderCartPage();
        if(typeof updateCartBadge === 'function') updateCartBadge();
    }

    document.addEventListener("DOMContentLoaded", renderCartPage);

    function generateZaloMessage() {
        let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
        if(cart.length === 0) return alert('Giỏ hàng trống!');
        
        let nameEl = document.getElementById('cusName');
        let phoneEl = document.getElementById('cusPhone');
        let addressEl = document.getElementById('cusAddress');
        let errorEl = document.getElementById('checkout-error');
        
        let name = nameEl.value.trim();
        let phone = phoneEl.value.trim();
        let address = addressEl.value.trim();
        let note = document.getElementById('cusNote').value.trim();

        // Reset errors
        nameEl.style.borderColor = '#ccc';
        phoneEl.style.borderColor = '#ccc';
        addressEl.style.borderColor = '#ccc';
        errorEl.style.display = 'none';

        // Validate
        if(name.length < 2) {
            nameEl.style.borderColor = '#d70018';
            errorEl.querySelector('span').innerText = 'Vui lòng nhập Họ và Tên hợp lệ.';
            errorEl.style.display = 'block';
            nameEl.focus();
            return;
        }

        let phoneRegex = /^(0[3|5|7|8|9])+([0-9]{8})$/;
        if(!phoneRegex.test(phone)) {
            phoneEl.style.borderColor = '#d70018';
            errorEl.querySelector('span').innerText = 'Số điện thoại không hợp lệ (Phải là 10 số và bắt đầu bằng 0).';
            errorEl.style.display = 'block';
            phoneEl.focus();
            return;
        }

        if(address.length < 5) {
            addressEl.style.borderColor = '#d70018';
            errorEl.querySelector('span').innerText = 'Vui lòng nhập địa chỉ giao hàng cụ thể.';
            errorEl.style.display = 'block';
            addressEl.focus();
            return;
        }

        let total = 0;
        let msg = `🛒 ĐƠN HÀNG TỪ WEB TANDA:\n`;
        msg += `---------------------------\n`;
        msg += `👤 Khách hàng: ${name}\n`;
        msg += `📞 SĐT: ${phone}\n`;
        msg += `📍 Địa chỉ: ${address}\n`;
        if(note) msg += `📝 Ghi chú: ${note}\n`;
        msg += `📦 SẢN PHẨM:\n`;
        
        cart.forEach(item => {
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            msg += `- ${item.qty}x ${item.name} (${formatCurrency(item.price)})\n`;
        });
        
        msg += `---------------------------\n`;
        msg += `💰 TỔNG: ${formatCurrency(total)}\n`;

        document.getElementById('zaloMessageContent').value = msg;
        document.getElementById('zaloModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('zaloModal').style.display = 'none';
    }

    function copyAndOpenZalo() {
        let text = document.getElementById('zaloMessageContent');
        text.select();
        document.execCommand('copy');
        
        // Thay đổi UI của Modal hiện tại thay vì dùng alert() xấu xí
        let modalContent = document.querySelector('.zalo-modal-content');
        modalContent.innerHTML = `
            <div style="text-align: center; padding: 10px;">
                <i class="fas fa-check-circle" style="font-size: 60px; color: #28a745; margin-bottom: 15px;"></i>
                <h3 style="color: #333; margin-bottom: 15px; font-size: 20px;">Đã lưu (Copy) đơn hàng!</h3>
                <p style="color: #555; font-size: 15px; line-height: 1.6; margin-bottom: 25px;">
                    Hệ thống đang tự động chuyển qua Zalo...<br>
                    👉 Bạn chỉ cần nhấn <b>Dán (Paste)</b> và Gửi cho shop để chốt đơn nhé!
                </p>
                <div style="margin-bottom: 10px;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size: 30px; color: #0068ff;"></i>
                </div>
            </div>
        `;

        // Chờ 2.5 giây để khách đọc kịp hướng dẫn "Dán (Paste)", sau đó mới chuyển hướng
        setTimeout(() => {
            window.location.href = 'https://zalo.me/0938440781';
        }, 2500);
    }
</script>

<?php include 'includes/footer.php'; ?>