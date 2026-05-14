<?php
// ajax_search_suggest.php - API gợi ý tìm kiếm thông minh
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once 'cores/db_config.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = min(10, max(3, (int)($_GET['limit'] ?? 8)));

if (mb_strlen($q) < 1) {
    echo json_encode(['success' => true, 'data' => [], 'count' => 0]);
    exit;
}

try {
    // Tách từ khóa để tìm chính xác hơn
    $words = preg_split('/\s+/', $q);
    $conditions = [];
    $params = [];
    
    foreach ($words as $i => $word) {
        $word = trim($word);
        if ($word === '') continue;
        $key = ":kw{$i}";
        $conditions[] = "(p.name LIKE {$key} OR p.sku LIKE {$key} OR p.cat_code LIKE {$key} OR p.specs_summary LIKE {$key})";
        $params[$key] = "%{$word}%";
    }
    
    if (empty($conditions)) {
        echo json_encode(['success' => true, 'data' => [], 'count' => 0]);
        exit;
    }
    
    $where = implode(' AND ', $conditions);
    
    $sql = "SELECT p.sku, p.name, p.slug, p.price, p.sale_price, p.image_1, p.status, p.cat_code
            FROM products p 
            WHERE p.status = 1 AND ({$where})
            ORDER BY 
                CASE WHEN p.name LIKE :exact THEN 0 ELSE 1 END,
                p.sort_order ASC, p.sku DESC
            LIMIT {$limit}";
    $params[':exact'] = "%{$q}%";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu trả về
    $data = [];
    foreach ($results as $row) {
        // Lấy ảnh đầu tiên
        $image = 'placeholder.png';
        if (!empty($row['image_1'])) {
            $imgs = explode(',', $row['image_1']);
            $firstImg = trim($imgs[0]);
            if (!empty($firstImg) && file_exists('uploads/' . $firstImg)) {
                $image = $firstImg;
            }
        }
        
        $chotGia = ($row['sale_price'] > 0) ? (int)$row['sale_price'] : (int)$row['price'];
        $hasSale = ($row['sale_price'] > 0 && (int)$row['price'] > (int)$row['sale_price']);
        $pct = $hasSale ? round((((int)$row['price'] - (int)$row['sale_price']) / (int)$row['price']) * 100) : 0;
        
        $data[] = [
            'sku'      => $row['sku'],
            'name'     => $row['name'],
            'slug'     => $row['slug'],
            'price'    => (int)$row['price'],
            'sale_price' => (int)$row['sale_price'],
            'chot_gia' => $chotGia,
            'has_sale' => $hasSale,
            'pct'      => $pct,
            'image'    => $image,
            'status'   => (int)$row['status'],
            'cat_code' => $row['cat_code']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data'    => $data,
        'count'   => count($data),
        'query'   => $q
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi truy vấn', 'count' => 0]);
}
