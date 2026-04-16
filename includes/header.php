<?php
// Bật hiển thị lỗi nếu cần test
// error_reporting(E_ALL); ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANDA - Hệ Thống An Ninh MÀU CAM</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="main-header">
    <div class="container header-inner">
        <a href="index.php" class="logo-area">TAN<span>DA</span></a>
        
        <form class="search-area" action="search.php" method="GET">
            <button type="submit"><i class="fas fa-search"></i></button>
            <input type="text" name="q" placeholder="Bạn tìm camera, đầu ghi gì..." required>
        </form>
        
        <a href="cart.php" class="cart-box">
            <i class="fas fa-shopping-cart"></i>
            <span>Giỏ hàng (0)</span>
        </a>
    </div>
</header>

<nav class="nav-bar">
    <div class="container">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="category.php?slug=camera-wifi" class="nav-link"><i class="fas fa-video"></i> CAMERA WIFI</a>
                <div class="dropdown">
                    <a href="category.php?slug=camera-wifi-ezviz">Ezviz chính hãng</a>
                    <a href="category.php?slug=camera-wifi-imou">Imou giá rẻ</a>
                    <a href="category.php?slug=camera-wifi-kbone">Kbone cao cấp</a>
                </div>
            </li>
            <li class="nav-item"><a href="category.php?slug=camera-tron-bo" class="nav-link"><i class="fas fa-camera"></i> CAMERA TRỌN BỘ</a></li>
            <li class="nav-item"><a href="category.php?slug=dau-ghi-hinh" class="nav-link"><i class="fas fa-server"></i> ĐẦU GHI HÌNH</a></li>
            <li class="nav-item"><a href="category.php?slug=phu-kien" class="nav-link"><i class="fas fa-hdd"></i> PHỤ KIỆN</a></li>
            <li class="nav-item"><a href="category.php?slug=thiet-bi-mang" class="nav-link"><i class="fas fa-network-wired"></i> THIẾT BỊ MẠNG</a></li>
        </ul>
    </div>
</nav>