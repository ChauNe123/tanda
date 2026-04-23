<?php
// admin/import_csv.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../cores/db_config.php';

// =========================================================
// HÀM TỐI ƯU DUNG LƯỢNG & KÍCH THƯỚC ẢNH UPLOAD (GD LIBRARY)
// =========================================================
function optimizeAndSaveImage($sourcePath, $destPath, $maxWidth = 800, $quality = 80) {
    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $width = $info[0];
    $height = $info[1];
    $mime = $info['mime'];

    // 1. Tính toán kích thước mới (Giữ nguyên tỉ lệ để không méo hình)
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = floor(($height / $width) * $newWidth);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    // 2. Tạo khung vẽ cho ảnh mới
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // 3. Xử lý kĩ nền trong suốt cho PNG (Quan trọng để up khung viền ngày Lễ Tết không bị đen)
    if ($mime == 'image/png' || $mime == 'image/webp' || $mime == 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // 4. Nhúng dữ liệu ảnh gốc vào
    switch ($mime) {
        case 'image/jpeg': $sourceImage = imagecreatefromjpeg($sourcePath); break;
        case 'image/png':  $sourceImage = imagecreatefrompng($sourcePath); break;
        case 'image/gif':  $sourceImage = imagecreatefromgif($sourcePath); break;
        case 'image/webp': $sourceImage = imagecreatefromwebp($sourcePath); break;
        default: return false;
    }

    // 5. Bắt đầu Resize mượt mà
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // 6. Nén và Xuất file (Giữ nguyên định dạng gốc đuôi file để khớp với file CSV)
    $success = false;
    switch ($mime) {
        case 'image/jpeg':
            $success = imagejpeg($newImage, $destPath, $quality); // Quality từ 0-100
            break;
        case 'image/png':
            // PNG dùng thang nén từ 0-9 (Tính toán ngược từ 80% về hệ số 9)
            $pngQuality = round(9 - ($quality / 100 * 9)); 
            $success = imagepng($newImage, $destPath, $pngQuality);
            break;
        case 'image/gif':
            $success = imagegif($newImage, $destPath);
            break;
        case 'image/webp':
            $success = imagewebp($newImage, $destPath, $quality);
            break;
    }

    // 7. Dọn rác RAM máy chủ
    imagedestroy($newImage);
    imagedestroy($sourceImage);

    return $success;
}

function findExistingSkuImage($sku, $suffix = '') {
    $sku = trim((string)$sku);
    if ($sku === '') return '';

    $uploadDir = __DIR__ . '/../uploads/';
    $baseName = $sku . $suffix;
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    foreach ($extensions as $ext) {
        $candidate = $baseName . '.' . $ext;
        if (file_exists($uploadDir . $candidate)) {
            return $candidate;
        }
    }

    return '';
}

// ---------------------------------------------------------
// XỬ LÝ AJAX 1: NHẬN HÌNH ẢNH (PHÂN LOẠI THƯ MỤC)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'upload_images') {
    
    $folder_name = (isset($_POST['target_folder']) && $_POST['target_folder'] == 'banners') ? 'banners' : 'uploads';
    $target_dir = "../" . $folder_name . "/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $count_success = 0; $count_error = 0;
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp']; // Khai báo đuôi file hợp lệ

    // Kiểm tra xem có file nào được up lên không
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $error = $_FILES['images']['error'][$key];
            
            if ($error === UPLOAD_ERR_OK) {
                $imageFileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                // Lấy tên mới từ input hoặc giữ tên gốc
                $new_name_post = isset($_POST['new_names'][$key]) ? trim($_POST['new_names'][$key]) : '';
                $final_name = ($new_name_post !== '') ? $new_name_post : pathinfo($name, PATHINFO_FILENAME);
                
                // Gắn lại đuôi file nếu m quên gõ
                if (!preg_match('/\.[a-zA-Z0-9]+$/', $final_name)) {
                    $final_name .= '.' . $imageFileType;
                }
                
                $target_file = $target_dir . $final_name;

                if (in_array($imageFileType, $allowed_types)) {
                    // GỌI HÀM NÉN ẢNH VỚI CHIỀU NGANG MAX 800PX, CHẤT LƯỢNG 80%
                    if (optimizeAndSaveImage($tmp_name, $target_file, 800, 80)) { 
                        $count_success++; 
                    } else {
                        // Fallback: copy file gốc nếu lỗi
                        if (move_uploaded_file($tmp_name, $target_file)) { $count_success++; } 
                        else { $count_error++; }
                    }
                } else {
                    $count_error++; // Sai định dạng
                }
            } else {
                $count_error++; // Lỗi file
            }
        }
    }
    
    echo json_encode(['success' => $count_success, 'error' => $count_error]);
    exit;
}

// ---------------------------------------------------------
// XỬ LÝ AJAX 2: NHẬN DỮ LIỆU KHO HÀNG (TỪ MINI-EXCEL)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'upload_products') {
    if (isset($_FILES['csv_products']) && $_FILES['csv_products']['error'] == 0) {
        $file_tmp = $_FILES['csv_products']['tmp_name'];
        if (($handle = fopen($file_tmp, "r")) !== FALSE) {
            $count_success = 0; $row_num = 0;
            while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
                $row_num++; if ($row_num == 1) continue; 

                $sku        = trim(preg_replace('/[\xEF\xBB\xBF]/', '', $row[0] ?? '')); 
                $cat_code   = trim($row[1] ?? ''); $name = trim($row[2] ?? '');
                $price      = (int)($row[3] ?? 0); $sale_price = (int)($row[4] ?? 0); 
                $coupon     = trim($row[5] ?? '');
                $specs = trim($row[8] ?? ''); 
                // Thêm dòng lấy dữ liệu description (Cột số 10 trong mảng, do mảng bắt đầu từ 0)
                $description = trim($row[9] ?? ''); 
                // Status lùi lại thành cột số 11 (Index 10)
                $status     = (int)($row[10] ?? 0);
                
                if(empty($sku) || empty($name)) continue;
                $slug = createSlug($name);

                // Tự map toàn bộ ảnh theo SKU từ thư mục uploads
                $image_file = findExistingSkuImage($sku, '');
                $image_2 = findExistingSkuImage($sku, '-2');
                $image_3 = findExistingSkuImage($sku, '-3');
                $image_4 = findExistingSkuImage($sku, '-4');
                $image_5 = findExistingSkuImage($sku, '-5');

                // CẬP NHẬT CÂU LỆNH SQL: Thêm cột description
                $sql = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, coupon_code, image_file, image_2, image_3, image_4, image_5, specs_summary, description, status, sort_order) 
                        VALUES (:sku, :cat, :name, :slug, :price, :sale, :coupon, :img, :img2, :img3, :img4, :img5, :specs, :desc, :stt, :sort)
                        ON DUPLICATE KEY UPDATE cat_code=VALUES(cat_code), name=VALUES(name), slug=VALUES(slug), price=VALUES(price), sale_price=VALUES(sale_price), 
                        coupon_code=VALUES(coupon_code), 
                        image_file=IF(VALUES(image_file) != '', VALUES(image_file), image_file),
                        image_2=IF(VALUES(image_2) != '', VALUES(image_2), image_2),
                        image_3=IF(VALUES(image_3) != '', VALUES(image_3), image_3),
                        image_4=IF(VALUES(image_4) != '', VALUES(image_4), image_4),
                        image_5=IF(VALUES(image_5) != '', VALUES(image_5), image_5),
                        specs_summary=VALUES(specs_summary), description=VALUES(description), status=VALUES(status), sort_order=VALUES(sort_order)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug, 
                    ':price'=>$price, ':sale'=>$sale_price, ':coupon'=>$coupon, 
                    ':img'=>$image_file, ':img2'=>$image_2, ':img3'=>$image_3, ':img4'=>$image_4, ':img5'=>$image_5,
                    ':specs'=>$specs, 
                    ':desc'=>$description, // Truyền biến mới vào đây
                    ':stt'=>$status, ':sort'=>$row_num
                ]);
                $count_success++;
            }
            fclose($handle);
            echo json_encode(['success' => $count_success]);
            exit;
        }
    }
    echo json_encode(['success' => 0, 'error' => 1]);
    exit;
}

$message = '';

function createSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/([^a-z0-9-\s])/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return trim($str, '-');
}

// ---------------------------------------------------------
// XỬ LÝ 3: NẠP GIAO DIỆN BẰNG NÚT CỔ ĐIỂN
// ---------------------------------------------------------
if (isset($_POST['btn_upload_banners'])) {
    if (isset($_FILES['csv_banners']) && $_FILES['csv_banners']['error'] == 0) {
        $file_tmp = $_FILES['csv_banners']['tmp_name'];
        if (($handle = fopen($file_tmp, "r")) !== FALSE) {
            $count_success = 0; $row_num = 0;
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_num++; if ($row_num == 1) continue;

                $banner_code = trim(preg_replace('/[\xEF\xBB\xBF]/', '', $row[0] ?? '')); 
                $image_file  = trim($row[1] ?? ''); $target_link = trim($row[2] ?? ''); 
                $status      = (int)($row[3] ?? 0);  
                
                if(empty($banner_code) || empty($image_file)) continue;

                $sql = "INSERT INTO banners (banner_code, image_file, target_link, status) 
                        VALUES (:code, :img, :link, :stt)
                        ON DUPLICATE KEY UPDATE image_file=VALUES(image_file), target_link=VALUES(target_link), status=VALUES(status)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([':code'=>$banner_code, ':img'=>$image_file, ':link'=>$target_link, ':stt'=>$status]);
                $count_success++;
            }
            fclose($handle);
            $message = "<div class='alert success'>🎨 ✅ Đã cập nhật $count_success BANNER GIAO DIỆN!</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị Hệ thống KB Tech</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-container">
    <div id="msg-box"><?php echo $message; ?></div>

    <div class="card card-images" id="image-upload-section">
        <h2 class="text-green">🖼️ NẠP HÌNH ẢNH LÊN SERVER</h2>
        <p class="subtitle">Bôi đen nhiều ảnh -> Đổi tên -> Bấm Tải Lên</p>
        
        <div style="text-align: center; margin-bottom: 15px; font-weight: bold; background: #f4fff6; padding: 10px; border-radius: 8px; border: 1px dashed #28a745;">
            Lưu ảnh này vào thư mục nào?<br><br>
            <label style="cursor: pointer; margin-right: 30px;">
                <input type="radio" name="target_folder" value="uploads" checked> 📦 Ảnh Sản Phẩm
            </label>
            <label style="cursor: pointer;">
                <input type="radio" name="target_folder" value="banners"> 🎨 Ảnh Banner/Trang Trí
            </label>
        </div>

        <div class="form-group">
            <div class="file-upload-wrapper wrap-green">
                <span class="file-upload-text text-green" id="file-name-images">📁 Chạm để chọn nhiều ảnh cùng lúc...</span>
                <input type="file" id="image_files_input" accept="image/*" multiple>
            </div>
        </div>

        <div class="preview-box" id="preview-box">
            <div class="preview-header">Danh sách ảnh chuẩn bị tải lên (Có thể đổi tên):</div>
            <div class="preview-list" id="preview-list"></div>
            <div class="preview-actions">
                <button type="button" class="btn-add-more" id="btn_add_more">➕ CHỌN THÊM ẢNH</button>
                <button type="button" class="btn-submit btn-green" id="btn_confirm_upload" style="width: auto;">🚀 XÁC NHẬN TẢI LÊN</button>
            </div>
        </div>
    </div>

    <div class="card card-products" id="product-upload-section">
        <h2 class="text-blue">📦 NẠP DỮ LIỆU KHO HÀNG</h2>
        <p class="subtitle">Tải CSV lên -> Sửa thông tin nếu cần -> Đồng bộ vào database</p>
        <div style="margin: 10px 0 15px; background:#e8f4ff; border:1px solid #b8d9ff; border-radius:8px; padding:10px 12px; font-size:13px; color:#0b4d8a;">
            <strong>Luồng chuẩn: kéo-thả ảnh trước, sau đó đồng bộ CSV.</strong><br>
            Tên ảnh tự map theo SKU: <strong>SKU.jpg</strong>, <strong>SKU-2.jpg</strong>...<strong>SKU-5.jpg</strong>.
        </div>
        
        <div class="form-group">
            <div class="file-upload-wrapper wrap-blue">
                <span class="file-upload-text text-blue" id="file-name-products">📁 Chạm để chọn file Kho_Hang.csv...</span>
                <input type="file" id="csv_products_input" accept=".csv">
            </div>
        </div>

        <div class="csv-preview-box" id="csv-preview-box">
            <div class="preview-header text-blue">📝 BẢNG CHỈNH SỬA DỮ LIỆU KHO HÀNG:</div>
            <div class="table-wrapper" id="csv-table-wrapper"></div>
            <div class="preview-actions">
                <button type="button" class="btn-submit btn-blue" id="btn_confirm_csv" style="width: auto;">🚀 LƯU VÀO DATABASE</button>
                <button type="button" class="btn-submit btn-green" id="btn_sync_all" style="width: auto; margin-left: 8px;">⚡ ĐỒNG BỘ TẤT CẢ (ẢNH + CSV)</button>
            </div>
        </div>
    </div>

    <div class="card card-banners" id="banner-upload-section">
        <h2 class="text-red">🎨 THAY ÁO GIAO DIỆN WEB</h2>
        <p class="subtitle">Tải CSV thiết kế mới -> Bấm XEM THỬ -> Xác nhận</p>
        
        <form action="" method="POST" enctype="multipart/form-data" id="form-csv-banners">
            <input type="hidden" name="btn_upload_banners" value="1">
            <div class="form-group">
                <div class="file-upload-wrapper wrap-red">
                    <span class="file-upload-text text-red" id="file-name-banners">📁 Chạm để chọn file Trang_Tri.csv...</span>
                    <input type="file" name="csv_banners" id="csv_banners_input" accept=".csv" required>
                </div>
            </div>
            <button type="submit" class="btn-submit btn-red" id="btn_submit_banners_legacy" style="display: none;">✨ Đổi Banner Sự Kiện</button>
        </form>

        <div id="design-action-box" style="display: none; margin-top: 15px;">
            <div class="action-buttons">
                <button type="button" class="btn-preview" id="btn_preview_design">👁️ XEM THỬ GIAO DIỆN</button>
                <button type="button" class="btn-cancel-file" id="btn_cancel_design">❌ HỦY BỎ FILE</button>
            </div>
            <button type="button" class="btn-submit btn-red" id="btn_confirm_design">🚀 XÁC NHẬN TẢI LÊN SERVER</button>
        </div>
    </div>
</div> <div id="realPreviewModal" class="tanda-modal">
    <div class="tanda-modal-content">
        <div class="tanda-modal-header">
            <h2>👁️ XEM THỬ TRANG CHỦ TANDA</h2>
            <button type="button" class="tanda-modal-close" id="btn_close_preview">ĐÓNG LẠI</button>
        </div>
        <div class="tanda-modal-body">
            <div class="tp-top-bar"><div class="tp-container"><span>📍 Hệ thống showroom</span> &nbsp;&nbsp;&nbsp; <span>📞 Bán hàng trực tuyến</span></div></div>
            <div class="tp-main-header"><div class="tp-container"><div class="tp-logo">TAN<span>DA</span></div></div></div>
            <div class="tp-nav-bar"><div class="tp-container"><div class="tp-nav-category">DANH MỤC SẢN PHẨM</div></div></div>
            
            <div class="tp-container tp-banner-section">
                <div class="tp-banner-top" id="prev-BANNER-CHINH"></div>
                <div class="tp-banner-bottom-row">
                    <div class="tp-banner-item" id="prev-BANNER-PHU-1"></div>
                    <div class="tp-banner-item" id="prev-BANNER-PHU-2"></div>
                    <div class="tp-banner-item" id="prev-BANNER-PHU-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/admin_import.js?v=<?php echo time(); ?>"></script>
</body>
</html>