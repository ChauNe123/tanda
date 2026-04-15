<?php
// Bật hiển thị lỗi nếu cần test
// error_reporting(E_ALL); ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Hệ Thống Phân Phối Camera & An Ninh</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <header class="tanda-header" id="mainHeader">
        <div class="header-main">
            <div class="container header-flex">
                <a href="index.php" class="logo">
                    TAN<span>DA</span>
                </a>
                
                <form class="search-form" action="search.php" method="GET">
                    <button type="submit"><i class="fas fa-search"></i></button>
                    <input type="text" name="q" placeholder="Bạn tìm gì (VD: Camera Ezviz, Trọn bộ Dahua...)" required>
                </form>

                <div class="header-tools">
                    <div class="tool-item location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Hồ Chí Minh</span>
                        <i class="fas fa-chevron-down small-icon"></i>
                    </div>
                    <div class="tool-item login">
                        <i class="far fa-user"></i>
                        <span>Đăng nhập</span>
                    </div>
                    <a href="cart.php" class="tool-item cart" id="cartBtn">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Giỏ hàng <b class="count">(0)</b></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="header-nav">
            <div class="container nav-flex">
                <a href="category.php?slug=camera-wifi"><i class="fas fa-video"></i> Camera Wifi</a>
                <a href="category.php?slug=camera-tron-bo"><i class="fas fa-camera"></i> Camera Trọn Bộ</a>
                <a href="category.php?slug=dau-ghi-hinh"><i class="fas fa-server"></i> Đầu Ghi Hình</a>
                <a href="category.php?slug=phu-kien"><i class="fas fa-hdd"></i> Phụ Kiện</a>
                <a href="category.php?slug=thiet-bi-mang"><i class="fas fa-network-wired"></i> Thiết Bị Mạng</a>
                <a href="#"><i class="fas fa-tools"></i> Dịch vụ lắp đặt</a>
            </div>
        </div>
    </header>