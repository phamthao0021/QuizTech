<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-brain fa-3x text-primary"></i>
                    <h4 class="mt-2">QuizTech</h4>
                    <p class="text-muted">Đăng nhập để tiếp tục</p>
                </div>

                <?php if (isset($_SESSION['errors']['global'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_SESSION['errors']['global']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login">
                    <div class="mb-3">
                        <label class="form-label">Email hoặc MSSV</label>
                        <input type="text" name="email" class="form-control" 
                               value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>" 
                               required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </button>
                </form>

                <p class="text-center mt-3">
                    Chưa có tài khoản? <a href="/register">Đăng ký</a>
                </p>
                <hr>
                <div class="text-center text-muted small">
                    <strong>Demo:</strong><br>
                    admin@quiztech.com / Admin@123<br>
                    teacher@quiztech.com / Teacher@123<br>
                    student1@example.com / Student@123
                </div>
            </div>
        </div>
    </div>
</div>

<?php
unset($_SESSION['errors']);
unset($_SESSION['old']);
?>