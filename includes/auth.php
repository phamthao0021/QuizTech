<?php
// includes/auth.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/permissions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (function_exists('ensure_users_role_schema')) {
    ensure_users_role_schema();
}

/**
 * Chuẩn hóa vai trò: admin | admin_test | teacher | student
 */
function normalizeRole($raw_role) {
    $role = strtolower(trim($raw_role ?? 'student'));
    if ($role === 'admin' || $role === 'super_admin' || $role === 'superadmin') {
        return 'admin';
    }
    if (in_array($role, ['admin_test', 'admintest', 'admin-test'], true)) {
        return 'admin_test';
    }
    if (in_array($role, ['teacher', 'giang_vien', 'giao_vien', 'giangvien', 'lecturer'], true)) {
        return 'teacher';
    }
    return 'student';
}


/**
 * Xử lý đăng nhập
 */
function login($email, $password) {
    global $pdo;

    $login = trim((string)$email);
    // Cho phép đăng nhập bằng email hoặc username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return false;
    }

    $status = strtolower(trim((string)($user['status'] ?? 'active')));
    if (in_array($status, ['blocked', 'inactive'], true) || !empty($user['deleted_at'])) {
        return 'locked';
    }

    $ok = password_verify($password, $user['password_hash'] ?? '')
        || (isset($user['password']) && md5($password) === $user['password']);

    if (!$ok) {
        return false;
    }

    session_regenerate_id(true);
    $role = normalizeRole($user['role'] ?? 'student');

    $_SESSION['user_id']      = $user['id'];
    $_SESSION['name']         = $user['name'];
    $_SESSION['user_name']    = $user['name'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['role']         = $role;
    $_SESSION['avatar']       = $user['avatar'] ?? '';
    $_SESSION['is_protected'] = (int)($user['is_protected'] ?? 0);

    $_SESSION['user'] = [
        'id'           => $user['id'],
        'name'         => $user['name'],
        'email'        => $user['email'],
        'username'     => $user['username'] ?? '',
        'role'         => $role,
        'avatar'       => $user['avatar'] ?? '',
        'is_protected' => (int)($user['is_protected'] ?? 0),
        'status'       => $user['status'] ?? 'active',
    ];

    try {
        $stmtUpdate = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmtUpdate->execute([$user['id']]);
    } catch (PDOException $e) {}

    return true;
}

/**
 * Xử lý đăng ký (chỉ student; không cho self-register admin)
 */
function register($name, $email, $password, $role = 'student') {
    global $pdo;

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return 'Email đã được sử dụng.';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    // Public register luôn là student
    $role = 'student';
    $username = strtolower(preg_replace('/[^a-z0-9._-]/i', '', explode('@', $email)[0] ?? 'user'));
    if ($username === '') {
        $username = 'user' . substr(md5($email), 0, 6);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, username, password_hash, role, status, is_protected, created_at) VALUES (?, ?, ?, ?, ?, 'active', 0, NOW())");
        $stmt->execute([$name, $email, $username, $hash, $role]);
        return true;
    } catch (PDOException $e) {
        return 'Đã có lỗi xảy ra khi tạo tài khoản: ' . $e->getMessage();
    }
}

/**
 * Xử lý đăng xuất
 */
function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Lấy trang chủ tương ứng theo Vai trò
 */
function homeForRole($role) {
    $role_clean = normalizeRole($role);

    if ($role_clean === 'admin' || $role_clean === 'admin_test') {
        return 'admin/dashboard.php';
    }
    if ($role_clean === 'teacher') {
        return 'teacher/dashboard.php';
    }
    return 'student/dashboard.php';
}

/**
 * Lấy vai trò hiện tại của người dùng
 */
function user_role() {
    if (isLoggedIn()) {
        $user = currentUser();
        if ($user && isset($user['role'])) {
            $_SESSION['role'] = normalizeRole($user['role']);
            return $_SESSION['role'];
        }
    }
    return $_SESSION['role'] ?? 'guest';
}

if (!function_exists('hasRole')) {
    function hasRole($role) {
        $user = currentUser();
        if (!$user || !isset($user['role'])) return false;

        return normalizeRole($user['role']) === normalizeRole($role);
    }
}