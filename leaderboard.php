<?php
// leaderboard.php
require_once 'config/database.php';

$leaderboard = $pdo->query("
    SELECT u.name, 
           COUNT(DISTINCT ea.exam_id) as exam_count,
           ROUND(AVG(ea.score) * 100 / ea.total_questions, 1) as avg_score,
           MIN(ea.time_taken) as best_time
    FROM exam_attempts ea
    JOIN users u ON ea.user_id = u.id
    WHERE ea.is_completed = 1
    GROUP BY ea.user_id
    ORDER BY avg_score DESC, best_time ASC
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng xếp hạng - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <img src="assets/images/Cardmoi_PLT_Trang.png" alt="" style="height:60px; width:65px; margin: right 10px;">  QuizTech
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="subjects.php">Môn học</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php">Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link active" href="leaderboard.php">Bảng xếp hạng</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="auth.php">Đăng nhập</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-trophy-fill text-warning"></i> Bảng xếp hạng</h5>
                <small class="text-muted">Điểm cao nhất, thời gian ngắn nhất được ưu tiên.</small>
            </div>
            <div class="card-body">
                <?php if (empty($leaderboard)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-emoji-frown" style="font-size: 48px; color: #ddd;"></i>
                        <p class="mt-3">Chưa có lượt thi nào. Hãy là người đầu tiên!</p>
                        <a href="exams.php" class="btn btn-primary">
                            <i class="bi bi-play-fill"></i> Làm bài thi
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Họ tên</th>
                                    <th>Điểm TB</th>
                                    <th>Số đề</th>
                                    <th>Thời gian tốt nhất</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($leaderboard as $index => $entry): ?>
                                <tr>
                                    <td>
                                        <?php if ($index === 0): ?>
                                            🥇
                                        <?php elseif ($index === 1): ?>
                                            🥈
                                        <?php elseif ($index === 2): ?>
                                            🥉
                                        <?php else: ?>
                                            #<?= $index + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($entry['name']) ?></td>
                                    <td>
                                        <span class="fw-bold text-<?= $entry['avg_score'] >= 80 ? 'success' : ($entry['avg_score'] >= 50 ? 'warning' : 'danger') ?>">
                                            <?= $entry['avg_score'] ?>%
                                        </span>
                                    </td>
                                    <td><?= $entry['exam_count'] ?></td>
                                    <td><?= $entry['best_time'] ?>s</td>
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