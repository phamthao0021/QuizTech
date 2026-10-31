<?php
// $prefix points from current PHP file to project root.
if (!isLoggedIn()) return;
$qtRoot=$prefix ?? '../';
?>
<div class="qt-notice" data-qt-api="<?= e($qtRoot.'api/qt_notifications.php') ?>" data-qt-root="<?= e($qtRoot) ?>" data-qt-csrf="<?= e(csrf_token()) ?>">
  <button type="button" class="qt-notice-trigger" aria-label="Thông báo" aria-expanded="false" aria-controls="qtNoticePanel">
    <i class="bi bi-bell"></i><span class="qt-notice-badge" hidden>0</span>
  </button>
  <section class="qt-notice-panel" id="qtNoticePanel" hidden aria-label="Thông báo của bạn">
    <div class="qt-notice-head"><div><strong>Thông báo</strong><small class="qt-notice-sub">Đang tải...</small></div>
      <button type="button" class="qt-notice-all">Đọc tất cả</button></div>
    <p class="qt-notice-error" role="alert" hidden></p>
    <div class="qt-notice-list"><p class="qt-notice-empty">Đang tải...</p></div>
  </section>
</div>
<link rel="stylesheet" href="<?= e($qtRoot) ?>assets/css/qt-notifications.css">
<script defer src="<?= e($qtRoot) ?>assets/js/qt-notifications.js"></script>
