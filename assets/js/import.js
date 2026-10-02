document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('modalUniversalImport');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    
    // UI Elements
    const dropZone = document.getElementById('importDropZone');
    const fileInput = document.getElementById('importFileInput');
    const errorMsg = document.getElementById('importFileError');
    const typeLabel = document.getElementById('importModalTypeLabel');
    const downloadBtn = document.getElementById('btnDownloadTemplate');
    
    const step1 = document.getElementById('importStep1');
    const step2 = document.getElementById('importStep2');
    const btnBack = document.getElementById('btnBackToStep1');
    const btnConfirm = document.getElementById('btnConfirmImport');
    
    // Preview Elements
    const thead = document.getElementById('importPreviewThead');
    const tbody = document.getElementById('importPreviewTbody');
    const spanTotal = document.getElementById('previewTotalRows');
    const spanValid = document.getElementById('previewValidRows');
    const spanError = document.getElementById('previewErrorRows');
    const spanConfirmCount = document.getElementById('btnConfirmCount');

    let currentType = '';
    let uploadedFile = null;
    let tempFileName = '';
    let isValidToCommit = false;

    // Compute base URL dynamically so it works from /admin/, /teacher/, /student/, or root
    function getApiUrl() {
        const path = window.location.pathname;
        const depth = (path.match(/\/[^/]+/g) || []).length - 1; // sub-folder depth
        const prefix = depth > 1 ? '../'.repeat(depth - 1) : '';
        return prefix + 'api/import.php';
    }

    // Trigger modal globally
    // Button usage: <button data-bs-toggle="modal" data-bs-target="#modalUniversalImport" data-import-type="users" data-import-label="Tài khoản">...</button>
    modalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (button) {
            currentType = button.getAttribute('data-import-type') || 'users';
            typeLabel.textContent = button.getAttribute('data-import-label') || 'Dữ liệu';
            downloadBtn.href = `${getApiUrl()}?action=template&type=${currentType}`;
        }
        resetModal();
    });


    function resetModal() {
        step1.classList.remove('d-none');
        step2.classList.add('d-none');
        btnConfirm.classList.add('d-none');
        btnConfirm.disabled = true;
        fileInput.value = '';
        uploadedFile = null;
        tempFileName = '';
        errorMsg.classList.add('d-none');
        dropZone.style.borderColor = 'var(--bs-primary)';
        dropZone.style.backgroundColor = '#fff';
    }

    btnBack.addEventListener('click', resetModal);

    // Drag & Drop Styling
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.backgroundColor = '#f8f9fa';
            dropZone.style.borderColor = '#0d6efd';
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.backgroundColor = '#fff';
            dropZone.style.borderColor = 'var(--bs-primary)';
        }, false);
    });

    // Handle Drop
    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length) handleFiles(files[0]);
    });

    // Handle Click (Input change)
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) handleFiles(e.target.files[0]);
    });

    function handleFiles(file) {
        // Validate Extension
        const validExts = ['.xlsx', '.csv'];
        const fileExt = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
        if (!validExts.includes(fileExt)) {
            showError('Chỉ chấp nhận file .xlsx hoặc .csv');
            return;
        }

        // Validate Size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showError('File không được vượt quá 5MB');
            return;
        }

        errorMsg.classList.add('d-none');
        uploadedFile = file;
        
        // Show loading state
        dropZone.style.opacity = '0.5';
        
        uploadForPreview(file);
    }

    function showError(msg) {
        errorMsg.querySelector('.msg').textContent = msg;
        errorMsg.classList.remove('d-none');
        fileInput.value = '';
    }

    async function uploadForPreview(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'preview');
        formData.append('type', currentType);

        try {
            const res = await fetch(getApiUrl(), {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            dropZone.style.opacity = '1';

            if (data.status === 'success') {
                renderPreview(data);
            } else {
                showError(data.message || 'Lỗi xử lý file.');
            }
        } catch (err) {
            dropZone.style.opacity = '1';
            showError('Không thể kết nối đến máy chủ.');
        }
    }

    function renderPreview(data) {
        const { headers, rows, temp_file, summary } = data.data;
        tempFileName = temp_file;

        // Render Headers
        let headerHtml = '<tr><th class="text-center" style="width:50px;">#</th>';
        headers.forEach(h => {
            headerHtml += `<th>${h}</th>`;
        });
        headerHtml += '<th>Trạng thái</th></tr>';
        thead.innerHTML = headerHtml;

        // Render Rows
        let tbodyHtml = '';
        rows.forEach((row, index) => {
            const isError = row._errors && row._errors.length > 0;
            const trClass = isError ? 'table-danger' : '';
            
            tbodyHtml += `<tr class="${trClass}">`;
            tbodyHtml += `<td class="text-center fw-bold text-muted">${index + 1}</td>`;
            
            headers.forEach(key => {
                const val = row[key] !== undefined ? row[key] : '';
                tbodyHtml += `<td>${val}</td>`;
            });
            
            if (isError) {
                const errList = row._errors.join(', ');
                tbodyHtml += `<td class="text-danger fw-semibold" style="font-size:0.8rem;"><i class="bi bi-x-circle-fill me-1"></i>${errList}</td>`;
            } else {
                tbodyHtml += `<td class="text-success fw-semibold"><i class="bi bi-check-circle-fill"></i> Hợp lệ</td>`;
            }
            tbodyHtml += `</tr>`;
        });

        tbody.innerHTML = tbodyHtml;

        // Update Summary
        spanTotal.textContent = summary.total;
        spanValid.textContent = summary.valid;
        spanError.textContent = summary.error;
        spanConfirmCount.textContent = summary.valid;

        // Toggle UI
        step1.classList.add('d-none');
        step2.classList.remove('d-none');
        btnConfirm.classList.remove('d-none');

        isValidToCommit = summary.error === 0 && summary.valid > 0;
        
        if (isValidToCommit) {
            btnConfirm.disabled = false;
        } else {
            btnConfirm.disabled = true;
            if(summary.valid === 0) {
                btnConfirm.innerHTML = `<i class="bi bi-x-circle me-2"></i>Không có dữ liệu hợp lệ`;
            } else {
                btnConfirm.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>Vui lòng sửa lỗi trước khi Import`;
            }
        }
    }

    // Handle Commit
    btnConfirm.addEventListener('click', async () => {
        if (!isValidToCommit || !tempFileName) return;
        
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang lưu...`;

        const formData = new FormData();
        formData.append('action', 'commit');
        formData.append('type', currentType);
        formData.append('temp_file', tempFileName);

        try {
            const res = await fetch(getApiUrl(), {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                alert('Import dữ liệu thành công!');
                window.location.reload();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể lưu dữ liệu'));
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = `<i class="bi bi-check-circle me-2"></i>Xác nhận Import (${spanConfirmCount.textContent})`;
            }
        } catch (err) {
            alert('Lỗi kết nối khi lưu dữ liệu.');
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = `<i class="bi bi-check-circle me-2"></i>Xác nhận Import (${spanConfirmCount.textContent})`;
        }
    });

});
