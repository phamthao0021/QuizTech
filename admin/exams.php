<?php
// exams.php - Trang Đề thi (Guest)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$page_title = 'Đề thi';
$subject_filter = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;

$subjects = getSubjects() ?? [];
$exams = getExams() ?? [];

// Lọc theo môn học
if ($subject_filter > 0) {
    $exams = array_filter($exams, function($e) use ($subject_filter) {
        return $e['subject_id'] == $subject_filter;
    });
}

include 'includes/header_guest.php';
?>

<div class="container py-5">
    <h1 class="mb-4">📝 Đề thi</h1>

    <!-- Filter by Subject -->
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2">
            <a href="exams.php" class="btn btn-sm <?= $subject_filter == 0 ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Tất cả
            </a>
            <?php foreach ($subjects as $s): ?>
                <a href="exams.php?subject=<?= $s['id'] ?>"
                   class="btn btn-sm <?= $subject_filter == $s['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <?= e($s['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Exams List -->
    <?php if (empty($exams)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle display-4"></i>
            <h4 class="mt-3">Chưa có đề thi nào</h4>
            <p class="text-muted mb-0">Vui lòng quay lại sau</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($exams as $e): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="card-body">
                            <!-- Subject Badge -->
                            <span class="badge bg-secondary mb-2">
                                <?= e($e['subject_name'] ?? 'N/A') ?>
                            </span>

                            <!-- Title -->
                            <h6 class="card-title mt-2">
                                <?= e($e['title'] ?? 'Untitled') ?>
                            </h6>

                            <!-- Description -->
                            <?php if (!empty($e['description'])): ?>
                                <p class="card-text small text-muted">
                                    <?= e(substr($e['description'], 0, 80)) ?>
                                    <?= strlen($e['description']) > 80 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>

                            <!-- Stats -->
                            <div class="d-flex gap-2 mt-3">
                                <span class="badge bg-primary">
                                    <i class="bi bi-question-circle"></i>
                                    <?= (int)($e['total_questions'] ?? 0) ?> câu
                                </span>
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-clock"></i>
                                    <?= (int)($e['duration'] ?? 20) ?> phút
                                </span>
                            </div>
                        </div>

                        <!-- Footer with Button -->
                        <div class="card-footer bg-transparent border-top-0">
                            <?php if (isLoggedIn()): ?>
                                <a href="exam.php?id=<?= (int)$e['id'] ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-play-fill"></i> Làm bài
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-shadow {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}
.hover-shadow:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-4px);
}
</style>

<?php include 'includes/footer_guest.php'; ?>