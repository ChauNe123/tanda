<?php
// compare.php - So sánh sản phẩm
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/cores/db_config.php';
include 'includes/header.php';

// Lấy danh sách SKU từ URL
$skuList = isset($_GET['skus']) ? trim($_GET['skus']) : '';
$skus = [];
if (!empty($skuList)) {
    $skus = array_filter(array_map('trim', explode(',', $skuList)));
}

// Query sản phẩm
$products = [];
if (count($skus) > 0) {
    $placeholders = implode(',', array_fill(0, count($skus), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE sku IN ($placeholders) AND status = 1");
    $stmt->execute(array_values($skus));
    $products = $stmt->fetchAll();
    
    // Sắp xếp lại theo đúng thứ tự skus
    $skuOrder = array_flip($skus);
    usort($products, function($a, $b) use ($skuOrder) {
        return ($skuOrder[$a['sku']] ?? 99) - ($skuOrder[$b['sku']] ?? 99);
    });
}

// Helper: lấy ảnh đầu tiên
function getFirstImage($p) {
    if (!empty($p['image_1'])) {
        $imgs = explode(',', $p['image_1']);
        $first = trim($imgs[0]);
        if (!empty($first) && file_exists('uploads/' . $first)) {
            return $first;
        }
    }
    return 'placeholder.png';
}

// Helper: parse specs từ dòng "key: value"
function parseSpecLines($content) {
    $content = trim($content);
    if (empty($content)) return [];
    $lines = explode("\n", $content);
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode(':', $line, 2);
        $key = trim($parts[0] ?? '');
        $val = trim($parts[1] ?? '');
        if ($key !== '') {
            $result[$key] = $val;
        }
    }
    return $result;
}

// Gom tất cả spec keys từ các sản phẩm để tạo bảng đồng nhất
$allSpecKeys = [];
$productsSpecs = [];
foreach ($products as $p) {
    $specs = [];
    $groups = [
        '📷 Camera & Tiện ích'              => $p['specs_group_1'] ?? '',
        '📶 Kết nối & Lưu trữ'               => $p['specs_group_2'] ?? '',
        '⚡ Nguồn điện & ĐK sử dụng'         => $p['specs_group_3'] ?? '',
        '🛠️ Lắp đặt & Hỗ trợ'               => $p['specs_group_4'] ?? '',
    ];
    $productSpecs = [];
    foreach ($groups as $groupName => $content) {
        $lines = parseSpecLines($content);
        if (count($lines) > 0) {
            $productSpecs[$groupName] = $lines;
            foreach ($lines as $key => $val) {
                $allSpecKeys[$key] = true;
            }
        }
    }
    $productsSpecs[$p['sku']] = $productSpecs;
}
?>

<link rel="stylesheet" href="assets/css/pages/compare.css?v=<?php echo time(); ?>">

<main class="container compare-page-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb" style="margin-bottom: 20px; color: #666; font-size: 14px;">
        <a href="index.php" style="color: #288ad6; font-weight: bold;">Trang chủ</a> / 
        <strong>So sánh sản phẩm</strong>
    </div>

    <?php if (count($products) > 0): ?>
    <!-- Thanh công cụ -->
    <div class="compare-toolbar">
        <h1 class="compare-title">SO SÁNH SẢN PHẨM <span>(<?php echo count($products); ?> sản phẩm)</span></h1>
        <div class="compare-toolbar-actions">
            <button class="btn-compare-action" onclick="clearCompare()">
                <i class="fas fa-trash-alt"></i> Xóa tất cả
            </button>
            <a href="index.php" class="btn-compare-action btn-add-more">
                <i class="fas fa-plus"></i> Thêm sản phẩm
            </a>
        </div>
    </div>

    <!-- Bảng so sánh -->
    <div class="compare-table-wrapper">
        <table class="compare-table">
            <thead>
                <tr>
                    <th class="col-label">Thông tin</th>
                    <?php foreach ($products as $p): 
                        $img = getFirstImage($p);
                        $chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
                        $hasDiscount = ($p['sale_price'] > 0 && $p['price'] > $p['sale_price']);
                        $pct = $hasDiscount ? round((($p['price'] - $chot_gia) / $p['price']) * 100) : 0;
                    ?>
                    <th class="col-product">
                        <button class="btn-remove-compare" onclick="removeCompareItem('<?php echo htmlspecialchars($p['sku']); ?>')" title="Xóa khỏi so sánh">&times;</button>
                        <a href="product-detail.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" class="compare-product-link">
                            <img src="uploads/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="compare-product-img" onerror="this.src='uploads/placeholder.png'">
                            <h3 class="compare-product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                        </a>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <!-- Hàng: Giá -->
                <tr class="row-price">
                    <td class="col-label">Giá bán</td>
                    <?php foreach ($products as $p): 
                        $chot_gia = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
                        $hasDiscount = ($p['sale_price'] > 0 && $p['price'] > $p['sale_price']);
                        $pct = $hasDiscount ? round((($p['price'] - $chot_gia) / $p['price']) * 100) : 0;
                    ?>
                    <td class="col-product">
                        <div class="compare-price">
                            <span class="compare-price-new"><?php echo number_format($chot_gia, 0, ',', '.'); ?>₫</span>
                            <?php if($hasDiscount): ?>
                                <span class="compare-price-old"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                                <span class="compare-price-pct">-<?php echo $pct; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-compare-buy" onclick="addToCart('<?php echo htmlspecialchars($p['sku']); ?>', '<?php echo htmlspecialchars(addslashes($p['name'])); ?>', <?php echo $chot_gia; ?>, '<?php echo htmlspecialchars(getFirstImage($p)); ?>')">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </button>
                    </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Hàng: SKU -->
                <tr class="row-sku">
                    <td class="col-label">Mã sản phẩm</td>
                    <?php foreach ($products as $p): ?>
                    <td class="col-product"><code><?php echo htmlspecialchars($p['sku']); ?></code></td>
                    <?php endforeach; ?>
                </tr>

                <!-- Hàng: Danh mục -->
                <tr>
                    <td class="col-label">Danh mục</td>
                    <?php foreach ($products as $p): ?>
                    <td class="col-product"><?php echo htmlspecialchars($p['cat_code']); ?></td>
                    <?php endforeach; ?>
                </tr>

                <!-- Spec Groups -->
                <?php 
                $groupNames = ['📷 Camera & Tiện ích', '📶 Kết nối & Lưu trữ', '⚡ Nguồn điện & ĐK sử dụng', '🛠️ Lắp đặt & Hỗ trợ'];
                foreach ($groupNames as $groupName):
                    // Kiểm tra có sản phẩm nào có spec trong group này không
                    $hasGroup = false;
                    foreach ($productsSpecs as $sku => $groups) {
                        if (isset($groups[$groupName]) && count($groups[$groupName]) > 0) {
                            $hasGroup = true;
                            break;
                        }
                    }
                    if (!$hasGroup) continue;

                    // Gom tất cả keys trong group này
                    $groupKeys = [];
                    foreach ($productsSpecs as $sku => $groups) {
                        if (isset($groups[$groupName])) {
                            foreach ($groups[$groupName] as $key => $val) {
                                $groupKeys[$key] = true;
                            }
                        }
                    }
                ?>
                <!-- Group Header -->
                <tr class="row-group-header">
                    <td colspan="<?php echo count($products) + 1; ?>">
                        <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($groupName); ?>
                    </td>
                </tr>
                <?php foreach ($groupKeys as $key => $_): ?>
                <tr>
                    <td class="col-label"><?php echo htmlspecialchars($key); ?></td>
                    <?php foreach ($products as $p): 
                        $val = $productsSpecs[$p['sku']][$groupName][$key] ?? '';
                        $highlight = '';
                        // Highlight nếu giá trị khác biệt
                        $values = [];
                        foreach ($products as $p2) {
                            $v = $productsSpecs[$p2['sku']][$groupName][$key] ?? '';
                            if ($v !== '') $values[] = $v;
                        }
                        $uniqueValues = array_unique($values);
                        if (count($uniqueValues) > 1 && $val !== '') {
                            $highlight = 'style="background:#fffde7; font-weight:600;"';
                        }
                    ?>
                    <td class="col-product" <?php echo $highlight; ?>>
                        <?php echo $val !== '' ? htmlspecialchars($val) : '<span style="color:#ccc;">—</span>'; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- Hàng: Mô tả -->
                <tr class="row-group-header">
                    <td colspan="<?php echo count($products) + 1; ?>">
                        <i class="fas fa-align-left"></i> Mô tả sản phẩm
                    </td>
                </tr>
                <tr>
                    <td class="col-label">Mô tả</td>
                    <?php foreach ($products as $p): 
                        $desc = !empty($p['description']) ? strip_tags($p['description']) : '';
                        $desc = mb_substr($desc, 0, 150) . (mb_strlen($desc) > 150 ? '...' : '');
                    ?>
                    <td class="col-product" style="font-size:13px; line-height:1.6; color:#555;">
                        <?php echo $desc !== '' ? nl2br(htmlspecialchars($desc)) : '<span style="color:#ccc;">Chưa có mô tả</span>'; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Sticky bar dưới cùng -->
    <div class="compare-sticky-bar" id="compareStickyBar" style="display:none;">
        <div class="compare-sticky-content">
            <span class="compare-sticky-count">Đang so sánh <strong id="compareCount"><?php echo count($products); ?></strong> sản phẩm</span>
            <div class="compare-sticky-actions">
                <button class="btn-compare-action" onclick="clearCompare()"><i class="fas fa-trash-alt"></i> Xóa</button>
                <a href="compare.php" class="btn-compare-action btn-compare-go"><i class="fas fa-balance-scale"></i> So sánh ngay</a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Không có sản phẩm nào -->
    <div class="compare-empty">
        <div class="compare-empty-icon">
            <i class="fas fa-balance-scale"></i>
        </div>
        <h2>Chưa có sản phẩm nào để so sánh</h2>
        <p>Hãy chọn sản phẩm bằng cách nhấn vào nút <strong>"So sánh"</strong> trên các thẻ sản phẩm.</p>
        <p style="color:#999; font-size:13px; margin-top:5px;">Bạn có thể chọn tối đa 4 sản phẩm để so sánh cùng lúc.</p>
        <a href="index.php" class="btn-compare-back">
            <i class="fas fa-arrow-left"></i> Về trang chủ chọn sản phẩm
        </a>
    </div>
    <?php endif; ?>
</main>

<script>
// === SO SÁNH SẢN PHẨM (LocalStorage) ===
const COMPARE_KEY = 'tanda_compare';
const MAX_COMPARE = 4;

function getCompareList() {
    try { return JSON.parse(localStorage.getItem(COMPARE_KEY)) || []; }
    catch(e) { return []; }
}

function saveCompareList(list) {
    localStorage.setItem(COMPARE_KEY, JSON.stringify(list));
    updateCompareUI();
}

function toggleCompareItem(checkbox) {
    let list = getCompareList();
    let sku = checkbox.value;
    
    if (checkbox.checked) {
        if (list.length >= MAX_COMPARE) {
            checkbox.checked = false;
            showToast('Bạn chỉ có thể so sánh tối đa ' + MAX_COMPARE + ' sản phẩm.', 'warning');
            return;
        }
        list.push({
            sku: sku,
            name: checkbox.dataset.name,
            img: checkbox.dataset.img,
            price: checkbox.dataset.price
        });
    } else {
        list = list.filter(item => item.sku !== sku);
    }
    
    saveCompareList(list);
    syncCheckboxes(list);
}

function removeCompareItem(sku) {
    let list = getCompareList().filter(item => item.sku !== sku);
    saveCompareList(list);
    syncCheckboxes(list);
    
    // Reload trang nếu đang ở compare.php
    let newSkus = list.map(i => i.sku).join(',');
    if (newSkus.length > 0) {
        window.location.href = 'compare.php?skus=' + encodeURIComponent(newSkus);
    } else {
        window.location.href = 'compare.php';
    }
}

function clearCompare() {
    showConfirmDialog('Bạn có chắc muốn xóa tất cả sản phẩm khỏi danh sách so sánh?', function() {
        localStorage.removeItem(COMPARE_KEY);
        window.location.href = 'compare.php';
    });
}

function syncCheckboxes(list) {
    let skuSet = new Set(list.map(i => i.sku));
    document.querySelectorAll('.compare-checkbox').forEach(cb => {
        cb.checked = skuSet.has(cb.value);
    });
}

function updateCompareUI() {
    let list = getCompareList();
    let bar = document.getElementById('compareStickyBar');
    let countEl = document.getElementById('compareCount');
    
    if (bar) {
        bar.style.display = list.length > 0 ? 'block' : 'none';
    }
    if (countEl) {
        countEl.textContent = list.length;
    }
    
    // Thêm/xóa class cho body để chừa khoảng trống sticky bar
    if (list.length > 0) {
        document.body.classList.add('has-compare-bar');
    } else {
        document.body.classList.remove('has-compare-bar');
    }
    
    // Cập nhật link "So sánh ngay" trên sticky bar
    let goBtn = document.querySelector('.btn-compare-go');
    if (goBtn && list.length > 0) {
        let skus = list.map(i => i.sku).join(',');
        goBtn.href = 'compare.php?skus=' + encodeURIComponent(skus);
    }
}

// Khởi tạo
document.addEventListener('DOMContentLoaded', function() {
    let list = getCompareList();
    syncCheckboxes(list);
    updateCompareUI();
});
</script>

<?php include 'includes/footer.php'; ?>
