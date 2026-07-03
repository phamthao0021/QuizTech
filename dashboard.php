<?php
// dashboard.php
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: auth.php');
    exit();
}

$user = getUserInfo($pdo, $_SESSION['user_id']);

// Thống kê của user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_attempts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM exam_attempts WHERE user_id = ? AND is_completed = 1");
$stmt->execute([$_SESSION['user_id']]);
$avg_score = round($stmt->fetch()['avg_score'] ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM exam_attempts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_exams = $stmt->fetchColumn();

// Lịch sử làm bài
$history = $pdo->prepare("
    SELECT ea.*, e.title as exam_title, s.name as subject_name 
    FROM exam_attempts ea 
    JOIN exams e ON ea.exam_id = e.id 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE ea.user_id = ? 
    ORDER BY ea.started_at DESC 
    LIMIT 10
");
$history->execute([$_SESSION['user_id']]);
$history = $history->fetchAll();

// Thống kê tổng
$stats = getStats($pdo);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-code-square"></i> QuizTech
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="subjects.php">Môn học</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php">Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link" href="leaderboard.php">Bảng xếp hạng</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Welcome -->
        <div class="row mb-4">
            <div class="col-12">
                <h4>Xin chào, <?= htmlspecialchars($user['name']) ?>!</h4>
                <p class="text-muted">Chào mừng bạn đến với dashboard cá nhân</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="number"><?= $total_attempts ?></div>
                    <div class="label">Lượt thi</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="number"><?= $avg_score ?>%</div>
                    <div class="label">Điểm TB</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="number"><?= $total_exams ?></div>
                    <div class="label">Đề thi đã làm</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="number"><?= $stats['subjects'] ?></div>
                    <div class="label">Môn học</div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Lịch sử làm bài</h6>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ddd;"></i>
                        <p class="mt-3">Bạn chưa làm bài thi nào.</p>
                        <a href="exams.php" class="btn btn-primary">Bắt đầu ngay</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Đề thi</th>
                                    <th>Môn</th>
                                    <th>Điểm</th>
                                    <th>Thời gian</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($history as $attempt): ?>
                                <tr>
                                    <td><?= htmlspecialchars($attempt['exam_title']) ?></td>
                                    <td><?= htmlspecialchars($attempt['subject_name']) ?></td>
                                    <td>
                                        <?php if ($attempt['is_completed']): ?>
                                            <span class="badge bg-<?= $attempt['score'] >= 4 ? 'success' : 'danger' ?>">
                                                <?= $attempt['score'] ?>/<?= $attempt['total_questions'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Đang làm</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $attempt['time_taken'] ?>s</td>
                                    <td>
                                        <?php if ($attempt['is_completed']): ?>
                                            <span class="badge bg-success">Hoàn thành</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Đang làm</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>