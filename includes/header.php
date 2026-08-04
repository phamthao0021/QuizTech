<?php
// includes/header.php
$page_title = $page_title ?? 'QuizTech';
$current_page = basename($_SERVER['PHP_SELF']);
$user = currentUser();
$role = $_SESSION['role'] ?? 'guest';

// Xác định prefix dựa vào thư mục hiện tại
$in_sub = (
    strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/teacher/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/student/') !== false
);
$asset_prefix = $in_sub ? '../' : '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - QuizTech</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- App CSS -->
    <link href="<?= $asset_prefix ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= $asset_prefix ?>assets/css/admin.css" rel="stylesheet">
    <style>
        /* Page wrapper cho layout với sidebar */
        .page-wrapper {
            display: flex;
            min-height: 100vh;
        }
    </style>
</head>

<body>

    <?php
    // Flash Messages (chỉ render nếu không có sidebar để tránh trùng)
    $flash = getFlash();
    if ($flash && isset($flash['type']) && isset($flash['message'])): ?>
        <div id="flash-container" style="position: fixed; top: 16px; right: 16px; z-index: 9999; min-width: 280px; max-width: 400px;">
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow" role="alert">
                <?php
                $icons = ['success' => 'check-circle', 'danger' => 'x-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
                $icon = $icons[$flash['type']] ?? 'info-circle';
                ?>
                <i class="bi bi-<?= $icon ?> me-2"></i>
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <script>
            setTimeout(function() {
                var el = document.getElementById('flash-container');
                if (el) el.style.display = 'none';
            }, 4000);
        </script>
    <?php endif; ?>