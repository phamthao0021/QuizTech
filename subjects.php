<?php
// subjects.php
require_once 'config/database.php';

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id > 0) {
    // Xem chi tiết môn học
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        header('Location: subjects.php');
        exit();
    }
    
    $exams = $pdo->prepare("SELECT * FROM exams WHERE subject_id = ?");
    $exams->execute([$subject_id]);
    $exams = $exams->fetchAll();
} else {
    // Danh sách môn học
    $subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Môn học - QuizTech</title>
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
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link active" href="subjects.php">Môn học</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php">Đề thi</a></li>
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
        <?php if ($subject_id > 0 && isset($subject)): ?>
            <!-- Chi tiết môn học -->
            <div class="mb-4">
                <a href="subjects.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
                <h3 class="mt-3"><?= htmlspecialchars($subject['name']) ?></h3>
                <p class="text-muted"><?= htmlspecialchars($subject['description']) ?></p>
            </div>

            <h5 class="mb-3">Đề thi trong môn</h5>
            <div class="row g-4">
                <?php if (empty($exams)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">Chưa có đề thi cho môn này.</div>
                    </div>
                <?php else: ?>
                    <?php foreach($exams as $exam): ?>
                    <div class="col-md-4">
                        <div class="exam-card">
                            <h6><?= htmlspecialchars($exam['title']) ?></h6>
                            <p class="text-muted small"><?= htmlspecialchars($exam['description']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary"><?= $exam['question_count'] ?> câu</span>
                                <span class="badge bg-info"><?= $exam['time_limit'] ?> phút</span>
                            </div>
                            <a href="exam.php?id=<?= $exam['id'] ?>" class="btn btn-primary w-100">
                                <i class="bi bi-play-fill"></i> Làm bài
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Danh sách môn học -->
            <h3 class="mb-4"><i class="fa-solid fa-book" style="color: rgb(116, 192, 252);"></i>Danh sách môn học</h3>
            <div class="row g-4">
                <?php foreach($subjects as $subject): ?>
                <div class="col-md-4 col-6">
                    <div class="subject-card" onclick="window.location.href='subjects.php?id=<?= $subject['id'] ?>'">
                        <div style="font-size: 3rem;"><?= $subject['icon'] ?? '📚' ?></div>
                        <h5><?= htmlspecialchars($subject['name']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($subject['description']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>