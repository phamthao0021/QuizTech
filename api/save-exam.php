<?php
// api/save-exam.php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$attempt_id = (int)$data['attempt_id'];
$answers = json_encode($data['answers']);

$stmt = $pdo->prepare("UPDATE exam_attempts SET answers = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$answers, $attempt_id, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
?>