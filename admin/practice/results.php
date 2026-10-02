<?php
$root = dirname(__DIR__, 2);

require_once $root . '/includes/config.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/practice_admin_service.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

pa_guard();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $a = $_POST['action'] ?? '';
    if ($a === 'delete' || $a === 'bulk_delete') {
        $ids = $a === 'delete' ? [(int)$_POST['id']] : pa_ids($_POST['ids'] ?? []);
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM practice_sessions WHERE id IN($in)")->execute($ids);
            setFlash('success', 'Đã xóa kết quả đã chọn.');
        }
        header('Location: results.php');
        exit;
    }
}
$q = trim($_GET['q'] ?? '');
$game = $_GET['game'] ?? '';
$status = $_GET['status'] ?? '';
$limit = pa_page_size();
$page = pa_page();
$where = [];
$par = [];
if ($q !== '') {
    $where[] = '(u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $x = pa_like($q);
    array_push($par, $x, $x, $x);
}
if (in_array($game, ['crossword', 'concept_match', 'quick_quiz'], true)) {
    $where[] = 's.game_type=?';
    $par[] = $game;
}
if (in_array($status, ['playing', 'completed', 'timeout', 'cancelled'], true)) {
    $where[] = 's.status=?';
    $par[] = $status;
}
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$sqlBase = " FROM practice_sessions s JOIN users u ON u.id=s.user_id $w";
$st = $pdo->prepare("SELECT COUNT(*)$sqlBase");
$st->execute($par);
$total = (int)$st->fetchColumn();
$st = $pdo->prepare("SELECT s.*,u.name,u.username,u.email $sqlBase ORDER BY s.id DESC LIMIT $limit OFFSET " . (($page - 1) * $limit));
$st->execute($par);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Kết quả Practice';
require $root . '/includes/header_admin.php'; ?>
<?php require __DIR__ . '/_practice_ui.php'; ?>
<div class="container-fluid py-4 px-3 px-md-4 pa-wrap">
    <div class="pa-head mb-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <div class="small text-white-50 fw-bold">QUIZTECH · PRACTICE ADMIN</div>
            <h3 class="fw-bold mb-1">Kết quả luyện tập</h3>
            <p class="mb-0 text-white-50">Theo dõi điểm, số câu đúng/sai, combo và thời gian.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2"><a class="btn btn-light" href="statistics.php"><i class="bi bi-bar-chart me-1"></i>Thống kê</a></div>
    </div>
    <?php if (function_exists('getFlash') && ($f = getFlash())): ?><div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <form class="pa-card p-3 mb-3 pa-toolbar" method="get"><input class="form-control pa-search" name="q" value="<?= e($q) ?>" placeholder="Tìm sinh viên..."><select class="form-select" name="game">
            <option value="">Tất cả game</option>
            <option value="crossword" <?= $game === 'crossword' ? 'selected' : '' ?>>Crossword</option>
            <option value="concept_match" <?= $game === 'concept_match' ? 'selected' : '' ?>>Nối cặp</option>
            <option value="quick_quiz" <?= $game === 'quick_quiz' ? 'selected' : '' ?>>Phản xạ nhanh</option>
        </select><select class="form-select" name="status">
            <option value="">Mọi trạng thái</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
            <option value="timeout" <?= $status === 'timeout' ? 'selected' : '' ?>>Hết giờ</option>
            <option value="playing" <?= $status === 'playing' ? 'selected' : '' ?>>Đang chơi</option>
        </select><button class="btn btn-pa">Lọc</button></form>
    <form id="bulkForm" method="post" class="pa-card overflow-hidden"><?php csrf_field(); ?><input name="action" value="bulk_delete" type="hidden">
        <div class="p-3 border-bottom d-flex justify-content-between"><b><?= number_format($total) ?> lượt</b><button class="btn btn-outline-danger btn-sm" onclick="return bulkDelete()">Xóa đã chọn</button></div>
        <div class="table-responsive">
            <table class="table pa-table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>Sinh viên</th>
                        <th>Game</th>
                        <th>Điểm</th>
                        <th>Đúng/Sai</th>
                        <th>Combo</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th>Ngày</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $r): ?><tr>
                            <td><input class="rowck form-check-input" type="checkbox" name="ids[]" value="<?= $r['id'] ?>"></td>
                            <td><b><?= e($r['name'] ?: $r['username']) ?></b><br><small><?= e($r['email']) ?></small></td>
                            <td><?= e($r['game_type']) ?></td>
                            <td><b><?= $r['score'] ?></b></td>
                            <td><span class="text-success"><?= $r['correct_count'] ?></span> / <span class="text-danger"><?= $r['wrong_count'] ?></span></td>
                            <td><?= $r['max_combo'] ?></td>
                            <td><?= $r['duration_seconds'] ?>s</td>
                            <td><?= e($r['status']) ?></td>
                            <td><?= e($r['completed_at'] ?: $r['started_at']) ?></td>
                            <td><button type="button" class="btn btn-sm btn-light text-danger" onclick="postDelete(<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr><?php endforeach; ?><?php if (!$rows): ?><tr>
                            <td colspan="10" class="pa-empty">Chưa có kết quả.</td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </form>
    <div class="d-flex justify-content-between mt-3"><small class="text-muted">Trang <?= $page ?> · <?= number_format($total) ?> lượt</small><?php pa_render_pagination($total, $limit, $page); ?></div>
</div>
<form id="oneForm" method="post" class="d-none"><?php csrf_field(); ?><input name="action" value="delete"><input name="id" id="oneId"></form>
<script>
    function toggleAll(x) {
        document.querySelectorAll('.rowck').forEach(c => c.checked = x.checked)
    }

    function bulkDelete() {
        if (!document.querySelector('.rowck:checked')) {
            alert('Hãy chọn kết quả.');
            return false
        }
        return confirm('Xóa các kết quả đã chọn?')
    }

    function postDelete(id) {
        if (confirm('Xóa kết quả này?')) {
            oneId.value = id;
            oneForm.submit()
        }
    }
</script><?php require $root . '/includes/footer.php'; ?>