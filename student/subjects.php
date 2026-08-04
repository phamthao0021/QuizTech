<?php
// student/subjects.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireLogin();

$subjects   = getSubjects() ?? [];
$all_exams  = getExams() ?? [];

$page_title = 'Danh sách môn học';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4>Môn học</h4>
                <p class="text-muted">Chọn môn học để xem danh sách đề thi tương ứng.</p>
            </div>
            <span class="badge bg-primary px-3 py-2">
                <?= count($subjects) ?> Môn học
            </span>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?= count($subjects) ?></div>
                    <div class="stat-label">Tổng số môn học</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?= count($all_exams) ?></div>
                    <div class="stat-label">Tổng số đề thi</div>
                </div>
            </div>
        </div>

        <?php if (empty($subjects)): ?>
            <div class="dashboard-card text-center py-5">
                <i class="bi bi-book display-1 text-primary"></i>
                <h3 class="mt-3">Chưa có môn học</h3>
                <p class="text-muted">Hiện tại chưa có dữ liệu môn học nào trên hệ thống.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($subjects as $s): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <!-- ĐÃ SỬA: Đường dẫn đúng là "exams.php?subject=..." -->
                        <a href="exams.php?subject=<?= $s['id'] ?>" class="text-decoration-none">
                            <div class="dashboard-card h-100 text-center p-4">
                                <h5 class="fw-bold text-dark"><?= e($s['name']) ?></h5>
                                <p class="text-muted small mb-3">
                                    <?= e($s['description'] ?? 'Chưa có mô tả.') ?>
                                </p>
                                <span class="btn btn-outline-primary btn-sm rounded-pill">
                                    Xem đề thi <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../includes/footer.php'; ?>