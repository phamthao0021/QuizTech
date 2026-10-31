<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/leaderboard_service.php';
requireRole('student');
$page_title = 'Bảng xếp hạng';
// Đọc tham số GET bằng PHP filter API, tránh cảnh báo sai của Intelephense.
$getParams = filter_input_array(INPUT_GET) ?: [];
$lb = qt_lb_fetch($pdo, $getParams);
$opts = qt_lb_options($pdo);
// Lấy thứ hạng cá nhân trên toàn bộ người tham gia, kể cả khi đang tìm kiếm người khác.
$myId = (int)($_SESSION['user_id'] ?? 0);
$lb['current_user_id'] = $myId;
$lb['my_rank'] = null;
if ($myId > 0 && $lb['mode'] === 'exam' && $lb['exam'] > 0) {
    $rankInput = $getParams;
    $rankInput['q'] = '';
    $rankInput['page'] = 1;
    $rankData = qt_lb_fetch($pdo, $rankInput);
    // qt_lb_fetch trả về 20 hàng mỗi trang, vì vậy cần dò theo từng trang.
    for ($rankPage = 1; $rankPage <= $rankData['pages']; $rankPage++) {
        if ($rankPage > 1) {
            $rankInput['page'] = $rankPage;
            $rankData = qt_lb_fetch($pdo, $rankInput);
        }
        foreach ($rankData['rows'] as $candidate) {
            if ((int)$candidate['id'] === $myId) {
                $lb['my_rank'] = $candidate;
                break 2;
            }
        }
    }
}
$baseUrl = 'leaderboard.php';
include dirname(__DIR__) . '/includes/header_student.php';
qt_lb_render($lb, $opts, $baseUrl);
include dirname(__DIR__) . '/includes/footer.php';
