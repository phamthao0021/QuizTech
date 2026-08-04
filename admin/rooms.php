<?php
// admin/rooms.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

// Xử lý POST request (Thêm / Sửa / Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action = $_POST['action'];

    // 1. THÊM PHÒNG THI
    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        $max_players = (int)($_POST['max_players'] ?? 10);

        if (empty($code) || empty($name)) {
            setFlash('danger', 'Vui lòng nhập đầy đủ mã phòng và tên phòng!');
        } else {
            // Kiểm tra mã phòng đã tồn tại chưa
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE code = ?");
            $checkStmt->execute([$code]);
            if ($checkStmt->fetchColumn() > 0) {
                setFlash('danger', 'Mã phòng thi đã tồn tại!');
            } else {
                $stmt = $pdo->prepare("INSERT INTO rooms (code, name, exam_id, max_players, status) VALUES (?, ?, ?, ?, 'waiting')");
                if ($stmt->execute([$code, $name, $exam_id, $max_players])) {
                    setFlash('success', 'Đã mở phòng thi mới thành công!');
                } else {
                    setFlash('danger', 'Có lỗi xảy ra khi thêm phòng thi!');
                }
            }
        }
        redirect('rooms.php');
    }

    // 2. SỬA PHÒNG THI
    if ($action === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        $max_players = (int)($_POST['max_players'] ?? 10);
        $status = $_POST['status'] ?? 'waiting';

        if (empty($name)) {
            setFlash('danger', 'Tên phòng không được để trống!');
        } else {
            $stmt = $pdo->prepare("UPDATE rooms SET name = ?, exam_id = ?, max_players = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $exam_id, $max_players, $status, $id])) {
                setFlash('success', 'Cập nhật phòng thi thành công!');
            } else {
                setFlash('danger', 'Không thể cập nhật thông tin phòng thi!');
            }
        }
        redirect('rooms.php');
    }

    // 3. XÓA PHÒNG THI
    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        if ($stmt->execute([$id])) {
            resetAutoIncrement($pdo, 'rooms');
            setFlash('success', 'Đã xóa phòng thi thành công!');
        } else {
            setFlash('danger', 'Không thể xóa phòng thi này!');
        }
        redirect('rooms.php');
    }
}

$rooms = getRooms();
$exams = getExams();

$page_title = 'Quản lý phòng thi';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4>Quản lý phòng thi</h4>
                <p class="text-muted">Mở phòng thi và quản lý danh sách phòng</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Mở phòng thi
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã phòng</th>
                                <th>Tên phòng</th>
                                <th>Đề thi</th>
                                <th>Số người</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Chưa có phòng thi nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $index => $r): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><code><?= e($r['code']) ?></code></td>
                                        <td><?= e($r['name']) ?></td>
                                        <td>
                                            <?php
                                            $exam_name = '';
                                            foreach ($exams as $ex) {
                                                if ($ex['id'] == $r['exam_id']) {
                                                    $exam_name = $ex['title'];
                                                    break;
                                                }
                                            }
                                            echo e($exam_name ?: 'Chưa có đề');
                                            ?>
                                        </td>
                                        <td><?= $r['max_players'] ?? 10 ?></td>
                                        <td><?= status_badge($r['status'] ?? 'waiting') ?></td>
                                        <td class="text-end">
                                            <!-- Nút Sửa -->
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-code="<?= e($r['code']) ?>"
                                                    data-name="<?= e($r['name']) ?>"
                                                    data-exam_id="<?= $r['exam_id'] ?>"
                                                    data-max_players="<?= $r['max_players'] ?>"
                                                    data-status="<?= $r['status'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <!-- Nút Xóa -->
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-name="<?= e($r['name']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<!-- Modal Thêm Phòng Thi -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Mở phòng thi mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Mã phòng</label>
                        <input type="text" name="code" class="form-control" placeholder="VD: PT-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Tên phòng</label>
                        <input type="text" name="name" class="form-control" placeholder="Nhập tên phòng thi..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đề thi</label>
                        <select name="exam_id" class="form-select">
                            <option value="0">-- Chọn đề thi --</option>
                            <?php foreach ($exams as $ex): ?>
                                <option value="<?= $ex['id'] ?>"><?= e($ex['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số người tối đa</label>
                        <input type="number" name="max_players" class="form-control" value="10" min="1" max="500">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Mở phòng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa Phòng Thi -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa phòng thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Mã phòng</label>
                        <input type="text" id="edit_code" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Tên phòng</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đề thi</label>
                        <select name="exam_id" id="edit_exam_id" class="form-select">
                            <option value="0">-- Chọn đề thi --</option>
                            <?php foreach ($exams as $ex): ?>
                                <option value="<?= $ex['id'] ?>"><?= e($ex['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số người tối đa</label>
                        <input type="number" name="max_players" id="edit_max_players" class="form-control" min="1" max="500">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="waiting">Đang chờ (waiting)</option>
                            <option value="running">Đang diễn ra (running)</option>
                            <option value="finished">Đã kết thúc (finished)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác Nhận Xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Xác nhận xóa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa phòng thi <strong id="delete_name"></strong> không? Hành động này không thể hoàn tác.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa phòng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Fill data into Edit Modal
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_code').value = button.getAttribute('data-code');
            document.getElementById('edit_name').value = button.getAttribute('data-name');
            document.getElementById('edit_exam_id').value = button.getAttribute('data-exam_id');
            document.getElementById('edit_max_players').value = button.getAttribute('data-max_players');
            document.getElementById('edit_status').value = button.getAttribute('data-status');
        });
    }

    // Fill data into Delete Modal
    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('delete_id').value = button.getAttribute('data-id');
            document.getElementById('delete_name').textContent = button.getAttribute('data-name');
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>