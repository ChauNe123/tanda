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

    <header class="tgdd-header">
        <div class="container header-top">
            <a href="index.php" class="tgdd-logo">
                <i class="fas fa-camera-retro" style="font-size: 26px; margin-right: 5px;"></i>
                TAN<span>DA</span>
            </a>
            
            <div class="tgdd-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Bạn tìm camera gì...">
            </div>
            
            <div class="header-actions">
                <div class="action-btn cart-btn" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
                    <span class="cart-badge" id="cartBadge">0</span>
                </div>

                <div class="action-btn location-btn">
                    <i class="fas fa-map-marker-alt"></i> Hồ Chí Minh <i class="fas fa-chevron-down" style="font-size: 10px; margin-left:2px;"></i>
                </div>
            </div>
        </div>

        <nav class="tgdd-nav">
            <div class="container">
                <ul class="tgdd-menu-list">
                    <li class="tgdd-menu-item">
                        <a href="#"><i class="fas fa-video"></i> Camera Wifi <i class="fas fa-chevron-down arrow"></i></a>
                        <ul class="dropdown">
                            <li><a href="#">Ezviz Trong Nhà</a></li>
                            <li><a href="#">Imou Ngoài Trời</a></li>
                        </ul>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="#"><i class="fas fa-shield-alt"></i> Camera Trọn Bộ <i class="fas fa-chevron-down arrow"></i></a>
                        <ul class="dropdown">
                            <li><a href="#">Bộ 4 Mắt Dahua</a></li>
                            <li><a href="#">Bộ 8 Mắt Hikvision</a></li>
                        </ul>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="#"><i class="fas fa-hdd"></i> Đầu Ghi Hình</a>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="#"><i class="fas fa-network-wired"></i> Thiết Bị Mạng <i class="fas fa-chevron-down arrow"></i></a>
                        <ul class="dropdown">
                            <li><a href="#">Switch PoE</a></li>
                            <li><a href="#">Router Wifi</a></li>
                        </ul>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="#"><i class="fas fa-tools"></i> Phụ Kiện <i class="fas fa-chevron-down arrow"></i></a>
                        <ul class="dropdown">
                            <li><a href="#">Ổ cứng & Thẻ nhớ</a></li>
                            <li><a href="#">Nguồn & Jack</a></li>
                        </ul>
                    </li>
                    <li class="tgdd-menu-item">
                        <a href="#"><i class="fas fa-headset"></i> Dịch vụ tiện ích <i class="fas fa-chevron-down arrow"></i></a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>