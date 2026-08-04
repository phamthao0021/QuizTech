<?php
// teacher/questions.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

// -------------------------------------------------------------
// XỬ LÝ THÊM CÂU HỎI MỚI
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $subject_id     = (int)($_POST['subject_id'] ?? 0);
    $content        = trim($_POST['content'] ?? '');
    $option_a       = trim($_POST['option_a'] ?? '');
    $option_b       = trim($_POST['option_b'] ?? '');
    $option_c       = trim($_POST['option_c'] ?? '');
    $option_d       = trim($_POST['option_d'] ?? '');
    $correct_answer = $_POST['correct_answer'] ?? 'A';
    $difficulty     = $_POST['difficulty'] ?? 'easy';
    $explanation    = trim($_POST['explanation'] ?? '');

    $allowed_answers   = ['A', 'B', 'C', 'D'];
    $allowed_difficult = ['easy', 'medium', 'hard'];

    // Ràng buộc & Kiểm tra tính hợp lệ
    if ($subject_id <= 0 || empty($content) || empty($option_a) || empty($option_b)) {
        setFlash('danger', 'Vui lòng chọn môn học, nhập nội dung câu hỏi và ít nhất 2 đáp án A, B!');
    } elseif (!in_array($correct_answer, $allowed_answers)) {
        setFlash('danger', 'Đáp án đúng không hợp lệ!');
    } elseif (!in_array($difficulty, $allowed_difficult)) {
        setFlash('danger', 'Mức độ khó không hợp lệ!');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO questions (subject_id, content, option_a, option_b, option_c, option_d, correct_answer, difficulty, explanation, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$subject_id, $content, $option_a, $option_b, $option_c, $option_d, $correct_answer, $difficulty, $explanation]);

            setFlash('success', 'Đã thêm câu hỏi mới thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi CSDL: Không thể lưu câu hỏi!');
        }
        redirect('questions.php');
    }
}

// -------------------------------------------------------------
// XỬ LÝ XÓA CÂU HỎI
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$id]);

            resetAutoIncrement($pdo, 'questions');
            setFlash('success', 'Đã xóa câu hỏi thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi: Không thể xóa câu hỏi này!');
        }
    }
    redirect('questions.php');
}

$questions = getQuestions();
$subjects  = getSubjects();

$page_title = 'Quản lý câu hỏi';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Quản lý câu hỏi</h4>
                <p class="text-muted mb-0">Thêm, sửa và quản lý ngân hàng câu hỏi trắc nghiệm</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Thêm câu hỏi
            </button>
        </div>

        <!-- Bảng Danh Sách -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 70px;">ID</th>
                                <th>Nội dung câu hỏi</th>
                                <th>Môn học</th>
                                <th class="text-center">Đáp án</th>
                                <th class="text-center">Độ khó</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Chưa có câu hỏi nào trong ngân hàng.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $q): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted">#<?= $q['id'] ?></td>
                                        <td>
                                            <span class="fw-semibold text-dark">
                                                <?= e(mb_strimwidth($q['content'], 0, 80, '...')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= e($q['subject_name'] ?? 'Chưa phân loại') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6">
                                                <?= e($q['correct_answer']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?= difficulty_badge($q['difficulty']) ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa câu hỏi">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Câu Hỏi -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="questions.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary">Thêm câu hỏi mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
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
                        <label class="form-label fw-semibold">Nội dung câu hỏi <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="3" placeholder="Nhập câu hỏi..." required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">A. Đáp án A <span class="text-danger">*</span></label>
                            <input type="text" name="option_a" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">B. Đáp án B <span class="text-danger">*</span></label>
                            <input type="text" name="option_b" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">C. Đáp án C</label>
                            <input type="text" name="option_c" class="form-control">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">D. Đáp án D</label>
                            <input type="text" name="option_d" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Đáp án đúng <span class="text-danger">*</span></label>
                            <select name="correct_answer" class="form-select">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Độ khó</label>
                            <select name="difficulty" class="form-select">
                                <option value="easy">Dễ</option>
                                <option value="medium" selected>Trung bình</option>
                                <option value="hard">Khó</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Giải thích chi tiết</label>
                            <input type="text" name="explanation" class="form-control" placeholder="Tùy chọn...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">Lưu câu hỏi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>