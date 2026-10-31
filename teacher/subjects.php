<?php
// teacher/subjects.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

$teacherId = (int)((function_exists('currentUser') ? (currentUser()['id'] ?? null) : null) ?? ($_SESSION['user_id'] ?? null) ?? ($_SESSION['user']['id'] ?? 0));

// -------------------------------------------------------------
// XỬ LÝ THÊM MÔN HỌC MỚI
// -------------------------------------------------------------
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $name        = trim($_POST['name'] ?? '');
    $code        = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($code)) {
        setFlash('danger', 'Vui lòng nhập Tên môn học và Mã môn học!');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (code, name, description, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$code, $name, $description, $teacherId]);

            // Tự động phân công môn này cho teacher
            $newSubjectId = $pdo->lastInsertId();
            $stmtAssign = $pdo->prepare("INSERT INTO teacher_subjects (subject_id, teacher_id) VALUES (?, ?)");
            $stmtAssign->execute([$newSubjectId, $teacherId]);

            setFlash('success', 'Đã thêm môn học mới thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi CSDL: Mã môn học có thể đã tồn tại!');
        }
        redirect('subjects.php');
    }
}

// -------------------------------------------------------------
// XỬ LÝ CẬP NHẬT MÔN HỌC (SỬA)
// -------------------------------------------------------------
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    verify_csrf();

    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $code        = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0 || empty($name) || empty($code)) {
        setFlash('danger', 'Dữ liệu không hợp lệ!');
    } else {
        // Kiểm tra quyền sở hữu hoặc phân công
        $stmtChk = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmtChk->execute([$id, $teacherId]);
        if (!$stmtChk->fetch()) {
            setFlash('danger', 'Bạn không có quyền sửa môn học này!');
            redirect('subjects.php');
        }

        try {
            $stmt = $pdo->prepare("UPDATE subjects SET code = ?, name = ?, description = ? WHERE id = ?");
            $stmt->execute([$code, $name, $description, $id]);

            setFlash('success', 'Đã cập nhật thông tin môn học thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi CSDL: Không thể cập nhật môn học!');
        }
        redirect('subjects.php');
    }
}

// -------------------------------------------------------------
// XỬ LÝ XÓA MÔN HỌC
// -------------------------------------------------------------
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        // Chỉ được xóa nếu là người tạo
        $stmtChk = $pdo->prepare("SELECT created_by FROM subjects WHERE id = ?");
        $stmtChk->execute([$id]);
        $subj = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if (!$subj || $subj['created_by'] != $teacherId) {
            setFlash('danger', 'Bạn không có quyền xóa môn học này!');
            redirect('subjects.php');
        }

        try {
            // Kiểm tra xem môn học có chứa câu hỏi nào không
            $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE subject_id = ?");
            $chkStmt->execute([$id]);
            $qCount = $chkStmt->fetchColumn();

            if ($qCount > 0) {
                setFlash('danger', "Không thể xóa môn học này vì đang chứa {$qCount} câu hỏi!");
            } else {
                $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                $stmt->execute([$id]);

                setFlash('success', 'Đã xóa môn học thành công!');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi: Không thể xóa môn học này!');
        }
    }
    redirect('subjects.php');
}

// Lấy danh sách môn học kèm số lượng câu hỏi hiện có (chỉ thuộc về Teacher)
$stmt = $pdo->prepare("
    SELECT s.*, COUNT(q.id) AS total_questions 
    FROM subjects s 
    LEFT JOIN questions q ON s.id = q.subject_id 
    WHERE s.created_by = ? OR s.id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?)
    GROUP BY s.id 
    ORDER BY s.id DESC
");
$stmt->execute([$teacherId, $teacherId]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Quản lý môn học';
include '../includes/header_teacher.php';
?>

<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-subjects">
<section class="qt-t-hero" aria-label="Giới thiệu trang"><div class="qt-t-hero-icon"><i class="bi bi-journal-bookmark"></i></div><div class="qt-t-hero-copy"><div class="qt-t-eyebrow">QUIZTECH · TEACHER</div><h1>Quản lý môn học</h1><p>Tổ chức môn học và nội dung giảng dạy</p></div><a class="qt-t-hero-home" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a><button type="button" class="qt-r-hero-create" data-bs-toggle="modal" data-bs-target="#addSubjectModal"><i class="bi bi-journal-plus"></i> Thêm môn học mới</button></section>
    <!-- Bảng danh sách Môn học -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <div class="qt-shared-toolbar" data-qt-table-toolbar><label class="qt-shared-search"><i class="bi bi-search"></i><input type="search" placeholder="Tìm tên môn, mã môn, mô tả..." aria-label="Tìm kiếm dữ liệu"></label><select class="qt-shared-filter" data-filter="ownership" aria-label="Lọc môn học theo quyền"><option value="">Tất cả môn học</option><option value="mine">Môn do tôi tạo</option><option value="assigned">Môn được phân công</option></select><select class="qt-shared-filter" data-filter="questions" aria-label="Lọc số câu hỏi"><option value="">Tất cả số câu</option><option value="has">Có câu hỏi</option><option value="empty">Chưa có câu hỏi</option></select><select class="qt-shared-sort" aria-label="Sắp xếp"><option value="original">Mặc định</option><option value="az">A–Z</option><option value="za">Z–A</option></select><button type="button" class="qt-shared-refresh"><i class="bi bi-arrow-clockwise"></i> Làm mới</button></div><table class="table table-striped table-hover align-middle mb-0 qt-enhanced-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 70px;">ID</th>
                            <th>Mã môn</th>
                            <th>Tên môn học</th>
                            <th>Mô tả / Ghi chú</th>
                            <th class="text-center">Số câu hỏi</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Bạn chưa được phân công môn học nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $s): ?>
                                <tr data-qt-ownership="<?= (int)$s['created_by'] === $teacherId ? 'mine' : 'assigned' ?>" data-qt-questions="<?= (int)$s['total_questions'] ?>">
                                    <td class="ps-3 fw-bold text-muted">#<?= $s['id'] ?></td>
                                    <td>
                                        <span class="badge bg-dark-subtle text-dark border font-monospace">
                                            <?= e($s['code']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-primary">
                                        <?= e($s['name']) ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= e(mb_strimwidth($s['description'] ?? '', 0, 60, '...')) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-6">
                                            <i class="bi bi-question-circle me-1"></i><?= $s['total_questions'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <!-- Nút Sửa -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?= $s['id'] ?>"
                                                title="Sửa môn học" <?= ((int)$s['created_by'] !== $teacherId) ? 'disabled' : '' ?>>
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Form Xóa -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa môn học này?')">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa môn học" <?= ($s['created_by'] != $teacherId) ? 'disabled' : '' ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal Sửa Môn Học -->
                                <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <form method="POST" action="subjects.php">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">

                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold text-primary">Cập nhật Môn học #<?= $s['id'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Mã môn học <span class="text-danger">*</span></label>
                                                        <input type="text" name="code" class="form-control text-uppercase" value="<?= e($s['code']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Tên môn học <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="<?= e($s['name']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Mô tả môn học</label>
                                                        <textarea name="description" class="form-control" rows="3"><?= e($s['description'] ?? '') ?></textarea>
                                                    </div>
                                                </div>

                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-primary px-4">Lưu thay đổi</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table><div class="qt-shared-pagination"><span class="qt-shared-count"></span><label>Hiển thị <select class="qt-shared-pagesize"><option>5</option><option selected>10</option><option>20</option><option>50</option></select></label><nav class="qt-shared-pages" aria-label="Phân trang"></nav></div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Thêm Môn Học Mới -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="subjects.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary">Thêm môn học mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mã môn học <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="VD: CNTT101, ENG01..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên môn học <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Lập trình Web PHP, Tiếng Anh Chuyên Ngành..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả môn học</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả tóm tắt môn học..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary px-4">Tạo môn học</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
echo '</div></div>';
include '../includes/footer.php'; 
?>
<script src="../assets/js/qt-teacher-pages.js?v=20261025" defer></script>
