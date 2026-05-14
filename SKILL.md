---
name: tanda-development
description: Hướng dẫn phát triển và bảo trì dự án TANDA — website phân phối camera & thiết bị an ninh (PHP + MySQL + TGDĐ UI Style).
version: 1.0.0
author: TANDA Team
tags: [php, mysql, ecommerce, tanda, camera, tgdd, vanilla-js]
triggers:
  - "sửa lỗi tanda"
  - "thêm tính năng tanda"
  - "tối ưu tanda"
  - "fix bug tanda"
  - "tanda website"
  - "tanda project"
---

# 🛠️ TANDA Development Skill

Kỹ năng này cung cấp hướng dẫn toàn diện để phát triển, sửa lỗi và mở rộng website TANDA.

---

## 📐 KIẾN TRÚC TỔNG QUAN

TANDA là **website TMĐT PHP thuần (không framework)** với:

```
┌─────────────────────────────────────────────────┐
│                   CLIENT (Browser)               │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │
│  │ HTML/CSS │  │ Vanilla  │  │ LocalStorage   │  │
│  │  (TGDĐ)  │  │  JS      │  │ + IndexedDB    │  │
│  └──────────┘  └──────────┘  └───────────────┘  │
└──────────────────────┬──────────────────────────┘
                       │ HTTP Request
┌──────────────────────▼──────────────────────────┐
│                PHP 7.4+ (PDO)                    │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │
│  │ Frontend │  │  Admin   │  │  Utility      │  │
│  │  Pages   │  │  Import  │  │  Scripts      │  │
│  └──────────┘  └──────────┘  └───────────────┘  │
└──────────────────────┬──────────────────────────┘
                       │ PDO
┌──────────────────────▼──────────────────────────┐
│              MySQL / MariaDB                     │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │
│  │ products │  │categories│  │   settings    │  │
│  └──────────┘  └──────────┘  └───────────────┘  │
└─────────────────────────────────────────────────┘
```

---

## 🔑 QUY ƯỚC CODE

### 1. Kết Nối Database

**LUÔN** sử dụng PDO thông qua file `cores/db_config.php`:

```php
require_once 'cores/db_config.php';
// $conn đã được khởi tạo sẵn
// $sys_settings là mảng chứa toàn bộ settings từ DB
```

**TUYỆT ĐỐI KHÔNG** hardcode thông tin DB ở bất kỳ file nào khác.

### 2. Include Header / Footer

```php
// Đầu file — require db_config trước
require_once 'cores/db_config.php';

// Sau đó include header (header.php sẽ tự động require db_config)
include 'includes/header.php';

// ... nội dung trang ...

include 'includes/footer.php';
```

### 3. Query Database (Luôn dùng Prepared Statement)

```php
$stmt = $conn->prepare("SELECT * FROM products WHERE cat_code = :cat AND status = 1 ORDER BY sort_order ASC");
$stmt->execute(['cat' => $catCode]);
$products = $stmt->fetchAll(); // Trả về mảng FETCH_ASSOC
```

### 4. Component Card Sản Phẩm

Mọi nơi cần hiển thị card sản phẩm đều **include `card_template.php`**:

```php
foreach($products as $p) {
    include 'card_template.php';
}
```

File này nhận biến `$p` (một row từ bảng products) và tự xử lý:
- Ép kiểu giá (`floatval`)
- Tính % giảm giá
- Lấy ảnh từ `image_1` (phân cách bởi dấu phẩy)
- Hiển thị badge, giá, nút thêm giỏ hàng

### 5. Grid 6 Cột

Hàm `fitGridBySix()` trong `index.php` đảm bảo số sản phẩm luôn là bội của 6:

```php
function fitGridBySix($items) {
    $count = count($items);
    if ($count < 6) return $items;
    $fitCount = floor($count / 6) * 6;
    return array_slice($items, 0, $fitCount);
}
```

---

## 🧩 CÁC THÀNH PHẦN CHÍNH

### Frontend Pages

| File | Chức Năng | Ghi Chú |
|------|-----------|---------|
| `index.php` | Trang chủ 4 khối | Dùng `fitGridBySix()`, AJAX filter |
| `category.php` | Danh mục + sort | Nhận `?slug=` và `?sort=` |
| `product-detail.php` | Chi tiết SP | Nhận `?slug=`, gallery ảnh từ `image_1` |
| `search.php` | Tìm kiếm | Nhận `?q=` (AND từ khóa) + `?cat=` |
| `cart.php` | Giỏ hàng + Đặt Zalo | JS render từ LocalStorage |
| `policy.php` | Chính sách | Nhận `?id=` (huong-dan, bao-hanh, doi-tra...) |

### API Endpoint

| File | Input | Output |
|------|-------|--------|
| `ajax_get_products.php` | `?cat=` | JSON `{html, btnHtml}` |

### Admin

| File | Chức Năng |
|------|-----------|
| `admin/index.php` | Redirect đến import_csv.php |
| `admin/import_csv.php` | Import CSV, upload ảnh, quản lý SP |

### Utility Scripts

| File | Chức Năng |
|------|-----------|
| `db_check.php` | Kiểm tra danh mục & đếm sản phẩm |
| `db_fix.php` | Thêm cột `sort_order`, sửa `image_1` |
| `db_optimize.php` | Tạo index cho `status`, `sort_order`, `cat_code` |
| `check_image_file.php` | Debug cột `image_file` |
| `scratch_db.php` | In ra categories và products |
| `scratch_db_check.php` | Kiểm tra SKU, name, status, cat_code |
| `scratch_fix_cats.php` | Thêm cột `icon_class` + gán icon |

---

## 🎨 CSS & JS

### CSS Architecture

```
assets/css/
├── style.css              # CSS chính (reset, variables, header, footer, menu)
├── admin.css              # CSS riêng cho admin (Fluent Design)
├── layout/
│   └── grid.css           # Grid 6 cột cho product grid
├── components/
│   └── product-card.css   # Style cho card sản phẩm (.tgdd-product-card)
└── pages/
    ├── cart.css           # Style trang giỏ hàng
    └── product-detail.css # Style trang chi tiết
```

**Quy ước**: Mỗi trang có style riêng được load bằng thẻ `<link>` trong chính trang đó, KHÔNG gom hết vào `style.css`.

### JavaScript

| File | Chức Năng Chính |
|------|-----------------|
| `script.js` | `addToCart()`, `updateCart()`, `orderViaZalo()`, `formatCurrency()`, scroll animation, sticky header |
| `admin_import.js` | IndexedDB manager, CSV parser, batch import UI |

---

## 🛒 LUỒNG GIỎ HÀNG & ĐẶT HÀNG

```
1. Người dùng bấm "THÊM VÀO GIỎ" (card_template.php)
   ↓ Gọi addToCart(sku, name, price, image)
2. Lưu vào LocalStorage: tanda_cart
   ↓ [{sku, name, price, image, qty}]
3. Bấm icon giỏ hàng → cart.php
   ↓ renderCartPage() từ LocalStorage
4. Nhập thông tin KH → Bấm "ĐẶT HÀNG QUA ZALO"
   ↓ generateZaloMessage()
5. Hiển thị modal + copy text → Mở Zalo với tin nhắn tự động
```

---

## 🔍 TÌM KIẾM (Search Logic)

`search.php` sử dụng **AND logic** cho từ khóa:

1. Tách `?q=` thành mảng từ khóa (split by space)
2. Mỗi từ khóa được tìm trong `name`, `sku`, `specs_summary` (LIKE %...%)
3. Ghép các điều kiện bằng `AND`:

```php
$words = explode(' ', $keyword);
foreach ($words as $i => $word) {
    $sql .= "(name LIKE :kw_$i OR sku LIKE :kw_$i OR specs_summary LIKE :kw_$i)";
    $params[":kw_$i"] = '%' . $word . '%';
}
$sql .= implode(' AND ', $wordConditions);
```

---

## 🖼️ XỬ LÝ ẢNH

### Quy tắc đặt tên ảnh
- Ảnh lưu trong `uploads/`
- Tên file = SKU + suffix (vd: `CAM001.jpg`, `CAM001_2.jpg`)
- Một sản phẩm có nhiều ảnh: lưu trong `image_1` (TEXT), phân cách bởi dấu phẩy

### Optimize ảnh (Admin)
- `admin/import_csv.php` có hàm `optimizeAndSaveImage()`:
  - Resize về max width 800px
  - Nén JPEG quality 80%
  - Hỗ trợ JPG, PNG, GIF, WebP
  - Giữ transparency cho PNG/GIF/WebP

---

## ⚠️ LƯU Ý QUAN TRỌNG

### Các lỗi thường gặp & cách fix

| Lỗi | Nguyên Nhân | Cách Fix |
|-----|-------------|----------|
| `Undefined array key` | Thiếu cột trong DB | Chạy `db_fix.php` |
| Ảnh không hiển thị | Ảnh không có trong `uploads/` | Upload ảnh đúng SKU |
| Sản phẩm không trong danh mục | `cat_code` không khớp | Kiểm tra bảng `categories` |
| Giỏ hàng mất sau khi F5 | LocalStorage bị xóa | Bình thường, đó là thiết kế |
| CSRF / Bảo mật | Không có CSRF token | Thêm session validation cho admin |

### Best Practices

1. **KHÔNG** push code có chứa password DB thật
2. **LUÔN** dùng `htmlspecialchars()` khi output dữ liệu từ DB ra HTML
3. **LUÔN** dùng prepared statements cho SQL queries
4. **KHÔNG** sửa trực tiếp trên production — test local trước
5. **LUÔN** chạy `db_optimize.php` sau khi thay đổi cấu trúc DB
6. **GIỮ** `fitGridBySix()` khi hiển thị grid sản phẩm — UI được thiết kế cho 6 cột

---

## 🚀 CÁC TASK PHỔ BIẾN

### Thêm danh mục mới

```sql
INSERT INTO categories (name, slug, cat_code, icon_class, status) 
VALUES ('Tên DM', 'ten-dm', 'MA-CODE', 'fas fa-icon', 1);
```

### Thêm sản phẩm mới (manual SQL)

```sql
INSERT INTO products (sku, name, slug, price, sale_price, cat_code, specs_summary, image_1, status)
VALUES ('SKU001', 'Tên SP', 'ten-sp', 1000000, 900000, 'MA-CODE', 'Thông số...', 'anh1.jpg,anh2.jpg', 1);
```

### Import hàng loạt (CSV)

1. Chuẩn bị file CSV với header: `sku,name,slug,price,sale_price,cat_code,description,specs_summary`
2. Upload ảnh vào `uploads/` (tên file = SKU)
3. Truy cập `/admin/` → Upload CSV → Hệ thống tự map ảnh

### Sửa giao diện Mobile

CSS responsive nằm trong từng file CSS riêng. Tìm `@media` queries để điều chỉnh.

---

## 📦 DEPENDENCIES

| Thư Viện | Version | CDN |
|----------|---------|-----|
| Font Awesome | 6.4.0 | `cdnjs.cloudflare.com` |
| Google Fonts (Roboto) | - | `fonts.googleapis.com` |

Không có dependency PHP bên ngoài — tất cả đều là built-in.

---

*Skill này được tạo để hỗ trợ Copilot Agent làm việc hiệu quả với dự án TANDA.*
