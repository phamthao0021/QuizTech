<?php
// includes/config.php
$host = 'localhost';
$dbname = 'pltprov1_jindo_plt_quiztech';
$username = 'pltprov1_jindo_plt_quiztech';
$password = 'Q%tY}~Wr&gXI6[0@';

// Kết nối PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// Cấu hình session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');