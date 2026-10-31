<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/leaderboard_service.php';
$page_title = 'Bảng xếp hạng';

// Lấy các tham số tìm kiếm, bộ lọc và phân trang.
$queryParams = $_GET ?? [];

// Lấy dữ liệu bảng xếp hạng.
$lb = qt_lb_fetch($pdo, $queryParams);

// Lấy danh sách tùy chọn bộ lọc.
$opts = qt_lb_options($pdo);
$baseUrl='leaderboard.php';
include __DIR__.'/includes/header_guest.php';
require_once __DIR__.'/includes/leaderboard_view.php';
qt_lb_render($lb, $opts, $baseUrl);
include __DIR__.'/includes/footer_guest.php';
