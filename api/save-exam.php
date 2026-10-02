<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['id'] ?? 0;
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

$attempt_id = (int)($data['attempt_id'] ?? 0);
$answers = $data['answers'] ?? [];

if ($attempt_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Lượt thi không hợp lệ']);
    exit();
}

$answers_json = json_encode($answers, JSON_UNESCAPED_UNICODE);

try {
    $stmt = $pdo->prepare("UPDATE exam_attempts 
                           SET answers_json = :answers_json 
                           WHERE id = :attempt_id AND student_id = :student_id AND status = 'doing'");
    
    $stmt->execute([
        'answers_json' => $answers_json,
        'attempt_id'   => $attempt_id,
        'student_id'   => $user_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Lưu tiến độ thành công']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>