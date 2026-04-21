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
    <link rel="stylesheet" href="assets/css/layout/grid.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/components/product-card.css?v=<?php echo time(); ?>">
</head>
<body>

    <header class="tgdd-header">
        <div class="container header-top">
            <a href="index.php" class="tgdd-logo">
                <i class="fas fa-camera-retro" style="font-size: 26px; margin-right: 5px;"></i>
                TAN<span>DA</span>
            </a>
            
            <div class="tgdd-search">
                <form action="search.php" method="GET" style="display: flex; width: 100%; align-items: center; margin: 0;">
                    <button type="submit" style="background: none; border: none; color: #666; cursor: pointer;"><i class="fas fa-search"></i></button>
                    <input type="text" name="q" placeholder="Bạn tìm camera gì..." style="border: none; outline: none; flex: 1; padding: 5px; background: transparent;">
                </form>
            </div>
            
            <div class="header-actions">
                <div class="action-btn cart-btn" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
                    <span class="cart-badge" id="cartBadge">0</span>
                </div>
            </div>
        </div>

        <nav class="tgdd-nav">
            <div class="container">
                <ul class="tgdd-menu-list">
                    <li class="tgdd-menu-item">
                        <a href="category.php?slug=camera-wifi"><i class="fas fa-video" style="font-size:16px;"></i> Camera WiFi</a>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="category.php?slug=camera-tron-bo"><i class="fas fa-server" style="font-size:16px;"></i> Camera Trọn Bộ</a>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="category.php?slug=dau-ghi-hinh"><i class="fas fa-hdd" style="font-size:16px;"></i> Đầu Ghi Hình</a>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="category.php?slug=thiet-bi-mang"><i class="fas fa-network-wired" style="font-size:16px;"></i> Thiết Bị Mạng</a>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="category.php?slug=phu-kien"><i class="fas fa-headphones" style="font-size:16px;"></i> Phụ Kiện</a>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="search.php?promo=1"><i class="fas fa-bolt" style="font-size:16px; color:#d70018;"></i> Khuyến Mãi Hot</a>
                    </li>
                </ul>
            </div>
        </nav>

    </header>