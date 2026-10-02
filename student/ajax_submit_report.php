<?php
// student/ajax_submit_report.php
ob_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Xóa sạch mọi ký tự lạ hoặc khoảng trắng vô tình in ra trước đó
ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
    exit;
}

$user_id     = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
$question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$exam_id     = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
$report_type = trim($_POST['report_type'] ?? 'Khác');
$content     = trim($_POST['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung chi tiết báo lỗi!']);
    exit;
}

try {
    // Tự động kiểm tra & tạo bảng nếu chưa tồn tại
    $pdo->exec("CREATE TABLE IF NOT EXISTS `practice_feedback` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'question_report',
        `exam_id` INT NULL,
        `question_id` INT NULL,
        `title` VARCHAR(255) NULL,
        `content` TEXT NOT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `admin_note` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $title = "Báo lỗi câu hỏi #" . $question_id . " (" . $report_type . ")";

    $stmt = $pdo->prepare("
        INSERT INTO practice_feedback (user_id, type, exam_id, question_id, title, content, status, created_at) 
        VALUES (?, 'question_report', ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$user_id, $exam_id ?: null, $question_id, $title, $content]);

    echo json_encode(['success' => true, 'message' => 'Đã gửi báo cáo câu hỏi thành công!']);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu báo cáo: ' . $e->getMessage()]);
    exit;
}