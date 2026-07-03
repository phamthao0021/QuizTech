    <?php
// api/ocr.php - AI OCR với Gemini Vision
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

// For demo, return mock data
// In production, replace with actual Gemini API call
echo json_encode([
    'success' => true,
    'question' => 'Câu hỏi mẫu từ OCR: Đây là câu hỏi được nhận diện từ ảnh',
    'options' => ['Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D']
]);
?>