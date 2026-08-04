<?php
echo "=== Kiểm tra Autoload ===\n\n";

// Kiểm tra file tồn tại
$files = [
    'app/Models/Exam.php',
    'app/Models/Subject.php',
    'app/Controllers/HomeController.php',
    'app/Core/Model.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo $file . ': ' . (file_exists($path) ? '✅ OK' : '❌ NOT FOUND') . "\n";
}

echo "\n=== Kiểm tra Class ===\n\n";

// Test autoload
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    echo "Looking for: $class → $file\n";
    if (file_exists($file)) {
        require $file;
        echo "✅ Loaded\n";
    } else {
        echo "❌ Not found\n";
    }
});

echo "\nTesting classes:\n";
echo "App\Models\Exam: " . (class_exists('App\\Models\\Exam') ? '✅ OK' : '❌ FAIL') . "\n";
echo "App\Models\Subject: " . (class_exists('App\\Models\\Subject') ? '✅ OK' : '❌ FAIL') . "\n";
echo "App\Controllers\HomeController: " . (class_exists('App\\Controllers\\HomeController') ? '✅ OK' : '❌ FAIL') . "\n";