<?php
// admin/exams.php
require_once '../config/database.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$subjects = $pdo->query("SELECT id, name FROM subjects ORDER BY name")->fetchAll();

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $time_limit = (int)($_POST['time_limit'] ?? 30);
    $question_count = (int)($_POST['question_count'] ?? 10);

    if ($action === 'create' && $title && $subject_id) {
        $stmt = $pdo->prepare("INSERT INTO exams (subject_id, title, description, time_limit, question_count) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$subject_id, $title, $description, $time_limit, $question_count]);
        $_SESSION['flash'] = 'Đã thêm đề thi thành công!';
    }
    
    if ($action === 'update' && $id && $title) {
        $stmt = $pdo->prepare("UPDATE exams SET subject_id = ?, title = ?, description = ?, time_limit = ?, question_count = ? WHERE id = ?");
        $stmt->execute([$subject_id, $title, $description, $time_limit, $question_count, $id]);
        $_SESSION['flash'] = 'Đã cập nhật đề thi!';
    }
    
    if ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = 'Đã xóa đề thi!';
    }
    
    header('Location: exams.php');
    exit();
}

$exams = $pdo->query("
    SELECT e.*, s.name as subject_name 
    FROM exams e 
    JOIN subjects s ON e.subject_id = s.id 
    ORDER BY e.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đề thi - QuizTech</title>
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
                    <li class="nav-item"><a class="nav-link" href="subjects.php"><i class="bi bi-book"></i> Môn học</a></li>
                    <li class="nav-item"><a class="nav-link" href="questions.php"><i class="bi bi-question-circle"></i> Câu hỏi</a></li>
                    <li class="nav-item"><a class="nav-link active" href="exams.php"><i class="bi bi-file-text"></i> Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>📝 Quản lý đề thi</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#examModal">
                        <i class="bi bi-plus-circle"></i> Thêm đề thi
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
                                    <th>Tiêu đề</th>
                                    <th>Môn</th>
                                    <th>Số câu</th>
                                    <th>Thời gian</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td><?= $exam['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($exam['title']) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($exam['subject_name']) ?></span></td>
                                    <td><?= $exam['question_count'] ?></td>
                                    <td><?= $exam['time_limit'] ?> phút</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-exam" 
                                                data-id="<?= $exam['id'] ?>"
                                                data-subject="<?= $exam['subject_id'] ?>"
                                                data-title="<?= htmlspecialchars($exam['title']) ?>"
                                                data-description="<?= htmlspecialchars($exam['description']) ?>"
                                                data-time="<?= $exam['time_limit'] ?>"
                                                data-count="<?= $exam['question_count'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-exam" 
                                                data-id="<?= $exam['id'] ?>">
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

    <!-- Modal Thêm/Sửa Đề thi -->
    <div class="modal fade" id="examModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="id" id="examId">
                    <input type="hidden" name="action" id="examAction" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title" id="examModalLabel">Thêm đề thi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Môn học</label>
                            <select name="subject_id" id="examSubject" class="form-select" required>
                                <option value="">-- Chọn môn --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề</label>
                            <input type="text" name="title" id="examTitle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" id="examDesc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Số câu hỏi</label>
                                    <input type="number" name="question_count" id="examCount" class="form-control" value="10" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Thời gian (phút)</label>
                                    <input type="number" name="time_limit" id="examTime" class="form-control" value="30" min="1">
                                </div>
                            </div>
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
                        <p>Bạn có chắc muốn xóa đề thi này?</p>
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
        // Edit exam
        document.querySelectorAll('.edit-exam').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('examId').value = this.dataset.id;
                document.getElementById('examSubject').value = this.dataset.subject;
                document.getElementById('examTitle').value = this.dataset.title;
                document.getElementById('examDesc').value = this.dataset.description;
                document.getElementById('examTime').value = this.dataset.time;
                document.getElementById('examCount').value = this.dataset.count;
                document.getElementById('examAction').value = 'update';
                document.getElementById('examModalLabel').textContent = 'Sửa đề thi';
                new bootstrap.Modal(document.getElementById('examModal')).show();
            });
        });

        // Delete exam
        document.querySelectorAll('.delete-exam').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteId').value = this.dataset.id;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        // Reset modal
        document.getElementById('examModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('examAction').value = 'create';
            document.getElementById('examId').value = '';
            document.getElementById('examSubject').value = '';
            document.getElementById('examTitle').value = '';
            document.getElementById('examDesc').value = '';
            document.getElementById('examTime').value = '30';
            document.getElementById('examCount').value = '10';
            document.getElementById('examModalLabel').textContent = 'Thêm đề thi';
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>