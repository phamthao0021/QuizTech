<?php

/** Render shared leaderboard with explicit parameters for PHP language servers. */
function qt_lb_render(array $lb, array $opts, string $baseUrl): void
{
    $modeNames = ['exam' => 'Bài thi', 'crossword' => 'Ô chữ mỗi ngày', 'concept_match' => 'Nối cặp kiến thức', 'quick_quiz' => 'Phản xạ nhanh'];
    $h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $query = static function (array $override = []) use ($lb, $baseUrl) {
        return $baseUrl . '?' . http_build_query(array_merge(['mode' => $lb['mode'], 'q' => $lb['q'], 'period' => $lb['period'], 'subject' => $lb['subject'], 'exam' => $lb['exam']], $override));
    };
?>
    <style>
        .qt-lb {
            max-width: 1180px;
            margin: auto;
            font-size: 1rem;
            color: #15223a
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
                    <div class="col-12 col-md-6 col-lg-5"><label class="form-label" for="lb-exam">Bộ đề (bài thi)</label><select id="lb-exam" name="exam" class="form-select" <?= $lb['mode'] !== 'exam' ? 'disabled' : '' ?>>
                            <option value="0">Tất cả bộ đề</option><?php foreach ($opts['exams'] as $ex): ?><option value="<?= (int)$ex['id'] ?>" <?= $lb['exam'] == (int)$ex['id'] ? 'selected' : '' ?>><?= $h($ex['title']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-12 col-lg-3 d-flex align-items-end gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Áp dụng</button><a class="btn btn-outline-secondary" href="<?= $h($baseUrl) ?>">Xóa lọc</a></div>
                </div>
            </form>
        </section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0"><?= $h($modeNames[$lb['mode']]) ?></h2><span class="text-muted"><?= (int)$lb['total'] ?> sinh viên</span>
        </div>
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
                        <?php foreach ($lb['rows'] as $r): ?><tr>
                                <td class="rank">#<?= (int)$r['rank'] ?></td>
                                <td><strong><?= $h($r['name']) ?></strong>
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
        <p class="text-muted mt-3">Bài thi và mini-game có thang điểm khác nhau nên được xếp hạng riêng. Chỉ tính các lượt đã nộp hoặc hết giờ.</p>
    </div>

<?php } // end qt_lb_render
