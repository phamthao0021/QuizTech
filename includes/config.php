<?php
declare(strict_types=1);

// Database Configuration
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'quiztech';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// App Configuration
const APP_NAME = 'QuizTech';
const APP_URL = 'http://localhost/QuizTech';
const UPLOAD_PATH = __DIR__ . '/../uploads/';
const UPLOAD_URL = '/QuizTech/uploads/';

// AI Configuration
const AI_ENABLED = true;
const OPENAI_API_KEY = ''; // Set your OpenAI API key here

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

function base_url(string $path = ''): string {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
    header('Location: ' . base_url($path));
    exit;
}