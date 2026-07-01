<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pop(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $t): bool {
    return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}

function format_date(string $date): string {
    return date('d/m/Y', strtotime($date));
}

function format_datetime(string $datetime): string {
    return date('d/m/Y H:i', strtotime($datetime));
}

function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'vài giây trước';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    return format_date($datetime);
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_teacher(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher']);
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function get_status_badge(string $status): string {
    $badges = [
        'waiting' => 'warning',
        'playing' => 'primary',
        'finished' => 'success',
        'pending' => 'warning',
        'confirmed' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    $badge = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-$badge'>" . ucfirst($status) . "</span>";
}

function get_difficulty_badge(string $difficulty): string {
    $badges = [
        'easy' => 'success',
        'medium' => 'warning',
        'hard' => 'danger',
        'mixed' => 'info'
    ];
    $badge = $badges[$difficulty] ?? 'secondary';
    return "<span class='badge bg-$badge'>" . ucfirst($difficulty) . "</span>";
}

function generate_room_code(): string {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function validate_email_domain(string $email): bool {
    $allowed_domains = ['tdc.edu.vn', 'example.com'];
    $domain = substr(strrchr($email, "@"), 1);
    return in_array($domain, $allowed_domains);
}