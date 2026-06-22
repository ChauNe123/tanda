<?php
// admin/import_csv.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../cores/db_config.php';

// TỰ ĐỘNG CẬP NHẬT CẤU TRÚC DATABASE (MIGRATION)
// Lấy danh sách cột hiện có để kiểm tra trước khi ALTER
try {
    $existingCols = [];
    $colQuery = $conn->query("SHOW COLUMNS FROM products");
    while ($col = $colQuery->fetch(PDO::FETCH_ASSOC)) {
        $existingCols[] = $col['Field'];
    }

    $migrations = [
        'image_1'       => "ALTER TABLE products ADD COLUMN image_1 TEXT NULL AFTER description",
        'image_file'    => "ALTER TABLE products ADD COLUMN image_file TEXT AFTER description",
        'specs_group_1' => "ALTER TABLE products ADD COLUMN specs_group_1 LONGTEXT NULL AFTER specs_summary",
        'specs_group_2' => "ALTER TABLE products ADD COLUMN specs_group_2 LONGTEXT NULL AFTER specs_group_1",
        'specs_group_3' => "ALTER TABLE products ADD COLUMN specs_group_3 LONGTEXT NULL AFTER specs_group_2",
        'specs_group_4' => "ALTER TABLE products ADD COLUMN specs_group_4 LONGTEXT NULL AFTER specs_group_3",
    ];

    foreach ($migrations as $colName => $sql) {
        if (!in_array($colName, $existingCols)) {
            $conn->exec($sql);
        }
    }
} catch (Exception $e) {
    // Ghi log lỗi migration để debug, không làm gián đoạn trang
    error_log("TANDA import_csv migration error: " . $e->getMessage());
}

// =========================================================
// HÀM TỐI ƯU DUNG LƯỢNG & KÍCH THƯỚC ẢNH UPLOAD (GD LIBRARY)
// =========================================================
function optimizeAndSaveImage($sourcePath, $destPath, $maxWidth = 800, $quality = 80) {
    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $width = $info[0];
    $height = $info[1];
    $mime = $info['mime'];

    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = floor(($height / $width) * $newWidth);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($mime == 'image/png' || $mime == 'image/webp' || $mime == 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    switch ($mime) {
        case 'image/jpeg': $sourceImage = imagecreatefromjpeg($sourcePath); break;
        case 'image/png':  $sourceImage = imagecreatefrompng($sourcePath); break;
        case 'image/gif':  $sourceImage = imagecreatefromgif($sourcePath); break;
        case 'image/webp': $sourceImage = imagecreatefromwebp($sourcePath); break;
        default: return false;
    }

    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $success = false;
    switch ($mime) {
        case 'image/jpeg':
            $success = imagejpeg($newImage, $destPath, $quality);
            break;
        case 'image/png':
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

// unzip helper
function unzip_file($zipPath, $destDir) {
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === TRUE) {
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        $res = $zip->extractTo($destDir);
        $zip->close();
        return $res;
    }
    return false;
}

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
// API: IMPORT CSV ĐƠN GIẢN (TỐI ƯU SIÊU TỐC - TRANSACTION & RAM CACHE)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'import_csv_simple') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        echo json_encode(['success'=>0,'error'=>'Thiếu file CSV']); exit;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $count = 0;
    
    $file_content = file_get_contents($file);
    $delimiter = (substr_count($file_content, ';') > substr_count($file_content, ',')) ? ';' : ',';

    try {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 10000, $delimiter); 
            
            // 1. TỐI ƯU BỘ NHỚ: Load sẵn toàn bộ mã danh mục lên RAM (Chỉ tốn 1 query duy nhất)
            $stmtAllCats = $conn->query("SELECT cat_code FROM categories");
            $existingCats = array_flip($stmtAllCats->fetchAll(PDO::FETCH_COLUMN)); 
            
            // 2. CHUẨN BỊ SẴN CÂU LỆNH (Tránh việc MySQL phải dịch lại mã SQL hàng ngàn lần)
            $insCat = $conn->prepare("INSERT INTO categories (cat_code, name, slug, status) VALUES (:code, :name, :slug, 1) ON DUPLICATE KEY UPDATE status=1");
            
            $sqlProd = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, specs_summary, specs_group_1, specs_group_2, specs_group_3, specs_group_4, description, status, sort_order) 
                        VALUES (:sku, :cat, :name, :slug, :price, :sale, :specs, :sg1, :sg2, :sg3, :sg4, :desc, :stt, :sort)
                        ON DUPLICATE KEY UPDATE cat_code=VALUES(cat_code), name=VALUES(name), slug=VALUES(slug), price=VALUES(price), 
                        sale_price=VALUES(sale_price), specs_summary=VALUES(specs_summary), specs_group_1=VALUES(specs_group_1), specs_group_2=VALUES(specs_group_2), specs_group_3=VALUES(specs_group_3), specs_group_4=VALUES(specs_group_4), description=VALUES(description), status=VALUES(status), sort_order=VALUES(sort_order)";
            $stmtProd = $conn->prepare($sqlProd);

            // 3. KHỞI ĐỘNG TRANSACTION: Khóa Database, gom tất cả dữ liệu nạp vào 1 cục
            $conn->beginTransaction();
            
            while (($row = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
                if (count($row) < 3) continue;
                
                $sku = trim($row[0] ?? '');
                $cat_code = trim($row[1] ?? '');
                $name = trim($row[2] ?? '');
                $price = (int)str_replace(['.', ','], '', $row[3] ?? 0);
                $sale_price = (int)str_replace(['.', ','], '', $row[4] ?? 0);
                $specs = $row[5] ?? '';
                // Convert newlines to pipe separator for specs
                $specs = str_replace(["\r\n", "\r", "\n"], '|', $specs);
                $specs = preg_replace('/\|+/', '|', $specs);
                $specs = trim($specs, "| \t");

                $specsG1 = $row[8] ?? '';
                $specsG2 = $row[9] ?? '';
                $specsG3 = $row[10] ?? '';
                $specsG4 = $row[11] ?? '';

                // Auto-map: Dồn tất cả specs vào group 1 (Thông số kỹ thuật chung).
                // KH có thể thêm group 2-4 thủ công sau nếu muốn phân loại chi tiết.
                // Group nào rỗng sẽ KHÔNG hiển thị trên website.
                if (!empty($specs) && empty($specsG1) && empty($specsG2) && empty($specsG3) && empty($specsG4)) {
                    $specsG1 = $specs;
                }

                $desc = $row[6] ?? '';
                // Decode HTML entities, preserve HTML content
                $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $desc = trim($desc);

                $status = (int)($row[7] ?? 1);

                if ($sku === '' || $name === '') continue;

                // TỐI ƯU: Kiểm tra trên RAM thay vì query Database
                if ($cat_code !== '' && !isset($existingCats[$cat_code])) {
                    $insCat->execute([':code' => $cat_code, ':name' => $cat_code, ':slug' => createSlug($cat_code)]);
                    // Thêm ngay vào mảng RAM để dòng sau không bị trùng
                    $existingCats[$cat_code] = true; 
                }

                $slug = createSlug($name);
                
                $stmtProd->execute([
                    ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug,
                    ':price'=>$price, ':sale'=>$sale_price, ':specs'=>$specs, ':sg1'=>$specsG1, ':sg2'=>$specsG2, ':sg3'=>$specsG3, ':sg4'=>$specsG4, ':desc'=>$desc, ':stt'=>$status, ':sort'=>$count
                ]);
                $count++;
            }
            // 4. CHỐT GHI VÀO Ổ CỨNG: (Chỉ tốn 1 lần thao tác vật lý)
            $conn->commit();
            fclose($handle);
        }
        echo json_encode(['success'=>1, 'count'=>$count]);
    } catch (Exception $e) {
        // Nếu lỡ có lỗi giữa chừng, RollBack (hủy toàn bộ) để DB không bị rác
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success'=>0, 'error'=> 'Lỗi SQL: ' . $e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------
// API: UPLOAD ẢNH & CẬP NHẬT DATABASE NGAY LẬP TỨC
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'upload_images') {
    $sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';
    if ($sku === '') { echo json_encode(['success' => 0, 'error' => 'Missing SKU']); exit; }

    $target_dir = __DIR__ . '/../uploads/';
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $count_success = 0;
    $new_filenames = [];
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $error = $_FILES['images']['error'][$key];
            if ($error === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                // Đặt tên theo SKU để quản lý gọn (vd: WIFI-001-timestamp.jpg)
                $final_name = $sku . '-' . time() . '-' . $key . '.' . $ext;
                $target_file = $target_dir . $final_name;

                if (in_array($ext, $allowed_types)) {
                    if (optimizeAndSaveImage($tmp_name, $target_file, 800, 80)) {
                        $new_filenames[] = $final_name;
                        $count_success++;
                    }
                }
            }
        }
    }

    if ($count_success > 0) {
        // Cập nhật Database: Lấy danh sách ảnh cũ + ảnh mới
        $stmt = $conn->prepare("SELECT image_1 FROM products WHERE sku = :sku");
        $stmt->execute([':sku' => $sku]);
        $row = $stmt->fetch();
        
        $current_images = [];
        if ($row && !empty($row['image_1'])) {
            $current_images = array_map('trim', explode(',', $row['image_1']));
        }
        
        $updated_images = array_merge($current_images, $new_filenames);
        $image_string = implode(',', $updated_images);

        $upd = $conn->prepare("UPDATE products SET image_1 = :imgs WHERE sku = :sku");
        $upd->execute([':imgs' => $image_string, ':sku' => $sku]);
    }

    echo json_encode(['success' => $count_success, 'filenames' => $new_filenames]);
    exit;
}

// ---------------------------------------------------------
// API: LẤY DANH SÁCH SẢN PHẨM (READ) - CÓ PHÂN TRANG & TÌM KIẾM
// ---------------------------------------------------------
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'get_products') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 25)));
    $search = trim($_GET['search'] ?? '');
    $offset = ($page - 1) * $perPage;
    
    $where = '';
    $params = [];
    
    if (!empty($search)) {
        $where = " WHERE (sku LIKE :s1 OR name LIKE :s2 OR cat_code LIKE :s3)";
        $params[':s1'] = "%{$search}%";
        $params[':s2'] = "%{$search}%";
        $params[':s3'] = "%{$search}%";
    }
    
    // Đếm tổng
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM products" . $where);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    // Lấy dữ liệu trang hiện tại
    $stmt = $conn->prepare("SELECT * FROM products" . $where . " ORDER BY sort_order ASC, sku DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------
// API: XÓA TẤT CẢ SẢN PHẨM & DANH MỤC
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_all_products') {
    try {
        // 1. Tắt tạm thời kiểm tra ràng buộc khóa ngoại
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 2. Xóa sạch dữ liệu và reset ID tự tăng của cả 2 bảng
        $conn->exec("TRUNCATE TABLE products;");
        $conn->exec("TRUNCATE TABLE categories;");

        // 3. Bật lại kiểm tra khóa ngoại ngay lập tức để bảo vệ hệ thống
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        echo json_encode(['success' => 1]);
    } catch (Exception $e) {
        // Đảm bảo khóa ngoại luôn được bật lại kể cả khi có lỗi xảy ra
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo json_encode(['success' => 0, 'error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_product') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    // Xóa dấu chấm, phẩy trước khi lưu vào Database
    $price = (int)str_replace(['.', ','], '', $_POST['price'] ?? 0);
    $sale_price = (int)str_replace(['.', ','], '', $_POST['sale_price'] ?? 0);
    $cat_code = trim($_POST['cat_code'] ?? '');
    $desc = $_POST['description'] ?? '';
    $specs = $_POST['specs_summary'] ?? '';
    $specsG1 = $_POST['specs_group_1'] ?? '';
    $specsG2 = $_POST['specs_group_2'] ?? '';
    $specsG3 = $_POST['specs_group_3'] ?? '';
    $specsG4 = $_POST['specs_group_4'] ?? '';
    $status = (int)($_POST['status'] ?? 1);

    if ($sku === '' || $name === '') { echo json_encode(['success'=>0,'error'=>'Thiếu SKU hoặc Tên']); exit; }

    $slug = createSlug($name);
    $sql = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, specs_summary, specs_group_1, specs_group_2, specs_group_3, specs_group_4, description, status) 
            VALUES (:sku, :cat, :name, :slug, :price, :sale, :specs, :sg1, :sg2, :sg3, :sg4, :desc, :stt)
            ON DUPLICATE KEY UPDATE cat_code=VALUES(cat_code), name=VALUES(name), slug=VALUES(slug), price=VALUES(price), 
            sale_price=VALUES(sale_price), specs_summary=VALUES(specs_summary), specs_group_1=VALUES(specs_group_1), specs_group_2=VALUES(specs_group_2), specs_group_3=VALUES(specs_group_3), specs_group_4=VALUES(specs_group_4), description=VALUES(description), status=VALUES(status)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug,
            ':price'=>$price, ':sale'=>$sale_price, ':specs'=>$specs, ':sg1'=>$specsG1, ':sg2'=>$specsG2, ':sg3'=>$specsG3, ':sg4'=>$specsG4, ':desc'=>$desc, ':stt'=>$status
        ]);
        echo json_encode(['success'=>1]);
    } catch (Exception $e) {
        echo json_encode(['success'=>0, 'error'=>$e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------
// API: LƯU GIÁ TRỰC TIẾP (INLINE EDIT - CHỈ CẬP NHẬT GIÁ)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_price_inline') {
    $sku = trim($_POST['sku'] ?? '');
    $field = trim($_POST['field'] ?? '');
    $value = trim($_POST['value'] ?? '');
    
    if ($sku === '' || !in_array($field, ['price', 'sale_price'])) {
        echo json_encode(['success'=>0, 'error'=>'Dữ liệu không hợp lệ']);
        exit;
    }
    
    // Chỉ nhận số, bỏ hết ký tự không phải số
    $value = preg_replace('/[^0-9]/', '', $value);
    $value = $value === '' ? '0' : $value;
    
    try {
        $stmt = $conn->prepare("UPDATE products SET {$field} = :val WHERE sku = :sku");
        $stmt->execute([':val' => $value, ':sku' => $sku]);
        
        // Lấy giá trị mới để trả về
        $fetch = $conn->prepare("SELECT price, sale_price FROM products WHERE sku = :sku");
        $fetch->execute([':sku' => $sku]);
        $row = $fetch->fetch();
        
        echo json_encode([
            'success' => 1,
            'price' => (int)$row['price'],
            'sale_price' => (int)$row['sale_price']
        ]);
    } catch (Exception $e) {
        echo json_encode(['success'=>0, 'error'=>$e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------
// API: BẬT/TẮT TRẠNG THÁI CÒN HÀNG / HẾT HÀNG (TOGGLE)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'toggle_status') {
    $sku = trim($_POST['sku'] ?? '');
    $status = (int)($_POST['status'] ?? 0);
    
    if ($sku === '') {
        echo json_encode(['success'=>0, 'error'=>'Thiếu SKU']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE products SET status = :stt WHERE sku = :sku");
        $stmt->execute([':stt' => $status, ':sku' => $sku]);
        echo json_encode(['success'=>1, 'new_status' => $status]);
    } catch (Exception $e) {
        echo json_encode(['success'=>0, 'error'=>$e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------
// API: XÓA SẢN PHẨM (DELETE)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_product') {
    $sku = $_POST['sku'] ?? '';
    if ($sku) {
        $stmt = $conn->prepare("DELETE FROM products WHERE sku = :sku");
        $stmt->execute([':sku' => $sku]);
        echo json_encode(['success'=>1]);
    } else {
        echo json_encode(['success'=>0]);
    }
    exit;
}

// ---------------------------------------------------------
// API: XÓA 1 ẢNH KHỎI DANH SÁCH (GALLERY)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'remove_image') {
    $sku = $_POST['sku'] ?? '';
    $filename = $_POST['filename'] ?? '';
    if ($sku && $filename) {
        $stmt = $conn->prepare("SELECT image_1 FROM products WHERE sku = :sku");
        $stmt->execute([':sku' => $sku]);
        $row = $stmt->fetch();
        if ($row) {
            $imgs = array_map('trim', explode(',', $row['image_1']));
            $updated = array_filter($imgs, function($i) use ($filename) { return $i !== $filename; });
            $image_string = implode(',', $updated);

            $upd = $conn->prepare("UPDATE products SET image_1 = :imgs WHERE sku = :sku");
            $upd->execute([':imgs' => $image_string, ':sku' => $sku]);
            
            // Tùy chọn: Xóa file vật lý trong uploads (cẩn thận nếu ảnh dùng chung)
            // @unlink(__DIR__ . '/../uploads/' . $filename);
            
            echo json_encode(['success'=>1]); exit;
        }
    }
    echo json_encode(['success'=>0]);
    exit;
}

// --- THAY THẾ TOÀN BỘ API: CẬP NHẬT HÀNG LOẠT ---
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'bulk_update') {
    $products = $_POST['products'] ?? [];
    $count = 0;
    if (is_array($products)) {
        $conn->beginTransaction();
        try {
            $uploadsDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

            $stmtAllCats = $conn->query("SELECT cat_code FROM categories");
            $existingCats = array_flip($stmtAllCats->fetchAll(PDO::FETCH_COLUMN)); 
            $insCat = $conn->prepare("INSERT INTO categories (cat_code, name, slug, status) VALUES (:code, :name, :slug, 1) ON DUPLICATE KEY UPDATE status=1");
            $sqlProd = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, specs_summary, specs_group_1, specs_group_2, specs_group_3, specs_group_4, description, image_1, status) 
                        VALUES (:sku, :cat, :name, :slug, :price, :sale, :specs, :sg1, :sg2, :sg3, :sg4, :desc, :imgs, 1)
                        ON DUPLICATE KEY UPDATE 
                            cat_code=VALUES(cat_code), 
                            name=VALUES(name), 
                            slug=VALUES(slug), 
                            price=VALUES(price), 
                            sale_price=VALUES(sale_price),
                            specs_summary=VALUES(specs_summary),
                            specs_group_1=VALUES(specs_group_1),
                            specs_group_2=VALUES(specs_group_2),
                            specs_group_3=VALUES(specs_group_3),
                            specs_group_4=VALUES(specs_group_4),
                            description=VALUES(description),
                            image_1=VALUES(image_1)";
            $stmtProd = $conn->prepare($sqlProd);

            foreach ($products as $p) {
                $sku = $p['sku'] ?? '';
                $name = $p['name'] ?? '';
                $price = (int)str_replace(['.', ','], '', $p['price'] ?? 0);
                $sale_price = (int)str_replace(['.', ','], '', $p['sale_price'] ?? 0);
                $cat_code = $p['cat_code'] ?? '';
                $specs = $p['specs_summary'] ?? '';
                $specsG1 = $p['specs_group_1'] ?? '';
                $specsG2 = $p['specs_group_2'] ?? '';
                $specsG3 = $p['specs_group_3'] ?? '';
                $specsG4 = $p['specs_group_4'] ?? '';
                $desc = $p['description'] ?? '';
                
                if ($sku && $name) {
                    if ($cat_code !== '' && !isset($existingCats[$cat_code])) {
                        $insCat->execute([':code' => $cat_code, ':name' => $cat_code, ':slug' => createSlug($cat_code)]);
                        $existingCats[$cat_code] = true;
                    }

                    $new_image_paths = [];
                    if (!empty($p['temp_images']) && is_array($p['temp_images'])) {
                        foreach ($p['temp_images'] as $idx => $base64) {
                            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                                $data = substr($base64, strpos($base64, ',') + 1);
                                $type = strtolower($type[1]);
                                $data = base64_decode($data);
                                if ($data === false) continue;

                                $hash = md5($data);
                                $filename = $sku . '-' . $hash . '.' . $type;
                                $filepath = $uploadsDir . $filename;
                                if (!file_exists($filepath)) {
                                    file_put_contents($filepath, $data);
                                    optimizeAndSaveImage($filepath, $filepath, 800, 80);
                                }
                                $new_image_paths[] = $filename;
                            }
                        }
                    }

                    $stmtOld = $conn->prepare("SELECT image_1 FROM products WHERE sku = :sku");
                    $stmtOld->execute([':sku' => $sku]);
                    $oldRow = $stmtOld->fetch();
                    $current_images = [];
                    if ($oldRow && !empty($oldRow['image_1'])) {
                        $current_images = array_map('trim', explode(',', $oldRow['image_1']));
                    }

                    // Deduplicate: merge + unique + remove non-existent files
                    $merged_images = array_merge($current_images, $new_image_paths);
                    $merged_images = array_map('trim', $merged_images);
                    $merged_images = array_filter($merged_images);
                    $final_images = array_unique($merged_images);
                    $final_images = array_values($final_images);
                    // Remove images that no longer exist on disk
                    $final_images = array_filter($final_images, function($img) use ($uploadsDir) {
                        return file_exists($uploadsDir . $img);
                    });
                    $final_images = array_values($final_images);
                    $final_images = array_slice($final_images, 0, 5);
                    $image_string = implode(',', $final_images);

                    $slug = createSlug($name);
                    $stmtProd->execute([
                        ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug,
                        ':price'=>$price, ':sale'=>$sale_price, ':specs'=>$specs, ':sg1'=>$specsG1, ':sg2'=>$specsG2, ':sg3'=>$specsG3, ':sg4'=>$specsG4, ':desc'=>$desc, ':imgs'=>$image_string
                    ]);
                    $count++;
                }
            }
            $conn->commit(); 
            echo json_encode(['success'=>1, 'count'=>$count]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success'=>0, 'error'=>$e->getMessage()]);
        }
    } else {
        echo json_encode(['success'=>0, 'error'=>'Dữ liệu không hợp lệ']);
    }
    exit;
}

// ---------------------------------------------------------
// API Step 3: Cleanup temp
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'cleanup_temp') {
    $token = $_POST['token'] ?? '';
    if ($token === '') { echo json_encode(['success'=>0]); exit; }
    $baseTemp = __DIR__ . '/../uploads/temp_unzip/';
    $tempDir = $baseTemp . $token . '/';
    if (!function_exists('rrmdir')) {
        function rrmdir($dir) {
            if (!is_dir($dir)) return;
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object == '.' || $object == '..') continue;
                $path = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($path)) rrmdir($path); else @unlink($path);
            }
            @rmdir($dir);
        }
    }
    rrmdir($tempDir);
    echo json_encode(['success'=>1]); exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị Hệ thống - TANDA</title>
    <script>
    // BỊT MIỆNG LỖI TỪ TIỆN ÍCH TRÌNH DUYỆT (ONBOARDING.JS)
    window.addEventListener('unhandledrejection', function (event) {
        if (event.reason === undefined || (event.reason && event.reason.stack && event.reason.stack.includes('onboarding.js'))) {
            event.preventDefault();
        }
    });
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-container">
    <!-- STICKY HEADER TOOLBAR -->
    <header class="admin-header">
        <h1 class="admin-title"><i class="fas fa-box-open"></i> Quản lý Sản phẩm</h1>
        <div class="admin-header-actions">
            <button type="button" class="btn btn-outline" onclick="exportToCSV()" style="color:#059669;border-color:#a7f3d0;">
                <i class="fas fa-file-export"></i> Xuất Excel
            </button>
            <button type="button" class="btn btn-success" id="btn-save-all">
                <i class="fas fa-save"></i> CHỐT LƯU
            </button>
            <button type="button" class="btn btn-danger" onclick="deleteAllProducts()">
                <i class="fas fa-trash-alt"></i> XÓA TẤT CẢ
            </button>
            <input type="file" id="direct_csv_upload" accept=".csv" style="display: none;">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('direct_csv_upload').click()">
                <i class="fas fa-file-import"></i> Nạp Excel
            </button>
            <button type="button" class="btn btn-primary" onclick="showProductModal()">
                <i class="fas fa-plus"></i> Thêm SP
            </button>
            <button type="button" class="btn btn-outline" id="btn-cancel-pending" onclick="cancelPendingMode()" style="display:none;color:#dc2626;border-color:#fecaca;">
                <i class="fas fa-times"></i> HỦY IMPORT
            </button>
        </div>
    </header>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="toolbar-row">
            <span class="admin-info-bar">
                <i class="fas fa-spinner fa-spin"></i> Đang tải...
            </span>
            <div class="admin-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="admin-search-input" class="admin-search-input" placeholder="Tìm SKU, tên, danh mục..." oninput="debouncedSearch(this.value)">
            </div>
        </div>
        
        <!-- MOBILE CARD VIEW -->
        <div class="mobile-cards" id="mobile-cards-container">
            <!-- Rendered by JS -->
        </div>
        
        <!-- DESKTOP TABLE VIEW -->
        <div class="table-wrapper">
            <table class="data-table" id="product-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Hình ảnh</th>
                        <th style="width: 100px;">Mã SKU</th>
                        <th>Thông tin & Danh mục</th>
                        <th style="width: 180px;">Giá bán / Khuyến mãi</th>
                        <th style="width: 100px; text-align: center;">Trạng thái</th>
                        <th style="width: 130px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="product-list-body">
                    <!-- Dữ liệu được nạp qua AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Log has been moved to Browser Console (DevTools) -->
</div>

<!-- MODALS (Giữ nguyên logic cũ nhưng cập nhật Style) -->
<div id="import-modal" class="modal-overlay">
    <div class="modal-card">
        <h3><i class="fas fa-file-excel" style="color: #107c10;"></i> Nhập dữ liệu từ Excel (CSV)</h3>
        <p style="color: #666; font-size: 14px;">Vui lòng chọn file CSV đúng định dạng mapping để nạp sản phẩm hàng loạt.</p>
        <div style="margin: 20px 0; padding: 20px; background: #f3f2f1; border-radius: 4px; text-align: center;">
            <input type="file" id="csv_file_input" accept=".csv">
        </div>
        <div id="csv-preview-wrap" style="display:none; margin-top:15px;">
            <h4 style="font-size: 14px;">Xem trước dữ liệu:</h4>
            <div id="csv-preview-table" style="max-height:200px; overflow-y:auto; border:1px solid #edebe9;"></div>
            <div style="margin-top:20px; text-align:right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="hideImportModal()">Hủy bỏ</button>
                <button type="button" class="btn btn-success" id="btn_confirm_import">Bắt đầu nạp dữ liệu</button>
            </div>
        </div>
    </div>
</div>

<!-- Các Modals khác giữ nguyên logic ẩn/hiện -->
<div id="product-modal" class="modal-overlay">
    <div class="modal-card" style="width: 800px;">
        <h3 id="modal-title">Sản phẩm</h3>
        <form id="product-form">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="form-group">
                    <label>Mã sản phẩm (SKU)</label>
                    <input type="text" id="p_sku" name="sku" required>
                </div>
                <div class="form-group">
                    <label>Mã danh mục</label>
                    <input type="text" id="p_cat" name="cat_code">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Tên sản phẩm</label>
                    <input type="text" id="p_name" name="name" required>
                </div>
                <div class="form-group">
                    <label>Giá niêm yết (Price)</label>
                    <div style="position: relative;">
                        <input type="text" id="p_price" name="price" class="format-currency" style="padding-right: 45px;">
                        <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #888; font-size: 13px; pointer-events: none;">VNĐ</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Giá khuyến mãi (Sale)</label>
                    <div style="position: relative;">
                        <input type="text" id="p_sale" name="sale_price" class="format-currency" style="padding-right: 45px;">
                        <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #888; font-size: 13px; pointer-events: none;">VNĐ</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ngày áp dụng (dd/mm/yyyy)</label>
                    <input type="text" id="p_date" name="apply_date" class="format-date" placeholder="Nhập số liền: 25122024...">
                </div>
                <input type="hidden" id="p_specs" name="specs_summary" value="">
                <div class="form-group" style="grid-column: span 2;">
                    <label><i class="fas fa-microchip"></i> Thông số kỹ thuật (4 nhóm)</label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top: 5px;">
                        <div class="spec-group-admin">
                            <h4><i class="fas fa-video"></i> Thông số kỹ thuật</h4>
                            <textarea id="specs_group_1" name="specs_group_1" rows="5" placeholder="Độ phân giải: 3MP&#10;Góc nhìn: 360 độ&#10;Đàm thoại: Có"></textarea>
                        </div>
                        <div class="spec-group-admin">
                            <h4><i class="fas fa-wifi"></i> Kết nối & Lưu trữ</h4>
                            <textarea id="specs_group_2" name="specs_group_2" rows="5" placeholder="Kết nối: WiFi&#10;Thẻ nhớ: 256GB&#10;Cloud: Có"></textarea>
                        </div>
                        <div class="spec-group-admin">
                            <h4><i class="fas fa-bolt"></i> Nguồn điện & Điều kiện sử dụng</h4>
                            <textarea id="specs_group_3" name="specs_group_3" rows="5" placeholder="Nguồn điện: 5V&#10;Nhiệt độ: -10°C ~ 45°C&#10;Chống nước: IP66"></textarea>
                        </div>
                        <div class="spec-group-admin">
                            <h4><i class="fas fa-tools"></i> Lắp đặt & Thiết bị hỗ trợ</h4>
                            <textarea id="specs_group_4" name="specs_group_4" rows="5" placeholder="Lắp đặt: Treo tường&#10;Hỗ trợ: Android / iOS&#10;Bảo hành: 24 tháng"></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Mô tả chi tiết (hỗ trợ HTML rich text)</label>
                    <textarea id="p_desc" name="description" rows="8"></textarea>
                </div>
            </div>
            <div style="margin-top:25px; text-align:right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="hideProductModal()">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<div id="lightbox-modal" onclick="this.style.display='none'">
    <img id="lightbox-img" src="">
</div>

<script src="../assets/js/admin_import.js?v=<?php echo time(); ?>"></script>

<!-- CKEditor 5 cho Mô tả chi tiết -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
var tandaEditor = null;
var editorInitialized = false;
document.addEventListener('DOMContentLoaded', function() {
    if (editorInitialized) return;
    var editorEl = document.querySelector('#p_desc');
    if (editorEl && typeof ClassicEditor !== 'undefined') {
        editorInitialized = true;
        ClassicEditor
            .create(editorEl, {
                toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo']
            })
            .then(function(editor) {
                tandaEditor = editor;
            })
            .catch(function(error) {
                editorInitialized = false;
                console.error('CKEditor error:', error);
            });
    }
});
</script>
</body>
</html>