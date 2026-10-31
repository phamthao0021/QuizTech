<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
require_once dirname(__DIR__).'/includes/qt_notifications.php';
function qt_notice_json(int $status, array $data): void { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
try {
    $u=qt_notice_user();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') qt_notice_json(200,['ok'=>true]+qt_notice_list($pdo,$u));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='POST') qt_notice_json(405,['ok'=>false,'message'=>'Phương thức không hợp lệ.']);
    $expected=(string)($_SESSION['csrf_token']??'');
    $actual=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['csrf_token']??'');
    if ($expected==='' || !hash_equals($expected,$actual)) qt_notice_json(419,['ok'=>false,'message'=>'Phiên bảo mật hết hạn. Hãy tải lại trang.']);
    $action=(string)($_POST['action']??'');
    if ($action==='mark') qt_notice_mark($pdo,$u,(int)($_POST['id']??0));
    elseif ($action==='mark_all') qt_notice_mark($pdo,$u,null);
    else qt_notice_json(422,['ok'=>false,'message'=>'Hành động không hợp lệ.']);
    qt_notice_json(200,['ok'=>true]+qt_notice_list($pdo,$u));
} catch (InvalidArgumentException $e) {
    qt_notice_json(422,['ok'=>false,'message'=>$e->getMessage()]);
} catch (PDOException $e) {
    error_log('QuizTech notifications DB: '.$e->getMessage());
    qt_notice_json(503,['ok'=>false,'message'=>'Không truy cập được dữ liệu thông báo. Kiểm tra migration V39.']);
} catch (Throwable $e) {
    error_log('QuizTech notifications: '.$e->getMessage());
    qt_notice_json(401,['ok'=>false,'message'=>'Yêu cầu đăng nhập lại hoặc liên hệ quản trị viên.']);
}
