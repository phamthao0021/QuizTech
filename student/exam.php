<?php
// student/take_exam.php
$rootDir = dirname(__DIR__);
require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/functions.php';
require_once $rootDir . '/includes/auth.php';

// Kích hoạt chặn bảo trì
checkMaintenanceMode();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isLoggedIn()) { header('Location: ../login.php'); exit(); }

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($exam_id <= 0) {
    setFlash('danger', 'Bài thi không tồn tại!');
    redirect('dashboard.php');
    exit;
}

try {
    $stmtExam = $pdo->prepare("SELECT e.*, s.name AS subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id WHERE e.id = ?");
    $stmtExam->execute([$exam_id]);
    $exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        setFlash('danger', 'Không tìm thấy bài thi!');
        redirect('dashboard.php');
        exit;
    }

    $duration_minutes = isset($exam['duration']) ? intval($exam['duration']) : (isset($exam['duration_minutes']) ? intval($exam['duration_minutes']) : 60);
    $total_seconds = $duration_minutes * 60;

    $stmtQuestions = $pdo->prepare("SELECT q.* FROM questions q INNER JOIN exam_questions eq ON q.id = eq.question_id WHERE eq.exam_id = ? ORDER BY eq.question_order ASC, q.id ASC");
    $stmtQuestions->execute([$exam_id]);
    $questions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions)) {
        $stmtQuestions = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
        $stmtQuestions->execute([$exam_id]);
        $questions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Lỗi CSDL: " . htmlspecialchars($e->getMessage()));
}

$page_title = 'Làm bài thi: ' . htmlspecialchars($exam['title'] ?? 'Đề thi');
require_once $rootDir . '/includes/header_student.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #6f42c1 0%, #4e73df 100%);
    }
    body {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }
    .bg-gradient-purple { background: var(--primary-gradient) !important; color: #fff; }
    .exam-header { border-radius: 16px; box-shadow: 0 10px 25px rgba(111, 66, 193, 0.15); }
    
    .question-item { display: none; }
    .question-item.active { display: block; }

    .q-card { border: none; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: all 0.2s ease; }
    .q-card.flagged { border: 2px solid #ffc107; }
    .option-box { border: 2px solid #e9ecef; border-radius: 10px; padding: 12px 16px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; }
    .option-box:hover { background-color: #f8f9fa; border-color: #b8daff; }
    .form-check-input:checked + .option-box { background-color: #f0e6ff; border-color: #6f42c1; font-weight: 600; color: #4a154b; }
    
    .nav-grid-btn { width: 38px; height: 38px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; background: #fff; color: #495057; transition: all 0.2s; position: relative; cursor: pointer; }
    .nav-grid-btn.active-current { border: 2px solid #4e73df; font-weight: 800; transform: scale(1.05); }
    .nav-grid-btn.answered { background: #6f42c1; color: #fff; border-color: #6f42c1; }
    .nav-grid-btn.flagged::after { content: ''; position: absolute; top: 2px; right: 2px; width: 8px; height: 8px; background: #ffc107; border-radius: 50%; }
    .sticky-sidebar { position: sticky; top: 90px; }
</style>

<div class="container-fluid py-4 px-md-4">
    <!-- Header Bài Thi -->
    <div class="card bg-gradient-purple exam-header mb-4 border-0 p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-white text-dark mb-2 px-3 py-2 rounded-pill fw-bold">
                    Môn: <?= htmlspecialchars($exam['subject_name'] ?? 'Tổng hợp') ?>
                </span>
                <h3 class="fw-bold mb-1 text-white"><?= htmlspecialchars($exam['title']) ?></h3>
                <p class="mb-0 text-white-50">Tổng số câu hỏi: <strong><?= count($questions) ?></strong> câu</p>
            </div>
            <div class="bg-white text-dark px-4 py-2 rounded-3 text-center shadow-sm">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.75rem;">Thời gian còn lại</small>
                <span class="fs-3 fw-bold text-primary" id="timer">--:--</span>
            </div>
        </div>
    </div>

    <!-- MAIN FORM LÀM BÀI -->
    <form id="examForm" action="submit_exam.php" method="POST">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        <input type="hidden" name="duration_seconds" id="duration_seconds" value="0">

        <div class="row g-4">
            <!-- Cột Câu Hỏi (Bên Trái) -->
            <div class="col-lg-8 col-xl-9">
                <div id="questionsContainer">
                    <?php foreach ($questions as $index => $q): 
                        $q_id = $q['id'];
                        $qContent = $q['content'] ?? $q['question_text'] ?? 'Nội dung câu hỏi';
                    ?>
                        <div class="question-item" id="q-item-<?= $index ?>" data-index="<?= $index ?>" data-id="<?= $q_id ?>">
                            <div class="card q-card mb-4" id="q-card-<?= $q_id ?>">
                                <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center rounded-top-4 gap-2">
                                    <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill">Câu <?= $index + 1 ?> / <?= count($questions) ?></span>
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Nút Báo lỗi -->
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="openReportModal(<?= $q_id ?>, <?= $index + 1 ?>)">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Báo lỗi
                                        </button>
                                        <!-- Nút Đánh dấu -->
                                        <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3" onclick="toggleFlag(<?= $q_id ?>)">
                                            <i class="bi bi-flag-fill me-1"></i>Đánh dấu
                                        </button>
                                        <!-- Nút Xóa chọn -->
                                        <button type="button" class="btn btn-link text-muted btn-sm p-0 text-decoration-none ms-1" onclick="clearSelection(<?= $q_id ?>)">
                                            <small><i class="bi bi-x-circle me-1"></i>Xóa chọn</small>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body px-4 pb-4 pt-0">
                                    <h5 class="fw-bold text-dark mb-4" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($qContent)) ?></h5>

                                    <div class="options-group d-flex flex-column gap-3">
                                        <?php 
                                        $options = ['A' => $q['option_a'] ?? '', 'B' => $q['option_b'] ?? '', 'C' => $q['option_c'] ?? '', 'D' => $q['option_d'] ?? ''];
                                        foreach ($options as $key => $val):
                                            if (trim($val) === '') continue;
                                        ?>
                                            <div class="position-relative">
                                                <input class="form-check-input d-none" type="radio" name="answers[<?= $q_id ?>]" id="q_<?= $q_id ?>_<?= $key ?>" value="<?= $key ?>" onchange="markAnswered(<?= $q_id ?>)">
                                                <label class="option-box w-100 mb-0" for="q_<?= $q_id ?>_<?= $key ?>">
                                                    <span class="badge bg-light text-dark border me-3 fs-6 px-3 py-2"><?= $key ?></span>
                                                    <span><?= htmlspecialchars($val) ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- THANH ĐIỀU HƯỚNG PHÂN TRANG -->
                <div class="d-flex flex-wrap justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-4 gap-3">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-semibold" id="btnPrevPage" onclick="changePage(-1)">
                        <i class="bi bi-arrow-left me-1"></i> Trang trước
                    </button>

                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <label class="fw-semibold text-muted small mb-0">Hiển thị:</label>
                            <select id="questionsPerPage" class="form-select form-select-sm rounded-3" style="width: auto;" onchange="changePerPage()">
                                <option value="1" selected>1 câu / trang</option>
                                <option value="5">5 câu / trang</option>
                                <option value="10">10 câu / trang</option>
                                <option value="all">Tất cả câu hỏi</option>
                            </select>
                        </div>
                        <span class="text-muted fw-semibold small" id="pageIndicator">Trang 1 / 1</span>
                    </div>

                    <button type="button" class="btn btn-primary px-4 rounded-pill fw-semibold" id="btnNextPage" onclick="changePage(1)">
                        Trang sau <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- Cột Bảng Điều Hướng (Bên Phải) -->
            <div class="col-lg-4 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 sticky-sidebar p-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Danh sách câu hỏi</h6>
                    
                    <div class="d-flex flex-wrap gap-2 mb-4" style="max-height: 350px; overflow-y: auto;">
                        <?php foreach ($questions as $index => $q): ?>
                            <button type="button" class="nav-grid-btn" id="nav-btn-<?= $q['id'] ?>" onclick="goToQuestion(<?= $index ?>)">
                                <?= $index + 1 ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex align-items-center gap-3 small text-muted mb-3">
                            <span class="d-flex align-items-center"><span class="badge bg-primary p-2 me-1"></span> Đã làm</span>
                            <span class="d-flex align-items-center"><span class="badge bg-warning p-2 me-1"></span> Đánh dấu</span>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold bg-gradient-purple border-0 shadow" onclick="return confirm('Bạn có chắc chắn muốn nộp bài thi?')">
                            <i class="bi bi-send-fill me-2"></i>NỘP BÀI THI
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL BÁO CÁO CÂU HỎI -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient-purple text-white rounded-top-4">
                <h5 class="modal-header-title modal-title fw-bold" id="reportModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Báo cáo câu hỏi <span id="reportQuestionNum"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="reportQuestionId" value="">
                <input type="hidden" id="reportExamId" value="<?= $exam_id ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Loại lỗi gặp phải:</label>
                    <select class="form-select rounded-3" id="reportType">
                        <option value="Sai đáp án">Sai đáp án / Lỗi đáp án</option>
                        <option value="Sai đề bài">Sai đề bài / Trùng câu hỏi</option>
                        <option value="Lỗi hiển thị">Lỗi hiển thị / Mất chữ / Lỗi hình ảnh</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nội dung góp ý / Chi tiết lỗi:</label>
                    <textarea class="form-control rounded-3" id="reportContent" rows="4" placeholder="Mô tả chi tiết nội dung bị lỗi..."></textarea>
                </div>
                <div id="reportAlert" class="alert d-none py-2 px-3 mb-0 rounded-3 small"></div>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary bg-gradient-purple border-0 rounded-pill px-4 fw-semibold" id="btnSubmitReport" onclick="executeSubmitReport()">
                    <i class="bi bi-send me-1"></i>Gửi báo cáo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CẢNH BÁO GIAN LẬN -->
<div class="modal fade" id="cheatWarningModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-exclamation me-2"></i>CẢNH BÁO VI PHẠM QUY CHẾ</h5>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="display-1 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold text-dark mb-2" id="cheatWarningTitle">Phát hiện hành vi gian lận!</h5>
                <p class="text-muted mb-3" id="cheatWarningMessage">Bạn vừa rời khỏi màn hình làm bài thi.</p>
                <div class="alert alert-warning py-2 rounded-3 fw-bold mb-0">
                    Số lần vi phạm: <span id="cheatCountDisplay" class="fs-5 text-danger">0</span>/3
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 justify-content-center">
                <button type="button" class="btn btn-danger px-4 rounded-pill fw-bold" onclick="resumeExam()">
                    ĐÃ HỌC QUY CHẾ & TẮT CẢNH BÁO
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let itemsPerPage = 1;
    const totalQuestions = <?= count($questions) ?>;

    function renderPage() {
        if (totalQuestions === 0) return;

        const selectValue = document.getElementById('questionsPerPage').value;
        itemsPerPage = (selectValue === 'all') ? totalQuestions : parseInt(selectValue);
        
        const totalPages = Math.max(1, Math.ceil(totalQuestions / itemsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        const allItems = document.querySelectorAll('.question-item');

        allItems.forEach((item, idx) => {
            if (idx >= startIndex && idx < endIndex) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        document.getElementById('pageIndicator').innerText = `Trang ${currentPage} / ${totalPages}`;
        document.getElementById('btnPrevPage').disabled = (currentPage === 1);
        document.getElementById('btnNextPage').disabled = (currentPage === totalPages);

        document.querySelectorAll('.nav-grid-btn').forEach(btn => btn.classList.remove('active-current'));
        for (let i = startIndex; i < endIndex && i < totalQuestions; i++) {
            const qId = allItems[i].getAttribute('data-id');
            const navBtn = document.getElementById('nav-btn-' + qId);
            if (navBtn) {
                navBtn.classList.add('active-current');
            }
        }
    }

    function changePage(direction) {
        currentPage += direction;
        renderPage();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function changePerPage() {
        currentPage = 1;
        renderPage();
    }

    function goToQuestion(questionIndex) {
        const selectValue = document.getElementById('questionsPerPage').value;
        itemsPerPage = (selectValue === 'all') ? totalQuestions : parseInt(selectValue);
        
        currentPage = Math.floor(questionIndex / itemsPerPage) + 1;
        renderPage();

        const targetElement = document.getElementById('q-item-' + questionIndex);
        if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    renderPage();

    // Đếm ngược thời gian
    let timeRemaining = <?= $total_seconds ?>;
    let timeElapsed = 0;

    const timerInterval = setInterval(() => {
        timeElapsed++;
        timeRemaining--;

        document.getElementById('duration_seconds').value = timeElapsed;

        let mins = Math.floor(Math.max(0, timeRemaining) / 60);
        let secs = Math.max(0, timeRemaining) % 60;
        
        document.getElementById('timer').innerText = 
            (mins < 10 ? "0" + mins : mins) + ":" + (secs < 10 ? "0" + secs : secs);

        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            alert("Đã hết thời gian làm bài! Hệ thống sẽ tự động nộp bài của bạn.");
            document.getElementById('examForm').submit();
        }
    }, 1000);

    function markAnswered(qId) {
        const btn = document.getElementById('nav-btn-' + qId);
        if (btn) btn.classList.add('answered');
    }

    function clearSelection(qId) {
        let radios = document.getElementsByName('answers[' + qId + ']');
        radios.forEach(r => r.checked = false);
        const btn = document.getElementById('nav-btn-' + qId);
        if (btn) btn.classList.remove('answered');
    }

    function toggleFlag(qId) {
        let card = document.getElementById('q-card-' + qId);
        let btn = document.getElementById('nav-btn-' + qId);
        if (card) card.classList.toggle('flagged');
        if (btn) btn.classList.toggle('flagged');
    }

    // XỬ LÝ BÁO CÁO CÂU HỎI
    const reportModalElem = document.getElementById('reportModal');

    if (reportModalElem) {
        reportModalElem.addEventListener('show.bs.modal', function () {
            const btnSubmit = document.getElementById('btnSubmitReport');
            const alertBox = document.getElementById('reportAlert');
            const contentInput = document.getElementById('reportContent');

            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="bi bi-send me-1"></i>Gửi báo cáo';
            }
            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.innerText = '';
            }
            if (contentInput) {
                contentInput.value = '';
            }
        });
    }

    function openReportModal(qId, qIndex) {
        document.getElementById('reportQuestionId').value = qId;
        document.getElementById('reportQuestionNum').innerText = qIndex;
        
        const reportModal = bootstrap.Modal.getOrCreateInstance(reportModalElem);
        reportModal.show();
    }

    function executeSubmitReport() {
        const questionId = document.getElementById('reportQuestionId').value;
        const examId = document.getElementById('reportExamId').value;
        const reportType = document.getElementById('reportType').value;
        const content = document.getElementById('reportContent').value.trim();
        const alertBox = document.getElementById('reportAlert');
        const btnSubmit = document.getElementById('btnSubmitReport');

        if (content === '') {
            alertBox.className = 'alert alert-danger py-2 px-3 mb-0 rounded-3 small';
            alertBox.innerText = 'Vui lòng nhập nội dung chi tiết lỗi!';
            alertBox.classList.remove('d-none');
            return;
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';

        const formData = new FormData();
        formData.append('question_id', questionId);
        formData.append('exam_id', examId);
        formData.append('report_type', reportType);
        formData.append('content', content);

        fetch('ajax_submit_report.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertBox.className = 'alert alert-success py-2 px-3 mb-0 rounded-3 small';
                alertBox.innerText = data.message;
                alertBox.classList.remove('d-none');

                setTimeout(() => {
                    const reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
                    if (reportModal) reportModal.hide();
                }, 1500);
            } else {
                alertBox.className = 'alert alert-danger py-2 px-3 mb-0 rounded-3 small';
                alertBox.innerText = data.message || 'Có lỗi xảy ra!';
                alertBox.classList.remove('d-none');
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="bi bi-send me-1"></i>Gửi báo cáo';
            }
        })
        .catch(error => {
            alertBox.className = 'alert alert-danger py-2 px-3 mb-0 rounded-3 small';
            alertBox.innerText = 'Lỗi kết nối máy chủ!';
            alertBox.classList.remove('d-none');

            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-send me-1"></i>Gửi báo cáo';
        });
    }

    // ==========================================
    // LOGIC CHỐNG GIAN LẬN (ANTI-CHEAT SYSTEM)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function () {
        let violationCount = 0;
        const maxViolations = 3;
        const examId = <?= $exam_id ?>;
        const cheatModalEl = document.getElementById('cheatWarningModal');
        const cheatModal = new bootstrap.Modal(cheatModalEl);

        function requestFullScreen() {
            const docEl = document.documentElement;
            if (docEl.requestFullscreen) {
                docEl.requestFullscreen().catch(err => console.log(err));
            } else if (docEl.mozRequestFullScreen) {
                docEl.mozRequestFullScreen();
            } else if (docEl.webkitRequestFullscreen) {
                docEl.webkitRequestFullscreen();
            } else if (docEl.msRequestFullscreen) {
                docEl.msRequestFullscreen();
            }
        }

        const enableFullscreenOnce = () => {
            requestFullScreen();
            document.removeEventListener('click', enableFullscreenOnce);
        };
        document.addEventListener('click', enableFullscreenOnce);

        function logCheatViolation() {
            const formData = new FormData();
            formData.append('exam_id', examId);

            fetch('ajax_log_cheat.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .catch(err => console.error('Lỗi ghi nhận vi phạm:', err));
        }

        function handleViolation(reason) {
            if (violationCount >= maxViolations) return;

            violationCount++;
            logCheatViolation();

            document.getElementById('cheatCountDisplay').innerText = violationCount;
            document.getElementById('cheatWarningMessage').innerText = reason;

            if (violationCount >= maxViolations) {
                document.getElementById('cheatWarningTitle').innerText = 'HỆ THỐNG TỰ ĐỘNG NỘP BÀI!';
                document.getElementById('cheatWarningMessage').innerText = 'Bạn đã vi phạm quy chế quá 3 lần. Bài thi sẽ tự động được gửi đi ngay lập tức!';
                cheatModal.show();
                
                setTimeout(() => {
                    document.getElementById('examForm').submit();
                }, 2000);
            } else {
                cheatModal.show();
            }
        }

        // Lắng nghe sự kiện chuyển tab / ẩn trang
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                handleViolation('Bạn đã chuyển tab hoặc thu nhỏ trình duyệt trong lúc làm bài!');
            }
        });

        // Lắng nghe sự kiện mất focus khỏi cửa sổ
        window.addEventListener('blur', function () {
            handleViolation('Bạn đã tương tác bên ngoài cửa sổ bài thi!');
        });

        // Lắng nghe thoát Fullscreen
        document.addEventListener('fullscreenchange', function () {
            if (!document.fullscreenElement && violationCount < maxViolations) {
                handleViolation('Bạn đã thoát khỏi chế độ toàn màn hình!');
            }
        });

        window.resumeExam = function () {
            cheatModal.hide();
            requestFullScreen();
        };

        // Chặn phím tắt F12, Ctrl+Shift+I/J/C, Ctrl+C/V/U, F5
        document.addEventListener('keydown', function (e) {
            if (
                e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c')) ||
                (e.ctrlKey && (e.key === 'u' || e.key === 'U' || e.key === 'c' || e.key === 'C' || e.key === 'v' || e.key === 'V')) ||
                e.key === 'F5' ||
                (e.ctrlKey && (e.key === 'r' || e.key === 'R'))
            ) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Chặn chuột phải
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    });
</script>

<?php require_once $rootDir . '/includes/footer.php'; ?>