<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

auth_logout();
flash_set('info', 'Bạn đã đăng xuất thành công.');
redirect('index.php');