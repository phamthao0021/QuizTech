<?php
// admin/create_exam.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$page_title = 'Tạo Đề Thi Mới';

$subjects = [];
if (isset($pdo)) {
    try {
        $subjects = $pdo->query("SELECT id, name FROM subjects ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $subjects = [];
    }
}

include '../includes/header_admin.php';
?>
<!-- CDN Thư viện hỗ trợ -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mammoth@1.6.0/mammoth.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
</script>

<style>
    :root {
        --bg-main: #f8fafc;
        --card-border: #e2e8f0;
        --card-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
        --card-shadow-hover: 0 12px 28px -4px rgba(15, 23, 42, 0.08);
        --brand-primary: #4f46e5;
        --brand-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    }

    body {
        background-color: var(--bg-main);
    }

    /* Modern Card Base */
    .card-modern {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 1rem;
        box-shadow: var(--card-shadow);
        transition: all 0.25s ease-in-out;
    }
    .card-modern:hover {
        box-shadow: var(--card-shadow-hover);
    }

    /* Top Sticky Header */
    .header-banner {
        background: var(--brand-gradient);
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.3);
    }

    /* Drag Dropzone Upload UI */
    .file-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 0.875rem;
        background: #f8fafc;
        transition: all 0.2s ease;
        cursor: pointer;
        text-align: center;
        padding: 1.5rem 1rem;
    }
    .file-dropzone:hover {
        border-color: var(--brand-primary);
        background: #f0fdf4;
    }

    /* Question Cards Dynamic Type Styling */
    .question-card {
        border: 1px solid var(--card-border);
        border-left: 5px solid var(--brand-primary);
        border-radius: 1rem;
        background: #ffffff;
        box-shadow: var(--card-shadow);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .question-card.type-single_choice { border-left-color: #6366f1; }
    .question-card.type-multiple_choice { border-left-color: #0d9488; }
    .question-card.type-true_false { border-left-color: #f59e0b; }
    .question-card.type-fill_blank { border-left-color: #f43f5e; }
    .question-card.type-matching { border-left-color: #a855f7; }

    /* Badge & Input Enhancements */
    .opt-badge {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
    }
    .form-control, .form-select {
        border-color: #cbd5e1;
        padding: 0.55rem 0.85rem;
        font-size: 0.925rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    }

    .explanation-box {
        background-color: #f0f9ff;
        border: 1px dashed #38bdf8;
        border-radius: 0.75rem;
    }

    /* Custom Floating Action Toolbar */
    .sticky-toolbar {
        position: sticky;
        top: 1rem;
        z-index: 900;
        backdrop-filter: blur(8px);
        background: rgba(255, 255, 255, 0.85);
    }
</style>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Banner Đầu Trang -->
    <div class="header-banner admin-page-head p-4 text-white mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <a href="exams.php" class="btn btn-sm btn-outline-light mb-2 rounded-pill px-3 shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Danh sách đề thi
                </a>
                <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-journal-plus"></i> Tạo & Quản Lý Đề Thi
                </h3>
                <p class="text-white-50 mb-0 small">Hỗ trợ tự động trích xuất file Excel, Word, PDF & Soạn thảo đa dạng câu hỏi.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light text-dark fw-bold rounded-3 px-3 py-2 shadow-sm" onclick="openPreviewModal()">
                    <i class="bi bi-eye-fill me-1 text-primary"></i> Xem trước
                </button>
                <button type="button" class="btn btn-warning text-dark fw-bold rounded-3 px-4 py-2 shadow-sm" onclick="saveExam()">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Lưu Đề Thi
                </button>
            </div>
        </div>
    </div>
<!-- Drag & Drop Import Box -->
            <div class="card-modern p-4 mb-4">
                <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-arrow-up text-success fs-5"></i> Nhập Đề Tự Động
                </h6>
                
                <div class="file-dropzone" onclick="document.getElementById('file_input').click();">
                    <i class="bi bi-cloud-upload text-primary display-6 d-block mb-2"></i>
                    <span class="fw-bold text-dark d-block mb-1">Bấm hoặc kéo thả file vào đây</span>
                    <span class="text-muted small d-block mb-2">Hỗ trợ định dạng .XLSX, .DOCX, .PDF</span>
                    <span class="badge bg-light text-secondary border">Tối đa 15MB</span>
                </div>
                <input type="file" id="file_input" class="d-none" accept=".xlsx,.xls,.csv,.docx,.pdf">
            </div>
    <div class="row g-4">
        <!-- Cột Trái: Cấu Hình Đề & Import File -->
        <div class="col-xl-4 col-lg-5">
            <div class="card-modern p-4 mb-4">
                <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-gear-wide-connected text-primary fs-5"></i> Thông Tin Cơ Bản
                </h6>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Tên Bài Thi / Đề Thi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" id="exam_title" placeholder="VD: Kiểm tra Giữa Kỳ - Lập Trình Web PHP">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Môn Học Liên Quan <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" id="subject_id">
                        <option value="" disabled selected>-- Chọn môn học --</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Thời Gian Làm Bài (Phút)</label>
                    <div class="input-group">
                        <input type="number" class="form-control rounded-start-3" id="duration" value="45" min="1">
                        <span class="input-group-text bg-light text-muted rounded-end-3">phút</span>
                    </div>
                </div>
            </div>

            

            <!-- Panel Thống Kê Dạng Câu Hỏi -->
            <div class="card-modern p-4">
                <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-info fs-5"></i> Tổng Quan Số Lượng
                </h6>
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-3">
                    <span class="small fw-bold text-secondary">Tổng số câu hỏi:</span>
                    <span class="badge bg-primary rounded-pill px-3 py-2 fs-6" id="q_count">0</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="small text-secondary"><i class="bi bi-dot text-indigo fs-5 me-1"></i>Trắc nghiệm (1 đáp án):</span>
                    <strong class="text-dark" id="stat_sc">0</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="small text-secondary"><i class="bi bi-dot text-teal fs-5 me-1"></i>Chọn nhiều đáp án:</span>
                    <strong class="text-dark" id="stat_mc">0</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center px-1">
                    <span class="small text-secondary"><i class="bi bi-dot text-warning fs-5 me-1"></i>Đúng / Sai:</span>
                    <strong class="text-dark" id="stat_tf">0</strong>
                </div>
            </div>
        </div>

        <!-- Cột Phải: Danh Sách Câu Hỏi & Phân Trang -->
        <div class="col-xl-8 col-lg-7">
            <div class="card-modern p-4">
                
                <!-- Thanh Công Cụ Điều Hướng Top -->
                <div class="sticky-toolbar p-3 rounded-3 border mb-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-primary fw-semibold rounded-3 px-3 shadow-sm d-flex align-items-center gap-1" onclick="addQuestion()">
                            <i class="bi bi-plus-lg"></i> Thêm Câu Hỏi Thủ Công
                        </button>
                        
                        <div class="d-flex align-items-center gap-3">
                            <span class="small fw-semibold text-secondary d-none d-sm-inline" id="page_info">Trang 0 / 0</span>
                            <select class="form-select form-select-sm rounded-2" style="width: 140px;" onchange="changePerPage(this.value)">
                                <option value="10" selected>10 câu / trang</option>
                                <option value="20">20 câu / trang</option>
                                <option value="50">50 câu / trang</option>
                                <option value="all">Hiện tất cả</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Container Hiển Thị Danh Sách Câu Hỏi -->
                <div id="questions_container"></div>

                <!-- Thanh Phân Trang Bottom -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                    <button class="btn btn-sm btn-outline-secondary rounded-2 px-3" id="btn_prev" onclick="goToPage(currentPage - 1)">
                        <i class="bi bi-chevron-left me-1"></i> Trang trước
                    </button>

                    <ul class="pagination pagination-sm mb-0 gap-1" id="pagination_list"></ul>

                    <button class="btn btn-sm btn-outline-secondary rounded-2 px-3" id="btn_next" onclick="goToPage(currentPage + 1)">
                        Trang sau <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Xem Trước Đề Thi (Preview) -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-eye text-primary me-2"></i>Xem Trước Giao Diện Bài Thi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="preview_content"></div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Đóng Xem Trước</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let questionsList = [];
let currentPage = 1;
let itemsPerPage = 10;

// ==========================================
// 1. DỮ LIỆU & IMPORT FILE SMART
// ==========================================
document.getElementById('file_input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const ext = file.name.split('.').pop().toLowerCase();
    if (ext === 'xlsx' || ext === 'xls' || ext === 'csv') parseExcel(file);
    else if (ext === 'docx') parseDocx(file);
    else if (ext === 'pdf') parsePdf(file);
    else Swal.fire('Lỗi', 'Định dạng file không được hỗ trợ!', 'error');
});

function parseExcel(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const rows = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], {header: 1});

        let newQs = [];
        for(let i = 1; i < rows.length; i++) {
            let row = rows[i];
            if(!row || !row[0]) continue;

            let qText = String(row[0]).trim();
            let opts = [];
            let explanation = '';
            let correct = 'A';

            for(let j = 1; j < row.length; j++) {
                let cellVal = row[j] !== undefined ? String(row[j]).trim() : '';
                if(!cellVal) continue;

                if (j === row.length - 2 && cellVal.length <= 5) {
                    correct = cellVal;
                } else if (j === row.length - 1) {
                    explanation = cellVal;
                } else {
                    opts.push(cellVal);
                }
            }

            newQs.push({ 
                type: 'single_choice', 
                question_text: qText, 
                options: opts, 
                correct_answer: correct,
                explanation: explanation
            });
        }
        appendQuestions(newQs);
    };
    reader.readAsArrayBuffer(file);
}

function parseDocx(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        mammoth.extractRawText({arrayBuffer: e.target.result}).then(res => parseTextToQuestions(res.value));
    };
    reader.readAsArrayBuffer(file);
}

function parsePdf(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        pdfjsLib.getDocument(new Uint8Array(e.target.result)).promise.then(pdf => {
            let promises = [];
            for (let j = 1; j <= pdf.numPages; j++) promises.push(pdf.getPage(j).then(p => p.getTextContent()));
            Promise.all(promises).then(pages => {
                let text = '';
                pages.forEach(p => p.items.forEach(item => text += item.str + '\n'));
                parseTextToQuestions(text);
            });
        });
    };
    reader.readAsArrayBuffer(file);
}

function parseTextToQuestions(text) {
    let formattedText = text.replace(/([^\n])\s*([A-D][\.\:\)])\s*/g, "$1\n$2 ");
    let lines = formattedText.split('\n').map(l => l.trim()).filter(l => l.length > 0);
    let parsedList = [], currentQ = null;
    let isExplanationMode = false;

    lines.forEach(line => {
        let expMatch = line.match(/(Giải thích|Lời giải|Explanation)\s*[:\.-]\s*(.*)/i);

        if (/^(Câu|Question|\d+)\s*\d*[:\.]/i.test(line)) {
            if (currentQ) parsedList.push(currentQ);
            currentQ = { 
                type: 'single_choice', 
                question_text: line.replace(/^(Câu|Question|\d+)\s*\d*[:\.]/i, '').trim(), 
                options: [], 
                correct_answer: 'A',
                explanation: ''
            };
            isExplanationMode = false;
        } 
        else if (expMatch && currentQ) {
            isExplanationMode = true;
            let expContent = expMatch[2] ? expMatch[2].trim() : '';
            currentQ.explanation = (currentQ.explanation ? currentQ.explanation + ' ' : '') + expContent;
        }
        else if (isExplanationMode && currentQ) {
            currentQ.explanation += ' ' + line;
        }
        else if (/^[A-E][\.\:\)]/i.test(line) && currentQ && currentQ.options.length < 5) {
            let optClean = line.replace(/^[A-E][\.\:\)]/i, '').trim();

            let innerExp = optClean.match(/(.*)?\s*(Giải thích|Lời giải|Explanation)\s*[:\.-]\s*(.*)/i);
            if (innerExp) {
                if (innerExp[1]) currentQ.options.push(innerExp[1].trim());
                isExplanationMode = true;
                currentQ.explanation = innerExp[3] ? innerExp[3].trim() : '';
            } else {
                currentQ.options.push(optClean);
            }
        } 
        else if (/^(Đáp án|Correct|Ans)\s*[:\.-]/i.test(line) && currentQ) {
            currentQ.correct_answer = line.replace(/^(Đáp án|Correct|Ans)\s*[:\.-]/i, '').trim();
        } 
        else if (currentQ) {
            if (currentQ.options.length === 0) {
                currentQ.question_text += ' ' + line;
            } else {
                let lastOptIdx = currentQ.options.length - 1;
                currentQ.options[lastOptIdx] += ' ' + line;
            }
        }
    });

    if (currentQ) parsedList.push(currentQ);
    parsedList.forEach(q => { if(q.explanation) q.explanation = q.explanation.trim(); });

    appendQuestions(parsedList);
}

function appendQuestions(newList) {
    questionsList = questionsList.concat(newList);
    currentPage = 1;
    renderQuestions();
}

// ==========================================
// 2. PHÂN TRANG & RENDER GIAO DIỆN
// ==========================================
function changePerPage(val) {
    itemsPerPage = val === 'all' ? questionsList.length : parseInt(val);
    currentPage = 1;
    renderQuestions();
}

function goToPage(p) {
    const totalPages = Math.ceil(questionsList.length / (itemsPerPage || 1)) || 1;
    if (p < 1 || p > totalPages) return;
    currentPage = p;
    renderQuestions();
}

function updateStats() {
    let sc = 0, mc = 0, tf = 0;
    questionsList.forEach(q => {
        if(q.type === 'single_choice') sc++;
        else if(q.type === 'multiple_choice') mc++;
        else if(q.type === 'true_false') tf++;
    });
    document.getElementById('stat_sc').innerText = sc;
    document.getElementById('stat_mc').innerText = mc;
    document.getElementById('stat_tf').innerText = tf;
}

function renderQuestions() {
    const container = document.getElementById('questions_container');
    document.getElementById('q_count').innerText = questionsList.length;
    updateStats();

    if (questionsList.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-card-checklist fs-1 d-block mb-2 text-black-50"></i>
                Chưa có câu hỏi nào. Hãy tải file lên hoặc bấm thêm câu hỏi thủ công!
            </div>`;
        document.getElementById('pagination_list').innerHTML = '';
        document.getElementById('page_info').innerText = 'Trang 0 / 0';
        return;
    }

    // Tính toán phân trang
    let effectiveLimit = itemsPerPage === 'all' ? questionsList.length : itemsPerPage;
    const totalPages = Math.ceil(questionsList.length / effectiveLimit) || 1;
    if (currentPage > totalPages) currentPage = totalPages;

    const startIdx = (currentPage - 1) * effectiveLimit;
    const endIdx = Math.min(startIdx + effectiveLimit, questionsList.length);
    const pageItems = questionsList.slice(startIdx, endIdx);

    // Cập nhật toolbar
    document.getElementById('page_info').innerText = `Hiển thị ${startIdx + 1} - ${endIdx} / ${questionsList.length} câu (Trang ${currentPage}/${totalPages})`;
    document.getElementById('btn_prev').disabled = currentPage === 1;
    document.getElementById('btn_next').disabled = currentPage === totalPages;

    // Render nút số trang
    let pagHtml = '';
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            pagHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a></li>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            pagHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    document.getElementById('pagination_list').innerHTML = pagHtml;

    // Render danh sách câu hỏi trang hiện tại
    let html = '';
    pageItems.forEach((q, relIdx) => {
        let absIdx = startIdx + relIdx; // Chỉ số thực trong mảng tổng

        html += `
        <div class="question-card p-3 p-md-4 mb-3 position-relative">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <span class="badge bg-primary fs-6 fw-bold rounded-pill px-3 py-1">Câu ${absIdx + 1}</span>
                <button class="btn btn-sm btn-outline-danger rounded-2" onclick="deleteQuestion(${absIdx})">
                    <i class="bi bi-trash-fill me-1"></i> Xóa Câu
                </button>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-3">
                    <label class="fw-bold small text-primary mb-1">Dạng Câu Hỏi:</label>
                    <select class="form-select form-select-sm rounded-2 py-1.5" onchange="changeType(${absIdx}, this.value)">
                        <option value="single_choice" ${q.type === 'single_choice' ? 'selected' : ''}>Trắc nghiệm (1 đáp án)</option>
                        <option value="multiple_choice" ${q.type === 'multiple_choice' ? 'selected' : ''}>Chọn nhiều đáp án</option>
                        <option value="true_false" ${q.type === 'true_false' ? 'selected' : ''}>Đúng / Sai</option>
                        <option value="fill_blank" ${q.type === 'fill_blank' ? 'selected' : ''}>Điền từ vào chỗ trống</option>
                        <option value="matching" ${q.type === 'matching' ? 'selected' : ''}>Nối cột (Matching)</option>
                    </select>
                </div>
                <div class="col-12 col-md-9">
                    <label class="fw-bold small text-secondary mb-1">Nội Dung Câu Hỏi:</label>
                    <textarea class="form-control form-control-sm rounded-2" rows="2" onchange="questionsList[${absIdx}].question_text = this.value">${q.question_text || ''}</textarea>
                </div>
            </div>

            <div class="bg-light p-3 rounded-3 mb-3 border">
                ${renderTypeOptions(q, absIdx)}
            </div>

            <!-- Lời Giải Thích -->
            <div class="explanation-box p-3">
                <label class="fw-bold small text-info mb-1"><i class="bi bi-lightbulb-fill me-1"></i> Lời Giải Thích / Hướng Dẫn Chi Tiết:</label>
                <textarea class="form-control form-control-sm rounded-2 border-0 bg-white" rows="2" placeholder="Nhập lời giải thích chi tiết cho câu hỏi..." onchange="questionsList[${absIdx}].explanation = this.value">${q.explanation || ''}</textarea>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function renderTypeOptions(q, idx) {
    if (q.type === 'single_choice' || q.type === 'multiple_choice') {
        let optsHtml = '';
        (q.options || []).forEach((opt, oIdx) => {
            optsHtml += `
            <div class="row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <span class="opt-badge bg-white text-primary border border-primary">${String.fromCharCode(65 + oIdx)}</span>
                </div>
                <div class="col">
                    <input type="text" class="form-control form-control-sm rounded-2" value="${opt}" onchange="questionsList[${idx}].options[${oIdx}] = this.value">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteOption(${idx}, ${oIdx})">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>`;
        });
        
        const labelHint = q.type === 'single_choice' ? 'Đáp án đúng (1 chữ cái, VD: A):' : 'Đáp án đúng (Phân cách bằng dấu phẩy, VD: A,C):';

        return `
            <div class="mb-2 fw-semibold text-primary small"><i class="bi bi-list-check me-1"></i> Các phương án lựa chọn:</div>
            ${optsHtml}
            <div class="mt-2">
                <button class="btn btn-sm btn-link text-decoration-none p-0 fw-bold" onclick="addOption(${idx})">
                    <i class="bi bi-plus-circle me-1"></i>Thêm phương án
                </button>
            </div>
            <div class="mt-3 pt-2 border-top d-flex align-items-center flex-wrap gap-2">
                <label class="small fw-bold text-secondary mb-0">${labelHint}</label>
                <input type="text" class="form-control form-control-sm w-auto fw-bold text-success text-uppercase" 
                       value="${q.correct_answer || 'A'}" 
                       onchange="questionsList[${idx}].correct_answer = this.value">
            </div>`;
    } 
    else if (q.type === 'true_false') {
        return `
            <div class="fw-semibold text-primary small mb-2"><i class="bi bi-check2-circle me-1"></i>Chọn kết quả đúng:</div>
            <div class="p-2 border rounded-3 bg-white d-inline-block">
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="tf_${idx}" id="tf_t_${idx}" value="True" ${q.correct_answer === 'True' ? 'checked' : ''} onchange="questionsList[${idx}].correct_answer = 'True'">
                    <label class="form-check-label fw-bold text-success" for="tf_t_${idx}">Đúng (True)</label>
                </div>
                <div class="form-check form-check-inline mb-0 ms-3">
                    <input class="form-check-input" type="radio" name="tf_${idx}" id="tf_f_${idx}" value="False" ${q.correct_answer === 'False' ? 'checked' : ''} onchange="questionsList[${idx}].correct_answer = 'False'">
                    <label class="form-check-label fw-bold text-danger" for="tf_f_${idx}">Sai (False)</label>
                </div>
            </div>`;
    } 
    else if (q.type === 'fill_blank') {
        return `
            <div class="fw-semibold text-primary small mb-2"><i class="bi bi-pencil-square me-1"></i>Cụm từ cần điền vào chỗ trống:</div>
            <input type="text" class="form-control form-control-sm rounded-2 fw-bold text-success" 
                   value="${q.correct_answer || ''}" 
                   onchange="questionsList[${idx}].correct_answer = this.value" 
                   placeholder="Nhập đáp án chuẩn xác...">`;
    } 
    else if (q.type === 'matching') {
        let pairsHtml = '';
        let pairs = Array.isArray(q.options) ? q.options : [];
        pairs.forEach((pair, pIdx) => {
            pairsHtml += `
            <div class="row g-2 mb-2 align-items-center">
                <div class="col-5">
                    <input type="text" class="form-control form-control-sm" value="${pair.left || ''}" placeholder="Mục A (${pIdx + 1})" onchange="updateMatching(${idx}, ${pIdx}, 'left', this.value)">
                </div>
                <div class="col-1 text-center text-muted fw-bold"><i class="bi bi-arrow-right"></i></div>
                <div class="col-5">
                    <input type="text" class="form-control form-control-sm" value="${pair.right || ''}" placeholder="Mục B (${pIdx + 1})" onchange="updateMatching(${idx}, ${pIdx}, 'right', this.value)">
                </div>
                <div class="col-1">
                    <button class="btn btn-sm btn-outline-danger w-100" onclick="deleteMatching(${idx}, ${pIdx})"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>`;
        });
        return `
            <div class="fw-semibold text-primary small mb-2"><i class="bi bi-card-heading me-1"></i>Cặp ghép nối tương ứng:</div>
            ${pairsHtml}
            <div class="mt-2">
                <button class="btn btn-sm btn-link text-decoration-none p-0 fw-bold" onclick="addMatching(${idx})">
                    <i class="bi bi-plus-circle me-1"></i>Thêm cặp nối mới
                </button>
            </div>`;
    }
}

// Thao tác chỉnh sửa câu hỏi
function changeType(idx, type) {
    questionsList[idx].type = type;
    if (type === 'single_choice' || type === 'multiple_choice') {
        if (!Array.isArray(questionsList[idx].options) || typeof questionsList[idx].options[0] === 'object') {
            questionsList[idx].options = ['Phương án A', 'Phương án B', 'Phương án C', 'Phương án D'];
        }
        questionsList[idx].correct_answer = 'A';
    } else if (type === 'true_false') {
        questionsList[idx].options = [];
        questionsList[idx].correct_answer = 'True';
    } else if (type === 'fill_blank') {
        questionsList[idx].options = [];
        questionsList[idx].correct_answer = '';
    } else if (type === 'matching') {
        questionsList[idx].options = [{ left: 'Vế A1', right: 'Vế B1' }, { left: 'Vế A2', right: 'Vế B2' }];
        questionsList[idx].correct_answer = '';
    }
    renderQuestions();
}

function addQuestion() {
    questionsList.push({ 
        type: 'single_choice', 
        question_text: 'Nhập nội dung câu hỏi mới...', 
        options: ['Phương án A', 'Phương án B', 'Phương án C', 'Phương án D'], 
        correct_answer: 'A',
        explanation: ''
    });
    const totalPages = Math.ceil(questionsList.length / (itemsPerPage === 'all' ? questionsList.length : itemsPerPage));
    currentPage = totalPages;
    renderQuestions();
}

function deleteQuestion(idx) { questionsList.splice(idx, 1); renderQuestions(); }
function deleteOption(qIdx, oIdx) { questionsList[qIdx].options.splice(oIdx, 1); renderQuestions(); }
function addOption(qIdx) {
    if(!Array.isArray(questionsList[qIdx].options)) questionsList[qIdx].options = [];
    questionsList[qIdx].options.push('Phương án mới');
    renderQuestions();
}
function updateMatching(qIdx, pIdx, side, val) {
    if(!questionsList[qIdx].options[pIdx]) questionsList[qIdx].options[pIdx] = {};
    questionsList[qIdx].options[pIdx][side] = val;
}
function addMatching(qIdx) {
    if(!Array.isArray(questionsList[qIdx].options)) questionsList[qIdx].options = [];
    questionsList[qIdx].options.push({left: '', right: ''});
    renderQuestions();
}
function deleteMatching(qIdx, pIdx) { questionsList[qIdx].options.splice(pIdx, 1); renderQuestions(); }

// ==========================================
// 3. PREVIEW & LƯU BÀI THI
// ==========================================
function openPreviewModal() {
    const title = document.getElementById('exam_title').value || 'Chưa đặt tên đề thi';
    let html = `<h4 class="fw-bold text-center mb-4 text-primary">${title}</h4>`;
    
    if(questionsList.length === 0) {
        html += `<p class="text-center text-muted">Chưa có câu hỏi nào để xem trước.</p>`;
    } else {
        questionsList.forEach((q, i) => {
            html += `<div class="p-3 border rounded-3 mb-3 bg-light">
                <div class="fw-bold text-dark mb-2">Câu ${i+1}: ${q.question_text}</div>`;
            if(q.options && q.options.length > 0 && typeof q.options[0] === 'string') {
                html += `<div class="row g-2">`;
                q.options.forEach((opt, oI) => {
                    html += `<div class="col-6"><small><b>${String.fromCharCode(65+oI)}.</b> ${opt}</small></div>`;
                });
                html += `</div>`;
            }
            if(q.explanation) {
                html += `<div class="mt-2 p-2 bg-white rounded border small text-info"><i class="bi bi-info-circle me-1"></i><b>Giải thích:</b> ${q.explanation}</div>`;
            }
            html += `</div>`;
        });
    }

    document.getElementById('preview_content').innerHTML = html;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function saveExam() {
    const title = document.getElementById('exam_title').value.trim();
    if (!title) {
        Swal.fire('Chú ý', 'Vui lòng nhập tên đề thi!', 'warning');
        return;
    }
    if (questionsList.length === 0) {
        Swal.fire('Chú ý', 'Đề thi phải có ít nhất 1 câu hỏi!', 'warning');
        return;
    }

    const payload = {
        title: title,
        subject_id: document.getElementById('subject_id').value,
        duration: document.getElementById('duration').value,
        questions: questionsList
    };

    Swal.fire({
        title: 'Đang lưu dữ liệu...',
        text: 'Vui lòng chờ trong giây lát',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('save_exam_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = data.redirect || 'exams.php';
            });
        } else {
            Swal.fire('Lỗi', data.message || 'Không thể lưu bài thi!', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ!', 'error');
    });
}
</script>

<?php 
if (file_exists('../includes/footer.php')) {
    include '../includes/footer.php'; 
}
?>