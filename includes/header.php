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
    <link rel="stylesheet" href="assets/css/pages/compare.css?v=<?php echo time(); ?>">
    
    <!-- CSS Header chuẩn Thegioididong -->
    <style>
        /* === HEADER CHÍNH === */
        .tgdd-header {
            background-color: #ffd400;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* === HÀNG TRÊN: Logo - Search - Actions === */
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            gap: 20px;
            transition: padding 0.3s ease;
        }

        /* Logo */
        .tgdd-logo {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            font-size: 28px;
            font-weight: 900;
            color: #000;
            text-decoration: none;
            font-style: italic;
            letter-spacing: -1px;
            line-height: 1;
        }
        .tgdd-logo span { font-weight: 400; }

        /* Search + Gợi ý */
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
            height: 42px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            padding: 0 12px;
            transition: border-color 0.2s;
        }
        .tgdd-search:focus-within { border-color: #000; }
        .sugg-list {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 10px;
            font-size: 13px;
            flex-wrap: wrap;
        }
        .sugg-list a { color: #000; text-decoration: none; font-weight: 500; }
        .sugg-list a:hover { text-decoration: underline; }

        /* Actions: Giỏ hàng + Địa chỉ */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }
        .cart-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #000;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: opacity 0.2s;
        }
        .cart-btn:hover { opacity: 0.7; }
        .cart-icon-wrap {
            position: relative;
            font-size: 22px;
        }
        .cart-badge {
            position: absolute;
            top: -7px;
            right: -9px;
            background: #d70018;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            border-radius: 10px;
            padding: 1px 5px;
            border: 1px solid #ffd400;
            line-height: 1.2;
            min-width: 16px;
            text-align: center;
        }
        .location-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,0,0,0.06);
            height: 40px;
            padding: 0 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            transition: background 0.2s;
        }
        .location-btn:hover { background: rgba(0,0,0,0.12); }

        /* === HÀNG DƯỚI: Menu Danh Mục === */
        .tgdd-nav {
            background-color: #ffd400;
            border-top: 1px solid rgba(0,0,0,0.08);
            overflow: visible;
            min-height: 52px;
            opacity: 1;
            transition: opacity 0.3s ease, border-top 0.3s ease, padding 0.3s ease, min-height 0.3s ease;
        }
        /* =========================================
           ẨN MENU DANH MỤC TRÊN PC (Desktop)
           ========================================= */
        @media (min-width: 769px) {
            /* 1. Giấu toàn bộ thanh menu màu vàng đi */
            #tgddNav {
                display: none !important;
            }
            
            /* 2. Cực kỳ quan trọng: Rút gọn khoảng trống đỉnh trang */
            /* TĂNG LÊN 115PX ĐỂ ĐẨY KHUYẾN MÃI HOT XUỐNG DƯỚI HEADER */
            body, body.header-compact {
                padding-top: 115px !important; 
            }
        }
        .tgdd-menu-list {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }
        .tgdd-menu-item a {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #000;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 16px 10px;
            border-radius: 6px;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .tgdd-menu-item a:hover {
            background: rgba(255,255,255,0.35);
        }
        .tgdd-menu-item a i.menu-icon {
            font-size: 16px;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            flex-shrink: 0;
            background: rgba(255,255,255,0.4);
            border-radius: 50%;
            color: #333;
            transition: background 0.2s;
        }
        .tgdd-menu-item a:hover i.menu-icon {
            background: rgba(255,255,255,0.7);
        }

        /* === RESPONSIVE: TABLET & MOBILE === */
        /* Nút Hamburger - ẩn trên desktop */
        .hamburger-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #000;
            padding: 6px 10px;
            border-radius: 4px;
            transition: background 0.2s;
            flex-shrink: 0;
            z-index: 10;
        }
        .hamburger-toggle:hover { background: rgba(0,0,0,0.08); }

        @media (max-width: 1024px) {
            .tgdd-menu-item a { padding: 14px 8px; font-size: 12px; }
            .tgdd-menu-item a i.menu-icon { font-size: 14px; width: 24px; height: 24px; line-height: 24px; }
            .sugg-list a { font-size: 11px; }
        }

        @media (max-width: 768px) {
            body { padding-top: 56px; }
            body.nav-open { padding-top: 56px; overflow: hidden; }
            body.header-compact { padding-top: 56px; }
            
            .hamburger-toggle { 
                display: flex !important; 
                align-items: center; 
                justify-content: center;
                width: 36px;
                height: 36px;
                font-size: 22px;
            }
            
            .header-top { 
                display: flex;
                flex-wrap: nowrap; 
                padding: 6px 8px; 
                gap: 6px; 
                align-items: center;
                justify-content: flex-start;
            }
            .tgdd-logo { font-size: 18px; flex-shrink: 0; margin-right: auto; }
            .tgdd-logo i { font-size: 18px !important; margin-right: 3px !important; }
            
            /* Layout order: Logo | Hamburger | Search | Cart */
            .hamburger-toggle { order: 2; }
            .tgdd-logo { order: 1; }
            
            .search-wrapper { 
                order: 3; 
                flex: 1 1 auto; 
                max-width: none; 
                min-width: 0;
            }
            .tgdd-search { height: 32px; padding: 0 6px; }
            .tgdd-search input { font-size: 11px; padding: 4px 2px; }
            .sugg-list { display: none; }
            .location-btn { display: none; }
            
            .header-actions { 
                order: 4;
                gap: 4px; 
                flex-shrink: 0;
            }
            .header-actions .cart-btn span { display: none; }
            .cart-icon-wrap { font-size: 18px; }
            .cart-badge { font-size: 8px; top: -5px; right: -6px; min-width: 13px; padding: 0 4px; }
            
            /* === NAV MOBILE: Slide-down drawer === */
            .tgdd-nav {
                display: none;
                position: fixed;
                top: 56px;
                left: 0;
                width: 100%;
                max-height: 85vh;
                background: #fff;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                z-index: 9998;
                overflow-y: auto;
                border-top: none;
            }
            .tgdd-nav.nav-open {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto !important;
                border-top: 3px solid #ffd400 !important;
            }
            /* Overlay nền mờ khi mở menu */
            .nav-overlay {
                display: none;
                position: fixed;
                top: 56px;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 9997;
            }
            .nav-overlay.show {
                display: block;
            }
            .tgdd-nav .container {
                padding: 0;
                max-width: 100%;
            }
            .tgdd-menu-list { 
                display: flex;
                flex-wrap: wrap; 
                gap: 0; 
                padding: 12px 8px;
                overflow-x: visible;
                background: #fff;
            }
            .tgdd-menu-item { 
                flex: 0 0 50%;
                max-width: 50%;
            }
            .tgdd-menu-item a { 
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 14px 12px; 
                font-size: 14px;
                font-weight: 600;
                border-radius: 8px;
                background: none;
                color: #333;
                border-bottom: none;
                white-space: normal;
                text-decoration: none;
                margin: 2px;
            }
            .tgdd-menu-item a:active,
            .tgdd-menu-item a:focus {
                background: #f0f0f0;
            }
            .tgdd-menu-item a i.menu-icon { 
                font-size: 18px; 
                width: 36px; 
                height: 36px; 
                line-height: 36px;
                text-align: center;
                background: #fff3cd;
                border-radius: 50%;
                flex-shrink: 0;
                color: #333;
            }
            .tgdd-menu-item a span {
                flex: 1;
                line-height: 1.4;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            body { padding-top: 50px; }
            body.nav-open { padding-top: 50px; overflow: hidden; }
            body.header-compact { padding-top: 50px; }
            
            .tgdd-nav { top: 50px; }
            .nav-overlay { top: 50px; }
            
            .header-top { padding: 4px 6px; gap: 4px; }
            .tgdd-logo { font-size: 16px; }
            .tgdd-search { height: 28px; }
            .tgdd-search input { font-size: 10px; }
            .cart-icon-wrap { font-size: 16px; }
            .hamburger-toggle { width: 32px; height: 32px; font-size: 20px; }
            
            .tgdd-menu-list { padding: 8px 4px; }
            .tgdd-menu-item a { 
                padding: 12px 8px; 
                font-size: 13px;
                gap: 8px;
            }
            .tgdd-menu-item a i.menu-icon { 
                font-size: 15px; 
                width: 30px; 
                height: 30px; 
                line-height: 30px;
            }
            .tgdd-menu-item a span { font-size: 12px; }
        }

        /* =========================================
           SLEDGEHAMMER FIX: ÉP HIỂN THỊ MENU MOBILE 
           ========================================= */
        @media (max-width: 768px) {
            #tgddNav.nav-open {
                display: block !important;
                height: auto !important;
                min-height: 150px !important; 
                opacity: 1 !important;
                visibility: visible !important;
            }
            #tgddNav.nav-open .container,
            #tgddNav.nav-open .tgdd-menu-list {
                display: flex !important;
                opacity: 1 !important;
                visibility: visible !important;
                height: auto !important;
            }
            #tgddNav.nav-open .tgdd-menu-item {
                display: block !important;
                flex: 0 0 50% !important;
                max-width: 50% !important;
                opacity: 1 !important;
                visibility: visible !important;
                height: auto !important;
            }
            #tgddNav.nav-open .tgdd-menu-item a {
                display: flex !important;
                visibility: visible !important;
            }
        }
    </style>

    <!-- MOBILE NAV TOGGLE -->
    <script>
    // Tạo overlay nền mờ (đợi DOM sẵn sàng)
    document.addEventListener('DOMContentLoaded', function() {
        var overlay = document.createElement('div');
        overlay.className = 'nav-overlay';
        overlay.id = 'navOverlay';
        overlay.onclick = closeMobileNav;
        document.body.appendChild(overlay);
    });
    
    function toggleMobileNav() {
        var nav = document.getElementById('tgddNav');
        var btn = document.getElementById('hamburgerBtn');
        var overlay = document.getElementById('navOverlay');
        if (!nav) return;
        
        var isOpen = nav.classList.contains('nav-open');
        
        if (isOpen) {
            closeMobileNav();
        } else {
            // Xóa inline styles do initStickyHeader có thể đã set (tránh conflict)
            nav.style.removeProperty('max-height');
            nav.style.removeProperty('opacity');
            nav.style.removeProperty('border-top');
            nav.style.removeProperty('padding');
            nav.style.removeProperty('visibility');
            
            nav.classList.add('nav-open');
            document.body.classList.add('nav-open');
            if (btn) btn.innerHTML = '<i class="fas fa-times"></i>';
            if (overlay) overlay.classList.add('show');
            nav.scrollTop = 0;
        }
    }
    
    function closeMobileNav() {
        var nav = document.getElementById('tgddNav');
        var btn = document.getElementById('hamburgerBtn');
        var overlay = document.getElementById('navOverlay');
        if (nav) {
            nav.classList.remove('nav-open');
        }
        document.body.classList.remove('nav-open');
        if (btn) btn.innerHTML = '<i class="fas fa-bars"></i>';
        if (overlay) overlay.classList.remove('show');
    }
    
    // Đóng menu khi click link trong nav (mobile)
    document.addEventListener('DOMContentLoaded', function() {
        var nav = document.getElementById('tgddNav');
        if (nav) {
            var links = nav.querySelectorAll('a');
            for (var i = 0; i < links.length; i++) {
                links[i].addEventListener('click', function() {
                    // Delay nhẹ để link kịp navigate
                    setTimeout(closeMobileNav, 100);
                });
            }
        }
    });
    
    // Thu gọn header khi scroll (chỉ desktop)
    var isMobile = window.matchMedia('(max-width: 768px)');
    function handleScroll() {
        if (isMobile.matches) return; // Không làm gì trên mobile
        if (window.scrollY > 80) {
            document.body.classList.add('header-compact');
        } else {
            document.body.classList.remove('header-compact');
        }
    }
    window.addEventListener('scroll', handleScroll, { passive: true });
    isMobile.addEventListener('change', function() {
        if (isMobile.matches) {
            document.body.classList.remove('header-compact');
        } else {
            handleScroll();
        }
    });
    </script>

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

    <header class="tgdd-header" id="tgddHeader">
        <div class="container header-top">
            <!-- Logo -->
            <a href="index.php" class="tgdd-logo">
                <i class="fas fa-camera-retro" style="font-size:24px; margin-right:6px;"></i>
                TAN<span>DA</span>
            </a>
            
            <!-- Nút Hamburger cho Mobile -->
            <button class="hamburger-toggle" id="hamburgerBtn" onclick="toggleMobileNav()" aria-label="Menu danh mục">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Cụm Tìm Kiếm & Gợi ý -->
            <div class="search-wrapper">
                <div class="tgdd-search">
                    <form action="search.php" method="GET" style="display: flex; width: 100%; align-items: center; margin: 0;">
                        <button type="submit" style="background: none; border: none; color: #999; cursor: pointer; padding: 0 6px 0 0;"><i class="fas fa-search"></i></button>
                        <input type="text" name="q" placeholder="Bạn tìm camera gì..." style="border: none; outline: none; flex: 1; padding: 10px 8px; background: transparent; font-size: 14px;">
                    </form>
                </div>
                
                <div class="sugg-list">
                    <span style="font-weight: 700; font-size: 12px; text-transform: uppercase;">Gợi ý:</span>
                    <a href="index.php" style="color: #d70018; font-weight: bold;">Tất cả</a>
                    <?php
                    try {
                        $stmtSugg = $conn->query("SELECT slug, cat_code FROM categories WHERE status = 1 LIMIT 5");
                        $suggs = $stmtSugg->fetchAll();
                        foreach ($suggs as $s) {
                            echo '<a href="category.php?slug=' . htmlspecialchars($s['slug']) . '">' . htmlspecialchars($s['cat_code']) . '</a>';
                        }
                    } catch (Exception $e) {
                        // Lỗi DB sugg-list không nghiêm trọng, ghi log ẩn để debug
                        echo '<!-- DEBUG SUGG: ' . $e->getMessage() . ' -->';
                    }
                    ?>
                    <a href="search.php?promo=1" style="color: #d70018; font-weight: bold;">
                        <i class="fas fa-bolt"></i> Khuyến Mãi
                    </a>
                </div>
            </div>
            
            <!-- Cụm Hành động: Giỏ hàng & Vị trí -->
            <div class="header-actions">
                <a href="compare.php" class="cart-btn" style="text-decoration:none;" title="So sánh sản phẩm">
                    <div class="cart-icon-wrap">
                        <i class="fas fa-balance-scale"></i>
                        <span class="cart-badge" id="compareBadge" style="background:#288ad6;">0</span>
                    </div>
                    <span>So sánh</span>
                </a>
                <div class="cart-btn" onclick="window.location.href='cart.php'">
                    <div class="cart-icon-wrap">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge" id="cartBadge">0</span>
                    </div>
                    <span>Giỏ hàng</span>
                </div>

                <div class="location-btn">
                    <i class="fas fa-map-marker-alt" style="font-size: 15px;"></i>
                    <span>Hồ Chí Minh</span>
                    <i class="fas fa-chevron-down" style="font-size: 10px; opacity: 0.5;"></i>
                </div>
            </div>
        </div>

        <!-- Menu Danh Mục -->
        <nav class="tgdd-nav" id="tgddNav">
            <div class="container">
                <ul class="tgdd-menu-list">
                    <?php
                    try {
                        $stmtMenu = $conn->query("SELECT name, slug, icon_class, cat_code FROM categories WHERE status = 1 ORDER BY name ASC");
                        $menuItems = $stmtMenu->fetchAll();
                        foreach ($menuItems as $item) {
                            // Ưu tiên icon_class từ DB, nếu rỗng hoặc fa-tag thì tự động map theo tên
                            $icon = (!empty($item['icon_class']) && $item['icon_class'] !== 'fas fa-tag') 
                                ? $item['icon_class'] 
                                : '';
                            
                            if (empty($icon)) {
                                $nameLower = mb_strtolower($item['name'], 'UTF-8');
                                $codeLower = mb_strtolower($item['cat_code'] ?? '', 'UTF-8');
                                $combined = $nameLower . ' ' . $codeLower;
                                
                                if (strpos($combined, 'camera') !== false && strpos($combined, 'wifi') !== false) $icon = 'fas fa-wifi';
                                elseif (strpos($combined, 'camera') !== false && (strpos($combined, 'trọn') !== false || strpos($combined, 'bo') !== false)) $icon = 'fas fa-box';
                                elseif (strpos($combined, 'camera') !== false && (strpos($combined, 'ip') !== false || strpos($combined, 'ngoài') !== false)) $icon = 'fas fa-satellite-dish';
                                elseif (strpos($combined, 'camera') !== false && strpos($combined, 'quan sát') !== false) $icon = 'fas fa-eye';
                                elseif (strpos($combined, 'camera') !== false && strpos($combined, 'trong') !== false) $icon = 'fas fa-home';
                                elseif (strpos($combined, 'camera') !== false && strpos($combined, 'ẩn') !== false) $icon = 'fas fa-user-secret';
                                elseif (strpos($combined, 'camera') !== false && strpos($combined, '360') !== false) $icon = 'fas fa-sync-alt';
                                elseif (strpos($combined, 'camera') !== false && strpos($combined, 'nhiệt') !== false) $icon = 'fas fa-thermometer-half';
                                elseif (strpos($combined, 'camera') !== false) $icon = 'fas fa-camera';
                                elseif (strpos($combined, 'đầu ghi') !== false || strpos($combined, 'dau ghi') !== false || strpos($combined, 'hình') !== false) $icon = 'fas fa-hdd';
                                elseif (strpos($combined, 'mạng') !== false || strpos($combined, 'switch') !== false || strpos($combined, 'router') !== false) $icon = 'fas fa-network-wired';
                                elseif (strpos($combined, 'phụ kiện') !== false || strpos($combined, 'phu kien') !== false) $icon = 'fas fa-headphones';
                                elseif (strpos($combined, 'cáp') !== false || strpos($combined, 'dây') !== false) $icon = 'fas fa-plug';
                                elseif (strpos($combined, 'ổ cứng') !== false || strpos($combined, 'o cung') !== false) $icon = 'fas fa-database';
                                elseif (strpos($combined, 'thẻ nhớ') !== false || strpos($combined, 'the nho') !== false) $icon = 'fas fa-sd-card';
                                elseif (strpos($combined, 'màn hình') !== false || strpos($combined, 'man hinh') !== false) $icon = 'fas fa-tv';
                                elseif (strpos($combined, 'pin') !== false || strpos($combined, 'nguồn') !== false) $icon = 'fas fa-battery-full';
                                elseif (strpos($combined, 'chuông') !== false || strpos($combined, 'báo') !== false) $icon = 'fas fa-bell';
                                else $icon = 'fas fa-tag';
                            }
                            
                            $hasDropdown = (strpos(strtolower($item['name']), 'phụ kiện') !== false);
                            echo '<li class="tgdd-menu-item' . ($hasDropdown ? ' has-child' : '') . '">';
                            echo '<a href="category.php?slug=' . htmlspecialchars($item['slug']) . '">';
                            echo '<i class="' . htmlspecialchars($icon) . ' menu-icon"></i> <span>' . htmlspecialchars($item['name']) . '</span>';
                            if ($hasDropdown) echo ' <i class="fas fa-chevron-down" style="font-size:9px; margin-left:1px; opacity:0.5;"></i>';
                            echo '</a>';
                            echo '</li>';
                        }
                    } catch (Exception $e) {
                        // NẾU CÓ LỖI NÓ SẼ HIỆN CHỮ ĐỎ Ở ĐÂY CHO BẠN THẤY
                        echo '<li style="color:red; padding:20px; font-weight:bold; width:100%;">LỖI DB MENU: ' . $e->getMessage() . '</li>';
                    }
                    ?>
                </ul>
            </div>
        </nav>
    </header>