<?php 
// 1. Kết nối Database
require_once 'cores/db_config.php';

// 2. Lấy dữ liệu Banner từ Database
$stmtBanners = $conn->prepare("SELECT banner_code, image_file, target_link FROM banners WHERE status = 1");
$stmtBanners->execute();
$bannerList = $stmtBanners->fetchAll();

// Tạo một mảng banner dễ gọi (Ví dụ gọi: $banners['BANNER-CHINH'])
$banners = [];
foreach ($bannerList as $b) {
    $banners[$b['banner_code']] = $b;
}

// 3. Lấy danh sách Sản Phẩm (Mới nhất lên đầu)
$stmtProds = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY sku DESC LIMIT 12");
$stmtProds->execute();
$products = $stmtProds->fetchAll();

// 4. Gọi Header (Thanh menu trên cùng)
include 'includes/header.php'; 
?>

<section class="container hero-section">
    <div class="hero-layout">
        <div class="menu-trai">
            <ul>
                <li>👉 Camera Wifi Không Dây</li>
                <li>👉 Camera Trọn Bộ (Có Dây)</li>
                <li>👉 Đầu Ghi Hình Chính Hãng</li>
                <li>👉 Thẻ Nhớ & Ổ Cứng</li>
                <li>👉 Phụ Kiện Lắp Đặt</li>
            </ul>
        </div>
        
        <div class="banner-chinh">
            <?php if(isset($banners['BANNER-CHINH'])): ?>
                <a href="<?php echo htmlspecialchars($banners['BANNER-CHINH']['target_link']); ?>">
                    <img src="uploads/<?php echo htmlspecialchars($banners['BANNER-CHINH']['image_file']); ?>" alt="Banner Chính">
                </a>
            <?php else: ?>
                <img src="https://via.placeholder.com/800x400?text=CHUA+UP+BANNER+CHINH" alt="Chưa có banner">
            <?php endif; ?>
        </div>
        
        <div class="banner-phu">
            <?php if(isset($banners['BANNER-PHU-1'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($banners['BANNER-PHU-1']['image_file']); ?>" alt="Banner phụ 1">
            <?php endif; ?>
            <?php if(isset($banners['BANNER-PHU-2'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($banners['BANNER-PHU-2']['image_file']); ?>" alt="Banner phụ 2">
            <?php endif; ?>
        </div>
    </div>
</section>

<?php 
// Lấy ảnh nền Flash Sale từ DB, nếu không có thì xài màu đỏ tĩnh
$flashSaleBg = isset($banners['FLASH-SALE-BG']) ? 'uploads/' . htmlspecialchars($banners['FLASH-SALE-BG']['image_file']) : '';
?>
<section class="flash-sale-bg" style="<?php echo !empty($flashSaleBg) ? "background-image: url('$flashSaleBg');" : "background-color: #d92515;"; ?>">
    <div class="container">
        <div class="title-img">
            <h2>⚡ ĐANG DIỄN RA - GIÁ TỐT CHỐT NGAY ⚡</h2>
        </div>
        
        <div class="product-grid">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $p): ?>
                <div class="product-card">
                    <div class="img-wrap">
                        <a href="san-pham/<?php echo htmlspecialchars($p['slug']); ?>">
                            <img src="uploads/<?php echo htmlspecialchars($p['image_file']); ?>" class="sp-goc" alt="<?php echo htmlspecialchars($p['name']); ?>">
                            
                            <?php if(!empty($p['frame_file'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($p['frame_file']); ?>" class="sp-vien" alt="khung">
                            <?php endif; ?>
                        </a>
                        
                        <?php if($p['sale_price'] > 0 && $p['price'] > $p['sale_price']): ?>
                            <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
                            <span class="discount-badge">-<?php echo $percent; ?>%</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info">
                        <a href="san-pham/<?php echo htmlspecialchars($p['slug']); ?>">
                            <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                        </a>
                        <div class="price-area">
                            <?php if($p['sale_price'] > 0): ?>
                                <span class="price-new"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</span>
                                <span class="price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
                            <?php else: ?>
                                <span class="price-new"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-zalo" onclick="orderViaZalo('<?php echo htmlspecialchars($p['name']); ?>', '<?php echo $p['sale_price'] > 0 ? $p['sale_price'] : $p['price']; ?>')">💬 Chốt đơn qua Zalo</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#fff; text-align:center; width:100%; font-size:18px;">Chưa có sản phẩm nào trong kho. Hãy nạp file CSV!</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php 
// 5. Gọi Footer
include 'includes/footer.php'; 
?>