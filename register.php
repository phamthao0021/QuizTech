<?php
// register.php - dùng chung bộ validation với login.php.
// GET: mở tab đăng ký. POST: chuyển xử lý sang login.php mà không làm mất dữ liệu POST.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: login.php?tab=register');
    exit;
}
require __DIR__ . '/login.php';
