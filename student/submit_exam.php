<?php
// student/submit_exam.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exams.php');
    exit;
}

global $pdo;

$exam_id          = (int)($_POST['exam_id'] ?? 0);
$student_id       = (int)($_SESSION['user_id'] ?? 0);
$duration_seconds = (int)($_POST['duration_seconds'] ?? 0);
$user_answers     = $_POST['answers'] ?? []; // Mảng [question_id => "A/B/C/D"]

if (!$exam_id || !$student_id) {
    header('Location: exams.php');
    exit;
}

// 1. Lấy danh sách câu hỏi chuẩn thuộc đề thi
try {
    $stmt_q = $pdo->prepare("
        SELECT q.id, q.correct_answer 
        FROM questions q
        JOIN exam_questions eq ON q.id = eq.question_id
        WHERE eq.exam_id = ?
    ");
    $stmt_q->execute([$exam_id]);
    $questions = $stmt_q->fetchAll();
} catch (PDOException $e) {
    $questions = [];
}

// Backup: Lấy câu hỏi theo subject_id nếu không xài bảng exam_questions
if (empty($questions)) {
    $stmt_exam = $pdo->prepare("SELECT subject_id FROM exams WHERE id = ?");
    $stmt_exam->execute([$exam_id]);
    $exam_data = $stmt_exam->fetch();
    
    if (!empty($exam_data['subject_id'])) {
        $stmt_q = $pdo->prepare("SELECT id, correct_answer FROM questions WHERE subject_id = ?");
        $stmt_q->execute([$exam_data['subject_id']]);
        $questions = $stmt_q->fetchAll();
    }
}

// 2. Chấm điểm bài thi
$total_questions = count($questions);
$correct_answers = 0;
$wrong_answers   = 0;
$unanswered      = 0;

foreach ($questions as $q) {
    $q_id = $q['id'];
    $right_ans = strtoupper(trim($q['correct_answer'] ?? ''));

    if (isset($user_answers[$q_id]) && $user_answers[$q_id] !== '') {
        $user_ans = strtoupper(trim($user_answers[$q_id]));
        if ($user_ans === $right_ans) {
            $correct_answers++;
        } else {
            $wrong_answers++;
        }
    } else {
        $unanswered++;
    }
}

// Tính thang điểm 10 và phần trăm
$score = $total_questions > 0 ? round(($correct_answers / $total_questions) * 10, 2) : 0;
$percentage = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100, 2) : 0;

$answers_json = json_encode($user_answers, JSON_UNESCAPED_UNICODE);
$now = date('Y-m-d H:i:s');

// 3. Lưu lượt làm bài vào bảng exam_attempts
$stmt_insert = $pdo->prepare("
    INSERT INTO exam_attempts (
        exam_id, 
        student_id, 
        started_at, 
        submitted_at, 
        duration_seconds, 
        score, 
        total_questions, 
        correct_answers, 
        wrong_answers, 
        unanswered, 
        answers_json, 
        percentage, 
        status, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?)
");

$stmt_insert->execute([
    $exam_id,
    $student_id,
    $now, // tạm lấy mốc thời gian hoàn thành làm mốc started_at nếu chưa lưu lúc bắt đầu
    $now,
    $duration_seconds,
    $score,
    $total_questions,
    $correct_answers,
    $wrong_answers,
    $unanswered,
    $answers_json,
    $percentage,
    $now
]);

$attempt_id = $pdo->lastInsertId();

// 4. Chuyển hướng tới trang chi tiết kết quả
header("Location: result.php?id=" . $attempt_id);
exit;