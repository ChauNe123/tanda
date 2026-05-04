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
    
    <link rel="icon" href="assets/img/favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/layout/grid.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/components/product-card.css?v=<?php echo time(); ?>">
    
    <!-- CSS Tối ưu giao diện TGDĐ -->
    <style>
        .tgdd-header {
            background-color: #ffd400;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }
        .header-top {
            display: flex;
            align-items: flex-start; /* Canh trên cùng để dòng gợi ý không đẩy layout */
            justify-content: space-between;
            padding-top: 15px;
            padding-bottom: 5px;
            gap: 20px;
        }
        .tgdd-logo {
            display: flex;
            align-items: center;
            height: 40px; /* Cố định chiều cao bằng thanh search */
            font-size: 30px;
            font-weight: 900;
            color: #000;
            text-decoration: none;
            font-style: italic; /* Style nghiêng của TGDĐ */
            letter-spacing: -1px;
        }
        .tgdd-logo span {
            font-weight: 400;
        }
        .search-wrapper {
            flex: 1;
            max-width: 650px;
            display: flex;
            flex-direction: column;
        }
        .tgdd-search {
            display: flex;
            align-items: center;
            background: #fff;
            height: 40px;
            border-radius: 20px;
            padding: 0 15px;
        }
        .sugg-list {
            display: flex;
            gap: 12px;
            margin-top: 8px;
            font-size: 13px;
        }
        .sugg-list a {
            color: #000;
            text-decoration: none;
        }
        .sugg-list a:hover {
            text-decoration: underline;
        }
        .header-actions {
            display: flex;
            align-items: center;
            height: 40px; /* Cố định chiều cao bằng thanh search */
            gap: 20px;
        }
        .cart-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #000;
            font-size: 14px;
        }
        .cart-icon-wrap {
            position: relative;
            font-size: 20px;
        }
        .cart-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #d0021b; /* Màu đỏ TGDĐ */
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            border-radius: 10px;
            padding: 1px 6px;
        }
        .location-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,0,0,0.08); /* Màu vàng sậm xuống 1 chút */
            height: 40px;
            padding: 0 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }
        .tgdd-nav {
            background-color: #ffd400;
        }
        .tgdd-menu-list {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 25px;
            flex-wrap: wrap;
        }
        .tgdd-menu-item a {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #000;
            text-decoration: none;
            font-size: 14px;
            padding: 12px 0;
        }
        .tgdd-menu-item:hover a {
            font-weight: bold;
        }
    </style>

    <!-- Cụm script chặn lỗi và ĐIỀU TRA NGUỒN GỐC LỖI -->
    <script>
    console.log("%c--- TANDA FORENSIC INVESTIGATION ---", "color: #ff5722; font-weight: bold; font-size: 14px;");
    
    // 1. Kiểm tra tất cả script đang chạy
    window.addEventListener('load', function() {
        const scripts = document.getElementsByTagName('script');
        console.log("Tổng số script trên trang:", scripts.length);
        for (let s of scripts) {
            if (s.src) console.log("🔍 Script Source:", s.src);
        }
        
        // Kiểm tra xem có file onboarding.js không
        const isExternal = Array.from(scripts).some(s => s.src.includes('onboarding.js'));
        if (isExternal) {
            console.warn("⚠️ PHÁT HIỆN: Script 'onboarding.js' đang chạy. Nguồn không thuộc về website.");
        } else {
            console.log("✅ Xác nhận: Không có file 'onboarding.js' nào được nạp từ Server của bạn.");
        }
    });

    // 2. Chặn lỗi từ script lạ để sạch Console
    window.addEventListener('unhandledrejection', function (event) {
        if (event.reason === undefined || (event.reason && event.reason.stack && event.reason.stack.includes('onboarding.js'))) {
            console.log("%c🛡️ Đã chặn một lỗi từ Tiện ích mở rộng trình duyệt (onboarding.js)", "color: #888;");
            event.preventDefault();
        }
    });
    console.log("%c✅ CODE DỰ ÁN TANDA ĐÃ SẴN SÀNG.", "color: #4CAF50; font-weight: bold;");
    </script>
</head>
<body>

    <header class="tgdd-header">
        <div class="container header-top">
            <!-- Logo -->
            <a href="index.php" class="tgdd-logo">
                <i class="fas fa-camera-retro" style="font-size: 28px; margin-right: 8px;"></i>
                TAN<span>DA</span>
            </a>
            
            <!-- Cụm Tìm Kiếm & Gợi ý -->
            <div class="search-wrapper">
                <div class="tgdd-search">
                    <form action="search.php" method="GET" style="display: flex; width: 100%; align-items: center; margin: 0;">
                        <button type="submit" style="background: none; border: none; color: #666; cursor: pointer; padding-left: 5px;"><i class="fas fa-search"></i></button>
                        <input type="text" name="q" placeholder="Bạn tìm camera gì..." style="border: none; outline: none; flex: 1; padding: 10px 10px; background: transparent; font-size: 14px;">
                    </form>
                </div>
                
                <div class="sugg-list">
                    <span style="font-weight: 600;">Gợi ý:</span>
                    <a href="index.php" style="color: #d0021b; font-weight: bold;">Tất cả</a>
                    <?php
                    try {
                        $stmtSugg = $conn->query("SELECT slug, cat_code FROM categories WHERE status = 1 LIMIT 5");
                        $suggs = $stmtSugg->fetchAll();
                        foreach ($suggs as $s) {
                            echo '<a href="category.php?slug=' . htmlspecialchars($s['slug']) . '">' . htmlspecialchars($s['cat_code']) . '</a>';
                        }
                    } catch (Exception $e) {}
                    ?>
                    <a href="search.php?promo=1" style="color: #d0021b; font-weight: bold;">
                        <i class="fas fa-bolt"></i> Khuyến Mãi
                    </a>
                </div>
            </div>
            
            <!-- Cụm Hành động: Giỏ hàng & Vị trí -->
            <div class="header-actions">
                <div class="cart-btn" onclick="window.location.href='cart.php'">
                    <div class="cart-icon-wrap">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge" id="cartBadge">0</span>
                    </div>
                    <span>Giỏ hàng</span>
                </div>

                <div class="location-btn">
                    <i class="fas fa-map-marker-alt" style="font-size: 16px;"></i>
                    <span>Hồ Chí Minh</span>
                    <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                </div>
            </div>
        </div>

        <!-- Menu Danh Mục -->
        <nav class="tgdd-nav">
            <div class="container">
                <ul class="tgdd-menu-list">
                    <?php
                    try {
                        $stmtMenu = $conn->query("SELECT name, slug, icon_class FROM categories WHERE status = 1 ORDER BY id ASC");
                        $menuItems = $stmtMenu->fetchAll();
                        foreach ($menuItems as $item) {
                            $icon = !empty($item['icon_class']) ? $item['icon_class'] : 'fas fa-tag';
                            $hasDropdown = (strpos(strtolower($item['name']), 'phụ kiện') !== false);
                            echo '<li class="tgdd-menu-item ' . ($hasDropdown ? 'has-child' : '') . '">';
                            echo '<a href="category.php?slug=' . htmlspecialchars($item['slug']) . '">';
                            echo '<i class="' . htmlspecialchars($icon) . '" style="font-size:16px;"></i> <span>' . htmlspecialchars($item['name']) . '</span>';
                            if ($hasDropdown) echo ' <i class="fas fa-chevron-down" style="font-size:10px; margin-left:2px; opacity:0.6;"></i>';
                            echo '</a>';
                            echo '</li>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </ul>
            </div>
        </nav>
    </header>