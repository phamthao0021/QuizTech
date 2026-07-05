<?php
// admin/subjects.php
require_once '../config/database.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = $_POST['icon'] ?? '📚';

    if ($action === 'create' && $name) {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, description, icon) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $icon]);
        $_SESSION['flash'] = 'Đã thêm môn học thành công!';
    }
    
    if ($action === 'update' && $id && $name) {
        $stmt = $pdo->prepare("UPDATE subjects SET name = ?, description = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $description, $icon, $id]);
        $_SESSION['flash'] = 'Đã cập nhật môn học!';
    }
    
    if ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = 'Đã xóa môn học!';
    }
    
    header('Location: subjects.php');
    exit();
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý môn học - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <h4 class="text-white mb-4"><i class="bi bi-code-square"></i> QuizTech</h4>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Người dùng</a></li>
                    <li class="nav-item"><a class="nav-link active" href="subjects.php"><i class="bi bi-book"></i> Môn học</a></li>
                    <li class="nav-item"><a class="nav-link" href="questions.php"><i class="bi bi-question-circle"></i> Câu hỏi</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php"><i class="bi bi-file-text"></i> Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>📚 Quản lý môn học</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#subjectModal">
                        <i class="bi bi-plus-circle"></i> Thêm môn học
                    </button>
                </div>

                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['flash']; unset($_SESSION['flash']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Icon</th>
                                    <th>Tên</th>
                                    <th>Mô tả</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?= $subject['id'] ?></td>
                                    <td style="font-size: 1.5rem;"><?= $subject['icon'] ?? '📚' ?></td>
                                    <td><strong><?= htmlspecialchars($subject['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($subject['description']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-subject" 
                                                data-id="<?= $subject['id'] ?>"
                                                data-name="<?= htmlspecialchars($subject['name']) ?>"
                                                data-description="<?= htmlspecialchars($subject['description']) ?>"
                                                data-icon="<?= $subject['icon'] ?? '📚' ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-subject" 
                                                data-id="<?= $subject['id'] ?>"
                                                data-name="<?= htmlspecialchars($subject['name']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

    <!-- Modal Thêm/Sửa -->
    <div class="modal fade" id="subjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="id" id="subjectId">
                    <input type="hidden" name="action" id="subjectAction" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title" id="subjectModalLabel">Thêm môn học</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Icon</label>
                            <input type="text" name="icon" id="subjectIcon" class="form-control" value="📚">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tên môn học</label>
                            <input type="text" name="name" id="subjectName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" id="subjectDesc" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <div class="modal-header">
                        <h5 class="modal-title">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bạn có chắc muốn xóa môn học <strong id="deleteName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">Xóa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Edit subject
        document.querySelectorAll('.edit-subject').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('subjectId').value = this.dataset.id;
                document.getElementById('subjectName').value = this.dataset.name;
                document.getElementById('subjectDesc').value = this.dataset.description;
                document.getElementById('subjectIcon').value = this.dataset.icon;
                document.getElementById('subjectAction').value = 'update';
                document.getElementById('subjectModalLabel').textContent = 'Sửa môn học';
                new bootstrap.Modal(document.getElementById('subjectModal')).show();
            });
        });

        // Delete subject
        document.querySelectorAll('.delete-subject').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteId').value = this.dataset.id;
                document.getElementById('deleteName').textContent = this.dataset.name;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        // Reset modal when closed
        document.getElementById('subjectModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('subjectAction').value = 'create';
            document.getElementById('subjectId').value = '';
            document.getElementById('subjectName').value = '';
            document.getElementById('subjectDesc').value = '';
            document.getElementById('subjectIcon').value = '📚';
            document.getElementById('subjectModalLabel').textContent = 'Thêm môn học';
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>