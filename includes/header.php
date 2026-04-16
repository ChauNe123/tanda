<?php
// Bật hiển thị lỗi nếu cần test
// error_reporting(E_ALL); ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Hệ Thống An Ninh MÀU CAM</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="tgdd-header">
    <div class="container tgdd-header-inner">
        <a href="index.php" class="tgdd-logo">TAN<span>DA</span></a>
        
        <form class="tgdd-search" action="search.php" method="GET">
            <input type="text" name="q" placeholder="Bạn tìm gì hôm nay?" required>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        
        <div class="tgdd-actions">
            <a href="#" class="tgdd-action-btn"><i class="fas fa-user"></i> Đăng nhập</a>
            <a href="cart.php" class="tgdd-action-btn"><i class="fas fa-shopping-cart"></i> Giỏ hàng <span class="count" style="background:#fff; color:#ff5722; padding:1px 5px; border-radius:10px; font-size:12px; font-weight:bold; margin-left:3px;">0</span></a>
            <a href="#" class="tgdd-action-btn location-btn"><i class="fas fa-map-marker-alt"></i> Hồ Chí Minh <i class="fas fa-chevron-right" style="font-size:10px; margin-left:3px;"></i></a>
        </div>
    </div>
</header>

<nav class="tgdd-nav">
    <div class="container">
        <ul class="tgdd-nav-list">
            <li><a href="category.php?slug=camera-wifi"><i class="fas fa-video"></i> Camera Wifi</a></li>
            <li><a href="category.php?slug=camera-tron-bo"><i class="fas fa-camera"></i> Trọn bộ Camera</a></li>
            <li><a href="category.php?slug=dau-ghi-hinh"><i class="fas fa-server"></i> Đầu Ghi Hình</a></li>
            <li><a href="category.php?slug=phu-kien"><i class="fas fa-hdd"></i> Phụ kiện, Thẻ nhớ <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a></li>
            <li><a href="category.php?slug=thiet-bi-mang"><i class="fas fa-network-wired"></i> Thiết Bị Mạng</a></li>
            <li><a href="#"><i class="fas fa-tools"></i> Dịch vụ tiện ích <i class="fas fa-chevron-down" style="font-size:10px; margin-left:3px;"></i></a></li>
        </ul>
    </div>
</nav>