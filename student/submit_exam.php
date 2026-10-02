<?php
// student/submit_exam.php

// Tự động định vị thư mục gốc (root)
$rootDir = dirname(__DIR__);

require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/functions.php';
require_once $rootDir . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = currentUser();
$user_id = (int)($user['id'] ?? $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

// 1. LẤY DỮ LIỆU TỪ FORM NỘP BÀI
$exam_id          = (int)($_POST['exam_id'] ?? 0);
$room_id          = (int)($_POST['room_id'] ?? 0);
$duration_seconds = (int)($_POST['duration_seconds'] ?? 0);
$user_answers     = $_POST['answers'] ?? []; // Dạng [question_id => 'A']

// 2. TRUY VẤN CÂU HỎI TỪ CSDL
if ($exam_id === 0) {
    // Đề thi thử / ôn tập tự do
    $question_ids = array_keys($user_answers);
    if (empty($question_ids)) {
        header('Location: dashboard.php');
        exit();
    }
    $in_clause = implode(',', array_map('intval', $question_ids));
    $questions = $pdo->query("SELECT * FROM questions WHERE id IN ($in_clause)")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Bài thi chính thức: lấy qua bảng trung gian exam_questions
    $stmtQ = $pdo->prepare("
        SELECT q.* FROM questions q 
        JOIN exam_questions eq ON q.id = eq.question_id 
        WHERE eq.exam_id = ?
        ORDER BY eq.question_order ASC, q.id ASC
    ");
    $stmtQ->execute([$exam_id]);
    $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: Nếu không có bảng trung gian, lấy trực tiếp theo exam_id
    if (empty($questions)) {
        $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
        $stmtQ->execute([$exam_id]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (empty($questions)) {
    die("Không tìm thấy dữ liệu câu hỏi cho bài thi này!");
}

// 3. TÍNH ĐIỂM VÀ THỐNG KÊ CHI TIẾT
$total_questions = count($questions);
$correct_count   = 0;
$detailed_results = [];

$map_letters = ['0' => 'A', '1' => 'B', '2' => 'C', '3' => 'D'];

foreach ($questions as $q) {
    $q_id = $q['id'];

    // Chuẩn hóa đáp án đúng từ DB
    $raw_correct = $q['correct_option'] ?? $q['correct_answer'] ?? $q['answer'] ?? 'A';
    $correct_opt = strtoupper(trim((string)$raw_correct));
    if (isset($map_letters[$correct_opt])) {
        $correct_opt = $map_letters[$correct_opt];
    }

    // Đáp án của học sinh
    $user_opt = isset($user_answers[$q_id]) ? strtoupper(trim((string)$user_answers[$q_id])) : '';

    $is_correct = ($user_opt !== '' && $user_opt === $correct_opt);
    if ($is_correct) {
        $correct_count++;
    }

    // Lưu lại thông tin giải thích để hiển thị tại trang result.php
    $detailed_results[$q_id] = [
        'user_answer'    => $user_opt,
        'correct_answer' => $correct_opt,
        'is_correct'     => $is_correct,
        'explanation'    => $q['explanation'] ?? $q['explain_text'] ?? $q['solution'] ?? ''
    ];
}

$score = $total_questions > 0 ? round(($correct_count / $total_questions) * 10, 2) : 0;
$answers_json = json_encode($user_answers, JSON_UNESCAPED_UNICODE);

// 4. THÍCH ỨNG CỘT CSDL VÀ LƯU KẾT QUẢ ĐỘNG (CHỐNG LỖI MISSING COLUMN)
try {
    $targetTable = $pdo->query("SHOW TABLES LIKE 'results'")->fetch() ? 'results' : 'exam_attempts';
    $existingCols = $pdo->query("SHOW COLUMNS FROM {$targetTable}")->fetchAll(PDO::FETCH_COLUMN);

    $insertData = [];
    
    // Ghép dữ liệu tương ứng với cột có sẵn trong CSDL
    if (in_array('user_id', $existingCols))      $insertData['user_id'] = $user_id;
    if (in_array('student_id', $existingCols))   $insertData['student_id'] = $user_id;
    if (in_array('exam_id', $existingCols))      $insertData['exam_id'] = $exam_id;
    if (in_array('room_id', $existingCols))      $insertData['room_id'] = $room_id;
    if (in_array('score', $existingCols))        $insertData['score'] = $score;
    if (in_array('correct_answers', $existingCols)) $insertData['correct_answers'] = $correct_count;
    if (in_array('total_questions', $existingCols)) $insertData['total_questions'] = $total_questions;
    if (in_array('answers', $existingCols))      $insertData['answers'] = $answers_json;
    if (in_array('answers_json', $existingCols)) $insertData['answers_json'] = $answers_json;
    if (in_array('time_taken', $existingCols))   $insertData['time_taken'] = $duration_seconds;
    if (in_array('duration_seconds', $existingCols)) $insertData['duration_seconds'] = $duration_seconds;
    if (in_array('created_at', $existingCols))   $insertData['created_at'] = date('Y-m-d H:i:s');

    $fields = array_keys($insertData);
    $placeholders = array_map(fn($f) => ":{$f}", $fields);

    $sqlInsert = "INSERT INTO {$targetTable} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute($insertData);
    
    $result_id = $pdo->lastInsertId();

    // Lưu tạm kết quả giải thích vào Session để hiển thị ở result.php
    $_SESSION['last_exam_result'] = [
        'result_id'        => $result_id,
        'exam_id'          => $exam_id,
        'score'            => $score,
        'correct_count'    => $correct_count,
        'total_questions'  => $total_questions,
        'duration_seconds' => $duration_seconds,
        'detailed_results' => $detailed_results
    ];

    // Chuyển hướng sang trang kết quả
    header("Location: exam_result.php?id=" . $result_id);
    exit();

} catch (PDOException $e) {
    die("<div style='padding:20px; color:red; font-family:sans-serif;'>
            <h3>Lỗi CSDL khi lưu kết quả:</h3> " . htmlspecialchars($e->getMessage()) . "
         </div>");
}