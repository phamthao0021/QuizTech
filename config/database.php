<?php
// config/database.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'pltprov1_jindo_plt_quiztech';
$username = 'pltprov1_jindo_plt_quiztech';
$password = 'Q%tY}~Wr&gXI6[0@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMOD E, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("❌ Lỗi kết nối database: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher']);
}

function getUserInfo($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

function getStats($pdo) {
    try {
        return [
            'subjects' => $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
            'exams' => $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
            'questions' => $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
            'attempts' => $pdo->query("SELECT COUNT(*) FROM exam_attempts")->fetchColumn(),
            'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        ];
    } catch(PDOException $e) {
        return ['subjects' => 0, 'exams' => 0, 'questions' => 0, 'attempts' => 0, 'users' => 0];
    }
}

function getRecentUsers($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>