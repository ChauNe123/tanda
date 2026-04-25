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
// Existing lightweight image upload handler (kept)
// ---------------------------------------------------------
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'upload_images') {
    $folder_name = (isset($_POST['target_folder']) && $_POST['target_folder'] == 'banners') ? 'banners' : 'uploads';
    $target_dir = __DIR__ . '/../' . $folder_name . '/';
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $count_success = 0; $count_error = 0;
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $error = $_FILES['images']['error'][$key];
            if ($error === UPLOAD_ERR_OK) {
                $imageFileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $new_name_post = isset($_POST['new_names'][$key]) ? trim($_POST['new_names'][$key]) : '';
                $final_name = ($new_name_post !== '') ? $new_name_post : $name;
                $target_file = $target_dir . $final_name;
                if (in_array($imageFileType, $allowed_types)) {
                    if (optimizeAndSaveImage($tmp_name, $target_file, 800, 80)) $count_success++;
                    else if (move_uploaded_file($tmp_name, $target_file)) $count_success++;
                    else $count_error++;
                } else $count_error++;
            } else $count_error++;
        }
    }
    echo json_encode(['success' => $count_success, 'error' => $count_error]);
    exit;
}

// ---------------------------------------------------------
// API Step 1: Validate ZIP + CSV
// ---------------------------------------------------------
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
    <title>Import CSV + ZIP - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        .center { max-width:900px; margin:30px auto; padding:18px; }
        .progress { width:100%; background:#eee; border-radius:6px; overflow:hidden; height:18px; }
        .progress > .bar { height:18px; background:#28a745; width:0%; color:#fff; text-align:center; font-size:12px; line-height:18px; }
        .card { background:#fff; padding:16px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        .muted { color:#666; font-size:13px; }
        .btn { padding:8px 14px; border:none; border-radius:6px; cursor:pointer; }
        .btn-green { background:#28a745; color:#fff; }
        .btn-gray { background:#f0f0f0; color:#333; }
        pre { white-space:pre-wrap; word-break:break-word; background:#f8f9fa; padding:10px; border-radius:6px; }
    </style>
</head>
<body>
<div class="center">
    <div class="card">
        <h2>📦 Import: ZIP (images) + CSV (data)</h2>
        <p class="muted">CSV column must contain image filenames in a column named <strong>Image</strong> (comma-separated). Max 5 images per product.</p>

        <form id="import-form" enctype="multipart/form-data" method="post">
            <div style="margin-bottom:12px;">
                <label>Chọn file ZIP (hình ảnh):</label><br>
                <input type="file" id="zip_file" name="zip_file" accept=".zip" required>
            </div>
            <div style="margin-bottom:12px;">
                <label>Chọn file CSV (dữ liệu):</label><br>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>

            <div style="margin:12px 0;">
                <button type="button" id="btn_start" class="btn btn-green">🚀 BẮT ĐẦU NẠP</button>
                <button type="button" id="btn_cancel" class="btn btn-gray" style="margin-left:8px; display:none;">HỦY</button>
            </div>

            <div style="margin-top:14px;">
                <div class="progress"><div class="bar" id="progress-bar">0%</div></div>
                <div id="status-line" style="margin-top:8px;" class="muted">Chưa bắt đầu</div>
            </div>

            <div id="log" style="margin-top:12px; display:none;">
                <h4>Log</h4>
                <pre id="logbox"></pre>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const btnStart = document.getElementById('btn_start');
    const btnCancel = document.getElementById('btn_cancel');
    const zipInput = document.getElementById('zip_file');
    const csvInput = document.getElementById('csv_file');
    const progressBar = document.getElementById('progress-bar');
    const statusLine = document.getElementById('status-line');
    const logBox = document.getElementById('logbox');
    const logWrap = document.getElementById('log');

    let abortFlag = false;

    function log(msg) {
        if (!logWrap) return;
        logWrap.style.display = 'block';
        const now = new Date().toLocaleTimeString();
        logBox.textContent += `[${now}] ${msg}\n`;
        logBox.scrollTop = logBox.scrollHeight;
    }

    btnStart.addEventListener('click', async function(){
        abortFlag = false;
        if (!zipInput.files[0] || !csvInput.files[0]) { alert('Chọn ZIP và CSV trước.'); return; }
        btnStart.disabled = true; btnCancel.style.display = 'inline-block';
        statusLine.textContent = 'Đang gửi file để kiểm tra...';

        // Step 1: validate
        const fd = new FormData();
        fd.append('ajax_action','validate_zip_csv');
        fd.append('zip_file', zipInput.files[0]);
        fd.append('csv_file', csvInput.files[0]);

        try {
            const res = await fetch(window.location.href, { method: 'POST', body: fd });
            const j = await res.json();
            if (!j.success) {
                btnStart.disabled = false; btnCancel.style.display = 'none';
                if (j.error === 'missing_images' && j.missing) {
                    let msg = 'Thiếu ảnh theo CSV:\\n';
                    j.missing.slice(0,20).forEach(m => msg += `- Row ${m.row}: ${m.image}\\n`);
                    alert(msg);
                    statusLine.textContent = 'Lỗi: Thiếu ảnh trong ZIP theo CSV.';
                    log(msg);
                    return;
                }
                alert('Validation lỗi: ' + (j.error || 'unknown'));
                statusLine.textContent = 'Validation thất bại.';
                log('Validation lỗi: ' + JSON.stringify(j));
                return;
            }

            const total = j.total || 0;
            const token = j.token;
            statusLine.textContent = 'Validation thành công. Số sản phẩm: ' + total;

            // batch loop
            let processed = 0;
            const batchSize = 10;
            while (processed < total) {
                if (abortFlag) { statusLine.textContent = 'Đã hủy bởi người dùng.'; break; }
                statusLine.textContent = `Đang nạp ${processed + 1}..${Math.min(processed + batchSize, total)} / ${total}`;
                log(`Xử lý batch bắt đầu tại ${processed}`);

                const fd2 = new FormData();
                fd2.append('ajax_action','process_batch');
                fd2.append('token', token);
                fd2.append('start', processed);
                fd2.append('batch', batchSize);

                const r2 = await fetch(window.location.href, { method: 'POST', body: fd2 });
                const j2 = await r2.json();
                if (!j2.success) {
                    alert('Lỗi khi nạp batch: ' + (j2.error || 'unknown'));
                    log('Lỗi batch: ' + JSON.stringify(j2));
                    break;
                }
                const got = j2.processed || 0;
                processed += got;
                const percent = Math.round((processed/total)*100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
                log(`Batch hoàn thành: ${got} sản phẩm.`);
                // small pause to avoid hammering
                await new Promise(r => setTimeout(r, 200));
            }

            if (!abortFlag) {
                statusLine.textContent = 'Hoàn tất. Dọn dẹp tạm...';
                // cleanup
                const fd3 = new FormData();
                fd3.append('ajax_action','cleanup_temp');
                fd3.append('token', token);
                await fetch(window.location.href, { method: 'POST', body: fd3 });
                progressBar.style.width = '100%'; progressBar.textContent = '100%';
                statusLine.textContent = 'Đã hoàn tất import!';
                log('Import hoàn tất.');
            }

        } catch (err) {
            console.error(err);
            alert('Có lỗi xảy ra. Kiểm tra console.');
            log('Exception: ' + err.message);
            statusLine.textContent = 'Lỗi khi nạp.';
        } finally {
            btnStart.disabled = false;
            btnCancel.style.display = 'none';
        }
    });

    btnCancel.addEventListener('click', function(){
        if (!confirm('Bạn có muốn hủy tiến trình hiện tại?')) return;
        abortFlag = true;
        btnStart.disabled = false; btnCancel.style.display = 'none';
    });
})();
</script>
</body>
</html>