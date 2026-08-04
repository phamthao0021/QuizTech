<?php
// teacher/subjects.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

// -------------------------------------------------------------
// XỬ LÝ THÊM MÔN HỌC MỚI
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $name        = trim($_POST['name'] ?? '');
    $code        = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($code)) {
        setFlash('danger', 'Vui lòng nhập Tên môn học và Mã môn học!');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (code, name, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$code, $name, $description]);

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    verify_csrf();

    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $code        = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0 || empty($name) || empty($code)) {
        setFlash('danger', 'Dữ liệu không hợp lệ!');
    } else {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
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

                resetAutoIncrement($pdo, 'subjects');
                setFlash('success', 'Đã xóa môn học thành công!');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi: Không thể xóa môn học này!');
        }
    }
    redirect('subjects.php');
}

// Lấy danh sách môn học kèm số lượng câu hỏi hiện có
$stmt = $pdo->query("
    SELECT s.*, COUNT(q.id) AS total_questions 
    FROM subjects s 
    LEFT JOIN questions q ON s.id = q.subject_id 
    GROUP BY s.id 
    ORDER BY s.id DESC
");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Quản lý môn học';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Quản lý danh mục Môn học</h4>
                <p class="text-muted mb-0">Quản lý danh sách môn học và phân loại ngân hàng câu hỏi</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                <i class="bi bi-journal-plus me-1"></i> Thêm môn học mới
            </button>
        </div>

        <!-- Bảng danh sách Môn học -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
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
                                    <td colspan="6" class="text-center text-muted py-4">Chưa có môn học nào trong hệ thống.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $s): ?>
                                    <tr>
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
                                                    title="Sửa môn học">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <!-- Form Xóa -->
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa môn học này?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa môn học">
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
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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

<?php include '../includes/footer.php'; ?>