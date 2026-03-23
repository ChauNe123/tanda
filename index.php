<?php 
// 1. Gọi file kết nối CSDL
require_once 'cores/db_config.php'; // (Nhớ tạo thư mục core và bỏ file db.php vào đổi tên nhé)

// 2. Nhúng Header
include 'includes/header.php'; 
?>

<section class="container hero-section">
    <div class="hero-layout">
        <div class="menu-trai">
            <ul>
                <li>👉 Camera Wifi Không Dây</li>
                <li>👉 Camera Trọn Bộ (Có Dây)</li>
                <li>👉 Đầu Ghi Hình Chính Hãng</li>
                <li>👉 Thẻ Nhớ & Ổ Cứng</li>
                <li>👉 Phụ Kiện Lắp Đặt</li>
            </ul>
        </div>
        <div class="banner-chinh">
            <img src="https://cdn.hoanghamobile.com/i/home/Uploads/2023/10/26/web-camera.png" alt="Banner Camera">
        </div>
        <div class="banner-phu">
            <img src="https://cdn.hoanghamobile.com/i/home/Uploads/2023/08/11/thay-man-hinh-samsung.png" alt="Banner phụ 1">
            <img src="https://cdn.hoanghamobile.com/i/home/Uploads/2023/07/28/banner-b2b-01.png" alt="Banner phụ 2">
        </div>
    </div>
</section>

<section class="flash-sale-bg">
    <div class="container">
        <div class="title-img">
            <h2>⚡ ĐANG DIỄN RA - GIÁ TỐT CHỐT NGAY ⚡</h2>
        </div>
        
        <div class="product-grid">
            <div class="product-card">
                <div class="img-wrap">
                    <img src="https://cdn.hoanghamobile.com/i/productlist/ts/Uploads/2023/06/13/camera-ip-wifi-tp-link-tapo-c200-360-1080p-2mp-1.png" class="sp-goc" alt="Tapo C200">
                    <img src="https://theme.hstatic.net/200000722513/1001090675/14/frame_1.png?v=3834" class="sp-vien" alt="khung">
                    <span class="discount-badge">-25%</span>
                </div>
                <div class="info">
                    <h3>Camera IP Wifi TP-Link Tapo C200 1080p</h3>
                    <div class="price-area">
                        <span class="price-new">450.000đ</span>
                        <span class="price-old">600.000đ</span>
                    </div>
                    <button class="btn-zalo">💬 Chốt đơn qua Zalo</button>
                </div>
            </div>
            </div>
    </div>
</section>

<?php 
// 3. Nhúng Footer
include 'includes/footer.php'; 
?>