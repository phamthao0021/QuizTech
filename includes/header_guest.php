<?php
// includes/header_guest.php - Header cho Guest
$page_title = $page_title ?? 'QuizTech';
$current_page = basename($_SERVER['PHP_SELF']);
$user = currentUser();
$is_logged_in = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/guest.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <img src="assets/images/Cardmoi_PLT_Trang.png" alt="" style="height:60px; width:65px; margin-right:10px;">
            QuizTech
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Public Menu -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                        Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'subjects.php' ? 'active' : '' ?>" href="subjects.php">
                        Môn học
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'exams.php' ? 'active' : '' ?>" href="exams.php">
                        Đề thi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'rooms.php' ? 'active' : '' ?>" href="rooms.php">
                        Phòng thi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'leaderboard.php' ? 'active' : '' ?>" href="leaderboard.php">
                        Bảng xếp hạng
                    </a>
                </li>
                
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'history.php' ? 'active' : '' ?>" href="history.php">
                            <i class="bi bi-clock-history"></i> Lịch sử
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5 me-1"></i>
                            <span><?= e($_SESSION['name'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person"></i> Hồ sơ
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'login.php' ? 'active' : '' ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light btn-sm text-primary ms-2 px-3" href="register.php">
                            <i class="bi bi-person-plus"></i> Đăng ký
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php
$flash = getFlash();
if ($flash && isset($flash['type']) && isset($flash['message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<main>