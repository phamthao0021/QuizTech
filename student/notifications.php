<?php
require_once dirname(__DIR__).'/includes/auth.php';
requireStudent();
$page_title='Thông báo hệ thống';
include dirname(__DIR__).'/includes/header_student.php';
include dirname(__DIR__).'/includes/qt_notification_center.php';
include dirname(__DIR__).'/includes/footer.php';
