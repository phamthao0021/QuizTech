<?php
// student/dashboard.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = currentUser();
$user_id = (int)($user['id'] ?? $_SESSION['user_id'] ?? 0);

// 1. LẤY DANH SÁCH ĐỀ THI KHẢ DỤNG
$stmtExams = $pdo->prepare("
    SELECT e.*, s.name AS subject_name 
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id 
    ORDER BY e.id DESC
");
$stmtExams->execute();
$available_exams = $stmtExams->fetchAll(PDO::FETCH_ASSOC);

// 2. LẤY LỊCH SỬ LÀM BÀI THI
$stmtHistory = $pdo->prepare("
    SELECT r.*, e.title AS exam_title, s.name AS subject_name 
    FROM results r
    LEFT JOIN exams e ON r.exam_id = e.id
    LEFT JOIN subjects s ON e.subject_id = s.id
    WHERE r.user_id = :user_id
    ORDER BY r.created_at DESC
");
$stmtHistory->execute(['user_id' => $user_id]);
$exam_history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Trang chủ Sinh viên";

// INCLUDES HEADER & SIDEBAR CHUẨN DỰ ÁN
$headerFile = dirname(__DIR__) . '/includes/header_user.php';
if (!file_exists($headerFile)) $headerFile = dirname(__DIR__) . '/includes/header.php';
if (file_exists($headerFile)) include $headerFile;

$sidebarFile = dirname(__DIR__) . '/includes/sidebar.php';
if (file_exists($sidebarFile)) include $sidebarFile;
?>

<!-- CSS FIX TRIỆT ĐỂ LỖI ĐỀ LAYOUT & DƯ TOP PHÍA TRÊN -->
<style>
  /* Định hình khung chứa sidebar và main song song */
  body {
    display: flex !important;
    margin: 0 !important;
    padding: 0 !important;
    background-color: #f8fafc !important;
  }

  /* Đảm bảo Sidebar wrapper của hệ thống nằm cố định đúng chuẩn bên trái */
  .sidebar-wrapper {
    width: 240px !important;
    min-width: 240px !important;
    height: 100vh !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 1000 !important;
  }

  /* Ép phần nội dung chính lên tận sát mép đỉnh trang (top: 0) và nằm bên phải Sidebar */
  .main-content-layout {
    margin-left: 240px !important;
    width: calc(100% - 240px) !important;
    min-height: 100vh !important;
    padding: 24px 30px !important;
    box-sizing: border-box !important;
    margin-top: 0 !important;
    padding-top: 24px !important;
  }

  /* Ẩn các khối header rỗng thừa (nếu có từ file header include) */
  header:empty, .navbar:empty, .top-header-empty {
    display: none !important;
  }

  .card-custom {
    border-radius: 16px;
    border: 1px solid #e2e8f0;
  }

  @media (max-width: 768px) {
    body { display: block !important; }
    .sidebar-wrapper { position: relative !important; width: 100% !important; height: auto !important; }
    .main-content-layout { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
  }
</style>

<!-- NỘI DUNG CHÍNH CHÈN VÀO BÊN PHẢI SIDEBAR -->
<div class="main-content-layout">
  <div class="container-fluid p-0">
    
    <!-- PHẦN 1: BÀI THI KHẢ DỤNG -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-check text-primary me-2"></i>Bài thi khả dụng</h5>
    </div>

    <div class="row g-3 mb-5">
      <?php if (empty($available_exams)): ?>
        <div class="col-12"><div class="alert alert-info border-0 shadow-sm">Hiện chưa có bài thi nào trên hệ thống.</div></div>
      <?php else: ?>
        <?php foreach ($available_exams as $ex): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card card-custom h-100 shadow-sm p-3">
              <span class="badge bg-primary-subtle text-primary fw-bold align-self-start mb-2 px-3 py-2 rounded-pill">
                <?= htmlspecialchars($ex['subject_name'] ?? 'Môn thi') ?>
              </span>
              <h6 class="fw-bold text-dark mb-2"><?= htmlspecialchars($ex['title']) ?></h6>
              <p class="text-muted small mb-3"><i class="bi bi-clock me-1"></i> Thời gian: <?= $ex['duration'] ?? 20 ?> phút</p>
              <a href="exam.php?id=<?= $ex['id'] ?>" class="btn btn-primary fw-bold w-100 mt-auto rounded-3" style="background-color: #635bff; border: none;">
                <i class="bi bi-pencil-square me-1"></i> Bắt đầu thi
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- PHẦN 2: LỊCH SỬ LÀM BÀI THI -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Lịch sử làm bài thi</h5>
    </div>

    <div class="card card-custom shadow-sm overflow-hidden mb-5">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Tên bài thi</th>
              <th>Môn học</th>
              <th>Thời gian nộp</th>
              <th>Thời gian làm</th>
              <th>Số câu đúng</th>
              <th>Điểm số</th>
              <th class="text-end pe-4">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($exam_history)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Bạn chưa hoàn thành bài thi nào.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($exam_history as $his): ?>
                <tr>
                  <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($his['exam_title'] ?? 'Bài thi') ?></td>
                  <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($his['subject_name'] ?? 'N/A') ?></span></td>
                  <td class="text-muted small"><?= date('H:i - d/m/Y', strtotime($his['created_at'])) ?></td>
                  <td class="text-muted small"><?= floor($his['time_taken'] / 60) ?>m <?= $his['time_taken'] % 60 ?>s</td>
                  <td>
                    <span class="text-success fw-bold"><?= $his['correct_answers'] ?></span> / <?= $his['total_questions'] ?>
                  </td>
                  <td>
                    <span class="badge <?= $his['score'] >= 5 ? 'bg-success' : 'bg-danger' ?> fs-6">
                      <?= number_format($his['score'], 1) ?> đ
                    </span>
                  </td>
                  <td class="text-end pe-4">
                    <a href="result.php?id=<?= $his['id'] ?>" class="btn btn-sm btn-outline-primary rounded-2">
                      <i class="bi bi-eye-fill me-1"></i> Xem kết quả
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php 
$footerFile = dirname(__DIR__) . '/includes/footer.php';
if (file_exists($footerFile)) include $footerFile;
?>