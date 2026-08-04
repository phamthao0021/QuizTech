<?php
// api/submit_exam.php - Xử lý tính điểm và lưu trực tiếp vào bảng results
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Bật thông báo lỗi chi tiết để dễ kiểm tra nếu phát sinh sự cố CSDL
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../student/dashboard.php');
    exit();
}

// Lấy thông tin đầu vào
$user_id      = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['id'] ?? $_POST['user_id'] ?? 0);
$exam_id      = (int)($_POST['exam_id'] ?? 0);
$time_taken   = (int)($_POST['time_taken_seconds'] ?? 0);
$user_answers = $_POST['answers'] ?? []; // Dạng: [question_id => "A"]

if ($exam_id <= 0 || $user_id <= 0) {
    die("Dữ liệu bài thi hoặc ID người dùng không hợp lệ.");
}

// 1. LẤY MÔN HỌC TỪ ĐỀ THI
$stmtExam = $pdo->prepare("SELECT subject_id FROM exams WHERE id = :id");
$stmtExam->execute(['id' => $exam_id]);
$examData = $stmtExam->fetch(PDO::FETCH_ASSOC);

if (!$examData) {
    die("Đề thi không tồn tại.");
}

$subject_id = (int)$examData['subject_id'];

// 2. LẤY TẤT CẢ CÂU HỎI THEO MÔN HỌC
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = :subject_id");
$stmtQ->execute(['subject_id' => $subject_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

$total_questions = count($questions);
$correct_count   = 0;

if ($total_questions === 0) {
    die("Không tìm thấy câu hỏi cho bài thi này.");
}

// 3. CHẤM ĐIỂM
foreach ($questions as $q) {
    $q_id = $q['id'];
    
    // Tự chọn cột chứa đáp án đúng linh hoạt
    $raw_correct = $q['correct_option'] ?? $q['correct_answer'] ?? $q['answer'] ?? $q['correct'] ?? '';
    $correct_opt = strtoupper(trim($raw_correct));
    
    $user_opt = isset($user_answers[$q_id]) ? strtoupper(trim($user_answers[$q_id])) : null;
    
    if ($user_opt !== null && $user_opt === $correct_opt) {
        $correct_count++;
    }
}

// Thang điểm 10
$score = round(($correct_count / $total_questions) * 10, 1);
$answers_json = json_encode($user_answers, JSON_UNESCAPED_UNICODE);

// 4. LƯU VÀO BẢNG results
try {
    $sql = "INSERT INTO results (user_id, exam_id, score, correct_answers, total_questions, time_taken, answers, created_at) 
            VALUES (:user_id, :exam_id, :score, :correct_answers, :total_questions, :time_taken, :answers, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'         => $user_id,
        'exam_id'         => $exam_id,
        'score'           => $score,
        'correct_answers' => $correct_count,
        'total_questions' => $total_questions,
        'time_taken'      => $time_taken,
        'answers'         => $answers_json
    ]);

    $result_id = $pdo->lastInsertId();

    // 5. CHUYỂN HƯỚNG TỚI TRANG KẾT QUẢ
    header("Location: ../student/result.php?id=" . $result_id);
    exit();

} catch (PDOException $e) {
    die("Lỗi SQL khi lưu kết quả bài thi: " . $e->getMessage());
}