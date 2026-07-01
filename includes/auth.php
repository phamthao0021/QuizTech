<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function auth_login(string $email, string $password): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, student_code, fullname, email, password_hash, role FROM users WHERE email = ? OR student_code = ?');
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if (!$user['is_active']) {
        return false;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['student_code'] = $user['student_code'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    session_regenerate_id(true);
    return true;
}

function auth_register(string $student_code, string $fullname, string $email, string $password): array {
    $errors = [];

    if (strlen($student_code) < 5) $errors[] = 'MSSV phải có ít nhất 5 ký tự.';
    if (strlen($fullname) < 2) $errors[] = 'Họ tên phải có ít nhất 2 ký tự.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (strlen($password) < 8) $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    
    // Validate email domain for students
    if (!validate_email_domain($email)) {
        $errors[] = 'Email phải thuộc miền trường (tdc.edu.vn)';
    }

    if (empty($errors)) {
        $pdo = db();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR student_code = ?');
        $check->execute([$email, $student_code]);
        if ($check->fetch()) {
            $errors[] = 'Email hoặc MSSV đã được đăng ký.';
        }
    }

    if (!empty($errors)) return $errors;

    $pdo = db();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (student_code, fullname, email, password_hash, role) VALUES (?, ?, ?, ?, "student")');
    $stmt->execute([$student_code, $fullname, $email, $hash]);

    $userId = (int)$pdo->lastInsertId();

    $_SESSION['user_id'] = $userId;
    $_SESSION['student_code'] = $student_code;
    $_SESSION['fullname'] = $fullname;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'student';

    session_regenerate_id(true);
    return [];
}

function auth_logout(): void {
    $_SESSION = [];
    session_destroy();
}

function require_login(): void {
    if (!is_logged_in()) {
        flash_set('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

function require_teacher(): void {
    require_login();
    if (!is_teacher()) {
        flash_set('danger', 'Bạn không có quyền truy cập.');
        redirect('index.php');
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        flash_set('danger', 'Bạn không có quyền truy cập.');
        redirect('index.php');
    }
}

function current_user(): ?array {
    if (!is_logged_in()) return null;

    static $user = null;
    if ($user !== null) return $user;

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, student_code, fullname, email, role, avatar FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user;
}