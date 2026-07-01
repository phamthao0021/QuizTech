<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Trang chủ';
$pdo = db();

// Thống kê tổng quan
$totalExams = $pdo->query('SELECT COUNT(*) FROM exams')->fetchColumn();
$totalQuestions = $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$totalSubjects = $pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn();

// Lấy các môn học nổi bật
$subjects = $pdo->query('SELECT * FROM subjects ORDER BY name LIMIT 8')->fetchAll();

// Lấy đề thi mới nhất
$exams = $pdo->query('
    SELECT e.*, s.name as subject_name, s.slug as subject_slug
    FROM exams e
    JOIN subjects s ON s.id = e.subject_id
    WHERE e.is_active = 1
    ORDER BY e.created_at DESC
    LIMIT 6
')->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="row align-items-center py-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold">Thi trắc nghiệm kiến thức <span class="text-primary">CNTT</span></h1>
            <p class="lead text-muted">
                <?= APP_NAME ?> - Hệ thống thi trắc nghiệm trực tuyến với ngân hàng câu hỏi đa dạng và phòng thi đấu thời gian thực.
            </p>
            <div class="mt-4">
                <?php if (is_logged_in()): ?>
                    <a href="<?= base_url('exams.php') ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-file-alt"></i> Làm bài thi
                    </a>
                    <a href="<?= base_url('rooms.php') ?>" class="btn btn-success btn-lg">
                        <i class="fas fa-users"></i> Phòng thi đấu
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('register.php') ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Bắt đầu ngay
                    </a>
                    <a href="<?= base_url('login.php') ?>" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row g-3">
                <div class="col-4">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h2 class="text-primary"><?= number_format($totalExams) ?></h2>
                            <p class="text-muted">Đề thi</p>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h2 class="text-success"><?= number_format($totalQuestions) ?></h2>
                            <p class="text-muted">Câu hỏi</p>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h2 class="text-warning"><?= number_format($totalSubjects) ?></h2>
                            <p class="text-muted">Môn học</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Môn học -->
    <section class="py-4">
        <h2 class="text-center mb-4">Môn học</h2>
        <div class="row g-4">
            <?php foreach ($subjects as $subject): ?>
                <div class="col-md-3 col-lg-2">
                    <a href="<?= base_url('exams.php?subject=' . $subject['slug']) ?>" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <i class="fas <?= e($subject['icon'] ?? 'fa-book') ?> fa-3x" style="color: <?= e($subject['color'] ?? '#0d6efd') ?>"></i>
                                <h6 class="mt-2"><?= e($subject['name']) ?></h6>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Đề thi mới nhất -->
    <section class="py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Đề thi mới nhất</h2>
            <a href="<?= base_url('exams.php') ?>" class="btn btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="row g-4">
            <?php foreach ($exams as $exam): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <span class="badge bg-secondary"><?= e($exam['subject_name']) ?></span>
                            <h6 class="mt-2"><?= e($exam['title']) ?></h6>
                            <p class="text-muted small"><?= e($exam['description'] ?? '') ?></p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="far fa-clock"></i> <?= $exam['duration'] ?> phút</span>
                                <span><i class="fas fa-question-circle"></i> <?= $exam['total_questions'] ?> câu</span>
                            </div>
                            <?= get_difficulty_badge($exam['difficulty']) ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="<?= base_url('exam-take.php?id=' . $exam['id']) ?>" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-play"></i> Làm bài
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>