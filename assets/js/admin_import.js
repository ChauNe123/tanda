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
        let imgURL = URL.createObjectURL(file); 
        let itemHTML = `
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

if (btnConfirm) {
    btnConfirm.addEventListener('click', function() {
        if(selectedFiles.length === 0) return;
        document.getElementById('image-upload-section').classList.add('loading');
        btnConfirm.innerHTML = '⏳ ĐANG TẢI LÊN...';

        let formData = new FormData();
        formData.append('ajax_action', 'upload_images');

        // BẮT LẤY LỰA CHỌN THƯ MỤC CỦA KHÁCH HÀNG
        let selectedFolder = document.querySelector('input[name="target_folder"]:checked').value;
        formData.append('target_folder', selectedFolder);

        selectedFiles.forEach((file, index) => {
            let newFileName = document.getElementById('rename_' + index).value;
            formData.append('images[]', file);
            formData.append('new_names[]', newFileName);
        });

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            document.getElementById('image-upload-section').classList.remove('loading');
            btnConfirm.innerHTML = '🚀 XÁC NHẬN TẢI LÊN';
            if(data.success > 0) {
                msgBox.innerHTML = `<div class='alert success'>🖼️ ✅ Đã lưu ${data.success} hình ảnh vào thư mục ${selectedFolder}!</div>`;
                selectedFiles = []; renderPreview(); 
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

function CSVToArray(strData) {
    let objPattern = new RegExp(("(\\,|\\r?\\n|\\r|^)(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|([^\"\\,\\r\\n]*))"), "gi");
    let arrData = [[]]; let arrMatches = null;
    while (arrMatches = objPattern.exec(strData)) {
        let strMatchedDelimiter = arrMatches[1];
        if (strMatchedDelimiter.length && strMatchedDelimiter !== ",") { arrData.push([]); }
        let strMatchedValue = arrMatches[2] ? arrMatches[2].replace(new RegExp("\"\"", "g"), "\"") : arrMatches[3];
        arrData[arrData.length - 1].push(strMatchedValue);
    }
    return arrData;
}

function ArrayToCSV(arr) {
    return arr.map(row => row.map(String).map(v => v.replaceAll('"', '""')).map(v => `"${v}"`).join(',')).join('\r\n');
}

function renderCSVTable() {
    if(parsedCSVData.length < 2) return;
    let html = '<table class="csv-table">';
    parsedCSVData.forEach((row, rIdx) => {
        if (row.length === 1 && row[0] === "") return; 
        html += '<tr>';
        row.forEach((col, cIdx) => {
            if (rIdx === 0) { html += `<th>${col}</th>`; } 
            else { html += `<td><input type="text" value="${col ? col.replace(/"/g, '&quot;') : ''}" onchange="updateCSVData(${rIdx}, ${cIdx}, this.value)"></td>`; }
        });
        if (rIdx === 0) { html += `<th>Hành động</th>`; } 
        else { html += `<td><button type="button" class="btn-remove-row" onclick="removeCSVRow(${rIdx})">Xóa</button></td>`; }
        html += '</tr>';
    });
    html += '</table>';
    csvTableWrapper.innerHTML = html;
    csvPreviewBox.style.display = 'block';
}

window.updateCSVData = function(rowIdx, colIdx, val) { parsedCSVData[rowIdx][colIdx] = val; };
window.removeCSVRow = function(rowIdx) { parsedCSVData.splice(rowIdx, 1); renderCSVTable(); };

if (csvInput) {
    csvInput.addEventListener('change', function(e) {
        let file = e.target.files[0];
        if (!file) return;
        document.getElementById('file-name-products').innerHTML = '📄 Đang mở: ' + file.name;
        let reader = new FileReader();
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
        let newCSVString = ArrayToCSV(parsedCSVData);
        let blob = new Blob([newCSVString], { type: 'text/csv;charset=utf-8;' });
        let formData = new FormData();
        formData.append('ajax_action', 'upload_products');
        formData.append('csv_products', blob, 'edited.csv'); 

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            document.getElementById('product-upload-section').classList.remove('loading');
            btnConfirmCSV.innerHTML = '🚀 LƯU VÀO DATABASE';
            if(data.success > 0) {
                msgBox.innerHTML = `<div class='alert success'>📦 ✅ Đã lưu ${data.success} SẢN PHẨM vào kho!</div>`;
                csvPreviewBox.style.display = 'none'; 
                document.getElementById('file-name-products').innerHTML = '📁 Chạm để chọn file Kho_Hang.csv...';
                csvInput.value = ''; parsedCSVData = [];
            }
        });
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

// KHI CHỌN FILE CSV
if (csvBannersInput) {
    csvBannersInput.addEventListener('change', function(e) {
        let file = e.target.files[0];
        if (!file) return;
        document.getElementById('file-name-banners').innerHTML = '📄 Đã chọn file: ' + file.name;
        
        let reader = new FileReader();
        reader.onload = function(event) {
            designData = CSVToArray(event.target.result); 
            // Hiện các nút thao tác lên
            designActionBox.style.display = 'block';
        };
        reader.readAsText(file, 'UTF-8');
    });
}

// KHI BẤM NÚT HỦY BỎ (XÓA FILE)
if (btnCancelDesign) {
    btnCancelDesign.addEventListener('click', function() {
        csvBannersInput.value = ''; // Xóa dữ liệu file đã chọn
        document.getElementById('file-name-banners').innerHTML = '📁 Chạm để chọn file Trang_Tri.csv...';
        designActionBox.style.display = 'none'; // Giấu nút đi
        designData = [];
    });
}

// KHI BẤM NÚT XEM THỬ GIAO DIỆN
if (btnPreviewDesign) {
    btnPreviewDesign.addEventListener('click', function() {
        if(designData.length < 2) {
            alert("File CSV không hợp lệ hoặc trống!"); return;
        }
        
        const positions = ['BANNER-CHINH', 'BANNER-PHU-1', 'BANNER-PHU-2', 'BANNER-PHU-3'];
        
        // Reset hình cũ thành ảnh mồi
        positions.forEach(pos => {
            const wrap = document.getElementById('prev-' + pos);
            if(wrap) { 
                wrap.innerHTML = `<img src="https://via.placeholder.com/1200x350/003028/ffffff?text=${pos}+CHƯA+KÊU" style="width:100%; display:block; object-fit:cover;">`; 
            }
        });

        // Bơm hình mới từ CSV vào Model
        designData.forEach((row, rIdx) => {
            if (rIdx === 0 || (row.length === 1 && row[0] === "")) return;
            let bannerCode = (row[0] ?? '').trim(); 
            let imageFile  = (row[1] ?? '').trim();
            let status     = (row[3] ?? '1').trim();

            if (positions.includes(bannerCode) && imageFile !== '' && status === '1') {
                const wrap = document.getElementById('prev-' + bannerCode);
                if (wrap) {
                    wrap.innerHTML = `<img src="../banners/${imageFile}?v=${new Date().getTime()}" alt="${bannerCode}" style="width:100%; display:block; object-fit:cover; border-radius: 8px;">`;
                }
            }
        });

        // Mở popup toàn màn hình lên
        realPreviewModal.style.display = 'flex';
    });
}

// KHI BẤM ĐÓNG XEM THỬ
if (btnClosePreview) {
    btnClosePreview.addEventListener('click', () => {
        realPreviewModal.style.display = 'none';
    });
}

// KHI BẤM XÁC NHẬN TẢI LÊN
if (btnConfirmDesign) {
    btnConfirmDesign.addEventListener('click', () => formCsvBanners.submit());
}