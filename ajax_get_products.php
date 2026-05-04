<?php
require_once 'cores/db_config.php';

try {
    $catFilter = isset($_GET['cat']) ? trim($_GET['cat']) : '';

    if(!empty($catFilter)) {
        $stmtSuggest = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat AND status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 16");
        $stmtSuggest->execute(['cat' => $catFilter]);
    } else {
        $stmtSuggest = $conn->prepare("SELECT * FROM products WHERE status = 1 ORDER BY sort_order ASC, sku DESC LIMIT 16");
        $stmtSuggest->execute();
    }

    $suggestProds = $stmtSuggest->fetchAll();
    $suggestProdsGrid = $suggestProds; // Hiện tất cả sản phẩm đang có

    $html = '';
    if(count($suggestProdsGrid) > 0) {
        ob_start();
        foreach($suggestProdsGrid as $p) {
            include 'card_template.php';
        }
        $html = ob_get_clean();
    } else {
        $html = '<p style="padding: 20px; color: #888; grid-column: 1 / -1; text-align: center;">Chưa có sản phẩm nào trong mục này.</p>';
    }

    $btnHtml = '';
    if(count($suggestProds) > count($suggestProdsGrid) || count($suggestProds) == 16) {
        // ... (Giữ nguyên logic tạo nút)
        $btnHtml = '<a href="search.php" class="btn-view-more">XEM TẤT CẢ SẢN PHẨM</a>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'btnHtml' => $btnHtml,
        'debug_count' => count($suggestProdsGrid)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
