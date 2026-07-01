<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$current_user = current_user();
$page_title = $page_title ?? APP_NAME;

// Determine active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$is_dashboard = strpos($current_page, 'dashboard') !== false || $current_page === 'index.php';
$is_exams = strpos($current_page, 'exam') !== false || $current_page === 'exams.php';
$is_questions = $current_page === 'questions.php';
$is_subjects = $current_page === 'subjects.php';
$is_rooms = $current_page === 'rooms.php';
$is_leaderboard = $current_page === 'leaderboard.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/dashboard.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?= base_url('index.php') ?>">
                <i class="fas fa-brain"></i> <?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= base_url('dashboard.php') ?>">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $is_exams ? 'active' : '' ?>" href="<?= base_url('exams.php') ?>">
                            <i class="fas fa-file-alt"></i> Đề thi
                        </a>
                    </li>
                    <?php if (is_teacher()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $is_questions ? 'active' : '' ?>" href="<?= base_url('questions.php') ?>">
                                <i class="fas fa-question-circle"></i> Câu hỏi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $is_subjects ? 'active' : '' ?>" href="<?= base_url('subjects.php') ?>">
                                <i class="fas fa-book"></i> Môn học
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $is_rooms ? 'active' : '' ?>" href="<?= base_url('rooms.php') ?>">
                            <i class="fas fa-users"></i> Phòng thi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $is_leaderboard ? 'active' : '' ?>" href="<?= base_url('leaderboard.php') ?>">
                            <i class="fas fa-trophy"></i> Bảng xếp hạng
                        </a>
                    </li>
                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link text-warning" href="<?= base_url('admin/dashboard.php') ?>">
                                <i class="fas fa-user-shield"></i> Admin
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search -->
                <form class="d-flex me-2" action="<?= base_url('search.php') ?>" method="GET">
                    <input class="form-control form-control-sm search-input" type="search" name="q" placeholder="Tìm kiếm..." aria-label="Search">
                </form>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= e($_SESSION['fullname'] ?? 'User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= base_url('profile.php') ?>">
                                <i class="fas fa-id-card"></i> Hồ sơ
                            </a></li>
                            <li><a class="dropdown-item" href="<?= base_url('dashboard.php') ?>">
                                <i class="fas fa-chart-line"></i> Dashboard
                            </a></li>
                            <?php if (is_teacher()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= base_url('admin/dashboard.php') ?>">
                                    <i class="fas fa-user-shield"></i> Quản lý
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= base_url('logout.php') ?>">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php foreach (flash_pop() as $flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endforeach; ?>

    <main class="py-4">