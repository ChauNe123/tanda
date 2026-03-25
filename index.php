<?php 
require_once 'cores/db_config.php';

// 1. Kéo Banners từ Database
$stmtBanners = $conn->prepare("SELECT banner_code, image_file, target_link FROM banners WHERE status = 1");
$stmtBanners->execute();
$bannerList = $stmtBanners->fetchAll();
$banners = [];
foreach ($bannerList as $b) { $banners[$b['banner_code']] = $b; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Hệ Thống Phân Phối Camera & Thiết Bị IT</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --orange-brand: #ff5722; /* Màu cam TTG / TANDA */
            --dark-bg: #003028;      /* Màu xanh đen tối của topbar TTG */
            --black-text: #222;
            --bg-body: #f4f6f9;
            --border-color: #e0e0e0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }
        body { background-color: var(--bg-body); color: var(--black-text); }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 10px; }

        /* ================= TOP BAR ================= */
        .top-bar { background: var(--dark-bg); color: #fff; padding: 6px 0; font-size: 13px; font-weight: 500; }
        .top-bar .container { display: flex; align-items: center; justify-content: flex-start; gap: 15px; }
        .top-badge { background: rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 15px; display: flex; align-items: center; gap: 5px; cursor: pointer; transition: 0.2s; }
        .top-badge:hover { background: var(--orange-brand); }
        .top-badge i { font-size: 14px; }

        /* ================= MAIN HEADER ================= */
        .main-header { background: #fff; padding: 25px 0 15px 0; }
        .main-header .container { display: flex; align-items: flex-start; justify-content: space-between; gap: 30px; }
        
        /* Logo */
        .logo-area { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .logo-text { font-size: 38px; font-weight: 900; letter-spacing: -1px; color: var(--black-text); line-height: 1; }
        .logo-text span { color: var(--orange-brand); }

        /* Search Area */
        .search-area { flex: 1; max-width: 600px; display: flex; flex-direction: column; gap: 8px; }
        .search-box { display: flex; border: 2px solid var(--orange-brand); border-radius: 6px; overflow: hidden; height: 42px; }
        .search-box select { border: none; outline: none; padding: 0 10px; background: #f9f9f9; border-right: 1px solid #ddd; color: #555; font-size: 13px; font-weight: 500; cursor: pointer; }
        .search-box input { flex: 1; border: none; outline: none; padding: 0 15px; font-size: 14px; }
        .search-box button { background: var(--orange-brand); color: #fff; border: none; width: 50px; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .search-box button:hover { background: #e64a19; }
        /* Dòng gợi ý dưới thanh tìm kiếm */
        .search-suggest { display: flex; gap: 15px; font-size: 12px; color: #777; }
        .search-suggest a:hover { color: var(--orange-brand); }

        /* Contact & Cart */
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .contact-box { display: flex; align-items: center; gap: 10px; }
        .contact-box i { font-size: 28px; color: #999; }
        .contact-info { display: flex; flex-direction: column; }
        .contact-info .title { font-size: 12px; color: #666; }
        .contact-info .phone { font-size: 16px; font-weight: 800; color: var(--black-text); }
        
        .cart-box { display: flex; align-items: center; gap: 8px; border: 1px solid #ddd; padding: 8px 15px; border-radius: 20px; cursor: pointer; transition: 0.2s; font-weight: 600; font-size: 14px; }
        .cart-box i { font-size: 18px; color: var(--black-text); }
        .cart-box .count { color: var(--orange-brand); }
        .cart-box:hover { border-color: var(--orange-brand); color: var(--orange-brand); }
        .cart-box:hover i { color: var(--orange-brand); }

        /* ================= NAVIGATION BAR ================= */
        .nav-bar { background: #fff; border-bottom: 2px solid #eee; }
        .nav-bar .container { display: flex; align-items: center; }
        
        .nav-category { background: var(--dark-bg); color: #fff; padding: 0 20px; font-weight: 700; width: 260px; height: 50px; display: flex; gap: 15px; align-items: center; text-transform: uppercase; cursor: pointer; border-radius: 6px 6px 0 0; }
        .nav-category i { font-size: 18px; }

        .nav-links { display: flex; align-items: center; justify-content: space-between; flex: 1; padding-left: 20px; height: 50px; }
        .nav-links a { font-weight: 600; font-size: 13.5px; color: var(--black-text); text-transform: uppercase; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
        .nav-links a i { font-size: 15px; color: #666; }
        .nav-links a:hover { color: var(--orange-brand); }
        .nav-links a:hover i { color: var(--orange-brand); }

        /* ================= BANNERS (CỤM HÌNH LỚN) ================= */
        .banner-section { margin-top: 15px; margin-bottom: 40px; }
        
        /* Banner siêu to ở trên */
        .banner-top { width: 100%; border-radius: 8px; overflow: hidden; margin-bottom: 10px; display: block; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .banner-top img { width: 100%; display: block; object-fit: cover; }
        
        /* 3 Banner nhỏ nằm ngang ở dưới */
        .banner-bottom-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .banner-item { border-radius: 8px; overflow: hidden; display: block; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.3s; }
        .banner-item:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
        .banner-item img { width: 100%; height: 100%; display: block; object-fit: cover; }

    </style>
</head>
<body>

    <div class="top-bar">
        <div class="container">
            <div class="top-badge"><i class="fas fa-map-marker-alt"></i> Hệ thống showroom</div>
            <div class="top-badge" style="background: transparent;"><i class="fas fa-phone-alt"></i> Bán hàng trực tuyến</div>
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
                        <option>Camera Có Dây</option>
                        <option>Đầu Ghi Hình</option>
                    </select>
                    <input type="text" placeholder="Tìm kiếm camera, đầu ghi, phụ kiện...">
                    <button type="button"><i class="fas fa-search"></i></button>
                </form>
                <div class="search-suggest">
                    <a href="#">Camera Wifi</a>
                    <a href="#">Camera Trọn Bộ</a>
                    <a href="#">Đầu Ghi Hình</a>
                    <a href="#">Ổ Cứng - Thẻ Nhớ</a>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="contact-box">
                    <i class="fas fa-headset"></i>
                    <div class="contact-info">
                        <span class="title">Hotline mua hàng</span>
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
                <a href="#"><i class="fas fa-video"></i> CAMERA WIFI</a>
                <a href="#"><i class="fas fa-camera"></i> CAMERA TRỌN BỘ</a>
                <a href="#"><i class="fas fa-server"></i> ĐẦU GHI HÌNH</a>
                <a href="#"><i class="fas fa-network-wired"></i> THIẾT BỊ MẠNG</a>
                <a href="#"><i class="fas fa-hdd"></i> Ổ CỨNG - THẺ NHỚ</a>
                <a href="#"><i class="fas fa-tools"></i> PHỤ KIỆN LẮP ĐẶT</a>
            </div>
        </div>
    </nav>

    <section class="banner-section container">
        
        <a href="<?php echo isset($banners['BANNER-CHINH']) ? htmlspecialchars($banners['BANNER-CHINH']['target_link']) : '#'; ?>" class="banner-top">
            <?php if(isset($banners['BANNER-CHINH'])): ?>
                <img src="banners/<?php echo htmlspecialchars($banners['BANNER-CHINH']['image_file']); ?>" alt="Banner Chính">
            <?php else: ?>
                <img src="https://via.placeholder.com/1200x350/ff5722/ffffff?text=BANNER-CHINH+(1200x350)" alt="Trống">
            <?php endif; ?>
        </a>

        <div class="banner-bottom-row">
            
            <a href="<?php echo isset($banners['BANNER-PHU-1']) ? htmlspecialchars($banners['BANNER-PHU-1']['target_link']) : '#'; ?>" class="banner-item">
                <?php if(isset($banners['BANNER-PHU-1'])): ?>
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-1']['image_file']); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x150/003028/ffffff?text=BANNER-PHU-1+(400x150)">
                <?php endif; ?>
            </a>

            <a href="<?php echo isset($banners['BANNER-PHU-2']) ? htmlspecialchars($banners['BANNER-PHU-2']['target_link']) : '#'; ?>" class="banner-item">
                <?php if(isset($banners['BANNER-PHU-2'])): ?>
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-2']['image_file']); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x150/003028/ffffff?text=BANNER-PHU-2+(400x150)">
                <?php endif; ?>
            </a>

            <a href="<?php echo isset($banners['BANNER-PHU-3']) ? htmlspecialchars($banners['BANNER-PHU-3']['target_link']) : '#'; ?>" class="banner-item">
                <?php if(isset($banners['BANNER-PHU-3'])): ?>
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-3']['image_file']); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x150/003028/ffffff?text=BANNER-PHU-3+(400x150)">
                <?php endif; ?>
            </a>

        </div>
    </section>

</body>
</html>