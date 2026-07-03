<?php
// index.php - Trang chủ
require_once 'config/database.php';

$stats = getStats($pdo);
$subjects = $pdo->query("SELECT * FROM subjects LIMIT 6")->fetchAll();
$exams = $pdo->query("SELECT e.*, s.name as subject_name 
                       FROM exams e 
                       JOIN subjects s ON e.subject_id = s.id 
                       ORDER BY e.id DESC 
                       LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizTech - Trắc nghiệm CNTT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <img src="assets/images/Cardmoi_PLT_Trang.png" alt="" style="height:60px; width:65px; margin: right 10px;"> 
                QuizTech
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="subjects.php">Môn học</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php">Đề thi</a></li>
                    <li class="nav-item"><a class="nav-link" href="leaderboard.php">Bảng xếp hạng</a></li>
                    <?php if (isLoggedIn()): 
                        $user = getUserInfo($pdo, $_SESSION['user_id']);
                    ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="auth.php">Đăng nhập</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <div class="hero-section text-white text-center py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <h1 class="display-4 fw-bold">Thi trắc nghiệm kiến thức CNTT</h1>
            <p class="lead">Ngân hàng câu hỏi đa dạng: Lập trình, Kiểm thử phần mềm, SQL...</p>
            <div class="mt-4">
                <a href="exams.php" class="btn btn-light btn-lg px-4 me-2">
                    <i class="bi bi-play-fill"></i> Làm bài thi
                </a>
                <a href="leaderboard.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-trophy"></i> Bảng xếp hạng
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="icon"><i class="bi bi-book text-primary" style="font-size: 2rem;"></i></div>
                    <div class="number"><?= $stats['subjects'] ?></div>
                    <div class="label">Môn học</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="icon"><i class="bi bi-file-text text-success" style="font-size: 2rem;"></i></div>
                    <div class="number"><?= $stats['exams'] ?></div>
                    <div class="label">Đề thi</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="icon"><i class="bi bi-question-circle text-warning" style="font-size: 2rem;"></i></div>
                    <div class="number"><?= $stats['questions'] ?></div>
                    <div class="label">Câu hỏi</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="icon"><i class="bi bi-people text-danger" style="font-size: 2rem;"></i></div>
                    <div class="number"><?= $stats['attempts'] ?></div>
                    <div class="label">Lượt thi</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subjects -->
    <div class="container py-4">
        <h3 class="mb-4"> Môn học nổi bật</h3>
        <div class="row g-4">
            <?php foreach($subjects as $subject): ?>
            <div class="col-md-4 col-6">
                <div class="subject-card" onclick="window.location.href='subjects.php?id=<?= $subject['id'] ?>'">
                    <div style="font-size: 3rem;"><?= $subject['icon'] ?? '' ?></div>
                    <h5><?= htmlspecialchars($subject['name']) ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars($subject['description']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="subjects.php" class="btn btn-outline-primary">Xem tất cả <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

    <!-- Exams -->
    <div class="container py-4">
        <h3 class="mb-4">Đề thi mới nhất</h3>
        <div class="row g-4">
            <?php foreach($exams as $exam): ?>
            <div class="col-md-4">
                <div class="exam-card">
                    <h6><?= htmlspecialchars($exam['title']) ?></h6>
                    <p class="text-muted small"><?= htmlspecialchars($exam['subject_name']) ?></p>
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
        </div>
        <div class="text-center mt-4">
            <a href="exams.php" class="btn btn-outline-primary">Xem tất cả <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center py-4 mt-4">
        <div class="container">
            <p class="text-muted mb-0">© 2026 QuizTech - Trắc nghiệm CNTT</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>