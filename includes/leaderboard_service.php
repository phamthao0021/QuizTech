<?php

/** Shared leaderboard queries. MySQL 5.7 / PHP 8.2. */
function qt_lb_options(PDO $pdo): array
{
    return [
        'subjects' => $pdo->query('SELECT id,name FROM subjects ORDER BY name')->fetchAll(PDO::FETCH_ASSOC),
        'exams' => $pdo->query('SELECT id,title,subject_id FROM exams ORDER BY title')->fetchAll(PDO::FETCH_ASSOC),
    ];
}
function qt_lb_fetch(PDO $pdo, array $input): array
{
    $mode = (string)($input['mode'] ?? 'exam');
    if (!in_array($mode, ['exam', 'crossword', 'concept_match', 'quick_quiz'], true)) $mode = 'exam';
    $search = mb_substr(trim((string)($input['q'] ?? '')), 0, 100);
    $period = (string)($input['period'] ?? 'all');
    if (!in_array($period, ['all', 'today', '7d', '30d'], true)) $period = 'all';
    $subject = max(0, (int)($input['subject'] ?? 0));
    $exam = max(0, (int)($input['exam'] ?? 0));
    $page = max(1, min(100000, (int)($input['page'] ?? 1)));
    $size = 20;
    // Xếp hạng bài thi phải gắn với một đề cụ thể, không gộp điểm các đề khác nhau.
    if ($mode === 'exam' && $exam === 0) {
        return ['rows'=>[], 'total'=>0, 'page'=>1, 'pages'=>1, 'mode'=>$mode,
            'period'=>$period, 'q'=>$search, 'subject'=>$subject, 'exam'=>$exam,
            'top'=>[], 'select_exam'=>true];
    }
    $params = [];
    $conditions = [];
    if ($search !== '') {
        $conditions[] = '(u.name LIKE ? OR u.student_code LIKE ?)';
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $periodSql = ['today' => 'CURDATE()', '7d' => 'DATE_SUB(NOW(),INTERVAL 7 DAY)', '30d' => 'DATE_SUB(NOW(),INTERVAL 30 DAY)'];
    if ($mode === 'exam') {
        $conditions[] = "a.status IN ('submitted','timeout')";
        if ($subject) {
            $conditions[] = 'e.subject_id=?';
            $params[] = $subject;
        }
        if ($exam) {
            $conditions[] = 'a.exam_id=?';
            $params[] = $exam;
        }
        if ($period !== 'all') {
            $conditions[] = 'a.submitted_at >= ' . $periodSql[$period];
        }
        $join = 'JOIN exam_attempts a ON a.student_id=u.id JOIN exams e ON e.id=a.exam_id';
        $metric = 'a.score';
        $duration = 'a.duration_seconds';
    } else {
        $conditions[] = "p.status IN ('completed','timeout')";
        $conditions[] = 'p.game_type=?';
        $params[] = $mode;
        if ($period !== 'all') {
            $conditions[] = 'p.completed_at >= ' . $periodSql[$period];
        }
        $join = 'JOIN practice_sessions p ON p.user_id=u.id';
        $metric = 'p.score';
        $duration = 'p.duration_seconds';
    }
    $where = "WHERE u.role='student'" . ($conditions ? ' AND ' . implode(' AND ', $conditions) : '');
    $from = "FROM users u $join $where";
    // Với từng đề: điểm cao nhất quyết định hạng, sau đó điểm trung bình, số lượt.
    $sort = $mode === 'exam'
        ? 'best_score DESC, avg_score DESC, play_count DESC, avg_duration ASC, u.id ASC'
        : 'avg_score DESC, best_score DESC, play_count DESC, u.id ASC';
    $sql = "SELECT u.id,u.name,u.student_code,COUNT(*) AS play_count,ROUND(AVG($metric),2) AS avg_score,MAX($metric) AS best_score,ROUND(AVG($duration),0) AS avg_duration $from GROUP BY u.id,u.name,u.student_code ORDER BY $sort";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($all);
    $pages = max(1, (int)ceil($total / $size));
    $page = min($page, $pages);
    $rows = array_slice($all, ($page - 1) * $size, $size);
    foreach ($rows as $i => &$r) $r['rank'] = ($page - 1) * $size + $i + 1;
    unset($r);
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'mode' => $mode, 'period' => $period, 'q' => $search, 'subject' => $subject, 'exam' => $exam, 'top' => array_slice($all, 0, 3), 'select_exam' => false];
}

/** Render shared leaderboard with explicit parameters for PHP language servers. */
function qt_lb_render(array $lb, array $opts, string $baseUrl): void
{
    $myRank = $lb['my_rank'] ?? null;
    $myId = (int)($lb['current_user_id'] ?? 0);
    $modeNames = ['exam' => 'Bài thi', 'crossword' => 'Ô chữ mỗi ngày', 'concept_match' => 'Nối cặp kiến thức', 'quick_quiz' => 'Phản xạ nhanh'];
    $h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $query = static function (array $override = []) use ($lb, $baseUrl) {
        return $baseUrl . '?' . http_build_query(array_merge(['mode' => $lb['mode'], 'q' => $lb['q'], 'period' => $lb['period'], 'subject' => $lb['subject'], 'exam' => $lb['exam']], $override));
    };
?>
    <style>
        /* Kế thừa font từ header_student.php để đồng bộ toàn bộ giao diện. */
        .qt-lb {
            max-width: 1180px;
            margin: auto;
            font-family: inherit;
            font-size: 1rem;
            color: #15223a;
        }
        .qt-lb :is(h1, h2, h3, h4, h5, h6, p, span, strong, label,
                   input, select, option, button, a, table, th, td, small,
                   .btn, .form-control, .form-select, .form-label) {
            font-family: inherit;
        }
        .qt-lb input::placeholder { font-family: inherit; }
        .qt-lb .form-control, .qt-lb .form-select, .qt-lb .btn {
            font-size: .95rem;
            letter-spacing: normal;
        }

        .qt-lb h1 {
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            font-weight: 800
        }

        .qt-lb .qt-card {
            background: white;
            border: 1px solid #dfe6f1;
            border-radius: 18px;
            padding: 22px
        }

        .qt-lb .qt-hero {
            background: linear-gradient(110deg, #263a8b, #7655e9);
            color: white;
            border-radius: 20px;
            padding: 28px
        }

        .qt-lb .form-label {
            font-weight: 650
        }

        .qt-lb .form-control,
        .qt-lb .form-select {
            min-height: 43px
        }

        .qt-lb .table th {
            font-weight: 700;
            white-space: nowrap
        }

        .qt-lb .table td {
            vertical-align: middle
        }

        .qt-lb .qt-my-row { background: #f2edff; }
        .qt-lb .qt-my-row td { background: #f2edff; }
        .qt-lb .qt-me-badge { color: #6d28d9; font-weight: 700; }
        .qt-lb .rank {
            font-size: 1.1rem;
            font-weight: 800;
            color: #5743c5
        }

        .qt-lb .podium {
            border: 1px solid #dfe6f1;
            border-radius: 14px;
            padding: 18px;
            height: 100%
        }

        .qt-lb .podium strong {
            font-size: 1.1rem
        }

        .qt-lb .score {
            font-weight: 800;
            color: #4f46e5
        }

        @media(max-width:600px) {
            .qt-lb .qt-card {
                padding: 15px
            }

            .qt-lb .qt-hero {
                padding: 20px
            }

            .qt-lb .table {
                font-size: .9rem
            }
        }
    </style>
    <div class="container py-4 qt-lb">
        <header class="qt-hero mb-4">
            <h1 class="mb-2"><i class="bi bi-trophy-fill me-2"></i>Bảng xếp hạng sinh viên</h1>
            <p class="mb-0">Tìm kiếm và so sánh kết quả theo bài thi hoặc từng chế độ luyện tập.</p>
        </header>
        <section class="qt-card mb-4" aria-label="Bộ lọc bảng xếp hạng">
            <form method="get" action="<?= $h($baseUrl) ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-4"><label class="form-label" for="lb-q">Tên sinh viên / MSSV</label><input id="lb-q" name="q" class="form-control" value="<?= $h($lb['q']) ?>" placeholder="Nhập tên hoặc MSSV"></div>
                    <div class="col-12 col-md-6 col-lg-4"><label class="form-label" for="lb-mode">Loại hoạt động</label><select id="lb-mode" name="mode" class="form-select" onchange="document.getElementById('lb-subject').disabled=this.value!=='exam';document.getElementById('lb-exam').disabled=this.value!=='exam'"><?php foreach ($modeNames as $key => $name): ?><option value="<?= $h($key) ?>" <?= $lb['mode'] === $key ? 'selected' : '' ?>><?= $h($name) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12 col-md-6 col-lg-4"><label class="form-label" for="lb-period">Thời gian</label><select id="lb-period" name="period" class="form-select"><?php foreach (['all' => 'Tất cả', 'today' => 'Hôm nay', '7d' => '7 ngày gần đây', '30d' => '30 ngày gần đây'] as $key => $name): ?><option value="<?= $key ?>" <?= $lb['period'] === $key ? 'selected' : '' ?>><?= $name ?></option><?php endforeach; ?></select></div>
                    <div class="col-12 col-md-6 col-lg-4"><label class="form-label" for="lb-subject">Môn học (bài thi)</label><select id="lb-subject" name="subject" class="form-select" <?= $lb['mode'] !== 'exam' ? 'disabled' : '' ?>>
                            <option value="0">Tất cả môn học</option><?php foreach ($opts['subjects'] as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $lb['subject'] == (int)$s['id'] ? 'selected' : '' ?>><?= $h($s['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-12 col-md-6 col-lg-5"><label class="form-label" for="lb-exam">Bộ đề (chọn để xếp hạng)</label><select id="lb-exam" name="exam" class="form-select" <?= $lb['mode'] !== 'exam' ? 'disabled' : '' ?>>
                            <option value="0">-- Chọn một đề thi --</option><?php foreach ($opts['exams'] as $ex): ?><option data-subject="<?= (int)$ex['subject_id'] ?>" value="<?= (int)$ex['id'] ?>" <?= $lb['exam'] == (int)$ex['id'] ? 'selected' : '' ?>><?= $h($ex['title']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-12 col-lg-3 d-flex align-items-end gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Áp dụng</button><a class="btn btn-outline-secondary" href="<?= $h($baseUrl) ?>">Xóa lọc</a></div>
                </div>
            </form>
        </section>
        <script>
        (function () {
            const subject = document.getElementById('lb-subject');
            const exam = document.getElementById('lb-exam');
            const mode = document.getElementById('lb-mode');
            if (!subject || !exam || !mode) return;
            function syncExams() {
                const selectedSubject = subject.value;
                for (const option of exam.options) {
                    if (!option.value) continue;
                    option.hidden = Boolean(selectedSubject !== '0' && option.dataset.subject !== selectedSubject);
                }
                if (exam.selectedOptions[0]?.hidden) exam.value = '0';
                const enabled = mode.value === 'exam';
                subject.disabled = !enabled;
                exam.disabled = !enabled;
            }
            subject.addEventListener('change', syncExams);
            mode.addEventListener('change', syncExams);
            syncExams();
        })();
        </script>
        <?php if (!empty($lb['select_exam'])): ?>
            <div class="alert alert-info" role="status">Vui lòng chọn <strong>một bộ đề</strong> rồi nhấn <strong>Áp dụng</strong> để xem bảng xếp hạng theo số lượt làm và điểm của riêng đề đó.</div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0"><?= $h($modeNames[$lb['mode']]) ?></h2><span class="text-muted"><?= (int)$lb['total'] ?> sinh viên</span>
        </div>
        <?php if ($lb['mode'] === 'exam' && (int)$lb['exam'] > 0): ?>
            <section class="qt-card mb-4" aria-label="Thứ hạng của tôi">
                <h3 class="h5 fw-bold mb-2"><i class="bi bi-person-circle me-2"></i>Thứ hạng của tôi</h3>
                <?php if ($myRank): ?>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <strong class="fs-3 text-primary">#<?= (int)$myRank['rank'] ?></strong>
                        <span><?= (int)$myRank['play_count'] ?> lượt làm</span>
                        <span>Điểm cao nhất: <strong><?= number_format((float)$myRank['best_score'], 2) ?></strong></span>
                        <span>Điểm trung bình: <strong><?= number_format((float)$myRank['avg_score'], 2) ?></strong></span>
                    </div>
                    <div class="text-muted small">Hạng được tính trên toàn bộ sinh viên có lượt làm hợp lệ của đề này, không phụ thuộc ô tìm kiếm.</div>
                <?php else: ?>
                    <p class="text-muted mb-0">Bạn chưa có lượt làm hợp lệ cho bộ đề này trong khoảng thời gian đã chọn.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <?php if ($lb['top']): ?><div class="row g-3 mb-4"><?php foreach ($lb['top'] as $i => $r): ?><div class="col-12 col-md-4">
                        <div class="podium">
                            <div class="fs-2 mb-2"><?= ['🥇', '🥈', '🥉'][$i] ?></div><strong><?= $h($r['name']) ?></strong>
                            <div class="text-muted"><?= $h($r['student_code'] ?? '') ?></div>
                            <div class="score fs-3 mt-2"><?= number_format((float)$r['avg_score'], 2) ?></div>
                            <div class="text-muted"><?= (int)$r['play_count'] ?> lượt · Cao nhất <?= number_format((float)$r['best_score'], 2) ?></div>
                        </div>
                    </div><?php endforeach; ?></div><?php endif; ?>
        <section class="qt-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Hạng</th>
                            <th>Sinh viên</th>
                            <th class="text-center">Số lượt</th>
                            <th class="text-end">Điểm TB</th>
                            <th class="text-end">Cao nhất</th>
                            <th class="text-end">TG TB</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lb['rows'] as $r): ?><tr class="<?= $myId === (int)$r['id'] ? 'qt-my-row' : '' ?>">
                                <td class="rank">#<?= (int)$r['rank'] ?></td>
                                <td><strong><?= $h($r['name']) ?></strong><?php if ($myId === (int)$r['id']): ?> <span class="qt-me-badge">(Bạn)</span><?php endif; ?>
                                    <div class="text-muted small"><?= $h($r['student_code'] ?? '') ?></div>
                                </td>
                                <td class="text-center"><?= (int)$r['play_count'] ?></td>
                                <td class="text-end score"><?= number_format((float)$r['avg_score'], 2) ?></td>
                                <td class="text-end"><?= number_format((float)$r['best_score'], 2) ?></td>
                                <td class="text-end"><?= (int)$r['avg_duration'] ?>s</td>
                            </tr><?php endforeach; ?>
                        <?php if (!$lb['rows']): ?><tr>
                                <td colspan="6" class="text-center text-muted py-5">Không có kết quả phù hợp. Hãy thay đổi bộ lọc.</td>
                            </tr><?php endif; ?></tbody>
                </table>
            </div>
            <?php if ($lb['pages'] > 1): ?><nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Phân trang"><span>Trang <?= (int)$lb['page'] ?>/<?= (int)$lb['pages'] ?></span>
                    <div class="d-flex gap-2"><?php if ($lb['page'] > 1): ?><a class="btn btn-outline-primary" href="<?= $h($query(['page' => $lb['page'] - 1])) ?>">Trước</a><?php endif; ?><?php if ($lb['page'] < $lb['pages']): ?><a class="btn btn-outline-primary" href="<?= $h($query(['page' => $lb['page'] + 1])) ?>">Sau</a><?php endif; ?></div>
                </nav><?php endif; ?>
        </section>
        <p class="text-muted mt-3">Mỗi đề thi có bảng xếp hạng riêng: ưu tiên điểm cao nhất, tiếp theo điểm trung bình và số lượt làm. Chỉ tính lượt đã nộp hoặc hết giờ; lượt chưa nộp không được tính.</p>
    </div>

<?php } // end qt_lb_render
