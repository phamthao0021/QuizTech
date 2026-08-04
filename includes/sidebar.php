<?php
// includes/sidebar.php

// 1. Kiểm tra tồn tại các hàm Helper để tránh lỗi Fatal Error khi re-include
if (!function_exists('sidebar_active')) {
  function sidebar_active($logical_path, $current_path)
  {
    return $logical_path === $current_path ? 'active' : '';
  }
}

// 2. Lấy dữ liệu Người dùng & Vai trò
$user = currentUser();
$role = user_role();

// Chuẩn hóa role về chữ thường
$role_clean = strtolower(trim($role));

// 3. Xử lý Prefix đường dẫn & Cấu trúc thư mục
$in_admin   = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$in_teacher = strpos($_SERVER['PHP_SELF'], '/teacher/') !== false;
$in_student = strpos($_SERVER['PHP_SELF'], '/student/') !== false;
$in_sub     = $in_admin || $in_teacher || $in_student;

$prefix = $in_sub ? '../' : '';

// Lấy tên file hiện tại (Ví dụ: dashboard.php)
$current_file   = basename($_SERVER['PHP_SELF']);
$current_folder = $in_admin ? 'admin' : ($in_teacher ? 'teacher' : ($in_student ? 'student' : ''));
$current_path   = $current_folder !== '' ? "$current_folder/$current_file" : $current_file;

// 4. Lấy thông tin Avatar và Tên hiển thị
$user_name   = $user['name'] ?? $_SESSION['name'] ?? 'User';
$user_avatar = $user['avatar'] ?? $_SESSION['avatar'] ?? '';
$avatar_file_path = __DIR__ . '/../uploads/avatars/' . $user_avatar;

$user_info   = currentUser();
$role_raw    = user_role();

// 2. Chuẩn hóa Role label cho 3 vai trò (Admin, Giảng viên, Sinh viên)
$role_clean  = strtolower(trim($role_raw));
$role_display_map = [
  'admin'      => 'Quản trị viên',
  'teacher'    => 'Giảng viên',
  'giang_vien' => 'Giảng viên',
  'student'    => 'Sinh viên',
  'sinh_vien'  => 'Sinh viên'
];
$role_label_text = $role_display_map[$role_clean] ?? 'Người dùng';

// 3. Tên người dùng & Đường dẫn Avatar
$user_name   = $user_info['name'] ?? $_SESSION['name'] ?? 'User';
$user_avatar = $user_info['avatar'] ?? $_SESSION['avatar'] ?? '';

// Đường dẫn tuyệt đối kiểm tra file ảnh tồn tại trên máy chủ
$avatar_file_path = __DIR__ . '/../uploads/avatars/' . $user_avatar;
?>

<div class="sidebar-wrapper">
  <!-- BRAND / LOGO -->
  <div class="sidebar-brand">
    <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png" alt="Logo" height="60px" width="65px">
    <span>QuizTech</span>
  </div>

  <!-- BLOCK HIỂN THỊ AVATAR NGƯỜI DÙNG -->
  <div class="sidebar-user d-flex align-items-center gap-3 p-2 mb-3 rounded">
    <div class="user-avatar position-relative" style="width: 45px; height: 45px; flex-shrink: 0;">
      <?php if (!empty($user_avatar) && file_exists($avatar_file_path)): ?>
        <img src="<?= $prefix ?>uploads/avatars/<?= e($user_avatar) ?>?v=<?= time() ?>"
          alt="Avatar"
          class="rounded-circle shadow-sm w-100 h-100"
          style="object-fit: cover;">
      <?php else: ?>
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm w-100 h-100"
          style="font-size: 18px;">
          <?= strtoupper(substr($user_name, 0, 1)) ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="user-info overflow-hidden">
      <div class="user-name text-truncate fw-bold"><?= e($user_name) ?></div>
      <div class="user-role small text-light"><?= role_label($role) ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul>
      <!-- ============================================
           1. STUDENT MENU
           ============================================ -->
      <?php if ($role_clean === 'student' || $role_clean === 'sinh_vien'): ?>
        <li class="nav-label">Menu</li>
        <li>
          <a href="<?= $prefix ?>student/dashboard.php" class="<?= sidebar_active('student/dashboard.php', $current_path) ?>">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/subjects.php" class="<?= sidebar_active('student/subjects.php', $current_path) ?>">
            <i class="bi bi-book"></i>
            <span>Môn học</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/exams.php" class="<?= sidebar_active('student/exams.php', $current_path) ?>">
            <i class="bi bi-journal-text"></i>
            <span>Đề thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/rooms.php" class="<?= sidebar_active('student/rooms.php', $current_path) ?>">
            <i class="bi bi-door-open"></i>
            <span>Phòng thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/leaderboard.php" class="<?= sidebar_active('student/leaderboard.php', $current_path) ?>">
            <i class="bi bi-trophy"></i>
            <span>BXH</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/profile.php" class="<?= sidebar_active('student/profile.php', $current_path) ?>">
            <i class="bi bi-person"></i>
            <span>Hồ sơ</span>
          </a>
        </li>

        <!-- ============================================
           2. TEACHER MENU (Dành riêng cho Teacher / Giảng viên)
           ============================================ -->
      <?php elseif (in_array($role_clean, ['teacher', 'giang_vien', 'giangvien'])): ?>
        <li class="nav-label">Menu</li>
        <li>
          <a href="<?= $prefix ?>teacher/dashboard.php" class="<?= sidebar_active('teacher/dashboard.php', $current_path) ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="nav-divider"></li>
        <li class="nav-label">Quản lý</li>
        <li>
          <a href="<?= $prefix ?>teacher/subjects.php" class="<?= sidebar_active('teacher/subjects.php', $current_path) ?>">
            <i class="bi bi-book"></i>
            <span>Môn học</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/questions.php" class="<?= sidebar_active('teacher/questions.php', $current_path) ?>">
            <i class="bi bi-question-circle"></i>
            <span>Câu hỏi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/exams.php" class="<?= sidebar_active('teacher/exams.php', $current_path) ?>">
            <i class="bi bi-file-text"></i>
            <span>Đề thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/rooms.php" class="<?= sidebar_active('teacher/rooms.php', $current_path) ?>">
            <i class="bi bi-door-open"></i>
            <span>Phòng thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/ai.php" class="<?= sidebar_active('teacher/ai.php', $current_path) ?>">
            <i class="bi bi-robot"></i>
            <span>AI Generator</span>
          </a>
        </li>

        <li class="nav-divider"></li>
        <li>
          <a href="<?= $prefix ?>teacher/profile.php" class="<?= sidebar_active('teacher/profile.php', $current_path) ?>">
            <i class="bi bi-person"></i>
            <span>Hồ sơ</span>
          </a>
        </li>

        <!-- ============================================
           3. ADMIN MENU
           ============================================ -->
      <?php elseif ($role_clean === 'admin'): ?>
        <li class="nav-label">Menu</li>
        <li>
          <a href="<?= $prefix ?>admin/dashboard.php" class="<?= sidebar_active('admin/dashboard.php', $current_path) ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/users.php" class="<?= sidebar_active('admin/users.php', $current_path) ?>">
            <i class="bi bi-people"></i>
            <span>Người dùng</span>
          </a>
        </li>

        <li class="nav-divider"></li>
        <li class="nav-label">Quản lý</li>
        <li>
          <a href="<?= $prefix ?>admin/subjects.php" class="<?= sidebar_active('admin/subjects.php', $current_path) ?>">
            <i class="bi bi-book"></i>
            <span>Môn học</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/questions.php" class="<?= sidebar_active('admin/questions.php', $current_path) ?>">
            <i class="bi bi-question-circle"></i>
            <span>Câu hỏi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/exams.php" class="<?= sidebar_active('admin/exams.php', $current_path) ?>">
            <i class="bi bi-file-text"></i>
            <span>Đề thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/rooms.php" class="<?= sidebar_active('admin/rooms.php', $current_path) ?>">
            <i class="bi bi-door-open"></i>
            <span>Phòng thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/leaderboard.php" class="<?= sidebar_active('admin/leaderboard.php', $current_path) ?>">
            <i class="bi bi-trophy"></i>
            <span>Bảng xếp hạng</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/ai.php" class="<?= sidebar_active('admin/ai.php', $current_path) ?>">
            <i class="bi bi-robot"></i>
            <span>AI Generator</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/ai_logs.php" class="<?= sidebar_active('admin/ai_logs.php', $current_path) ?>">
            <i class="bi bi-journal-text"></i>
            <span>AI Logs</span>
          </a>
        </li>

        <li class="nav-divider"></li>
        <li class="nav-label">Hệ thống</li>
        <li>
          <a href="<?= $prefix ?>admin/reset_all.php" class="<?= sidebar_active('admin/reset_all.php', $current_path) ?>">
            <i class="bi bi-arrow-repeat"></i>
            <span>Reset ID</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/settings.php" class="<?= sidebar_active('admin/settings.php', $current_path) ?>">
            <i class="bi bi-gear"></i>
            <span>Cài đặt</span>
          </a>
        </li>

        <li class="nav-divider"></li>
        <li>
          <a href="<?= $prefix ?>admin/profile.php" class="<?= sidebar_active('admin/profile.php', $current_path) ?>">
            <i class="bi bi-person-circle"></i>
            <span>Hồ sơ cá nhân</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- ============================================
           4. DĂNG XUẤT (HIỂN THỊ CHUNG)
           ============================================ -->
      <li class="nav-divider"></li>
      <li>
        <a href="<?= $prefix ?>logout.php" class="text-danger">
          <i class="bi bi-box-arrow-right"></i>
          <span>Đăng xuất</span>
        </a>
      </li>
    </ul>
  </nav>
</div>