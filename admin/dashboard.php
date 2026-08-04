
<?php // admin/dashboard.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

$page_title = 'Admin Dashboard';
$stats = getStats();
$users = getUsers();
$exams = getExams();
include '../includes/header.php';
?>
<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?> 
    <div class="main-content"> <!-- Topbar -->
                <div class="topbar">
            <div>
                <h4>Admin Dashboard</h4>
                <p class="text-muted">Tổng quan chỉ số hệ thống</p>
            </div> <span class="badge bg-danger">Quyền Admin</span>
        </div> <!-- Thống kê chung -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card border p-3 rounded shadow-sm bg-white">
                    <div class="stat-number fs-2 fw-bold text-primary"><?= number_format($stats['users'] ?? 0) ?></div>
                    <div class="stat-label text-muted">Người dùng</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border p-3 rounded shadow-sm bg-white">
                    <div class="stat-number fs-2 fw-bold text-success"><?= number_format($stats['subjects'] ?? 0) ?></div>
                    <div class="stat-label text-muted">Môn học</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border p-3 rounded shadow-sm bg-white">
                    <div class="stat-number fs-2 fw-bold text-warning"><?= number_format($stats['questions'] ?? 0) ?></div>
                    <div class="stat-label text-muted">Câu hỏi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border p-3 rounded shadow-sm bg-white">
                    <div class="stat-number fs-2 fw-bold text-danger"><?= number_format($stats['exams'] ?? 0) ?></div>
                    <div class="stat-label text-muted">Đề thi</div>
                </div>
            </div>
        </div> <!-- Bảng danh sách xem nhanh -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Người dùng mới</h6> <a href="users.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                    </div>
                    <div class="card-body p-0"> <?php if (empty($users)): ?> <div class="text-center py-3 text-muted">Chưa có người dùng</div> <?php else: ?> <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Họ tên</th>
                                            <th>Email</th>
                                            <th>Vai trò</th>
                                        </tr>
                                    </thead>
                                    <tbody> <?php foreach (array_slice($users, 0, 5) as $idx => $u): ?> <tr>
                                                <td><?= $idx + 1 ?></td>
                                                <td><?= e($u['name']) ?></td>
                                                <td><?= e($u['email']) ?></td>
                                                <td> <span class="badge bg-<?= ($u['role'] ?? '') === 'admin' ? 'danger' : (($u['role'] ?? '') === 'teacher' ? 'warning' : 'primary') ?>"> <?= role_label($u['role'] ?? 'user') ?> </span> </td>
                                            </tr> <?php endforeach; ?> </tbody>
                                </table>
                            </div> <?php endif; ?> </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Đề thi gần đây</h6> <a href="exams.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                    </div>
                    <div class="card-body p-0"> <?php if (empty($exams)): ?> <div class="text-center py-3 text-muted">Chưa có đề thi</div> <?php else: ?> <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Tiêu đề</th>
                                            <th>Môn học</th>
                                            <th>Ngày tạo</th>
                                        </tr>
                                    </thead>
                                    <tbody> <?php foreach (array_slice($exams, 0, 5) as $idx => $e): ?> <tr>
                                                <td><?= $idx + 1 ?></td>
                                                <td><?= e($e['title']) ?></td>
                                                <td><?= e($e['subject_name'] ?? 'Môn học') ?></td>
                                                <td><?= format_date($e['created_at'] ?? date('Y-m-d')) ?></td>
                                            </tr> <?php endforeach; ?> </tbody>
                                </table>
                            </div> <?php endif; ?> </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>