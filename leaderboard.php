<?php
// leaderboard.php - Bảng xếp hạng
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';

$page_title  = 'Bảng xếp hạng';
$leaderboard = getLeaderboard() ?? [];
$is_logged_in = isLoggedIn();

$total_students = count($leaderboard);
$best_score_all = 0.0;
$avg_score_all  = 0.0;

if ($total_students > 0) {
    $best_scores   = array_column($leaderboard, 'best_score');
    $avg_scores    = array_column($leaderboard, 'avg_score');
    $best_score_all = !empty($best_scores) ? (float)max($best_scores) : 0.0;
    $avg_score_all  = !empty($avg_scores)  ? array_sum($avg_scores) / $total_students : 0.0;
}

include 'includes/header_guest.php';
?>

<style>
  .top-card {
    border: none;
    border-radius: 20px;
    background: #fff;
    transition: transform .3s ease;
    box-shadow: 0 10px 30px rgba(0,0,0,.05);
  }
  .top-card:hover { transform: translateY(-8px); }
  .rank-1 { border-top: 5px solid #f59e0b; }
  .rank-2 { border-top: 5px solid #94a3b8; }
  .rank-3 { border-top: 5px solid #b45309; }

  .stat-card {
    border: none;
    border-radius: 16px;
    background: #f8fafc;
  }
</style>

<div class="container py-4">

  <!-- Header -->
  <div class="text-center mb-5">
    <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill fw-bold mb-2">
      <i class="bi bi-trophy-fill me-1"></i> Vinh danh
    </span>
    <h1 class="display-5 fw-bold text-dark mb-2">Bảng Xếp Hạng</h1>
    <p class="text-muted">Cập nhật theo kết quả thi thực tế của các thí sinh trong hệ thống.</p>
  </div>

  <?php if (empty($leaderboard)): ?>

    <div class="card border-0 shadow-sm rounded-4 text-center py-5">
      <div class="card-body">
        <i class="bi bi-trophy display-1 text-muted"></i>
        <h4 class="fw-bold mt-3">Chưa có dữ liệu xếp hạng</h4>
        <p class="text-muted mb-4">Hãy làm bài thi đầu tiên để ghi danh vào bảng vàng!</p>
        <a href="exams.php" class="btn btn-primary px-4">
          <i class="bi bi-play-fill me-1"></i> Làm bài thi ngay
        </a>
      </div>
    </div>

  <?php else: ?>

    <!-- Thống kê tổng quan -->
    <div class="row g-3 mb-5">
      <div class="col-md-4">
        <div class="stat-card p-4 text-center">
          <h2 class="fw-bold text-primary mb-1"><?= $total_students ?></h2>
          <span class="text-muted">Tổng thí sinh</span>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card p-4 text-center">
          <h2 class="fw-bold text-success mb-1"><?= number_format($best_score_all, 1) ?></h2>
          <span class="text-muted">Điểm kỷ lục</span>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card p-4 text-center">
          <h2 class="fw-bold text-warning mb-1"><?= number_format($avg_score_all, 1) ?></h2>
          <span class="text-muted">Điểm trung bình hệ thống</span>
        </div>
      </div>
    </div>

    <!-- TOP 3 -->
    <?php $medals = ['🥇','🥈','🥉']; ?>
    <div class="row g-4 mb-5 justify-content-center">
      <?php foreach (array_slice($leaderboard, 0, 3) as $i => $u): ?>
      <div class="col-md-4">
        <div class="card top-card rank-<?= $i + 1 ?> h-100 p-3 text-center">
          <div class="card-body">
            <div class="mb-3" style="font-size:54px"><?= $medals[$i] ?></div>
            <h4 class="fw-bold text-dark mb-1"><?= e($u['name'] ?? 'Thí sinh') ?></h4>

            <?php if (!empty($u['student_code'])): ?>
              <span class="badge bg-light text-dark border mb-3"><?= e($u['student_code']) ?></span>
            <?php else: ?>
              <div class="mb-3"></div>
            <?php endif; ?>

            <div class="p-3 bg-light rounded-4 mb-3">
              <small class="text-muted d-block mb-1">Điểm Trung Bình</small>
              <div class="display-6 fw-bold text-primary">
                <?= number_format($u['avg_score'] ?? 0, 1) ?>
              </div>
            </div>

            <div class="row g-2 text-center small text-muted">
              <div class="col-6">
                <div class="p-2 border rounded-3">
                  <strong><?= (int)($u['exam_count'] ?? 0) ?></strong> Bài thi
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 border rounded-3">
                  <strong class="text-success"><?= number_format($u['best_score'] ?? 0, 1) ?></strong> Cao nhất
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Bảng đầy đủ -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="fw-bold mb-0">Danh sách xếp hạng đầy đủ</h5>
        <span class="badge bg-primary-subtle text-primary rounded-pill">
          <?= $total_students ?> Thí sinh
        </span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4" width="90">Hạng</th>
              <th>Thí sinh</th>
              <th class="text-center">Số bài thi</th>
              <th class="text-center">Điểm TB</th>
              <th class="text-center pe-4">Điểm Cao Nhất</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($leaderboard as $i => $u): ?>
            <tr>
              <td class="ps-4 fw-bold">
                <?php if ($i < 3): ?>
                  <span class="fs-4"><?= $medals[$i] ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary rounded-circle px-2 py-1"><?= $i + 1 ?></span>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($u['name'] ?? 'Thí sinh') ?></div>
                <?php if (!empty($u['student_code'])): ?>
                  <small class="text-muted">MSSV: <?= e($u['student_code']) ?></small>
                <?php endif; ?>
              </td>
              <td class="text-center"><?= (int)($u['exam_count'] ?? 0) ?></td>
              <td class="text-center">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
                  <?= number_format($u['avg_score'] ?? 0, 1) ?>
                </span>
              </td>
              <td class="text-center pe-4">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold">
                  <?= number_format($u['best_score'] ?? 0, 1) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>

  <!-- CTA nếu chưa đăng nhập -->
  <?php if (!$is_logged_in): ?>
  <div class="card border-0 bg-primary text-white shadow-lg rounded-4 mt-5">
    <div class="card-body text-center py-5">
      <i class="bi bi-rocket-takeoff display-3 text-white-50 mb-3"></i>
      <h3 class="fw-bold">Bạn muốn chinh phục bảng xếp hạng?</h3>
      <p class="text-white-50 mb-4">Đăng nhập tài khoản để tham gia giải đề và ghi danh ngay hôm nay.</p>
      <a href="login.php" class="btn btn-light btn-lg px-5 rounded-pill fw-bold text-primary shadow">
        Đăng nhập ngay
      </a>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php include 'includes/footer_guest.php'; ?>