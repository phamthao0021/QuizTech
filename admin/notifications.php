<?php
require_once dirname(__DIR__).'/includes/auth.php';
requireAdmin();
$page_title='Thông báo hệ thống';
include dirname(__DIR__).'/includes/header_admin.php';
include dirname(__DIR__).'/includes/qt_notification_center.php';
include dirname(__DIR__).'/includes/footer.php';
