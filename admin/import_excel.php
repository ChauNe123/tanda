<?php
// admin/import_excel.php
session_start();
require_once '../cores/db_config.php';
require_once '../cores/SimpleXLSX.php'; // Gọi thư viện đọc Excel

// Thêm dòng này để gọi đích danh thư viện
use Shuchkin\SimpleXLSX;

$message = '';

// Hàm tạo Link chuẩn SEO (Slug) tự động từ Tên SP
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

// Xử lý khi bấm nút Upload
if (isset($_POST['btn_upload'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        
        // Đọc file Excel trực tiếp từ thư mục tạm
        if ($xlsx = SimpleXLSX::parse($_FILES['excel_file']['tmp_name'])) {
            $rows = $xlsx->rows();
            
            // Bỏ qua dòng số 1 (Dòng tiêu đề cột)
            unset($rows[0]);
            
            $count_success = 0;

            foreach ($rows as $row) {
                // Map dữ liệu từ Excel (Lưu ý thứ tự cột trong file Excel phải chuẩn)
                $sku           = trim($row[0]); // Cột A: Mã SP
                $cat_code      = trim($row[1]); // Cột B: Mã Danh Mục
                $name          = trim($row[2]); // Cột C: Tên SP
                $price         = (int)$row[3];  // Cột D: Giá gốc
                $sale_price    = (int)$row[4];  // Cột E: Giá KM
                $coupon        = trim($row[5]); // Cột F: Mã giảm giá
                $image_file    = trim($row[6]); // Cột G: Tên file ảnh
                $frame_file    = trim($row[7]); // Cột H: Tên viền
                $specs         = trim($row[8]); // Cột I: Thông số
                $status        = (int)$row[9];  // Cột J: Trạng thái (1: Hiện, 0: Ẩn)
                
                // Nếu không có tên SP hoặc SKU thì bỏ qua dòng đó
                if(empty($sku) || empty($name)) continue;

                // Tự động tạo slug nếu trong Excel không có
                $slug = createSlug($name);

                // Câu lệnh SQL "Ma Thuật": Thêm mới hoặc Cập nhật đè nếu trùng SKU
                $sql = "INSERT INTO products (sku, cat_code, name, slug, price, sale_price, coupon_code, image_file, frame_file, specs_summary, status) 
                        VALUES (:sku, :cat, :name, :slug, :price, :sale, :coupon, :img, :frame, :specs, :stt)
                        ON DUPLICATE KEY UPDATE 
                        cat_code=VALUES(cat_code), name=VALUES(name), slug=VALUES(slug), price=VALUES(price), 
                        sale_price=VALUES(sale_price), coupon_code=VALUES(coupon_code), image_file=VALUES(image_file), 
                        frame_file=VALUES(frame_file), specs_summary=VALUES(specs_summary), status=VALUES(status)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':sku' => $sku, ':cat' => $cat_code, ':name' => $name, ':slug' => $slug,
                    ':price' => $price, ':sale' => $sale_price, ':coupon' => $coupon, 
                    ':img' => $image_file, ':frame' => $frame_file, ':specs' => $specs, ':stt' => $status
                ]);
                $count_success++;
            }
            $message = "<div class='alert success'>✅ Cập nhật thành công $count_success sản phẩm!</div>";
        } else {
            $message = "<div class='alert error'>❌ Lỗi đọc file: " . SimpleXLSX::parseError() . "</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ Vui lòng chọn file Excel (.xlsx)!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Quản lý Kho KB Tech</title>
    <style>
        /* CSS viết thẳng vào đây để trang admin độc lập, load siêu nhanh */
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; color: #333; padding: 15px; }
        
        .admin-container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 25px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        h2 { text-align: center; color: #0056b3; margin-bottom: 5px; font-size: 22px; }
        .subtitle { text-align: center; color: #666; font-size: 14px; margin-bottom: 25px; }

        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 10px; font-size: 15px; }
        
        /* Input File giả lập lại cho đẹp trên điện thoại */
        .file-upload-wrapper {
            position: relative;
            width: 100%;
            height: 60px;
            border: 2px dashed #0056b3;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fbff;
            overflow: hidden;
        }
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0; top: 0; width: 100%; height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .file-upload-text {
            color: #0056b3; font-weight: 500; font-size: 16px;
        }

        /* Nút bấm khổng lồ cho Mobile */
        .btn-submit {
            width: 100%;
            background: #0056b3;
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0,86,179,0.2);
        }
        .btn-submit:hover { background: #004494; }
        .btn-submit:active { transform: scale(0.98); }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Hướng dẫn cấu trúc Excel */
        .guide { margin-top: 30px; background: #fff3cd; padding: 15px; border-radius: 8px; font-size: 13px; color: #856404; border: 1px solid #ffeeba; }
        .guide ul { padding-left: 20px; margin-top: 10px; }
        .guide li { margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="admin-container">
    <h2>NẠP DỮ LIỆU KHO HÀNG</h2>
    <p class="subtitle">Đồng bộ Sản phẩm & Giá bán từ Excel</p>

    <?php echo $message; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Chọn file báo giá (Định dạng .xlsx)</label>
            <div class="file-upload-wrapper">
                <span class="file-upload-text" id="file-name">📁 Chạm để chọn file Excel...</span>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx" required>
            </div>
        </div>

        <button type="submit" name="btn_upload" class="btn-submit">🚀 Bắt đầu Nạp Dữ Liệu</button>
    </form>

    <div class="guide">
        <strong>📌 Cấu trúc cột Excel bắt buộc (Dòng 1 là tiêu đề):</strong>
        <ul>
            <li>Cột A: Mã SP (SKU) - <i>Dùng để đối chiếu đè giá</i></li>
            <li>Cột B: Mã Danh Mục (VD: CAM-WIFI)</li>
            <li>Cột C: Tên Sản phẩm</li>
            <li>Cột D: Giá gốc (Chỉ để số)</li>
            <li>Cột E: Giá khuyến mãi (Chỉ để số)</li>
            <li>Cột F: Mã giảm giá (Nếu có)</li>
            <li>Cột G: Tên file ảnh (VD: ezviz-c6n.png)</li>
            <li>Cột H: Khung sự kiện (VD: khung-noel.png)</li>
            <li>Cột I: Thông số nổi bật (Alt+Enter để xuống dòng)</li>
            <li>Cột J: Trạng thái (1: Hiện, 0: Ẩn)</li>
        </ul>
    </div>
</div>

<script>
    // JS nhỏ để đổi chữ khi chọn file thành công trên điện thoại
    document.getElementById('excel_file').addEventListener('change', function(e) {
        var fileName = e.target.files[0].name;
        document.getElementById('file-name').innerHTML = '📄 Đã chọn: ' + fileName;
        document.getElementById('file-name').style.color = '#28a745';
    });
</script>

</body>
</html>