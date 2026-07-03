<?php
// config/database.php - Phiên bản đã sửa lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Thông tin database
$host = 'localhost';
$dbname = 'quiztech1';
$username = 'root';
$password = ''; // Nếu XAMPP mặc định là rỗng



try {
    // Tạo kết nối PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("❌ Lỗi kết nối database: " . $e->getMessage());
}

// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Hàm lấy thông tin user
function getUserInfo($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Hàm thống kê
function getStats($pdo) {
    try {
        $stats = [
            'subjects' => $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
            'exams' => $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
            'questions' => $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
            'attempts' => $pdo->query("SELECT COUNT(*) FROM exam_attempts")->fetchColumn()
        ];
        return $stats;
    } catch(PDOException $e) {
        return ['subjects' => 0, 'exams' => 0, 'questions' => 0, 'attempts' => 0];
    }
}

// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
