<?php // admin/dashboard.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';

requireAdmin();

$page_title = 'Admin Dashboard';

// Lấy dữ liệu an toàn, xử lý chống warning nếu hàm trả về null/false
$stats_raw = function_exists('getStats') ? getStats() : [];
$stats     = is_array($stats_raw) ? $stats_raw : [];

$users_raw = function_exists('getUsers') ? getUsers() : [];
$users     = is_array($users_raw) ? $users_raw : [];

$exams_raw = function_exists('getExams') ? getExams() : [];
$exams     = is_array($exams_raw) ? $exams_raw : [];

include '../includes/header_admin.php';
?>

<style>
  /* Base Dashboard Styles */
  .dashboard-wrapper {
    animation: fadeIn 0.4s ease-in-out;
  }
  
  /* Modern Stat Cards */
  .stat-card {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 1.25rem !important;
    border: 1px solid rgba(255, 255, 255, 0.8) !important;
    background: linear-gradient(145deg, #ffffff, #f8fafc);
  }
  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.03) !important;
  }
  .stat-icon {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    flex-shrink: 0;
  }

  /* Creative Quick Action Buttons with Glowing Shadow */
  .quick-action-card {
    position: relative;
    border-radius: 1.25rem;
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none !important;
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    overflow: hidden;
    z-index: 1;
  }

  /* Color-specific glow effects */
  .action-blue { --glow-color: rgba(59, 130, 246, 0.35); }
  .action-green { --glow-color: rgba(34, 197, 94, 0.35); }
  .action-purple { --glow-color: rgba(147, 51, 234, 0.35); }
  .action-red { --glow-color: rgba(239, 68, 68, 0.35); }

  .quick-action-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 1.25rem;
    background: radial-gradient(circle at center, var(--glow-color) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.35s ease;
    z-index: -1;
  }

  .quick-action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -5px var(--glow-color);
    border-color: transparent;
  }

  .quick-action-card:hover::before {
    opacity: 0.15;
  }

  .quick-action-icon {
    width: 42px;
    height: 42px;
    border-radius: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 0.85rem;
    flex-shrink: 0;
  }

  /* Creative Modern Table Design */
  .creative-table-card {
    border-radius: 1.25rem !important;
    overflow: hidden;
  }

  .creative-table {
    border-collapse: separate;
    border-spacing: 0 0.5rem;
  }

  .creative-table thead th {
    border: none;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #64748b;
    padding: 0.75rem 1rem;
    background: transparent;
  }

  .creative-table tbody tr {
    background: #ffffff;
    transition: all 0.2s ease;
    border-radius: 0.85rem;
  }

  .creative-table tbody tr td {
    border: none;
    padding: 0.85rem 1rem;
    background: #f8fafc;
  }

  .creative-table tbody tr td:first-child {
    border-top-left-radius: 0.85rem;
    border-bottom-left-radius: 0.85rem;
  }

  .creative-table tbody tr td:last-child {
    border-top-right-radius: 0.85rem;
    border-bottom-right-radius: 0.85rem;
  }

  .creative-table tbody tr:hover td {
    background: #f1f5f9;
  }

  /* Responsive Adjustments */
  @media (max-width: 575.98px) {
    .banner-card {
      padding: 1.25rem !important;
    }
    .stat-card {
      padding: 0.25rem;
    }
    .quick-action-card {
      padding: 0.85rem 1rem;
    }
    .quick-action-icon {
      width: 36px;
      height: 36px;
      font-size: 1.1rem;
      margin-right: 0.65rem;
    }
  }
</style>

<div class="container-fluid px-0 dashboard-wrapper">
  
  <!-- Banner Chào Mừng / Header -->
  <div class="card border-0 text-white mb-4 shadow-sm banner-card admin-page-head" style="background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%); border-radius: 1.25rem;">
    <div class="card-body p-3 p-md-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <h3 class="fw-bold mb-1 fs-4 fs-md-3">Xin chào, <?= e(isset($user_name) ? $user_name : 'Quản trị viên') ?> ^^</h3>
        <p class="text-white-50 mb-0 small">Tổng quan chỉ số và hoạt động trọng yếu của hệ thống QuizTech hôm nay.</p>
      </div>
      <div class="d-flex align-self-start align-self-md-center gap-2">
        <a href="approve_items.php" class="btn btn-light text-primary fw-semibold px-3 py-2 rounded-3 shadow-sm text-nowrap">
          <i class="bi bi-check2-circle me-1"></i> Duyệt Đề Xuất
        </a>
      </div>
    </div>
  </div>

  <!-- Thống Kê Tổng Quan -->
  <div class="row g-2 g-md-3 mb-4">
    <!-- Người dùng -->
    <div class="col-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100 stat-card">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Người dùng</span>
            <h3 class="fw-bold mb-0 mt-1 text-dark fs-4 fs-md-3"><?= number_format(isset($stats['users']) ? $stats['users'] : count($users)) ?></h3>
          </div>
          <div class="stat-icon bg-primary bg-opacity-10 text-primary fs-4 d-none d-sm-flex">
            <i class="bi bi-people-fill"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Môn học -->
    <div class="col-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100 stat-card">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Môn học</span>
            <h3 class="fw-bold mb-0 mt-1 text-dark fs-4 fs-md-3"><?= number_format(isset($stats['subjects']) ? $stats['subjects'] : 0) ?></h3>
          </div>
          <div class="stat-icon bg-success bg-opacity-10 text-success fs-4 d-none d-sm-flex">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Ngân hàng câu hỏi -->
    <div class="col-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100 stat-card">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Ngân hàng câu hỏi</span>
            <h3 class="fw-bold mb-0 mt-1 text-dark fs-4 fs-md-3"><?= number_format(isset($stats['questions']) ? $stats['questions'] : 0) ?></h3>
          </div>
          <div class="stat-icon bg-warning bg-opacity-10 text-warning fs-4 d-none d-sm-flex">
            <i class="bi bi-patch-question-fill"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Đề thi -->
    <div class="col-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100 stat-card">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Đề thi hiện có</span>
            <h3 class="fw-bold mb-0 mt-1 text-dark fs-4 fs-md-3"><?= number_format(isset($stats['exams']) ? $stats['exams'] : count($exams)) ?></h3>
          </div>
          <div class="stat-icon bg-purple bg-opacity-10 fs-4 d-none d-sm-flex" style="color: #6d28d9; background-color: rgba(109, 40, 217, 0.1);">
            <i class="bi bi-file-earmark-text-fill"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Thao Tác Nhanh (Quick Actions - Creative Glow Effects) -->
  <div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="fw-bold mb-0 text-secondary text-uppercase small tracking-wider">
        <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Thao tác nhanh
      </h6>
    </div>
    
    <div class="row g-2 g-md-3">
      <!-- Thêm người dùng -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="users.php?action=add" class="quick-action-card action-blue shadow-sm">
          <div class="quick-action-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-plus-fill"></i>
          </div>
          <div class="overflow-hidden">
            <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;">Thêm người dùng</div>
            <div class="text-muted small text-truncate" style="font-size: 0.75rem;">Cấp tài khoản mới</div>
          </div>
        </a>
      </div>

      <!-- Tạo môn học -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="subjects.php?action=add" class="quick-action-card action-green shadow-sm">
          <div class="quick-action-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-journal-plus"></i>
          </div>
          <div class="overflow-hidden">
            <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;">Tạo môn học</div>
            <div class="text-muted small text-truncate" style="font-size: 0.75rem;">Thêm danh mục mới</div>
          </div>
        </a>
      </div>

      <!-- Soạn đề thi -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="exams.php?action=add" class="quick-action-card action-purple shadow-sm">
          <div class="quick-action-icon bg-opacity-10" style="background-color: rgba(109, 40, 217, 0.1); color: #6d28d9;">
            <i class="bi bi-file-earmark-plus-fill"></i>
          </div>
          <div class="overflow-hidden">
            <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;">Soạn đề thi mới</div>
            <div class="text-muted small text-truncate" style="font-size: 0.75rem;">Tạo bộ đề kiểm tra</div>
          </div>
        </a>
      </div>

      <!-- Mở phòng thi -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="rooms.php?action=add" class="quick-action-card action-red shadow-sm">
          <div class="quick-action-icon bg-danger bg-opacity-10 text-danger">
            <i class="bi bi-door-open-fill"></i>
          </div>
          <div class="overflow-hidden">
            <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;">Mở phòng thi</div>
            <div class="text-muted small text-truncate" style="font-size: 0.75rem;">Kích hoạt ca thi</div>
          </div>
        </a>
      </div>
    </div>
  </div>

  <!-- Bảng Dữ Liệu Thiết Kế Mới -->
  <div class="row g-4">
    
    <!-- Người Dùng Mới -->
    <div class="col-xl-6">
      <div class="card border-0 shadow-sm h-100 creative-table-card" style="background: #ffffff;">
        <div class="card-header bg-transparent py-3 px-3 px-md-4 d-flex justify-content-between align-items-center border-0">
          <h6 class="mb-0 fw-bold d-flex align-items-center gap-2 text-dark">
            <i class="bi bi-people text-primary"></i> Thành viên mới
          </h6>
          <a href="users.php" class="btn btn-sm btn-light text-primary fw-semibold rounded-pill px-3">Xem tất cả</a>
        </div>
        <div class="card-body p-3 pt-0">
          <?php if (empty($users)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-black-50"></i>
              Chưa có dữ liệu người dùng
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table creative-table align-middle mb-0">
                <thead>
                  <tr>
                    <th class="ps-3">THÀNH VIÊN</th>
                    <th>EMAIL</th>
                    <th class="text-end pe-3">VAI TRÒ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($users, 0, 5) as $u): ?>
                    <?php 
                      $role_code = isset($u['role']) ? $u['role'] : 'student';
                      switch ($role_code) {
                        case 'admin':
                          $role_bg = 'bg-danger text-danger';
                          break;
                        case 'teacher':
                          $role_bg = 'bg-warning text-warning';
                          break;
                        default:
                          $role_bg = 'bg-primary text-primary';
                          break;
                      }
                      $user_disp_name = isset($u['name']) ? $u['name'] : (isset($u['fullname']) ? $u['fullname'] : 'N/A');
                    ?>
                    <tr>
                      <td class="ps-3">
                        <div class="d-flex align-items-center gap-2.5">
                          <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width:36px; height:36px; font-size: 0.85rem; flex-shrink: 0;">
                            <?= strtoupper(mb_substr($user_disp_name, 0, 1, 'UTF-8')) ?>
                          </div>
                          <span class="fw-semibold text-dark text-truncate" style="max-width: 130px;"><?= e($user_disp_name) ?></span>
                        </div>
                      </td>
                      <td class="text-muted small">
                        <div class="text-truncate" style="max-width: 140px;"><?= e(isset($u['email']) ? $u['email'] : 'N/A') ?></div>
                      </td>
                      <td class="text-end pe-3">
                        <span class="badge <?= $role_bg ?> bg-opacity-10 border border-current rounded-pill px-2.5 py-1" style="font-size: 0.725rem;">
                          <?= function_exists('role_label') ? role_label($role_code) : strtoupper($role_code) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Đề Thi Vừa Tạo -->
    <div class="col-xl-6">
      <div class="card border-0 shadow-sm h-100 creative-table-card" style="background: #ffffff;">
        <div class="card-header bg-transparent py-3 px-3 px-md-4 d-flex justify-content-between align-items-center border-0">
          <h6 class="mb-0 fw-bold d-flex align-items-center gap-2 text-dark">
            <i class="bi bi-journal-text" style="color: #6d28d9;"></i> Đề thi mới tạo
          </h6>
          <a href="exams.php" class="btn btn-sm btn-light text-primary fw-semibold rounded-pill px-3">Xem tất cả</a>
        </div>
        <div class="card-body p-3 pt-0">
          <?php if (empty($exams)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-journal-x fs-1 d-block mb-2 text-black-50"></i>
              Chưa có dữ liệu đề thi
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table creative-table align-middle mb-0">
                <thead>
                  <tr>
                    <th class="ps-3">TÊN ĐỀ THI</th>
                    <th>MÔN HỌC</th>
                    <th class="text-end pe-3">NGÀY TẠO</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($exams, 0, 5) as $e): ?>
                    <?php 
                      $exam_title = isset($e['title']) ? $e['title'] : (isset($e['name']) ? $e['name'] : 'Đề thi không tên');
                      $subj_title = isset($e['subject_name']) ? $e['subject_name'] : (isset($e['subject']) ? $e['subject'] : 'Chưa phân môn');
                      $created_date = isset($e['created_at']) ? $e['created_at'] : 'now';
                    ?>
                    <tr>
                      <td class="ps-3 fw-semibold text-dark">
                        <div class="text-truncate" style="max-width: 160px;" title="<?= e($exam_title) ?>">
                          <?= e($exam_title) ?>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-white text-secondary border rounded-2 px-2 py-1 font-monospace shadow-2xs" style="font-size: 0.725rem;">
                          <?= e($subj_title) ?>
                        </span>
                      </td>
                      <td class="text-end pe-3 text-muted small">
                        <?php 
                          echo function_exists('format_date') ? format_date($created_date) : date('d/m/Y', strtotime($created_date));
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

</div>

</main> <!-- Đóng thẻ main từ header_admin.php -->
<?php include '../includes/footer.php'; ?>