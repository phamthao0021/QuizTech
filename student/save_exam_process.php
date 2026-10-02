<?php
// save_exam_process.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

// Bắt ngoại lệ để luôn trả về JSON cho phía JS mở lại nút bấm
set_exception_handler(function ($e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    exit;
});

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Phiên đăng nhập đã hết hạn!']);
    exit;
}

$user = currentUser();
$user_id = (int)($user['id'] ?? $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

// Đọc dữ liệu Payload
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?: $_POST;

$title       = trim($inputData['title'] ?? '');
$subject_id  = intval($inputData['subject_id'] ?? 1);
$duration    = intval($inputData['duration'] ?? 45);
$pass_score  = floatval($inputData['pass_score'] ?? 5.0);
$description = trim($inputData['description'] ?? '');
$is_public   = isset($inputData['is_public']) ? intval($inputData['is_public']) : 1;
$questions   = $inputData['questions'] ?? [];

if (empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập tên đề thi!']);
    exit;
}

if ($subject_id <= 0) $subject_id = 1;

if (empty($questions) || !is_array($questions)) {
    echo json_encode(['status' => 'error', 'message' => 'Đề thi phải chứa ít nhất một câu hỏi hợp lệ!']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. CHỐNG SPAM ĐỀ THI TRÙNG BẰNG TÊN TRONG 15 GIÂY
    $stmtCheck = $pdo->prepare("SELECT id FROM exams WHERE title = ? AND created_by = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 SECOND)");
    $stmtCheck->execute([$title, $user_id]);
    if ($stmtCheck->fetch()) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Đề thi này đang được hệ thống lưu, vui lòng chờ giây lát!']);
        exit;
    }

    // 2. THÊM ĐỀ THI MỚI (EXAMS)
    $examCols = $pdo->query("SHOW COLUMNS FROM exams")->fetchAll(PDO::FETCH_COLUMN);
    $examFields = ['title', 'subject_id', 'duration', 'description', 'created_by'];
    $examParams = [$title, $subject_id, $duration, $description, $user_id];

    if (in_array('teacher_id', $examCols)) { $examFields[] = 'teacher_id'; $examParams[] = $user_id; }
    if (in_array('pass_score', $examCols)) { $examFields[] = 'pass_score'; $examParams[] = $pass_score; }
    if (in_array('is_public', $examCols))  { $examFields[] = 'is_public';  $examParams[] = $is_public; }
    if (in_array('status', $examCols))     { $examFields[] = 'status';     $examParams[] = 'published'; }
    if (in_array('created_at', $examCols)) { $examFields[] = 'created_at'; }

    $placeholders = array_map(fn($f) => $f === 'created_at' ? 'NOW()' : '?', $examFields);
    $sqlExam = "INSERT INTO exams (" . implode(', ', $examFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmtExam = $pdo->prepare($sqlExam);
    $stmtExam->execute($examParams);
    
    $exam_id = $pdo->lastInsertId();

    // 3. XỬ LÝ LƯU CÂU HỎI VÀ CHỐNG TRÙNG TUỆT ĐỐI
    $qCols = $pdo->query("SHOW COLUMNS FROM questions")->fetchAll(PDO::FETCH_COLUMN);
    $contentCol   = in_array('content', $qCols) ? 'content' : 'question_text';
    $hasExamId    = in_array('exam_id', $qCols);
    $hasSubjectId = in_array('subject_id', $qCols);
    $hasTeacherId = in_array('teacher_id', $qCols);
    $hasCreatedBy = in_array('created_by', $qCols);

    $hasExamQuestionsTable = (bool)$pdo->query("SHOW TABLES LIKE 'exam_questions'")->fetch();
    $eqCols = $hasExamQuestionsTable ? $pdo->query("SHOW COLUMNS FROM exam_questions")->fetchAll(PDO::FETCH_COLUMN) : [];

    // Chuẩn bị Query tìm câu hỏi đã có sẵn trong CSDL
    $stmtCheckDBQ = $pdo->prepare("SELECT id FROM questions WHERE `{$contentCol}` = ? LIMIT 1");

    // MẢNG NÀY DÙNG ĐỂ LƯU CÁC CÂU HỎI ĐÃ XỬ LÝ TRONG PHIÊN LƯU NÀY (TRÁNH TRÙNG 2 FILE IMPORT)
    $processedQuestionsInBatch = []; 

    $order = 1;
    $countInserted = 0;

    foreach ($questions as $q) {
        $q_text = trim($q['question_text'] ?? $q['content'] ?? '');
        $opt_a  = trim($q['option_a'] ?? '');
        $opt_b  = trim($q['option_b'] ?? '');
        $opt_c  = trim($q['option_c'] ?? '');
        $opt_d  = trim($q['option_d'] ?? '');
        $correct = strtoupper(trim($q['correct_answer'] ?? 'A'));

        if (empty($q_text)) continue;

        // Mã hóa khóa nhận diện duy nhất cho câu hỏi (Dựa vào nội dung câu hỏi)
        $qHash = md5(mb_strtolower(preg_replace('/\s+/', '', $q_text)));

        $question_id = 0;

        // BƯỚC A: Kiểm tra xem câu hỏi này có vừa được thêm ở dòng/file trước trong đợt lưu này không
        if (isset($processedQuestionsInBatch[$qHash])) {
            $question_id = $processedQuestionsInBatch[$qHash];
        } else {
            // BƯỚC B: Nếu chưa có trong đợt này, tra cứu trực tiếp trong CSDL
            $stmtCheckDBQ->execute([$q_text]);
            $existingQ = $stmtCheckDBQ->fetch();

            if ($existingQ) {
                $question_id = (int)$existingQ['id'];
            } else {
                // BƯỚC C: Nếu hoàn toàn mới -> Insert vào bảng questions
                $qFields = [$contentCol, 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'];
                $qParams = [$q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct];

                if ($hasExamId)    { $qFields[] = 'exam_id';    $qParams[] = $exam_id; }
                if ($hasSubjectId) { $qFields[] = 'subject_id'; $qParams[] = $subject_id; }
                if ($hasTeacherId) { $qFields[] = 'teacher_id'; $qParams[] = $user_id; }
                if ($hasCreatedBy) { $qFields[] = 'created_by'; $qParams[] = $user_id; }
                if (in_array('created_at', $qCols)) { $qFields[] = 'created_at'; }

                $qPlaceholders = array_map(fn($f) => $f === 'created_at' ? 'NOW()' : '?', $qFields);
                $sqlQ = "INSERT INTO questions (" . implode(', ', $qFields) . ") VALUES (" . implode(', ', $qPlaceholders) . ")";
                $stmtQ = $pdo->prepare($sqlQ);
                $stmtQ->execute($qParams);

                $question_id = (int)$pdo->lastInsertId();
            }

            // Ghi nhớ lại ID câu hỏi vào bộ nhớ đệm batch
            $processedQuestionsInBatch[$qHash] = $question_id;
        }

        // 4. GÁN CÂU HỎI VÀO ĐỀ THI (BẢNG TRUNG GIAN EXAM_QUESTIONS)
        if ($hasExamQuestionsTable && $question_id > 0) {
            // Tránh liên kết 1 câu hỏi trùng nhau nhiều lần vào cùng 1 đề thi
            $stmtCheckEQ = $pdo->prepare("SELECT 1 FROM exam_questions WHERE exam_id = ? AND question_id = ?");
            $stmtCheckEQ->execute([$exam_id, $question_id]);
            
            if (!$stmtCheckEQ->fetch()) {
                $eqFields = ['exam_id', 'question_id'];
                $eqParams = [$exam_id, $question_id];
                
                if (in_array('question_order', $eqCols)) {
                    $eqFields[] = 'question_order';
                    $eqParams[] = $order;
                }

                $sqlEQ = "INSERT INTO exam_questions (" . implode(', ', $eqFields) . ") VALUES (" . implode(',', array_fill(0, count($eqFields), '?')) . ")";
                $stmtEQ = $pdo->prepare($sqlEQ);
                $stmtEQ->execute($eqParams);
                $order++;
            }
        }

        $countInserted++;
    }

    $pdo->commit();

    echo json_encode([
        'status'   => 'success',
        'message'  => "Đã tạo đề thi thành công!",
        'redirect' => 'exams.php'
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    exit;
}