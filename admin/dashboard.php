<?php
// admin/dashboard.php
require_once '../config/database.php';

// Kiểm tra admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$stats = getStats($pdo);
$recentUsers = getRecentUsers($pdo, 5);

// Lấy đề thi gần đây
$recentExams = $pdo->query("
    SELECT e.*, s.name as subject_name 
    FROM exams e 
    JOIN subjects s ON e.subject_id = s.id 
    ORDER BY e.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h4 class="text-white mb-4"><i class="bi bi-code-square"></i> QuizTech</h4>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Người dùng</a></li>
                    <li class="nav-item"><a class="nav-link" href="subjects.php"><i class="bi bi-book"></i> Môn học</a></li>
                    <li class="nav-item"><a class="nav-link" href="questions.php"><i class="bi bi-question-circle"></i> Câu hỏi</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php"><i class="bi bi-file-text"></i> Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h3 class="mb-4">📊 Admin Dashboard</h3>
                
                <!-- Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Người dùng</h6>
                                    <h2 class="text-white"><?= $stats['users'] ?></h2>
                                </div>
                                <i class="bi bi-people fs-1 text-white-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Môn học</h6>
                                    <h2 class="text-white"><?= $stats['subjects'] ?></h2>
                                </div>
                                <i class="bi bi-book fs-1 text-white-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Câu hỏi</h6>
                                    <h2 class="text-white"><?= $stats['questions'] ?></h2>
                                </div>
                                <i class="bi bi-question-circle fs-1 text-white-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-danger">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Đề thi</h6>
                                    <h2 class="text-white"><?= $stats['exams'] ?></h2>
                                </div>
                                <i class="bi bi-file-text fs-1 text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users & Exams -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-people"></i> Người dùng mới</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr><th>Họ tên</th><th>Email</th><th>Vai trò</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>"><?= $user['role'] ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-file-text"></i> Đề thi mới</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr><th>Tiêu đề</th><th>Môn</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentExams as $exam): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($exam['title']) ?></td>
                                            <td><?= htmlspecialchars($exam['subject_name']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>