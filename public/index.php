<?php
declare(strict_types=1);

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
if ($_ENV['APP_DEBUG'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Load routes
$router = require __DIR__ . '/../routes/web.php';

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove base path
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/') {
    $uri = str_replace($basePath, '', $uri);
}

$router->dispatch($method, $uri);