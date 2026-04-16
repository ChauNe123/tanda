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

// Lấy toàn bộ sản phẩm đang hoạt động để phân loại cho Tab
$stmt = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC");
$stmt->execute();
$allProds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Phân loại sản phẩm theo cat_code để gửi cho JS
$categorizedData = [
    'wifi' => [],
    'tronbo' => [],
    'daughi' => [],
    'phukien' => [],
    'mang' => []
];

foreach ($allProds as $p) {
    if ($p['cat_code'] == 'CAM-WIFI') $categorizedData['wifi'][] = $p;
    elseif ($p['cat_code'] == 'CAM-DAY') $categorizedData['tronbo'][] = $p;
    elseif ($p['cat_code'] == 'DAU-GHI') $categorizedData['daughi'][] = $p;
    elseif ($p['cat_code'] == 'PHU-KIEN') $categorizedData['phukien'][] = $p;
    elseif ($p['cat_code'] == 'THIET-BI-MANG') $categorizedData['mang'][] = $p;
}
?>

<?php include 'includes/header.php'; ?>
<main class="container">
    <section class="flash-sale-wrap">
        <h2 style="font-size: 22px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; color: #ff5722;">
            Sản Phẩm Nổi Bật Tanda
        </h2>
        
        <div class="fs-tabs" id="fs-tabs">
            <div class="fs-tab active" data-tab="wifi">Camera Wifi</div>
            <div class="fs-tab" data-tab="tronbo">Trọn bộ Camera</div>
            <div class="fs-tab" data-tab="daughi">Đầu Ghi Hình</div>
            <div class="fs-tab" data-tab="phukien">Phụ Kiện</div>
            <div class="fs-tab" data-tab="mang">Thiết Bị Mạng</div>
        </div>

        <div id="fs-grid" class="fs-grid"></div>
    </section>
</main>

<script>
const dbProducts = <?php echo json_encode($categorizedData); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('fs-grid');
    const tabs = document.querySelectorAll('.fs-tab');

    function renderProducts(tabId) {
        const items = dbProducts[tabId] || [];
        
        grid.classList.add('fade-out');
        grid.classList.remove('fade-in');

        setTimeout(() => {
            if(items.length === 0) {
                grid.innerHTML = '<p style="grid-column: 1/-1; text-align:center; padding:50px; color:#999;">Đang cập nhật sản phẩm cho mục này...</p>';
            } else {
                grid.innerHTML = items.map(p => {
                    const hasSale = p.sale_price > 0 && p.sale_price < p.price;
                    const displayPrice = hasSale ? p.sale_price : p.price;
                    const percent = hasSale ? Math.round(((p.price - p.sale_price) / p.price) * 100) : 0;
                    
                    return `
                        <div class="fs-card">
                            <div class="fs-img-wrap" onclick="window.location.href='product-detail.php?slug=${p.slug}'">
                                <img src="uploads/${p.image_file}" alt="${p.name}" loading="lazy">
                            </div>
                            <div class="fs-title" onclick="window.location.href='product-detail.php?slug=${p.slug}'">${p.name}</div>
                            <div class="fs-price-row">
                                <span class="fs-price-new">${parseInt(displayPrice).toLocaleString('vi-VN')}₫</span>
                                ${hasSale ? `<span class="fs-price-old">${parseInt(p.price).toLocaleString('vi-VN')}₫</span>` : ''}
                                ${hasSale ? `<span class="fs-discount">-${percent}%</span>` : ''}
                            </div>
                            <div class="fs-progress">
                                <div class="fs-progress-bar" style="width: ${Math.floor(Math.random() * 60) + 30}%"></div>
                                <span>Mới về kho</span>
                            </div>
                            <button class="btn-fs-buy" onclick="addToCart('${p.sku}', '${p.name.replace(/'/g, "\\'")}', ${displayPrice}, '${p.image_file}')">
                                MUA NGAY
                            </button>
                        </div>
                    `;
                }).join('');
            }

            grid.classList.remove('fade-out');
            grid.classList.add('fade-in');
        }, 150);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            renderProducts(this.getAttribute('data-tab'));
        });
    });

    renderProducts('wifi');
});
</script>
    <?php include 'includes/footer.php'; ?>