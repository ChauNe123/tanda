<?php
// Bắt ID để biết khách đang xem trang nào
$id = isset($_GET['id']) ? $_GET['id'] : 'huong-dan';

$title = "";
$content = "";

switch ($id) {
    case 'huong-dan':
        $title = "Hướng dẫn mua hàng";
        $content = "
            <h3>1. Đặt hàng qua Website</h3>
            <p><strong>Bước 1:</strong> Tìm kiếm sản phẩm cần mua. Quý khách có thể tìm qua danh mục hoặc ô tìm kiếm.</p>
            <p><strong>Bước 2:</strong> Nhấp vào sản phẩm để xem thông số chi tiết. Bấm nút <strong>'THÊM VÀO GIỎ HÀNG'</strong>.</p>
            <p><strong>Bước 3:</strong> Truy cập Giỏ hàng ở góc trên màn hình, kiểm tra số lượng và bấm 'Tiến hành thanh toán'.</p>
            <p><strong>Bước 4:</strong> Điền đầy đủ thông tin giao hàng và chọn hình thức thanh toán. Nhân viên TANDA sẽ gọi điện xác nhận trong 15 phút.</p>
            
            <h3 style='margin-top: 20px;'>2. Đặt hàng qua Zalo / Hotline</h3>
            <p>Quý khách chỉ cần bấm vào nút Zalo hoặc gọi thẳng số Hotline <strong>098.655.xxxx</strong>. Đội ngũ kỹ thuật viên của TANDA luôn trực 24/7 để tư vấn cấu hình, số lượng camera phù hợp nhất với nhà xưởng, cửa hàng của quý khách.</p>
        ";
        break;
        
    case 'bao-hanh':
        $title = "Chính sách bảo hành";
        $content = "
            <h3>1. Thời hạn bảo hành</h3>
            <ul>
                <li><strong>Camera & Đầu ghi hình:</strong> Bảo hành chính hãng 24 tháng.</li>
                <li><strong>Ổ cứng & Thẻ nhớ:</strong> Bảo hành chính hãng 24 tháng.</li>
                <li><strong>Phụ kiện (Nguồn, Balun, Jack):</strong> Bảo hành 06 tháng.</li>
            </ul>
            
            <h3 style='margin-top: 20px;'>2. Điều kiện được bảo hành</h3>
            <ul>
                <li>Sản phẩm còn trong thời hạn bảo hành.</li>
                <li>Tem bảo hành, mã vạch seri trên sản phẩm phải còn nguyên vẹn, không bị rách rời, chắp vá.</li>
                <li>Sản phẩm bị lỗi kỹ thuật do nhà sản xuất.</li>
            </ul>
            
            <h3 style='margin-top: 20px;'>3. Các trường hợp TỪ CHỐI bảo hành</h3>
            <p>Sản phẩm bị rơi vỡ, vô nước, cháy nổ do chập điện, hoặc có dấu hiệu bị côn trùng phá hoại. TANDA hỗ trợ sửa chữa tính phí ưu đãi cho khách hàng trong trường hợp này.</p>
        ";
        break;

    case 'doi-tra':
        $title = "Chính sách đổi trả";
        $content = "
            <h3>1. Đổi mới 100% (Trong 30 ngày đầu)</h3>
            <p>Cam kết <strong>LỖI LÀ ĐỔI MỚI NGAY LẬP TỨC</strong> trong vòng 30 ngày đầu tiên nếu sản phẩm phát sinh lỗi phần cứng từ nhà sản xuất. Sản phẩm đổi trả phải giữ nguyên vỏ hộp và phụ kiện đi kèm.</p>
            
            <h3 style='margin-top: 20px;'>2. Trả hàng & Hoàn tiền</h3>
            <p>Trong trường hợp sản phẩm không đúng như cam kết, hoặc khách hàng thay đổi nhu cầu (chưa qua sử dụng), TANDA hỗ trợ nhập lại hàng và hoàn tiền mặt/chuyển khoản (Có tính phí chiết khấu theo quy định của công ty).</p>
        ";
        break;

    case 'thanh-toan':
        $title = "Hình thức thanh toán";
        $content = "
            <p>TANDA hỗ trợ đa dạng các hình thức thanh toán để tạo sự thuận tiện tối đa cho quý khách:</p>
            <ul style='margin-top: 15px;'>
                <li><strong style='color: var(--orange-brand);'>1. Thanh toán tiền mặt khi nhận hàng (COD):</strong> Quý khách nhận hàng, kiểm tra kỹ lưỡng (check serial, tem mác) rồi mới thanh toán cho nhân viên giao hàng hoặc kỹ thuật viên lắp đặt.</li>
                <li style='margin-top: 10px;'><strong style='color: var(--orange-brand);'>2. Chuyển khoản ngân hàng:</strong> Áp dụng cho khách hàng mua online hoặc các dự án lắp đặt lớn. Thông tin số tài khoản sẽ được cung cấp khi xác nhận đơn hàng.</li>
                <li style='margin-top: 10px;'><strong style='color: var(--orange-brand);'>3. Thanh toán trả góp 0%:</strong> Áp dụng qua thẻ tín dụng của hơn 25 ngân hàng liên kết.</li>
            </ul>
        ";
        break;

    case 'bao-mat':
        $title = "Quy định bảo mật";
        $content = "
            <h3>1. Thu thập thông tin</h3>
            <p>TANDA chỉ thu thập Họ tên, Số điện thoại và Địa chỉ giao hàng khi quý khách chủ động cung cấp để phục vụ cho việc vận chuyển và kích hoạt bảo hành điện tử.</p>
            
            <h3 style='margin-top: 20px;'>2. Cam kết bảo mật</h3>
            <p>Hệ thống dữ liệu của chúng tôi được mã hóa an toàn. Chúng tôi <strong>cam kết TUYỆT ĐỐI KHÔNG mua bán, trao đổi thông tin khách hàng</strong> cho bất kỳ bên thứ 3 nào.</p>
            
            <h3 style='margin-top: 20px;'>3. Quyền lợi của khách hàng</h3>
            <p>Quý khách hoàn toàn có quyền yêu cầu TANDA xóa bỏ dữ liệu cá nhân của mình khỏi hệ thống chăm sóc khách hàng bất cứ lúc nào bằng cách gọi tới số Hotline.</p>
        ";
        break;

    default:
        $title = "Thông tin không tồn tại";
        $content = "<p>Rất tiếc, nội dung bạn tìm kiếm không có sẵn.</p>";
        break;
}

include 'includes/header.php';
?>

<main class="container" style="margin-top: 30px; margin-bottom: 60px; min-height: 50vh;">
    <div class="breadcrumb" style="margin-bottom: 20px; color: #666; font-size: 14px;">
        <a href="index.php" style="color: var(--orange-brand); font-weight: bold;">Trang chủ</a> / 
        <span>Hỗ trợ khách hàng</span> / 
        <strong><?php echo htmlspecialchars($title); ?></strong>
    </div>

    <div class="block-section" style="padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h1 style="color: var(--orange-brand); font-size: 28px; font-weight: 900; margin-bottom: 25px; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
            <?php echo htmlspecialchars($title); ?>
        </h1>
        
        <div class="policy-content" style="line-height: 1.8; color: #333; font-size: 15px;">
            <?php echo $content; ?>
        </div>
    </div>
</main>

<style>
    /* Làm đẹp sương sương cho nội dung policy */
    .policy-content h3 { color: #003028; font-weight: 800; font-size: 18px; margin-bottom: 10px; }
    .policy-content ul { margin-left: 20px; margin-bottom: 15px; }
    .policy-content li { margin-bottom: 8px; }
    .policy-content p { margin-bottom: 15px; text-align: justify; }
</style>

<?php include 'includes/footer.php'; ?>