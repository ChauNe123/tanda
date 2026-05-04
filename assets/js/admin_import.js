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

document.addEventListener('DOMContentLoaded', async function() {
    // Khởi tạo DB
    await dbStore.init();

    // 1. Tạo container cho Toast
    if (!document.getElementById('kb-toast-container')) {
        const container = document.createElement('div');
        container.id = 'kb-toast-container';
        document.body.appendChild(container);
    }

    // 2. Tạo cấu trúc cho Confirm Modal
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

    loadProductList();

    const btnSaveAll = document.getElementById('btn-save-all');
    if (btnSaveAll) btnSaveAll.addEventListener('click', bulkUpdate);

    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveProduct(new FormData(this));
        });
    }

    const csvInput = document.getElementById('csv_file_input');
    if (csvInput) {
        csvInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) previewCSV(file);
        });
    }

    const btnConfirmImport = document.getElementById('btn_confirm_import');
    if (btnConfirmImport) {
        btnConfirmImport.addEventListener('click', function() {
            const file = csvInput.files[0];
            if (file) startImport(file);
        });
    }

    // Lắng nghe sự kiện chỉnh sửa trực tiếp trên bảng
    document.addEventListener('blur', function(e) {
        if (e.target.classList.contains('editable')) {
            const tr = e.target.closest('tr');
            const sku = tr.getAttribute('data-sku');
            const field = e.target.getAttribute('data-field');
            let val = e.target.innerText.trim();
            if (field === 'price' || field === 'sale_price') {
                val = val.replace(/[^0-9]/g, '');
            }
            updateLocalProduct(sku, field, val);
        }
    }, true);
});

// --- HÀM XÁC NHẬN TÙY CHỈNH (THAY THẾ CONFIRM) ---
function kbConfirm(message, title = 'Xác nhận xóa') {
    return new Promise((resolve) => {
        const overlay = document.getElementById('kb-confirm-overlay');
        const msgPara = document.getElementById('kb-confirm-msg');
        const titleH3 = document.getElementById('kb-confirm-title');
        const okBtn = document.getElementById('kb-confirm-ok');
        const cancelBtn = document.getElementById('kb-confirm-cancel');

        msgPara.innerText = message;
        titleH3.innerText = title;
        overlay.style.display = 'flex';

        const handleOk = () => {
            overlay.style.display = 'none';
            cleanup();
            resolve(true);
        };

        const handleCancel = () => {
            overlay.style.display = 'none';
            cleanup();
            resolve(false);
        };

        const cleanup = () => {
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
        };

        okBtn.addEventListener('click', handleOk);
        cancelBtn.addEventListener('click', handleCancel);
    });
}

// --- THÔNG BÁO TOAST ---
function showToast(message, type = 'success') {
    const container = document.getElementById('kb-toast-container');
    const toast = document.createElement('div');
    toast.className = `kb-toast ${type}`;
    let icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-circle';
    if (type === 'info') icon = 'fa-info-circle';
    toast.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 500);
    }, 4000);
}

function addLog(msg, type = 'info') {
    const now = new Date().toLocaleTimeString();
    const logMsg = `[${now}] [${type.toUpperCase()}] ${msg}`;
    
    switch(type) {
        case 'error':
            console.error(logMsg);
            break;
        case 'success':
            console.log('%c' + logMsg, 'color: #28a745; font-weight: bold;');
            break;
        case 'info':
            console.info(logMsg);
            break;
        default:
            console.log(logMsg);
    }
}

async function loadProductList() {
    const data = await dbStore.get();
    if (data) {
        renderTable(data);
        addLog('Đã nạp ' + data.length + ' sản phẩm từ bộ nhớ IndexedDB.', 'info');
        return;
    }

    fetch('import_csv.php?ajax_action=get_products')
        .then(res => res.json())
        .then(async data => {
            await dbStore.save(data);
            renderTable(data);
            addLog('Đã đồng bộ ' + data.length + ' sản phẩm từ Database.', 'success');
        });
}

function renderTable(data) {
    const tbody = document.getElementById('product-list-body');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:50px; color:#999;">Chưa có sản phẩm nào.</td></tr>';
        return;
    }
    data.forEach(p => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-sku', p.sku);
        
        let galleryHtml = '';
        let allImages = [];
        if (p.image_1) allImages = p.image_1.split(',').filter(i => i.trim() !== '');
        if (p.temp_images && Array.isArray(p.temp_images)) allImages = [...allImages, ...p.temp_images];
        allImages = allImages.slice(0, 5);

        allImages.forEach((img, idx) => {
            const isBase64 = img.startsWith('data:image');
            const imgSrc = isBase64 ? img : `../uploads/${img.trim()}`;
            
            // Tính toán dung lượng hiển thị khi hover
            let sizeInfo = '';
            if (isBase64) {
                const kb = ((img.length * 3/4) / 1024).toFixed(0);
                sizeInfo = `Dung lượng: ${kb}KB (Đã nén)`;
            } else {
                sizeInfo = `Ảnh đã lưu trên Server`;
            }

            galleryHtml += `
                <div class="gallery-item ${idx === 0 ? 'main' : ''}" title="${sizeInfo}">
                    <img src="${imgSrc}" onclick="openLightbox('${imgSrc}')">
                    <span class="remove-badge" onclick="handleRemoveImageLocal('${p.sku}', ${idx})">×</span>
                </div>
            `;
        });

        if (allImages.length < 5) {
            galleryHtml += `
                <div class="gallery-item add-btn" onclick="triggerFileUpload('${p.sku}')">
                    <i class="fas fa-plus"></i>
                </div>
            `;
        }

        tr.innerHTML = `
            <td>
                <div class="gallery-grid-container" ondrop="dropImageHandler(event, '${p.sku}')" ondragover="allowDrop(event)">
                    ${galleryHtml}
                </div>
            </td>
            <td><code style="font-weight:600; color:#000;">${p.sku}</code></td>
            <td>
                <div class="editable" contenteditable="true" data-field="name" style="font-weight:600; color:#323130; margin-bottom:4px;">${p.name}</div>
                <span style="font-size:12px; color:#605e5c; background:#f3f2f1; padding:2px 8px; border-radius:2px; border:1px solid #edebe9;">${p.cat_code || 'N/A'}</span>
            </td>
            <td>
                <div style="color:var(--danger-color); font-weight:700; font-size:15px;">
                    <span class="editable" contenteditable="true" data-field="sale_price">${parseInt(p.sale_price || 0).toLocaleString()}</span>đ
                </div>
                <div style="text-decoration:line-through; color:#a19f9d; font-size:12px;">
                    <span class="editable" contenteditable="true" data-field="price">${parseInt(p.price || 0).toLocaleString()}</span>đ
                </div>
            </td>
            <td style="text-align:center;">
                <div style="display:flex; flex-direction:column; gap:4px; align-items:center;">
                    <button class="btn btn-outline" style="padding:4px 10px; width:100px;" onclick='editProduct(${JSON.stringify(p).replace(/'/g, "&apos;")})'><i class="fas fa-edit"></i> Sửa</button>
                    <button class="btn btn-outline" style="padding:4px 10px; width:100px; color:var(--danger-color);" onclick="handleDeleteProduct('${p.sku}')"><i class="fas fa-trash"></i> Xóa</button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

async function updateLocalProduct(sku, field, val) {
    const data = await dbStore.get();
    if (!data) return;
    const idx = data.findIndex(p => p.sku === sku);
    if (idx !== -1) {
        data[idx][field] = val;
        await dbStore.save(data);
    }
}

async function handleRemoveImage(sku, filename) {
    const ok = await kbConfirm("Bạn có chắc chắn muốn xóa ảnh này?", "Xác nhận");
    if (ok) {
        const fd = new FormData();
        fd.append('ajax_action', 'remove_image');
        fd.append('sku', sku);
        fd.append('filename', filename);
        fetch('import_csv.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if(data.success) loadProductList();
        });
    }
}

async function handleDeleteProduct(sku) {
    const ok = await kbConfirm(`Xóa vĩnh viễn sản phẩm ${sku}?`, "Cảnh báo");
    if (ok) {
        const fd = new FormData();
        fd.append('ajax_action', 'delete_product');
        fd.append('sku', sku);
        fetch('import_csv.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if (data.success) loadProductList();
        });
    }
}

async function bulkUpdate() {
    const products = await dbStore.get();
    if (!products) return;

    const formData = new FormData();
    formData.append('ajax_action', 'bulk_update');
    products.forEach((p, idx) => {
        formData.append(`products[${idx}][sku]`, p.sku);
        formData.append(`products[${idx}][name]`, p.name);
        formData.append(`products[${idx}][sale_price]`, String(p.sale_price).replace(/[^0-9]/g, ''));
        formData.append(`products[${idx}][price]`, String(p.price).replace(/[^0-9]/g, ''));
        formData.append(`products[${idx}][cat_code]`, p.cat_code || '');
        if (p.temp_images) {
            p.temp_images.forEach((base64, imgIdx) => {
                formData.append(`products[${idx}][temp_images][${imgIdx}]`, base64);
            });
        }
    });

    const btn = document.getElementById('btn-save-all');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ĐANG CHỐT...';
    btn.disabled = true;

    fetch('import_csv.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(async data => {
            btn.innerHTML = '<i class="fas fa-save"></i> CHỐT LƯU DATABASE';
            btn.disabled = false;
            if (data.success) {
                showToast('Đã lưu thành công!', 'success');
                await dbStore.clear();
                loadProductList();
            }
        });
}

function openLightbox(src) {
    const modal = document.getElementById('lightbox-modal');
    const img = document.getElementById('lightbox-img');
    img.src = src;
    modal.style.display = 'flex';
}

function showProductModal() { document.getElementById('product-modal').style.display = 'flex'; }
function hideProductModal() { document.getElementById('product-modal').style.display = 'none'; }

function editProduct(p) {
    document.getElementById('p_sku').value = p.sku;
    document.getElementById('p_name').value = p.name;
    document.getElementById('p_price').value = p.price;
    document.getElementById('p_sale').value = p.sale_price;
    document.getElementById('product-modal').style.display = 'flex';
}

function saveProduct(formData) {
    formData.append('ajax_action', 'save_product');
    fetch('import_csv.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
        if (data.success) { hideProductModal(); loadProductList(); }
    });
}

function allowDrop(e) { e.preventDefault(); }
function dropImageHandler(e, sku) {
    e.preventDefault();
    processFiles(e.dataTransfer.files, sku);
}

function triggerFileUpload(sku) {
    const input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    input.accept = 'image/*';
    input.onchange = (e) => processFiles(e.target.files, sku);
    input.click();
}

// --- HÀM NÉN ẢNH TẠI TRÌNH DUYỆT ---
async function compressImage(file, maxWidth = 800, quality = 0.7) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // Tính toán tỷ lệ để giảm kích thước nếu vượt quá maxWidth
                if (width > maxWidth) {
                    height = (maxWidth / width) * height;
                    width = maxWidth;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // Xuất ra Base64 với chất lượng nén mong muốn
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
        };
    });
}

async function processFiles(files, sku) {
    const data = await dbStore.get();
    if (!data) return;
    const idx = data.findIndex(p => p.sku === sku);
    if (idx === -1) return;
    if (!data[idx].temp_images) data[idx].temp_images = [];
    
    let currentTotal = (data[idx].image_1 ? data[idx].image_1.split(',').length : 0) + data[idx].temp_images.length;
    
    for (let file of Array.from(files)) {
        if (currentTotal >= 5) {
            showToast('Chỉ cho phép tối đa 5 ảnh!', 'info');
            break;
        }
        
        const originalSize = (file.size / 1024).toFixed(1); // KB
        
        // Nén ảnh
        const compressedBase64 = await compressImage(file);
        
        // Tính toán dung lượng Base64 (Chuỗi Base64 dài hơn thực tế ~33%)
        const compressedSize = ( (compressedBase64.length * 3/4) / 1024 ).toFixed(1); // KB
        const savedPercent = (100 - (compressedSize / originalSize * 100)).toFixed(0);

        data[idx].temp_images.push(compressedBase64);
        currentTotal++;

        addLog(`📸 Nén ảnh ${sku}: ${originalSize}KB → ${compressedSize}KB (Giảm ${savedPercent}%)`, 'success');
    }
    
    await dbStore.save(data);
    renderTable(data);
}

async function handleRemoveImageLocal(sku, imgIdx) {
    const data = await dbStore.get();
    if (!data) return;
    const pIdx = data.findIndex(p => p.sku === sku);
    if (pIdx === -1) return;
    let dbImages = data[pIdx].image_1 ? data[pIdx].image_1.split(',').filter(i => i.trim() !== '') : [];
    let tempImages = data[pIdx].temp_images || [];
    if (imgIdx < dbImages.length) {
        dbImages.splice(imgIdx, 1);
        data[pIdx].image_1 = dbImages.join(',');
    } else {
        tempImages.splice(imgIdx - dbImages.length, 1);
        data[pIdx].temp_images = tempImages;
    }
    await dbStore.save(data);
    renderTable(data);
}

function showImportModal() { document.getElementById('import-modal').style.display = 'flex'; }
function hideImportModal() { document.getElementById('import-modal').style.display = 'none'; }
function previewCSV(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const text = e.target.result;
        const delimiter = (text.split(';').length > text.split(',').length) ? ';' : ',';
        const rows = text.split('\n').slice(0, 6);
        let html = '<table class="data-table">';
        rows.forEach(row => {
            const cols = row.split(delimiter);
            html += '<tr>' + cols.map(c => `<td>${c}</td>`).join('') + '</tr>';
        });
        document.getElementById('csv-preview-table').innerHTML = html + '</table>';
        document.getElementById('csv-preview-wrap').style.display = 'block';
    };
    reader.readAsText(file);
}

function startImport(file) {
    const btn = document.getElementById('btn_confirm_import');
    btn.innerHTML = 'Đang phân tích...';
    const reader = new FileReader();
    reader.onload = async (e) => {
        const text = e.target.result;
        const delimiter = (text.split(';').length > text.split(',').length) ? ';' : ',';
        const lines = text.split('\n').filter(line => line.trim() !== '');
        const products = [];
        for (let i = 1; i < lines.length; i++) {
            const cols = lines[i].split(delimiter);
            if (cols.length < 3) continue;
            products.push({
                sku: cols[0].trim(), cat_code: cols[1].trim(), name: cols[2].trim(),
                price: cols[3].replace(/[^0-9]/g, ''), sale_price: cols[4].replace(/[^0-9]/g, ''),
                image_1: ''
            });
        }
        let combinedData = await dbStore.get() || [];
        products.forEach(newP => {
            const idx = combinedData.findIndex(oldP => oldP.sku === newP.sku);
            if (idx !== -1) combinedData[idx] = newP; else combinedData.push(newP);
        });
        await dbStore.save(combinedData);
        hideImportModal(); renderTable(combinedData);
    };
    reader.readAsText(file);
}

async function clearLocalData() {
    await dbStore.clear();
    loadProductList();
    showToast('Đã xóa bộ nhớ tạm và đồng bộ lại từ Database.', 'info');
}

async function deleteAllProducts() {
    const ok1 = await kbConfirm("CẢNH BÁO: Hành động này sẽ xóa VĨNH VIỄN toàn bộ sản phẩm trong Database. Bạn có chắc chắn không?", "XÁC NHẬN XÓA TẤT CẢ");
    if (!ok1) return;

    const ok2 = await kbConfirm("BẠN CÓ THẬT SỰ CHẮC CHẮN? Dữ liệu không thể khôi phục sau khi xóa!", "CẢNH BÁO CUỐI CÙNG");
    if (!ok2) return;

    const fd = new FormData();
    fd.append('ajax_action', 'delete_all_products');

    showToast('Đang thực hiện xóa sạch dữ liệu...', 'info');

    fetch('import_csv.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(async data => {
            if (data.success) {
                await dbStore.clear(); // Xóa luôn bộ nhớ tạm
                showToast('Đã xóa sạch toàn bộ sản phẩm!', 'success');
                loadProductList();
                addLog('Hệ thống đã thực hiện lệnh xóa sạch toàn bộ Database.', 'error');
            } else {
                showToast('Lỗi: ' + data.error, 'error');
            }
        });
}