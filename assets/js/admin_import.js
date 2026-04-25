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
// 2.IMPORT ZIP + CSV BATCH PROCESS
// =========================================================
const zipInput = document.getElementById('zip_file');
const csvInput = document.getElementById('csv_file');
const btnStart = document.getElementById('btn_start');
const btnCancel = document.getElementById('btn_cancel');
const progressBar = document.getElementById('progress-bar');
const statusLine = document.getElementById('status-line');
const logBox = document.getElementById('logbox');
const logWrap = document.getElementById('log');

const BATCH_SIZE = 10;
let abortImport = false;

function setStatus(text) {
    if (statusLine) statusLine.textContent = text;
}

function updateProgress(percent) {
    if (!progressBar) return;
    const safePercent = Math.max(0, Math.min(100, Math.round(percent)));
    progressBar.style.width = safePercent + '%';
    progressBar.textContent = safePercent + '%';
}

function logMessage(text) {
    if (!logBox || !logWrap) return;
    logWrap.style.display = 'block';
    const now = new Date().toLocaleTimeString();
    logBox.textContent += `[${now}] ${text}\n`;
    logBox.scrollTop = logBox.scrollHeight;
}

async function ajaxPost(formData) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData,
        });
        return await response.json();
    } catch (error) {
        return { success: 0, error: error.message || 'Network error' };
    }
}

async function validateFiles(zipFile, csvFile) {
    const formData = new FormData();
    formData.append('ajax_action', 'validate_zip_csv');
    formData.append('zip_file', zipFile);
    formData.append('csv_file', csvFile);
    return ajaxPost(formData);
}

async function processBatch(token, start, batchSize) {
    const formData = new FormData();
    formData.append('ajax_action', 'process_batch');
    formData.append('token', token);
    formData.append('start', start);
    formData.append('batch', batchSize);
    return ajaxPost(formData);
}

async function cleanupTemp(token) {
    const formData = new FormData();
    formData.append('ajax_action', 'cleanup_temp');
    formData.append('token', token);
    return ajaxPost(formData);
}

function resetProgress() {
    updateProgress(0);
    setStatus('Chưa bắt đầu');
    if (logBox) logBox.textContent = '';
}

async function startImport() {
    if (!zipInput || !csvInput || !zipInput.files[0] || !csvInput.files[0]) {
        alert('Vui lòng chọn file ZIP và file CSV trước khi bắt đầu.');
        return;
    }

    abortImport = false;
    if (btnStart) btnStart.disabled = true;
    if (btnCancel) btnCancel.style.display = 'inline-block';
    resetProgress();

    setStatus('Đang kiểm tra file ZIP và CSV...');
    logMessage('Bắt đầu kiểm tra file.');

    const validation = await validateFiles(zipInput.files[0], csvInput.files[0]);
    if (!validation.success) {
        const message = validation.error === 'missing_images' && validation.missing
            ? `Thiếu ảnh theo CSV:\n${validation.missing.slice(0, 20).map(item => `- Row ${item.row}: ${item.image}`).join('\n')}`
            : `Validation lỗi: ${validation.error || 'Không xác định'}`;
        alert(message);
        setStatus('Validation lỗi');
        logMessage(message);
        if (btnStart) btnStart.disabled = false;
        if (btnCancel) btnCancel.style.display = 'none';
        return;
    }

    const total = Number(validation.total || 0);
    const token = validation.token;
    if (!token || total <= 0) {
        alert('Validation không trả về token hoặc tổng số sản phẩm không hợp lệ.');
        setStatus('Validation thất bại');
        logMessage('Validation trả về dữ liệu không hợp lệ.');
        if (btnStart) btnStart.disabled = false;
        if (btnCancel) btnCancel.style.display = 'none';
        return;
    }

    setStatus(`Validation OK. Tổng sản phẩm: ${total}`);
    logMessage(`Validation OK. Tổng sản phẩm: ${total}. Token: ${token}`);

    let processed = 0;
    updateProgress(0);

    while (processed < total && !abortImport) {
        const nextEnd = Math.min(processed + BATCH_SIZE, total);
        setStatus(`Đang nạp ${processed + 1} - ${nextEnd} / ${total}`);
        logMessage(`Gửi batch ${processed} -> ${nextEnd}`);

        const batchResult = await processBatch(token, processed, BATCH_SIZE);
        if (!batchResult.success) {
            alert(`Lỗi khi nạp batch: ${batchResult.error || 'Không xác định'}`);
            setStatus('Lỗi khi nạp batch');
            logMessage(`Batch lỗi: ${batchResult.error || JSON.stringify(batchResult)}`);
            break;
        }

        const got = Number(batchResult.processed || 0);
        if (got <= 0) {
            if (processed >= total) break;
            alert('Batch trả về 0 sản phẩm đã xử lý. Dừng để tránh vòng lặp vô hạn.');
            setStatus('Dừng do batch 0');
            logMessage('Batch trả về 0 sản phẩm đã xử lý.');
            break;
        }

        processed += got;
        const percent = (total > 0) ? (processed / total) * 100 : 100;
        updateProgress(percent);
        logMessage(`Hoàn thành batch, processed=${processed}.`);

        if (processed >= total) break;
    }

    if (abortImport) {
        setStatus('Tiến trình đã bị hủy');
        logMessage('Người dùng hủy tiến trình.');
    } else if (processed >= total) {
        setStatus('Nạp dữ liệu xong. Đang dọn dẹp...');
        logMessage('Hoàn tất nạp dữ liệu. Dọn dẹp dữ liệu tạm.');
        const cleanup = await cleanupTemp(token);
        if (cleanup.success) {
            updateProgress(100);
            setStatus('Đã hoàn tất import!');
            logMessage('Dọn dẹp thành công.');
        } else {
            setStatus('Hoàn tất nhưng không dọn được tạm.');
            logMessage(`Cleanup lỗi: ${cleanup.error || 'Không xác định'}`);
        }
    }

    if (btnStart) btnStart.disabled = false;
    if (btnCancel) btnCancel.style.display = 'none';
}

if (btnStart) {
    btnStart.addEventListener('click', startImport);
}

if (btnCancel) {
    btnCancel.addEventListener('click', function() {
        if (confirm('Bạn có chắc muốn hủy tiến trình hiện tại?')) {
            abortImport = true;
            if (btnStart) btnStart.disabled = false;
            if (btnCancel) btnCancel.style.display = 'none';
            setStatus('Đang hủy...');
            logMessage('Người dùng yêu cầu hủy.');
        }
    });
}

// =========================================================
// 3. XỬ LÝ CHỌN FILE, HỦY FILE VÀ XEM THỬ REALISTIC
// =========================================================
// Tiếp tục từ đoạn bị cắt: function CSVToArray(strData) { ... }

function CSVToArray(strData) {
    // Biểu thức chính quy (Regex) để tách các cột phân cách bằng dấu phẩy
    // Giữ nguyên được dữ liệu nếu trong tên sản phẩm có chứa dấu phẩy (nằm trong ngoặc kép "")
    const objPattern = new RegExp(
        "(\\,|\\r?\\n|\\r|^)" +
        "(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|" +
        "([^\"\\,\\r\\n]*))",
        "gi"
    );

    const arrData = [[]];
    let arrMatches = null;

    // Vòng lặp quét qua toàn bộ chuỗi CSV
    while (arrMatches = objPattern.exec(strData)) {
        const strMatchedDelimiter = arrMatches[1];

        // Nếu gặp dấu xuống dòng, tạo một dòng mới trong mảng
        if (strMatchedDelimiter.length && strMatchedDelimiter !== ",") {
            arrData.push([]);
        }

        let strMatchedValue;
        // Nếu giá trị được bọc trong ngoặc kép
        if (arrMatches[2]) {
            strMatchedValue = arrMatches[2].replace(new RegExp("\"\"", "g"), "\"");
        } else {
            // Giá trị bình thường không có ngoặc kép
            strMatchedValue = arrMatches[3];
        }

        // Đẩy dữ liệu vào cột cuối cùng của dòng hiện tại
        arrData[arrData.length - 1].push(strMatchedValue);
    }

    return arrData;
}

// =========================================================
// 4. XỬ LÝ SỰ KIỆN GIAO DIỆN KHI CHỌN FILE
// =========================================================

// Hiển thị tên file ZIP khi khách hàng chọn
if (zipInput) {
    zipInput.addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : "Chưa chọn file";
        const label = document.getElementById('zip_file_name');
        if (label) label.textContent = fileName;
        setStatus('Đã chọn file ZIP: ' + fileName);
    });
}

// Hiển thị tên file CSV và kích hoạt chế độ XEM TRƯỚC (Preview)
if (csvInput) {
    csvInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const label = document.getElementById('csv_file_name');
        
        if (!file) {
            if (label) label.textContent = "Chưa chọn file";
            return;
        }

        if (label) label.textContent = file.name;
        setStatus('Đang đọc dữ liệu CSV để xem trước...');

        // Dùng FileReader để đọc lướt qua file CSV trên trình duyệt (không cần gửi lên Server)
        const reader = new FileReader();
        reader.onload = function(event) {
            const csvData = event.target.result;
            const parsedData = CSVToArray(csvData);
            
            // Render ra cái bảng Preview nhỏ gọn (Giả sử bạn có thẻ div id="csv_preview_table")
            renderPreviewTable(parsedData);
        };
        reader.readAsText(file);
    });
}

// Hàm render bảng xem trước 5 dòng đầu tiên của CSV
function renderPreviewTable(data) {
    const previewContainer = document.getElementById('csv_preview_table');
    if (!previewContainer) return; // Nếu trong HTML không có id này thì bỏ qua

    let html = '<table class="preview-table" style="width:100%; border-collapse: collapse; margin-top: 15px; font-size: 13px;">';
    
    // Lặp qua tối đa 6 dòng (1 dòng tiêu đề + 5 dòng dữ liệu) để tránh lag nếu file quá lớn
    const maxRows = Math.min(data.length, 6); 
    
    for (let i = 0; i < maxRows; i++) {
        const row = data[i];
        // Bỏ qua dòng trống
        if (row.length === 1 && row[0].trim() === '') continue;

        html += '<tr>';
        row.forEach(col => {
            if (i === 0) {
                // Dòng tiêu đề
                html += `<th style="border: 1px solid #ddd; padding: 8px; background: #f4f4f4;">${col}</th>`;
            } else {
                // Dòng dữ liệu
                // Highlight cột hình ảnh (giả sử cột hình ảnh là cột số 5 - index 4) để khách dễ nhìn
                const cellStyle = 'border: 1px solid #ddd; padding: 8px;';
                html += `<td style="${cellStyle}">${col}</td>`;
            }
        });
        html += '</tr>';
    }
    
    html += '</table>';
    
    if (data.length > 6) {
        html += `<p style="color: #666; font-size: 12px; font-style: italic; margin-top: 5px;">* Chỉ hiển thị xem trước 5 dòng đầu tiên. Tổng cộng có ${data.length - 1} sản phẩm.</p>`;
    }

    previewContainer.innerHTML = html;
    setStatus('Đã load xong bản xem trước CSV.');
}