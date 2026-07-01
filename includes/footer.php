<?php
// Close main container from header
?>
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-brain"></i> <?= APP_NAME ?></h5>
                    <p class="text-muted">Hệ thống thi trắc nghiệm kiến thức CNTT</p>
                </div>
                <div class="col-md-4">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= base_url('exams.php') ?>" class="text-muted text-decoration-none">Đề thi</a></li>
                        <li><a href="<?= base_url('rooms.php') ?>" class="text-muted text-decoration-none">Phòng thi</a></li>
                        <li><a href="<?= base_url('leaderboard.php') ?>" class="text-muted text-decoration-none">Bảng xếp hạng</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <p class="text-muted">
                        <i class="fas fa-envelope"></i> support@quiztech.com<br>
                        <i class="fas fa-phone"></i> 0900 000 000
                    </p>
                </div>
            </div>
            <hr>
            <div class="text-center text-muted">
                &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.min.js"></script>
    <script src="<?= base_url('assets/js/script.js') ?>"></script>
    <?php if (isset($page_script)): ?>
        <script src="<?= base_url('assets/js/' . $page_script) ?>"></script>
    <?php endif; ?>
</body>
</html>