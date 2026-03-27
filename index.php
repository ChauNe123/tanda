<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'cores/db_config.php';
// ... code cũ của ông ...
require_once 'cores/db_config.php';

// 1. Kéo Banners từ Database
$stmtBanners = $conn->prepare("SELECT banner_code, image_file, target_link FROM banners WHERE status = 1");
$stmtBanners->execute();
$bannerList = $stmtBanners->fetchAll();
$banners = [];
foreach ($bannerList as $b) { $banners[$b['banner_code']] = $b; }

// 2. Kéo TẤT CẢ Sản phẩm (kể cả hết hàng)
$stmtProds = $conn->prepare("SELECT * FROM products");
$stmtProds->execute();
$allProducts = $stmtProds->fetchAll();

// 3. Phân loại sản phẩm vào các nhóm để đẩy ra giao diện TANDA
$dealHotProds = [];
$camWifiProds = [];
$camBoProds = [];
$dauGhiProds = [];
$phuKienProds = [];
$thietBiMangProds = [];

foreach ($allProducts as $p) {
    if ($p['sale_price'] > 0) { $dealHotProds[] = $p; }
    if (stripos($p['cat_code'], 'WIFI') !== false) { $camWifiProds[] = $p; }
    if (stripos($p['cat_code'], 'BO') !== false || stripos($p['cat_code'], 'DAY') !== false) { $camBoProds[] = $p; }
    if (stripos($p['cat_code'], 'DAU') !== false) { $dauGhiProds[] = $p; }
    if (stripos($p['cat_code'], 'PHU-KIEN') !== false) { $phuKienProds[] = $p; }
    if (stripos($p['cat_code'], 'MANG') !== false) { $thietBiMangProds[] = $p; }
}

// Lấy mồi vài sản phẩm nếu các nhóm bị trống
if(empty($dealHotProds)) $dealHotProds = array_slice($allProducts, 0, 8);
if(empty($camWifiProds)) $camWifiProds = array_slice($allProducts, 0, 8);
if(empty($camBoProds)) $camBoProds = array_slice($allProducts, 0, 8);
if(empty($dauGhiProds)) $dauGhiProds = array_slice($allProducts, 0, 8);
if(empty($phuKienProds)) $phuKienProds = array_slice($allProducts, 0, 8);
if(empty($thietBiMangProds)) $thietBiMangProds = array_slice($allProducts, 0, 8);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Hệ Thống Phân Phối Camera & An Ninh Chính Hãng</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --orange-brand: #ff5722; 
            --dark-bg: #003028;      
            --black-text: #222;
            --bg-body: #f4f6f9;
            --white-bg: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }
        body { background-color: var(--bg-body); color: var(--black-text); overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 10px; }

        /* ================= TOP BAR & HEADER ================= */
        .top-bar { background: var(--dark-bg); color: #fff; padding: 6px 0; font-size: 13px; font-weight: 500; }
        .top-bar .container { display: flex; align-items: center; justify-content: flex-start; gap: 15px; }
        .top-badge { background: rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 15px; display: flex; align-items: center; gap: 5px; cursor: pointer; transition: 0.2s; }
        .top-badge:hover { background: var(--orange-brand); }
        
        .main-header { background: var(--white-bg); padding: 25px 0 15px 0; }
        .main-header .container { display: flex; align-items: flex-start; justify-content: space-between; gap: 30px; }
        .logo-area { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .logo-text { font-size: 38px; font-weight: 900; letter-spacing: -1px; color: var(--black-text); line-height: 1; }
        .logo-text span { color: var(--orange-brand); }
        
        .search-area { flex: 1; max-width: 600px; display: flex; flex-direction: column; gap: 8px; }
        .search-box { display: flex; border: 2px solid var(--orange-brand); border-radius: 6px; overflow: hidden; height: 42px; }
        .search-box select { border: none; outline: none; padding: 0 10px; background: #f9f9f9; border-right: 1px solid #ddd; color: #555; font-size: 13px; font-weight: 500; cursor: pointer; }
        .search-box input { flex: 1; border: none; outline: none; padding: 0 15px; font-size: 14px; }
        .search-box button { background: var(--orange-brand); color: #fff; border: none; width: 50px; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .search-box button:hover { background: #e64a19; }
        .search-suggest { display: flex; gap: 15px; font-size: 12px; color: #777; }
        .search-suggest a:hover { color: var(--orange-brand); }
        
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .contact-box { display: flex; align-items: center; gap: 10px; }
        .contact-box i { font-size: 28px; color: #999; }
        .contact-info { display: flex; flex-direction: column; }
        .contact-info .title { font-size: 12px; color: #666; }
        .contact-info .phone { font-size: 16px; font-weight: 800; color: var(--black-text); }
        .cart-box { display: flex; align-items: center; gap: 8px; border: 1px solid #ddd; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .cart-box .count { color: var(--orange-brand); }
        .cart-box:hover { border-color: var(--orange-brand); color: var(--orange-brand); }

        /* ================= NAV BAR ================= */
        .nav-bar { background: var(--white-bg); border-bottom: 2px solid #eee; }
        .nav-bar .container { display: flex; align-items: center; }
        .nav-category { background: var(--dark-bg); color: #fff; padding: 0 20px; font-weight: 700; width: 260px; height: 50px; display: flex; gap: 15px; align-items: center; text-transform: uppercase; cursor: pointer; border-radius: 6px 6px 0 0; }
        .nav-links { display: flex; align-items: center; justify-content: space-between; flex: 1; padding-left: 20px; height: 50px; }
        .nav-links a { font-weight: 600; font-size: 13.5px; color: var(--black-text); text-transform: uppercase; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
        .nav-links a i { font-size: 15px; color: #666; }
        .nav-links a:hover, .nav-links a:hover i { color: var(--orange-brand); }

        /* ================= BANNERS (CỤM HÌNH LỚN) ================= */
        .banner-section { margin-top: 15px; margin-bottom: 30px; }
        .banner-top { width: 100%; border-radius: 8px; overflow: hidden; margin-bottom: 10px; display: block; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .banner-top img { width: 100%; display: block; object-fit: cover; }
        .banner-bottom-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .banner-item { border-radius: 8px; overflow: hidden; display: block; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.3s; }
        .banner-item:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
        .banner-item img { width: 100%; height: 100%; display: block; object-fit: cover; }

       /* ================= 0. DEAL HOT MỖI NGÀY ================= */
        .deal-hot-bg { 
            background: linear-gradient(135deg, #7b2ff7 0%, #2a8bf2 100%);
            background-size: cover; background-position: center; 
            padding: 20px; border-radius: 12px; margin-bottom: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .deal-hot-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 10px; }
        .deal-hot-title { font-size: 26px; font-weight: 900; color: #ffeb3b; text-transform: uppercase; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); font-style: italic; letter-spacing: 1px; }
        .hot-sale-badge { background: #ffeb3b; color: #d32f2f; padding: 6px 15px; font-weight: 900; border-radius: 4px; font-size: 14px; }
        .btn-view-deal { display: block; width: 140px; margin: 20px auto 0; text-align: center; background: #ff5722; color: #fff; padding: 8px 0; border-radius: 20px; font-weight: bold; font-size: 14px; transition: 0.3s; border: 2px solid #fff; }
        .btn-view-deal:hover { background: #fff; color: #ff5722; border-color: #ff5722; }

        /* ================= 1. KHUNG DANH MỤC CƠ BẢN ================= */
        .block-section { margin-bottom: 40px; background: var(--white-bg); border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .ribbon-header { display: flex; align-items: center; border-bottom: 2px solid #f0f0f0; margin-bottom: 20px; position: relative; padding-bottom: 10px; }
        .ribbon-title { background: var(--orange-brand); color: #fff; padding: 8px 30px 8px 15px; font-size: 18px; font-weight: 800; text-transform: uppercase; clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%); margin-right: 20px; }
        .ribbon-links { display: flex; gap: 10px; font-size: 13px; font-weight: 500; }
        .ribbon-links a { background: #f4f6f9; color: #555; padding: 6px 12px; border-radius: 4px; transition: 0.2s; border: 1px solid #eee; }
        .ribbon-links a:hover { background: var(--orange-brand); color: #fff; border-color: var(--orange-brand); }
        .view-all-link { margin-left: auto; color: #0056b3; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .view-all-link:hover { color: var(--orange-brand); text-decoration: underline; }

        /* ================= SLIDER & CAROUSEL ================= */
        .carousel-wrap { position: relative; }
        .product-carousel { display: flex; gap: 12px; overflow-x: auto; scroll-behavior: smooth; padding: 10px 5px; scrollbar-width: none; }
        .product-carousel::-webkit-scrollbar { display: none; }
        .btn-scroll { position: absolute; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: 1px solid #ddd; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); cursor: pointer; z-index: 10; font-size: 18px; color: #555; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-scroll:hover { background: var(--orange-brand); color: #fff; border-color: var(--orange-brand); }
        .scroll-left { left: -15px; }
        .scroll-right { right: -15px; }

        /* ================= CSS GOM CHUNG CHO THẺ SẢN PHẨM ================= */
        .product-card { background: var(--white-bg); border: 1px solid #eee; border-radius: 6px; padding: 12px; position: relative; transition: all 0.3s ease; display: flex; flex-direction: column; min-width: 220px; max-width: 220px; flex: 0 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .product-card:hover { border-color: var(--orange-brand); box-shadow: 0 8px 25px rgba(0,0,0,0.12); transform: translateY(-5px); z-index: 2; }
        .card-img { width: 100%; height: 180px; position: relative; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; overflow: hidden; background: #fff; }
        .card-img img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.4s ease; }
        .product-card:hover .card-img img { transform: scale(1.12); } 
        .discount-badge { position: absolute; top: 0; left: 0; background: var(--orange-brand); color: #fff; font-size: 12px; font-weight: bold; padding: 4px 8px; border-radius: 4px; z-index: 3; clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%); }
        .card-title { font-size: 14px; color: var(--black-text); line-height: 1.4; margin-bottom: 8px; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; }
        .product-card:hover .card-title { color: var(--orange-brand); }
        .price-box { margin-bottom: 15px; flex-grow: 1; }
        .price-new { display: block; color: #d70018; font-size: 19px; font-weight: 800; margin-bottom: 2px; }
        .price-old { display: block; color: #999; font-size: 13px; text-decoration: line-through; }
        .btn-buy { width: 100%; background: #fff; color: var(--orange-brand); border: 2px solid var(--orange-brand); padding: 9px 0; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.2s ease; display: flex; justify-content: center; align-items: center; gap: 8px; text-transform: uppercase; }
        .product-card:hover .btn-buy { background: var(--orange-brand); color: #fff; }
        .btn-buy:active { transform: scale(0.95); }
        .status-badge { position: absolute; bottom: 22px; right: 12px; font-size: 11px; color: #28a745; font-weight: 600; pointer-events: none; }
    </style>
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
            <a href="/" class="logo-area">
                <div class="logo-text">TAN<span>DA</span></div>
            </a>
            
            <div class="search-area">
                <form class="search-box">
                    <select>
                        <option>Tất cả danh mục</option>
                        <option>Camera Wifi</option>
                        <option>Camera Trọn Bộ</option>
                        <option>Đầu Ghi Hình</option>
                    </select>
                    <input type="text" placeholder="Tìm kiếm mã camera, đầu ghi, thẻ nhớ...">
                    <button type="button"><i class="fas fa-search"></i></button>
                </form>
                <div class="search-suggest">
                    <a href="#">Camera Ezviz</a>
                    <a href="#">Camera Imou</a>
                    <a href="#">Trọn bộ Dahua</a>
                    <a href="#">Ổ cứng chuyên dụng</a>
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
                <div class="cart-box">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng <span class="count">(0)</span>
                </div>
            </div>
        </div>
    </header>

    <nav class="nav-bar">
        <div class="container">
            <div class="nav-category">
                <i class="fas fa-bars"></i> DANH MỤC SẢN PHẨM
            </div>
            <div class="nav-links">
                <a href="#"><i class="fas fa-video"></i> CAMERA WIFI KHÔNG DÂY</a>
                <a href="#"><i class="fas fa-camera"></i> CAMERA TRỌN BỘ</a>
                <a href="#"><i class="fas fa-server"></i> ĐẦU GHI HÌNH</a>
                <a href="#"><i class="fas fa-hdd"></i> THẺ NHỚ & Ổ CỨNG</a>
                <a href="#"><i class="fas fa-network-wired"></i> THIẾT BỊ MẠNG</a>
                <a href="#"><i class="fas fa-tools"></i> DỊCH VỤ LẮP ĐẶT</a>
            </div>
        </div>
    </nav>

    <section class="banner-section container">
        <a href="<?php echo isset($banners['BANNER-CHINH']) ? htmlspecialchars($banners['BANNER-CHINH']['target_link']) : '#'; ?>" class="banner-top">
            <?php if(isset($banners['BANNER-CHINH'])): ?>
                <img src="banners/<?php echo htmlspecialchars($banners['BANNER-CHINH']['image_file']); ?>?v=<?php echo time(); ?>" alt="Banner Chính">
            <?php else: ?>
                <img src="https://via.placeholder.com/1200x350/ff5722/ffffff?text=BANNER-CHINH+(1200x350)" alt="Trống">
            <?php endif; ?>
        </a>
        <div class="banner-bottom-row">
            <a href="<?php echo isset($banners['BANNER-PHU-1']) ? htmlspecialchars($banners['BANNER-PHU-1']['target_link']) : '#'; ?>" class="banner-item">
                <?php if(isset($banners['BANNER-PHU-1'])): ?>
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-1']['image_file']); ?>?v=<?php echo time(); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x150/003028/ffffff?text=BANNER-PHU-1+(400x150)">
                <?php endif; ?>
            </a>
            <a href="<?php echo isset($banners['BANNER-PHU-2']) ? htmlspecialchars($banners['BANNER-PHU-2']['target_link']) : '#'; ?>" class="banner-item">
                <?php if(isset($banners['BANNER-PHU-2'])): ?>
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-2']['image_file']); ?>?v=<?php echo time(); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x150/003028/ffffff?text=BANNER-PHU-2+(400x150)">
                <?php endif; ?>
            </a>
            <a href="<?php echo isset($banners['BANNER-PHU-3']) ? htmlspecialchars($banners['BANNER-PHU-3']['target_link']) : '#'; ?>" class="banner-item">
                <?php if(isset($banners['BANNER-PHU-3'])): ?>
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-3']['image_file']); ?>?v=<?php echo time(); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x150/003028/ffffff?text=BANNER-PHU-3+(400x150)">
                <?php endif; ?>
            </a>
        </div>
    </section>

    <div class="container deal-hot-bg" <?php if(isset($banners['DEAL-HOT-BG'])) echo "style=\"background-image: url('banners/".htmlspecialchars($banners['DEAL-HOT-BG']['image_file'])."?v=".time()."');\""; ?>>
        <div class="deal-hot-header">
            <div class="deal-hot-title">DEAL HOT MỖI NGÀY - KHUYẾN MÃI LIỀN TAY</div>
            <div class="hot-sale-badge">HOT SALE</div>
        </div>
        
        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-deal')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-deal')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-deal">
                <?php foreach($dealHotProds as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <a href="#" class="btn-view-deal">Xem tất cả <i class="fas fa-angle-double-right"></i></a>
    </div>

    <div class="container block-section">
        <div class="ribbon-header">
            <div class="ribbon-title">CAMERA TRỌN BỘ CÓ DÂY</div>
            <div class="ribbon-links">
                <a href="#">Trọn Bộ Dahua 2.0MP</a>
                <a href="#">Trọn Bộ KBVision</a>
                <a href="#">Trọn Bộ Hikvision</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả &raquo;</a>
        </div>

        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-bo')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-bo')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-bo">
                <?php foreach($camBoProds as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container block-section">
        <div class="ribbon-header">
            <div class="ribbon-title">CAMERA WIFI GIÁ RẺ</div>
            <div class="ribbon-links">
                <a href="#">Ezviz Trong Nhà</a>
                <a href="#">Imou Xoay 360</a>
                <a href="#">Tapo Giá Rẻ</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả &raquo;</a>
        </div>

        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-wifi')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-wifi')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-wifi">
                <?php foreach($camWifiProds as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container block-section" style="margin-bottom: 60px;">
        <div class="ribbon-header">
            <div class="ribbon-title">ĐẦU GHI HÌNH CAMERA</div>
            <div class="ribbon-links">
                <a href="#">Đầu Ghi 4 Kênh</a>
                <a href="#">Đầu Ghi 8 Kênh</a>
                <a href="#">Đầu Ghi IP NVR</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả &raquo;</a>
        </div>

        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-dau')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-dau')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-dau">
                <?php foreach($dauGhiProds as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container block-section" style="margin-bottom: 60px;">
        <div class="ribbon-header">
            <div class="ribbon-title">PHỤ KIỆN CAMERA</div>
            <div class="ribbon-links">
                <a href="#">Thẻ Nhớ Sandisk</a>
                <a href="#">Ổ Cứng Chuyên Dụng</a>
                <a href="#">Nguồn & Jack</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả &raquo;</a>
        </div>

        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-phu-kien')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-phu-kien')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-phu-kien">
                <?php foreach($phuKienProds as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container block-section" style="margin-bottom: 60px;">
        <div class="ribbon-header">
            <div class="ribbon-title">THIẾT BỊ MẠNG</div>
            <div class="ribbon-links">
                <a href="#">Bộ Phát Wifi</a>
                <a href="#">Switch PoE</a>
                <a href="#">Dây Cáp Mạng</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả &raquo;</a>
        </div>

        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-mang')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-mang')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-mang">
                <?php foreach($thietBiMangProds as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container block-policy-section">
    <div class="policy-box-wrapper">
        <div class="policy-box">
            <div class="policy-item">
                <i class="fas fa-truck policy-icon"></i>
                <h4 class="policy-title">GIAO HÀNG TOÀN QUỐC</h4>
                <p class="policy-desc">Giao hàng trước, trả tiền sau COD</p>
            </div>
            <div class="policy-item">
                <i class="fas fa-box-open policy-icon"></i>
                <h4 class="policy-title">ĐỔI TRẢ DỄ DÀNG</h4>
                <p class="policy-desc">Đổi mới trong 30 ngày đầu</p>
            </div>
            <div class="policy-item">
                <i class="fas fa-credit-card policy-icon"></i>
                <h4 class="policy-title">THANH TOÁN TIỆN LỢI</h4>
                <p class="policy-desc">Trả tiền mặt, chuyển khoản, trả góp 0%</p>
            </div>
            <div class="policy-item">
                <i class="fas fa-headset policy-icon"></i>
                <h4 class="policy-title">HỖ TRỢ NHIỆT TÌNH</h4>
                <p class="policy-desc">Tư vấn tổng đài miễn phí 24/7</p>
            </div>
        </div>
    </div>

    <div class="commitment-text">
        <p class="cm-subtitle">Trải nghiệm mua sắm tại <span class="cm-brand">TANDA</span></p>
        <h3 class="cm-title">Cam Kết 100% <span class="cm-highlight">Hài Lòng</span></h3>
    </div>
</div>

    <script>
        function slideLeft(sliderId) { document.getElementById(sliderId).scrollBy({ left: -232, behavior: 'smooth' }); }
        function slideRight(sliderId) { document.getElementById(sliderId).scrollBy({ left: 232, behavior: 'smooth' }); }

        function autoSlide(sliderId) {
            const slider = document.getElementById(sliderId);
            if(!slider) return;
            let isHovered = false;
            slider.addEventListener('mouseenter', () => isHovered = true);
            slider.addEventListener('mouseleave', () => isHovered = false);

            setInterval(() => {
                if(!isHovered) {
                    let maxScroll = slider.scrollWidth - slider.clientWidth;
                    if(slider.scrollLeft >= maxScroll - 10) {
                        slider.scrollBy({ left: -maxScroll, behavior: 'smooth' }); 
                    } else {
                        slider.scrollBy({ left: 232, behavior: 'smooth' }); 
                    }
                }
            }, 3500); 
        }

        window.onload = () => {
            autoSlide('slider-deal');
            autoSlide('slider-bo');
            autoSlide('slider-wifi');
            autoSlide('slider-dau');
            autoSlide('slider-phu-kien');
            autoSlide('slider-mang');
        }
    </script>
</body>
</html>