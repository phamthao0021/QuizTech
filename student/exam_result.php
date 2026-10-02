<?php
// student/result.php
$rootDir = dirname(__DIR__);
require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/functions.php';
require_once $rootDir . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isLoggedIn()) { header('Location: ../login.php'); exit(); }

$result_id = intval($_GET['id'] ?? 0);
$sessionData = $_SESSION['last_exam_result'] ?? null;

if (!$sessionData || $sessionData['result_id'] != $result_id) {
    $targetTable = $pdo->query("SHOW TABLES LIKE 'results'")->fetch() ? 'results' : 'exam_attempts';
    $stmt = $pdo->prepare("SELECT * FROM {$targetTable} WHERE id = ?");
    $stmt->execute([$result_id]);
    $dbResult = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dbResult) {
        setFlash('danger', 'Không tìm thấy thông tin kết quả!');
        redirect('dashboard.php');
        exit();
    }
    $exam_id = $dbResult['exam_id'];
    $score   = $dbResult['score'];
    $answers = json_decode($dbResult['answers'] ?? $dbResult['answers_json'] ?? '{}', true);
} else {
    $exam_id = $sessionData['exam_id'];
    $score   = $sessionData['score'];
    $answers = array_column($sessionData['detailed_results'], 'user_answer', 'key');
}

$stmtExam = $pdo->prepare("SELECT e.*, s.name as subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id WHERE e.id = ?");
$stmtExam->execute([$exam_id]);
$exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

$stmtQ = $pdo->prepare("SELECT q.* FROM questions q JOIN exam_questions eq ON q.id = eq.question_id WHERE eq.exam_id = ? ORDER BY eq.question_order ASC, q.id ASC");
$stmtQ->execute([$exam_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
    $stmtQ->execute([$exam_id]);
    $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'Kết quả bài thi';
require_once $rootDir . '/includes/header_student.php';
?>

<style>
:root {
  --primary-gradient: linear-gradient(135deg, #6f42c1 0%, #4e73df 100%);
}    
    .bg-gradient-purple { background: linear-gradient(135deg, #6f42c1 0%, #4e73df 100%) !important; color: #fff; }
    .score-card { border-radius: 20px; box-shadow: 0 10px 30px rgba(111, 66, 193, 0.15); }
    .res-card { border: none; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .res-card.correct-card { border-left: 6px solid #198754; }
    .res-card.wrong-card { border-left: 6px solid #dc3545; }
    .res-card.empty-card { border-left: 6px solid #ffc107; }
    .opt-item { border: 1.5px solid #e9ecef; border-radius: 10px; padding: 12px 16px; margin-bottom: 8px; font-weight: 500; }
    .opt-item.is-correct { background-color: #d1e7dd; border-color: #a3cfbb; color: #0a3622; font-weight: 700; }
    .opt-item.is-wrong { background-color: #f8d7da; border-color: #f5c2c7; color: #842029; }
</style>

<div class="container py-4">
    <!-- Thống kê điểm số tổng quan -->
    <div class="card score-card bg-gradient-purple text-center p-4 p-md-5 mb-5 border-0">
        <h4 class="text-white-50 text-uppercase fw-bold mb-2">Kết Quả Đã Ghi Nhận</h4>
        <h2 class="fw-bold text-white mb-3"><?= htmlspecialchars($exam['title'] ?? 'Bài thi') ?></h2>
        
        <div class="display-1 fw-bold my-2 text-white">
            <?= number_format((float)$score, 2) ?> <span class="fs-4 opacity-75">/ 10</span>
        </div>

        <div class="mt-3">
            <?php if ($score >= 5.0): ?>
                <span class="badge bg-success fs-6 px-4 py-2 rounded-pill shadow">ĐẠT YÊU CẦU</span>
            <?php else: ?>
                <span class="badge bg-danger fs-6 px-4 py-2 rounded-pill shadow">CHƯA ĐẠT</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chi tiết đáp án và Lời giải thích -->
    <h4 class="fw-bold text-dark mb-4"><i class="bi bi-journal-check me-2 text-primary"></i>Chi tiết lời giải & Đáp án</h4>

    <?php 
    $map_letters = ['0' => 'A', '1' => 'B', '2' => 'C', '3' => 'D'];

    foreach ($questions as $index => $q): 
        $q_id = $q['id'];
        $qText = $q['content'] ?? $q['question_text'] ?? '';
        
        $correct_opt = strtoupper(trim((string)($q['correct_option'] ?? $q['correct_answer'] ?? $q['answer'] ?? 'A')));
        if (isset($map_letters[$correct_opt])) $correct_opt = $map_letters[$correct_opt];

        $user_opt = isset($answers[$q_id]) ? strtoupper(trim((string)$answers[$q_id])) : '';
        if (isset($sessionData['detailed_results'][$q_id])) {
            $user_opt = $sessionData['detailed_results'][$q_id]['user_answer'];
        }

        $is_correct = ($user_opt !== '' && $user_opt === $correct_opt);
        $explanation = trim($q['explanation'] ?? $q['explain_text'] ?? $q['solution'] ?? '');

        $cardClass = $is_correct ? 'correct-card' : ($user_opt === '' ? 'empty-card' : 'wrong-card');
    ?>
        <div class="card res-card <?= $cardClass ?> mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark fs-5">Câu <?= $index + 1 ?></span>
                <?php if ($is_correct): ?>
                    <span class="badge bg-success fs-6 px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i>Đúng</span>
                <?php elseif ($user_opt === ''): ?>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill"><i class="bi bi-exclamation-triangle me-1"></i>Bỏ trống</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill"><i class="bi bi-x-circle me-1"></i>Sai</span>
                <?php endif; ?>
            </div>

            <div class="card-body pt-0 px-4 pb-4">
                <p class="fw-bold text-dark fs-6 mb-3"><?= nl2br(htmlspecialchars($qText)) ?></p>

                <!-- Các lựa chọn -->
                <div class="options-list mb-3">
                    <?php 
                    $options = ['A' => $q['option_a'] ?? '', 'B' => $q['option_b'] ?? '', 'C' => $q['option_c'] ?? '', 'D' => $q['option_d'] ?? ''];
                    foreach ($options as $key => $val):
                        if (trim($val) === '') continue;
                        $optClass = '';
                        if ($key === $correct_opt) $optClass = 'is-correct';
                        elseif ($key === $user_opt && !$is_correct) $optClass = 'is-wrong';
                    ?>
                        <div class="opt-item d-flex justify-content-between align-items-center <?= $optClass ?>">
                            <div>
                                <span class="me-2 fw-bold"><?= $key ?>.</span>
                                <span><?= htmlspecialchars($val) ?></span>
                            </div>
                            <div>
                                <?php if ($key === $correct_opt): ?>
                                    <span class="small fw-bold text-success"><i class="bi bi-check-lg me-1"></i>Đáp án đúng</span>
                                <?php endif; ?>
                                <?php if ($key === $user_opt && !$is_correct): ?>
                                    <span class="small fw-bold text-danger"><i class="bi bi-x-lg me-1"></i>Bạn đã chọn</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- LỜI GIẢI THÍCH (Câu SAI/Trống hiện ngay, câu ĐÚNG bấm vào mới hiện) -->
                <?php if (!empty($explanation)): ?>
                    <?php if (!$is_correct): ?>
                        <div class="alert alert-danger border-0 rounded-3 mt-3 mb-0">
                            <h6 class="fw-bold text-danger mb-1"><i class="bi bi-lightbulb-fill me-2"></i>Lời giải thích:</h6>
                            <div class="text-dark"><?= nl2br(htmlspecialchars($explanation)) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="accordion mt-3" id="acc-<?= $q_id ?>">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-light text-primary fw-bold rounded-3 shadow-none p-2 fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $q_id ?>">
                                        <i class="bi bi-lightbulb me-2"></i>Xem lời giải thích chi tiết
                                    </button>
                                </h2>
                                <div id="collapse-<?= $q_id ?>" class="accordion-collapse collapse" data-bs-parent="#acc-<?= $q_id ?>">
                                    <div class="accordion-body bg-light text-dark rounded-bottom-3">
                                        <?= nl2br(htmlspecialchars($explanation)) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-5 mb-4">
        <a href="dashboard.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold bg-gradient-purple border-0 shadow">
            <i class="bi bi-house-door-fill me-2"></i>Quay về Trang Chủ
        </a>
    </div>
</div>

<?php require_once $rootDir . '/includes/footer.php'; ?>