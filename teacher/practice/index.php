<?php

/** Teacher Practice dashboard — all content visible, only own content editable. */
$root = dirname(__DIR__, 2);
require_once $root . '/includes/qt_teacher_practice.php';
$teacher = qt_tp_guard();
$tid = (int)$teacher['id'];
$page_title = 'Practice - Giảng viên';

$games = [
    'crosswords' => ['table' => 'practice_crosswords', 'title' => 'Daily Crossword', 'label' => 'Ô chữ CNTT', 'field' => 'title', 'icon' => 'bi-grid-3x3-gap-fill', 'desc' => 'Bộ ô chữ, gợi ý ngang/dọc và đáp án.'],
    'concepts' => ['table' => 'practice_concepts', 'title' => 'Nối cặp', 'label' => 'Nối cặp kiến thức', 'field' => 'term', 'icon' => 'bi-diagram-2-fill', 'desc' => 'Thuật ngữ, định nghĩa và chủ đề.'],
    'quick_quiz' => ['table' => 'practice_quiz_questions', 'title' => 'Phản xạ nhanh', 'label' => 'Câu hỏi phản xạ nhanh', 'field' => 'question', 'icon' => 'bi-lightning-charge-fill', 'desc' => 'Câu hỏi, đáp án và thời gian phản xạ.'],
];
$stats = ['all' => 0, 'own' => 0, 'admin' => 0, 'sessions' => 0, 'students' => 0, 'avg_score' => 0];
$gameStats = [];
$notice = '';
try {
    foreach ($games as $key => $game) {
        $sql = "SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN created_by = ? THEN 1 ELSE 0 END),0) AS mine, COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END),0) AS active_count FROM `{$game['table']}` WHERE is_deleted=0";
        $st = $pdo->prepare($sql);
        $st->execute([$tid]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $gameStats[$key] = ['total' => (int)($row['total'] ?? 0), 'mine' => (int)($row['mine'] ?? 0), 'active' => (int)($row['active_count'] ?? 0)];
        $stats['all'] += $gameStats[$key]['total'];
        $stats['own'] += $gameStats[$key]['mine'];
    }
    $stats['admin'] = $stats['all'] - $stats['own'];
    $st = $pdo->query("SELECT COUNT(*) AS sessions, COUNT(DISTINCT user_id) AS students, COALESCE(AVG(score),0) AS avg_score FROM practice_sessions WHERE status IN ('completed','timeout')");
    $r = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['sessions'] = (int)($r['sessions'] ?? 0);
    $stats['students'] = (int)($r['students'] ?? 0);
    $stats['avg_score'] = (float)($r['avg_score'] ?? 0);
} catch (Throwable $ex) {
    $notice = 'Không thể tải đầy đủ thống kê. Hãy kiểm tra migration Practice và các cột created_by, is_deleted.';
}

$gameFilter = (string)($_GET['game'] ?? 'crosswords');
if (!isset($games[$gameFilter])) $gameFilter = 'crosswords';
$ownerFilter = (string)($_GET['owner'] ?? 'all');
if (!in_array($ownerFilter, ['all', 'mine', 'other'], true)) $ownerFilter = 'all';
$statusFilter = (string)($_GET['status'] ?? 'all');
if (!in_array($statusFilter, ['all', 'active', 'hidden'], true)) $statusFilter = 'all';
$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
if (!in_array($perPage, [5, 10, 20, 50], true)) $perPage = 10;
$sort = (string)($_GET['sort'] ?? 'newest');
if (!in_array($sort, ['newest', 'oldest', 'az'], true)) $sort = 'newest';
$total = 0;
$rows = [];
$g = $games[$gameFilter];
$orderSql = $sort === 'oldest' ? 'p.id ASC' : ($sort === 'az' ? "p.`{$g['field']}` ASC, p.id DESC" : 'p.id DESC');
$where = ['p.is_deleted=0'];
$params = [];
if ($ownerFilter === 'mine') {
    $where[] = 'p.created_by=?';
    $params[] = $tid;
}
if ($ownerFilter === 'other') {
    $where[] = '(p.created_by<>? OR p.created_by IS NULL)';
    $params[] = $tid;
}
if ($statusFilter !== 'all') {
    $where[] = 'p.is_active=?';
    $params[] = $statusFilter === 'active' ? 1 : 0;
}
if ($q !== '') {
    $where[] = "p.`{$g['field']}` LIKE ?";
    $params[] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
}
$whereSql = implode(' AND ', $where);
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM `{$g['table']}` p WHERE $whereSql");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $st = $pdo->prepare("SELECT p.id,p.`{$g['field']}` AS item_title,p.created_by,p.is_active,u.name AS creator_name,u.role AS creator_role FROM `{$g['table']}` p LEFT JOIN users u ON u.id=p.created_by WHERE $whereSql ORDER BY $orderSql LIMIT ? OFFSET ?");
    $i = 1;
    foreach ($params as $value) {
        $st->bindValue($i++, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->bindValue($i++, $perPage, PDO::PARAM_INT);
    $st->bindValue($i, ($page - 1) * $perPage, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $ex) {
    $notice = 'Không thể tải danh sách Practice. Kiểm tra cấu trúc bảng và migration Teacher Practice.';
    $pages = 1;
}

$recent = [];
try {
    $st = $pdo->query("SELECT s.id,s.game_type,s.score,s.correct_count,s.total_count,s.duration_seconds,s.completed_at,s.status,u.name AS student_name,u.email AS student_email FROM practice_sessions s LEFT JOIN users u ON u.id=s.user_id WHERE s.status IN ('completed','timeout') ORDER BY s.id DESC LIMIT 8");
    $recent = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $ex) {
    if (!$notice) $notice = 'Không thể tải lịch sử kết quả luyện tập.';
}
function qt_tp_index_url(array $changes = []): string
{
    $args = array_merge(['game' => $_GET['game'] ?? 'crosswords', 'owner' => $_GET['owner'] ?? 'all', 'status' => $_GET['status'] ?? 'all', 'q' => $_GET['q'] ?? '', 'sort' => $_GET['sort'] ?? 'newest', 'per_page' => $_GET['per_page'] ?? 10], $changes);
    return 'index.php?' . http_build_query($args);
}
include $root . '/includes/header_teacher.php';
?>
<style>
    .tp-index {
        --tp-purple: #5b4ce6;
        --tp-ink: #172033
    }

    .tp-index .tp-hero {
        background: linear-gradient(135deg, #4338ca, #6d5dfc);
        color: #fff;
        border-radius: 22px;
        padding: 24px
    }

    .tp-index .tp-card {
        background: #fff;
        border: 1px solid #e8eaf1;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(17, 24, 39, .04)
    }

    .tp-index .tp-stat {
        padding: 18px;
        height: 100%
    }

    .tp-index .tp-stat small {
        color: #64748b;
        font-weight: 600
    }

    .tp-index .tp-stat strong {
        display: block;
        font-size: 1.55rem;
        color: var(--tp-ink)
    }

    .tp-index .tp-game {
        padding: 20px;
        height: 100%;
        transition: transform .2s
    }

    .tp-index .tp-game:hover {
        transform: translateY(-3px)
    }

    .tp-index .tp-icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #eeecff;
        color: var(--tp-purple);
        font-size: 1.25rem
    }

    .tp-index .table>:not(caption)>*>* {
        padding: .85rem 1rem
    }

    .tp-index .tp-break {
        overflow-wrap: anywhere;
        max-width: 400px
    }

    .tp-index .tp-filter-form {background:#fbfaff;border-bottom:1px solid #eee8fb}
    .tp-index .tp-filter-form .form-label {font-weight:750;color:#57417c}
    .tp-index .tp-filter-form .form-control,.tp-index .tp-filter-form .form-select {border-radius:11px;border-color:#e5daf7;min-height:43px}
    .tp-index .tp-page-nav {display:flex;align-items:center;gap:5px;flex-wrap:wrap}
    .tp-index .tp-page-nav .btn {min-width:35px;border-radius:9px}
    .tp-index .tp-mobile {
        display: none
    }

    @media(max-width:767.98px) {
        .tp-index .tp-hero {
            padding: 18px;
            border-radius: 16px
        }

        .tp-index .tp-desktop {
            display: none
        }

        .tp-index .tp-filter-form {background:#fbfaff;border-bottom:1px solid #eee8fb}
    .tp-index .tp-filter-form .form-label {font-weight:750;color:#57417c}
    .tp-index .tp-filter-form .form-control,.tp-index .tp-filter-form .form-select {border-radius:11px;border-color:#e5daf7;min-height:43px}
    .tp-index .tp-page-nav {display:flex;align-items:center;gap:5px;flex-wrap:wrap}
    .tp-index .tp-page-nav .btn {min-width:35px;border-radius:9px}
    .tp-index .tp-mobile {
            display: block
        }

        .tp-index .tp-page-nav {justify-content:center;width:100%}
        .tp-index .tp-stat strong {
            font-size: 1.25rem
        }
    }
</style>
<div class="container-fluid py-4 tp-index">
    <div class="tp-hero mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div><small class="text-white-50 fw-bold">QUIZTECH TEACHER</small>
            <h2 class="fw-bold mb-1"><i class="bi bi-controller me-2"></i>Quản lý Practice</h2>
            <p class="mb-0 text-white-50">Xem toàn bộ nội dung hệ thống, quản lý nội dung của bạn và theo dõi kết quả sinh viên.</p>
        </div>
        <a href="../dashboard.php" class="btn btn-light btn-sm align-self-start"><i class="bi bi-speedometer2 me-1"></i>Dashboard Teacher</a>
    </div>
    <?php if ($notice): ?><div class="alert alert-warning" role="alert"><?= e($notice) ?></div><?php endif; ?>
    <div class="row g-3 mb-4">
        <?php foreach ([['Tổng nội dung', $stats['all']], ['Nội dung của tôi', $stats['own']], ['Nội dung khác', $stats['admin']], ['Lượt luyện tập', $stats['sessions']], ['Sinh viên', $stats['students']], ['Điểm TB', number_format($stats['avg_score'], 1)]] as $item): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="tp-card tp-stat"><small><?= e($item[0]) ?></small><strong><?= e((string)$item[1]) ?></strong></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach ($games as $key => $game): ?><div class="col-md-4">
                <div class="tp-card tp-game">
                    <div class="tp-icon mb-3"><i class="bi <?= e($game['icon']) ?>"></i></div>
                    <h5 class="fw-bold"><?= e($game['title']) ?></h5>
                    <p class="text-muted small"><?= e($game['desc']) ?></p>
                    <div class="small text-muted mb-3">Tổng: <b><?= ($gameStats[$key]['total'] ?? 0) ?></b> · Của tôi: <b><?= ($gameStats[$key]['mine'] ?? 0) ?></b> · Công bố: <b><?= ($gameStats[$key]['active'] ?? 0) ?></b></div>
                    <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary btn-sm" href="<?= e(qt_tp_index_url(['game' => $key, 'page' => 1])) ?>">Xem tất cả</a><a class="btn btn-primary btn-sm" href="manage.php?game=<?= e($key) ?>">Quản lý của tôi</a></div>
                </div>
            </div><?php endforeach; ?>
    </div>
    <div class="tp-card mb-4 overflow-hidden">
        <div class="p-3 border-bottom">
            <h5 class="fw-bold mb-1">Ngân hàng Practice toàn hệ thống</h5>
            <div class="small text-muted">Nội dung của Admin và giảng viên khác chỉ được xem. Bạn chỉ có quyền chỉnh sửa nội dung của mình.</div>
        </div>
        <form method="get" class="row g-2 p-3 align-items-end tp-filter-form">
            <div class="col-12 col-md-3"><label class="form-label small" for="tpGame">Chế độ</label><select class="form-select" id="tpGame" name="game"><?php foreach ($games as $key => $game): ?><option value="<?= e($key) ?>" <?= $gameFilter === $key ? 'selected' : '' ?>><?= e($game['title']) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="tpOwner">Người tạo</label><select class="form-select" id="tpOwner" name="owner">
                    <option value="all">Tất cả</option>
                    <option value="mine" <?= $ownerFilter === 'mine' ? 'selected' : '' ?>>Của tôi</option>
                    <option value="other" <?= $ownerFilter === 'other' ? 'selected' : '' ?>>Người khác</option>
                </select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="tpStatus">Trạng thái</label><select class="form-select" id="tpStatus" name="status">
                    <option value="all">Tất cả</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Đã công bố</option>
                    <option value="hidden" <?= $statusFilter === 'hidden' ? 'selected' : '' ?>>Đã ẩn</option>
                </select></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="tpSearch">Tìm nội dung</label><input class="form-control" id="tpSearch" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Tên bộ ô chữ, thuật ngữ, câu hỏi..."></div>
            <div class="col-6 col-lg-2"><label class="form-label small" for="tpSort">Sắp xếp</label><select id="tpSort" name="sort" class="form-select"><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option><option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option><option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>Nội dung A–Z</option></select></div>
            <div class="col-6 col-lg-2"><label class="form-label small" for="tpPerPage">Số dòng/trang</label><select id="tpPerPage" name="per_page" class="form-select"><?php foreach ([5,10,20,50] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?> dòng</option><?php endforeach; ?></select></div>
            <div class="col-12 col-lg-auto d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Áp dụng</button><a class="btn btn-outline-secondary" href="index.php"><i class="bi bi-arrow-clockwise me-1"></i>Xóa lọc</a></div>
        </form>
        <div class="table-responsive tp-desktop">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nội dung</th>
                        <th>Người tạo</th>
                        <th>Trạng thái</th>
                        <th>Quyền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): $own = (int)$r['created_by'] === $tid; ?><tr>
                            <td>#<?= (int)$r['id'] ?></td>
                            <td class="tp-break fw-semibold"><?= e($r['item_title'] ?? '') ?></td>
                            <td><?= e($r['creator_name'] ?: 'Không xác định') ?> <small class="text-muted d-block"><?= e($r['creator_role'] ?? '') ?></small></td>
                            <td><span class="badge <?= $r['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $r['is_active'] ? 'Công bố' : 'Đã ẩn' ?></span></td>
                            <td><?php if ($own): ?><a class="btn btn-sm btn-outline-primary" href="manage.php?game=<?= e($gameFilter) ?>">Quản lý của tôi</a><?php else: ?><span class="badge bg-light text-secondary border"><i class="bi bi-eye me-1"></i>Chỉ xem</span><?php endif; ?></td>
                        </tr><?php endforeach; ?>
                    <?php if (!$rows): ?><tr>
                            <td colspan="5" class="text-center text-muted py-4">Không có dữ liệu phù hợp.</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="tp-mobile p-3"><?php foreach ($rows as $r): $own = (int)$r['created_by'] === $tid; ?><div class="border rounded-3 p-3 mb-2"><strong class="d-block tp-break"><?= e($r['item_title'] ?? '') ?></strong>
                    <div class="small text-muted mt-1">#<?= (int)$r['id'] ?> · <?= e($r['creator_name'] ?: 'Không xác định') ?> · <?= $r['is_active'] ? 'Công bố' : 'Đã ẩn' ?></div>
                    <div class="mt-2"><?php if ($own): ?><a class="btn btn-sm btn-outline-primary" href="manage.php?game=<?= e($gameFilter) ?>">Quản lý của tôi</a><?php else: ?><span class="badge bg-light text-secondary border">Chỉ xem</span><?php endif; ?></div>
                </div><?php endforeach; ?><?php if (!$rows): ?><p class="text-muted text-center">Không có dữ liệu phù hợp.</p><?php endif; ?></div>
        <div class="p-3 border-top d-flex justify-content-between align-items-center gap-2 flex-wrap"><small class="text-muted">Hiển thị <?= $total ? number_format(($page - 1) * $perPage + 1) : 0 ?>–<?= number_format(min($total, $page * $perPage)) ?> / <?= number_format($total) ?> bản ghi</small>
            <nav class="tp-page-nav" aria-label="Phân trang nội dung Practice"><?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(qt_tp_index_url(['page' => $page - 1])) ?>">‹ Trước</a><?php endif; ?><?php for ($n = max(1, $page - 2); $n <= min($pages, $page + 2); $n++): ?><a class="btn btn-sm <?= $n === $page ? 'btn-primary' : 'btn-outline-secondary' ?>" <?= $n === $page ? 'aria-current="page"' : '' ?> href="<?= e(qt_tp_index_url(['page' => $n])) ?>"><?= $n ?></a><?php endfor; ?><?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(qt_tp_index_url(['page' => $page + 1])) ?>">Sau ›</a><?php endif; ?></nav>
        </div>
    </div>
    <div class="tp-card overflow-hidden">
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-0">Kết quả luyện tập gần đây</h5><small class="text-muted">8 lượt hoàn thành hoặc hết giờ mới nhất · Chỉ xem, không xóa kết quả</small>
            </div><span class="badge bg-primary-subtle text-primary">Thống kê toàn hệ thống</span>
        </div>
        <div class="table-responsive tp-desktop">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sinh viên</th>
                        <th>Game</th>
                        <th>Điểm</th>
                        <th>Đúng / Tổng</th>
                        <th>Độ chính xác</th>
                        <th>Thời gian</th>
                        <th>Ngày</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): $totalCount = (int)$r['total_count']; ?><tr>
                            <td><b><?= e($r['student_name'] ?? 'Sinh viên') ?></b><br><small class="text-muted"><?= e($r['student_email'] ?? '') ?></small></td>
                            <td><?= e($r['game_type']) ?></td>
                            <td class="fw-bold"><?= e((string)$r['score']) ?></td>
                            <td><?= (int)$r['correct_count'] ?> / <?= $totalCount ?></td>
                            <td><?= $totalCount ? number_format((int)$r['correct_count'] * 100 / $totalCount, 1) : '0.0' ?>%</td>
                            <td><?= (int)$r['duration_seconds'] ?>s</td>
                            <td><?= e($r['completed_at'] ?? '') ?></td>
                        </tr><?php endforeach; ?>
                    <?php if (!$recent): ?><tr>
                            <td colspan="7" class="text-center text-muted py-4">Chưa có kết quả luyện tập.</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="tp-mobile p-3"><?php foreach ($recent as $r): ?><div class="border rounded-3 p-3 mb-2"><strong><?= e($r['student_name'] ?? 'Sinh viên') ?></strong>
                    <div class="small text-muted"><?= e($r['game_type']) ?> · <?= e((string)$r['score']) ?> điểm · <?= (int)$r['duration_seconds'] ?>s</div>
                </div><?php endforeach; ?></div>
    </div>
</div>
<?php include $root . '/includes/footer.php'; ?>