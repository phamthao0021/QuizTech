<?php
// includes/header_guest.php - Header dành riêng cho Khách (chưa đăng nhập)
$page_title = $page_title ?? 'QuizTech';
$current_page = basename($_SERVER['PHP_SELF'] ?? "");
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
    <link rel="icon" type="image/png" href="assets/images/CARD MOI.png">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --qt-primary: #6554F0;
            --qt-primary-dark: #4F3DE5;
            --qt-primary-light: #8B5CF6;
            --qt-bg-soft: #F8F7FF;
            --qt-text-main: #19183B;
            --qt-text-muted: #6B7280;
            --qt-border: #E8E5F5;
        }
        body { font-family: Inter, system-ui, -apple-system, sans-serif; background: #fff; color: var(--qt-text-main); }
        .qt-guest-nav {
            background: rgba(255, 255, 255, 0.92) !important;
            border-bottom: 1px solid var(--qt-border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 20px rgba(101, 84, 240, 0.04);
            padding: 0.65rem 0;
            transition: all 0.25s ease;
        }
        .qt-guest-nav .container {
            max-width: 1280px;
        }
        .qt-brand-wrap {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .qt-brand-logo {
            height: 38px;
            width: auto;
            max-width: 72px;
            object-fit: contain;
            border-radius: 8px;
        }
        .qt-brand-text {
            font-family: "Space Grotesk", Inter, sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--qt-text-main);
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .qt-brand-text span {
            color: var(--qt-primary);
        }
        .qt-nav-center {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .qt-guest-nav .nav-link {
            color: #4B5563 !important;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.5rem 0.75rem !important;
            border-radius: 999px;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .qt-guest-nav .nav-link:hover {
            color: var(--qt-primary) !important;
            background: rgba(101, 84, 240, 0.07);
        }
        .qt-guest-nav .nav-link.active {
            color: var(--qt-primary) !important;
            background: rgba(101, 84, 240, 0.1);
            font-weight: 700;
        }
        .qt-login-link {
            color: var(--qt-text-main) !important;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.5rem 0.8rem !important;
            border-radius: 999px;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .qt-login-link:hover {
            color: var(--qt-primary) !important;
            background: var(--qt-bg-soft);
        }
        .qt-register-btn {
            background: var(--qt-primary) !important;
            background: linear-gradient(135deg, #6554F0 0%, #4F3DE5 100%) !important;
            color: #fff !important;
            border: 0;
            font-weight: 600;
            font-size: 0.88rem;
            border-radius: 999px;
            padding: 0.5rem 1.1rem !important;
            white-space: nowrap;
            box-shadow: 0 4px 14px rgba(101, 84, 240, 0.28);
            transition: all 0.2s ease;
        }
        .qt-register-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(101, 84, 240, 0.38);
            color: #fff !important;
        }
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .qt-guest-nav .nav-link {
                padding: 0.45rem 0.55rem !important;
                font-size: 0.83rem;
            }
            .qt-login-link {
                padding: 0.45rem 0.6rem !important;
                font-size: 0.83rem;
            }
            .qt-register-btn {
                padding: 0.45rem 0.9rem !important;
                font-size: 0.83rem;
            }
            .qt-brand-text {
                font-size: 1.2rem;
            }
            .qt-brand-logo {
                height: 32px;
            }
        }
        @media (max-width: 991.98px) {
            .qt-guest-nav .navbar-collapse {
                background: #fff;
                padding: 1.25rem;
                border-radius: 16px;
                margin-top: 0.75rem;
                box-shadow: 0 16px 36px rgba(25, 24, 59, 0.1);
                border: 1px solid var(--qt-border);
            }
            .qt-nav-center {
                flex-direction: column;
                align-items: stretch;
                gap: 0.25rem;
            }
            .qt-guest-nav .nav-link {
                border-radius: 10px;
                padding: 0.7rem 1rem !important;
            }
            .qt-guest-nav .navbar-nav.ms-auto {
                border-top: 1px solid var(--qt-border);
                padding-top: 0.85rem;
                margin-top: 0.85rem !important;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .qt-register-btn {
                text-align: center;
                display: block;
                width: 100%;
            }
        }
        main { min-height: 55vh; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top qt-guest-nav">
    <div class="container">
        <a class="qt-brand-wrap" href="index.php">
            <img src="assets/images/CARD MOI.png" alt="PLT Solutions" class="qt-brand-logo">
            <span class="qt-brand-text">Quiz<span>Tech</span></span>
        </a>
        <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Mở menu">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto qt-nav-center my-2 my-lg-0">
                <li class="nav-item"><a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#about">Giới thiệu</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#method">Phương pháp học</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#features">Tính năng</a></li>
                <li class="nav-item"><a class="nav-link <?= $current_page === 'demo.php' ? 'active' : '' ?>" href="demo.php"><i class="bi bi-play-circle me-1 text-primary"></i>Dùng thử</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#contact">Liên hệ</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link qt-login-link <?= ($current_page === 'login.php' && $active_tab !== 'register') ? 'active' : '' ?>" href="login.php?tab=login"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập</a></li>
                <li class="nav-item ms-lg-2"><a class="btn qt-register-btn <?= ($current_page === 'login.php' && $active_tab === 'register') ? 'active' : '' ?>" href="login.php?tab=register"><i class="bi bi-person-plus me-1"></i>Bắt đầu miễn phí</a></li>
            </ul>
        </div>
    </div>
</nav>
<?php $flash = getFlash(); if ($flash && isset($flash['type'],$flash['message'])): ?>
<div class="container mt-3"><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
<?php endif; ?>
<main>
