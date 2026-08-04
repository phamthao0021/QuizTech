<?php
// room_join.php

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = strtoupper(trim($_POST['room_code'] ?? ''));
} else {
    $code = strtoupper(trim($_GET['code'] ?? ''));
}

if ($code === '') {
    setFlash('danger', 'Vui lòng nhập mã phòng.');
    redirect('rooms.php');
}

// Bảng đúng theo DB là exam_rooms, cột mã phòng là room_code
$stmt = $pdo->prepare("
    SELECT *
    FROM exam_rooms
    WHERE room_code = ?
    LIMIT 1
");
$stmt->execute([$code]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    setFlash('danger', 'Không tìm thấy phòng thi.');
    redirect('rooms.php');
}

$status = strtolower($room['status']);

// enum thật của exam_rooms.status: waiting, running, finished, cancelled
if (in_array($status, ['finished', 'cancelled'])) {
    setFlash('warning', 'Phòng thi đã kết thúc.');
    redirect('rooms.php');
}

// Giới hạn số lượng: cột đúng là max_students
if (!empty($room['max_students'])) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM room_members
        WHERE room_id = ?
    ");
    $stmt->execute([$room['id']]);
    $memberCount = (int)$stmt->fetchColumn();

    if ($memberCount >= (int)$room['max_students']) {
        setFlash('danger', 'Phòng thi đã đầy.');
        redirect('rooms.php');
    }
}

// (Tuỳ chọn) Kiểm tra mật khẩu phòng nếu giáo viên có đặt room_password
if (!empty($room['room_password'])) {
    $inputPass = trim($_POST['room_password'] ?? $_GET['room_password'] ?? '');
    if ($inputPass !== $room['room_password']) {
        setFlash('danger', 'Mật khẩu phòng thi không đúng.');
        redirect('rooms.php');
    }
}

// room_members dùng student_id, không phải user_id
$stmt = $pdo->prepare("
    SELECT id
    FROM room_members
    WHERE room_id = ?
      AND student_id = ?
    LIMIT 1
");
$stmt->execute([
    $room['id'],
    $_SESSION['user_id']
]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    $stmt = $pdo->prepare("
        INSERT INTO room_members
        (room_id, student_id, joined_at, is_online, is_submitted)
        VALUES (?, ?, NOW(), 1, 0)
    ");
    $stmt->execute([
        $room['id'],
        $_SESSION['user_id']
    ]);
} else {
    // Quay lại phòng: đánh dấu online lại
    $stmt = $pdo->prepare("
        UPDATE room_members
        SET is_online = 1, left_at = NULL
        WHERE room_id = ? AND student_id = ?
    ");
    $stmt->execute([$room['id'], $_SESSION['user_id']]);
}

setFlash('success', 'Đã tham gia phòng ' . e($code));

switch ($status) {
    case 'running':
        redirect('room_play.php?room_id=' . $room['id']);
        break;
    case 'waiting':
    default:
        redirect('room_wait.php?room_id=' . $room['id']);
        break;
}