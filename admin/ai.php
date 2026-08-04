<?php
// admin/ai.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();
$message = '';
$error = '';
$ai_result = '';
$generated_questions = [];

// Xử lý sinh câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    verify_csrf();
    $subject = trim($_POST['subject'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $count = (int)($_POST['count'] ?? 5);
    $level = $_POST['level'] ?? 'Trung bình';

    if (empty($topic) || empty($subject)) {
        setFlash('danger', 'Vui lòng điền chủ đề và chọn môn học!');
    } else {
        // Sinh câu hỏi mẫu dựa theo cấu hình
        for ($i = 1; $i <= $count; $i++) {
            $generated_questions[] = [
                'question' => "[$level] Câu hỏi $i về chủ đề '$topic' trong môn $subject?",
                'options' => [
                    "A. Lựa chọn A cho câu $i", 
                    "B. Lựa chọn B cho câu $i", 
                    "C. Lựa chọn C cho câu $i", 
                    "D. Lựa chọn D cho câu $i"
                ],
                'correct' => 'A',
                'explanation' => "Giải thích mẫu cho đáp án A câu hỏi $i."
            ];
        }
        
        setFlash('success', 'Đã sinh thành công ' . count($generated_questions) . ' câu hỏi trắc nghiệm!');
    }
}

// Xử lý lưu câu hỏi vào cơ sở dữ liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_questions'])) {
    verify_csrf();
    $subject_name = $_POST['subject_name'] ?? '';
    $question_data = json_decode($_POST['question_data'] ?? '[]', true);
    
    if (empty($question_data)) {
        setFlash('danger', 'Không có câu hỏi nào để lưu!');
    } else {
        $subject_id = 0;
        foreach (getSubjects() as $s) {
            if ($s['name'] === $subject_name) {
                $subject_id = $s['id'];
                break;
            }
        }
        
        if ($subject_id > 0) {
            $saved = 0;
            foreach ($question_data as $q) {
                $stmt = $pdo->prepare("INSERT INTO questions (subject_id, content, option_a, option_b, option_c, option_d, correct_answer, difficulty, points, ai_generated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $option_a = preg_replace('/^[A-D]\.\s*/', '', $q['options'][0] ?? '');
                $option_b = preg_replace('/^[A-D]\.\s*/', '', $q['options'][1] ?? '');
                $option_c = preg_replace('/^[A-D]\.\s*/', '', $q['options'][2] ?? '');
                $option_d = preg_replace('/^[A-D]\.\s*/', '', $q['options'][3] ?? '');
                $correct = $q['correct'] ?? 'A';
                
                if ($stmt->execute([$subject_id, $q['question'], $option_a, $option_b, $option_c, $option_d, $correct, 'medium', 1])) {
                    $saved++;
                }
            }
            setFlash('success', "Đã lưu thành công $saved câu hỏi vào ngân hàng!");
            $generated_questions = [];
        } else {
            setFlash('danger', 'Môn học không hợp lệ!');
        }
    }
}

$subjects = getSubjects();
$questions = getQuestions();

$page_title = 'AI Generator';
include '../includes/header.php';
?>
<style>
  /* ============================================
   RÀNG BUỘC & MARGIN PADDING CHO AI.PHP
   ============================================ */

/* Khung Card chính của trang AI */
.ai-generator-card {
    border: none;
    border-radius: var(--radius) !important;
    box-shadow: var(--shadow);
    background: #ffffff;
    margin-bottom: 1.5rem;
}

.ai-generator-card .card-body {
    padding: 2rem !important; /* Spacing thoáng cho form cấu hình AI */
}

/* Header & Banner hướng dẫn */
.ai-header-info {
    background: linear-gradient(135deg, rgba(108, 92, 231, 0.08) 0%, rgba(162, 155, 254, 0.12) 100%);
    border-left: 4px solid var(--primary);
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

/* Định dạng Form Group & Input */
.ai-form-group {
    margin-bottom: 1.25rem; /* Margins chuẩn giữa các trường */
}

.ai-form-group .form-label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dark);
    font-size: 0.925rem;
}

/* Ô nhập liệu Textarea cho Prompt/Chủ đề */
.ai-generator-card textarea.form-control {
    padding: 0.75rem 1rem;
    line-height: 1.5;
    border-radius: 8px;
    resize: vertical;
    min-height: 100px;
}

/* Nút Submit Sinh câu hỏi */
.btn-ai-generate {
    min-width: 200px;
    padding: 0.625rem 1.75rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    border-radius: 8px;
}

/* Alert thông báo kết quả */
.ai-generator-card .alert {
    padding: 0.875rem 1.25rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
}

/* ============================================
   RESPONSIVE BREAKPOINTS CHO AI.PHP
   ============================================ */
@media (max-width: 767.98px) {
    /* Thu nhỏ padding trong card khi xem trên điện thoại */
    .ai-generator-card .card-body {
        padding: 1.25rem !important;
    }

    .ai-header-info {
        padding: 0.875rem 1rem;
        margin-bottom: 1.25rem;
    }

    .ai-form-group {
        margin-bottom: 1rem;
    }

    /* Ràng buộc Nút bấm full-width trên Mobile */
    .btn-ai-generate {
        width: 100% !important;
        margin-top: 0.5rem;
    }
}
</style>
<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <h4 class="m-0 fw-bold"><i class="bi bi-robot text-primary me-2"></i>Tạo câu hỏi tự động bằng AI</h4>
        <span class="badge bg-primary fs-6">Phiên bản AI v1.0</span>
    </div>

    <div class="row">
        <div class="col-lg-9 col-xl-8 mx-auto">
            <!-- Card bọc Form với ràng buộc CSS mới -->
            <div class="card ai-generator-card">
                <div class="card-body">
                    
                    <!-- Banner hướng dẫn ngắn -->
                    <div class="ai-header-info d-flex align-items-center gap-3">
                        <i class="bi bi-magic fs-3 text-primary"></i>
                        <div>
                            <div class="fw-bold">Sinh câu hỏi trắc nghiệm tự động</div>
                            <div class="small text-muted">Nhập chủ đề và yêu cầu, AI sẽ tự động phân tích và tạo bộ câu hỏi kèm đáp án chuẩn xác.</div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Chọn môn học -->
                        <div class="ai-form-group">
                            <label class="form-label required">Môn học</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Chủ đề / Từ khóa -->
                        <div class="ai-form-group">
                            <label class="form-label required">Chủ đề hoặc Yêu cầu chi tiết</label>
                            <textarea name="topic" class="form-control" rows="3" placeholder="Ví dụ: Vòng lặp For và While trong ngôn ngữ Lập trình PHP..." required></textarea>
                        </div>

                        <div class="row">
                            <!-- Số lượng câu hỏi -->
                            <div class="col-md-6 ai-form-group">
                                <label class="form-label required">Số lượng câu hỏi</label>
                                <input type="number" name="num_questions" class="form-control" min="1" max="20" value="5" required>
                            </div>

                            <!-- Độ khó -->
                            <div class="col-md-6 ai-form-group">
                                <label class="form-label">Mức độ khó</label>
                                <select name="difficulty" class="form-select">
                                    <option value="Dễ">Dễ</option>
                                    <option value="Trung bình" selected>Trung bình</option>
                                    <option value="Khó">Khó</option>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-end mt-3">
                            <button type="submit" name="generate_ai" class="btn btn-grad btn-ai-generate">
                                <i class="bi bi-magic me-2"></i>Bắt đầu sinh câu hỏi
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>