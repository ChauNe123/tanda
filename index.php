<?php 
require_once 'cores/db_config.php';

// 1. Kéo Banners từ Database
$stmtBanners = $conn->prepare("SELECT banner_code, image_file, target_link FROM banners WHERE status = 1");
$stmtBanners->execute();
$bannerList = $stmtBanners->fetchAll();
$banners = [];
foreach ($bannerList as $b) { $banners[$b['banner_code']] = $b; }

// 2. Kéo TẤT CẢ Sản phẩm đang bật lên
$stmtProds = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY sku DESC");
$stmtProds->execute();
$allProducts = $stmtProds->fetchAll();

// 3. Phân loại sản phẩm vào các nhóm để đẩy ra giao diện TANDA
$dealHotProds = [];
$camWifiProds = [];
$dauGhiProds = [];
$pcGamingProds = [];

foreach ($allProducts as $p) {
    // Nếu có giảm giá -> Quăng vô nhóm Deal Hot
    if ($p['sale_price'] > 0) { $dealHotProds[] = $p; }
    
    // Nhóm Camera Wifi (Chứa chữ WIFI trong Mã danh mục)
    if (stripos($p['cat_code'], 'WIFI') !== false) { $camWifiProds[] = $p; }
    
    // Nhóm Đầu Ghi (Chứa chữ DAU trong Mã danh mục)
    if (stripos($p['cat_code'], 'DAU') !== false) { $dauGhiProds[] = $p; }

    // Nhóm PC Gaming (Chứa chữ PC trong Mã danh mục)
    if (stripos($p['cat_code'], 'PC') !== false) { $pcGamingProds[] = $p; }
}

// Nếu nhóm nào rỗng (do chưa up CSV đủ), lấy tạm vài sp bỏ vô cho khỏi trống layout
if(empty($dealHotProds)) $dealHotProds = array_slice($allProducts, 0, 8);
if(empty($camWifiProds)) $camWifiProds = array_slice($allProducts, 0, 8);
if(empty($dauGhiProds)) $dauGhiProds = array_slice($allProducts, 0, 8);
if(empty($pcGamingProds)) $pcGamingProds = array_slice($allProducts, 0, 8);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Thế Giới Công Nghệ, Camera & Build PC Chuyên Nghiệp</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --orange-brand: #ff6600;  /* Màu Cam TANDA chủ đạo */
            --black-text: #1a1a1a;    /* Chữ đen ngầu */
            --white-bg: #ffffff;      /* Khung trắng cơ bản */
            --bg-body: #f4f6f9;       /* Nền xám nhạt để nổi khung */
            --dark-accent: #333;      /* Phối màu phụ */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }

        /* HEADER & SEARCH TÔNG TRẮNG - ĐEN - CAM */
        .top-bar { background: var(--white-bg); color: var(--text-dark); font-size: 13px; padding: 6px 0; border-bottom: 1px solid #eee; }
        .top-bar .container { display: flex; justify-content: space-between; }
        .top-bar i { color: var(--orange-brand); }

        .main-header { background: var(--white-bg); padding: 20px 0; border-bottom: 2px solid var(--orange-brand); }
        .main-header .container { display: flex; align-items: center; justify-content: space-between; gap: 30px; }
        
        /* LOGO TANDA (TAN Đen - DA Cam) */
        .logo { font-size: 34px; font-weight: 900; color: var(--black-text); letter-spacing: -1.5px; }
        .logo span { color: var(--orange-brand); }
        
        .search-bar { flex: 1; display: flex; max-width: 650px; border: 2px solid var(--orange-brand); border-radius: 6px; overflow: hidden; }
        .search-bar input { flex: 1; padding: 12px 15px; border: none; outline: none; font-size: 14px; }
        .search-bar button { background: var(--orange-brand); color: #fff; border: none; padding: 0 30px; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .search-bar button:hover { background: #e05c00; }

        .header-contact { display: flex; gap: 20px; align-items: center; }
        .contact-box { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; color: var(--black-text); }
        .contact-box i { font-size: 28px; color: var(--orange-brand); }
        .cart-btn { background: var(--white-bg); border: 2px solid #eee; padding: 10px 20px; border-radius: 6px; color: var(--orange-brand); font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; gap: 8px; align-items: center; }
        .cart-btn:hover { border-color: var(--orange-brand); }

        /* NAV BAR TÔNG TRẮNG - ĐEN - CAM */
        .nav-bar { background: var(--white-bg); border-bottom: 1px solid #eee; }
        .nav-bar .container { display: flex; }
        .nav-category { background: var(--orange-brand); color: #fff; padding: 15px 20px; font-weight: 700; width: 260px; display: flex; gap: 10px; align-items: center; text-transform: uppercase; }
        .nav-links { display: flex; align-items: center; padding-left: 20px; gap: 30px; font-weight: 600; font-size: 14px; color: var(--black-text); text-transform: uppercase; }
        .nav-links a:hover { color: var(--orange-brand); }
        .nav-links i { color: var(--orange-brand); margin-right: 5px; }

        /* HERO BANNERS (Khung Trắng) */
        .hero-section { margin-top: 20px; display: flex; gap: 15px; height: 380px; }
        .menu-trai { width: 260px; background: var(--white-bg); border: 1px solid #ddd; border-top: none; }
        .menu-trai li { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; color: var(--text-dark); }
        .menu-trai li:hover { color: var(--orange-brand); padding-left: 20px; background: #f9f9f9; }
        .banner-chinh { flex: 1; border-radius: 6px; overflow: hidden; background: #ddd; }
        .banner-chinh img { width: 100%; height: 100%; object-fit: cover; }
        .banner-phu { width: 300px; display: flex; flex-direction: column; gap: 15px; }
        .banner-phu img { width: 100%; height: calc(50% - 7.5px); object-fit: cover; border-radius: 6px; }

        /* ================= CAROUSEL & PRODUCT CARD ================= */
        .block-section { margin-top: 30px; background: var(--white-bg); border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        
        /* BANNER NỀN DEAL HOT (LƠ LỬNG) */
        .deal-hot-bg { 
            /* Lấy ảnh từ Database nếu có, mã DEAL-HOT-BG */
            background-image: url('<?php echo isset($banners['DEAL-HOT-BG']) ? 'banners/'.htmlspecialchars($banners['DEAL-HOT-BG']['image_file']) : 'https://via.placeholder.com/1200x400/333/999?text=NEN+DEAL+HOT+TANDA+(TU+NAP)'; ?>'); 
            background-size: cover; background-position: center; padding: 40px 20px 20px; border-radius: 12px; margin-top: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        /* Ribbon Title giống TTG Shop (Màu Cam) */
        .ribbon-header { display: flex; align-items: center; border-bottom: 2px solid #eee; margin-bottom: 20px; position: relative; }
        .ribbon-title { background: var(--orange-brand); color: #fff; padding: 8px 30px 8px 15px; font-size: 18px; font-weight: 800; text-transform: uppercase; display: inline-block; clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%); }
        
        /* Font chữ menu màu đen cơ bản */
        .ribbon-links { margin-left: auto; display: flex; gap: 15px; font-size: 13px; font-weight: 500; color: var(--black-text); }
        .ribbon-links a { padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px; transition: 0.2s; }
        .ribbon-links a:hover { border-color: var(--orange-brand); color: var(--orange-brand); }
        .view-all-link { color: #0056b3; font-weight: bold; margin-left: 20px; }

        /* Slider Wrapper */
        .carousel-wrap { position: relative; }
        .product-carousel { display: flex; gap: 12px; overflow-x: auto; scroll-behavior: smooth; padding: 10px 5px; scrollbar-width: none; }
        .product-carousel::-webkit-scrollbar { display: none; }
        
        /* Nút trượt Slider */
        .btn-scroll { position: absolute; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: 1px solid #ddd; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); cursor: pointer; z-index: 10; font-size: 18px; color: #555; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-scroll:hover { background: var(--orange-brand); color: #fff; border-color: var(--orange-brand); }
        .scroll-left { left: -15px; }
        .scroll-right { right: -15px; }

        /* FLOATING BUTTONS */
        .floating-action { position: fixed; right: 20px; bottom: 50px; display: flex; flex-direction: column; gap: 15px; z-index: 999; }
        .float-btn { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: 0.3s; cursor: pointer; }
        .float-btn:hover { transform: scale(1.15); }
        .float-call { background: var(--orange-brand); color: #fff; font-size: 20px; }
        .float-zalo { background: #0068ff; color: #fff; font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="container">
            <div><i class="fas fa-shield-alt"></i> Showroom TANDA - Thế Giới Máy Tính & Camera Chính Hãng</div>
            <div>
                <span style="margin-right: 20px;"><i class="fas fa-tools"></i> Tư vấn Build PC & Dịch Vụ Mạng</span>
                <span>Đăng ký | Đăng nhập</span>
            </div>
        </div>
    </div>

    <div class="main-header">
        <div class="container">
            <a href="/" class="logo">TAN<span>DA</span></a>
            
            <div class="search-bar">
                <input type="text" placeholder="Tìm Camera, PC, Phụ kiện, Mã sản phẩm...">
                <button><i class="fas fa-search"></i></button>
            </div>
            
            <div class="header-contact">
                <div class="contact-box">
                    <i class="fas fa-headset"></i>
                    <div>
                        <div style="font-size: 12px; color: #888;">Hotline mua hàng</div>
                        <div style="color: var(--black-text); font-size: 18px; font-weight: 800;">098.xxx.yyyy</div>
                    </div>
                </div>
                <button class="cart-btn"><i class="fas fa-shopping-cart"></i> Giỏ hàng (0)</button>
            </div>
        </div>
    </div>

    <div class="nav-bar">
        <div class="container">
            <div class="nav-category">
                <i class="fas fa-bars"></i> DANH MỤC SẢN PHẨM
            </div>
            <div class="nav-links">
                <a href="#"><i class="fas fa-laptop"></i> PC GAMING - ĐỒ HỌA</a>
                <a href="#"><i class="fas fa-video"></i> CAMERA WIFII</a>
                <a href="#"><i class="fas fa-hdd"></i> Ổ CỨNG, THẺ NHỚ</a>
                <a href="#"><i class="fas fa-network-wired"></i> THIẾT BỊ MẠNG</a>
            </div>
        </div>
    </div>

    <div class="container hero-section">
        <ul class="menu-trai">
            <li><i class="fas fa-desktop" style="margin-right:10px; width:15px; text-align:center;"></i> Build PC Gaming / Đồ Họa</li>
            <li><i class="fas fa-video" style="margin-right:10px; width:15px; text-align:center;"></i> Camera Ezviz Trong Nhà</li>
            <li><i class="fas fa-camera" style="margin-right:10px; width:15px; text-align:center;"></i> Camera Imou Ngoài Trời</li>
            <li><i class="fas fa-server" style="margin-right:10px; width:15px; text-align:center;"></i> Đầu Ghi Hình KBVision, Dahua</li>
            <li><i class="fas fa-print" style="margin-right:10px; width:15px; text-align:center;"></i> Máy in, Phụ kiện IT</li>
        </ul>

        <div class="banner-chinh">
            <?php if(isset($banners['BANNER-CHINH'])): ?>
                <a href="<?php echo htmlspecialchars($banners['BANNER-CHINH']['target_link']); ?>">
                    <img src="banners/<?php echo htmlspecialchars($banners['BANNER-CHINH']['image_file']); ?>" alt="Banner Chính">
                </a>
            <?php else: ?>
                <img src="https://via.placeholder.com/800x380/000/fff?text=TU+NAP+BANNER-CHINH+QUA+CSV">
            <?php endif; ?>
        </div>

        <div class="banner-phu">
            <?php if(isset($banners['BANNER-PHU-1'])): ?>
                <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-1']['image_file']); ?>">
            <?php else: ?>
                <img src="https://via.placeholder.com/300x185/eee/999?text=TU+NAP+BANNER-PHU-1">
            <?php endif; ?>

            <?php if(isset($banners['BANNER-PHU-2'])): ?>
                <img src="banners/<?php echo htmlspecialchars($banners['BANNER-PHU-2']['image_file']); ?>">
            <?php else: ?>
                <img src="https://via.placeholder.com/300x185/eee/999?text=TU+NAP+BANNER-PHU-2">
            <?php endif; ?>
        </div>
    </div>

    <div class="container deal-hot-bg">
        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-deal')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-deal')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-deal">
                <?php foreach($dealHotProds as $p): ?>
                    <?php include 'card_template.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container block-section">
        <div class="ribbon-header">
            <div class="ribbon-title">PC GAMING - ĐỒ HỌA</div>
            <div class="ribbon-links">
                <a href="#">PC Streamer</a>
                <a href="#">PC Kiến Trúc</a>
                <a href="#">PC Giả Lập</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả <i class="fas fa-angle-double-right"></i></a>
        </div>

        <div class="carousel-wrap">
            <button class="btn-scroll scroll-left" onclick="slideLeft('slider-pc')"><i class="fas fa-chevron-left"></i></button>
            <button class="btn-scroll scroll-right" onclick="slideRight('slider-pc')"><i class="fas fa-chevron-right"></i></button>
            
            <div class="product-carousel" id="slider-pc">
                <?php foreach($pcGamingProds as $p): ?>
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
                <a href="#">Imou Ngoài Trời</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả <i class="fas fa-angle-double-right"></i></a>
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
            <div class="ribbon-title">HỆ THỐNG ĐẦU GHI HÌNH</div>
            <div class="ribbon-links">
                <a href="#">Đầu 4 Kênh</a>
                <a href="#">Đầu 8 Kênh</a>
                <a href="#">Đầu IP</a>
            </div>
            <a href="#" class="view-all-link">Xem tất cả <i class="fas fa-angle-double-right"></i></a>
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

    <div class="floating-action">
        <div class="float-btn float-call" title="Gọi Ngay"><i class="fas fa-phone-alt"></i></div>
        <div class="float-btn float-zalo" title="Zalo">Z</div>
    </div>

    <script>
        // Hàm cuộn ngang thủ công bằng mũi tên
        function slideLeft(sliderId) {
            document.getElementById(sliderId).scrollBy({ left: -232, behavior: 'smooth' });
        }
        function slideRight(sliderId) {
            document.getElementById(sliderId).scrollBy({ left: 232, behavior: 'smooth' });
        }

        // TỰ ĐỘNG CHẠY BĂNG CHUYỀN (CAROUSEL)
        function autoSlide(sliderId) {
            const slider = document.getElementById(sliderId);
            if(!slider) return;
            
            let isHovered = false;
            slider.addEventListener('mouseenter', () => isHovered = true);
            slider.addEventListener('mouseleave', () => isHovered = false);

            setInterval(() => {
                if(!isHovered) {
                    let maxScroll = slider.scrollWidth - slider.clientWidth;
                    // Nếu cuộn tới cuối rồi
                    if(slider.scrollLeft >= maxScroll - 10) {
                        slider.scrollBy({ left: -maxScroll, behavior: 'smooth' }); // Quay lại đầu
                    } else {
                        slider.scrollBy({ left: 232, behavior: 'smooth' }); // Nhích qua 1 sản phẩm
                    }
                }
            }, 3500); // 3.5 giây trượt 1 nấc
        }

        // Kích hoạt auto-slide cho các khu vực
        window.onload = () => {
            autoSlide('slider-deal');
            autoSlide('slider-pc');
            autoSlide('slider-wifi');
            autoSlide('slider-dau');
        }
    </script>
</body>
</html>