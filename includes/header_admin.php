<?php
// includes/header_admin.php - Header & Sidebar đồng bộ chuẩn cho Admin
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$page_title   = $page_title ?? 'QuizTech - Quản trị hệ thống';
$current_page = basename($_SERVER['PHP_SELF']);

// Xác định vị trí file gọi để tính toán prefix đường dẫn
$scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$adminPos = strpos($scriptPath, '/admin/');
$in_admin = $adminPos !== false;
$afterAdmin = $in_admin ? trim(substr($scriptPath, $adminPos + 7), '/') : '';
$adminDepth = ($in_admin && $afterAdmin !== '') ? max(0, substr_count($afterAdmin, '/')) : 0;
$prefix = $in_admin ? str_repeat('../', $adminDepth + 1) : '';
$base_url = $in_admin ? str_repeat('../', $adminDepth) : 'admin/';

$user         = currentUser();
$is_logged_in = isLoggedIn();
$user_name    = $user['name'] ?? $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Quản trị viên';
$user_email   = $user['email'] ?? $_SESSION['email'] ?? '';
$user_role    = function_exists('normalizeRole')
    ? normalizeRole($user['role'] ?? $_SESSION['role'] ?? 'admin')
    : ($user['role'] ?? $_SESSION['role'] ?? 'admin');
$is_super_admin = $user_role === 'admin' && function_exists('isSuperAdmin') && isSuperAdmin();
$role_badge_text = $user_role === 'admin_test' ? 'ADMIN KIỂM THỬ' : 'QUẢN TRỊ VIÊN';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> - QuizTech Admin</title>

  <!-- Bootstrap 5 CSS & Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" crossorigin="anonymous">

  <!-- OCR Library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/tesseract.js/5.0.4/tesseract.min.js"></script>

  <!-- Google Fonts: Plus Jakarta Sans & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="<?= $prefix ?>assets/css/style.css" rel="stylesheet">
  <link href="<?= $prefix ?>assets/css/guest.css" rel="stylesheet">

  <style>
    :root {
      --purple-main: #6366f1;
      --purple-dark: #4f46e5;
      --purple-deep: #3730a3;
      --purple-night: #1e1b4b;
      --purple-glow: rgba(99, 102, 241, 0.35);
      --purple-light: #e0e7ff;
      --bg-canvas: #f8fafc;
      --text-dark: #0f172a;
      --sidebar-width: 270px;
    }

    body {
      font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif;
      background-color: var(--bg-canvas);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    .admin-wrapper {
      display: flex;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* SIDEBAR GRADIENT TÍM HIỆN ĐẠI (CHUNG CHO CẢ DESKTOP & MOBILE OFFCANVAS) */
    aside.admin-sidebar, #mobileAdminSidebar {
      background: linear-gradient(180deg, #2e1065 0%, #4c1d95 60%, #3b0764 100%);
      color: #ffffff;
    }

    aside.admin-sidebar {
      width: var(--sidebar-width);
      flex-shrink: 0;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1020;
      box-shadow: 4px 0 25px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
    }

    .sidebar-brand-wrapper {
      padding: 1.25rem 1.5rem;
      background: rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .brand-logo-img {
      height: 38px;
      width: auto;
      filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.2));
    }

    .sidebar-menu-wrapper {
      padding: 1rem 0;
      flex-grow: 1;
      overflow-y: auto;
    }

    .sidebar-label {
      font-size: 0.68rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #c084fc;
      font-weight: 700;
      padding: 0.85rem 1.5rem 0.35rem;
      opacity: 0.85;
    }

    /* ĐỒNG BỘ NÚT ĐIỀU HƯỚNG CẢ DESKTOP VÀ MOBILE */
    .admin-sidebar .nav-link,
    #mobileAdminSidebar .nav-link {
      color: #e9d5ff !important;
      padding: 0.75rem 1.15rem;
      border-radius: 14px;
      margin: 0.25rem 1rem;
      font-weight: 500;
      font-size: 0.925rem;
      display: flex;
      align-items: center;
      gap: 0.85rem;
      transition: all 0.25s ease;
    }

    .admin-sidebar .nav-link i,
    #mobileAdminSidebar .nav-link i {
      font-size: 1.15rem;
      transition: transform 0.25s ease;
    }

    .admin-sidebar .nav-link:hover,
    #mobileAdminSidebar .nav-link:hover {
      color: #ffffff !important;
      background: rgba(255, 255, 255, 0.12) !important;
      transform: translateX(4px);
    }

    .admin-sidebar .nav-link:hover i,
    #mobileAdminSidebar .nav-link:hover i {
      transform: scale(1.15);
    }

    /* ĐỒNG BỘ TRẠNG THÁI ACTIVE THÀNH GRADIENT TÍM */
    .admin-sidebar .nav-link.active,
    #mobileAdminSidebar .nav-link.active {
      color: #ffffff !important;
      background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%) !important;
      box-shadow: 0 8px 20px rgba(168, 85, 247, 0.35);
      font-weight: 600;
    }

    .admin-sidebar .nav-link.active i,
    #mobileAdminSidebar .nav-link.active i {
      color: #ffffff !important;
    }

    /* MAIN CONTENT AREA & HEADER NAVBAR */
    .admin-content {
      flex-grow: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      background-color: var(--bg-canvas);
    }

    .admin-navbar {
      background: rgba(255, 255, 255, 0.85) !important;
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      padding: 0.75rem 1.5rem;
      z-index: 1010;
    }

    .user-profile-badge {
      padding: 0.35rem 0.85rem 0.35rem 0.4rem;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 50px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      transition: all 0.25s ease;
    }

    .user-profile-badge:hover {
      border-color: #c084fc;
      box-shadow: 0 4px 14px rgba(168, 85, 247, 0.15);
      background: #faf5ff;
    }

    .avatar-img-header {
      width: 38px;
      height: 38px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid var(--purple-main);
      box-shadow: 0 3px 10px rgba(99, 102, 241, 0.2);
    }

    .avatar-placeholder-header {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, #a855f7, #7c3aed);
      color: #ffffff;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      box-shadow: 0 3px 10px rgba(124, 58, 237, 0.25);
    }

    /* DROPDOWN MENU CUSTOM */
    .dropdown-menu-admin {
      border-radius: 20px;
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 20px 35px rgba(15, 23, 42, 0.08);
      padding: 0.6rem;
      min-width: 240px;
      margin-top: 0.75rem !important;
      animation: fadeInDropdown 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes fadeInDropdown {
      from { opacity: 0; transform: translateY(-8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-menu-admin .dropdown-item {
      border-radius: 12px;
      font-weight: 500;
      padding: 0.65rem 0.9rem;
      font-size: 0.88rem;
      transition: all 0.2s ease;
    }

    .dropdown-menu-admin .dropdown-item:hover {
      background-color: #f3e8ff;
      color: #6b21a8;
      transform: translateX(3px);
    }

    .dropdown-menu-admin .dropdown-item.text-danger:hover {
      background-color: #fef2f2;
      color: #dc2626;
    }

    /* FLASH ALERTS */
    .alert-modern {
      border-radius: 16px;
      border: none;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
      padding: 1rem 1.25rem;
    }

    @media (max-width: 991.98px) {
      aside.admin-sidebar {
        display: none !important;
      }
      .admin-navbar {
        padding: 0.75rem 1rem;
      }
    }
    /* =========================================================
   ADMIN HEADER - MOBILE BRAND
   ========================================================= */

.admin-mobile-brand {
    display: flex;
    align-items: center;
}

.admin-mobile-brand-link {
    display: flex;
    align-items: center;
    gap: 9px;

    text-decoration: none;
    color: inherit;

    min-width: 0;
}

/* Khung tím dành cho logo trắng */
.admin-mobile-logo {
    width: 48px;
    height: 36px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    padding: 6px 8px;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #6366f1 0%,
            #7c5ce7 52%,
            #8b5cf6 100%
        );

    border: 1px solid rgba(124, 92, 231, .25);

    box-shadow:
        0 4px 12px rgba(99, 102, 241, .18),
        inset 0 1px 0 rgba(255, 255, 255, .18);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}

.admin-mobile-logo img {
    display: block;

    width: 100%;
    height: 100%;

    object-fit: contain;

    filter:
        drop-shadow(
            0 1px 2px rgba(0, 0, 0, .12)
        );
}

.admin-mobile-brand-link:hover .admin-mobile-logo {
    transform: translateY(-1px);

    box-shadow:
        0 6px 16px rgba(99, 102, 241, .24),
        inset 0 1px 0 rgba(255, 255, 255, .2);
}

/* Chữ QuizTech */
.admin-mobile-brand-text {
    font-size: .95rem;
    font-weight: 800;

    letter-spacing: -.02em;

    color: #312e81;

    white-space: nowrap;
}


/* =========================================================
   MOBILE HEADER
   ========================================================= */

@media (max-width: 991.98px) {

    .admin-navbar {
        min-height: 64px;

        padding:
            9px 14px !important;

        background:
            rgba(255, 255, 255, .96) !important;

        border-bottom:
            1px solid #e9e7f5;

        box-shadow:
            0 4px 18px rgba(15, 23, 42, .055);

        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    /* Hamburger */
    .admin-navbar .btn-light {
        width: 40px;
        height: 40px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        padding: 0 !important;

        border-radius: 11px !important;

        color: #4f46e5;

        background: #f5f3ff;

        border:
            1px solid #e5e0ff !important;

        box-shadow: none;
    }

    .admin-navbar .btn-light:hover {
        color: #3730a3;
        background: #ede9fe;
        border-color: #d8d0ff !important;
    }

    /* Avatar */
    .admin-navbar .avatar-img-header {
        width: 38px;
        height: 38px;

        object-fit: cover;

        border-radius: 50%;

        border: 2px solid #ffffff;

        box-shadow:
            0 0 0 2px #e3ddff,
            0 3px 9px rgba(79, 70, 229, .14);
    }

    .admin-navbar .user-profile-badge {
        padding: 2px;
        border-radius: 50%;
    }
}


/* Mobile nhỏ */
@media (max-width: 480px) {

    .admin-navbar {
        padding:
            8px 10px !important;
    }

    .admin-navbar > .d-flex:first-child {
        gap: 7px !important;
    }

    .admin-mobile-logo {
        width: 43px;
        height: 34px;

        padding: 6px 7px;
    }

    .admin-mobile-brand-text {
        font-size: .88rem;
    }

    .admin-navbar .avatar-img-header {
        width: 36px;
        height: 36px;
    }
}


/* Màn hình rất nhỏ */
@media (max-width: 370px) {

    .admin-mobile-brand-text {
        display: none;
    }
}

    /* Sidebar desktop đồng bộ với giao diện giảng viên */
    :root { --sidebar-collapsed-width: 82px; }
    .admin-sidebar { min-width: var(--sidebar-width); flex: 0 0 var(--sidebar-width); overflow: hidden; position: sticky; top: 0; height: 100vh; }
    .admin-sidebar .sidebar-brand-wrapper { min-height: 78px; height: 78px; padding: 0 1rem 0 1.35rem; gap: .7rem !important; white-space: nowrap; overflow: hidden; }
    .admin-sidebar .brand-logo-img { flex-shrink: 0; }
    .admin-sidebar .brand-text { min-width: 0; overflow: hidden; }
    .admin-sidebar .sidebar-menu-wrapper { overflow-x: hidden; }
    .admin-sidebar .nav-link { white-space: nowrap; overflow: hidden; }
    .admin-sidebar .nav-link i { min-width: 24px; text-align: center; flex-shrink: 0; }
    .admin-sidebar .nav-text { overflow: hidden; text-overflow: ellipsis; }
    .admin-sidebar .sidebar-toggle-btn { width: 30px; height: 30px; flex: 0 0 30px; margin-left: auto; padding: 0; border: 0; border-radius: 8px; background: transparent; color: white; display: inline-flex; align-items: center; justify-content: center; }
    .admin-sidebar .sidebar-toggle-btn:hover, .admin-sidebar .sidebar-toggle-btn:focus-visible { background: rgba(255,255,255,.15); outline: 2px solid rgba(255,255,255,.5); outline-offset: 2px; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar { width: var(--sidebar-collapsed-width); min-width: var(--sidebar-collapsed-width); flex-basis: var(--sidebar-collapsed-width); }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .sidebar-brand-wrapper { padding: 0 .55rem; gap: .15rem !important; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .brand-text { display: none !important; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .sidebar-brand-wrapper { height: 110px; min-height: 110px; flex-direction: column; justify-content: center; gap: .4rem !important; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .sidebar-toggle-btn { margin-left: 0; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .sidebar-label { font-size: 0; height: 14px; padding: 0; margin: 0; overflow: hidden; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .nav-link { justify-content: center; padding: .75rem .5rem; margin-left: .65rem; margin-right: .65rem; gap: 0; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .nav-text { width: 0; opacity: 0; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .nav-link:hover { transform: none; }
    .admin-wrapper.sidebar-collapsed .admin-sidebar .sidebar-toggle-btn i { transform: rotate(180deg); }
    @media (prefers-reduced-motion: reduce) { .admin-sidebar, .admin-sidebar * { transition: none !important; } }
  </style>
<?php require_once __DIR__ . '/admin_theme.php'; ?>
</head>

<body>
<div class="admin-wrapper" id="adminWrapper">

  <!-- SIDEBAR DESKTOP -->
  <aside class="admin-sidebar d-none d-lg-flex" id="adminSidebar">
    <div class="sidebar-brand-wrapper d-flex align-items-center gap-3">
      <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png" alt="QuizTech" class="brand-logo-img">
      <div class="brand-text d-flex flex-column">
        <span class="fw-bold fs-5 text-white lh-1">QuizTech</span>
        <span class="text-white-50 small mt-1" style="font-size: 0.7rem; letter-spacing: 0.05em;"><?= e($role_badge_text) ?></span>
      </div>
      <button class="sidebar-toggle-btn" type="button" id="sidebarToggle" aria-label="Thu gọn sidebar" aria-expanded="true" aria-controls="adminSidebar" title="Thu gọn sidebar"><i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i></button>
    </div>

    <div class="sidebar-menu-wrapper">
      <div class="sidebar-label">Tổng Quan</div>
      <ul class="nav nav-pills flex-column mb-2">
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="<?= $base_url ?>dashboard.php">
            <i class="bi bi-speedometer2" aria-hidden="true"></i><span class="nav-text">Dashboard</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-label">Quản Lý Hệ Thống</div>
      <ul class="nav nav-pills flex-column mb-2">
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>" href="<?= $base_url ?>users.php">
            <i class="bi bi-people" aria-hidden="true"></i><span class="nav-text">Người dùng</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'subjects.php' ? 'active' : '' ?>" href="<?= $base_url ?>subjects.php">
            <i class="bi bi-journal-bookmark" aria-hidden="true"></i><span class="nav-text">Môn học</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= in_array($current_page, ['exams.php', 'questions.php', 'create_exam.php', 'exam_questions.php']) ? 'active' : '' ?>" href="<?= $base_url ?>exams.php">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span class="nav-text">Đề thi & Câu hỏi</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'rooms.php' ? 'active' : '' ?>" href="<?= $base_url ?>rooms.php">
            <i class="bi bi-door-open" aria-hidden="true"></i><span class="nav-text">Quản lý phòng</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= strpos($scriptPath, '/admin/practice/') !== false ? 'active' : '' ?>" href="<?= $base_url ?>practice/index.php">
            <i class="bi bi-controller" aria-hidden="true"></i><span class="nav-text">Practice</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'approve_items.php' ? 'active' : '' ?>" href="<?= $base_url ?>approve_items.php">
            <i class="bi bi-check2-square" aria-hidden="true"></i><span class="nav-text">Duyệt đề xuất</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-label">Báo Cáo & Cấu Hình</div>
      <ul class="nav nav-pills flex-column">
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'leaderboard.php' ? 'active' : '' ?>" href="<?= $base_url ?>leaderboard.php">
            <i class="bi bi-trophy" aria-hidden="true"></i><span class="nav-text">Bảng xếp hạng</span>
          </a>
        </li>
        <?php if ($is_super_admin): ?>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>" href="<?= $base_url ?>settings.php">
            <i class="bi bi-gear" aria-hidden="true"></i><span class="nav-text">Cài đặt hệ thống</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'reset_all.php' ? 'active' : '' ?>" href="<?= $base_url ?>reset_all.php">
            <i class="bi bi-arrow-repeat" aria-hidden="true"></i><span class="nav-text">Reset ID</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </aside>

  <!-- SIDEBAR MOBILE (OFFCANVAS) -->
  <div class="offcanvas offcanvas-start text-white border-0" tabindex="-1" id="mobileAdminSidebar" style="width: var(--sidebar-width);">
    <div class="offcanvas-header border-bottom border-white border-opacity-10 p-3" style="background: rgba(0, 0, 0, 0.15);">
      <!-- Brand Logo hiển thị trên Header Mobile khi Sidebar ẩn -->
<div class="d-flex align-items-center gap-2 d-lg-none ms-1">
  <span class="d-inline-flex align-items-center justify-content-center p-1.5 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #2e1065 0%, #4c1d95 100%);">
    <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png" alt="QuizTech" style="height: 55px; width: auto;">
  </span>
  <span class="fw-bold fs-3 text-light ms-1">QuizTech</span>
</div>  
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 py-3">
      <div class="sidebar-label">Tổng Quan</div>
      <ul class="nav nav-pills flex-column mb-2">
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="<?= $base_url ?>dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>
      </ul>

      <div class="sidebar-label">Quản Lý Hệ Thống</div>
      <ul class="nav nav-pills flex-column mb-2">
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>" href="<?= $base_url ?>users.php">
            <i class="bi bi-people"></i> Người dùng
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'subjects.php' ? 'active' : '' ?>" href="<?= $base_url ?>subjects.php">
            <i class="bi bi-journal-bookmark"></i> Môn học
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= in_array($current_page, ['exams.php', 'questions.php', 'create_exam.php', 'exam_questions.php']) ? 'active' : '' ?>" href="<?= $base_url ?>exams.php">
            <i class="bi bi-file-earmark-text"></i> Đề thi & Câu hỏi
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'rooms.php' ? 'active' : '' ?>" href="<?= $base_url ?>rooms.php">
            <i class="bi bi-door-open"></i> Quản lý phòng
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= strpos($scriptPath, '/admin/practice/') !== false ? 'active' : '' ?>" href="<?= $base_url ?>practice/index.php">
            <i class="bi bi-controller"></i> Practice
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'approve_items.php' ? 'active' : '' ?>" href="<?= $base_url ?>approve_items.php">
            <i class="bi bi-check2-square"></i> Duyệt đề xuất
          </a>
        </li>
      </ul>

      <div class="sidebar-label">Báo Cáo & Cấu Hình</div>
      <ul class="nav nav-pills flex-column">
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'leaderboard.php' ? 'active' : '' ?>" href="<?= $base_url ?>leaderboard.php">
            <i class="bi bi-trophy"></i> Bảng xếp hạng
          </a>
        </li>
        <?php if ($is_super_admin): ?>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>" href="<?= $base_url ?>settings.php">
            <i class="bi bi-gear"></i> Cài đặt hệ thống
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'reset_all.php' ? 'active' : '' ?>" href="<?= $base_url ?>reset_all.php">
            <i class="bi bi-arrow-repeat"></i> Reset ID
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- MAIN CONTENT AREA -->
  <div class="admin-content">
    
    <!-- TOP NAVBAR -->
    <header class="navbar navbar-expand admin-navbar sticky-top">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-light rounded-circle p-2 d-lg-none border" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileAdminSidebar">
          <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Brand Logo hiển thị trên Header Mobile khi Sidebar ẩn -->
        <div class="admin-mobile-brand d-lg-none ms-1">

    <a href="<?= e($base_url) ?>dashboard.php"
       class="admin-mobile-brand-link">

        <span class="admin-mobile-logo">
            <img src="../assets/images/Cardmoi_PLT_Trang.png"
                 alt="QuizTech">
        </span>

        <span class="admin-mobile-brand-text">
            QuizTech
        </span>

    </a>

</div>
      </div>

      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="dropdown">
          <a class="d-flex align-items-center gap-2 text-decoration-none user-profile-badge" href="#" id="adminProfileDrop" data-bs-toggle="dropdown" aria-expanded="false">
            <?php
              $raw_avatar = $user['avatar'] ?? $_SESSION['avatar'] ?? $_SESSION['user']['avatar'] ?? '';
              // DB lưu avatar dưới dạng đường dẫn từ gốc hoặc chỉ tên file.
              $clean_avatar_path = ltrim(str_replace('\\', '/', (string) $raw_avatar), '/');
              if ($clean_avatar_path !== '' && strpos($clean_avatar_path, '/') === false) {
                $clean_avatar_path = 'uploads/avatars/' . $clean_avatar_path;
              }
              if (strpos($clean_avatar_path, '..') !== false || !preg_match('~^uploads/avatars/[a-zA-Z0-9._/-]+$~', $clean_avatar_path)) {
                $clean_avatar_path = '';
              }
              $server_file_path  = __DIR__ . '/../' . $clean_avatar_path;
              $has_avatar        = !empty($clean_avatar_path) && file_exists($server_file_path);
            ?>

            <?php if ($has_avatar): ?>
              <img src="<?= e($prefix . $clean_avatar_path) ?>?v=<?= time() ?>" alt="Avatar" class="avatar-img-header">
            <?php else: ?>
              <div class="avatar-placeholder-header">
                <?= strtoupper(mb_substr($user_name, 0, 1, 'UTF-8')) ?>
              </div>
            <?php endif; ?>

            <div class="d-none d-md-block text-start pe-1">
              <div class="fw-semibold text-dark small lh-1"><?= e($user_name) ?></div>
            </div>
            <i class="bi bi-chevron-down text-muted small ms-1 d-none d-md-inline"></i>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-admin" aria-labelledby="adminProfileDrop">
            <li class="px-3 py-2 border-bottom mb-1">
              <div class="fw-bold text-dark mb-0"><?= e($user_name) ?></div>
              <div class="text-muted small text-truncate" style="max-width: 200px;"><?= e($user_email) ?></div>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2.5" href="<?= e($base_url) ?>profile.php">
                <i class="bi bi-person-badge text-primary fs-6"></i> Hồ sơ cá nhân
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2.5" href="<?= e($prefix) ?>student/dashboard.php" target="_blank">
                <i class="bi bi-box-arrow-up-right text-success fs-6"></i> Giao diện Sinh viên
              </a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2.5 text-danger" href="<?= e($prefix) ?>logout.php">
                <i class="bi bi-box-arrow-right fs-6"></i> Đăng xuất
              </a>
            </li>
          </ul>
        </div>
      </div>
    </header>

    <!-- FLASH MESSAGES -->
    <?php if (function_exists('getFlash')): ?>
      <?php $flash = getFlash(); ?>
      <?php if ($flash && isset($flash['type']) && isset($flash['message'])): ?>
        <div class="px-3 px-lg-4 mt-3">
          <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show alert-modern mb-0" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <main class="p-3 p-lg-4">
    <script>
      (() => {
        const wrapper = document.getElementById('adminWrapper');
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');
        const key = 'quiztech_admin_sidebar_collapsed';
        if (!wrapper || !toggle || !sidebar) return;
        const apply = collapsed => {
          wrapper.classList.toggle('sidebar-collapsed', collapsed);
          const label = collapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar';
          toggle.setAttribute('aria-label', label);
          toggle.setAttribute('title', label);
          toggle.setAttribute('aria-expanded', String(!collapsed));
          sidebar.querySelectorAll('.nav-link').forEach(link => {
            if (collapsed) link.title = link.querySelector('.nav-text')?.textContent.trim() || '';
            else link.removeAttribute('title');
          });
        };
        try { apply(localStorage.getItem(key) === '1'); } catch (_) { apply(false); }
        toggle.addEventListener('click', () => {
          const collapsed = !wrapper.classList.contains('sidebar-collapsed');
          apply(collapsed);
          try { localStorage.setItem(key, collapsed ? '1' : '0'); } catch (_) {}
        });
      })();
    </script>
