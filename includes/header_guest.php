<?php
// includes/header_guest.php - Header dành riêng cho Khách (chưa đăng nhập)
$page_title = $page_title ?? 'QuizTech';
$current_page = basename($_SERVER['PHP_SELF']);
$active_tab = $_GET['tab'] ?? '';
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
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/guest.css" rel="stylesheet">
    <style>
        :root{--qt-primary:#5b4df7;--qt-purple:#8b5cf6;--qt-navy:#17164a}
        body{font-family:Inter,"Segoe UI",sans-serif;background:#fff;color:#111827}
        .qt-guest-nav{background:rgba(255,255,255,.96)!important;border-bottom:1px solid #edf0f6;backdrop-filter:blur(16px);box-shadow:0 4px 18px rgba(15,23,42,.04)}
        .qt-guest-nav .navbar-brand{color:var(--qt-navy)!important;font-family:"Space Grotesk",sans-serif;font-size:1.35rem}
        .qt-guest-nav .navbar-brand img{height:43px;width:auto}
        .qt-guest-nav .nav-link{color:#475569!important;font-weight:600;padding:.7rem .9rem!important;border-radius:10px}
        .qt-guest-nav .nav-link:hover,.qt-guest-nav .nav-link.active{color:var(--qt-primary)!important;background:#f5f3ff}
        .qt-register-btn{background:linear-gradient(135deg,#6558f5,#4f46e5);color:#fff!important;border:0;border-radius:12px;padding:.65rem 1rem!important;box-shadow:0 8px 18px rgba(79,70,229,.22)}
        .qt-register-btn:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(79,70,229,.28)}
        .qt-login-link{border:1px solid transparent}
        main{min-height:55vh}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light sticky-top qt-guest-nav">
    <div class="container py-1">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <img src="assets/images/CARD MOI.png" alt="QuizTech Logo" class="me-2">
            QuizTech
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-lg-4 align-items-lg-center">
                <li class="nav-item"><a class="nav-link <?= $current_page === 'index.php' ? 'active fw-bold' : '' ?>" href="index.php">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#about">Giới thiệu</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#method">Phương pháp học</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#features">Tính năng</a></li>
                <li class="nav-item"><a class="nav-link <?= $current_page === 'demo.php' ? 'active fw-bold' : '' ?>" href="demo.php"><i class="bi bi-play-circle me-1"></i>Dùng thử</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#contact">Liên hệ</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link qt-login-link <?= ($current_page === 'login.php' && $active_tab !== 'register') ? 'active fw-bold' : '' ?>" href="login.php?tab=login"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập</a></li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0"><a class="btn btn-sm qt-register-btn <?= ($current_page === 'login.php' && $active_tab === 'register') ? 'active' : '' ?>" href="login.php?tab=register"><i class="bi bi-person-plus me-1"></i>Đăng ký ngay</a></li>
            </ul>
        </div>
    </div>
</nav>
<?php $flash = getFlash(); if ($flash && isset($flash['type'],$flash['message'])): ?>
<div class="container mt-3"><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
<?php endif; ?>
<main>
