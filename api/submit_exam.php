<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../student/dashboard.php');
    exit();
}

$user_id      = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['id'] ?? $_POST['user_id'] ?? 0);
$exam_id      = (int)($_POST['exam_id'] ?? 0);
$attempt_id   = (int)($_POST['attempt_id'] ?? 0);
$time_taken   = (int)($_POST['time_taken_seconds'] ?? $_POST['time_taken'] ?? 0);
$user_answers = $_POST['answers'] ?? [];

if ($exam_id <= 0 || $user_id <= 0) {
    die("Dữ liệu bài thi hoặc người dùng không hợp lệ.");
}

$sqlQ = "SELECT q.id, q.correct_answer 
         FROM exam_questions eq 
         JOIN questions q ON eq.question_id = q.id 
         WHERE eq.exam_id = :exam_id";

$stmtQ = $pdo->prepare($sqlQ);
$stmtQ->execute(['exam_id' => $exam_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

$total_questions = count($questions);
$correct_count   = 0;

if ($total_questions === 0) {
    die("Không tìm thấy câu hỏi thuộc đề thi này.");
}

foreach ($questions as $q) {
    $q_id = $q['id'];
    $correct_opt = strtoupper(trim($q['correct_answer'] ?? ''));
    $user_opt = isset($user_answers[$q_id]) ? strtoupper(trim($user_answers[$q_id])) : null;
    
    if ($user_opt !== null && $user_opt === $correct_opt) {
        $correct_count++;
    }
}

$score = round(($correct_count / $total_questions) * 10, 2);
$answers_json = json_encode($user_answers, JSON_UNESCAPED_UNICODE);

try {
    $pdo->beginTransaction();

    $sqlResult = "INSERT INTO results (user_id, exam_id, score, correct_answers, total_questions, time_taken, answers, created_at) 
                  VALUES (:user_id, :exam_id, :score, :correct_answers, :total_questions, :time_taken, :answers, NOW())";
    
    $stmtResult = $pdo->prepare($sqlResult);
    $stmtResult->execute([
        'user_id'         => $user_id,
        'exam_id'         => $exam_id,
        'score'           => $score,
        'correct_answers' => $correct_count,
        'total_questions' => $total_questions,
        'time_taken'      => $time_taken,
        'answers'         => $answers_json
    ]);

    $result_id = $pdo->lastInsertId();

    if ($attempt_id > 0) {
        $wrong_count = $total_questions - $correct_count;
        $sqlAttempt = "UPDATE exam_attempts 
                       SET submitted_at = NOW(),
                           duration_seconds = :duration,
                           score = :score,
                           total_questions = :total_questions,
                           correct_answers = :correct_answers,
                           wrong_answers = :wrong_answers,
                           answers_json = :answers_json,
                           percentage = :percentage,
                           status = 'submitted'
                       WHERE id = :attempt_id AND student_id = :student_id";
        
        $stmtAttempt = $pdo->prepare($sqlAttempt);
        $stmtAttempt->execute([
            'duration'        => $time_taken,
            'score'           => $score,
            'total_questions' => $total_questions,
            'correct_answers' => $correct_count,
            'wrong_answers'   => $wrong_count,
            'answers_json'    => $answers_json,
            'percentage'      => round(($correct_count / $total_questions) * 100, 2),
            'attempt_id'      => $attempt_id,
            'student_id'      => $user_id
        ]);
    }

    $pdo->commit();

    header("Location: ../student/result.php?id=" . $result_id);
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Lỗi CSDL khi nộp bài thi: " . $e->getMessage());
}
?>