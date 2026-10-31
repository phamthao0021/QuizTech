<?php
// result.php — Xử lý nộp bài trực tiếp và hiển thị kết quả.
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';
requireLogin();

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    verify_csrf();
    $studentId = (int) ($_SESSION['user_id'] ?? 0);
    $examId = filter_var($_POST['exam_id'] ?? null, FILTER_VALIDATE_INT);
    $timeTaken = filter_var($_POST['time_taken'] ?? null, FILTER_VALIDATE_INT);

    if ($studentId <= 0 || $examId === false || $examId === null || $examId <= 0) {
        setFlash('danger', 'Thông tin bài thi không hợp lệ.');
        redirect('exams.php');
        exit;
    }

    $exam = getExamById($examId);
    if (!$exam) {
        setFlash('danger', 'Đề thi không tồn tại.');
        redirect('exams.php');
        exit;
    }

    $maxTime = max(1, (int) ($exam['duration'] ?? 30)) * 60;
    if ($timeTaken === false || $timeTaken === null || $timeTaken < 0) {
        $timeTaken = $maxTime;
    }
    $timeTaken = min($maxTime, $timeTaken);

    $questions = getQuestionsByExam($examId);
    $total = count($questions);
    if ($total === 0) {
        setFlash('danger', 'Đề thi chưa có câu hỏi hợp lệ.');
        redirect('exams.php');
        exit;
    }

    $correct = 0;
    $unanswered = 0;
    $answers = [];
    foreach ($questions as $question) {
        $questionId = (int) $question['id'];
        $rawAnswer = $_POST['q_' . $questionId] ?? '';
        $answer = is_string($rawAnswer) ? trim($rawAnswer) : '';
        $answers[$questionId] = $answer;
        if ($answer === '') {
            $unanswered++;
        } elseif ($answer === (string) $question['correct_answer']) {
            $correct++;
        }
    }

    $wrong = $total - $correct - $unanswered;
    $score = round($correct / $total * 10, 1);
    $percentage = round($correct / $total * 100, 2);
    $startedAt = date('Y-m-d H:i:s', time() - $timeTaken);
    $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);
    if ($answersJson === false) {
        setFlash('danger', 'Không thể mã hóa câu trả lời.');
        redirect('exams.php');
        exit;
    }

    try {
        $pdo->beginTransaction();
        $check = $pdo->prepare('SELECT id FROM exam_attempts WHERE student_id = ? AND exam_id = ? AND room_id IS NULL LIMIT 1');
        $check->execute([$studentId, $examId]);
        if ($check->fetchColumn()) {
            $pdo->rollBack();
            setFlash('warning', 'Bạn đã làm bài thi này rồi!');
            redirect('exams.php');
            exit;
        }

        // Chỉ INSERT một lần. Không gọi saveResult() vì hàm đó cũng INSERT exam_attempts.
        $insert = $pdo->prepare("INSERT INTO exam_attempts
            (room_id, exam_id, student_id, started_at, submitted_at, duration_seconds,
             score, total_questions, correct_answers, wrong_answers, unanswered,
             percentage, status, answers_json)
            VALUES (NULL, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'submitted', ?)");
        $insert->execute([
            $examId, $studentId, $startedAt, $timeTaken,
            $score, $total, $correct, $wrong, $unanswered, $percentage, $answersJson
        ]);

        $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description)
                              VALUES (?, 'submit', 'Nộp bài thi')");
        $log->execute([$studentId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[result.php] ' . $exception->getMessage());
        setFlash('danger', 'Không thể lưu kết quả bài thi. Vui lòng liên hệ quản trị viên.');
        redirect('exams.php');
        exit;
    }

    $_SESSION['last_result'] = [
        'exam_id' => $examId,
        'exam_title' => $exam['title'] ?? 'Đề thi',
        'subject_name' => $exam['subject_name'] ?? '',
        'score' => $score,
        'correct' => $correct,
        'wrong' => $wrong,
        'unanswered' => $unanswered,
        'total' => $total,
        'time_taken' => $timeTaken,
    ];
    redirect('result.php');
    exit;
}

if ($requestMethod !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Phương thức không được hỗ trợ.');
}

if (empty($_SESSION['last_result']) || !is_array($_SESSION['last_result'])) {
    setFlash('warning', 'Không có kết quả để hiển thị.');
    redirect('exams.php');
    exit;
}

$result = $_SESSION['last_result'];
unset($_SESSION['last_result']);
$examTitle = (string) ($result['exam_title'] ?? 'Đề thi');
$subjectName = (string) ($result['subject_name'] ?? '');
$score = (float) ($result['score'] ?? 0);
$correct = (int) ($result['correct'] ?? 0);
$total = (int) ($result['total'] ?? 0);
$unanswered = (int) ($result['unanswered'] ?? 0);
$wrong = (int) ($result['wrong'] ?? max(0, $total - $correct - $unanswered));
$timeTaken = max(0, (int) ($result['time_taken'] ?? 0));
$percent = $total > 0 ? round($correct / $total * 100) : 0;
$page_title = 'Kết quả thi';
include __DIR__ . '/includes/header.php';
?>
<div class="page-wrapper">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div>
                <h4>Kết quả thi</h4>
                <p class="text-muted"><?= e($examTitle) ?> &bull; <?= e($subjectName) ?></p>
            </div>
            <span class="badge bg-<?= $score >= 8 ? 'success' : ($score >= 5 ? 'warning' : 'danger') ?>">
                <?= $score >= 8 ? 'Đạt loại Giỏi' : ($score >= 5 ? 'Đạt' : 'Chưa đạt') ?>
            </span>
        </div>
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8">
                <div class="card mb-4" style="border:none;border-radius:20px;background:linear-gradient(135deg,<?= $score >= 8 ? '#00b894,#00cec9' : ($score >= 5 ? '#fdcb6e,#e17055' : '#e17055,#d63031') ?>);box-shadow:0 8px 32px rgba(0,0,0,.15)">
                    <div class="card-body text-center text-white py-5">
                        <div style="font-size:5rem;line-height:1;font-weight:800">
                            <?php if ($score >= 8): ?>🎉<?php elseif ($score >= 5): ?>👍<?php else: ?>📚<?php endif; ?>
                        </div>
                        <div class="mt-3" style="font-size:1.1rem;opacity:.9">Điểm số của bạn</div>
                        <div style="font-size:5rem;font-weight:800;line-height:1.1"><?= number_format($score, 1) ?></div>
                        <div style="font-size:1.2rem;opacity:.8">/10</div>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-4"><div class="stat-card text-center"><div class="stat-number text-success"><?= $correct ?></div><div class="stat-label">Câu đúng</div></div></div>
                    <div class="col-4"><div class="stat-card text-center"><div class="stat-number text-danger"><?= $wrong ?></div><div class="stat-label">Câu sai</div></div></div>
                    <div class="col-4"><div class="stat-card text-center"><div class="stat-number text-secondary"><?= $unanswered ?></div><div class="stat-label">Chưa trả lời</div></div></div>
                </div>
                <div class="card mb-4"><div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span class="fw-600">Tỷ lệ đúng</span><span class="fw-bold"><?= $percent ?>%</span></div>
                    <div class="progress" style="height:14px;border-radius:7px">
                        <div class="progress-bar bg-<?= $score >= 8 ? 'success' : ($score >= 5 ? 'warning' : 'danger') ?>" style="width:<?= $percent ?>%;border-radius:7px"></div>
                    </div>
                    <div class="text-muted small mt-2"><i class="bi bi-clock"></i> Thời gian làm bài: <?= sprintf('%02d:%02d:%02d', intdiv($timeTaken, 3600), intdiv($timeTaken % 3600, 60), $timeTaken % 60) ?> &bull; Tổng <?= $total ?> câu</div>
                </div></div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="exams.php" class="btn btn-primary"><i class="bi bi-file-text"></i> Danh sách đề thi</a>
                    <a href="leaderboard.php" class="btn btn-outline-primary"><i class="bi bi-trophy"></i> Bảng xếp hạng</a>
                    <a href="history.php" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Lịch sử thi</a>
                    <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
