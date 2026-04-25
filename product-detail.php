<?php
// product-detail.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Kết nối DB
require_once 'cores/db_config.php';

// 2. Bắt biến slug (Đã fix lỗi dấu /)
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: index.php"); 
    exit;
}

// 3. Query lấy SP bằng SLUG
$stmt = $conn->prepare("SELECT * FROM products WHERE slug = :slug AND status = 1 LIMIT 1");
$stmt->execute(['slug' => $slug]);
$p = $stmt->fetch();

if (!$p) {
    include 'includes/header.php';
    echo '<main class="container pd-container"><h2 style="text-align:center; padding: 100px 0; color: #888;">😥 Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.</h2></main>';
    include 'includes/footer.php';
    exit;
}

// 4. Lấy Tên Danh Mục (cho breadcrumb)
$cat_slug = 'camera-wifi';
$cat_name = 'Camera WiFi';
if($p['cat_code'] === 'CAM-DAY') { $cat_slug = 'camera-tron-bo'; $cat_name = 'Camera Trọn Bộ'; }
elseif($p['cat_code'] === 'DAU-GHI') { $cat_slug = 'dau-ghi-hinh'; $cat_name = 'Đầu Ghi Hình'; }
elseif($p['cat_code'] === 'PHU-KIEN') { $cat_slug = 'phu-kien'; $cat_name = 'Phụ Kiện'; }
elseif($p['cat_code'] === 'THIET-BI-MANG') { $cat_slug = 'thiet-bi-mang'; $cat_name = 'Thiết Bị Mạng'; }

// 5. Require Header
include 'includes/header.php';

// Tính Toán Giá Sale
$chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
$hasDiscount = ($p['sale_price'] > 0 && $p['price'] > $chot_gia);
$pct = $hasDiscount ? round((($p['price'] - $chot_gia) / $p['price']) * 100) : 0;
?>

<!-- Load CSS riêng cho Product Detail chuẩn TGDĐ -->
<link rel="stylesheet" href="assets/css/pages/product-detail.css?v=<?php echo time(); ?>">
<!-- Load CSS cho giao diện thẻ sản phẩm (Card) dưới chân trang -->
<link rel="stylesheet" href="assets/css/components/product-card.css?v=<?php echo time(); ?>">

<main class="container tgdd-pd-container">
    <div class="pd-breadcrumb" style="padding: 15px 0;">
        <a href="index.php">Trang chủ</a> <span style="color:#999; margin: 0 5px;">&rsaquo;</span>
        <a href="category.php?slug=<?php echo htmlspecialchars($cat_slug); ?>"><?php echo htmlspecialchars($cat_name); ?></a> <span style="color:#999; margin: 0 5px;">&rsaquo;</span>
        <strong style="color:#333;"><?php echo htmlspecialchars($p['name']); ?></strong>
    </div>

    <!-- Header Thông tin sản phẩm (Màu tiêu đề + rating + ... ) -->
    <div class="pd-top-info">
        <h1 class="pd-title"><?php echo htmlspecialchars($p['name']); ?></h1>
        <div class="pd-rating">
            <span class="stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
            </span>
            <span class="rating-count">4.9</span>
            <span style="color:#ddd">|</span>
            <span class="sold-count">Đã bán <?php echo rand(100, 999); ?></span>
            <span style="color:#ddd">|</span>
            <span class="pd-meta-id">SKU: <?php echo htmlspecialchars($p['sku']); ?></span>
        </div>
    </div>

    <div class="tgdd-pd-layout">
        <!-- ==== CỘT TRÁI (ẢNH VÀ THÔNG TIN CHI TIẾT) ==== -->
        <div class="pd-left">
            <?php
            // Lấy toàn bộ ảnh từ cột image_file (nếu có)
            $gallery_images = [];
            if (!empty($p['image_file'])) {
                $image_files = explode(',', $p['image_file']); // Giả sử các ảnh được lưu cách nhau bởi dấu phẩy
                foreach ($image_files as $image) {
                    $image = trim($image);
                    if (!empty($image) && file_exists('uploads/' . $image)) {
                        $gallery_images[] = $image;
                    }
                }
            }

            ?>
            <!-- Box Gallery (Ảnh SP) -->
            <div class="pd-box pd-gallery-wrapper" style="padding-bottom: 15px;">
                <div class="pd-gallery-box" style="position: relative; width: 100%; aspect-ratio: 1 / 1; max-height: 500px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; border-radius: 8px;">
                    <?php $main_show = !empty($gallery_images) ? $gallery_images[0] : 'placeholder.png'; ?>
                    <img id="main-product-image" src="uploads/<?php echo htmlspecialchars($main_show); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="pd-img-main" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; transform: none; margin: 0; padding: 0;">
                </div>
                
                <!-- Thumbnails Slider -->
                <?php if(count($gallery_images) > 1): ?>
                <div class="pd-thumbnails" style="display: flex; gap: 10px; margin-top: 15px; justify-content: center; overflow-x: auto;">
                    <?php foreach($gallery_images as $index => $img): ?>
                    <img src="uploads/<?php echo htmlspecialchars($img); ?>" class="pd-thumb-item" style="width: 60px; height: 60px; min-width: 60px; object-fit: contain; background: #fff; border: 2px solid <?php echo $index === 0 ? '#288ad6' : '#eee'; ?>; border-radius: 4px; cursor: pointer; transition: 0.2s;" onclick="changeMainImage('<?php echo htmlspecialchars($img); ?>', this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cam kết TGDĐ Style -->
            <div class="pd-policy-box">
                <h3 class="policy-title">TANDA cam kết</h3>
                <div class="policy-grid">
                    <div class="policy-item">
                        <i class="fas fa-sync-alt"></i> 
                        <div>Lỗi 1 đổi 1 <strong>30 ngày</strong> đối với sản phẩm lỗi</div>
                    </div>
                    <div class="policy-item">
                        <i class="fas fa-box-open"></i> 
                        <div>Bộ sản phẩm gồm: Hộp, Hướng dẫn sử dụng, Chân đế, Cáp nguồn</div>
                    </div>
                    <div class="policy-item">
                        <i class="fas fa-shield-alt"></i> 
                        <div>Bảo hành <strong>chính hãng 24 tháng</strong> tại nhà sản xuất</div>
                    </div>
                </div>
            </div>

            <!-- Tabs: Thông tin sản phẩm & Thông số -->
            <div class="pd-tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchProductTab(event, 'tab-mota')">Thông tin sản phẩm</button>
                    <button class="tab-btn" onclick="switchProductTab(event, 'tab-thongso')">Thông số kỹ thuật</button>
                </div>
                
                <!-- Tab Mô tả -->
                <div id="tab-mota" class="tab-content active" style="padding: 0 15px 15px 15px;">
                    <?php 
                        if(!empty($p['description'])) {
                            echo trim($p['description']); // Description thường đã chứa thẻ p từ lúc nhập
                        } else {
                            echo '<p style="color:#888; font-style:italic;">Nội dung chi tiết đang được cập nhật...</p>';
                        }
                    ?>
                </div>

                <!-- Tab Thông số -->
                <div id="tab-thongso" class="tab-content">
                    <ul class="specs-list">
                        <?php 
                        if(!empty($p['specs_summary'])) {
                            // Chuyển các dấu phân cách phổ biến (✔️, xuống dòng, thẻ br, dấu phẩy) thành một ký tự chung '|'
                            $raw_text = str_replace(['✔️', '✔', "\n", "\r", '<br>', '<br/>', ','], '|', $p['specs_summary']);
                            $specs = explode('|', $raw_text);
                            
                            foreach($specs as $idx => $spec) {
                                $spec = trim($spec, " \t\n\r\0\x0B-");
                                if(empty($spec)) continue;
                                
                                $parts = explode(':', $spec, 2);
                                if(count($parts) == 2) {
                                    echo "<li><strong><i class='fas fa-check' style='color:#288ad6; margin-right:5px; font-size:12px;'></i> ".htmlspecialchars(trim($parts[0]))."</strong><span>".htmlspecialchars(trim($parts[1]))."</span></li>";
                                } else {
                                    echo "<li><strong><i class='fas fa-check' style='color:#288ad6; margin-right:5px; font-size:12px;'></i> Đặc điểm ".($idx+1)."</strong><span>".htmlspecialchars($spec)."</span></li>";
                                }
                            }
                        } else {
                            echo "<li><strong>Thông số</strong><span>Đang cập nhật...</span></li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ==== CỘT GIỮA (GIÁ, KHUYẾN MÃI) ==== -->
        <div class="pd-middle">
            
            <!-- KHU VỰC GIÁ -->
            <?php if($hasDiscount): ?>
            <div class="flash-sale-box">
                <div class="flash-header" style="justify-content: flex-start; padding: 10px 15px;">
                    <div class="flash-title">
                        <i class="fas fa-bolt"></i> Khuyến Mãi Đặc Biệt
                    </div>
                </div>
                <div class="flash-body" style="padding-bottom: 15px;">
                    <div class="main-price-row"><?php echo number_format($chot_gia, 0, ',', '.'); ?>₫</div>
                    <div class="old-price-row">
                        <span class="old-price"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                        <span class="percent">(-<?php echo $pct; ?>%)</span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="normal-price-box" style="padding: 15px 0; margin-bottom: 15px;">
                <div class="main-price-row" style="color: #d70018; font-size: 36px; font-weight: bold; line-height: 1;">
                    <?php echo number_format($chot_gia, 0, ',', '.'); ?>₫
                </div>
            </div>
            <?php endif; ?>
                
                <!-- Box Khuyến mãi ăn theo TGDĐ -->
                <?php if($p['status'] == 1): ?>
                <div class="promo-box">
                    <div class="promo-title">Khuyến mãi kèm theo</div>
                    <div class="promo-note">Giá và khuyến mãi có thể kết thúc sớm hơn dự kiến</div>
                    
                    <ul class="promo-list">
                        <li>
                            <div class="promo-num">1</div>
                            <?php echo !empty($p['coupon_code']) ? 'Giảm thêm khi sử dụng mã <strong>'.htmlspecialchars($p['coupon_code']).'</strong>' : 'Tặng Phiếu mua hàng Thiết bị mạng trị giá 200,000₫'; ?>
                        </li>
                        <li>
                            <div class="promo-num">2</div>
                            Hỗ trợ tư vấn, cài đặt lắp ráp từ xa qua Ultraview/Teamviewer Miễn phí
                        </li>
                        <li>
                            <div class="promo-num">3</div>
                            Giảm 10% khi mua kèm thẻ nhớ hoặc ổ cứng chuyên dụng
                        </li>
                    </ul>

                    <div class="promo-extra">
                        <div class="promo-extra-item">Mỗi số điện thoại chỉ mua 1 sản phẩm</div>
                        <div class="promo-extra-item">Giao hàng nhanh chóng (tuỳ khu vực)</div>
                    </div>
                </div>
                <?php endif; ?>
        </div>

        <!-- ==== CỘT PHẢI (HÀNH ĐỘNG VÀ LIÊN HỆ) ==== -->
        <div class="pd-right-action">
            
            <!-- Các Nút chức năng -->
            <div class="pd-actions">
                <div class="btn-row">
                    <button type="button" class="btn-outline-blue" onclick="addToCart('<?php echo htmlspecialchars($p['sku']); ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo htmlspecialchars($p['image_file']); ?>')">
                        <i class="fas fa-cart-plus"></i>Thêm vào giỏ
                    </button>
                    <button type="button" class="btn-solid-orange" onclick="addToCart('<?php echo htmlspecialchars($p['sku']); ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo htmlspecialchars($p['image_file']); ?>'); window.location.href='cart.php';">
                        MUA NGAY
                    </button>
                </div>
            </div>

            <div class="pd-contact-inline">
                <p><i class="fas fa-phone-alt"></i> Mua hàng <a href="tel:0969696969">0969 696 969</a> (8:00 - 21:00)</p>
                <p><i class="fas fa-store"></i> Giao hàng toàn quốc - Tận nơi kiểm tra</p>
                <p><i class="fab fa-facebook-messenger"></i> <a href="https://zalo.me/0969696969" target="_blank" style="font-weight:normal;">Chat qua Zalo tư vấn</a></p>
            </div>
        </div>
    </div>
    
    <!-- BLOCK RELATED PRODUCTS (CÓ THỂ BẠN CŨNG THÍCH) -->
    <?php
    $stmtRelated = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat AND sku != :sku AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 5");
    $stmtRelated->execute(['cat' => $p['cat_code'], 'sku' => $p['sku']]);
    $relatedProds = $stmtRelated->fetchAll();
    
    if(count($relatedProds) > 0): 
    ?>
    <div class="product-section" style="margin-top: 40px;">
        <div class="section-header" style="padding: 15px 20px; border-bottom: 1px solid #f1f1f1; display: flex; justify-content: space-between; align-items: center;">
            <h2 class="section-title" style="font-size: 18px; margin: 0; text-transform: uppercase;">CÓ THỂ BẠN CŨNG THÍCH</h2>
            <a href="category.php?slug=<?php echo htmlspecialchars($cat_slug); ?>" class="view-all-link" style="color: #288ad6; font-size: 14px;">Xem thêm &raquo;</a>
        </div>
        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); background: #f1f1f1; gap: 1px; border: 1px solid #f1f1f1;">
            <?php 
            $p_backup = $p; 
            foreach($relatedProds as $p) {
                include 'card_template.php'; 
            }
            $p = $p_backup; 
            ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
function switchProductTab(event, tabId) {
    // Xóa active hiện tại
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    // Gán active mới
    event.currentTarget.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

function changeMainImage(imgFile, el) {
    document.getElementById('main-product-image').src = 'uploads/' + imgFile;
    // Đổi viền active cho thumbnail
    document.querySelectorAll('.pd-thumb-item').forEach(thumb => {
        thumb.style.borderColor = '#eee';
    });
    el.style.borderColor = '#288ad6';
}
</script>

<?php include 'includes/footer.php'; ?>