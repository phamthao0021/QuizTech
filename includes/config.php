<?php
// includes/config.php

$host = 'localhost';
$db   = 'pltprov1_jindo_plt_quiztech';
$user = 'pltprov1_jindo_plt_quiztech';
$pass = 'Q%tY}~Wr&gXI6[0@';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

$pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Kết nối cơ sở dữ liệu thất bại: ' . $e->getMessage());
}
// Require file chứa các hàm hệ thống
require_once __DIR__ . '/functions.php';