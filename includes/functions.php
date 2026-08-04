<?php
// includes/functions.php
require_once __DIR__ . '/config.php';

// Escape HTML
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

// Flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Redirect
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Base URL
function base_url($path = '') {
    return '/' . ltrim($path, '/');
}

// Format date
function format_date($date) {
    if (empty($date)) return '--';
    return date('d/m/Y', strtotime($date));
}

function format_datetime($datetime) {
    if (empty($datetime)) return '--';
    return date('d/m/Y H:i', strtotime($datetime));
}

// Time ago
function time_ago($datetime) {
    if (empty($datetime)) return '--';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'vài giây trước';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    return format_date($datetime);
}

// Get difficulty badge
function difficulty_badge($difficulty) {
    $map = [
        'easy' => 'success',
        'medium' => 'warning',
        'hard' => 'danger',
        'mixed' => 'info'
    ];
    $class = $map[$difficulty] ?? 'secondary';
    return "<span class='badge bg-$class'>" . ucfirst($difficulty) . "</span>";
}

// Role label
function role_label($role) {
    $map = [
        'admin' => 'Quản trị viên',
        'teacher' => 'Giảng viên',
        'student' => 'Sinh viên'
    ];
    return $map[$role] ?? $role;
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if teacher
function isTeacher() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher']);
}

// Get current user
function currentUser() {
    if (!isLoggedIn()) return null;
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

/**
 * FIX: Bug cũ dùng rtrim($path, '/admin') — rtrim() coi tham số thứ 2 là
 * TẬP KÝ TỰ cần cắt (không phải chuỗi con!), nên mọi ký tự /,a,d,m,i,n
 * trong path đều bị cắt sạch, dẫn tới redirect sai hoặc rỗng.
 *
 * Vì requireAdmin() chỉ được gọi từ các file trong thư mục admin/ (1 cấp
 * dưới root), và requireTeacher() chỉ được gọi từ thư mục teacher/,
 * chỉ cần dùng redirect tương đối '../dashboard.php' là chính xác và
 * không phụ thuộc vào cấu trúc SCRIPT_NAME.
 */

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('danger', 'Bạn không có quyền truy cập.');
        redirect('../dashboard.php');
    }
}

// Require teacher
// Require teacher
function requireTeacher() {
    // 1. Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        setFlash('danger', 'Vui lòng đăng nhập để tiếp tục!');
        redirect('../login.php');
    }
    
    // 2. Ưu tiên lấy role từ $_SESSION['role'], nếu không có mới tìm trong $_SESSION['user']['role']
    $raw_role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
    $role = strtolower(trim($raw_role));

    // 3. Các role hợp lệ cho phép truy cập khu vực Teacher
    $allowed_roles = ['teacher', 'giang_vien', 'giangvien', 'admin', '1'];

    if (!in_array($role, $allowed_roles)) {
        setFlash('danger', 'Bạn không có quyền truy cập vào khu vực Giảng viên!');
        redirect('../index.php'); // Lùi 1 cấp về trang chủ root
    }
}

// Reset auto increment
function resetAutoIncrement($pdo, $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        return true;
    }
    return false;
}

function isGuest() {
    return !isLoggedIn();
}

function status_badge($status) {
    $map = [
        'waiting' => 'warning',
        'playing' => 'primary',
        'finished' => 'secondary',
        'active' => 'success',
        'inactive' => 'secondary',
        'pending' => 'warning',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    $class = $map[$status] ?? 'secondary';
    $label = ucfirst($status);
    return "<span class='badge bg-$class'>$label</span>";
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf()
{
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('CSRF token invalid');
    }
}