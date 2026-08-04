<?php
// exams.php - Trang Đề thi (Guest)
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';

$page_title = 'Đề thi';
$subject_filter = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
// exams.php
require_once 'config/database.php';


$subjects = getSubjects() ?? [];
$exams = getExams() ?? [];

if ($subject_filter > 0) {
    $exams = array_filter($exams, function($e) use ($subject_filter) {
        return isset($e['subject_id']) && $e['subject_id'] == $subject_filter;
    });
}

include 'includes/header_guest.php';
?>
<<<<<<< HEAD

<style>
/* Hero Header đồng bộ với subjects & rooms */
.hero-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #fff;
    border-radius: 24px;
    padding: 3rem 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.exam-card {
    border: none;
    border-radius: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.exam-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 36px rgba(0, 0, 0, 0.1) !important;
}

.filter-btn {
    border-radius: 12px;
    padding: 8px 18px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.meta-badge {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    border-radius: 8px;
    padding: 6px 12px;
    font-weight: 500;
}
</style>

<div class="container py-4">
    <!-- Hero Header Đồng bộ -->
    <div class="hero-header mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-white text-dark px-3 py-2 rounded-pill fw-bold mb-2">Thư viện</span>
            <h1 class="fw-bold mb-2 display-6">Danh sách Đề thi</h1>
            <p class="text-white-50 mb-0 fs-6">Thử sức với các bộ đề thi trắc nghiệm được biên soạn chuẩn hóa.</p>
        </div>

        <?php if (function_exists('isTeacher') && isTeacher()): ?>
            <div>
                <a href="exam_create.php" class="btn btn-light btn-lg rounded-pill shadow-sm fw-bold text-primary px-4">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tạo đề thi
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bộ lọc môn học -->
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2">
            <a href="exams.php" class="btn filter-btn <?= $subject_filter == 0 ? 'btn-primary shadow-sm' : 'btn-light border' ?>">
                <i class="bi bi-grid-fill me-1"></i> Tất cả môn
            </a>
            <?php foreach ($subjects as $s): ?>
                <a href="exams.php?subject=<?= intval($s['id'] ?? 0) ?>"
                   class="btn filter-btn <?= $subject_filter == ($s['id'] ?? 0) ? 'btn-primary shadow-sm' : 'btn-light border' ?>">
                    <?= e($s['name'] ?? 'Môn học') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Danh sách đề thi -->
    <?php if (empty($exams)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <i class="bi bi-card-heading display-1 text-muted"></i>
                <h4 class="fw-bold mt-3">Không tìm thấy đề thi</h4>
                <p class="text-muted mb-0">Không có đề thi nào phù hợp với danh mục bạn đã chọn.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($exams as $e): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 exam-card">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="mb-3">
                                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill">
                                    <?= e($e['subject_name'] ?? 'Môn học') ?>
                                </span>
                            </div>

                            <h5 class="fw-bold text-dark mb-2 line-clamp-2" style="min-height: 48px;">
                                <?= e($e['title'] ?? 'Đề thi') ?>
                            </h5>

                            <p class="text-muted small flex-grow-1 mb-4" style="min-height: 40px;">
                                <?= e($e['description'] ?? 'Luyện tập để củng cố và nâng cao kiến thức.') ?>
                            </p>

                            <div class="d-flex gap-2 mb-4">
                                <span class="meta-badge small">
                                    <i class="bi bi-list-check text-primary me-1"></i>
                                    <?= intval($e['question_count'] ?? $e['total_questions'] ?? 0) ?> câu
                                </span>
                                <span class="meta-badge small">
                                    <i class="bi bi-clock text-warning me-1"></i>
                                    <?= intval($e['duration'] ?? 20) ?> phút
                                </span>
                            </div>

                            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                <a href="exam.php?id=<?= intval($e['id'] ?? 0) ?>" class="btn btn-primary rounded-3 fw-semibold py-2 mt-auto">
                                    <i class="bi bi-play-circle-fill me-1"></i> Bắt đầu làm bài
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary rounded-3 fw-semibold py-2 mt-auto">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập để làm
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
=======
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


<?php include 'includes/footer_guest.php'; ?>