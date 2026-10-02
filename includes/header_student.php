<?php
// includes/header_student.php - Header Student Soft Purple Gradient Modern UI
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$page_title   = $page_title ?? 'QuizTech - Học viên';
$current_page = basename($_SERVER['PHP_SELF']);

// Xác định vị trí file gọi để tính toán prefix đường dẫn
$in_student = strpos($_SERVER['PHP_SELF'], '/student/') !== false;
$prefix     = $in_student ? '../' : '';
$base_url   = $in_student ? '' : 'student/';

$user         = currentUser();  
$is_logged_in = isLoggedIn();
$user_name    = $user['name'] ?? $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Sinh viên';
$user_email   = $user['email'] ?? $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> - QuizTech</title>

  <!-- Bootstrap 5 CSS & Icons -->
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    crossorigin="anonymous">

  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
    crossorigin="anonymous">

  <!-- OCR Tesseract.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/tesseract.js/5.0.4/tesseract.min.js"></script>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="<?= $prefix ?>assets/css/style.css" rel="stylesheet">
  <link href="<?= $prefix ?>assets/css/guest.css" rel="stylesheet">

  <style>
    :root {
      /* Student: soft indigo học tập — khác Admin (tím đậm sidebar) & Teacher (teal) */
      --student-main: #6366f1;
      --student-soft: #818cf8;
      --student-accent: #8b5cf6;
      --purple-light-bg: linear-gradient(135deg, #6366f1 0%, #818cf8 48%, #8b5cf6 100%);
      --font-primary: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      --bg-canvas: #f8fafc;
      --text-dark: #0f172a;
      --card-radius: 1.1rem;
      --btn-radius: 0.7rem;
    }

    body {
      font-family: var(--font-primary);
      background-color: var(--bg-canvas);
      color: var(--text-dark);
    }

    .student-main .card,
    main .card {
      border-radius: var(--card-radius);
      border: 1px solid #e8ecf4;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .student-main .btn,
    main .btn {
      border-radius: var(--btn-radius);
      font-weight: 650;
    }

    .student-main .form-control,
    .student-main .form-select,
    main .form-control,
    main .form-select {
      border-radius: 0.7rem;
      min-height: 42px;
    }

    .student-main .page-header,
    main .page-header,
    main .dashboard-header {
      border-radius: 1.15rem;
      background: var(--purple-light-bg);
      color: #fff;
      padding: 1.25rem 1.35rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 12px 28px rgba(99, 102, 241, 0.18);
    }

    /* Modern Soft Purple Navbar */
    .student-navbar {
      background: var(--purple-light-bg) !important;
      box-shadow: 0 8px 24px -4px rgba(139, 92, 246, 0.28);
      padding-top: 0.55rem;
      padding-bottom: 0.55rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Brand Logo & Title */
    .student-navbar .navbar-brand {
      font-weight: 800;
      letter-spacing: -0.02em;
      font-size: clamp(1.15rem, 1.5vw, 1.35rem);
      color: #ffffff !important;
      white-space: nowrap;
    }

    .student-navbar .brand-logo {
      height: clamp(32px, 3vw, 38px);
      width: auto;
      margin-right: 8px;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
    }

    .student-navbar .brand-badge {
      font-size: 0.65rem;
      font-weight: 700;
      background: rgba(255, 255, 255, 0.22);
      border: 1px solid rgba(255, 255, 255, 0.35);
      padding: 2px 8px;
      border-radius: 20px;
      color: #ffffff;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin-left: 6px;
    }

    /* Dynamic Horizontal Navigation Links */
    .student-navbar .navbar-nav {
      gap: clamp(0.15rem, 0.4vw, 0.4rem);
    }

    .student-navbar .nav-link {
      color: rgba(255, 255, 255, 0.9) !important;
      font-weight: 600;
      font-size: clamp(0.82rem, 0.88vw, 0.9rem);
      padding: 0.48rem clamp(0.5rem, 0.75vw, 0.85rem) !important;
      border-radius: 0.65rem;
      transition: all 0.2s ease-in-out;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      white-space: nowrap; /* Chống đè chữ/rớt dòng */
    }

    .student-navbar .nav-link i {
      font-size: 0.95rem;
      opacity: 0.9;
    }

    /* Link Hover & Active State */
    .student-navbar .nav-link:hover {
      color: #ffffff !important;
      background: rgba(255, 255, 255, 0.18);
      transform: translateY(-1px);
    }

    .student-navbar .nav-link.active {
      color: #ffffff !important;
      background: rgba(255, 255, 255, 0.28);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      font-weight: 700;
    }

    /* Nút Luyện tập tự do nổi bật nhẹ */
    .nav-link.practice-pill {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 235, 150, 0.4);
      color: #ffffff !important;
    }

    .nav-link.practice-pill:hover {
      background: rgba(255, 255, 255, 0.28);
    }

    /* User Pill Button */
    .user-pill-btn {
      background: rgba(255, 255, 255, 0.18);
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 0.3rem 0.75rem;
      border-radius: 50rem;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .user-pill-btn:hover,
    .user-pill-btn[aria-expanded="true"] {
      background: rgba(255, 255, 255, 0.3);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .avatar-img-header {
      width: 32px;
      height: 32px;
      object-fit: cover;
      flex-shrink: 0;
    }

    /* Dropdown Animated Menu */
    .student-navbar .dropdown-menu {
      background: #ffffff;
      border: 1px solid rgba(226, 232, 240, 0.9);
      border-radius: 0.85rem;
      box-shadow: 0 15px 30px -5px rgba(15, 23, 42, 0.18);
      padding: 0.5rem;
      min-width: 220px;
      animation: dropdownFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes dropdownFadeIn {
      from {
        opacity: 0;
        transform: translateY(8px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .student-navbar .dropdown-item {
      border-radius: 0.5rem;
      font-weight: 600;
      font-size: 0.88rem;
      padding: 0.55rem 0.8rem;
      color: #334155;
      transition: all 0.15s ease;
    }

    .student-navbar .dropdown-item:hover {
      background-color: #f3e8ff;
      color: #7c3aed;
    }

    .student-navbar .dropdown-item.text-danger:hover {
      background-color: #fef2f2;
      color: #dc2626 !important;
    }

    /* Mobile & Tablet Responsive Layout Rules */
    @media (max-width: 991.98px) {
      .student-navbar .navbar-collapse {
        background: rgba(109, 40, 217, 0.96);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 1rem;
        border-radius: 0.85rem;
        margin-top: 0.65rem;
        border: 1px solid rgba(255, 255, 255, 0.25);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      }

      .student-navbar .navbar-nav {
        gap: 0.35rem;
      }

      .student-navbar .nav-link {
        padding: 0.65rem 0.9rem !important;
        width: 100%;
        justify-content: flex-start;
      }

      .user-pill-btn {
        width: 100%;
        justify-content: flex-start;
        border-radius: 0.65rem;
        margin-top: 0.5rem;
      }
    }

    /* Responsive name clipping for laptops with smaller widths */
    @media (min-width: 992px) and (max-width: 1200px) {
      .user-name-text {
        max-width: 90px !important;
      }
    }
  
/* ===== QuizTech Student UI consistency layer (CSS only) ===== */
.student-main,main{--role-main:#6366f1;--role-dark:#4f46e5;--role-soft:#eef2ff}
.student-main .page-header,.student-main .dashboard-header,.student-main .student-page-head,
main .page-header,main .dashboard-header,main .student-page-head{
 background:linear-gradient(135deg,#6366f1 0%,#818cf8 52%,#8b5cf6 100%)!important;
 color:#fff!important;border-color:transparent!important;border-radius:20px!important;
 box-shadow:0 12px 28px rgba(99,102,241,.16)!important
}
.student-main .page-header h1,.student-main .page-header h2,.student-main .page-header h3,.student-main .page-header h4,
main .page-header h1,main .page-header h2,main .page-header h3,main .page-header h4,
main .dashboard-header h1,main .dashboard-header h2,main .dashboard-header h3,main .dashboard-header h4{color:#fff!important}
.student-main .page-header .text-muted,main .page-header .text-muted,main .dashboard-header .text-muted{color:rgba(255,255,255,.78)!important}
.student-main .card:not(.page-header):not(.dashboard-header):not(.student-page-head),main .card:not(.page-header):not(.dashboard-header):not(.student-page-head){background:#fff;border:1px solid #e7e9f4;border-radius:18px;box-shadow:0 8px 24px rgba(79,70,229,.045)}
.student-main .table thead th,main .table thead th{background:#f7f7ff;color:#626b80;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.student-main .btn-primary,main .btn-primary{background:linear-gradient(135deg,#6366f1,#4f46e5)!important;border-color:transparent!important}
.student-main .form-control:focus,.student-main .form-select:focus,main .form-control:focus,main .form-select:focus{border-color:#818cf8;box-shadow:0 0 0 .2rem rgba(99,102,241,.12)}
.student-main .pagination .active>.page-link,main .pagination .active>.page-link{background:#6366f1;border-color:#6366f1}
@media(max-width:767.98px){.student-main .page-header,main .page-header,main .dashboard-header,main .student-page-head{border-radius:16px!important;padding:16px!important}.student-main .table-responsive,main .table-responsive{overflow-x:auto}.student-main .table,main .table{min-width:680px}}

</style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark student-navbar sticky-top">
    <div class="container">
      <!-- Logo Brand -->
      <a class="navbar-brand d-flex align-items-center" href="<?= $base_url ?>dashboard.php">
        <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png" alt="QuizTech Logo" class="brand-logo">
        <span>QuizTech</span>
        <span class="brand-badge d-none d-sm-inline-block">Student</span>
      </a>

      <!-- Mobile Navbar Toggler -->
      <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarStudent" aria-controls="navbarStudent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarStudent">
        <!-- 1. STUDENT MENU -->
        <ul class="navbar-nav me-auto ms-lg-2 py-2 py-lg-0 align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="<?= $base_url ?>dashboard.php">
              <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'subjects.php' ? 'active' : '' ?>" href="<?= $base_url ?>subjects.php">
              <i class="bi bi-journal-bookmark-fill"></i> Môn học
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'exams.php' ? 'active' : '' ?>" href="<?= $base_url ?>exams.php">
              <i class="bi bi-journal-text"></i> Đề thi
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'rooms.php' ? 'active' : '' ?>" href="<?= $base_url ?>rooms.php">
              <i class="bi bi-door-open-fill"></i> Phòng thi
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link practice-pill <?= in_array($current_page, ['practice.php', 'wrong_question.php']) ? 'active' : '' ?>" href="<?= $base_url ?>practice.php">
              <i class="bi bi-lightning-charge-fill text-warning"></i> Luyện tập tự do
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page == 'leaderboard.php' ? 'active' : '' ?>" href="<?= $base_url ?>leaderboard.php">
              <i class="bi bi-trophy-fill"></i> Bảng xếp hạng
            </a>
          </li>
        </ul>

        <!-- 2. USER ACCOUNT DROPDOWN -->
        <div class="dropdown ms-lg-auto mt-2 mt-lg-0">
          <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-2 user-pill-btn" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?php
              $raw_avatar = $user['avatar'] ?? $_SESSION['avatar'] ?? $_SESSION['user']['avatar'] ?? '';

              // Xử lý chuẩn đường dẫn file để kiểm tra sự tồn tại trên Server
              $clean_avatar_path = ltrim($raw_avatar, '/');
              $server_file_path  = __DIR__ . '/../' . $clean_avatar_path;
              $has_avatar        = !empty($clean_avatar_path) && file_exists($server_file_path);
            ?>

            <?php if ($has_avatar): ?>
              <img src="<?= e($prefix . $clean_avatar_path) ?>?v=<?= time() ?>" alt="Avatar" class="rounded-circle avatar-img-header border border-2 border-white shadow-sm">
            <?php else: ?>
              <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold avatar-img-header shadow-sm" style="color: #7c3aed !important;">
                <?= strtoupper(mb_substr($user_name, 0, 1, 'UTF-8')) ?>
              </div>
            <?php endif; ?>

            <span class="fw-semibold small text-truncate user-name-text" style="max-width: 130px;"><?= e($user_name) ?></span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" aria-labelledby="userDropdown">
            <li>
              <div class="dropdown-header px-3 py-2 bg-light rounded-top-3">
                <div class="fw-bold text-dark text-truncate"><?= e($user_name) ?></div>
                <div class="text-muted small text-truncate"><?= e($user_email) ?></div>
              </div>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= e($prefix) ?>student/profile.php">
                <i class="bi bi-person-circle fs-6 text-primary"></i> Thông tin cá nhân
              </a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="<?= e($prefix) ?>logout.php">
                <i class="bi bi-box-arrow-right fs-6"></i> Đăng xuất
              </a>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </nav>

  <!-- Flash Messages Notification -->
  <?php
  if (function_exists('getFlash')) {
    $flash = getFlash();
    if ($flash && isset($flash['type']) && isset($flash['message'])): ?>
      <div class="container mt-3">
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <div><?= e($flash['message']) ?></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
  <?php endif;
  }
  ?>

  <main class="py-4">