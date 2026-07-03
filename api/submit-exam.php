<?php
// api/submit-exam.php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$attempt_id = (int)$data['attempt_id'];
$answers = json_encode($data['answers']);
$score = (int)$data['score'];
$time_taken = (int)$data['time_taken'];

$stmt = $pdo->prepare("UPDATE exam_attempts 
                       SET answers = ?, score = ?, time_taken = ?, is_completed = 1, completed_at = NOW() 
                       WHERE id = ? AND user_id = ?");
$stmt->execute([$answers, $score, $time_taken, $attempt_id, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
?>