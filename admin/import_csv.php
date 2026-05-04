<?php
// admin/import_csv.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../cores/db_config.php';

// TỰ ĐỘNG CẬP NHẬT CẤU TRÚC DATABASE (MIGRATION)
try {
    $conn->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS image_file TEXT AFTER description");
} catch (Exception $e) {
    // Nếu đã có cột hoặc có lỗi khác thì bỏ qua
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
// API: IMPORT CSV ĐƠN GIẢN (TỪ MODAL PREVIEW)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'import_csv_simple') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        echo json_encode(['success'=>0,'error'=>'Thiếu file CSV']); exit;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $count = 0;
    
    // Tự động phát hiện dấu phân cách ( , hoặc ; )
    $file_content = file_get_contents($file);
    $delimiter = (substr_count($file_content, ';') > substr_count($file_content, ',')) ? ';' : ',';

    try {
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Bỏ qua dòng đầu (header)
            $headers = fgetcsv($handle, 10000, $delimiter); 
            
            while (($row = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
                if (count($row) < 3) continue;
                
                $sku = trim($row[0] ?? '');
                $cat_code = trim($row[1] ?? '');
                $name = trim($row[2] ?? '');
                $price = (int)str_replace(['.', ','], '', $row[3] ?? 0);
                $sale_price = (int)str_replace(['.', ','], '', $row[4] ?? 0);
                $specs = trim($row[5] ?? '');
                $desc = trim($row[6] ?? '');
                $status = (int)($row[7] ?? 1);

                if ($sku === '' || $name === '') continue;

                // TỰ ĐỘNG TẠO DANH MỤC NẾU CHƯA CÓ
                if ($cat_code !== '') {
                    $chkCat = $conn->prepare("SELECT cat_code FROM categories WHERE cat_code = :code");
                    $chkCat->execute([':code' => $cat_code]);
                    if ($chkCat->rowCount() == 0) {
                        $insCat = $conn->prepare("INSERT INTO categories (cat_code, name, slug, status) VALUES (:code, :name, :slug, 1)");
                        $insCat->execute([':code' => $cat_code, ':name' => $cat_code, ':slug' => createSlug($cat_code)]);
                    }
                }

                $slug = createSlug($name);
                // CHỈ INSERT/UPDATE CÁC TRƯỜNG THÔNG TIN, GIỮ NGUYÊN ẢNH TRONG IMAGE_1
                $sql = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, specs_summary, description, status, sort_order) 
                        VALUES (:sku, :cat, :name, :slug, :price, :sale, :specs, :desc, :stt, :sort)
                        ON DUPLICATE KEY UPDATE cat_code=VALUES(cat_code), name=VALUES(name), slug=VALUES(slug), price=VALUES(price), 
                        sale_price=VALUES(sale_price), specs_summary=VALUES(specs_summary), description=VALUES(description), status=VALUES(status), sort_order=VALUES(sort_order)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug,
                    ':price'=>$price, ':sale'=>$sale_price, ':specs'=>$specs, ':desc'=>$desc, ':stt'=>$status, ':sort'=>$count
                ]);
                $count++;
            }
            fclose($handle);
        }
        echo json_encode(['success'=>1, 'count'=>$count]);
    } catch (Exception $e) {
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
// API: LẤY DANH SÁCH SẢN PHẨM (READ)
// ---------------------------------------------------------
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'get_products') {
    // Sắp xếp theo sort_order để khớp hoàn toàn với thứ tự trong file Excel
    $stmt = $conn->query("SELECT * FROM products ORDER BY sort_order ASC, sku DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ---------------------------------------------------------
// API: LƯU SẢN PHẨM (CREATE / UPDATE)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_all_products') {
    try {
        // TRUNCATE xóa sạch dữ liệu và reset luôn cả ID tự tăng
        $conn->exec("TRUNCATE TABLE products");
        echo json_encode(['success' => 1]);
    } catch (Exception $e) {
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
    $status = (int)($_POST['status'] ?? 1);

    if ($sku === '' || $name === '') { echo json_encode(['success'=>0,'error'=>'Thiếu SKU hoặc Tên']); exit; }

    $slug = createSlug($name);
    $sql = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, specs_summary, description, status) 
            VALUES (:sku, :cat, :name, :slug, :price, :sale, :specs, :desc, :stt)
            ON DUPLICATE KEY UPDATE cat_code=VALUES(cat_code), name=VALUES(name), slug=VALUES(slug), price=VALUES(price), 
            sale_price=VALUES(sale_price), specs_summary=VALUES(specs_summary), description=VALUES(description), status=VALUES(status)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug,
            ':price'=>$price, ':sale'=>$sale_price, ':specs'=>$specs, ':desc'=>$desc, ':stt'=>$status
        ]);
        echo json_encode(['success'=>1]);
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

// ---------------------------------------------------------
// API: CẬP NHẬT HÀNG LOẠT (BULK UPDATE)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'bulk_update') {
    $products = $_POST['products'] ?? [];
    $count = 0;
    if (is_array($products)) {
        $conn->beginTransaction();
        try {
            $uploadsDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

            foreach ($products as $p) {
                $sku = $p['sku'] ?? '';
                $name = $p['name'] ?? '';
                $price = (int)str_replace(['.', ','], '', $p['price'] ?? 0);
                $sale_price = (int)str_replace(['.', ','], '', $p['sale_price'] ?? 0);
                $cat_code = $p['cat_code'] ?? '';
                
                if ($sku && $name) {
                    // 1. Xử lý ảnh mới (Base64) nếu có
                    $new_image_paths = [];
                    if (!empty($p['temp_images']) && is_array($p['temp_images'])) {
                        foreach ($p['temp_images'] as $idx => $base64) {
                            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                                $data = substr($base64, strpos($base64, ',') + 1);
                                $type = strtolower($type[1]); // jpg, png, gif
                                $data = base64_decode($data);
                                if ($data === false) continue;

                                $filename = $sku . '-' . time() . '-new-' . $idx . '.' . $type;
                                $filepath = $uploadsDir . $filename;
                                
                                // Lưu file tạm trước khi optimize
                                file_put_contents($filepath, $data);
                                optimizeAndSaveImage($filepath, $filepath, 800, 80);
                                $new_image_paths[] = $filename;
                            }
                        }
                    }

                    // 2. Lấy danh sách ảnh cũ hiện tại trong DB
                    $stmtOld = $conn->prepare("SELECT image_1 FROM products WHERE sku = :sku");
                    $stmtOld->execute([':sku' => $sku]);
                    $oldRow = $stmtOld->fetch();
                    $current_images = [];
                    if ($oldRow && !empty($oldRow['image_1'])) {
                        $current_images = array_map('trim', explode(',', $oldRow['image_1']));
                    }

                    // 3. Gộp ảnh cũ và ảnh mới
                    $final_images = array_merge($current_images, $new_image_paths);
                    $final_images = array_slice($final_images, 0, 5); // Chốt chặn 5 ảnh cuối cùng
                    $image_string = implode(',', $final_images);

                    // 4. Cập nhật Database
                    $slug = createSlug($name);
                    $sql = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, image_1, status) 
                            VALUES (:sku, :cat, :name, :slug, :price, :sale, :imgs, 1)
                            ON DUPLICATE KEY UPDATE 
                                cat_code=VALUES(cat_code), 
                                name=VALUES(name), 
                                slug=VALUES(slug), 
                                price=VALUES(price), 
                                sale_price=VALUES(sale_price),
                                image_1=VALUES(image_1)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug,
                        ':price'=>$price, ':sale'=>$sale_price, ':imgs'=>$image_string
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
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'validate_zip_csv') {
    if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] != 0) {
        echo json_encode(['success'=>0,'error'=>'ZIP file missing']); exit;
    }
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        echo json_encode(['success'=>0,'error'=>'CSV file missing']); exit;
    }

    $token = uniqid('imp_', true);
    $baseTemp = __DIR__ . '/../uploads/temp_unzip/';
    $tempDir = $baseTemp . $token . '/';
    if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

    $zipTmp = $_FILES['zip_file']['tmp_name'];
    $zipName = $_FILES['zip_file']['name'];
    $savedZip = $tempDir . basename($zipName);
    if (!move_uploaded_file($zipTmp, $savedZip)) {
        echo json_encode(['success'=>0,'error'=>'Failed to move ZIP']); exit;
    }

    if (!unzip_file($savedZip, $tempDir)) {
        echo json_encode(['success'=>0,'error'=>'Failed to unzip file']); exit;
    }

    $csvTmp = $_FILES['csv_file']['tmp_name'];
    $csvSaved = $tempDir . 'data.csv';
    if (!move_uploaded_file($csvTmp, $csvSaved)) {
        echo json_encode(['success'=>0,'error'=>'Failed to save CSV']); exit;
    }

    $missing = [];
    $totalRows = 0;
    if (($handle = fopen($csvSaved, 'r')) !== FALSE) {
        $headers = fgetcsv($handle, 10000, ',');
        if (!$headers) { fclose($handle); echo json_encode(['success'=>0,'error'=>'CSV invalid header']); exit; }
        $imageCol = -1;
        foreach ($headers as $i => $h) {
            if (preg_match('/image|images|image_file|anh/i', $h)) { $imageCol = $i; break; }
        }
        $rowNum = 0;
        while (($row = fgetcsv($handle, 10000, ',')) !== FALSE) {
            $rowNum++; $totalRows++;
            if ($imageCol === -1) continue;
            $imgs = array_map('trim', explode(',', $row[$imageCol] ?? ''));
            foreach ($imgs as $img) {
                if ($img === '') continue;
                if (!file_exists($tempDir . $img)) {
                    $missing[] = ['row'=>$rowNum, 'image'=>$img];
                    if (count($missing) > 20) break;
                }
            }
            if (count($missing) > 20) break;
        }
        fclose($handle);
    }

    if (!empty($missing)) {
        echo json_encode(['success'=>0,'error'=>'missing_images','missing'=>array_slice($missing,0,20)]);
        exit;
    }

    echo json_encode(['success'=>1,'total'=>$totalRows,'token'=>$token]);
    exit;
}

// ---------------------------------------------------------
// API Step 2: Process batch
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'process_batch') {
    $token = $_POST['token'] ?? '';
    $start = (int)($_POST['start'] ?? 0);
    $batch = (int)($_POST['batch'] ?? 10);
    if ($token === '') { echo json_encode(['success'=>0,'error'=>'no_token']); exit; }

    $baseTemp = __DIR__ . '/../uploads/temp_unzip/';
    $tempDir = $baseTemp . $token . '/';
    $csvSaved = $tempDir . 'data.csv';
    if (!is_dir($tempDir) || !file_exists($csvSaved)) { echo json_encode(['success'=>0,'error'=>'temp_missing']); exit; }

    $uploadsDir = __DIR__ . '/../uploads/'; if (!is_dir($uploadsDir)) mkdir($uploadsDir,0777,true);

    $processed = 0; $errors = [];
    if (($handle = fopen($csvSaved,'r')) !== FALSE) {
        $headers = fgetcsv($handle, 10000, ',');
        $map = [];
        foreach ($headers as $i => $h) { $key = trim(strtolower($h)); $map[$key] = $i; }

        $getIdx = function($names, $default) use ($map) {
            foreach ($names as $n) { $k = trim(strtolower($n)); if (isset($map[$k])) return $map[$k]; }
            return $default;
        };

        $idx_sku = $getIdx(['sku'], 0);
        $idx_cat = $getIdx(['cat_code','cat','category'], 1);
        $idx_name = $getIdx(['name','title'], 2);
        $idx_price = $getIdx(['price'], 3);
        $idx_sale = $getIdx(['sale_price','sale'], 4);
        $idx_coupon = $getIdx(['coupon','coupon_code'], 5);
        $idx_image = $getIdx(['image','images','image_file','anh'], -1);
        $idx_specs = $getIdx(['specs','specs_summary'], 8);
        $idx_desc = $getIdx(['description','desc'], 9);
        $idx_status = $getIdx(['status'], 10);

        $rowIndex = 0;
        while (($row = fgetcsv($handle, 10000, ',')) !== FALSE) {
            if ($rowIndex < $start) { $rowIndex++; continue; }
            if ($processed >= $batch) break;

            $sku = trim($row[$idx_sku] ?? '');
            $cat_code = trim($row[$idx_cat] ?? '');
            $name = trim($row[$idx_name] ?? '');
            $price = (int)($row[$idx_price] ?? 0);
            $sale_price = (int)($row[$idx_sale] ?? 0);
            $coupon = trim($row[$idx_coupon] ?? '');
            $specs = trim($row[$idx_specs] ?? '');
            $description = trim($row[$idx_desc] ?? '');
            $status = (int)($row[$idx_status] ?? 1);

            if ($sku === '' || $name === '') { $rowIndex++; continue; }

            // create category if not exists (minimal)
            if ($cat_code !== '') {
                try {
                    $chk = $conn->prepare("SELECT cat_code FROM categories WHERE cat_code = :code LIMIT 1");
                    $chk->execute([':code'=>$cat_code]);
                    if ($chk->rowCount() == 0) {
                        $insc = $conn->prepare("INSERT INTO categories (cat_code, name) VALUES (:code, :name)");
                        $insc->execute([':code'=>$cat_code, ':name'=>$cat_code]);
                    }
                } catch (Exception $e) { /* ignore */ }
            }

            // images
            $image_list = [];
            if ($idx_image !== -1 && isset($row[$idx_image])) {
                $raw = $row[$idx_image];
                $parts = array_map('trim', explode(',', $raw));
                foreach ($parts as $p) if ($p !== '') $image_list[] = $p;
            }
            if (empty($image_list)) {
                for ($i=0;$i<5;$i++) {
                    $suf = $i==0? '': '-' . ($i+1);
                    $found = findExistingSkuImage($sku, $suf);
                    if ($found) $image_list[] = $found;
                }
            }

            $savedNames = ['', '', '', '', ''];
            $countImg = 0;
            foreach ($image_list as $i => $imgName) {
                if ($countImg >= 5) break;
                $src = $tempDir . $imgName;
                if (!file_exists($src)) continue;
                $dest = $uploadsDir . basename($imgName);
                $base = pathinfo($dest, PATHINFO_FILENAME);
                $ext = pathinfo($dest, PATHINFO_EXTENSION);
                $final = $base . '.' . $ext;
                $j = 1;
                while (file_exists($uploadsDir . $final)) { $final = $base . '-' . $j . '.' . $ext; $j++; }
                $finalPath = $uploadsDir . $final;
                if (optimizeAndSaveImage($src, $finalPath, 800, 80)) {
                    $savedNames[$countImg] = $final;
                    $countImg++;
                } else if (@copy($src, $finalPath)) {
                    $savedNames[$countImg] = $final; $countImg++;
                }
            }

            $slug = createSlug($name);
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

            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':sku'=>$sku, ':cat'=>$cat_code, ':name'=>$name, ':slug'=>$slug, 
                    ':price'=>$price, ':sale'=>$sale_price, ':coupon'=>$coupon, 
                    ':img'=>$savedNames[0], ':img2'=>$savedNames[1], ':img3'=>$savedNames[2], ':img4'=>$savedNames[3], ':img5'=>$savedNames[4],
                    ':specs'=>$specs, ':desc'=>$description, ':stt'=>$status, ':sort'=>$start + $rowIndex
                ]);
            } catch (Exception $e) {
                $errors[] = ['sku'=>$sku,'error'=>$e->getMessage()];
            }

            $processed++; $rowIndex++;
        }
        fclose($handle);
    }

    echo json_encode(['success'=>1,'processed'=>$processed,'errors'=>$errors]);
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
        <h1 class="admin-title"><i class="fas fa-box-open" style="color: var(--primary-color);"></i> Quản lý Sản phẩm</h1>
        <div style="display: flex; gap: 12px;">
            <button type="button" class="btn btn-success" id="btn-save-all">
                <i class="fas fa-save"></i> CHỐT LƯU 
            </button>
            <button type="button" class="btn btn-danger" onclick="deleteAllProducts()">
                <i class="fas fa-trash-alt"></i> XÓA TẤT CẢ SẢN PHẨM
            </button>
            <button type="button" class="btn btn-outline" onclick="showImportModal()">
                <i class="fas fa-file-import"></i> Nhập từ Excel
            </button>
            <button type="button" class="btn btn-primary" onclick="showProductModal()">
                <i class="fas fa-plus"></i> Thêm sản phẩm
            </button>
        </div>
    </header>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 15px 24px; background: #faf9f8; border-bottom: 1px solid #edebe9;">
            <span class="muted" style="font-size: 13px; color: #605e5c;">
                <i class="fas fa-info-circle"></i> <strong>Hướng dẫn:</strong> Nhấp trực tiếp vào <strong>Tên</strong> hoặc <strong>Giá</strong> để sửa nhanh. Kéo thả ảnh trực tiếp vào cột <strong>Ảnh</strong>.
            </span>
        </div>
        
        <div style="overflow-x:auto;">
            <table class="data-table" id="product-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Hình ảnh sản phẩm</th>
                        <th style="width: 100px;">Mã SKU</th>
                        <th>Thông tin sản phẩm & Danh mục</th>
                        <th style="width: 180px;">Giá bán / Khuyến mãi</th>
                        <th style="width: 150px; text-align: center;">Thao tác</th>
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
                <div class="form-group" style="grid-column: span 2;">
                    <label>Thông số kỹ thuật tóm tắt</label>
                    <input type="text" id="p_specs" name="specs_summary" placeholder="Cách nhau bằng dấu |">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Mô tả chi tiết</label>
                    <textarea id="p_desc" name="description" rows="6"></textarea>
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
</body>
</html>