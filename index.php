<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/cores/db_config.php';

// 1. Kéo Banners
$stmtBanners = $conn->prepare("SELECT banner_code, image_file, target_link FROM banners WHERE status = 1");
$stmtBanners->execute();
$bannerList = $stmtBanners->fetchAll();
$banners = [];
foreach ($bannerList as $b) { 
    $banners[$b['banner_code']] = $b; 
}

// 2. Tối ưu Query Sản Phẩm (Dùng sku làm fallback sort, không dùng id)
$limit = 10;

// Deal Hot (Giảm giá)
$stmt = $conn->prepare("SELECT * FROM products WHERE sale_price > 0  ORDER BY sort_order ASC, sku DESC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$dealHotProds = $stmt->fetchAll();

// Camera Wifi (cat_code = 'CAM-WIFI')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'CAM-WIFI'  ORDER BY sort_order ASC, sku DESC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$camWifiProds = $stmt->fetchAll();

// Camera Trọn Bộ (cat_code = 'CAM-DAY')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'CAM-DAY'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$camBoProds = $stmt->fetchAll();

// Đầu Ghi Hình (cat_code = 'DAU-GHI')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'DAU-GHI'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$dauGhiProds = $stmt->fetchAll();

// Phụ Kiện (cat_code = 'PHU-KIEN')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'PHU-KIEN'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$phuKienProds = $stmt->fetchAll();

// Thiết Bị Mạng (cat_code = 'THIET-BI-MANG')
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = 'THIET-BI-MANG'  ORDER BY sort_order ASC LIMIT :limit");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$thietBiMangProds = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Hệ Thống Phân Phối Camera & An Ninh Chính Hãng</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo rand(10000, 99999); ?>">
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
                <a href="category.php?slug=camera-wifi"><i class="fas fa-video"></i> CAMERA WIFI KHÔNG DÂY</a>
                <a href="category.php?slug=camera-tron-bo"><i class="fas fa-camera"></i> CAMERA TRỌN BỘ</a>
                <a href="category.php?slug=dau-ghi-hinh"><i class="fas fa-server"></i> ĐẦU GHI HÌNH</a>
                <a href="category.php?slug=phu-kien"><i class="fas fa-hdd"></i> THẺ NHỚ & PHỤ KIỆN</a>
                <a href="category.php?slug=thiet-bi-mang"><i class="fas fa-network-wired"></i> THIẾT BỊ MẠNG</a>
                <a href="#"><i class="fas fa-tools"></i> DỊCH VỤ LẮP ĐẶT</a> </div>
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
            <a href="search.php?q=Dahua">Trọn Bộ Dahua</a>
            <a href="search.php?q=KBVision">Trọn Bộ KBVision</a>
            <a href="search.php?q=Hikvision">Trọn Bộ Hikvision</a>
        </div>
            <a href="category.php?slug=camera-tron-bo" class="view-all-link">Xem tất cả &raquo;</a>
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
        <a href="search.php?q=Ezviz">Ezviz Trong Nhà</a>
        <a href="search.php?q=Imou">Imou Xoay 360</a>
        <a href="search.php?q=Tapo">Tapo Giá Rẻ</a>
    </div>
    <a href="category.php?slug=camera-wifi" class="view-all-link">Xem tất cả &raquo;</a>
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
        <a href="search.php?q=4 kênh">Đầu Ghi 4 Kênh</a>
        <a href="search.php?q=8 kênh">Đầu Ghi 8 Kênh</a>
        <a href="search.php?q=IP">Đầu Ghi IP NVR</a>
    </div>
    <a href="category.php?slug=dau-ghi-hinh" class="view-all-link">Xem tất cả &raquo;</a>
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
        <a href="search.php?q=Thẻ nhớ">Thẻ Nhớ Sandisk</a>
        <a href="search.php?q=Ổ cứng">Ổ Cứng Chuyên Dụng</a>
        <a href="search.php?q=Nguồn">Nguồn & Jack</a>
    </div>
    <a href="category.php?slug=phu-kien" class="view-all-link">Xem tất cả &raquo;</a>
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
        <a href="search.php?q=Router">Bộ Phát Wifi</a>
        <a href="search.php?q=Switch">Switch PoE</a>
        <a href="search.php?q=Cáp">Dây Cáp Mạng</a>
    </div>
    <a href="category.php?slug=thiet-bi-mang" class="view-all-link">Xem tất cả &raquo;</a>
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

    <script>
        // HÀM TRƯỢT SIÊU MƯỢT (Dùng chung cho cả Tự động và Bấm nút)
        function smoothScrollTo(slider, target, duration) {
            const start = slider.scrollLeft;
            const distance = target - start;
            let startTime = null;

            function animation(currentTime) {
                if (startTime === null) startTime = currentTime;
                const timeElapsed = currentTime - startTime;
                
                // Công thức tính độ mượt (chậm dần ở cuối)
                let progress = Math.min(timeElapsed / duration, 1);
                const ease = progress < 0.5 ? 2 * progress * progress : -1 + (4 - 2 * progress) * progress;
                
                slider.scrollLeft = start + distance * ease;

                if (timeElapsed < duration) {
                    requestAnimationFrame(animation);
                }
            }
            requestAnimationFrame(animation);
        }

        // ĐỘNG CƠ BĂNG CHUYỀN
        function setupPremiumSlider(sliderId) {
            const slider = document.getElementById(sliderId);
            if (!slider) return;

            slider.style.scrollBehavior = 'auto'; // Tắt cuộn mặc định

            // Nhân bản nội dung để lặp vô tận
            const originalHTML = slider.innerHTML;
            slider.innerHTML += originalHTML;

            let isHovered = false;
            slider.addEventListener('mouseenter', () => { isHovered = true; });
            slider.addEventListener('mouseleave', () => { isHovered = false; });

            // Tự động chạy sau mỗi 3 giây
            setInterval(() => {
                if (!isHovered) {
                    const originalWidth = slider.scrollWidth / 2;
                    if (slider.scrollLeft >= originalWidth) {
                        slider.scrollLeft -= originalWidth;
                    }
                    // Tự động trượt trong 800ms
                    smoothScrollTo(slider, slider.scrollLeft + 232, 2000); 
                }
            }, 3000);
        }

        // XỬ LÝ NÚT BẤM (Cũng trượt mượt mà trong 500ms)
        function slideLeft(sliderId) { 
            const slider = document.getElementById(sliderId);
            if (slider) smoothScrollTo(slider, slider.scrollLeft - 232, 500);
        }
        function slideRight(sliderId) { 
            const slider = document.getElementById(sliderId);
            if (slider) smoothScrollTo(slider, slider.scrollLeft + 232, 500);
        }

        // Khởi động mọi dải sản phẩm
        window.onload = () => {
            setupPremiumSlider('slider-deal');
            setupPremiumSlider('slider-bo');
            setupPremiumSlider('slider-wifi');
            setupPremiumSlider('slider-dau');
            setupPremiumSlider('slider-phu-kien');
            setupPremiumSlider('slider-mang');
            setupPremiumSlider('slider-thiet-bi-mang');
        }
    </script>

    <?php include 'includes/footer.php'; ?>