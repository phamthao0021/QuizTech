<?php
// test.php - Đặt trong thư mục gốc QuizTech
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP đang chạy!<br>";

// Kiểm tra kết nối database
try {
    $pdo = new PDO("mysql:host=localhost;dbname=quiztech", "root", "");
    echo "Kết nối database thành công!";
} catch(PDOException $e) {
    echo "Lỗi database: " . $e->getMessage();
}
?>