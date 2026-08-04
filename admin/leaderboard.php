<?php
// admin/leaderboard.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

$leaderboard = getLeaderboard();

// Sắp xếp lại thứ tự ưu tiên điểm số từ cao đến thấp
usort($leaderboard, function($a, $b) {
    return ($b['best_score'] ?? 0) <=> ($a['best_score'] ?? 0);
});

$page_title = 'Bảng xếp hạng';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4>Bảng xếp hạng Thí sinh</h4>
                <p class="text-muted">Danh sách thí sinh có điểm số xuất sắc nhất</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Xếp hạng</th>
                                <th>Thí sinh</th>
                                <th>Số bài thi</th>
                                <th>Điểm TB</th>
                                <th>Điểm cao nhất</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaderboard)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu xếp hạng.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($leaderboard as $index => $r): ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <?php if ($index == 0): ?>
                                                <span class="badge bg-warning text-dark fs-6">🥇 1</span>
                                            <?php elseif ($index == 1): ?>
                                                <span class="badge bg-secondary fs-6">🥈 2</span>
                                            <?php elseif ($index == 2): ?>
                                                <span class="badge text-bg-danger fs-6">🥉 3</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border ms-2"><?= $index + 1 ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= e($r['name']) ?></strong>
                                            <?php if (!empty($r['student_code'])): ?>
                                                <br><small class="text-muted">MSSV: <?= e($r['student_code']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-info text-dark"><?= $r['exam_count'] ?? 0 ?> bài</span></td>
                                        <td>
                                            <span class="badge bg-success"><?= number_format($r['avg_score'] ?? 0, 1) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6"><?= number_format($r['best_score'] ?? 0, 1) ?></span>
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
</div>

<?php include '../includes/footer.php'; ?>