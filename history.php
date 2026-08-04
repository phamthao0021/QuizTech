<?php
// history.php - Lịch sử thi (Chỉ khi đăng nhập)
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';
requireLogin();

$history = getHistory($_SESSION['user_id']);

$page_title = 'Lịch sử thi';

// Kiểm tra nếu đã đăng nhập thì dùng header guest vẫn được
include 'includes/header_guest.php';
?>

<div class="container py-5">
    <h1 class="mb-4">📜 Lịch sử thi</h1>
    
    <?php if (empty($history)): ?>
        <div class="alert alert-info text-center py-4">
            <p class="mb-0">Bạn chưa có lịch sử thi. Hãy bắt đầu làm bài thi!</p>
            <a href="exams.php" class="btn btn-primary mt-3">Làm bài thi</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Đề thi</th>
                                <th>Môn</th>
                                <th>Điểm</th>
                                <th>Thời gian</th>
                                <th>Ngày làm</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><strong><?= e($h['exam_title']) ?></strong></td>
                                    <td><?= e($h['subject_name']) ?></td>
                                    <td>
                                        <span class="badge <?= $h['score'] >= 8 ? 'bg-success' : ($h['score'] >= 5 ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= number_format($h['score'], 1) ?>
                                        </span>
                                    </td>
                                    <td><?= gmdate('i:s', $h['time_taken'] ?? 0) ?></td>
                                    <td><?= format_date($h['created_at']) ?></td>
                                    <td>
                                        <a href="result.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer_guest.php'; ?>