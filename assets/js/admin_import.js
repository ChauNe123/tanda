// assets/js/admin_import.js

// =========================================================
// 1. CHỨC NĂNG UP HÌNH ẢNH CÓ PREVIEW & CHỌN THƯ MỤC
// =========================================================
let selectedFiles = [];
const imageInput = document.getElementById('image_files_input');
const previewBox = document.getElementById('preview-box');
const previewList = document.getElementById('preview-list');
const btnConfirm = document.getElementById('btn_confirm_upload');
const btnAddMore = document.getElementById('btn_add_more');
const msgBox = document.getElementById('msg-box');

function renderPreview() {
    if (selectedFiles.length === 0) {
        previewBox.style.display = 'none';
        document.getElementById('file-name-images').innerHTML = '📁 Chạm để chọn nhiều ảnh cùng lúc...';
        imageInput.value = '';
        return;
    }
    document.getElementById('file-name-images').innerHTML = '🖼️ Đang đợi duyệt ' + selectedFiles.length + ' ảnh...';
    previewList.innerHTML = '';
    selectedFiles.forEach((file, index) => {
        const imgURL = URL.createObjectURL(file);
        const itemHTML = `
            <div class="preview-item">
                <img src="${imgURL}" class="preview-img" alt="preview">
                <input type="text" class="preview-input" id="rename_${index}" value="${file.name}">
                <button type="button" class="btn-remove" onclick="removeImage(${index})">❌ Xóa</button>
            </div>
        `;
        previewList.insertAdjacentHTML('beforeend', itemHTML);
    });
    previewBox.style.display = 'block';
}

window.removeImage = function(index) {
    selectedFiles.splice(index, 1);
    renderPreview();
};

if (imageInput) {
    imageInput.addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files);
        selectedFiles = selectedFiles.concat(newFiles);
        renderPreview();
    });
}

if (btnAddMore) {
    btnAddMore.addEventListener('click', () => imageInput.click());
}

function uploadImagesRequest() {
    if (selectedFiles.length === 0) {
        return Promise.resolve({ success: 0, error: 0, skipped: true });
    }

    const formData = new FormData();
    formData.append('ajax_action', 'upload_images');

    const selectedFolder = document.querySelector('input[name="target_folder"]:checked').value;
    formData.append('target_folder', selectedFolder);

    selectedFiles.forEach((file, index) => {
        const input = document.getElementById('rename_' + index);
        const newFileName = input ? input.value : file.name;
        formData.append('images[]', file);
        formData.append('new_names[]', newFileName);
    });

    return fetch(window.location.href, { method: 'POST', body: formData }).then(res => res.json());
}

if (btnConfirm) {
    btnConfirm.addEventListener('click', function() {
        if (selectedFiles.length === 0) return;
        document.getElementById('image-upload-section').classList.add('loading');
        btnConfirm.innerHTML = '⏳ ĐANG TẢI LÊN...';

        uploadImagesRequest().then(data => {
            document.getElementById('image-upload-section').classList.remove('loading');
            btnConfirm.innerHTML = '🚀 XÁC NHẬN TẢI LÊN';
            const selectedFolder = document.querySelector('input[name="target_folder"]:checked').value;

            if (data.success > 0) {
                msgBox.innerHTML = `<div class='alert success'>🖼️ ✅ Đã lưu ${data.success} hình ảnh vào thư mục ${selectedFolder}!</div>`;
                selectedFiles = [];
                renderPreview();
            } else {
                msgBox.innerHTML = `<div class='alert error'>❌ Lỗi tải ảnh!</div>`;
            }
        });
    });
}

// =========================================================
// 2. MINI EXCEL: KHO HÀNG
// =========================================================
let parsedCSVData = [];
const csvInput = document.getElementById('csv_products_input');
const csvPreviewBox = document.getElementById('csv-preview-box');
const csvTableWrapper = document.getElementById('csv-table-wrapper');
const btnConfirmCSV = document.getElementById('btn_confirm_csv');
const btnSyncAll = document.getElementById('btn_sync_all');

function CSVToArray(strData) {
    const objPattern = new RegExp(("(\\,|\\r?\\n|\\r|^)(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|([^\"\\,\\r\\n]*))"), "gi");
    const arrData = [[]];
    let arrMatches = null;
    while (arrMatches = objPattern.exec(strData)) {
        const strMatchedDelimiter = arrMatches[1];
        if (strMatchedDelimiter.length && strMatchedDelimiter !== ",") {
            arrData.push([]);
        }
        const strMatchedValue = arrMatches[2] ? arrMatches[2].replace(new RegExp("\"\"", "g"), "\"") : arrMatches[3];
        arrData[arrData.length - 1].push(strMatchedValue);
    }
    return arrData;
}

function ArrayToCSV(arr) {
    return arr.map(row => row.map(String).map(v => v.replaceAll('"', '""')).map(v => `"${v}"`).join(',')).join('\r\n');
}

function saveProductsRequest() {
    if (parsedCSVData.length < 2) {
        return Promise.resolve({ success: 0, error: 1, message: 'CSV chưa hợp lệ' });
    }

    const newCSVString = ArrayToCSV(parsedCSVData);
    const blob = new Blob([newCSVString], { type: 'text/csv;charset=utf-8;' });
    const formData = new FormData();
    formData.append('ajax_action', 'upload_products');
    formData.append('csv_products', blob, 'edited.csv');

    return fetch(window.location.href, { method: 'POST', body: formData }).then(res => res.json());
}

function renderCSVTable() {
    if (parsedCSVData.length < 2) return;
    const header = parsedCSVData[0] || [];
    const imageColIdx = header.findIndex(c => /^(image|image_file|anh)$/i.test(String(c || '').trim()));

    let html = '<table class="csv-table">';
    parsedCSVData.forEach((row, rIdx) => {
        if (row.length === 1 && row[0] === "") return;
        html += '<tr>';

        row.forEach((col, cIdx) => {
            if (rIdx === 0) {
                if (cIdx === imageColIdx) html += '<th>Image (Tự động)</th>';
                else if (cIdx === frameColIdx) html += '<th>Frame (Tự động)</th>';
                else html += `<th>${col}</th>`;
            } else {
                const skuVal = row[0] ? row[0].replace(/"/g, '') : 'SKU';
                if (cIdx === imageColIdx) {
                    html += `<td class="drop-zone-cell" ondragover="window.dragOverHandler(event)" ondragleave="window.dragLeaveHandler(event)" ondrop="window.dropImageHandler(event, '${skuVal}', this)" style="background:#e8f5e9; text-align:center;">
                                <div class="drop-zone-content" id="img-preview-${skuVal}">
                                    <div style="font-size:11px; color:#155724;"><b>${skuVal}.jpg</b><br>Kéo thả ảnh (tối đa 5)</div>
                                </div>
                             </td>`;
                } else {
                    html += `<td><input type="text" value="${col ? col.replace(/"/g, '&quot;') : ''}" onchange="updateCSVData(${rIdx}, ${cIdx}, this.value)"></td>`;
                }
            }
        });

        if (rIdx === 0 && imageColIdx === -1) html += '<th>Image (Tự động)</th>';
        if (rIdx > 0 && imageColIdx === -1) {
            const skuVal = row[0] ? row[0].replace(/"/g, '') : 'SKU';
            html += `<td class="drop-zone-cell" ondragover="window.dragOverHandler(event)" ondragleave="window.dragLeaveHandler(event)" ondrop="window.dropImageHandler(event, '${skuVal}', this)" style="background:#e8f5e9; text-align:center;">
                        <div class="drop-zone-content" id="img-preview-${skuVal}">
                            <div style="font-size:11px; color:#155724;"><b>${skuVal}.jpg</b><br>Kéo thả ảnh (tối đa 5)</div>
                        </div>
                     </td>`;
        }

        if (rIdx === 0) html += `<th>Hành động</th>`;
        else html += `<td><button type="button" class="btn-remove-row" onclick="removeCSVRow(${rIdx})">Xóa</button></td>`;
        html += '</tr>';
    });
    html += '</table>';
    csvTableWrapper.innerHTML = html;
    csvPreviewBox.style.display = 'block';
}

window.updateCSVData = function(rowIdx, colIdx, val) {
    parsedCSVData[rowIdx][colIdx] = val;
};

window.removeCSVRow = function(rowIdx) {
    parsedCSVData.splice(rowIdx, 1);
    renderCSVTable();
};

window.dragOverHandler = function(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.add('dragover');
};

window.dragLeaveHandler = function(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('dragover');
};

window.dropImageHandler = function(ev, sku, cellElement) {
    ev.preventDefault();
    cellElement.classList.remove('dragover');
    
    if (ev.dataTransfer.items) {
        const files = Array.from(ev.dataTransfer.files).filter(f => f.type.startsWith('image/'));
        if (files.length === 0) return;
        
        // Show loading state
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'uploading-overlay';
        loadingDiv.innerText = 'Đang tải...';
        cellElement.appendChild(loadingDiv);

        const formData = new FormData();
        formData.append('ajax_action', 'upload_images');
        formData.append('target_folder', 'uploads');

        files.forEach((file, index) => {
            if (index >= 5) return; // Max 5 images
            
            let ext = file.name.split('.').pop().toLowerCase();
            let newName = (index === 0 ? `${sku}.${ext}` : `${sku}-${index + 1}.${ext}`);
            
            formData.append('images[]', file);
            formData.append('new_names[]', newName);
        });

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            cellElement.removeChild(loadingDiv);
            if (data.success > 0) {
                // Update UI to show thumbnail of the first dropped image
                const contentDiv = cellElement.querySelector('.drop-zone-content');
                const imgURL = URL.createObjectURL(files[0]);
                let ext = files[0].name.split('.').pop().toLowerCase();
                let mainName = `${sku}.${ext}`;
                contentDiv.innerHTML = `<div style="font-size:11px; color:#155724;"><b>${mainName}</b> <span style="color:red">(${data.success} ảnh)</span></div><img src="${imgURL}" class="thumb">`;
                
                // Show a brief success message in the main message box
                const msgBox = document.getElementById('msg-box');
                if (msgBox) {
                    msgBox.innerHTML = `<div class='alert success' style='padding: 8px; margin-bottom: 10px; font-size: 14px;'>✅ Đã nạp ngay ${data.success} ảnh cho SKU: <b>${sku}</b></div>`;
                    setTimeout(() => msgBox.innerHTML = '', 3000);
                }
            } else {
                alert('Tải ảnh thất bại!');
            }
        })
        .catch(err => {
            console.error(err);
            cellElement.removeChild(loadingDiv);
            alert('Có lỗi xảy ra khi tải ảnh.');
        });
    }
};

if (csvInput) {
    csvInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        document.getElementById('file-name-products').innerHTML = '📄 Đang mở: ' + file.name;
        const reader = new FileReader();
        reader.onload = function(event) {
            parsedCSVData = CSVToArray(event.target.result);
            renderCSVTable();
        };
        reader.readAsText(file, 'UTF-8');
    });
}

if (btnConfirmCSV) {
    btnConfirmCSV.addEventListener('click', function() {
        if (parsedCSVData.length < 2) return;
        document.getElementById('product-upload-section').classList.add('loading');
        btnConfirmCSV.innerHTML = '⏳ ĐANG LƯU DỮ LIỆU...';

        saveProductsRequest().then(data => {
            document.getElementById('product-upload-section').classList.remove('loading');
            btnConfirmCSV.innerHTML = '🚀 LƯU VÀO DATABASE';
            if (data.success > 0) {
                msgBox.innerHTML = `<div class='alert success'>📦 ✅ Đã lưu ${data.success} SẢN PHẨM vào kho!</div>`;
                csvPreviewBox.style.display = 'none';
                document.getElementById('file-name-products').innerHTML = '📁 Chạm để chọn file Kho_Hang.csv...';
                csvInput.value = '';
                parsedCSVData = [];
            }
        });
    });
}

if (btnSyncAll) {
    btnSyncAll.addEventListener('click', async function() {
        if (parsedCSVData.length < 2) {
            msgBox.innerHTML = `<div class='alert error'>❌ Bạn chưa chọn CSV sản phẩm.</div>`;
            return;
        }

        const productSection = document.getElementById('product-upload-section');
        productSection.classList.add('loading');
        btnSyncAll.disabled = true;
        btnSyncAll.innerHTML = '⏳ ĐANG ĐỒNG BỘ...';

        try {
            const imgResult = await uploadImagesRequest();
            const prodResult = await saveProductsRequest();

            if (prodResult.success > 0) {
                msgBox.innerHTML = `<div class='alert success'>✅ Đồng bộ thành công! Ảnh: ${imgResult.success || 0} | Sản phẩm: ${prodResult.success}</div>`;
                selectedFiles = [];
                renderPreview();
                csvPreviewBox.style.display = 'none';
                document.getElementById('file-name-products').innerHTML = '📁 Chạm để chọn file Kho_Hang.csv...';
                csvInput.value = '';
                parsedCSVData = [];
            } else {
                msgBox.innerHTML = `<div class='alert error'>❌ Lưu sản phẩm thất bại. Kiểm tra lại CSV.</div>`;
            }
        } catch (error) {
            console.error(error);
            msgBox.innerHTML = `<div class='alert error'>❌ Đồng bộ thất bại: ${error.message}</div>`;
        } finally {
            productSection.classList.remove('loading');
            btnSyncAll.disabled = false;
            btnSyncAll.innerHTML = '⚡ ĐỒNG BỘ TẤT CẢ (ẢNH + CSV)';
        }
    });
}

// =========================================================
// 3. XỬ LÝ CHỌN FILE, HỦY FILE VÀ XEM THỬ REALISTIC
// =========================================================
const csvBannersInput = document.getElementById('csv_banners_input');
const designActionBox = document.getElementById('design-action-box');
const btnPreviewDesign = document.getElementById('btn_preview_design');
const btnCancelDesign = document.getElementById('btn_cancel_design');
const btnConfirmDesign = document.getElementById('btn_confirm_design');
const formCsvBanners = document.getElementById('form-csv-banners');

const realPreviewModal = document.getElementById('realPreviewModal');
const btnClosePreview = document.getElementById('btn_close_preview');

let designData = [];

if (csvBannersInput) {
    csvBannersInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        document.getElementById('file-name-banners').innerHTML = '📄 Đã chọn file: ' + file.name;

        const reader = new FileReader();
        reader.onload = function(event) {
            designData = CSVToArray(event.target.result);
            designActionBox.style.display = 'block';
        };
        reader.readAsText(file, 'UTF-8');
    });
}

if (btnCancelDesign) {
    btnCancelDesign.addEventListener('click', function() {
        csvBannersInput.value = '';
        document.getElementById('file-name-banners').innerHTML = '📁 Chạm để chọn file Trang_Tri.csv...';
        designActionBox.style.display = 'none';
        designData = [];
    });
}

if (btnPreviewDesign) {
    btnPreviewDesign.addEventListener('click', function() {
        if (designData.length < 2) {
            alert('File CSV không hợp lệ hoặc trống!');
            return;
        }

        const positions = ['BANNER-CHINH', 'BANNER-PHU-1', 'BANNER-PHU-2', 'BANNER-PHU-3'];

        positions.forEach(pos => {
            const wrap = document.getElementById('prev-' + pos);
            if (wrap) {
                wrap.innerHTML = `<img src="https://via.placeholder.com/1200x350/003028/ffffff?text=${pos}+CHƯA+KÊU" style="width:100%; display:block; object-fit:cover;">`;
            }
        });

        designData.forEach((row, rIdx) => {
            if (rIdx === 0 || (row.length === 1 && row[0] === '')) return;
            const bannerCode = (row[0] ?? '').trim();
            const imageFile = (row[1] ?? '').trim();
            const status = (row[3] ?? '1').trim();

            if (positions.includes(bannerCode) && imageFile !== '' && status === '1') {
                const wrap = document.getElementById('prev-' + bannerCode);
                if (wrap) {
                    wrap.innerHTML = `<img src="../banners/${imageFile}?v=${new Date().getTime()}" alt="${bannerCode}" style="width:100%; display:block; object-fit:cover; border-radius: 8px;">`;
                }
            }
        });

        realPreviewModal.style.display = 'flex';
    });
}

if (btnClosePreview) {
    btnClosePreview.addEventListener('click', () => {
        realPreviewModal.style.display = 'none';
    });
}

if (btnConfirmDesign) {
    btnConfirmDesign.addEventListener('click', () => formCsvBanners.submit());
}