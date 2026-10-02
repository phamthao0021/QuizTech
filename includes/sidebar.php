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
$user_info   = currentUser();
$role_raw    = user_role();
$role_clean  = strtolower(trim($role_raw));

// Chuẩn hóa Role label hiển thị
$role_display_map = [
    'admin'      => 'Super Admin',
    'admin_test' => 'Admin kiểm thử',
    'teacher'    => 'Giảng viên',
    'giang_vien' => 'Giảng viên',
    'student'    => 'Sinh viên',
    'sinh_vien'  => 'Sinh viên'
];
$role_label_text = $role_display_map[$role_clean] ?? 'Người dùng';

// 3. Xử lý Prefix đường dẫn & Cấu trúc thư mục
$in_admin   = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$in_teacher = strpos($_SERVER['PHP_SELF'], '/teacher/') !== false;
$in_student = strpos($_SERVER['PHP_SELF'], '/student/') !== false;
$in_sub     = $in_admin || $in_teacher || $in_student;

$prefix = $in_sub ? '../' : '';

$current_file   = basename($_SERVER['PHP_SELF']);
$current_folder = $in_admin ? 'admin' : ($in_teacher ? 'teacher' : ($in_student ? 'student' : ''));
$current_path   = $current_folder !== '' ? "$current_folder/$current_file" : $current_file;

// 4. Thông tin Avatar và Tên hiển thị
$user_name   = $user_info['name'] ?? $_SESSION['name'] ?? 'User';
$user_avatar = $user_info['avatar'] ?? $_SESSION['avatar'] ?? '';
$avatar_file_path = __DIR__ . '/../uploads/avatars/' . $user_avatar;
?>

<div class="sidebar-wrapper">
  <!-- BRAND / LOGO -->
  <div class="sidebar-brand d-flex align-items-center gap-2 p-3">
    <img src="<?= $prefix ?>assets/images/Cardmoi_PLT_Trang.png" alt="Logo" height="40" width="40" style="object-fit: contain;">
    <span class="fw-bold fs-4 text-white">QuizTech</span>
  </div>

  <!-- BLOCK HIỂN THỊ AVATAR NGƯỜI DÙNG -->
  <div class="sidebar-user d-flex align-items-center gap-3 p-2 mx-2 mb-3 rounded bg-dark bg-opacity-25">
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
      <div class="user-name text-truncate fw-bold text-white"><?= e($user_name) ?></div>
      <div class="user-role small text-white-50"><?= e($role_label_text) ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul class="list-unstyled">
      <!-- ============================================
           1. STUDENT MENU
           ============================================ -->
      <?php if ($role_clean === 'student' || $role_clean === 'sinh_vien'): ?>
        <li class="nav-label text-uppercase small text-muted px-3 mt-3 mb-1">Menu</li>
        <li>
          <a href="<?= $prefix ?>student/dashboard.php" class="<?= sidebar_active('student/dashboard.php', $current_path) ?>">
            <i class="bi bi-grid me-2"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/subjects.php" class="<?= sidebar_active('student/subjects.php', $current_path) ?>">
            <i class="bi bi-book me-2"></i>
            <span>Môn học</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/exams.php" class="<?= sidebar_active('student/exams.php', $current_path) ?>">
            <i class="bi bi-journal-text me-2"></i>
            <span>Đề thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/rooms.php" class="<?= sidebar_active('student/rooms.php', $current_path) ?>">
            <i class="bi bi-door-open me-2"></i>
            <span>Phòng thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/practice.php" class="<?= sidebar_active('student/practice.php', $current_path) ?>">
            <i class="bi bi-lightning-charge-fill me-2 text-warning"></i>
            <span>Luyện tập tự do</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/wrong_question.php" class="<?= sidebar_active('student/wrong_question.php', $current_path) ?>">
            <i class="bi bi-x-circle-fill me-2 text-danger"></i>
            <span>Sổ tay câu sai</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/leaderboard.php" class="<?= sidebar_active('student/leaderboard.php', $current_path) ?>">
            <i class="bi bi-trophy me-2"></i>
            <span>Bảng xếp hạng</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/profile.php" class="<?= sidebar_active('student/profile.php', $current_path) ?>">
            <i class="bi bi-person me-2"></i>
            <span>Hồ sơ</span>
          </a>
        </li>

      <!-- ============================================
           2. TEACHER MENU
           ============================================ -->
      <?php elseif (in_array($role_clean, ['teacher', 'giang_vien', 'giangvien'])): ?>
        <li class="nav-label text-uppercase small text-muted px-3 mt-3 mb-1">Menu</li>
        <li>
          <a href="<?= $prefix ?>teacher/dashboard.php" class="<?= sidebar_active('teacher/dashboard.php', $current_path) ?>">
            <i class="bi bi-speedometer2 me-2"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="nav-divider my-2 border-top opacity-25"></li>
        <li class="nav-label text-uppercase small text-muted px-3 mb-1">Quản lý</li>
        <li>
          <a href="<?= $prefix ?>teacher/subjects.php" class="<?= sidebar_active('teacher/subjects.php', $current_path) ?>">
            <i class="bi bi-book me-2"></i>
            <span>Môn học</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/questions.php" class="<?= sidebar_active('teacher/questions.php', $current_path) ?>">
            <i class="bi bi-question-circle me-2"></i>
            <span>Câu hỏi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/exams.php" class="<?= sidebar_active('teacher/exams.php', $current_path) ?>">
            <i class="bi bi-file-text me-2"></i>
            <span>Đề thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/rooms.php" class="<?= sidebar_active('teacher/rooms.php', $current_path) ?>">
            <i class="bi bi-door-open me-2"></i>
            <span>Phòng thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/practice.php" class="<?= sidebar_active('student/practice.php', $current_path) ?>">
            <i class="bi bi-lightning-charge-fill me-2 text-warning"></i>
            <span>Luyện tập tự do</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>student/wrong_question.php" class="<?= sidebar_active('student/wrong_question.php', $current_path) ?>">
            <i class="bi bi-x-circle-fill me-2 text-danger"></i>
            <span>Sổ tay câu sai</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>teacher/ai.php" class="<?= sidebar_active('teacher/ai.php', $current_path) ?>">
            <i class="bi bi-robot me-2"></i>
            <span>AI Generator</span>
          </a>
        </li>

        <li class="nav-divider my-2 border-top opacity-25"></li>
        <li>
          <a href="<?= $prefix ?>teacher/profile.php" class="<?= sidebar_active('teacher/profile.php', $current_path) ?>">
            <i class="bi bi-person me-2"></i>
            <span>Hồ sơ</span>
          </a>
        </li>

      <!-- ============================================
           3. ADMIN MENU (admin + admin_test)
           ============================================ -->
      <?php elseif (in_array($role_clean, ['admin', 'admin_test'], true)): ?>
        <li class="nav-label text-uppercase small text-muted px-3 mt-3 mb-1">Menu</li>
        <li>
          <a href="<?= $prefix ?>admin/dashboard.php" class="<?= sidebar_active('admin/dashboard.php', $current_path) ?>">
            <i class="bi bi-speedometer2 me-2"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/users.php" class="<?= sidebar_active('admin/users.php', $current_path) ?>">
            <i class="bi bi-people me-2"></i>
            <span>Người dùng</span>
          </a>
        </li>

        <li class="nav-divider my-2 border-top opacity-25"></li>
        <li class="nav-label text-uppercase small text-muted px-3 mb-1">Quản lý</li>
        <li>
          <a href="<?= $prefix ?>admin/subjects.php" class="<?= sidebar_active('admin/subjects.php', $current_path) ?>">
            <i class="bi bi-book me-2"></i>
            <span>Môn học</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/questions.php" class="<?= sidebar_active('admin/questions.php', $current_path) ?>">
            <i class="bi bi-question-circle me-2"></i>
            <span>Câu hỏi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/exams.php" class="<?= sidebar_active('admin/exams.php', $current_path) ?>">
            <i class="bi bi-file-text me-2"></i>
            <span>Đề thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/rooms.php" class="<?= sidebar_active('admin/rooms.php', $current_path) ?>">
            <i class="bi bi-door-open me-2"></i>
            <span>Phòng thi</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/leaderboard.php" class="<?= sidebar_active('admin/leaderboard.php', $current_path) ?>">
            <i class="bi bi-trophy me-2"></i>
            <span>Bảng xếp hạng</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/ai.php" class="<?= sidebar_active('admin/ai.php', $current_path) ?>">
            <i class="bi bi-robot me-2"></i>
            <span>AI Generator</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/ai_logs.php" class="<?= sidebar_active('admin/ai_logs.php', $current_path) ?>">
            <i class="bi bi-journal-text me-2"></i>
            <span>AI Logs</span>
          </a>
        </li>

        <?php if ($role_clean === 'admin'): ?>
        <li class="nav-divider my-2 border-top opacity-25"></li>
        <li class="nav-label text-uppercase small text-muted px-3 mb-1">Hệ thống</li>
        <li>
          <a href="<?= $prefix ?>admin/reset_all.php" class="<?= sidebar_active('admin/reset_all.php', $current_path) ?>">
            <i class="bi bi-arrow-repeat me-2"></i>
            <span>Reset ID</span>
          </a>
        </li>
        <li>
          <a href="<?= $prefix ?>admin/settings.php" class="<?= sidebar_active('admin/settings.php', $current_path) ?>">
            <i class="bi bi-gear me-2"></i>
            <span>Cài đặt</span>
          </a>
        </li>
        <?php endif; ?>

        <li class="nav-divider my-2 border-top opacity-25"></li>
        <li>
          <a href="<?= $prefix ?>admin/profile.php" class="<?= sidebar_active('admin/profile.php', $current_path) ?>">
            <i class="bi bi-person-circle me-2"></i>
            <span>Hồ sơ cá nhân</span>
          </a>
        </li>
      <?php endif; ?>

      <!-- ============================================
           4. ĐĂNG XUẤT (HIỂN THỊ CHUNG)
           ============================================ -->
      <li class="nav-divider my-2 border-top opacity-25"></li>
      <li>
        <a href="<?= $prefix ?>logout.php" class="text-danger fw-bold">
          <i class="bi bi-box-arrow-right me-2"></i>
          <span>Đăng xuất</span>
        </a>
      </li>
    </ul>
  </nav>
</div>