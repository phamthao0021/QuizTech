<?php
// student/exams.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireLogin();

$subject_id = (int)($_GET['subject'] ?? 0);

if ($subject_id > 0) {
    $exams = getExamsBySubject($subject_id);
    $subject = getSubject($subject_id);
    $title = $subject['name'] ?? 'Đề thi';
} else {
    $exams = getExams();
    $title = 'Tất cả đề thi';
}

$page_title = 'Danh sách đề thi';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4><?= e($title) ?></h4>
                <p class="text-muted">Chọn một đề thi để bắt đầu làm bài.</p>
            </div>
            <a href="subjects.php" class="btn btn-outline-primary rounded-pill">
                <i class="bi bi-book"></i> Môn học
            </a>
        </div>

        <?php if (empty($exams)): ?>
            <div class="dashboard-card text-center py-5">
                <i class="bi bi-file-earmark-x display-1 text-secondary"></i>
                <h3 class="mt-3">Chưa có đề thi</h3>
                <p class="text-muted">Hiện chưa có đề thi nào khả dụng trong danh mục này.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($exams as $e): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="dashboard-card h-100 d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-primary rounded-pill">
                                    <?= e($e['subject_name'] ?? 'Môn học') ?>
                                </span>
                            </div>
                            <h5 class="fw-bold mt-1"><?= e($e['title']) ?></h5>
                            <p class="text-muted small flex-grow-1"><?= e($e['description'] ?? '') ?></p>
                            
                            <div class="d-flex gap-2 my-3">
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-list-check"></i> <?= $e['total_questions'] ?? $e['question_count'] ?? 0 ?> câu
                                </span>
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-clock"></i> <?= $e['duration'] ?? 30 ?> phút
                                </span>
                            </div>

                            <div class="mt-auto">
                                <a href="waiting-room.php?exam_id=<?= $e['id'] ?>" class="btn btn-primary">
    <i class="bi bi-box-arrow-in-right me-1"></i> Vào phòng chờ
</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../includes/footer.php'; ?>