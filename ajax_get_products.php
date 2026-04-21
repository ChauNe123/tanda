<?php
require_once 'cores/db_config.php';

$catFilter = isset($_GET['cat']) ? trim($_GET['cat']) : '';

if(!empty($catFilter)) {
    $stmtSuggest = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 16");
    $stmtSuggest->execute(['cat' => $catFilter]);
} else {
    $stmtSuggest = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 16");
    $stmtSuggest->execute();
}

$suggestProds = $stmtSuggest->fetchAll();

$html = '';
if(count($suggestProds) > 0) {
    ob_start();
    foreach($suggestProds as $p) {
        include 'card_template.php';
    }
    $html = ob_get_clean();
} else {
    $html = '<p style="padding: 20px; color: #888; grid-column: 1 / -1; text-align: center;">Chưa có sản phẩm nào trong mục này.</p>';
}

$btnHtml = '';
if(count($suggestProds) == 16) {
    if(!empty($catFilter)) {
        $catSlug = 'camera-wifi';
        if($catFilter === 'KB-DAY') $catSlug = 'camera-tron-bo';
        if($catFilter === 'KB-REC') $catSlug = 'dau-ghi-hinh';
        if($catFilter === 'KB-PHU') $catSlug = 'phu-kien';
        
        $btnHtml = '<a href="category.php?slug='.$catSlug.'" class="btn-view-more">XEM THÊM SẢN PHẨM KHÁC</a>';
    } else {
        $btnHtml = '<a href="search.php" class="btn-view-more">XEM TẤT CẢ SẢN PHẨM</a>';
    }
}

echo json_encode([
    'html' => $html,
    'btnHtml' => $btnHtml
]);
