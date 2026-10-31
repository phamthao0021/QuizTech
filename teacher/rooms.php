<?php
// teacher/rooms.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

$teacherId = (int)((function_exists('currentUser') ? (currentUser()['id'] ?? null) : null) ?? ($_SESSION['user_id'] ?? null) ?? ($_SESSION['user']['id'] ?? 0));

// -------------------------------------------------------------
// XỬ LÝ THÊM PHÒNG THI MỚI
// -------------------------------------------------------------
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $room_code    = strtoupper(trim($_POST['room_code'] ?? ''));
    $room_name    = trim($_POST['room_name'] ?? '');
    $exam_id      = (int)($_POST['exam_id'] ?? 0);
    $max_students = (int)($_POST['max_students'] ?? 50);
    $start_time   = $_POST['start_time'] ?? date('Y-m-d H:i:s');
    $end_time     = $_POST['end_time'] ?? date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Check if exam belongs to teacher
    $stmtEx = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND teacher_id = ?");
    $stmtEx->execute([$exam_id, $teacherId]);
    if (!$stmtEx->fetch()) {
        setFlash('danger', 'Đề thi không hợp lệ hoặc bạn không có quyền!');
        redirect('rooms.php');
    }

    if (empty($room_code) || empty($room_name)) {
        setFlash('danger', 'Vui lòng nhập đầy đủ Mã phòng và Tên phòng thi!');
    } elseif ($max_students < 1) {
        setFlash('danger', 'Số lượng người tham gia phải lớn hơn 0!');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO exam_rooms (exam_id, teacher_id, room_code, room_name, max_students, start_time, end_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'waiting', NOW())");
            $stmt->execute([$exam_id, $teacherId, $room_code, $room_name, $max_students, $start_time, $end_time]);

            setFlash('success', 'Đã tạo phòng thi mới thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi CSDL: Mã phòng thi có thể đã tồn tại!');
        }
        redirect('rooms.php');
    }
}

// -------------------------------------------------------------
// XỬ LÝ XÓA PHÒNG THI
// -------------------------------------------------------------
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmtChk = $pdo->prepare("SELECT id FROM exam_rooms WHERE id = ? AND teacher_id = ?");
        $stmtChk->execute([$id, $teacherId]);
        if (!$stmtChk->fetch()) {
            setFlash('danger', 'Bạn không có quyền xóa phòng thi này!');
            redirect('rooms.php');
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM exam_rooms WHERE id = ?");
            $stmt->execute([$id]);

            resetAutoIncrement($pdo, 'exam_rooms');
            setFlash('success', 'Đã xóa phòng thi thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi: Không thể xóa phòng thi (có thể đã có kết quả)!');
        }
    }
    redirect('rooms.php');
}

// Lấy danh sách phòng thi của giáo viên này
$stmt = $pdo->prepare("
    SELECT r.*, e.title as exam_title 
    FROM exam_rooms r 
    LEFT JOIN exams e ON r.exam_id = e.id 
    WHERE r.teacher_id = ?
    ORDER BY r.id DESC
");
$stmt->execute([$teacherId]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách đề thi của giáo viên
$stmtEx = $pdo->prepare("SELECT id, title FROM exams WHERE teacher_id = ? ORDER BY id DESC");
$stmtEx->execute([$teacherId]);
$exams = $stmtEx->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Quản lý phòng thi';
include '../includes/header_teacher.php';
?>

<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-rooms">
<section class="qt-t-hero" aria-label="Giới thiệu trang"><div class="qt-t-hero-icon"><i class="bi bi-door-open"></i></div><div class="qt-t-hero-copy"><div class="qt-t-eyebrow">QUIZTECH · TEACHER</div><h1>Quản lý phòng thi</h1><p>Tổ chức và vận hành các phòng thi</p></div><a class="qt-t-hero-home" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a><button type="button" class="qt-r-hero-create" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-door-open"></i> Mở phòng thi mới</button></section>
    <!-- Bảng danh sách phòng thi -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <div class="qt-shared-toolbar" data-qt-table-toolbar><label class="qt-shared-search"><i class="bi bi-search"></i><input type="search" placeholder="Tìm tên phòng, mã phòng, đề thi..." aria-label="Tìm kiếm dữ liệu"></label><select class="qt-shared-filter" data-filter="status" aria-label="Lọc trạng thái phòng"><option value="">Tất cả trạng thái</option><option value="waiting">Chờ bắt đầu</option><option value="running">Đang thi</option><option value="closed">Đã đóng</option><option value="finished">Hoàn thành</option><option value="cancelled">Đã hủy</option></select><select class="qt-shared-sort" aria-label="Sắp xếp"><option value="original">Mặc định</option><option value="az">A–Z</option><option value="za">Z–A</option></select><button type="button" class="qt-shared-refresh"><i class="bi bi-arrow-clockwise"></i> Làm mới</button></div><table class="table table-striped table-hover align-middle mb-0 qt-enhanced-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Phòng thi</th>
                            <th>Mã phòng</th>
                            <th>Đề thi liên kết</th>
                            <th class="text-center">Số người tối đa</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Chưa có phòng thi nào được mở.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $r): ?>
                                <tr data-qt-status="<?= e(strtolower((string)($r['status'] ?? 'waiting'))) ?>">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-primary bg-opacity-10 rounded text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <i class="bi bi-door-open fs-4"></i>
                                            </div>
                                            <div>
                                                <strong class="d-block text-dark"><?= e($r['room_name']) ?></strong>
                                                <small class="text-muted">ID: #<?= $r['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark-subtle text-dark border px-2 py-1 font-monospace">
                                            <?= e($r['room_code']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-secondary fw-semibold">
                                            <?= e($r['exam_title'] ?: 'Chưa gán đề thi') ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <i class="bi bi-people me-1 text-muted"></i><?= $r['max_students'] ?? 50 ?>
                                    </td>
                                    <td class="text-center">
                                        <?= status_badge($r['status'] ?? 'waiting') ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng thi này?')">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa phòng thi">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table><div class="qt-shared-pagination"><span class="qt-shared-count"></span><label>Hiển thị <select class="qt-shared-pagesize"><option>5</option><option selected>10</option><option>20</option><option>50</option></select></label><nav class="qt-shared-pages" aria-label="Phân trang"></nav></div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Mở Phòng Thi -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="rooms.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary">Mở phòng thi mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mã phòng thi <span class="text-danger">*</span></label>
                                <input type="text" name="room_code" class="form-control text-uppercase" placeholder="VD: PT-101" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tên phòng thi <span class="text-danger">*</span></label>
                                <input type="text" name="room_name" class="form-control" placeholder="VD: Phòng thi Lập trình Web - Nhóm 1" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Chọn đề thi</label>
                                <select name="exam_id" class="form-select" required>
                                    <option value="">-- Chọn đề thi --</option>
                                    <?php foreach ($exams as $e): ?>
                                        <option value="<?= $e['id'] ?>"><?= e($e['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Số người tối đa</label>
                                <input type="number" name="max_students" class="form-control" value="50" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary px-4">Tạo phòng thi</button>
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
