<?php
// Bật hiển thị lỗi nếu cần test
// error_reporting(E_ALL); ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TanDa - Chuyên thiết bị an ninh & công nghệ</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="top-banner">
        <img src="https://theme.hstatic.net/200000722513/1001090675/14/top_banner.jpg?v=3834" alt="Khuyến mãi thả ga">
    </div>

    <header class="main-header bg-image-header">
        <div class="container header-wrap">
            <div class="logo">
                <a href="index.php"><h1 class="logo-text">TANDA</h1></a>
            </div>
            
            <form class="search-box" action="search.php" method="GET">
                <input type="text" name="q" placeholder="Bạn cần tìm camera, thẻ nhớ, đầu ghi...?" required>
                <button type="submit">🔍 Tìm kiếm</button>
            </form>
            
            <div class="contact-info">
                <span class="hotline-text">Gọi mua hàng & Lắp đặt</span>
                <a href="https://zalo.me/09xxxxxxxx" target="_blank" class="hotline-number">09xx.xxx.xxx</a>
            </div>
        </div>
    </header>