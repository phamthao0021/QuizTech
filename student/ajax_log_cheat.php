<?php
// student/ajax_log_cheat.php
$rootDir = dirname(__DIR__);
require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/functions.php';
require_once $rootDir . '/includes/auth.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
    exit;
}

$user = currentUser();
$exam_id = intval($_POST['exam_id'] ?? 0);

if ($exam_id > 0 && $user) {
    try {
        // Cập nhật tăng số lần vi phạm trong lượt làm bài
        $stmt = $pdo->prepare("
            UPDATE exam_submissions 
            SET cheat_count = cheat_count + 1 
            WHERE exam_id = ? AND student_id = ? AND status = 'in_progress'
        ");
        $stmt->execute([$exam_id, $user['id']]);
        
        echo json_encode(['status' => 'success', 'cheat_count_updated' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'invalid_params']);