<?php
// exams.php
require_once 'config/database.php';

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
    <title>Đề thi - QuizTech</title>
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
                    <li class="nav-item"><a class="nav-link active" href="exams.php">Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link" href="leaderboard.php">Bảng xếp hạng</a></li>
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
        <h3 class="mb-4">📝 Danh sách đề thi</h3>
        <div class="row g-4">
            <?php foreach($exams as $exam): ?>
            <div class="col-md-4">
                <div class="exam-card">
                    <h6><?= htmlspecialchars($exam['title']) ?></h6>
                    <p class="text-muted small"><?= htmlspecialchars($exam['subject_name']) ?></p>
                    <p class="small"><?= htmlspecialchars($exam['description']) ?></p>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary"><?= $exam['question_count'] ?> câu</span>
                        <span class="badge bg-info"><?= $exam['time_limit'] ?> phút</span>
                    </div>
                    <a href="exam.php?id=<?= $exam['id'] ?>" class="btn btn-primary w-100">
                        <i class="bi bi-play-fill"></i> Bắt đầu làm bài
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>