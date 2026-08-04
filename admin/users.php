<?php
// admin/users.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

// Xử lý Xóa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id > 0 && $id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Đã xóa người dùng!');
    }
    redirect('users.php');
}

// Xử lý Khóa / Mở khóa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    verify_csrf();
    $id = (int)($_POST['user_id'] ?? 0);
    $current_status = $_POST['current_status'] ?? 'active';
    
    if ($id > 0 && $id != $_SESSION['user_id']) {
        $new_status = ($current_status === 'active') ? 'blocked' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        
        $msg = ($new_status === 'blocked') ? 'Đã khóa tài khoản!' : 'Đã mở khóa tài khoản!';
        setFlash('success', $msg);
    } else {
        setFlash('danger', 'Không thể thao tác trên tài khoản của chính mình!');
    }
    redirect('users.php');
}

// Cập nhật role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    verify_csrf();
    $id = (int)$_POST['user_id'];
    $role = $_POST['role'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $id]);
        setFlash('success', 'Đã cập nhật vai trò!');
    } else {
        setFlash('danger', 'Không thể thay đổi vai trò của chính mình!');
    }
    redirect('users.php');
}

$users = getUsers();
$page_title = 'Quản lý người dùng';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4>Quản lý người dùng</h4>
                <p class="text-muted">Quản lý tài khoản và phân quyền</p>
            </div>
            <span class="badge bg-primary">Tổng: <?= count($users) ?></span>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td><strong><?= e($u['name']) ?></strong></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <select name="role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Sinh viên</option>
                                                <option value="teacher" <?= $u['role'] === 'teacher' ? 'selected' : '' ?>>Giảng viên</option>
                                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($u['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ($u['status'] ?? 'active') === 'active' ? 'Hoạt động' : 'Khóa' ?>
                                        </span>
                                    </td>
                                    <td><?= format_date($u['created_at']) ?></td>
                                    <td>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <!-- Nút Toggle Khóa / Mở khóa (Mở Popup Modal) -->
                                            <?php if (($u['status'] ?? 'active') === 'active'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning" 
                                                        title="Khóa tài khoản"
                                                        onclick="openStatusModal(<?= $u['id'] ?>, '<?= e($u['name']) ?>', 'active')">
                                                    <i class="bi bi-lock-fill"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        title="Mở khóa tài khoản"
                                                        onclick="openStatusModal(<?= $u['id'] ?>, '<?= e($u['name']) ?>', 'blocked')">
                                                    <i class="bi bi-unlock-fill"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Nút Xóa người dùng (Mở Popup Modal) -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    title="Xóa người dùng"
                                                    onclick="openDeleteModal(<?= $u['id'] ?>, '<?= e($u['name']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Bạn</span>
                                        <?php endif; ?>
                                    </td> 
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== POPUP MODAL KHÓA / MỞ KHÓA ==================== -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" id="modal_status_user_id">
                <input type="hidden" name="current_status" id="modal_status_current">

                <div class="modal-header" id="statusModalHeader">
                    <h5 class="modal-title" id="statusModalTitle">Xác nhận</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="statusModalMessage">Bạn có chắc chắn muốn thực hiện thao tác này?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn" id="statusModalBtn">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== POPUP MODAL XÓA NGƯỜI DÙNG ==================== -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="modal_delete_user_id">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Xác nhận xóa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Bạn có chắc chắn muốn xóa người dùng <strong id="deleteUserName" class="text-danger"></strong> không?</p>
                    <small class="text-muted">Hành động này không thể hoàn tác.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Xóa vĩnh viễn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hàm bật Popup Modal Khóa / Mở khóa
function openStatusModal(userId, userName, currentStatus) {
    document.getElementById('modal_status_user_id').value = userId;
    document.getElementById('modal_status_current').value = currentStatus;

    const modalHeader = document.getElementById('statusModalHeader');
    const modalTitle = document.getElementById('statusModalTitle');
    const modalMessage = document.getElementById('statusModalMessage');
    const modalBtn = document.getElementById('statusModalBtn');

    if (currentStatus === 'active') {
        modalHeader.className = 'modal-header bg-warning text-dark';
        modalTitle.innerHTML = '<i class="bi bi-lock-fill"></i> Xác nhận khóa tài khoản';
        modalMessage.innerHTML = `Bạn có chắc chắn muốn <strong>khóa</strong> tài khoản của <strong>${userName}</strong>?`;
        modalBtn.className = 'btn btn-warning';
        modalBtn.innerText = 'Khóa tài khoản';
    } else {
        modalHeader.className = 'modal-header bg-success text-white';
        modalTitle.innerHTML = '<i class="bi bi-unlock-fill"></i> Xác nhận mở khóa';
        modalMessage.innerHTML = `Bạn có chắc chắn muốn <strong>mở khóa</strong> tài khoản của <strong>${userName}</strong>?`;
        modalBtn.className = 'btn btn-success';
        modalBtn.innerText = 'Mở khóa tài khoản';
    }

    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

// Hàm bật Popup Modal Xóa người dùng
function openDeleteModal(userId, userName) {
    document.getElementById('modal_delete_user_id').value = userId;
    document.getElementById('deleteUserName').innerText = userName;

    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>