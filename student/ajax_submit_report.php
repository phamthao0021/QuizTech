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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
    exit;
}

$expected=(string)($_SESSION['csrf_token']??'');
$actual=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');
if (!$expected || !hash_equals($expected,$actual)) {
    http_response_code(419);echo json_encode(['success'=>false,'message'=>'CSRF hết hạn. Hãy tải lại trang.']);exit;
}
$current=currentUser();
if (!$current || normalizeRole($current['role']??'')!=='student') {
    http_response_code(403);echo json_encode(['success'=>false,'message'=>'Không có quyền báo lỗi.']);exit;
}
$user_id=(int)$current['id'];
$question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$exam_id     = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
$report_type = trim($_POST['report_type'] ?? 'Khác');
$content     = trim($_POST['content'] ?? '');

if ($question_id<1 || strlen($content)<10 || strlen($content)>8000 || strlen($report_type)>320) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung chi tiết báo lỗi!']);
    exit;
}

try {
    $title = "Báo lỗi câu hỏi #" . $question_id . " (" . $report_type . ")";

    $stmt = $pdo->prepare("
        INSERT INTO practice_feedback (user_id, type, exam_id, question_id, title, content, status, created_at) 
        VALUES (?, 'question_report', ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$user_id, $exam_id ?: null, $question_id, $title, $content]);
    $feedbackId = (int)$pdo->lastInsertId();
    try {
        require_once dirname(__DIR__) . '/includes/qt_feedback_admin_notice.php';
        qt_feedback_alert_admin($pdo, (int)$user_id, $feedbackId, 'question_report', (string)$title);
    } catch (Throwable $noticeError) {
        error_log('Feedback saved but admin notification failed for feedback #' . $feedbackId . ': ' . $noticeError->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Đã gửi báo cáo câu hỏi thành công!']);
    exit;
} catch (Throwable $e) {
    error_log('Student report error: '.$e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Chưa lưu được báo cáo. Kiểm tra dữ liệu hoặc liên hệ quản trị viên.']);
    exit;
}