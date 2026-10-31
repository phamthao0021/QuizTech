<?php
require_once dirname(__DIR__) . '/includes/auth.php';

requireTeacher();

$page_title = 'Thông báo hệ thống';

include dirname(__DIR__) . '/includes/header_teacher.php';
?>

<link
    rel="stylesheet"
    href="../assets/css/qt-teacher-notifications.css?v=20261025"
>

<?php
include dirname(__DIR__) . '/includes/qt_notification_center.php';

include dirname(__DIR__) . '/includes/footer.php';