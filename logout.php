<?php
// logout.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

logout();
setFlash('info', 'Đã đăng xuất.');
redirect('index.php');