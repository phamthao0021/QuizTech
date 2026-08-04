<?php
// api/log_cheating.php - API lưu vết vi phạm gian lận của sinh viên
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 1. KIỂM TRA PHƯƠNG THỨC REQUEST (Chỉ chấp nhận POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Phương thức Request không được hỗ trợ.'
    ]);
    exit();
}

// 2. KIỂM TRA ĐĂNG NHẬP (Chỉ người dùng đã đăng nhập mới được gọi API)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Bạn chưa đăng nhập hoặc phiên làm việc đã hết hạn.'
    ]);
    exit();
}

// 3. ĐỌC DỮ LIỆU JSON TỪ REQUEST BODY
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Dữ liệu gửi lên không hợp lệ.'
    ]);
    exit();
}

// 4. LẤY VÀ LÀM SẠCH DỮ LIỆU INPUT
$exam_id        = (int)($data['exam_id'] ?? 0);
$user_id        = (int)($_SESSION['user_id'] ?? $data['user_id'] ?? 0);
$event_type     = trim(filter_var($data['event_type'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
$violation_count = (int)($data['count'] ?? 1);

// Danh sách các loại vi phạm hợp lệ được chấp nhận
$allowed_events = ['TAB_SWITCH', 'DEVTOOLS_SHORTCUT', 'COPY_ATTEMPT', 'PASTE_ATTEMPT', 'CONTEXT_MENU'];

if ($exam_id <= 0 || $user_id <= 0 || empty($event_type)) {
    http_response_code(422);
    echo json_encode([
        'success' => false, 
        'message' => 'Thiếu thông tin bắt buộc (exam_id, user_id hoặc event_type).'
    ]);
    exit();
}

// Nếu event_type không nằm trong danh sách chuẩn, gắn nhãn UNKNOWN
if (!in_array($event_type, $allowed_events)) {
    $event_type = 'OTHER_VIOLATION';
}

// 5. LƯU NHẬT KÝ VI PHẠM VÀO DATABASE
try {
    // Kiểm tra sự tồn tại của đề thi
    $stmtCheck = $pdo->prepare("SELECT id FROM exams WHERE id = :exam_id");
    $stmtCheck->execute(['exam_id' => $exam_id]);
    
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Bài thi không tồn tại.'
        ]);
        exit();
    }

    // Insert nhật ký vi phạm
    $sql = "INSERT INTO cheating_logs (exam_id, user_id, event_type, violation_count, created_at) 
            VALUES (:exam_id, :user_id, :event_type, :violation_count, NOW())";
            
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'exam_id'         => $exam_id,
        'user_id'         => $user_id,
        'event_type'      => $event_type,
        'violation_count' => $violation_count
    ]);

    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Đã ghi nhận nhật ký vi phạm thành công.',
            'data'    => [
                'exam_id' => $exam_id,
                'event'   => $event_type,
                'count'   => $violation_count
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Không thể ghi dữ liệu vào CSDL.'
        ]);
    }

} catch (PDOException $e) {
    // Log lỗi server (ẩn thông tin chi tiết với client để đảm bảo bảo mật)
    error_log("Cheating Log API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi hệ thống khi lưu nhật ký.'
    ]);
}