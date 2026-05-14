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

// 4. Lấy Tên Danh Mục từ DB (cho breadcrumb) - KHÔNG hardcode nữa
$cat_slug = 'camera-wifi';
$cat_name = 'Camera WiFi';
if (!empty($p['cat_code'])) {
    $stmtCat = $conn->prepare("SELECT slug, name FROM categories WHERE cat_code = :cat_code AND status = 1 LIMIT 1");
    $stmtCat->execute(['cat_code' => $p['cat_code']]);
    $cat = $stmtCat->fetch();
    if ($cat) {
        $cat_slug = $cat['slug'];
        $cat_name = $cat['name'];
    }
}

// 5. Lấy ảnh đầu tiên cho giỏ hàng
$first_image = 'placeholder.png';
if (!empty($p['image_1'])) {
    $image_files = explode(',', $p['image_1']);
    $first_img = trim($image_files[0]);
    if (!empty($first_img) && file_exists('uploads/' . $first_img)) {
        $first_image = $first_img;
    }
}

// 6. Require Header
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
            // Lấy toàn bộ ảnh từ cột image_1 (Theo quy tắc mapping mới)
            $gallery_images = [];
            if (!empty($p['image_1'])) {
                $image_files = explode(',', $p['image_1']); // Các ảnh được lưu cách nhau bởi dấu phẩy
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
                <div class="pd-gallery-box" style="position: relative; width: 100%; aspect-ratio: 1 / 1; max-height: 500px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #f1f1f1;">
                    <?php $main_show = !empty($gallery_images) ? $gallery_images[0] : 'placeholder.png'; ?>
                    <img id="main-product-image" src="uploads/<?php echo htmlspecialchars($main_show); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="pd-img-main" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; transition: 0.3s ease; cursor: zoom-in;" onclick="openLightbox(this.src)">
                    
                    <?php if(count($gallery_images) > 1): ?>
                        <!-- Nút mũi tên -->
                        <button class="gallery-nav-btn prev" onclick="prevImage()" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:40px; height:40px; border-radius:50%; border:none; background:rgba(255,255,255,0.8); cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.1); z-index:10; transition:0.2s;">
                            <i class="fas fa-chevron-left" style="font-size:18px; color:#333;"></i>
                        </button>
                        <button class="gallery-nav-btn next" onclick="nextImage()" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); width:40px; height:40px; border-radius:50%; border:none; background:rgba(255,255,255,0.8); cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,0.1); z-index:10; transition:0.2s;">
                            <i class="fas fa-chevron-right" style="font-size:18px; color:#333;"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Thumbnails Slider -->
                <?php if(count($gallery_images) > 1): ?>
                <div class="pd-thumbnails" id="gallery-thumbnails" style="display: flex; gap: 10px; margin-top: 15px; justify-content: center; overflow-x: auto; padding: 5px 0;">
                    <?php foreach($gallery_images as $index => $img): ?>
                    <img src="uploads/<?php echo htmlspecialchars($img); ?>" 
                         class="pd-thumb-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                         data-index="<?php echo $index; ?>"
                         style="width: 60px; height: 60px; min-width: 60px; object-fit: contain; background: #fff; border: 2px solid <?php echo $index === 0 ? '#288ad6' : '#eee'; ?>; border-radius: 4px; cursor: pointer; transition: 0.2s;" 
                         loading="lazy" 
                         onclick="changeMainImage('<?php echo htmlspecialchars($img); ?>', this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <script>
                // Logic Gallery Ảnh
                let currentImgIndex = 0;
                const galleryImages = <?php echo json_encode($gallery_images); ?>;

                function changeMainImage(imgName, thumb) {
                    const mainImg = document.getElementById('main-product-image');
                    mainImg.style.opacity = '0.5';
                    setTimeout(() => {
                        mainImg.src = 'uploads/' + imgName;
                        mainImg.style.opacity = '1';
                    }, 150);

                    // Cập nhật viền cho Thumbnails
                    const thumbs = document.querySelectorAll('.pd-thumb-item');
                    thumbs.forEach(t => {
                        t.style.borderColor = '#eee';
                        t.classList.remove('active');
                    });
                    thumb.style.borderColor = '#288ad6';
                    thumb.classList.add('active');
                    
                    if (thumb.dataset.index !== undefined) {
                        currentImgIndex = parseInt(thumb.dataset.index);
                    }
                }

                function nextImage() {
                    currentImgIndex = (currentImgIndex + 1) % galleryImages.length;
                    const nextThumb = document.querySelector(`.pd-thumb-item[data-index="${currentImgIndex}"]`);
                    if(nextThumb) changeMainImage(galleryImages[currentImgIndex], nextThumb);
                }

                function prevImage() {
                    currentImgIndex = (currentImgIndex - 1 + galleryImages.length) % galleryImages.length;
                    const prevThumb = document.querySelector(`.pd-thumb-item[data-index="${currentImgIndex}"]`);
                    if(prevThumb) changeMainImage(galleryImages[currentImgIndex], prevThumb);
                }

                // Hỗ trợ phím mũi tên
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowLeft') prevImage();
                    if (e.key === 'ArrowRight') nextImage();
                });
            </script>

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

            <!-- Tabs: Mô tả sản phẩm & Thông số kỹ thuật -->
            <div class="pd-tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchProductTab(event, 'tab-mota')">Mô tả sản phẩm</button>
                    <button class="tab-btn" onclick="switchProductTab(event, 'tab-thongso')">Thông số kỹ thuật</button>
                </div>
                
                <!-- Tab Mô tả -->
                <div id="tab-mota" class="tab-content active">
                    <div class="description-wrapper">
                        <div id="descriptionContent" class="description-content collapsed">
                            <?php 
                                if(!empty($p['description'])) {
                                    echo $p['description'];
                                } else {
                                    echo '<p style="color:#888;">Nội dung đang cập nhật...</p>';
                                }
                            ?>
                        </div>
                        <?php if(!empty($p['description']) && strlen(strip_tags($p['description'])) > 250): ?>
                        <button type="button" id="toggleDescription" class="btn-show-more">Xem thêm</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab Thông số kỹ thuật -->
                <div id="tab-thongso" class="tab-content">
                    <?php
                    // Gom spec từ 4 nhóm. Chỉ hiện nhóm nào CÓ NỘI DUNG.
                    // Nếu chỉ có 1 nhóm duy nhất → hiện danh sách phẳng (không sub-header).
                    // Nếu có nhiều nhóm → hiện dạng collapsible sub-groups.
                    
                    $g1 = trim($p['specs_group_1'] ?? '');
                    $g2 = trim($p['specs_group_2'] ?? '');
                    $g3 = trim($p['specs_group_3'] ?? '');
                    $g4 = trim($p['specs_group_4'] ?? '');
                    
                    // Lọc ra các nhóm có nội dung
                    $filledGroups = [];
                    if (!empty($g1)) $filledGroups[] = ['title' => 'Camera & Tiện ích',              'content' => $g1];
                    if (!empty($g2)) $filledGroups[] = ['title' => 'Kết nối & Lưu trữ',               'content' => $g2];
                    if (!empty($g3)) $filledGroups[] = ['title' => 'Nguồn điện & Điều kiện sử dụng',  'content' => $g3];
                    if (!empty($g4)) $filledGroups[] = ['title' => 'Lắp đặt & Thiết bị hỗ trợ',       'content' => $g4];
                    
                    // Nếu không có group nào, thử lấy specs_summary
                    if (empty($filledGroups)) {
                        $summary = trim($p['specs_summary'] ?? '');
                        if (!empty($summary)) {
                            $filledGroups[] = ['title' => '', 'content' => $summary];
                        }
                    }
                    
                    $hasAnySpec = !empty($filledGroups);
                    $singleGroup = (count($filledGroups) === 1);
                    ?>
                    
                    <?php if ($hasAnySpec): ?>
                    <div class="specs-container">
                        <?php if ($singleGroup): ?>
                            <!-- CHỈ 1 NHÓM: hiện danh sách phẳng, không sub-header -->
                            <div class="spec-group active" style="border: none;">
                                <div class="spec-body" style="display: block;">
                                    <?php
                                    $rows = explode("\n", $filledGroups[0]['content']);
                                    foreach($rows as $row):
                                        $row = trim($row);
                                        if(empty($row)) continue;
                                        // Hỗ trợ "key: value" và "key|value"
                                        $parts = explode(':', $row, 2);
                                        if (count($parts) < 2) $parts = explode('|', $row, 2);
                                    ?>
                                    <div class="spec-row">
                                        <span><?php echo htmlspecialchars(trim($parts[0] ?? '')); ?></span>
                                        <span><?php echo htmlspecialchars(trim($parts[1] ?? '')); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- NHIỀU NHÓM: hiện dạng collapsible sub-groups -->
                            <?php $idx = 0; foreach($filledGroups as $group): ?>
                            <div class="spec-group <?php echo $idx === 0 ? 'active' : ''; ?>">
                                <div class="spec-header" onclick="toggleSpecGroup(this)">
                                    <span><?php echo htmlspecialchars($group['title']); ?></span>
                                    <span class="arrow">▼</span>
                                </div>
                                <div class="spec-body">
                                    <?php
                                    $rows = explode("\n", $group['content']);
                                    foreach($rows as $row):
                                        $row = trim($row);
                                        if(empty($row)) continue;
                                        $parts = explode(':', $row, 2);
                                        if (count($parts) < 2) $parts = explode('|', $row, 2);
                                    ?>
                                    <div class="spec-row">
                                        <span><?php echo htmlspecialchars(trim($parts[0] ?? '')); ?></span>
                                        <span><?php echo htmlspecialchars(trim($parts[1] ?? '')); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php $idx++; endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p style="color:#888; font-style:italic; padding: 20px; text-align: center;">Thông số kỹ thuật đang được cập nhật...</p>
                    <?php endif; ?>
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
            <!-- Giá không khuyến mãi - Thiết kế đẹp -->
            <div class="pd-box" style="text-align: center; padding: 20px;">
                <div class="main-price-row" style="color: #d70018; font-size: 32px; font-weight: bold; line-height: 1.2; margin-bottom: 5px;">
                    <?php echo number_format($chot_gia, 0, ',', '.'); ?>₫
                </div>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">Giá niêm yết (Đã bao gồm VAT)</div>
                <?php if(!empty($p['coupon_code'])): ?>
                <div style="margin-top: 10px; background: #fff8e1; border: 1px dashed #ff9f00; border-radius: 6px; padding: 8px 12px; display: inline-block;">
                    <i class="fas fa-ticket-alt" style="color: #ff9f00;"></i> 
                    <span style="font-size: 13px; color: #333;">Mã giảm: <strong style="color: #d70018;"><?php echo htmlspecialchars($p['coupon_code']); ?></strong></span>
                </div>
                <?php endif; ?>
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
            
            <!-- Trạng thái còn hàng / hết hàng -->
            <div class="pd-stock-status" style="margin-bottom:12px; padding:10px 14px; border-radius:6px; text-align:center; font-weight:600; font-size:14px; <?php echo $p['status'] == 1 ? 'background:#e8f5e9; color:#2e7d32;' : 'background:#ffebee; color:#c62828;'; ?>">
                <i class="fas <?php echo $p['status'] == 1 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <?php echo $p['status'] == 1 ? 'Còn hàng' : 'Tạm hết hàng'; ?>
            </div>

            <!-- Các Nút chức năng -->
            <div class="pd-actions">
                <div class="btn-row">
                    <?php if($p['status'] == 1): ?>
                    <button type="button" class="btn-outline-blue" onclick="addToCart('<?php echo htmlspecialchars($p['sku']); ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo htmlspecialchars($first_image); ?>')">
                        <i class="fas fa-cart-plus"></i>Thêm vào giỏ
                    </button>
                    <button type="button" class="btn-solid-orange" onclick="addToCart('<?php echo htmlspecialchars($p['sku']); ?>', '<?php echo addslashes($p['name']); ?>', <?php echo $chot_gia; ?>, '<?php echo htmlspecialchars($first_image); ?>'); window.location.href='cart.php';">
                        MUA NGAY
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn-outline-blue" disabled style="opacity:0.5; cursor:not-allowed; flex:1;">
                        <i class="fas fa-cart-plus"></i>Hết hàng tạm thời
                    </button>
                    <?php endif; ?>
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
        <div class="product-grid">
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

    <!-- KB LIGHTBOX SYSTEM -->
    <div id="kb-lightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(10px); cursor:zoom-out;" onclick="closeLightbox()">
        <span style="position:absolute; top:20px; right:30px; color:#fff; font-size:40px; cursor:pointer;">&times;</span>
        <img id="lightbox-img" src="" style="max-width:90%; max-height:90%; object-fit:contain; border-radius:8px; box-shadow:0 0 50px rgba(0,0,0,0.5); transform:scale(0.9); transition:0.3s ease;">
    </div>

    <script>
        function openLightbox(src) {
            const lightbox = document.getElementById('kb-lightbox');
            const img = document.getElementById('lightbox-img');
            img.src = src;
            lightbox.style.display = 'flex';
            setTimeout(() => {
                img.style.transform = 'scale(1)';
            }, 50);
        }

        function closeLightbox() {
            const lightbox = document.getElementById('kb-lightbox');
            const img = document.getElementById('lightbox-img');
            img.style.transform = 'scale(0.9)';
            setTimeout(() => {
                lightbox.style.display = 'none';
            }, 200);
        }
    </script>
</body>
</html>