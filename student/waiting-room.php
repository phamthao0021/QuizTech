<?php
// student/waiting-room.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireLogin();

$exam_id = $_GET['exam_id'] ?? 0;
$student_id = $_SESSION['user_id'] ?? 0;

global $pdo;

// Lấy thông tin bài thi
$stmt = $pdo->prepare("
    SELECT e.*, s.name AS subject_name 
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id 
    WHERE e.id = ?
");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: exams.php');
    exit;
}

// Đếm tổng số câu hỏi trong đề thi
try {
    $stmt_q = $pdo->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
    $stmt_q->execute([$exam_id]);
    $total_questions = $stmt_q->fetchColumn();
} catch (PDOException $e) {
    $stmt_q = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE subject_id = ?");
    $stmt_q->execute([$exam['subject_id']]);
    $total_questions = $stmt_q->fetchColumn();
}

$page_title = 'Phòng chờ thi - ' . $exam['title'];
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- Card Thông Tin Đề Thi -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 text-center">
                        <span class="badge bg-primary-soft text-primary px-3 py-2 mb-2 rounded-pill fs-7">
                            <i class="bi bi-book me-1"></i> Môn: <?= e($exam['subject_name'] ?? 'Chung') ?>
                        </span>
                        <h2 class="fw-bold mb-3"><?= e($exam['title']) ?></h2>
                        <p class="text-muted mb-4"><?= e($exam['description'] ?? 'Hãy đọc kỹ hướng dẫn trước khi bắt đầu làm bài.') ?></p>

                        <div class="row g-3 my-3">
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-clock fs-3 text-warning"></i>
                                    <div class="text-muted fs-7 mt-1">Thời gian</div>
                                    <strong class="fs-6"><?= (int)($exam['duration_minutes'] ?? 40) ?> phút</strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-question-circle fs-3 text-info"></i>
                                    <div class="text-muted fs-7 mt-1">Số câu hỏi</div>
                                    <strong class="fs-6"><?= $total_questions ?> câu</strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-award fs-3 text-success"></i>
                                    <div class="text-muted fs-7 mt-1">Thang điểm</div>
                                    <strong class="fs-6">10.0</strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-shield-check fs-3 text-danger"></i>
                                    <div class="text-muted fs-7 mt-1">Chế độ</div>
                                    <strong class="fs-6">Trực tuyến</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nội quy & Nút Bắt đầu -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Quy định làm bài</h5>
                        <ul class="text-muted mb-4 fs-7">
                            <li>Thời gian làm bài sẽ bắt đầu tính ngay sau khi bạn nhấn nút <strong>"Bắt đầu làm bài"</strong>.</li>
                            <li>Không được thoát hoặc tải lại trang trong quá trình thi. Bài thi sẽ tự động nộp khi hết thời gian.</li>
                            <li>Đảm bảo kết nối mạng ổn định suốt quá trình làm bài.</li>
                        </ul>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <a href="exams.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Quay lại
                            </a>
                            <a href="exam.php?id=<?= $exam['id'] ?>&start=1" class="btn btn-success btn-lg px-4">
                                <i class="bi bi-play-circle me-1"></i> Bắt đầu làm bài
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>