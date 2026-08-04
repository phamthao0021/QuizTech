<?php
// includes/footer.php
$in_sub = (
    strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/teacher/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/student/') !== false
);
$asset_prefix = $in_sub ? '../' : '';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $asset_prefix ?>assets/js/app.js"></script>
</body>
</html>