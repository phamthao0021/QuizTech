<?php
// includes/auth.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

function login($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return true;
    }
    return false;
}

function register($name, $email, $password) {
    global $pdo;
    
    // Check email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return 'Email đã được sử dụng.';
    }
    
    // Insert user
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
    $stmt->execute([$name, $email, $hash]);
    
    return true;
}

function logout() {
    $_SESSION = [];
    session_destroy();
}

function homeForRole($role) {
    $map = [
        'admin' => 'admin/dashboard.php',
        'teacher' => 'dashboard.php',
        'student' => 'dashboard.php'
    ];
    return $map[$role] ?? 'index.php';
}
// user_role() — dùng đúng tên hàm isLoggedIn() và currentUser()
function user_role() {
    if (isLoggedIn()) {
        $user = currentUser();
        if ($user) {
            $_SESSION['role'] = $user['role'];
            return $user['role'];
        }
    }
    return isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
}
// isAdmin() đã được định nghĩa trong functions.php — không định nghĩa lại ở đây
