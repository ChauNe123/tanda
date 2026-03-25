// assets/js/admin_import.js
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
                <input type="text" class="preview-input" id="rename_${index}" value="${file.name}" placeholder="Nhập tên mới...">
                <button type="button" class="btn-remove" onclick="removeImage(${index})" title="Xóa ảnh này">❌ Xóa</button>
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
    btnAddMore.addEventListener('click', function() {
        imageInput.click(); // Mở lại cửa sổ chọn file
    });
}

if (btnConfirm) {
    btnConfirm.addEventListener('click', function() {
        if(selectedFiles.length === 0) return;

        document.getElementById('image-upload-section').classList.add('loading');
        btnConfirm.innerHTML = '⏳ ĐANG TẢI LÊN...';

        let formData = new FormData();
        formData.append('ajax_action', 'upload_images');

        selectedFiles.forEach((file, index) => {
            let newFileName = document.getElementById('rename_' + index).value;
            formData.append('images[]', file);
            formData.append('new_names[]', newFileName);
        });

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('image-upload-section').classList.remove('loading');
            btnConfirm.innerHTML = '🚀 XÁC NHẬN TẢI LÊN';
            
            if(data.success > 0) {
                msgBox.innerHTML = `<div class='alert success'>🖼️ ✅ Đã lưu thành công ${data.success} hình ảnh với tên mới!</div>`;
                selectedFiles = []; 
                renderPreview(); 
            } else {
                msgBox.innerHTML = `<div class='alert error'>❌ Lỗi tải ảnh! Kiểm tra lại định dạng file.</div>`;
            }
        })
        .catch(error => {
            alert('Có lỗi mạng xảy ra khi tải ảnh lên!');
            document.getElementById('image-upload-section').classList.remove('loading');
            btnConfirm.innerHTML = '🚀 XÁC NHẬN TẢI LÊN';
        });
    });
}

const csvProducts = document.getElementById('csv_products');
if (csvProducts) {
    csvProducts.addEventListener('change', function(e) {
        var name = e.target.files[0] ? e.target.files[0].name : 'Chưa chọn file';
        document.getElementById('file-name-products').innerHTML = '📄 Đã chọn: ' + name;
    });
}

const csvBanners = document.getElementById('csv_banners');
if (csvBanners) {
    csvBanners.addEventListener('change', function(e) {
        var name = e.target.files[0] ? e.target.files[0].name : 'Chưa chọn file';
        document.getElementById('file-name-banners').innerHTML = '🎨 Đã chọn: ' + name;
    });
}

// =========================================================
// MINI EXCEL: ĐỌC, HIỂN THỊ VÀ CHỈNH SỬA FILE CSV KHO HÀNG
// =========================================================
let parsedCSVData = []; // Mảng chứa dữ liệu các ô Excel

const csvInput = document.getElementById('csv_products_input');
const csvPreviewBox = document.getElementById('csv-preview-box');
const csvTableWrapper = document.getElementById('csv-table-wrapper');
const btnConfirmCSV = document.getElementById('btn_confirm_csv');

// Thuật toán bóc tách file CSV chuẩn quốc tế (Xử lý được cả dấu phẩy nằm trong ngoặc kép)
function CSVToArray(strData) {
    let objPattern = new RegExp(("(\\,|\\r?\\n|\\r|^)(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|([^\"\\,\\r\\n]*))"), "gi");
    let arrData = [[]]; let arrMatches = null;
    while (arrMatches = objPattern.exec(strData)) {
        let strMatchedDelimiter = arrMatches[1];
        if (strMatchedDelimiter.length && strMatchedDelimiter !== ",") { arrData.push([]); }
        let strMatchedValue;
        if (arrMatches[2]) { strMatchedValue = arrMatches[2].replace(new RegExp("\"\"", "g"), "\""); }
        else { strMatchedValue = arrMatches[3]; }
        arrData[arrData.length - 1].push(strMatchedValue);
    }
    return arrData;
}

// Thuật toán đóng gói mảng thành file CSV ngược lại để gửi đi
function ArrayToCSV(arr) {
    return arr.map(row => 
        row.map(String).map(v => v.replaceAll('"', '""')).map(v => `"${v}"`).join(',')
    ).join('\r\n');
}

// Hàm vẽ cái bảng HTML
function renderCSVTable() {
    if(parsedCSVData.length < 2) return; // Nếu file rỗng thì dẹp

    let html = '<table class="csv-table">';
    parsedCSVData.forEach((row, rIdx) => {
        // Bỏ qua dòng trống rác cuối file
        if (row.length === 1 && row[0] === "") return; 

        html += '<tr>';
        row.forEach((col, cIdx) => {
            if (rIdx === 0) {
                html += `<th>${col}</th>`; // Vẽ Dòng Tiêu Đề màu xanh
            } else {
                // Vẽ các ô input để khách hàng gõ chữ
                let safeVal = col ? col.replace(/"/g, '&quot;') : '';
                html += `<td><input type="text" value="${safeVal}" onchange="updateCSVData(${rIdx}, ${cIdx}, this.value)"></td>`;
            }
        });
        
        // Thêm cột Xóa Dòng
        if (rIdx === 0) {
            html += `<th>Hành động</th>`;
        } else {
            html += `<td><button type="button" class="btn-remove-row" onclick="removeCSVRow(${rIdx})" title="Xóa nguyên dòng này">Xóa</button></td>`;
        }
        html += '</tr>';
    });
    html += '</table>';
    csvTableWrapper.innerHTML = html;
    csvPreviewBox.style.display = 'block';
}

// Lưu dữ liệu khi Khách Hàng gõ vào ô
window.updateCSVData = function(rowIdx, colIdx, val) {
    parsedCSVData[rowIdx][colIdx] = val;
};

// Khách hàng bấm Xóa 1 dòng sản phẩm
window.removeCSVRow = function(rowIdx) {
    parsedCSVData.splice(rowIdx, 1);
    renderCSVTable(); // Xóa xong vẽ lại bảng
};

// KHI CHỌN FILE CSV TỪ MÁY TÍNH
if (csvInput) {
    csvInput.addEventListener('change', function(e) {
        let file = e.target.files[0];
        if (!file) return;

        document.getElementById('file-name-products').innerHTML = '📄 Đang mở: ' + file.name;

        // Dùng FileReader để đọc ruột file
        let reader = new FileReader();
        reader.onload = function(event) {
            let csvText = event.target.result;
            parsedCSVData = CSVToArray(csvText); // Bóc tách text thành Mảng
            renderCSVTable(); // Hiển thị ra bảng
        };
        // Nhớ đọc bằng UTF-8 để không bị lỗi tiếng Việt
        reader.readAsText(file, 'UTF-8'); 
    });
}

// KHI BẤM NÚT "LƯU VÀO DATABASE"
if (btnConfirmCSV) {
    btnConfirmCSV.addEventListener('click', function() {
        if (parsedCSVData.length < 2) return;

        document.getElementById('product-upload-section').classList.add('loading');
        btnConfirmCSV.innerHTML = '⏳ ĐANG LƯU DỮ LIỆU...';

        // Đóng gói mảng đã chỉnh sửa thành cục CSV mới
        let newCSVString = ArrayToCSV(parsedCSVData);
        // Tạo một file ảo từ cục chữ đó
        let blob = new Blob([newCSVString], { type: 'text/csv;charset=utf-8;' });
        
        let formData = new FormData();
        formData.append('ajax_action', 'upload_products');
        formData.append('csv_products', blob, 'edited_products.csv'); // Quăng file ảo vô gói hàng

        // Gửi qua PHP xử lý như bình thường
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('product-upload-section').classList.remove('loading');
            btnConfirmCSV.innerHTML = '🚀 LƯU VÀO DATABASE';
            
            if(data.success > 0) {
                document.getElementById('msg-box').innerHTML = `<div class='alert success'>📦 ✅ Đã lưu thành công ${data.success} SẢN PHẨM vào kho!</div>`;
                csvPreviewBox.style.display = 'none'; // Xong thì giấu bảng đi
                document.getElementById('file-name-products').innerHTML = '📁 Chạm để chọn file Kho_Hang.csv...';
                csvInput.value = ''; 
                parsedCSVData = [];
            } else {
                document.getElementById('msg-box').innerHTML = `<div class='alert error'>❌ Có lỗi xảy ra lúc lưu dữ liệu!</div>`;
            }
        })
        .catch(error => {
            alert('Lỗi mạng! Vui lòng thử lại.');
            document.getElementById('product-upload-section').classList.remove('loading');
            btnConfirmCSV.innerHTML = '🚀 LƯU VÀO DATABASE';
        });
    });
}