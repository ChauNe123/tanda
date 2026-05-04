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
            <a href="index.php" class="tgdd-logo">
                <i class="fas fa-camera-retro" style="font-size: 30px; margin-right: 8px;"></i>
                TAN<span>DA</span>
            </a>
            
            <div class="tgdd-search-container">
                <div class="tgdd-search">
                    <form action="search.php" method="GET" style="display: flex; width: 100%; align-items: center; margin: 0;">
                        <button type="submit" style="background: none; border: none; color: #666; cursor: pointer;"><i class="fas fa-search"></i></button>
                        <input type="text" name="q" placeholder="Bạn tìm camera gì..." style="border: none; outline: none; flex: 1; padding: 10px; background: transparent; font-size: 15px;">
                    </form>
                </div>
                <div class="header-suggestions">
                    <div class="suggestion-list">
                        <span class="sugg-label">Gợi ý:</span>
                        <a href="index.php" class="sugg-pill active">Tất cả</a>
                        <?php
                        try {
                            $stmtSugg = $conn->query("SELECT slug, cat_code FROM categories WHERE status = 1 LIMIT 5");
                            $suggs = $stmtSugg->fetchAll();
                            foreach ($suggs as $s) {
                                echo '<a href="category.php?slug=' . htmlspecialchars($s['slug']) . '" class="sugg-pill">' . htmlspecialchars($s['cat_code']) . '</a>';
                            }
                        } catch (Exception $e) {}
                        ?>
                        <a href="search.php?promo=1" class="sugg-pill promo-pill">
                            <i class="fas fa-bolt"></i> Khuyến Mãi
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="action-btn cart-pill" onclick="window.location.href='cart.php'">
                    <div class="cart-icon-wrap">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge" id="cartBadge">0</span>
                    </div>
                    <span>Giỏ hàng</span>
                </div>
            </div>
        </div>

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
                            echo '<a href="category.php?slug=' . htmlspecialchars($item['slug']) . '" style="font-size: 15px; padding: 12px 15px;">';
                            echo '<i class="' . htmlspecialchars($icon) . '" style="font-size:18px;"></i> ' . htmlspecialchars($item['name']);
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