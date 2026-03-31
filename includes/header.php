<?php
// Bật hiển thị lỗi nếu cần test
// error_reporting(E_ALL); ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Hệ Thống Phân Phối Camera & An Ninh Chính Hãng</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="top-bar">
        <div class="container">
            <div class="top-badge"><i class="fas fa-map-marker-alt"></i> Hệ thống showroom</div>
            <div class="top-badge" style="background: transparent;"><i class="fas fa-phone-alt"></i> Mua hàng trực tuyến</div>
        </div>
    </div>

    <header class="main-header">
        <div class="container">
            <a href="index.php" class="logo-area">
                <div class="logo-text">TAN<span>DA</span></div>
            </a>
            
            <div class="search-area">
                <form class="search-box" action="search.php" method="GET">
                    <select name="cat">
                        <option value="">Tất cả danh mục</option>
                        <option value="CAM-WIFI">Camera Wifi</option>
                        <option value="CAM-DAY">Camera Trọn Bộ</option>
                        <option value="DAU-GHI">Đầu Ghi Hình</option>
                        <option value="PHU-KIEN">Phụ Kiện</option>
                        <option value="THIET-BI-MANG">Thiết Bị Mạng</option>
                    </select>
                    <input type="text" name="q" placeholder="Tìm kiếm mã camera, đầu ghi, thẻ nhớ..." required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="search-suggest">
                    <a href="search.php?q=Ezviz">Camera Ezviz</a>
                    <a href="search.php?q=Imou">Camera Imou</a>
                    <a href="search.php?q=Dahua">Trọn bộ Dahua</a>
                    <a href="search.php?q=Ổ cứng">Ổ cứng chuyên dụng</a>
                </div>
            </div>
            
            <div class="header-actions">
    <div class="contact-box">
        <i class="fas fa-headset"></i>
        <div class="contact-info">
            <span class="title">Hotline & Zalo (24/7)</span>
            <span class="phone">098.655.xxxx</span>
        </div>
    </div>

        <a href="cart.php" style="text-decoration: none;">
                    <div class="cart-box" onclick="window.location.href='cart.php'" style="cursor: pointer; transition: 0.3s;">
    <i class="fas fa-shopping-cart"></i> Giỏ hàng <span class="count">(0)</span>
</div>
                </a>
            </div>
    </div>
</header>

    <nav class="nav-bar">
        <div class="container">
            <div class="nav-category">
                <i class="fas fa-bars"></i> DANH MỤC SẢN PHẨM
            </div>
            <div class="nav-links">
                <a href="category.php?slug=camera-wifi"><i class="fas fa-video"></i> CAMERA WIFI KHÔNG DÂY</a>
                <a href="category.php?slug=camera-tron-bo"><i class="fas fa-camera"></i> CAMERA TRỌN BỘ</a>
                <a href="category.php?slug=dau-ghi-hinh"><i class="fas fa-server"></i> ĐẦU GHI HÌNH</a>
                <a href="category.php?slug=phu-kien"><i class="fas fa-hdd"></i> THẺ NHỚ & PHỤ KIỆN</a>
                <a href="category.php?slug=thiet-bi-mang"><i class="fas fa-network-wired"></i> THIẾT BỊ MẠNG</a>
                <a href="#"><i class="fas fa-tools"></i> DỊCH VỤ LẮP ĐẶT</a> 
            </div>
        </div>
    </nav>