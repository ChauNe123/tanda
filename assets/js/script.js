// === HÀM FORMAT TIỀN (Hiển thị) ===
const formatMoney = (num) => new Intl.NumberFormat('vi-VN').format(num) + '₫';

// === TOAST THÔNG BÁO (thay thế alert) ===
function showToast(message, type) {
    type = type || 'info';
    var bgColor = type === 'warning' ? '#ff9f00' : (type === 'error' ? '#d70018' : '#288ad6');
    var icon = type === 'warning' ? 'fa-exclamation-triangle' : (type === 'error' ? 'fa-times-circle' : 'fa-info-circle');
    
    var toast = document.getElementById('customToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'customToast';
        toast.style.cssText = 'position:fixed; top:20px; left:50%; transform:translateX(-50%); background:' + bgColor + '; color:#fff; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; z-index:9999999; box-shadow:0 4px 20px rgba(0,0,0,0.25); display:flex; align-items:center; gap:8px; transition:opacity 0.3s ease, transform 0.3s ease; opacity:0; pointer-events:none; max-width:90%; text-align:center;';
        document.body.appendChild(toast);
    }
    
    toast.style.background = bgColor;
    toast.innerHTML = '<i class="fas ' + icon + '"></i> ' + message;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';
    
    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(-15px)';
    }, 3000);
}

// === SO SÁNH SẢN PHẨM (LocalStorage) ===
const COMPARE_KEY = 'tanda_compare';
const MAX_COMPARE = 4;

function getCompareList() {
    try { return JSON.parse(localStorage.getItem(COMPARE_KEY)) || []; }
    catch(e) { return []; }
}

function saveCompareList(list) {
    localStorage.setItem(COMPARE_KEY, JSON.stringify(list));
    updateCompareUI();
}

function toggleCompareItem(checkbox) {
    let list = getCompareList();
    let sku = checkbox.value;
    
    if (checkbox.checked) {
        if (list.length >= MAX_COMPARE) {
            checkbox.checked = false;
            showToast('Bạn chỉ có thể so sánh tối đa ' + MAX_COMPARE + ' sản phẩm.', 'warning');
            return;
        }
        list.push({
            sku: sku,
            name: checkbox.dataset.name,
            img: checkbox.dataset.img,
            price: checkbox.dataset.price
        });
    } else {
        list = list.filter(item => item.sku !== sku);
    }
    
    saveCompareList(list);
    syncCheckboxes(list);
}

function clearCompare() {
    showConfirmDialog('Bạn có chắc muốn xóa tất cả sản phẩm khỏi danh sách so sánh?', function() {
        localStorage.removeItem(COMPARE_KEY);
        updateCompareUI();
        document.querySelectorAll('.compare-checkbox').forEach(cb => { cb.checked = false; });
    });
}

function syncCheckboxes(list) {
    let skuSet = new Set(list.map(i => i.sku));
    document.querySelectorAll('.compare-checkbox').forEach(cb => {
        cb.checked = skuSet.has(cb.value);
    });
}

function updateCompareUI() {
    let list = getCompareList();
    let bar = document.getElementById('compareStickyBar');
    let countEl = document.getElementById('compareCount');
    let badge = document.getElementById('compareBadge');
    
    if (bar) bar.style.display = list.length > 0 ? 'block' : 'none';
    if (countEl) countEl.textContent = list.length;
    if (badge) badge.textContent = list.length;
    
    // Thêm/xóa class has-compare-bar để chừa khoảng trống cho sticky bar
    if (list.length > 0) {
        document.body.classList.add('has-compare-bar');
    } else {
        document.body.classList.remove('has-compare-bar');
    }
    
    let goBtn = document.querySelector('.btn-compare-go');
    if (goBtn && list.length > 0) {
        goBtn.href = 'compare.php?skus=' + encodeURIComponent(list.map(i => i.sku).join(','));
    }
}

// === GIỎ HÀNG (LocalStorage) ===
function updateCart() {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let badge = document.getElementById('cartBadge');
    if (badge) badge.innerText = cart.reduce((sum, i) => sum + i.qty, 0);
}

// Thêm vào giỏ (Nhận đủ thông tin để hiển thị bên trang cart.php)
function addToCart(sku, name, price, image) {
    let cart = JSON.parse(localStorage.getItem('tanda_cart')) || [];
    let item = cart.find(i => i.sku === sku);
    
    if (item) {
        item.qty++;
    } else {
        cart.push({
            sku: sku, 
            name: name, 
            price: price, 
            image: image, 
            qty: 1
        });
    }
    
    localStorage.setItem('tanda_cart', JSON.stringify(cart));
    updateCart();
    
    // Bật Dialog thông báo thành công
    let modal = document.getElementById('addToCartModal');
    let nameEl = document.getElementById('addedProductName');
    if (modal && nameEl) {
        nameEl.innerText = name;
        modal.style.display = 'flex';
    }
}

// Mở Zalo để tư vấn/mua ngay không thông qua giỏ hàng (Áp dụng cho trang chi tiết)
function orderViaZalo(productName, price) {
    let msg = `Chào shop, mình cần tư vấn/đặt hàng sản phẩm:\n- ${productName}\n- Giá: ${formatMoney(price)}\n(Từ website TANDA)`;
    let encodedMsg = encodeURIComponent(msg);
    // Thay số điện thoại Zalo của shop tại đây
    window.location.href = `https://zalo.me/0938440781?text=${encodedMsg}`;
}

// === OBSERVER ANIMATION (Cuộn đến đâu hiện ra đến đó) ===
function initScrollAnim() {
    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(e.isIntersecting) { e.target.classList.add('show'); obs.unobserve(e.target); }
        });
    }, {threshold: 0.1});
    document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));
}

// === HEADER SCROLL: Ẩn/Hiện Menu Danh Mục (Chỉ Desktop) ===
function initStickyHeader() {
    var nav = document.getElementById('tgddNav');
    var header = document.getElementById('tgddHeader');
    if (!nav || !header) return;

    // Không chạy sticky header trên mobile (để hamburger menu hoạt động)
    if (window.matchMedia('(max-width: 768px)').matches) return;

    // Hiệu ứng popup: header trượt xuống khi load trang
    header.style.transform = 'translateY(-100%)';
    header.style.opacity = '0';
    header.style.transition = 'transform 0.5s ease-out, opacity 0.5s ease-out';
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            header.style.transform = 'translateY(0)';
            header.style.opacity = '1';
        });
    });
    setTimeout(function() {
        header.style.transform = '';
        header.style.opacity = '';
        header.style.transition = '';
    }, 550);

    var HIDE_AT = 60;
    var SHOW_AT = 10;
    var hidden = false;

    function hideNav() {
        // Chỉ ẩn trên desktop
        if (window.matchMedia('(max-width: 768px)').matches) return;
        nav.style.setProperty('max-height', '0', 'important');
        nav.style.setProperty('opacity', '0', 'important');
        nav.style.setProperty('border-top', 'none', 'important');
        nav.style.setProperty('padding', '0', 'important');
        document.body.classList.add('header-compact');
        hidden = true;
    }

    function showNav() {
        if (window.matchMedia('(max-width: 768px)').matches) return;
        nav.style.setProperty('max-height', '60px', 'important');
        nav.style.setProperty('opacity', '1', 'important');
        nav.style.setProperty('border-top', '1px solid rgba(0,0,0,0.08)', 'important');
        nav.style.setProperty('padding', '', 'important');
        document.body.classList.remove('header-compact');
        hidden = false;
    }

    window.addEventListener('scroll', function() {
        if (window.matchMedia('(max-width: 768px)').matches) return;
        var y = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop;
        if (y <= SHOW_AT && hidden) {
            showNav();
        } else if (y > HIDE_AT && !hidden) {
            hideNav();
        }
    }, { passive: true });

    if ((window.scrollY || document.documentElement.scrollTop) > HIDE_AT) {
        hideNav();
    }
}

// === INIT KHI LOAD XONG & CÁC HÀM FORMAT INPUT TỰ ĐỘNG ===
window.addEventListener('DOMContentLoaded', () => {
    updateCart();
    initScrollAnim();
    initStickyHeader();
    
    // Khởi tạo so sánh
    let compareList = getCompareList();
    syncCheckboxes(compareList);
    updateCompareUI();

    // 1. Tự động định dạng tiền tệ khi người dùng gõ (Thêm class "format-currency" vào thẻ input)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('format-currency')) {
            // Lấy giá trị, xóa bỏ mọi ký tự không phải là số
            let value = e.target.value.replace(/\D/g, '');
            if (value !== '') {
                // Format theo chuẩn VN (có dấu chấm ngăn cách, vd: 1.000.000)
                value = new Intl.NumberFormat('vi-VN').format(value);
            }
            e.target.value = value;
        }
    });

    // 2. Tự động định dạng ngày tháng dd/mm/yyyy khi người dùng gõ (Thêm class "format-date" vào thẻ input)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('format-date')) {
            // Xóa mọi ký tự không phải là số
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 8) v = v.slice(0, 8); // Tối đa 8 số (ddmmyyyy)
            
            // Tự động chèn dấu '/'
            if (v.length >= 5) {
                e.target.value = v.slice(0,2) + '/' + v.slice(2,4) + '/' + v.slice(4);
            } else if (v.length >= 3) {
                e.target.value = v.slice(0,2) + '/' + v.slice(2);
            } else {
                e.target.value = v;
            }
        }
    });

    /* =========================
       XEM THÊM MÔ TẢ
    ========================= */
    var toggleBtn = document.getElementById('toggleDescription');
    var desc = document.getElementById('descriptionContent');

    if (toggleBtn && desc) {
        toggleBtn.addEventListener('click', function() {
            desc.classList.toggle('expanded');
            desc.classList.toggle('collapsed');
            toggleBtn.innerText = desc.classList.contains('expanded') ? 'Thu gọn' : 'Xem thêm';
        });
    }

    /* =========================
       ACCORDION THÔNG SỐ KỸ THUẬT
    ========================= */
    document.querySelectorAll('.spec-header').forEach(function(header) {
        header.addEventListener('click', function() {
            header.parentElement.classList.toggle('active');
        });
    });
});
