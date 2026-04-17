<?php
// Đảm bảo đã gọi file kết nối DB trước khi gọi header
require_once __DIR__ . '/../cores/db_config.php';
global $sys_settings;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($sys_settings['seo_title']) ? $sys_settings['seo_title'] : 'TANDA - Phân Phối Camera'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <header>
        <div class="container header-wrap">
            <a href="index.php" class="logo">TAN<span>DA</span></a>
            
            <div class="search-box">
                <input type="text" placeholder="Bạn tìm camera gì...">
                <button><i class="fas fa-search"></i></button>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <div class="header-btn hotline-btn">
                    <i class="fas fa-phone-alt"></i> <?php echo isset($sys_settings['hotline']) ? $sys_settings['hotline'] : '09xx.xxx.xxx'; ?>
                </div>
                <div class="header-btn cart-btn" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
                    <span class="cart-badge" id="cartBadge">0</span>
                </div>
            </div>
        </div>
    </header>

    <nav class="main-menu">
        <div class="container">
            <ul class="menu-list">
                <li class="menu-item">Camera Wifi
                    <ul class="dropdown"><li>Ezviz Trong Nhà</li><li>Imou Ngoài Trời</li></ul>
                </li>
                <li class="menu-item">Camera Trọn Bộ
                    <ul class="dropdown"><li>Bộ 4 Mắt Dahua</li><li>Bộ 8 Mắt Hikvision</li></ul>
                </li>
                <li class="menu-item">Đầu Ghi Hình</li>
                <li class="menu-item">Thiết Bị Mạng</li>
                <li class="menu-item">Phụ Kiện</li>
            </ul>
        </div>
    </nav>