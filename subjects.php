<?php
// subjects.php

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';

$page_title = 'Môn học';
$subjects = getSubjects() ?? [];

require_once 'config/database.php';


include 'includes/header_guest.php';


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
<<<<<<< HEAD

<style>
.hero-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #fff;
    border-radius: 24px;
    padding: 3rem 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.subject-card {
    border: none;
    border-radius: 20px;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.subject-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12) !important;
}

.subject-icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: linear-gradient(135deg, #e0e7ff 0%, #e0f2fe 100%);
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 1.25rem;
}

.subject-card .btn-custom {
    border-radius: 12px;
    font-weight: 600;
    padding: 0.6rem 1.2rem;
    transition: all 0.2s ease;
}

.subject-card:hover .btn-custom {
    background-color: #4f46e5;
    color: #ffffff !important;
    border-color: #4f46e5;
}
</style>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="hero-header mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-white text-dark px-3 py-2 rounded-pill fw-bold mb-2">Danh mục</span>
            <h1 class="fw-bold mb-2 display-6">Danh sách Môn học</h1>
            <p class="text-white-50 mb-0 fs-6">Chọn môn học bất kỳ để khám phá các đề thi trắc nghiệm phong phú.</p>
=======
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

        <?php if (function_exists('isTeacher') && isTeacher()): ?>
            <div>
                <a href="subject_create.php" class="btn btn-light btn-lg rounded-pill shadow-sm fw-bold text-primary px-4">
                    <i class="bi bi-plus-circle-fill me-2"></i>Thêm môn học
                </a>
<<<<<<< HEAD
=======
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

    <!-- Main Content -->
    <?php if (empty($subjects)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body py-4">
                <i class="bi bi-journal-album display-1 text-muted"></i>
                <h4 class="fw-bold mt-4">Chưa có môn học nào</h4>
                <p class="text-muted">Dữ liệu môn học hiện tại đang trống. Vui lòng quay lại sau!</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($subjects as $s): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a href="exams.php?subject=<?= intval($s['id'] ?? 0) ?>" class="text-decoration-none">
                        <div class="card h-100 subject-card p-3">
                            <div class="card-body text-center d-flex flex-column align-items-center">
                                <div class="subject-icon-wrapper">
                                    <i class="bi bi-book"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-2 line-clamp-2" style="min-height: 48px;">
                                    <?= e($s['name'] ?? 'Môn học') ?>
                                </h5>
                                <p class="text-muted small flex-grow-1 mb-4 text-break" style="min-height: 42px;">
                                    <?= e($s['description'] ?? 'Chưa có mô tả cho môn học này.') ?>
                                </p>
                                <span class="btn btn-outline-primary btn-custom w-100 mt-auto">
                                    Xem đề thi <i class="bi bi-arrow-right ms-1"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer_guest.php'; ?>