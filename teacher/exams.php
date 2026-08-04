<?php
// teacher/exams.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

// -------------------------------------------------------------
// XỬ LÝ THÊM ĐỀ THI
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $subject_id     = (int)($_POST['subject_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $time_limit     = (int)($_POST['time_limit'] ?? 20);
    $question_count = (int)($_POST['question_count'] ?? 10);

    // Validate Dữ liệu
    if ($subject_id <= 0 || empty($title)) {
        setFlash('danger', 'Vui lòng chọn môn học và nhập tiêu đề đề thi!');
    } elseif ($time_limit < 1 || $question_count < 1) {
        setFlash('danger', 'Thời gian làm bài và số lượng câu hỏi phải lớn hơn 0!');
    } else {
        try {
            // Sinh mã tự động cho Đề thi (Ví dụ: EXAM-8F32A)
            $exam_code = 'EXAM-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));

            $stmt = $pdo->prepare("INSERT INTO exams (subject_id, exam_code, title, description, time_limit, question_count, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$subject_id, $exam_code, $title, $description, $time_limit, $question_count]);
            
            setFlash('success', 'Đã thêm đề thi thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi CSDL: Không thể thêm đề thi mới!');
        }
        redirect('exams.php');
    }
}

// -------------------------------------------------------------
// XỬ LÝ XÓA ĐỀ THI
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$id]);

            resetAutoIncrement($pdo, 'exams');
            setFlash('success', 'Đã xóa đề thi!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi: Không thể xóa đề thi này!');
        }
    }
    redirect('exams.php');
}

$exams    = getExams();
$subjects = getSubjects();

$page_title = 'Quản lý đề thi';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Quản lý đề thi</h4>
                <p class="text-muted mb-0">Thêm, xem và xóa các đề thi trong hệ thống</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Thêm đề thi
            </button>
        </div>

        <!-- Bảng danh sách -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Tiêu đề</th>
                                <th>Môn</th>
                                <th>Số câu</th>
                                <th>Thời gian</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($exams)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Chưa có đề thi nào trong hệ thống.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($exams as $ex): ?>
                                    <tr>
                                        <td class="ps-3"><?= $ex['id'] ?></td>
                                        <td><strong><?= e($ex['title']) ?></strong></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary border"><?= e($ex['subject_name'] ?? 'N/A') ?></span></td>
                                        <td><?= $ex['question_count'] ?? 0 ?> câu</td>
                                        <td><?= $ex['time_limit'] ?? 20 ?> phút</td>
                                        <td class="text-end pe-3">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đề thi này?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa đề thi">
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

<!-- Modal thêm Đề thi -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="exams.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm đề thi mới</h5>
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
                        <label class="form-label fw-semibold">Tiêu đề đề thi <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="VD: Kiểm tra Giữa kỳ SQL" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Ghi chú thêm về bài thi..."></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Số câu hỏi</label>
                            <input type="number" name="question_count" class="form-control" value="10" min="1" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Thời gian (phút)</label>
                            <input type="number" name="time_limit" class="form-control" value="20" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu đề thi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>