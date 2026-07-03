<?php
// config/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /QuizTech/auth.php');
        exit();
    }
}

function getUserInfo($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getStats($pdo) {
    return [
        'subjects' => $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
        'exams' => $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
        'questions' => $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
        'attempts' => $pdo->query("SELECT COUNT(*) FROM exam_attempts")->fetchColumn()
    ];
}
?>