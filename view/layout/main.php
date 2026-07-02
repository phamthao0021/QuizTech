<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'QuizTech') ?> - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?= $_ENV['APP_URL'] ?? '' ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <main class="py-5">
        <div class="container">
            <?= $content ?? '' ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>