<?php
// room_join.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';
requireLogin();

// Lấy mã phòng từ POST hoặc GET
$room_code = trim($_POST['room_code'] ?? $_GET['code'] ?? '');
$room_code = strtoupper($room_code);

if (empty($room_code)) {
    setFlash('danger', 'Vui lòng nhập mã phòng thi.');
    redirect('student/rooms.php');
    exit;
}

// Lấy danh sách phòng thi
$rooms = getRooms() ?? [];
$target_room = null;

foreach ($rooms as $r) {
    if (strtoupper($r['code']) === $room_code) {
        $target_room = $r;
        break;
    }
}

// Kiểm tra phòng có tồn tại không
if (!$target_room) {
    setFlash('danger', 'Mã phòng thi không tồn tại hoặc đã đóng.');
    redirect('student/rooms.php');
    exit;
}

// Lưu thông tin phòng vào Session
$_SESSION['current_room_id']   = $target_room['id'];
$_SESSION['current_room_code'] = $target_room['code'];
$_SESSION['current_exam_id']   = $target_room['exam_id'];

// Chuyển hướng tới Phòng chờ (waiting-room.php)
redirect('student/waiting-room.php?room_id=' . $target_room['id']);
exit;