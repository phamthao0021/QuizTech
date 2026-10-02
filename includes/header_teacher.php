<?php
// includes/header_teacher.php - Header & Sidebar đồng bộ chuẩn cho Giảng viên
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$page_title   = $page_title ?? 'QuizTech - Giảng viên';
$current_page = basename($_SERVER['PHP_SELF']);

// Đường dẫn chính xác cả khi trang giảng viên nằm trong thư mục con.
$scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$teacherPos = strpos($scriptPath, '/teacher/');
$in_teacher = $teacherPos !== false;
$afterTeacher = $in_teacher ? trim(substr($scriptPath, $teacherPos + 9), '/') : '';
$teacherDepth = ($in_teacher && $afterTeacher !== '') ? substr_count($afterTeacher, '/') : 0;
$prefix = $in_teacher ? str_repeat('../', $teacherDepth + 1) : '';
$base_url = $in_teacher ? str_repeat('../', $teacherDepth) : 'teacher/';

$user         = currentUser();
$is_logged_in = isLoggedIn();
$user_name    = $user['name'] ?? $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Giảng viên';
$user_email   = $user['email'] ?? $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> - QuizTech Teacher</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" crossorigin="anonymous">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="<?= $prefix ?>assets/css/style.css" rel="stylesheet">
  <link href="<?= $prefix ?>assets/css/guest.css" rel="stylesheet">

  <style>
    :root {
      /* Cùng bảng màu tím với Admin */
      --teacher-main: #6366f1;
      --teacher-dark: #4f46e5;
      --teacher-deep: #3730a3;
      --teacher-night: #1e1b4b;
      --teacher-glow: rgba(99, 102, 241, 0.35);
      --teacher-light: #e0e7ff;
      --bg-canvas: #f8fafc;
      --text-dark: #1e1b4b;

      --sidebar-width: 270px;
      --sidebar-collapsed-width: 82px;
      --sidebar-transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);

      /* Alias tương thích CSS cũ trong file */
      --purple-main: var(--teacher-main);
      --purple-dark: var(--teacher-dark);
      --purple-deep: var(--teacher-deep);
      --purple-night: var(--teacher-night);
      --purple-glow: var(--teacher-glow);
      --purple-light: var(--teacher-light);
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif;
      background-color: var(--bg-canvas);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    .teacher-wrapper {
      display: flex;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* =========================================================
       SIDEBAR
       ========================================================= */
    aside.teacher-sidebar,
    #mobileTeacherSidebar {
      background: linear-gradient(180deg, #2e1065 0%, #4c1d95 60%, #3b0764 100%);
      color: #ffffff;
    }

    aside.teacher-sidebar {
      width: var(--sidebar-width);
      min-width: var(--sidebar-width);
      flex: 0 0 var(--sidebar-width);
      transition: var(--sidebar-transition);
      z-index: 1020;
      box-shadow: 4px 0 25px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      position: sticky;
      top: 0;
      height: auto;
      min-height: 100vh;
      align-self: stretch;
    }

    /* Sidebar thu gọn trên desktop */
    .teacher-wrapper.sidebar-collapsed aside.teacher-sidebar {
      width: var(--sidebar-collapsed-width);
      min-width: var(--sidebar-collapsed-width);
      flex-basis: var(--sidebar-collapsed-width);
    }

    .sidebar-brand-wrapper {
      height: 78px;
      min-height: 78px;
      padding: 0 1rem 0 1.35rem;
      background: rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 0.7rem;
      transition: var(--sidebar-transition);
      overflow: hidden;
      white-space: nowrap;
    }

    .brand-logo-img {
      height: 38px;
      width: auto;
      flex-shrink: 0;
      filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.2));
    }

    .brand-text {
      min-width: 0;
      opacity: 1;
      transform: translateX(0);
      transition: opacity 0.18s ease, transform 0.25s ease;
    }

    .teacher-wrapper.sidebar-collapsed .sidebar-brand-wrapper {
      justify-content: center;
      padding-left: 0.55rem;
      padding-right: 0.55rem;
      gap: 0.15rem;
    }

    .teacher-wrapper.sidebar-collapsed .sidebar-brand-wrapper { height: 110px; min-height: 110px; flex-direction: column; justify-content: center; gap: .4rem; }

    .teacher-wrapper.sidebar-collapsed .brand-text {
      display: none !important;
      opacity: 0;
      width: 0;
      transform: translateX(-8px);
      pointer-events: none;
    }

    .sidebar-menu-wrapper {
      padding: 1rem 0;
      flex-grow: 1;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .sidebar-label {
      font-size: 0.68rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #c084fc;
      font-weight: 700;
      padding: 0.85rem 1.5rem 0.35rem;
      opacity: 0.85;
      white-space: nowrap;
      transition: var(--sidebar-transition);
    }

    .teacher-wrapper.sidebar-collapsed .sidebar-label {
      font-size: 0;
      height: 14px;
      padding: 0;
      margin: 0;
      opacity: 0;
    }

    .teacher-sidebar .nav-link,
    #mobileTeacherSidebar .nav-link {
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
      white-space: nowrap;
      overflow: hidden;
    }

    .teacher-sidebar .nav-link i,
    #mobileTeacherSidebar .nav-link i {
      font-size: 1.15rem;
      min-width: 24px;
      text-align: center;
      flex-shrink: 0;
      transition: transform 0.25s ease;
    }

    .teacher-sidebar .nav-link span.nav-text {
      opacity: 1;
      transform: translateX(0);
      transition: opacity 0.18s ease, transform 0.25s ease;
    }

    .teacher-sidebar .nav-link:hover,
    #mobileTeacherSidebar .nav-link:hover {
      color: #ffffff !important;
      background: rgba(255, 255, 255, 0.12) !important;
      transform: translateX(4px);
    }

    .teacher-sidebar .nav-link:hover i,
    #mobileTeacherSidebar .nav-link:hover i {
      transform: scale(1.08);
    }

    .teacher-sidebar .nav-link.active,
    #mobileTeacherSidebar .nav-link.active {
      color: #ffffff !important;
      background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%) !important;
      box-shadow: 0 8px 20px rgba(168, 85, 247, 0.35);
      font-weight: 600;
    }

    /* Khi thu gọn: chỉ còn icon */
    .teacher-wrapper.sidebar-collapsed .teacher-sidebar .nav-link {
      justify-content: center;
      padding-left: 0.5rem;
      padding-right: 0.5rem;
      margin-left: 0.65rem;
      margin-right: 0.65rem;
      gap: 0;
    }

    .teacher-wrapper.sidebar-collapsed .teacher-sidebar .nav-link span.nav-text {
      opacity: 0;
      width: 0;
      transform: translateX(-8px);
      pointer-events: none;
    }

    .teacher-wrapper.sidebar-collapsed .teacher-sidebar .nav-link:hover {
      transform: translateX(0);
    }

    /* Tooltip native khi sidebar đang thu gọn */
    .teacher-wrapper.sidebar-collapsed .teacher-sidebar .nav-link[title] {
      position: relative;
    }

    /* =========================================================
       MAIN CONTENT
       ========================================================= */
    .teacher-content {
      flex-grow: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      background-color: var(--bg-canvas);
    }

    .teacher-navbar {
      height: 78px;
      min-height: 78px;
      box-sizing: border-box;
      background: rgba(255, 255, 255, 0.85) !important;
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      padding: 0 1.5rem;
      z-index: 1010;
    }

    /* Nút thu gọn: đặt ngay trong header sidebar, giống mẫu PLT / WordWise */
    .sidebar-toggle-btn {
      width: 30px;
      height: 30px;
      flex: 0 0 30px;
      margin-left: auto;
      padding: 0;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: rgba(255, 255, 255, 0.92);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      line-height: 1;
      transition: all 0.2s ease;
    }

    .sidebar-toggle-btn:hover {
      background: rgba(255, 255, 255, 0.12);
      color: #ffffff;
      transform: none;
      box-shadow: none;
    }

    .sidebar-toggle-btn:active {
      background: rgba(255, 255, 255, 0.18);
    }

    .sidebar-toggle-btn i {
      font-size: 1.1rem;
      transition: transform 0.25s ease;
    }

    .teacher-wrapper.sidebar-collapsed .sidebar-toggle-btn i {
      transform: rotate(180deg);
    }

    .teacher-wrapper.sidebar-collapsed .sidebar-toggle-btn {
      margin-left: 0;
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
      border-color: #c4b5fd;
      box-shadow: 0 6px 18px rgba(109, 40, 217, 0.10);
      transform: translateY(-1px);
    }

    .avatar-img-header {
      width: 38px;
      height: 38px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid var(--purple-main);
    }

    .avatar-placeholder-header {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, #a855f7, #4f46e5);
      color: #ffffff;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
    }

    .dropdown-menu-teacher {
      border-radius: 20px;
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 20px 35px rgba(15, 23, 42, 0.08);
      padding: 0.6rem;
      min-width: 240px;
    }

    .dropdown-menu-teacher .dropdown-item {
      border-radius: 10px;
      padding-top: 0.6rem;
      padding-bottom: 0.6rem;
      transition: all 0.2s ease;
    }

    .dropdown-menu-teacher .dropdown-item:hover {
      background: #f5f3ff;
      transform: translateX(2px);
    }

    .alert-modern {
      border-radius: 16px;
      border: none;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
      padding: 1rem 1.25rem;
    }

    @media (max-width: 991.98px) {
      aside.teacher-sidebar {
        display: none !important;
      }

      .teacher-navbar {
        padding: 0.75rem 1rem;
      }

      /* Mobile dùng offcanvas, không dùng trạng thái collapse desktop */
      .sidebar-toggle-btn {
        display: none !important;
      }
    }

    @media (prefers-reduced-motion: reduce) {

      aside.teacher-sidebar,
      .sidebar-brand-wrapper,
      .brand-text,
      .teacher-sidebar .nav-link,
      .teacher-sidebar .nav-link span.nav-text,
      .sidebar-toggle-btn,
      .sidebar-toggle-btn i {
        transition: none !important;
      }
    }

    /* ===== QuizTech Teacher UI consistency layer (CSS only) ===== */
    .teacher-content main,
    .teacher-main,
    body {
      --role-main: #6366f1;
      --role-dark: #4f46e5;
      --role-soft: #e0e7ff
    }

    .teacher-content main .page-header,
    .teacher-content main .dashboard-header,
    .teacher-content main .teacher-page-head,
    main .teacher-page-head {
      background: linear-gradient(135deg, #4f46e5 0%, #6366f1 55%, #a855f7 100%) !important;
      color: #fff !important;
      border-color: transparent !important;
      border-radius: 20px !important;
      box-shadow: 0 12px 28px rgba(13, 148, 136, .16) !important
    }

    .teacher-content main .page-header h1,
    .teacher-content main .page-header h2,
    .teacher-content main .page-header h3,
    .teacher-content main .page-header h4,
    .teacher-content main .dashboard-header h1,
    .teacher-content main .dashboard-header h2,
    .teacher-content main .dashboard-header h3,
    .teacher-content main .dashboard-header h4 {
      color: #fff !important
    }

    .teacher-content main .page-header .text-muted,
    .teacher-content main .dashboard-header .text-muted {
      color: rgba(255, 255, 255, .78) !important
    }

    .teacher-content main .card:not(.page-header):not(.dashboard-header):not(.teacher-page-head) {
      background: #fff;
      border: 1px solid #dceeea;
      border-radius: 18px;
      box-shadow: 0 8px 24px rgba(15, 118, 110, .05)
    }

    .teacher-content main .table thead th {
      background: #f8fafc;
      color: #52736f;
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .04em;
      white-space: nowrap
    }

    .teacher-content main .btn-primary {
      background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
      border-color: transparent !important
    }

    .teacher-content main .form-control:focus,
    .teacher-content main .form-select:focus {
      border-color: #2dd4bf;
      box-shadow: 0 0 0 .2rem rgba(13, 148, 136, .12)
    }

    .teacher-content main .pagination .active>.page-link {
      background: #6366f1;
      border-color: #6366f1
    }

    @media(max-width:767.98px) {

      .teacher-content main .page-header,
      .teacher-content main .dashboard-header,
      .teacher-content main .teacher-page-head {
        border-radius: 16px !important;
        padding: 16px !important
      }

      .teacher-content main .table-responsive {
        overflow-x: auto
      }

      .teacher-content main .table {
        min-width: 720px
      }
    }
    .sidebar-toggle-btn:focus-visible { outline: 2px solid rgba(255,255,255,.6); outline-offset: 2px; }
  </style>
</head>

<body>
  <div class="teacher-wrapper" id="teacherWrapper">

    <!-- SIDEBAR DESKTOP -->
    <aside class="teacher-sidebar d-none d-lg-flex" id="teacherSidebar">
      <div class="sidebar-brand-wrapper">
        <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png" alt="QuizTech" class="brand-logo-img">

        <div class="brand-text d-flex flex-column">
          <span class="fw-bold fs-5 text-white lh-1">QuizTech</span>
          <span class="text-white-50 small mt-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">GIẢNG VIÊN</span>
        </div>

        <!-- Nút thu gọn nằm ngay trong header sidebar -->
        <button class="sidebar-toggle-btn d-inline-flex"
          type="button"
          id="sidebarToggle"
          aria-controls="teacherSidebar"
          aria-expanded="true"
          aria-label="Thu gọn sidebar"
          title="Thu gọn sidebar">
          <i class="bi bi-layout-sidebar-inset"></i>
        </button>
      </div>

      <div class="sidebar-menu-wrapper">

        <div class="sidebar-label">Tổng Quan</div>
        <ul class="nav nav-pills flex-column mb-2">
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>dashboard.php"
              title="Tổng quan">
              <i class="bi bi-speedometer2"></i>
              <span class="nav-text">Tổng quan</span>
            </a>
          </li>
        </ul>

        <div class="sidebar-label">Giảng Dạy & Thi</div>
        <ul class="nav nav-pills flex-column mb-2">
          <li class="nav-item">
            <a class="nav-link <?= in_array($current_page, ['exams.php', 'exam_detail.php', 'create_exam.php']) ? 'active' : '' ?>"
              href="<?= $base_url ?>exams.php"
              title="Quản lý đề thi">
              <i class="bi bi-file-earmark-text"></i>
              <span class="nav-text">Quản lý đề thi</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'questions.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>questions.php"
              title="Ngân hàng câu hỏi">
              <i class="bi bi-question-circle"></i>
              <span class="nav-text">Ngân hàng câu hỏi</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'rooms.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>rooms.php"
              title="Phòng thi">
              <i class="bi bi-door-open"></i>
              <span class="nav-text">Phòng thi</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'subjects.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>subjects.php"
              title="Quản lý môn học">
              <i class="bi bi-book"></i>
              <span class="nav-text">Quản lý môn học</span>
            </a>
          </li>
        </ul>

        <div class="sidebar-label">Thống Kê & Báo Cáo</div>
        <ul class="nav nav-pills flex-column">
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'results.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>results.php"
              title="Kết quả & Thống kê">
              <i class="bi bi-bar-chart-line"></i>
              <span class="nav-text">Kết quả & Thống kê</span>
            </a>
          </li>
        </ul>
      </div>
    </aside>

    <!-- SIDEBAR MOBILE -->
    <div class="offcanvas offcanvas-start text-white border-0"
      tabindex="-1"
      id="mobileTeacherSidebar"
      style="width: var(--sidebar-width);">

      <div class="offcanvas-header border-bottom border-white border-opacity-10 p-3"
        style="background: rgba(0, 0, 0, 0.15);">

        <div class="d-flex align-items-center gap-2 d-lg-none ms-1">
          <span class="d-inline-flex align-items-center justify-content-center p-1 rounded-3 shadow-sm"
            style="background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%);">
            <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png"
              alt="QuizTech"
              style="height: 35px; width: auto;">
          </span>
          <span class="fw-bold fs-4 text-light ms-1">QuizTech</span>
        </div>

        <button type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="offcanvas"
          aria-label="Close"></button>
      </div>

      <div class="offcanvas-body p-0 py-3">

        <div class="sidebar-label">Tổng Quan</div>
        <ul class="nav nav-pills flex-column mb-2">
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>dashboard.php">
              <i class="bi bi-speedometer2"></i>
              <span class="nav-text">Tổng quan</span>
            </a>
          </li>
        </ul>

        <div class="sidebar-label">Giảng Dạy & Thi</div>
        <ul class="nav nav-pills flex-column mb-2">
          <li class="nav-item">
            <a class="nav-link <?= in_array($current_page, ['exams.php', 'exam_detail.php', 'create_exam.php']) ? 'active' : '' ?>"
              href="<?= $base_url ?>exams.php">
              <i class="bi bi-file-earmark-text"></i>
              <span class="nav-text">Quản lý đề thi</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'questions.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>questions.php">
              <i class="bi bi-question-circle"></i>
              <span class="nav-text">Ngân hàng câu hỏi</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'rooms.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>rooms.php">
              <i class="bi bi-door-open"></i>
              <span class="nav-text">Quản lý phòng thi</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'subjects.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>subjects.php">
              <i class="bi bi-book"></i>
              <span class="nav-text">Quản lý môn học</span>
            </a>
          </li>
        </ul>

        <div class="sidebar-label">Thống Kê & Báo Cáo</div>
        <ul class="nav nav-pills flex-column">
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'results.php' ? 'active' : '' ?>"
              href="<?= $base_url ?>results.php">
              <i class="bi bi-bar-chart-line"></i>
              <span class="nav-text">Kết quả & Thống kê</span>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="teacher-content">

      <!-- TOP NAVBAR -->
      <header class="navbar navbar-expand teacher-navbar sticky-top">

        <div class="d-flex align-items-center gap-2">

          <!-- Mobile: mở offcanvas -->
          <button class="btn btn-light rounded-circle p-2 d-lg-none border"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileTeacherSidebar"
            aria-label="Mở menu">
            <i class="bi bi-list fs-5"></i>
          </button>

          <div class="d-flex align-items-center gap-2 d-lg-none ms-1">
            <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png"
              alt="QuizTech"
              style="height: 28px; width: auto; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.12));">
            <span class="fw-bold fs-6 text-dark">QuizTech</span>
          </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-3">
          <div class="dropdown">

            <a class="d-flex align-items-center gap-2 text-decoration-none user-profile-badge"
              href="#"
              id="teacherProfileDrop"
              data-bs-toggle="dropdown"
              aria-expanded="false">

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
                <img src="<?= e($prefix . $clean_avatar_path) ?>?v=<?= time() ?>"
                  alt="Avatar"
                  class="avatar-img-header">
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

            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-teacher"
              aria-labelledby="teacherProfileDrop">

              <li class="px-3 py-2 border-bottom mb-1">
                <div class="fw-bold text-dark mb-0"><?= e($user_name) ?></div>
                <div class="text-muted small text-truncate" style="max-width: 200px;">
                  <?= e($user_email) ?>
                </div>
              </li>

              <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                  href="<?= e($base_url) ?>profile.php">
                  <i class="bi bi-person-badge text-primary fs-6"></i>
                  Hồ sơ cá nhân
                </a>
              </li>

              <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                  href="<?= e($prefix) ?>student/dashboard.php"
                  target="_blank">
                  <i class="bi bi-box-arrow-up-right text-success fs-6"></i>
                  Giao diện Sinh viên
                </a>
              </li>

              <li>
                <hr class="dropdown-divider my-1">
              </li>

              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                  href="<?= e($prefix) ?>logout.php">
                  <i class="bi bi-box-arrow-right fs-6"></i>
                  Đăng xuất
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
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show alert-modern mb-0"
              role="alert">
              <i class="bi bi-info-circle-fill me-2"></i><?= e($flash['message']) ?>
              <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <main class="p-3 p-lg-4">

        <script>
          (function() {
            const wrapper = document.getElementById('teacherWrapper');
            const toggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('teacherSidebar');
            const storageKey = 'quiztech_teacher_sidebar_collapsed';

            if (!wrapper || !toggle || !sidebar) return;

            function applySidebarState(collapsed) {
              wrapper.classList.toggle('sidebar-collapsed', collapsed);
              toggle.setAttribute('aria-expanded', String(!collapsed));
              sidebar.querySelectorAll('.nav-link').forEach(link => {
                if (collapsed) link.title = link.querySelector('.nav-text')?.textContent.trim() || '';
                else link.removeAttribute('title');
              });

              toggle.setAttribute(
                'aria-label',
                collapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'
              );

              toggle.setAttribute(
                'title',
                collapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'
              );
            }

            let collapsed = false;

            try {
              collapsed = localStorage.getItem(storageKey) === '1';
            } catch (e) {
              collapsed = false;
            }

            applySidebarState(collapsed);

            toggle.addEventListener('click', function() {
              collapsed = !wrapper.classList.contains('sidebar-collapsed');
              applySidebarState(collapsed);

              try {
                localStorage.setItem(storageKey, collapsed ? '1' : '0');
              } catch (e) {
                // Không làm gián đoạn giao diện nếu localStorage bị chặn.
              }
            });
          })();
        </script>