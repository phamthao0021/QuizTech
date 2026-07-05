<?php
// admin/questions.php
require_once '../config/database.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Lấy danh sách môn học cho dropdown
$subjects = $pdo->query("SELECT id, name FROM subjects ORDER BY name")->fetchAll();

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $question_text = trim($_POST['question_text'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_answer = (int)($_POST['correct_answer'] ?? 0);

    if ($action === 'create' && $question_text && $subject_id) {
        $stmt = $pdo->prepare("INSERT INTO questions (subject_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$subject_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer]);
        $_SESSION['flash'] = 'Đã thêm câu hỏi thành công!';
    }
    
    if ($action === 'update' && $id && $question_text) {
        $stmt = $pdo->prepare("UPDATE questions SET subject_id = ?, question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ? WHERE id = ?");
        $stmt->execute([$subject_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $id]);
        $_SESSION['flash'] = 'Đã cập nhật câu hỏi!';
    }
    
    if ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = 'Đã xóa câu hỏi!';
    }
    
    header('Location: questions.php');
    exit();
}

// Lọc câu hỏi
$filter_subject = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$sql = "SELECT q.*, s.name as subject_name FROM questions q JOIN subjects s ON q.subject_id = s.id";
if ($filter_subject > 0) {
    $sql .= " WHERE q.subject_id = $filter_subject";
}
$sql .= " ORDER BY q.id DESC";
$questions = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý câu hỏi - QuizTech</title>
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
                    <li class="nav-item"><a class="nav-link active" href="questions.php"><i class="bi bi-question-circle"></i> Câu hỏi</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php"><i class="bi bi-file-text"></i> Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>❓ Quản lý câu hỏi</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal">
                        <i class="bi bi-plus-circle"></i> Thêm câu hỏi
                    </button>
                </div>

                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['flash']; unset($_SESSION['flash']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <select name="subject" class="form-select" onchange="this.form.submit()">
                                    <option value="0">-- Tất cả môn --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= $filter_subject == $s['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Môn</th>
                                    <th>Câu hỏi</th>
                                    <th>Đáp án</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $q): ?>
                                <tr>
                                    <td><?= $q['id'] ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($q['subject_name']) ?></span></td>
                                    <td><?= htmlspecialchars(substr($q['question_text'], 0, 60)) ?>...</td>
                                    <td><?= ['A', 'B', 'C', 'D'][$q['correct_answer']] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-question" 
                                                data-id="<?= $q['id'] ?>"
                                                data-subject="<?= $q['subject_id'] ?>"
                                                data-question="<?= htmlspecialchars($q['question_text']) ?>"
                                                data-oa="<?= htmlspecialchars($q['option_a']) ?>"
                                                data-ob="<?= htmlspecialchars($q['option_b']) ?>"
                                                data-oc="<?= htmlspecialchars($q['option_c']) ?>"
                                                data-od="<?= htmlspecialchars($q['option_d']) ?>"
                                                data-correct="<?= $q['correct_answer'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-question" 
                                                data-id="<?= $q['id'] ?>">
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

    <!-- Modal Thêm/Sửa Câu hỏi -->
    <div class="modal fade" id="questionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="id" id="questionId">
                    <input type="hidden" name="action" id="questionAction" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title" id="questionModalLabel">Thêm câu hỏi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Môn học</label>
                            <select name="subject_id" id="questionSubject" class="form-select" required>
                                <option value="">-- Chọn môn --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Câu hỏi</label>
                            <textarea name="question_text" id="questionText" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">A. Đáp án A</label>
                                    <input type="text" name="option_a" id="questionA" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">B. Đáp án B</label>
                                    <input type="text" name="option_b" id="questionB" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">C. Đáp án C</label>
                                    <input type="text" name="option_c" id="questionC" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">D. Đáp án D</label>
                                    <input type="text" name="option_d" id="questionD" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Đáp án đúng</label>
                            <select name="correct_answer" id="questionCorrect" class="form-select">
                                <option value="0">A</option>
                                <option value="1">B</option>
                                <option value="2">C</option>
                                <option value="3">D</option>
                            </select>
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
                        <p>Bạn có chắc muốn xóa câu hỏi này?</p>
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
        // Edit question
        document.querySelectorAll('.edit-question').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('questionId').value = this.dataset.id;
                document.getElementById('questionSubject').value = this.dataset.subject;
                document.getElementById('questionText').value = this.dataset.question;
                document.getElementById('questionA').value = this.dataset.oa;
                document.getElementById('questionB').value = this.dataset.ob;
                document.getElementById('questionC').value = this.dataset.oc;
                document.getElementById('questionD').value = this.dataset.od;
                document.getElementById('questionCorrect').value = this.dataset.correct;
                document.getElementById('questionAction').value = 'update';
                document.getElementById('questionModalLabel').textContent = 'Sửa câu hỏi';
                new bootstrap.Modal(document.getElementById('questionModal')).show();
            });
        });

        // Delete question
        document.querySelectorAll('.delete-question').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteId').value = this.dataset.id;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        // Reset modal
        document.getElementById('questionModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('questionAction').value = 'create';
            document.getElementById('questionId').value = '';
            document.getElementById('questionSubject').value = '';
            document.getElementById('questionText').value = '';
            document.getElementById('questionA').value = '';
            document.getElementById('questionB').value = '';
            document.getElementById('questionC').value = '';
            document.getElementById('questionD').value = '';
            document.getElementById('questionCorrect').value = '0';
            document.getElementById('questionModalLabel').textContent = 'Thêm câu hỏi';
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>