// assets/js/admin_import.js

// --- HỆ THỐNG QUẢN LÝ BỘ NHỚ LỚN (INDEXEDDB) ---
const dbStore = {
    dbName: 'TandaAdminDB',
    storeName: 'temp_products',
    db: null,

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, 1);
            request.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'id' });
                }
            };
            request.onsuccess = (e) => {
                this.db = e.target.result;
                resolve(this.db);
            };
            request.onerror = (e) => reject('IndexedDB error: ' + e.target.errorCode);
        });
    },

    async save(data) {
        if (!this.db) await this.init();
        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            store.put({ id: 'current_session', products: data });
            tx.oncomplete = () => resolve(true);
        });
    },

    async get() {
        if (!this.db) await this.init();
        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const request = store.get('current_session');
            request.onsuccess = () => resolve(request.result ? request.result.products : null);
        });
    },

    async clear() {
        if (!this.db) await this.init();
        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            store.delete('current_session');
            tx.oncomplete = () => resolve(true);
        });
    }
};

// --- TRÌNH ĐỌC CSV XỬ LÝ ĐƯỢC XUỐNG DÒNG BÊN TRONG Ô ---
function parseCSV(text) {
    let p = '', row = [''], ret = [row], i = 0, r = 0, s = !0, l;
    for (l of text) {
        if ('"' === l) {
            if (s && l === p) row[i] += l;
            s = !s;
        } else if (',' === l && s) l = row[++i] = '';
        else if ('\n' === l && s) {
            if ('\r' === p) row[i] = row[i].slice(0, -1);
            row = ret[++r] = [l = '']; i = 0;
        } else row[i] += l;
        p = l;
    }
    return ret;
}

// ============================================================
// TRẠNG THÁI PHÂN TRANG & TÌM KIẾM
// ============================================================
let adminState = {
    page: 1,
    perPage: 25,
    search: '',
    total: 0,
    totalPages: 1,
    data: [],           // Dữ liệu trang hiện tại (từ server HOẶC từ CSV pending)
    dirtyEdits: {},     // {sku: {field: value, ...}} - chỉnh sửa chưa lưu
    dirtySnapshots: {}, // {sku: originalProduct} - lưu snapshot gốc khi có edit, giúp giữ data khi chuyển trang
    isLoading: false,
    isPendingMode: false, // true = đang hiển thị dữ liệu từ CSV (chưa lưu DB)
    pendingProducts: [],  // Toàn bộ sản phẩm từ CSV đang chờ duyệt
    pendingTotal: 0       // Tổng số sản phẩm pending
};

// ============================================================
// DEBOUNCE HELPER
// ============================================================
function debounce(fn, delay) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ============================================================
// SKELETON LOADING
// ============================================================
function showSkeleton() {
    const tbody = document.getElementById('product-list-body');
    if (!tbody) return;
    let html = '';
    for (let i = 0; i < Math.min(adminState.perPage, 6); i++) {
        html += `
            <tr class="skeleton-row">
                <td><div class="skeleton-block" style="width:100px;height:80px;"></div></td>
                <td><div class="skeleton-block" style="width:60px;height:16px;"></div></td>
                <td>
                    <div class="skeleton-block" style="width:70%;height:18px;margin-bottom:8px;"></div>
                    <div class="skeleton-block" style="width:40%;height:14px;"></div>
                </td>
                <td>
                    <div class="skeleton-block" style="width:80px;height:20px;margin-bottom:4px;"></div>
                    <div class="skeleton-block" style="width:60px;height:14px;"></div>
                </td>
                <td><div class="skeleton-block" style="width:48px;height:26px;border-radius:26px;"></div></td>
                <td><div class="skeleton-block" style="width:60px;height:30px;"></div></td>
            </tr>`;
    }
    tbody.innerHTML = html;
    
    // Mobile skeleton
    const mc = document.getElementById('mobile-cards-container');
    if (mc) {
        let mHtml = '';
        for (let i = 0; i < Math.min(adminState.perPage, 5); i++) {
            mHtml += `
            <div class="product-card-mobile" style="padding:12px;">
                <div style="display:flex;gap:12px;align-items:flex-start;">
                    <div class="skeleton-block" style="width:80px;height:80px;"></div>
                    <div style="flex:1;">
                        <div class="skeleton-block" style="width:60px;height:16px;margin-bottom:8px;"></div>
                        <div class="skeleton-block" style="width:80%;height:18px;margin-bottom:4px;"></div>
                        <div class="skeleton-block" style="width:40%;height:14px;"></div>
                    </div>
                </div>
            </div>`;
        }
        mc.innerHTML = mHtml;
    }
}

// ============================================================
// LOAD PRODUCT LIST (TỪ SERVER - CÓ PHÂN TRANG)
// ============================================================
async function loadProductList() {
    if (adminState.isLoading) return;
    adminState.isLoading = true;
    
    const tbody = document.getElementById('product-list-body');
    if (tbody) showSkeleton();
    
    try {
        const params = new URLSearchParams({
            ajax_action: 'get_products',
            page: adminState.page,
            per_page: adminState.perPage,
            search: adminState.search
        });
        
        const resp = await fetch('import_csv.php?' + params.toString());
        const result = await resp.json();
        
        if (result.success) {
            adminState.data = result.data;
            adminState.total = result.total;
            adminState.totalPages = result.total_pages;
            adminState.page = result.page;
            
            // Merge dirty edits vào data
            for (const sku in adminState.dirtyEdits) {
                const idx = adminState.data.findIndex(p => p.sku === sku);
                if (idx !== -1) {
                    Object.assign(adminState.data[idx], adminState.dirtyEdits[sku]);
                }
            }
            
            renderTable(adminState.data);
            renderMobileCards(adminState.data);
            renderPagination();
            updateAdminInfo();
            addLog(`Đã tải ${result.data.length}/${result.total} sản phẩm (Trang ${result.page}/${result.total_pages})`, 'success');
        }
    } catch (err) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:50px;color:#d70018;">⚠ Lỗi tải dữ liệu: ' + err.message + '</td></tr>';
        const mc = document.getElementById('mobile-cards-container');
        if (mc) mc.innerHTML = '<div style="text-align:center;padding:40px;color:#dc2626;">⚠ Lỗi tải dữ liệu</div>';
    }
    adminState.isLoading = false;
}

// ============================================================
// RENDER TABLE (DÙNG DocumentFragment - SIÊU NHANH)
// ============================================================
function renderTable(data) {
    const tbody = document.getElementById('product-list-body');
    if (!tbody) return;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:50px; color:#999;">' + 
            (adminState.search ? 'Không tìm thấy sản phẩm khớp với "' + escapeHtml(adminState.search) + '"' : 'Chưa có sản phẩm nào.') + 
            '</td></tr>';
        return;
    }
    
    const frag = document.createDocumentFragment();
    
    data.forEach(p => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-sku', p.sku);
        
        // ẢNH: Không kiểm tra file_exists (quá chậm trên network), hiển thị trực tiếp
        let galleryHtml = '';
        let allImages = [];
        if (p.image_1) allImages = p.image_1.split(',').filter(i => i.trim() !== '');
        if (p.temp_images && Array.isArray(p.temp_images)) allImages = [...allImages, ...p.temp_images];
        allImages = allImages.slice(0, 5);
        
        allImages.forEach((img, idx) => {
            const isBase64 = img.startsWith('data:image');
            const imgSrc = isBase64 ? img : `../uploads/${img.trim()}`;
            galleryHtml += `
                <div class="gallery-item ${idx === 0 ? 'main' : ''}">
                    <img src="${imgSrc}" onclick="openLightbox('${imgSrc}')" loading="lazy" onerror="this.style.display='none'">
                    <span class="remove-badge" onclick="handleRemoveImageLocal('${p.sku}', ${idx})">×</span>
                </div>`;
        });
        
        if (allImages.length < 5) {
            galleryHtml += `<div class="gallery-item add-btn" onclick="triggerFileUpload('${p.sku}')"><i class="fas fa-plus"></i></div>`;
        }
        
        // Đánh dấu trạng thái
        const isDirty = adminState.dirtyEdits[p.sku] ? true : false;
        const isPending = (adminState.isPendingMode || p._isPending) ? true : false;
        
        tr.innerHTML = `
            <td>
                <div class="gallery-grid-container" ondrop="dropImageHandler(event, '${p.sku}')" ondragover="allowDrop(event)">
                    ${galleryHtml}
                </div>
            </td>
            <td><code style="font-weight:600; color:#000;">${escapeHtml(p.sku)}</code></td>
            <td>
                <div class="editable" contenteditable="true" data-field="name" style="font-weight:600; color:#323130; margin-bottom:4px; font-size:15px;">${escapeHtml(p.name)}</div>
                <span style="font-size:12px; color:#605e5c; background:#f3f2f1; padding:2px 8px; border-radius:2px; border:1px solid #edebe9;">${escapeHtml(p.cat_code || 'N/A')}</span>
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ccc;">
                    <div class="admin-specs-preview">${renderAdminSpecsPreview(p)}</div>
                    <div class="admin-desc-preview">
                        <div class="admin-desc-title">📝 Mô tả</div>
                        <div class="admin-desc-content">${(p.description || '').replace(/<[^>]*>/g, '').substring(0, 150)}${(p.description || '').length > 150 ? '...' : ''}</div>
                    </div>
                </div>
            </td>
            <td style="vertical-align: top;">
                <div style="color:var(--danger-color); font-weight:700; font-size:15px; margin-top: 5px;">
                    <span class="editable price-inline" contenteditable="true" data-field="sale_price">${parseInt(p.sale_price || 0).toLocaleString('vi-VN')}</span><span class="currency-suffix">₫</span>
                </div>
                <div style="text-decoration:line-through; color:#a19f9d; font-size:12px;">
                    <span class="editable price-inline" contenteditable="true" data-field="price">${parseInt(p.price || 0).toLocaleString('vi-VN')}</span><span class="currency-suffix">₫</span>
                </div>
            </td>
            <td style="text-align:center; vertical-align: top; padding-top: 12px;">
                <label class="stock-toggle" title="${p.status == 1 ? 'Đang Còn hàng - Click để chuyển Hết hàng' : 'Đang Hết hàng - Click để chuyển Còn hàng'}">
                    <input type="checkbox" ${p.status == 1 ? 'checked' : ''} onchange="toggleProductStatus('${escapeHtml(p.sku)}', this.checked)">
                    <span class="toggle-slider"></span>
                </label>
                <div class="stock-status-text" style="font-size:11px; margin-top:4px; font-weight:600; color:${p.status == 1 ? '#107c10' : '#d83b01'};">${p.status == 1 ? 'Còn hàng' : 'Hết hàng'}</div>
            </td>
            <td style="text-align:center; vertical-align: top; padding-top: 15px;">
                <div style="display:flex; flex-direction:column; gap:8px; align-items:center;">
                    <button class="btn-edit-admin" onclick="editProductBySku('${escapeHtml(p.sku)}')"> Chỉnh sửa</button>
                    <button class="btn-delete-admin" onclick="handleDeleteProduct('${escapeHtml(p.sku)}')">🗑️ Xóa</button>
                </div>
            </td>
        `;
        
        // Set background: dirty edits = vàng nhạt, pending = vàng cam nhạt
        if (adminState.dirtyEdits[p.sku]) {
            tr.style.background = '#fffde7';
        } else if (adminState.isPendingMode || p._isPending) {
            tr.style.background = '#fff8e1';
        }
        
        // Thêm badge "CHỜ LƯU" nếu là pending
        if (adminState.isPendingMode || p._isPending) {
            const badge = document.createElement('span');
            badge.style.cssText = 'display:inline-block;background:#ff9800;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;margin-left:6px;font-weight:bold;';
            badge.textContent = 'CHỜ LƯU';
            const skuCell = tr.querySelector('td:nth-child(2) code');
            if (skuCell) skuCell.appendChild(badge);
        }
        
        frag.appendChild(tr);
    });
    
    tbody.innerHTML = '';
    tbody.appendChild(frag);
    
    // Đồng thời render mobile cards
    renderMobileCards(data);
}

// ============================================================
// RENDER MOBILE CARDS (HIỂN THỊ DẠNG THẺ TRÊN ĐIỆN THOẠI)
// ============================================================
function renderMobileCards(data) {
    const container = document.getElementById('mobile-cards-container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#94a3b8;font-size:14px;">' +
            (adminState.search ? 'Không tìm thấy sản phẩm khớp với "' + escapeHtml(adminState.search) + '"' : 'Chưa có sản phẩm nào.') +
            '</div>';
        return;
    }

    let html = '';
    data.forEach(p => {
        const isDirty = adminState.dirtyEdits[p.sku] ? true : false;
        const isPending = (adminState.isPendingMode || p._isPending) ? true : false;
        const dirtyClass = isDirty ? ' dirty' : '';

        // Ảnh đầu tiên
        let firstImg = '';
        let allImages = [];
        if (p.image_1) allImages = p.image_1.split(',').filter(i => i.trim() !== '');
        if (p.temp_images && Array.isArray(p.temp_images)) allImages = [...allImages, ...p.temp_images];
        if (allImages.length > 0) {
            const img = allImages[0];
            const isBase64 = img.startsWith('data:image');
            firstImg = isBase64 ? img : `../uploads/${img.trim()}`;
        }

        // Specs preview
        let specsPreview = '';
        if (p.specs_summary) {
            specsPreview = escapeHtml(String(p.specs_summary).replace(/\|/g, ' | ').substring(0, 120));
        }

        const statusText = p.status == 1 ? 'Còn hàng' : 'Hết hàng';
        const statusColor = p.status == 1 ? '#059669' : '#dc2626';
        const checkedAttr = p.status == 1 ? ' checked' : '';

        html += `
        <div class="product-card-mobile${dirtyClass}" data-sku="${escapeHtml(p.sku)}">
            <div class="pcm-header">
                <div class="pcm-gallery" onclick="editProductBySku('${escapeHtml(p.sku)}')">
                    ${firstImg ? `<img src="${firstImg}" loading="lazy" onerror="this.style.display='none'">` : '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:24px;"><i class="fas fa-image"></i></div>'}
                    ${allImages.length > 1 ? `<span class="pcm-img-count">${allImages.length}</span>` : ''}
                </div>
                <div class="pcm-sku-wrap">
                    <span class="pcm-sku">${escapeHtml(p.sku)}${isPending ? ' <span style="background:#f59e0b;color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;margin-left:4px;">CHỜ LƯU</span>' : ''}</span>
                    <div class="pcm-name">${escapeHtml(p.name)}</div>
                    <span class="pcm-cat">${escapeHtml(p.cat_code || 'N/A')}</span>
                </div>
            </div>
            <div class="pcm-prices">
                <span class="pcm-sale-price">${parseInt(p.sale_price || 0).toLocaleString('vi-VN')}₫</span>
                <span class="pcm-list-price">${parseInt(p.price || 0).toLocaleString('vi-VN')}₫</span>
            </div>
            ${specsPreview ? `<div class="pcm-specs">📋 ${specsPreview}</div>` : ''}
            <div class="pcm-actions">
                <div class="pcm-status">
                    <label class="stock-toggle" title="${statusText}">
                        <input type="checkbox"${checkedAttr} onchange="toggleProductStatus('${escapeHtml(p.sku)}', this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="stock-status-text" style="color:${statusColor}">${statusText}</span>
                </div>
                <div class="pcm-btns">
                    <button class="pcm-btn edit" onclick="editProductBySku('${escapeHtml(p.sku)}')"><i class="fas fa-edit"></i> Sửa</button>
                    <button class="pcm-btn delete" onclick="handleDeleteProduct('${escapeHtml(p.sku)}')"><i class="fas fa-trash"></i> Xóa</button>
                </div>
            </div>
        </div>`;
    });

    container.innerHTML = html;
}
function renderPagination() {
    let container = document.getElementById('pagination-container');
    if (!container) {
        // Tạo container nếu chưa có
        const card = document.querySelector('.card');
        if (!card) return;
        container = document.createElement('div');
        container.id = 'pagination-container';
        container.className = 'admin-pagination';
        card.appendChild(container);
    }
    
    const { page, totalPages, total, perPage } = adminState;
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination-info">Hiển thị ' + ((page-1)*perPage+1) + '-' + Math.min(page*perPage, total) + ' / ' + total + ' sản phẩm</div>';
    html += '<div class="pagination-btns">';
    
    // Nút trang đầu
    html += `<button class="page-btn" onclick="goToPage(1)" ${page===1?'disabled':''} title="Trang đầu"><i class="fas fa-angle-double-left"></i></button>`;
    html += `<button class="page-btn" onclick="goToPage(${page-1})" ${page===1?'disabled':''}><i class="fas fa-angle-left"></i></button>`;
    
    // Hiển thị tối đa 7 nút trang
    let startPage = Math.max(1, page - 3);
    let endPage = Math.min(totalPages, page + 3);
    if (endPage - startPage < 6) {
        if (startPage === 1) endPage = Math.min(totalPages, startPage + 6);
        else startPage = Math.max(1, endPage - 6);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="page-btn ${i===page?'active':''}" onclick="goToPage(${i})">${i}</button>`;
    }
    
    html += `<button class="page-btn" onclick="goToPage(${page+1})" ${page===totalPages?'disabled':''}><i class="fas fa-angle-right"></i></button>`;
    html += `<button class="page-btn" onclick="goToPage(${totalPages})" ${page===totalPages?'disabled':''} title="Trang cuối"><i class="fas fa-angle-double-right"></i></button>`;
    
    // Chọn số dòng/trang
    html += `<select class="per-page-select" onchange="changePerPage(this.value)">`;
    [10, 25, 50, 100].forEach(n => {
        html += `<option value="${n}" ${perPage===n?'selected':''}>${n}/trang</option>`;
    });
    html += '</select>';
    
    html += '</div>';
    container.innerHTML = html;
}

function goToPage(page) {
    if (page < 1 || page > adminState.totalPages || page === adminState.page) return;
    adminState.page = page;
    loadProductList();
    // Scroll lên đầu bảng
    document.querySelector('.card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function changePerPage(n) {
    adminState.perPage = parseInt(n);
    adminState.page = 1;
    loadProductList();
}

function updateAdminInfo() {
    const infoBar = document.querySelector('.admin-info-bar');
    if (!infoBar) return;
    
    // Nếu đang pending mode, dùng updatePendingInfo
    if (adminState.isPendingMode) {
        updatePendingInfo();
        return;
    }
    
    const dirtyCount = Object.keys(adminState.dirtyEdits).length;
    infoBar.innerHTML = '<i class="fas fa-database"></i> Tổng: <strong>' + adminState.total + '</strong> sản phẩm trong Database' +
        (adminState.search ? ' | Tìm: "<strong>' + escapeHtml(adminState.search) + '</strong>"' : '') +
        (dirtyCount > 0 ? ' | <span style="color:#d83b01;">⚠ ' + dirtyCount + ' thay đổi chưa lưu</span>' : '');
}

// ============================================================
// DEBOUNCED UPDATE LOCAL PRODUCT (300ms)
// ============================================================
const debouncedSaveEdit = debounce(function(sku, field, val) {
    // Lưu vào dirtyEdits
    if (!adminState.dirtyEdits[sku]) adminState.dirtyEdits[sku] = {};
    
    // Lưu snapshot gốc của sản phẩm khi có edit đầu tiên (để giữ data khi chuyển trang)
    if (!adminState.dirtySnapshots[sku]) {
        const original = adminState.data.find(p => p.sku === sku);
        if (original) {
            adminState.dirtySnapshots[sku] = JSON.parse(JSON.stringify(original));
        }
    }
    
    // So sánh với dữ liệu gốc từ snapshot
    const snapshot = adminState.dirtySnapshots[sku];
    if (snapshot && String(snapshot[field]) === String(val)) {
        delete adminState.dirtyEdits[sku][field];
        if (Object.keys(adminState.dirtyEdits[sku]).length === 0) {
            delete adminState.dirtyEdits[sku];
            delete adminState.dirtySnapshots[sku];
        }
    } else {
        adminState.dirtyEdits[sku][field] = val;
    }
    
    updateAdminInfo();
    // Highlight hàng
    const row = document.querySelector(`tr[data-sku="${sku}"]`);
    if (row) {
        row.style.background = adminState.dirtyEdits[sku] ? '#fffde7' : '';
    }
    addLog(`✏️ ${sku}.${field} → ${val} (chưa lưu DB)`, 'info');
}, 300);

async function updateLocalProduct(sku, field, val) {
    // Cập nhật trong pendingProducts nếu đang ở pending mode
    if (adminState.isPendingMode) {
        const pIdx = adminState.pendingProducts.findIndex(p => p.sku === sku);
        if (pIdx !== -1) {
            adminState.pendingProducts[pIdx][field] = val;
        }
    }
    // Cập nhật trong data (trang hiện tại)
    const idx = adminState.data.findIndex(p => p.sku === sku);
    if (idx !== -1) {
        adminState.data[idx][field] = val;
    }
    debouncedSaveEdit(sku, field, val);
}

// ============================================================
// BẬT/TẮT TRẠNG THÁI CÒN HÀNG / HẾT HÀNG
// ============================================================
async function toggleProductStatus(sku, checked) {
    const newStatus = checked ? 1 : 0;
    const tr = document.querySelector(`tr[data-sku="${sku}"]`);
    const mobileCard = document.querySelector(`.product-card-mobile[data-sku="${sku}"]`);
    const statusText = tr ? tr.querySelector('.stock-status-text') : null;
    const toggleLabel = tr ? tr.querySelector('.stock-toggle') : null;
    
    // Cập nhật UI desktop
    if (statusText) {
        statusText.textContent = newStatus ? 'Còn hàng' : 'Hết hàng';
        statusText.style.color = newStatus ? '#059669' : '#dc2626';
    }
    if (toggleLabel) {
        toggleLabel.title = newStatus ? 'Đang Còn hàng - Click để chuyển Hết hàng' : 'Đang Hết hàng - Click để chuyển Còn hàng';
    }
    
    // Cập nhật UI mobile card
    if (mobileCard) {
        const mobileStatusText = mobileCard.querySelector('.stock-status-text');
        const mobileToggle = mobileCard.querySelector('.stock-toggle input');
        if (mobileStatusText) {
            mobileStatusText.textContent = newStatus ? 'Còn hàng' : 'Hết hàng';
            mobileStatusText.style.color = newStatus ? '#059669' : '#dc2626';
        }
        if (mobileToggle) {
            mobileToggle.checked = checked;
        }
    }
    
    const formData = new FormData();
    formData.append('ajax_action', 'toggle_status');
    formData.append('sku', sku);
    formData.append('status', newStatus);
    
    try {
        const resp = await fetch('import_csv.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            if (tr) {
                tr.style.transition = 'background 0.3s';
                tr.style.background = '#d4edda';
                setTimeout(() => { tr.style.background = ''; }, 600);
            }
            if (mobileCard) {
                mobileCard.style.transition = 'background 0.3s';
                mobileCard.style.background = '#d4edda';
                setTimeout(() => { mobileCard.style.background = ''; }, 600);
            }
        } else {
            // Revert
            if (statusText) {
                const revertStatus = checked ? 0 : 1;
                statusText.textContent = revertStatus ? 'Còn hàng' : 'Hết hàng';
                statusText.style.color = revertStatus ? '#059669' : '#dc2626';
            }
        }
    } catch (err) {
        if (statusText) {
            const revertStatus = checked ? 0 : 1;
            statusText.textContent = revertStatus ? 'Còn hàng' : 'Hết hàng';
            statusText.style.color = revertStatus ? '#059669' : '#dc2626';
        }
    }
    
    const idx = adminState.data.findIndex(p => p.sku === sku);
    if (idx !== -1) {
        adminState.data[idx].status = newStatus;
    }
}

// ============================================================
// TÌM KIẾM (DEBOUNCED 400ms)
// ============================================================
const debouncedSearch = debounce(function(query) {
    adminState.search = query;
    adminState.page = 1;
    if (adminState.isPendingMode) {
        // Tìm kiếm local trên pending products
        if (query) {
            const q = query.toLowerCase();
            const filtered = adminState.pendingProducts.filter(p => 
                (p.sku || '').toLowerCase().includes(q) ||
                (p.name || '').toLowerCase().includes(q) ||
                (p.cat_code || '').toLowerCase().includes(q)
            );
            adminState.pendingProducts = adminState.pendingProducts; // giữ nguyên
            // Tạm thời hiển thị kết quả lọc
            adminState.total = filtered.length;
            adminState.totalPages = Math.ceil(filtered.length / adminState.perPage);
            const start = 0;
            const pageData = filtered.slice(start, start + adminState.perPage);
            adminState.data = pageData;
            renderTable(pageData);
            renderMobileCards(pageData);
            renderPagination();
        } else {
            renderPendingTable();
        }
    } else {
        loadProductList();
    }
}, 400);

// ============================================================
// KHỞI TẠO ADMIN
// ============================================================
document.addEventListener('DOMContentLoaded', async function() {
    await dbStore.init();
    
    // Khôi phục pending session nếu có (từ lần trước chưa chốt lưu)
    const savedPending = await dbStore.get();
    if (savedPending && Array.isArray(savedPending) && savedPending.length > 0) {
        adminState.pendingProducts = savedPending;
        adminState.pendingTotal = savedPending.length;
        adminState.isPendingMode = true;
        renderPendingTable();
        const cancelBtn = document.getElementById('btn-cancel-pending');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
        addLog('📂 Đã khôi phục ' + savedPending.length + ' sản phẩm từ phiên trước (CHƯA LƯU DB). Bấm "CHỐT LƯU" để lưu.', 'info');
    } else {
        loadProductList();
    }
    
    // Tạo Toast container
    if (!document.getElementById('kb-toast-container')) {
        const container = document.createElement('div');
        container.id = 'kb-toast-container';
        document.body.appendChild(container);
    }
    
    // Tạo Confirm Modal
    if (!document.getElementById('kb-confirm-overlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'kb-confirm-overlay';
        overlay.className = 'kb-confirm-overlay';
        overlay.innerHTML = `
            <div class="kb-confirm-card">
                <i class="fas fa-exclamation-triangle"></i>
                <h3 id="kb-confirm-title">Xác nhận</h3>
                <p id="kb-confirm-msg">Bạn có chắc chắn muốn thực hiện hành động này?</p>
                <div class="kb-confirm-btns">
                    <button class="btn btn-outline" id="kb-confirm-cancel">Hủy bỏ</button>
                    <button class="btn btn-primary" id="kb-confirm-ok" style="background:var(--danger-color);">Đồng ý</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    
    // Nút lưu tất cả
    const btnSaveAll = document.getElementById('btn-save-all');
    if (btnSaveAll) btnSaveAll.addEventListener('click', bulkUpdate);
    
    // Form thêm/sửa sản phẩm
    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveProduct();
        });
    }
    
    // Nạp CSV trực tiếp
    const directCsvInput = document.getElementById('direct_csv_upload');
    if (directCsvInput) {
        directCsvInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) startImport(file);
            this.value = '';
        });
    }
    
    // Inline edit: GIÁ → lưu trực tiếp DB, các field khác → dirty edit
    document.addEventListener('blur', function(e) {
        if (e.target.classList.contains('editable')) {
            const tr = e.target.closest('tr');
            if (!tr) return;
            const sku = tr.getAttribute('data-sku');
            const field = e.target.getAttribute('data-field');
            let val = e.target.innerText.trim();
            
            if (field === 'price' || field === 'sale_price') {
                // Lưu TRỰC TIẾP vào DB (không cần bấm CHỐT LƯU)
                val = val.replace(/[^0-9]/g, '');
                e.target.innerText = val ? parseInt(val).toLocaleString('vi-VN') : '0';
                
                // Gửi AJAX lưu ngay
                const formData = new FormData();
                formData.append('ajax_action', 'save_price_inline');
                formData.append('sku', sku);
                formData.append('field', field);
                formData.append('value', val || '0');
                
                fetch('import_csv.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Cập nhật ô giá còn lại nếu cần
                            const otherField = field === 'price' ? 'sale_price' : 'price';
                            const otherSpan = tr.querySelector(`.editable[data-field="${otherField}"]`);
                            if (otherSpan) {
                                otherSpan.innerText = parseInt(data[otherField] || 0).toLocaleString('vi-VN');
                            }
                            // Flash xanh để báo lưu OK
                            e.target.style.transition = 'background 0.3s';
                            e.target.style.background = '#d4edda';
                            setTimeout(() => { e.target.style.background = ''; }, 800);
                        } else {
                            e.target.style.background = '#f8d7da';
                            setTimeout(() => { e.target.style.background = ''; }, 800);
                        }
                    })
                    .catch(() => {
                        e.target.style.background = '#f8d7da';
                        setTimeout(() => { e.target.style.background = ''; }, 800);
                    });
                
                // Cũng cập nhật local state
                updateLocalProduct(sku, field, val || '0');
            } else {
                // Field khác: dirty edit như cũ
                updateLocalProduct(sku, field, val);
            }
        }
    }, true);
    
    // Phím tắt: Enter trong ô giá → blur để trigger lưu
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.classList.contains('editable')) {
            const field = e.target.getAttribute('data-field');
            if (field === 'price' || field === 'sale_price') {
                e.preventDefault();
                e.target.blur();
            }
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            bulkUpdate();
        }
    });
});

// ============================================================
// BULK UPDATE / CHỐT LƯU: Gửi tất cả lên server lưu DB
// ============================================================
async function bulkUpdate() {
    // === TRƯỜNG HỢP 1: ĐANG Ở CHẾ ĐỘ PENDING (CSV) ===
    if (adminState.isPendingMode && adminState.pendingProducts.length > 0) {
        // Merge dirty edits vào pending products
        const allProducts = adminState.pendingProducts.map(p => {
            const dirty = adminState.dirtyEdits[p.sku] || {};
            return { ...p, ...dirty };
        });
        
        const totalCount = allProducts.length;
        
        const btn = document.getElementById('btn-save-all');
        const origHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ĐANG LƯU ' + totalCount + ' SP VÀO DB...';
        btn.disabled = true;
        
        try {
            // Gửi file CSV gốc lên server (cách nhanh nhất cho số lượng lớn)
            // Tạo CSV từ pending products
            let csvContent = 'sku,cat_code,name,price,sale_price,specs_summary,description,status,specs_group_1,specs_group_2,specs_group_3,specs_group_4\n';
            allProducts.forEach(p => {
                const esc = (str) => str ? '"' + String(str).replace(/"/g, '""') + '"' : '""';
                csvContent += [
                    esc(p.sku), esc(p.cat_code), esc(p.name), p.price, p.sale_price,
                    esc(p.specs_summary), esc(p.description), p.status || 1,
                    esc(p.specs_group_1), esc(p.specs_group_2), esc(p.specs_group_3), esc(p.specs_group_4)
                ].join(',') + '\n';
            });
            
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const formData = new FormData();
            formData.append('ajax_action', 'import_csv_simple');
            formData.append('csv_file', blob, 'import.csv');
            
            const resp = await fetch('import_csv.php', { method: 'POST', body: formData });
            const data = await resp.json();
            
            if (data.success) {
                // Sau khi lưu DB, gửi thêm ảnh nếu có
                const productsWithImages = allProducts.filter(p => p.temp_images && p.temp_images.length > 0);
                if (productsWithImages.length > 0) {
                    showToast('✅ Đã lưu ' + data.count + ' SP. Đang upload ' + productsWithImages.length + ' ảnh...', 'info');
                    for (const p of productsWithImages) {
                        const imgFormData = new FormData();
                        imgFormData.append('ajax_action', 'bulk_update');
                        imgFormData.append('products[0][sku]', p.sku);
                        imgFormData.append('products[0][name]', p.name);
                        imgFormData.append('products[0][sale_price]', String(p.sale_price || '').replace(/[^0-9]/g, ''));
                        imgFormData.append('products[0][price]', String(p.price || '').replace(/[^0-9]/g, ''));
                        imgFormData.append('products[0][cat_code]', p.cat_code || '');
                        imgFormData.append('products[0][specs_summary]', p.specs_summary || '');
                        imgFormData.append('products[0][specs_group_1]', p.specs_group_1 || '');
                        imgFormData.append('products[0][specs_group_2]', p.specs_group_2 || '');
                        imgFormData.append('products[0][specs_group_3]', p.specs_group_3 || '');
                        imgFormData.append('products[0][specs_group_4]', p.specs_group_4 || '');
                        imgFormData.append('products[0][description]', p.description || '');
                        p.temp_images.forEach((base64, idx) => {
                            imgFormData.append('products[0][temp_images][' + idx + ']', base64);
                        });
                        await fetch('import_csv.php', { method: 'POST', body: imgFormData });
                    }
                }
                
                // Xóa pending mode
                adminState.isPendingMode = false;
                adminState.pendingProducts = [];
                adminState.pendingTotal = 0;
                adminState.dirtyEdits = {};
                adminState.dirtySnapshots = {};
                adminState.page = 1;
                adminState.search = '';
                await dbStore.clear();
                
                // Ẩn nút hủy
                const cancelBtn = document.getElementById('btn-cancel-pending');
                if (cancelBtn) cancelBtn.style.display = 'none';
                
                showToast('✅ Đã chốt lưu ' + data.count + ' sản phẩm vào Database thành công!', 'success');
                addLog('💾 CHỐT LƯU: ' + data.count + ' sản phẩm đã vào Database.', 'success');
                loadProductList();
            } else {
                showToast('❌ Lỗi lưu: ' + (data.error || 'Không xác định'), 'error');
                addLog('❌ CHỐT LƯU thất bại: ' + (data.error || ''), 'error');
            }
        } catch (err) {
            showToast('❌ Lỗi kết nối: ' + err.message, 'error');
        }
        
        btn.innerHTML = origHTML;
        btn.disabled = false;
        return;
    }
    
    // === TRƯỜNG HỢP 2: CHẾ ĐỘ DB (chỉnh sửa sản phẩm đã có) ===
    const dirtySkus = Object.keys(adminState.dirtyEdits);
    
    if (dirtySkus.length === 0) {
        showToast('Không có thay đổi nào cần lưu!', 'info');
        return;
    }
    
    const dirtyProducts = [];
    for (const sku of dirtySkus) {
        const idx = adminState.data.findIndex(p => p.sku === sku);
        const dirty = adminState.dirtyEdits[sku] || {};
        
        if (idx !== -1) {
            // SKU đang hiển thị trên trang hiện tại
            dirtyProducts.push({ ...adminState.data[idx], ...dirty });
        } else if (adminState.dirtySnapshots[sku]) {
            // SKU không có trên trang hiện tại nhưng có snapshot → dùng snapshot + dirty
            dirtyProducts.push({ ...adminState.dirtySnapshots[sku], ...dirty });
        } else {
            // Không có data và không có snapshot → chỉ gửi những gì có trong dirtyEdits
            // (trường hợp này hiếm, nhưng vẫn cho gửi để không mất dữ liệu)
            const minimalProduct = { sku: sku, ...dirty };
            if (minimalProduct.sku && (minimalProduct.name || minimalProduct.temp_images)) {
                dirtyProducts.push(minimalProduct);
            } else {
                // Thực sự không đủ thông tin → xóa dirty edit
                delete adminState.dirtyEdits[sku];
                delete adminState.dirtySnapshots[sku];
            }
        }
    }
    
    if (dirtyProducts.length === 0) {
        showToast('Không có dữ liệu hợp lệ để lưu!', 'warning');
        loadProductList();
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax_action', 'bulk_update');
    dirtyProducts.forEach((p, idx) => {
        formData.append('products[' + idx + '][sku]', p.sku);
        formData.append('products[' + idx + '][name]', p.name);
        formData.append('products[' + idx + '][sale_price]', String(p.sale_price || '').replace(/[^0-9]/g, ''));
        formData.append('products[' + idx + '][price]', String(p.price || '').replace(/[^0-9]/g, ''));
        formData.append('products[' + idx + '][cat_code]', p.cat_code || '');
        formData.append('products[' + idx + '][specs_summary]', p.specs_summary || '');
        formData.append('products[' + idx + '][specs_group_1]', p.specs_group_1 || '');
        formData.append('products[' + idx + '][specs_group_2]', p.specs_group_2 || '');
        formData.append('products[' + idx + '][specs_group_3]', p.specs_group_3 || '');
        formData.append('products[' + idx + '][specs_group_4]', p.specs_group_4 || '');
        formData.append('products[' + idx + '][description]', p.description || '');
        if (p.temp_images) {
            p.temp_images.forEach((base64, imgIdx) => {
                formData.append('products[' + idx + '][temp_images][' + imgIdx + ']', base64);
            });
        }
    });
    
    const btn = document.getElementById('btn-save-all');
    const origHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ĐANG LƯU ' + dirtyProducts.length + ' SP...';
    btn.disabled = true;
    
    try {
        const resp = await fetch('import_csv.php', { method: 'POST', body: formData });
        const data = await resp.json();
        
        if (data.success) {
            adminState.dirtyEdits = {};
            adminState.dirtySnapshots = {};
            showToast('✅ Đã chốt lưu ' + data.count + ' sản phẩm thành công!', 'success');
            addLog('💾 Đã chốt lưu ' + data.count + ' sản phẩm vào Database.', 'success');
            loadProductList();
        } else {
            showToast('❌ Lỗi: ' + (data.error || 'Không xác định'), 'error');
        }
    } catch (err) {
        showToast('❌ Lỗi kết nối: ' + err.message, 'error');
    }
    
    btn.innerHTML = origHTML;
    btn.disabled = false;
}

// ============================================================
// XÓA SẢN PHẨM
// ============================================================
async function handleDeleteProduct(sku) {
    // Nếu đang pending mode, xóa khỏi pending list
    if (adminState.isPendingMode) {
        const ok = await kbConfirm('Xóa sản phẩm "' + sku + '" khỏi danh sách chờ?', 'Xác nhận xóa');
        if (!ok) return;
        adminState.pendingProducts = adminState.pendingProducts.filter(p => p.sku !== sku);
        adminState.pendingTotal = adminState.pendingProducts.length;
        delete adminState.dirtyEdits[sku];
        delete adminState.dirtySnapshots[sku];
        await dbStore.save(adminState.pendingProducts);
        renderPendingTable();
        showToast('Đã xóa ' + sku + ' khỏi danh sách chờ.', 'success');
        return;
    }
    
    // Chế độ DB: xóa khỏi database
    const ok = await kbConfirm('Xóa vĩnh viễn sản phẩm ' + sku + ' khỏi Database?', 'Cảnh báo');
    if (!ok) return;
    
    const fd = new FormData();
    fd.append('ajax_action', 'delete_product');
    fd.append('sku', sku);
    
    try {
        const resp = await fetch('import_csv.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            delete adminState.dirtyEdits[sku];
            delete adminState.dirtySnapshots[sku];
            showToast('Đã xóa ' + sku, 'success');
            loadProductList();
        }
    } catch (err) {
        showToast('Lỗi xóa: ' + err.message, 'error');
    }
}

// ============================================================
// IMPORT CSV: Parse → Lưu tạm → Hiện bảng (CHƯA VÔ DB)
// ============================================================
function startImport(file) {
    showToast('⏳ Đang đọc file CSV...', 'info');
    const reader = new FileReader();
    reader.onload = async (e) => {
        const text = e.target.result;
        const rows = parseCSV(text);
        
        if (rows.length < 2) {
            showToast('❌ File CSV rỗng hoặc chỉ có header!', 'error');
            return;
        }
        
        // Dòng đầu là header
        const headers = rows[0];
        const dataRows = rows.slice(1);
        
        // Parse từng dòng thành object sản phẩm
        const products = [];
        let skipped = 0;
        
        for (let i = 0; i < dataRows.length; i++) {
            const row = dataRows[i];
            if (row.length < 3) { skipped++; continue; }
            
            const sku = (row[0] || '').trim();
            const cat_code = (row[1] || '').trim();
            const name = (row[2] || '').trim();
            
            if (!sku || !name) { skipped++; continue; }
            
            const price = parseInt(String(row[3] || '0').replace(/[^0-9]/g, ''));
            const sale_price = parseInt(String(row[4] || '0').replace(/[^0-9]/g, ''));
            const specs = (row[5] || '').replace(/\n/g, '|').replace(/\r/g, '');
            const description = (row[6] || '').trim();
            const status = parseInt(row[7] || '1');
            const specsG1 = (row[8] || '').trim();
            const specsG2 = (row[9] || '').trim();
            const specsG3 = (row[10] || '').trim();
            const specsG4 = (row[11] || '').trim();
            
            products.push({
                sku: sku,
                cat_code: cat_code,
                name: name,
                slug: '',
                price: price,
                sale_price: sale_price,
                specs_summary: specs,
                specs_group_1: specsG1,
                specs_group_2: specsG2,
                specs_group_3: specsG3,
                specs_group_4: specsG4,
                description: description,
                status: status,
                sort_order: i,
                image_1: '',
                temp_images: [],
                _isPending: true  // Đánh dấu là hàng chờ duyệt
            });
        }
        
        if (products.length === 0) {
            showToast('❌ Không có sản phẩm hợp lệ nào trong file CSV! (đã bỏ qua ' + skipped + ' dòng lỗi)', 'error');
            return;
        }
        
        // Lưu vào adminState pending
        adminState.pendingProducts = products;
        adminState.pendingTotal = products.length;
        adminState.isPendingMode = true;
        adminState.page = 1;
        adminState.search = '';
        adminState.dirtyEdits = {};
        
        // Lưu vào IndexedDB để phòng reload trang
        await dbStore.save(products);
        
        // Hiển thị pending products (phân trang thủ công)
        renderPendingTable();
        
        // Hiện nút hủy import
        const cancelBtn = document.getElementById('btn-cancel-pending');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
        
        const msg = '📥 Đã đọc ' + products.length + ' sản phẩm từ CSV. Kiểm tra, thêm ảnh rồi bấm "CHỐT LƯU" để lưu vào Database.';
        if (skipped > 0) msg += ' (Đã bỏ qua ' + skipped + ' dòng không hợp lệ)';
        showToast(msg, 'success');
        addLog(msg, 'success');
    };
    reader.readAsText(file);
}

// ============================================================
// HIỂN THỊ BẢNG PENDING (TỪ CSV, CHƯA LƯU DB)
// ============================================================
function renderPendingTable() {
    const allProducts = adminState.pendingProducts;
    adminState.total = allProducts.length;
    adminState.totalPages = Math.ceil(allProducts.length / adminState.perPage);
    
    const start = (adminState.page - 1) * adminState.perPage;
    const pageData = allProducts.slice(start, start + adminState.perPage);
    adminState.data = pageData;
    
    renderTable(pageData);
    renderMobileCards(pageData);
    renderPagination();
}

function updatePendingInfo() {
    const infoBar = document.querySelector('.admin-info-bar');
    const dirtyCount = Object.keys(adminState.dirtyEdits).length;
    if (infoBar) {
        infoBar.innerHTML = '<i class="fas fa-file-csv" style="color:#107c10;"></i> ' +
            '<strong style="color:#d83b01;">⚠ CHƯA LƯU DATABASE</strong> | ' +
            'Tổng: <strong>' + adminState.pendingTotal + '</strong> sản phẩm từ CSV' +
            (dirtyCount > 0 ? ' | <span style="color:#d83b01;">⚠ ' + dirtyCount + ' thay đổi chưa lưu</span>' : '') +
            ' | <em style="color:#888;">Bấm "CHỐT LƯU" để lưu tất cả vào Database</em>';
    }
}

// ============================================================
// GO TO PAGE (hỗ trợ cả pending mode)
// ============================================================
function goToPage(page) {
    if (page < 1 || page > adminState.totalPages || page === adminState.page) return;
    adminState.page = page;
    if (adminState.isPendingMode) {
        renderPendingTable();
    } else {
        loadProductList();
    }
    document.querySelector('.card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function changePerPage(n) {
    adminState.perPage = parseInt(n);
    adminState.page = 1;
    if (adminState.isPendingMode) {
        renderPendingTable();
    } else {
        loadProductList();
    }
}

// ============================================================
// HỦY PENDING MODE - XÓA DỮ LIỆU CSV CHƯA LƯU
// ============================================================
async function cancelPendingMode() {
    if (adminState.pendingProducts.length > 0) {
        const ok = await kbConfirm(
            'Bạn có chắc muốn hủy? ' + adminState.pendingProducts.length + ' sản phẩm từ CSV sẽ bị xóa khỏi bảng (chưa lưu Database nên không mất gì).',
            'Hủy import CSV?'
        );
        if (!ok) return;
    }
    
    adminState.isPendingMode = false;
    adminState.pendingProducts = [];
    adminState.pendingTotal = 0;
    adminState.dirtyEdits = {};
    adminState.dirtySnapshots = {};
    adminState.page = 1;
    adminState.search = '';
    await dbStore.clear();
    
    document.getElementById('btn-cancel-pending').style.display = 'none';
    loadProductList();
    showToast('Đã hủy import CSV, trở về danh sách Database.', 'info');
}

// ============================================================
// LIGHTBOX & MODAL
// ============================================================
function openLightbox(src) {
    let modal = document.getElementById('lightbox-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'lightbox-modal';
        modal.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:99999;align-items:center;justify-content:center;cursor:zoom-out;';
        modal.onclick = function() { this.style.display = 'none'; };
        modal.innerHTML = '<span style="position:absolute;top:20px;right:30px;color:#fff;font-size:40px;cursor:pointer;">&times;</span><img id="lightbox-img" src="" style="max-width:90%;max-height:90%;object-fit:contain;border-radius:8px;">';
        document.body.appendChild(modal);
    }
    document.getElementById('lightbox-img').src = src;
    modal.style.display = 'flex';
}

function showProductModal() { 
    document.getElementById('product-modal').style.display = 'flex'; 
    document.getElementById('product-form').reset();
    document.getElementById('p_sku').value = '';
    document.getElementById('modal-title').innerText = 'Thêm sản phẩm mới';
    ['specs_group_1','specs_group_2','specs_group_3','specs_group_4'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    if (typeof tandaEditor !== 'undefined' && tandaEditor) tandaEditor.setData('');
}
function hideProductModal() { document.getElementById('product-modal').style.display = 'none'; }

async function editProductBySku(sku) {
    try {
        const resp = await fetch('../ajax_get_product.php?sku=' + encodeURIComponent(sku));
        const product = await resp.json();
        if (product && !product.error) { editProduct(product); }
        else { showToast('Không tìm thấy sản phẩm: ' + sku, 'error'); }
    } catch(e) { showToast('Lỗi tải dữ liệu', 'error'); }
}

function editProduct(p) {
    document.getElementById('modal-title').innerText = 'Sửa: ' + (p.name || '');
    document.getElementById('p_sku').value = p.sku || '';
    document.getElementById('p_cat').value = p.cat_code || '';
    document.getElementById('p_name').value = p.name || '';
    document.getElementById('p_price').value = (p.price || 0).toLocaleString('vi-VN');
    document.getElementById('p_sale').value = (p.sale_price || 0).toLocaleString('vi-VN');
    document.getElementById('p_specs').value = p.specs_summary || '';
    var g1 = p.specs_group_1 || '', g2 = p.specs_group_2 || '', g3 = p.specs_group_3 || '', g4 = p.specs_group_4 || '';
    if (!g1 && !g2 && !g3 && !g4 && p.specs_summary) g1 = p.specs_summary.replace(/\|/g, '\n').replace(/\//g, '\n');
    var sg1 = document.getElementById('specs_group_1'); if (sg1) sg1.value = g1;
    var sg2 = document.getElementById('specs_group_2'); if (sg2) sg2.value = g2;
    var sg3 = document.getElementById('specs_group_3'); if (sg3) sg3.value = g3;
    var sg4 = document.getElementById('specs_group_4'); if (sg4) sg4.value = g4;
    if (typeof tandaEditor !== 'undefined' && tandaEditor) tandaEditor.setData(p.description || '');
    else document.getElementById('p_desc').value = p.description || '';
    document.getElementById('product-modal').style.display = 'flex';
}

async function saveProduct() {
    if (typeof tandaEditor !== 'undefined' && tandaEditor) document.getElementById('p_desc').value = tandaEditor.getData();
    var g1 = document.getElementById('specs_group_1'), g2 = document.getElementById('specs_group_2');
    var autoSpecs = ((g1 ? g1.value : '') + '\n' + (g2 ? g2.value : '')).replace(/\n+/g, ' | ').trim();
    var formData = new FormData(document.getElementById('product-form'));
    formData.set('specs_summary', autoSpecs);
    formData.append('ajax_action', 'save_product');
    try {
        const resp = await fetch('import_csv.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) { hideProductModal(); loadProductList(); showToast('Đã lưu sản phẩm!', 'success'); }
        else { showToast('Lỗi: ' + (data.error || 'Không xác định'), 'error'); }
    } catch(e) { showToast('Lỗi kết nối', 'error'); }
}

// ============================================================
// KÉO THẢ ẢNH (DÙNG adminState THAY VÌ dbStore)
// ============================================================
function allowDrop(e) { e.preventDefault(); }
function dropImageHandler(e, sku) { e.preventDefault(); processFiles(e.dataTransfer.files, sku); }

function triggerFileUpload(sku) {
    const input = document.createElement('input');
    input.type = 'file'; input.multiple = true; input.accept = 'image/*';
    input.onchange = (e) => processFiles(e.target.files, sku);
    input.click();
}

async function compressImage(file, maxWidth = 800, quality = 0.7) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width, height = img.height;
                if (width > maxWidth) { height = (maxWidth / width) * height; width = maxWidth; }
                canvas.width = width; canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
        };
    });
}

async function processFiles(files, sku) {
    // Tìm trong pendingProducts trước (nếu pending mode), rồi mới tới data
    let targetProduct = null;
    let pIdx = -1;
    
    if (adminState.isPendingMode) {
        pIdx = adminState.pendingProducts.findIndex(p => p.sku === sku);
        if (pIdx !== -1) targetProduct = adminState.pendingProducts[pIdx];
    }
    
    if (!targetProduct) {
        pIdx = adminState.data.findIndex(p => p.sku === sku);
        if (pIdx !== -1) targetProduct = adminState.data[pIdx];
    }
    
    if (!targetProduct) return;
    if (!targetProduct.temp_images) targetProduct.temp_images = [];
    
    let currentTotal = (targetProduct.image_1 ? targetProduct.image_1.split(',').filter(i => i.trim() !== '').length : 0) + targetProduct.temp_images.length;
    
    for (let file of Array.from(files)) {
        if (currentTotal >= 5) { showToast('Tối đa 5 ảnh!', 'info'); break; }
        const originalSize = (file.size / 1024).toFixed(1);
        const compressedBase64 = await compressImage(file);
        const compressedSize = ((compressedBase64.length * 3/4) / 1024).toFixed(1);
        targetProduct.temp_images.push(compressedBase64);
        currentTotal++;
        addLog('📸 Nén ảnh ' + sku + ': ' + originalSize + 'KB → ' + compressedSize + 'KB', 'success');
    }
    
    // Đồng bộ lại vào adminState
    if (adminState.isPendingMode && pIdx !== -1) {
        adminState.pendingProducts[pIdx] = targetProduct;
    }
    if (pIdx !== -1) {
        // Cập nhật data nếu sản phẩm đang hiển thị trên trang
        const dataIdx = adminState.data.findIndex(p => p.sku === sku);
        if (dataIdx !== -1) adminState.data[dataIdx] = targetProduct;
    }
    
    adminState.dirtyEdits[sku] = adminState.dirtyEdits[sku] || {};
    adminState.dirtyEdits[sku].temp_images = targetProduct.temp_images;
    adminState.dirtyEdits[sku].image_1 = targetProduct.image_1;
    
    // Lưu snapshot gốc để giữ data khi chuyển trang
    if (!adminState.dirtySnapshots[sku]) {
        adminState.dirtySnapshots[sku] = JSON.parse(JSON.stringify(targetProduct));
    } else {
        adminState.dirtySnapshots[sku].temp_images = [...targetProduct.temp_images];
        adminState.dirtySnapshots[sku].image_1 = targetProduct.image_1;
    }
    
    renderTable(adminState.data);
}

async function handleRemoveImageLocal(sku, imgIdx) {
    // Tìm trong pendingProducts trước
    let targetProduct = null;
    let pIdx = -1;
    
    if (adminState.isPendingMode) {
        pIdx = adminState.pendingProducts.findIndex(p => p.sku === sku);
        if (pIdx !== -1) targetProduct = adminState.pendingProducts[pIdx];
    }
    
    if (!targetProduct) {
        pIdx = adminState.data.findIndex(p => p.sku === sku);
        if (pIdx !== -1) targetProduct = adminState.data[pIdx];
    }
    
    if (!targetProduct) return;
    
    let dbImages = targetProduct.image_1 ? targetProduct.image_1.split(',').filter(i => i.trim() !== '') : [];
    let tempImages = targetProduct.temp_images || [];
    
    if (imgIdx < dbImages.length) {
        dbImages.splice(imgIdx, 1);
        targetProduct.image_1 = dbImages.join(',');
    } else {
        tempImages.splice(imgIdx - dbImages.length, 1);
        targetProduct.temp_images = tempImages;
    }
    
    if (adminState.isPendingMode && pIdx !== -1) {
        adminState.pendingProducts[pIdx] = targetProduct;
    }
    const dataIdx = adminState.data.findIndex(p => p.sku === sku);
    if (dataIdx !== -1) adminState.data[dataIdx] = targetProduct;
    
    adminState.dirtyEdits[sku] = adminState.dirtyEdits[sku] || {};
    adminState.dirtyEdits[sku].image_1 = targetProduct.image_1;
    adminState.dirtyEdits[sku].temp_images = targetProduct.temp_images;
    
    // Cập nhật snapshot nếu có
    if (adminState.dirtySnapshots[sku]) {
        adminState.dirtySnapshots[sku].image_1 = targetProduct.image_1;
        adminState.dirtySnapshots[sku].temp_images = [...targetProduct.temp_images];
    }
    
    renderTable(adminState.data);
}

// ============================================================
// XÓA TẤT CẢ & XUẤT EXCEL
// ============================================================
async function deleteAllProducts() {
    const ok1 = await kbConfirm("Xóa VĨNH VIỄN toàn bộ sản phẩm trong Database?", "XÁC NHẬN XÓA TẤT CẢ");
    if (!ok1) return;
    const ok2 = await kbConfirm("Dữ liệu không thể khôi phục! Chắc chắn?", "CẢNH BÁO CUỐI CÙNG");
    if (!ok2) return;
    
    const fd = new FormData();
    fd.append('ajax_action', 'delete_all_products');
    try {
        const resp = await fetch('import_csv.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            adminState.data = []; adminState.dirtyEdits = {}; adminState.dirtySnapshots = {}; adminState.total = 0;
            showToast('Đã xóa sạch toàn bộ!', 'success');
            loadProductList();
        } else { showToast('Lỗi: ' + data.error, 'error'); }
    } catch(e) { showToast('Lỗi: ' + e.message, 'error'); }
}

async function exportToCSV() {
    // Lấy toàn bộ sản phẩm từ server (không phân trang)
    try {
        const resp = await fetch('import_csv.php?ajax_action=get_products&per_page=99999');
        const result = await resp.json();
        const data = result.data || [];
        
        if (data.length === 0) { showToast('Không có dữ liệu!', 'error'); return; }
        
        let csvContent = "\uFEFFsku,catcode,product,price,saleprice,specs,description,status\n";
        data.forEach(p => {
            const escapeCSV = (str) => str ? `"${String(str).replace(/"/g, '""')}"` : '""';
            csvContent += [p.sku, p.cat_code, escapeCSV(p.name), p.price, p.sale_price, escapeCSV(p.specs_summary), escapeCSV(p.description), 1].join(",") + "\n";
        });
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `TANDA_Products_${new Date().toISOString().slice(0,10).replace(/-/g,"")}.csv`;
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
        showToast('Đã xuất ' + data.length + ' sản phẩm!', 'success');
    } catch(e) { showToast('Lỗi xuất: ' + e.message, 'error'); }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ============================================================
// TOAST NOTIFICATION SYSTEM
// ============================================================
function showToast(message, type = 'info') {
    const container = document.getElementById('kb-toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'kb-toast ' + type;
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    toast.innerHTML = '<span>' + (icons[type] || '') + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// ============================================================
// CUSTOM CONFIRM DIALOG (returns Promise<boolean>)
// ============================================================
function kbConfirm(message, title = 'Xác nhận') {
    return new Promise((resolve) => {
        const overlay = document.getElementById('kb-confirm-overlay');
        if (!overlay) { resolve(confirm(message)); return; }
        document.getElementById('kb-confirm-title').textContent = title;
        document.getElementById('kb-confirm-msg').textContent = message;
        overlay.style.display = 'flex';
        const okBtn = document.getElementById('kb-confirm-ok');
        const cancelBtn = document.getElementById('kb-confirm-cancel');
        const cleanup = () => {
            overlay.style.display = 'none';
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
        };
        const onOk = () => { cleanup(); resolve(true); };
        const onCancel = () => { cleanup(); resolve(false); };
        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
    });
}

// ============================================================
// CONSOLE LOGGING (Ghi log ra console DevTools)
// ============================================================
function addLog(message, type = 'info') {
    const styles = {
        success: 'color: #107c10; font-weight: bold;',
        error: 'color: #d83b01; font-weight: bold;',
        info: 'color: #0078d4;'
    };
    const time = new Date().toLocaleTimeString('vi-VN');
    console.log('%c[' + time + '] ' + message, styles[type] || '');
}

// ============================================================
// RENDER ADMIN SPECS PREVIEW (Trong bảng)
// ============================================================
function renderAdminSpecsPreview(p) {
    if (!p) return '';
    const groups = [
        { title: '📷 Thông số kỹ thuật', content: p.specs_group_1 },
        { title: '📡 Kết nối & Lưu trữ', content: p.specs_group_2 },
        { title: '⚡ Nguồn & Điều kiện', content: p.specs_group_3 },
        { title: '🔧 Lắp đặt & Hỗ trợ', content: p.specs_group_4 }
    ];
    let html = '';
    groups.forEach(g => {
        if (!g.content || g.content.trim() === '') return;
        const lines = g.content.trim().split('\n').filter(l => l.trim() !== '');
        if (lines.length === 0) return;
        html += '<div class="admin-spec-box">';
        html += '<div class="admin-spec-title">' + g.title + '</div>';
        html += '<div class="admin-spec-content">';
        lines.forEach(line => {
            html += '<div class="admin-spec-line">' + escapeHtml(line.trim()) + '</div>';
        });
        html += '</div></div>';
    });
    return html || '<span style="color:#a19f9d;font-size:11px;">Chưa có thông số</span>';
}

// ============================================================
// HIDE IMPORT MODAL
// ============================================================
function hideImportModal() {
    const modal = document.getElementById('import-modal');
    if (modal) modal.style.display = 'none';
}