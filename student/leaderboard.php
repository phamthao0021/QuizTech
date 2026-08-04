<?php
// student/leaderboard.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireLogin();

$leaderboard = getLeaderboard() ?? [];
$total_students = count($leaderboard);

$page_title = 'Bảng xếp hạng';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4>Bảng xếp hạng</h4>
                <p class="text-muted">Thành tích xuất sắc của các sinh viên.</p>
            </div>
            <span class="badge bg-primary px-3 py-2"><?= $total_students ?> thí sinh</span>
        </div>

        <?php if (empty($leaderboard)): ?>
            <div class="dashboard-card text-center py-5">
                <i class="bi bi-trophy display-1 text-warning"></i>
                <h3 class="mt-3">Chưa có dữ liệu bảng xếp hạng</h3>
                <p class="text-muted">Hãy làm bài thi ngay để trở thành người dẫn đầu!</p>
                <a href="exams.php" class="btn btn-primary px-4 mt-2">
                    <i class="bi bi-play-fill"></i> Làm bài ngay
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_students ?></div>
                        <div class="stat-label">Thí sinh tham gia</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($leaderboard[0]['best_score'] ?? 0, 1) ?></div>
                        <div class="stat-label">Điểm cao nhất</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">
                            <?= number_format(array_sum(array_column($leaderboard, 'avg_score')) / max($total_students, 1), 1) ?>
                        </div>
                        <div class="stat-label">Điểm trung bình chung</div>
                    </div>
                </div>
            </div>

            <div class="card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="90">Hạng</th>
                                <th>Thí sinh</th>
                                <th>Số bài thi</th>
                                <th>Điểm TB</th>
                                <th>Điểm cao nhất</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $i => $u): ?>
                                <tr>
                                    <td>
                                        <?php
                                        if ($i == 0) echo "🥇";
                                        elseif ($i == 1) echo "🥈";
                                        elseif ($i == 2) echo "🥉";
                                        else echo '<span class="badge bg-secondary">' . ($i + 1) . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?= e($u['name'] ?? '') ?></strong>
                                        <?php if (!empty($u['student_code'])): ?>
                                            <br><small class="text-muted"><?= e($u['student_code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?= (int)($u['exam_count'] ?? 0) ?></span></td>
                                    <td><span class="badge bg-success"><?= number_format($u['avg_score'] ?? 0, 1) ?></span></td>
                                    <td><span class="badge bg-primary"><?= number_format($u['best_score'] ?? 0, 1) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../includes/footer.php'; ?>