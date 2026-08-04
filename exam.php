<?php
// exam.php - Làm bài thi
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';

// Kiểm tra đăng nhập
requireLogin();

// Lấy ID bài thi từ URL
$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($exam_id <= 0) {
    header('Location: exams.php');
    exit();
}

// Lấy thông tin đề thi
$stmt = $pdo->prepare("SELECT e.*, s.name as subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id WHERE e.id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    if (function_exists('setFlash')) {
        setFlash('danger', 'Không tìm thấy đề thi.');
    }
    header('Location: exams.php');
    exit();
}

// Lấy danh sách câu hỏi
$questions = function_exists('getQuestionsByExam') ? getQuestionsByExam($exam_id) : [];
$total_questions = count($questions);
$time_limit = $exam['time_limit'] ?? 20;

$page_title = $exam['title'];
include 'includes/header.php';
?>

<div class="page-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm rounded">
            <div>
                <h4 class="mb-1"><?= htmlspecialchars($exam['title']) ?></h4>
                <p class="text-muted mb-0">
                    <?= htmlspecialchars($exam['subject_name'] ?? 'Chung') ?> • <?= $total_questions ?> câu • <?= $time_limit ?> phút
                </p>
            </div>
            <div class="text-center">
                <div class="small text-muted">Thời gian còn lại</div>
                <div class="h2 fw-bold text-danger mb-0" id="timer"><?= str_pad($time_limit, 2, '0', STR_PAD_LEFT) ?>:00</div>
            </div>
        </div>

        <form method="POST" action="result.php" id="examForm">
            <?php if (function_exists('csrf_field')) csrf_field(); ?>
            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
            <input type="hidden" name="time_taken" id="timeTaken" value="0">

            <?php if (empty($questions)): ?>
                <div class="alert alert-warning">Đề thi này hiện chưa có câu hỏi nào.</div>
            <?php else: ?>
                <?php foreach ($questions as $i => $q): ?>
                    <div class="card mb-3 shadow-sm border-0 rounded-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-secondary">Câu <?= $i + 1 ?>/<?= $total_questions ?></span>
                                <span class="badge <?= ($q['difficulty'] ?? 'easy') === 'easy' ? 'bg-success' : (($q['difficulty'] ?? '') === 'medium' ? 'bg-warning' : 'bg-danger') ?>">
                                    <?= ucfirst($q['difficulty'] ?? 'easy') ?>
                                </span>
                            </div>
                            <h6 class="mt-2 fw-bold"><?= htmlspecialchars($q['content']) ?></h6>
                            <div class="mt-3">
                                <?php
                                $options = ['A', 'B', 'C', 'D'];
                                foreach ($options as $opt):
                                    $field = 'option_' . strtolower($opt);
                                    if (!empty($q[$field])):
                                ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio"
                                               name="q_<?= $q['id'] ?>"
                                               id="q<?= $q['id'] ?>_<?= $opt ?>"
                                               value="<?= $opt ?>" required>
                                        <label class="form-check-label" for="q<?= $q['id'] ?>_<?= $opt ?>">
                                            <strong><?= $opt ?>.</strong> <?= htmlspecialchars($q[$field]) ?>
                                        </label>
                                    </div>
                                <?php 
                                    endif; 
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex gap-2 my-4">
                    <button type="submit" class="btn btn-success btn-lg px-4" onclick="return confirm('Bạn có chắc muốn nộp bài thi này?')">
                        <i class="bi bi-check-circle me-1"></i> Nộp bài
                    </button>
                    <a href="exams.php" class="btn btn-secondary btn-lg px-4">Quay lại</a>
                </div>
            <?php endif; ?>
        </form>

    </div>
</div>

<script>
// Quản lý thời gian làm bài
let totalSeconds = <?= $time_limit * 60 ?>;
let timerElement = document.getElementById('timer');
let timeTakenInput = document.getElementById('timeTaken');

const timerInterval = setInterval(function() {
    totalSeconds--;
    const mins = Math.floor(totalSeconds / 60);
    const secs = totalSeconds % 60;
    
    if (timerElement) {
        timerElement.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        if (totalSeconds <= 60) {
            timerElement.style.color = '#dc3545';
        }
    }

    if (totalSeconds <= 0) {
        clearInterval(timerInterval);
        alert('Hết giờ làm bài! Hệ thống sẽ tự động nộp bài.');
        document.getElementById('examForm').submit();
    }
}, 1000);

// Tính thời gian đã làm khi gửi form
document.getElementById('examForm').addEventListener('submit', function() {
    const totalTime = <?= $time_limit * 60 ?>;
    const taken = totalTime - totalSeconds;
    document.getElementById('timeTaken').value = taken;
});
</script>

<?php include 'includes/footer.php'; ?>