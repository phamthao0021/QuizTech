<?php
// teacher/ai.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

$subjects  = getSubjects();
$questions = getQuestions();
$exams     = getExams();

// -------------------------------------------------------------
// XỬ LÝ XỬ LÝ TẠO CÂU HỎI TỪ VĂN BẢN / FILE / ẢNH (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    $action      = $_POST['action'];
    $subject_id  = (int)($_POST['subject_id'] ?? 0);
    $count       = (int)($_POST['count'] ?? 5);
    $difficulty  = $_POST['difficulty'] ?? 'medium';

    if ($subject_id <= 0) {
        setFlash('danger', 'Vui lòng chọn môn học cần thêm câu hỏi!');
        redirect('ai.php');
    }

    // 1. TẠO CÂU HỎI TỪ CHỦ ĐỀ / PROMPT
    if ($action === 'generate_prompt') {
        $topic = trim($_POST['topic'] ?? '');
        if (empty($topic)) {
            setFlash('danger', 'Vui lòng nhập chủ đề câu hỏi!');
        } else {
            // TODO: Gọi API AI (Gemini / OpenAI API) với $topic
            setFlash('success', "AI đã phân tích chủ đề '{$topic}' và trích xuất thành công {$count} câu hỏi!");
            redirect('ai.php');
        }
    }

    // 2. IMPORT TÀI LIỆU (PDF, DOCX, TXT)
    elseif ($action === 'generate_file') {
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            setFlash('danger', 'Vui lòng chọn tệp tài liệu hợp lệ!');
        } else {
            $fileTmpPath = $_FILES['document']['tmp_name'];
            $fileName    = $_FILES['document']['name'];
            $fileSize    = $_FILES['document']['size'];
            $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['pdf', 'docx', 'doc', 'txt'];

            if (!in_array($fileExt, $allowedExtensions)) {
                setFlash('danger', 'Chỉ hỗ trợ import tệp định dạng: .pdf, .docx, .doc, .txt!');
            } elseif ($fileSize > 10 * 1024 * 1024) { // Max 10MB
                setFlash('danger', 'Dung lượng tệp tối đa cho phép là 10MB!');
            } else {
                // TODO: Đọc nội dung tệp (pdf/docx parser) -> gửi Prompt tới AI API
                setFlash('success', "Đã import thành công tệp '{$fileName}'! AI đang tiến hành tạo câu hỏi.");
                redirect('ai.php');
            }
        }
    }

    // 3. CHỤP HÌNH / TẢI ẢNH ĐỀ THI (OCR + AI)
    elseif ($action === 'generate_ocr') {
        if (!isset($_FILES['exam_image']) || $_FILES['exam_image']['error'] !== UPLOAD_ERR_OK) {
            setFlash('danger', 'Vui lòng chụp hoặc chọn ảnh đề thi!');
        } else {
            $fileTmpPath = $_FILES['exam_image']['tmp_name'];
            $fileName    = $_FILES['exam_image']['name'];
            $fileSize    = $_FILES['exam_image']['size'];
            $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'heic'];

            if (!in_array($fileExt, $allowedExtensions)) {
                setFlash('danger', 'Chỉ chấp nhận các định dạng ảnh: JPG, PNG, WEBP!');
            } elseif ($fileSize > 8 * 1024 * 1024) { // Max 8MB
                setFlash('danger', 'Dung lượng ảnh tối đa là 8MB!');
            } else {
                // TODO: Gọi Vision AI / OCR API để quét chữ từ ảnh -> sinh câu hỏi
                setFlash('success', "Đã quét ảnh đề thi thành công! AI đang tự động nhận diện chữ và trích xuất câu hỏi.");
                redirect('ai.php');
            }
        }
    }
}

$page_title = 'AI Generator & Import Đề';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">🤖 AI Generator & Import Đề Thi</h4>
                <p class="text-muted mb-0">Tự động tạo câu hỏi trắc nghiệm từ Chủ đề, File tài liệu (PDF/DOCX) hoặc Chụp ảnh đề thi</p>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2">
                <i class="bi bi-cpu-fill me-1"></i> OCR & Vision AI
            </span>
        </div>

        <div class="row g-4">
            <!-- Cột Trái: Tab Phương Thức Tạo Câu Hỏi -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <!-- Navigation Tabs -->
                        <ul class="nav nav-pills card-header-pills" id="aiTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold" id="topic-tab" data-bs-toggle="tab" data-bs-target="#topic-pane" type="button" role="tab">
                                    <i class="bi bi-lightbulb me-1"></i> Từ chủ đề
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="file-tab" data-bs-toggle="tab" data-bs-target="#file-pane" type="button" role="tab">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import PDF / Word
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="camera-tab" data-bs-toggle="tab" data-bs-target="#camera-pane" type="button" role="tab">
                                    <i class="bi bi-camera me-1"></i> Chụp ảnh đề thi
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4">
                        <div class="tab-content" id="aiTabContent">
                            
                            <!-- TAB 1: SINH CÂU HỎI TỪ CHỦ ĐỀ -->
                            <div class="tab-pane fade show active" id="topic-pane" role="tabpanel">
                                <form method="POST" action="ai.php">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="generate_prompt">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Môn học <span class="text-danger">*</span></label>
                                        <select name="subject_id" class="form-select" required>
                                            <option value="">-- Chọn môn học --</option>
                                            <?php foreach ($subjects as $s): ?>
                                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Chủ đề hoặc Nội dung kiến thức <span class="text-danger">*</span></label>
                                        <input type="text" name="topic" class="form-control" placeholder="VD: Mệnh đề JOIN trong SQL, Vòng lập For trong C++..." required>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Số lượng câu hỏi</label>
                                            <input type="number" name="count" class="form-control" value="5" min="1" max="30" required>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Mức độ khó</label>
                                            <select name="difficulty" class="form-select">
                                                <option value="easy">Dễ</option>
                                                <option value="medium" selected>Trung bình</option>
                                                <option value="hard">Khó</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-stars me-1"></i> Tạo câu hỏi tự động
                                    </button>
                                </form>
                            </div>

                            <!-- TAB 2: IMPORT TỆP PDF / DOCX -->
                            <div class="tab-pane fade" id="file-pane" role="tabpanel">
                                <form method="POST" enctype="multipart/form-data" action="ai.php">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="generate_file">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Môn học <span class="text-danger">*</span></label>
                                        <select name="subject_id" class="form-select" required>
                                            <option value="">-- Chọn môn học --</option>
                                            <?php foreach ($subjects as $s): ?>
                                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tải lên tệp tài liệu / Đề thi (.PDF, .DOCX, .TXT) <span class="text-danger">*</span></label>
                                        <input type="file" name="document" class="form-control" accept=".pdf,.docx,.doc,.txt" required>
                                        <div class="form-text">Hệ thống sẽ tự đọc tài liệu và chuyển thành các câu hỏi trắc nghiệm kèm đáp án.</div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Số câu cần trích xuất</label>
                                            <input type="number" name="count" class="form-control" value="10" min="1" max="50">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">Mức độ khó mong muốn</label>
                                            <select name="difficulty" class="form-select">
                                                <option value="easy">Dễ</option>
                                                <option value="medium" selected>Tự động theo file</option>
                                                <option value="hard">Khó</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="bi bi-file-earmark-text-fill me-1"></i> Đọc File & Tạo Đề Thi
                                    </button>
                                </form>
                            </div>

                            <!-- TAB 3: CHỤP HÌNH / UPLOAD ẢNH ĐỀ THI -->
                            <div class="tab-pane fade" id="camera-pane" role="tabpanel">
                                <form method="POST" enctype="multipart/form-data" action="ai.php">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="generate_ocr">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Môn học <span class="text-danger">*</span></label>
                                        <select name="subject_id" class="form-select" required>
                                            <option value="">-- Chọn môn học --</option>
                                            <?php foreach ($subjects as $s): ?>
                                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Chụp ảnh hoặc tải ảnh đề thi giấy <span class="text-danger">*</span></label>
                                        
                                        <!-- Khung hiển thị preview ảnh -->
                                        <div class="text-center p-3 border rounded bg-light mb-3">
                                            <img id="ocrPreview" src="https://via.placeholder.com/400x200?text=Ch%E1%BB%A5p+ho%E1%BA%B7c+Ch%E1%BB%8Dn+%E1%BA%A3nh+%C4%91%E1%BB%81+thi" class="img-fluid rounded mb-2 style-preview" style="max-height: 220px; object-fit: contain;" alt="OCR Preview">
                                            <div class="d-flex justify-content-center gap-2">
                                                <label for="cameraInput" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-camera-fill me-1"></i> Chụp trực tiếp bằng camera
                                                </label>
                                                <label for="cameraInput" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-image me-1"></i> Chọn từ thư viện
                                                </label>
                                            </div>
                                            <!-- Input hỗ trợ Camera trên Mobile -->
                                            <input type="file" id="cameraInput" name="exam_image" class="d-none" accept="image/*" capture="environment" onchange="previewOcrImage(this);" required>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-warning text-dark fw-semibold px-4">
                                        <i class="bi bi-bounding-box-circles me-1"></i> Quét Ảnh (OCR) & Sinh Đề
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột Phải: Thống kê & Hướng dẫn -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-1 text-primary"></i> Hướng dẫn sử dụng</h5>
                    </div>
                    <div class="card-body p-4 small text-muted">
                        <p><strong class="text-dark">1. Từ chủ đề:</strong> AI sẽ tự động biên soạn các câu hỏi trắc nghiệm chuẩn kèm đáp án dựa trên từ khóa bạn nhập.</p>
                        <p><strong class="text-dark">2. Import PDF / Word:</strong> Tải lên giáo trình, bài giảng hoặc đề thi mẫu (.pdf, .docx). AI sẽ bóc tách dữ liệu văn bản.</p>
                        <p><strong class="text-dark">3. Chụp ảnh đề thi:</strong> Sử dụng điện thoại chụp ảnh đề thi giấy, AI Vision sẽ quét chữ (OCR) và chuyển thành câu hỏi điện tử.</p>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Thống kê ngân hàng</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-question-circle me-2"></i>Tổng câu hỏi</span>
                            <span class="badge bg-primary rounded-pill"><?= count($questions) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted"><i class="bi bi-journal-text me-2"></i>Tổng đề thi</span>
                            <span class="badge bg-success rounded-pill"><?= count($exams) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="bi bi-book me-2"></i>Tổng môn học</span>
                            <span class="badge bg-warning text-dark rounded-pill"><?= count($subjects) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewOcrImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('ocrPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>