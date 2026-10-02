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

/* =========================================================
 * 1. THỐNG KÊ TỔNG QUAN
 * ========================================================= */

$summary = $pdo->query("
    SELECT
        COUNT(*) AS sessions,
        COUNT(DISTINCT user_id) AS students,

        COALESCE(
            AVG(score),
            0
        ) AS avg_score,

        COALESCE(
            AVG(
                CASE
                    WHEN total_count > 0
                    THEN correct_count * 100.0 / total_count
                END
            ),
            0
        ) AS accuracy,

        COALESCE(
            AVG(duration_seconds),
            0
        ) AS avg_time,

        COALESCE(
            MAX(score),
            0
        ) AS max_score,

        COALESCE(
            SUM(correct_count),
            0
        ) AS total_correct,

        COALESCE(
            SUM(wrong_count),
            0
        ) AS total_wrong

    FROM practice_sessions

    WHERE status IN (
        'completed',
        'timeout'
    )
")->fetch(PDO::FETCH_ASSOC);


/* =========================================================
 * 2. THỐNG KÊ THEO GAME
 * ========================================================= */

$games = $pdo->query("
    SELECT
        game_type,

        COUNT(*) AS sessions,

        COALESCE(
            AVG(score),
            0
        ) AS avg_score,

        COALESCE(
            AVG(duration_seconds),
            0
        ) AS avg_time,

        COALESCE(
            SUM(correct_count),
            0
        ) AS corrects,

        COALESCE(
            SUM(wrong_count),
            0
        ) AS wrongs

    FROM practice_sessions

    WHERE status IN (
        'completed',
        'timeout'
    )

    GROUP BY game_type

    ORDER BY sessions DESC
")->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
 * 3. TOP SINH VIÊN
 *
 * QUAN TRỌNG:
 * users của project dùng "name", không dùng "fullname".
 * ========================================================= */

$top = $pdo->query("
    SELECT
        s.user_id,

        u.name AS student_name,
        u.username,
        u.email,

        COUNT(*) AS sessions,

        COALESCE(
            MAX(s.score),
            0
        ) AS best_score,

        COALESCE(
            ROUND(AVG(s.score), 1),
            0
        ) AS avg_score,

        COALESCE(
            SUM(s.correct_count),
            0
        ) AS corrects,

        COALESCE(
            SUM(s.wrong_count),
            0
        ) AS wrongs

    FROM practice_sessions s

    INNER JOIN users u
        ON u.id = s.user_id

    WHERE s.status IN (
        'completed',
        'timeout'
    )

    GROUP BY
        s.user_id,
        u.name,
        u.username,
        u.email

    ORDER BY
        best_score DESC,
        avg_score DESC,
        sessions DESC

    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
 * 4. THỐNG KÊ 7 NGÀY GẦN NHẤT
 * ========================================================= */

$dailyRaw = $pdo->query("
    SELECT
        DATE(
            COALESCE(
                completed_at,
                started_at
            )
        ) AS practice_date,

        COUNT(*) AS sessions,

        COALESCE(
            ROUND(AVG(score), 1),
            0
        ) AS avg_score

    FROM practice_sessions

    WHERE
        status IN (
            'completed',
            'timeout'
        )

        AND COALESCE(
            completed_at,
            started_at
        ) >= DATE_SUB(
            CURDATE(),
            INTERVAL 6 DAY
        )

    GROUP BY
        DATE(
            COALESCE(
                completed_at,
                started_at
            )
        )

    ORDER BY practice_date ASC
")->fetchAll(PDO::FETCH_ASSOC);


/*
 * Tạo đủ 7 ngày kể cả ngày không có lượt luyện tập.
 */
$dailyMap = [];

foreach ($dailyRaw as $row) {
    $dailyMap[$row['practice_date']] = $row;
}

$dailyLabels   = [];
$dailySessions = [];
$dailyScores   = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date(
        'Y-m-d',
        strtotime("-{$i} days")
    );

    $dailyLabels[] = date(
        'd/m',
        strtotime($date)
    );

    $dailySessions[] = isset(
        $dailyMap[$date]
    )
        ? (int)$dailyMap[$date]['sessions']
        : 0;

    $dailyScores[] = isset(
        $dailyMap[$date]
    )
        ? (float)$dailyMap[$date]['avg_score']
        : 0;
}


/* =========================================================
 * 5. CHUẨN BỊ DATA BIỂU ĐỒ GAME
 * ========================================================= */

$gameLabels   = [];
$gameSessions = [];
$gameScores   = [];
$gameCorrect  = [];
$gameWrong    = [];

$gameNames = [
    'crossword'     => 'Ô chữ',
    'concept_match' => 'Nối cặp',
    'quick_quiz'    => 'Phản xạ nhanh'
];

foreach ($games as $gameRow) {

    $type = $gameRow['game_type'];

    $gameLabels[] =
        $gameNames[$type]
        ?? $type;

    $gameSessions[] =
        (int)$gameRow['sessions'];

    $gameScores[] =
        round(
            (float)$gameRow['avg_score'],
            1
        );

    $gameCorrect[] =
        (int)$gameRow['corrects'];

    $gameWrong[] =
        (int)$gameRow['wrongs'];
}


/* =========================================================
 * 6. PAGE
 * ========================================================= */

$page_title = 'Thống kê Practice';

require $root . '/includes/header_admin.php';

require __DIR__ . '/_practice_ui.php';

?>

<style>
    /* =====================================================
       PRACTICE STATISTICS
       ===================================================== */

    .practice-stat-card {
        background: #fff;
        border: 1px solid #edf0f5;
        border-radius: 18px;
        padding: 20px;
        height: 100%;
        box-shadow:
            0 4px 18px rgba(31, 41, 55, .05);
        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }

    .practice-stat-card:hover {
        transform: translateY(-3px);
        box-shadow:
            0 10px 28px rgba(31, 41, 55, .09);
    }

    .practice-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        flex-shrink: 0;
    }

    .practice-stat-purple {
        background: #f1edff;
        color: #6f42c1;
    }

    .practice-stat-blue {
        background: #eaf3ff;
        color: #0d6efd;
    }

    .practice-stat-green {
        background: #e9f9ef;
        color: #198754;
    }

    .practice-stat-orange {
        background: #fff4e6;
        color: #fd7e14;
    }

    .practice-stat-red {
        background: #fff0f1;
        color: #dc3545;
    }

    .practice-stat-label {
        color: #7b8494;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 3px;
    }

    .practice-stat-value {
        color: #212529;
        font-size: 25px;
        line-height: 1.15;
        font-weight: 800;
    }


    /* Chart card */

    .practice-chart-card {
        background: #fff;
        border: 1px solid #edf0f5;
        border-radius: 20px;
        box-shadow:
            0 4px 20px rgba(31, 41, 55, .05);
        overflow: hidden;
        height: 100%;
    }

    .practice-chart-header {
        padding: 18px 20px;
        border-bottom: 1px solid #edf0f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .practice-chart-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: #252a34;
    }

    .practice-chart-subtitle {
        margin-top: 3px;
        color: #8a93a3;
        font-size: 12px;
    }

    .practice-chart-body {
        padding: 20px;
        position: relative;
    }

    .practice-chart-box {
        position: relative;
        height: 310px;
    }

    .practice-chart-box-sm {
        height: 270px;
    }


    /* Table */

    .practice-ranking {
        background: #fff;
        border: 1px solid #edf0f5;
        border-radius: 20px;
        overflow: hidden;
        box-shadow:
            0 4px 20px rgba(31, 41, 55, .05);
    }

    .practice-ranking .table {
        margin-bottom: 0;
    }

    .practice-ranking thead th {
        background: #f8f9fc;
        border-bottom: 1px solid #edf0f5;
        color: #697386;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        padding: 14px 16px;
    }

    .practice-ranking tbody td {
        vertical-align: middle;
        padding: 14px 16px;
        border-color: #f0f2f5;
    }

    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0ebff;
        color: #6f42c1;
        font-weight: 800;
        flex-shrink: 0;
    }

    .rank-number {
        width: 31px;
        height: 31px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f2f3f7;
        font-weight: 800;
        font-size: 13px;
    }

    .rank-1 {
        background: #fff4cc;
        color: #b78103;
    }

    .rank-2 {
        background: #eef0f3;
        color: #6c757d;
    }

    .rank-3 {
        background: #fff0e5;
        color: #b85c1e;
    }

    .score-pill {
        display: inline-flex;
        min-width: 48px;
        justify-content: center;
        padding: 5px 9px;
        border-radius: 20px;
        background: #eef9f2;
        color: #198754;
        font-weight: 700;
        font-size: 12px;
    }

    @media (max-width: 767.98px) {

        .practice-chart-box {
            height: 250px;
        }

        .practice-chart-box-sm {
            height: 230px;
        }

        .practice-stat-value {
            font-size: 21px;
        }

        .practice-chart-header {
            align-items: flex-start;
        }
    }
</style>


<div class="container-fluid py-4 px-3 px-md-4 pa-wrap">

    <!-- ===================================================
         HEADER
         =================================================== -->

    <div
        class="pa-head mb-4 d-flex flex-column flex-lg-row
               align-items-lg-center justify-content-between gap-3"
    >

        <div>

            <div class="small text-white-50 fw-bold">
                QUIZTECH · PRACTICE ADMIN
            </div>

            <h3 class="fw-bold mb-1">
                Thống kê Practice
            </h3>

            <p class="mb-0 text-white-50">
                Theo dõi hiệu suất và hoạt động luyện tập
                của sinh viên.
            </p>

        </div>

        <div class="d-flex flex-column flex-sm-row gap-2">

            <a
                class="btn btn-light"
                href="results.php"
            >
                <i class="bi bi-list-check me-1"></i>
                Xem kết quả
            </a>

        </div>

    </div>


    <!-- ===================================================
         FLASH MESSAGE
         =================================================== -->

    <?php
    if (
        function_exists('getFlash')
        && ($flash = getFlash())
    ):
    ?>

        <div
            class="alert alert-<?= e($flash['type']) ?>
                   alert-dismissible fade show"
        >
            <?= e($flash['message']) ?>

            <button
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>
        </div>

    <?php endif; ?>


    <!-- ===================================================
         SUMMARY CARDS
         =================================================== -->

    <div class="row g-3 mb-4">

        <div class="col-6 col-xl">

            <div class="practice-stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="practice-stat-icon
                               practice-stat-purple"
                    >
                        <i class="bi bi-controller"></i>
                    </div>

                    <div>

                        <div class="practice-stat-label">
                            Lượt luyện
                        </div>

                        <div class="practice-stat-value">
                            <?= number_format(
                                (int)$summary['sessions']
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-xl">

            <div class="practice-stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="practice-stat-icon
                               practice-stat-blue"
                    >
                        <i class="bi bi-people"></i>
                    </div>

                    <div>

                        <div class="practice-stat-label">
                            Sinh viên
                        </div>

                        <div class="practice-stat-value">
                            <?= number_format(
                                (int)$summary['students']
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-xl">

            <div class="practice-stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="practice-stat-icon
                               practice-stat-green"
                    >
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>

                    <div>

                        <div class="practice-stat-label">
                            Điểm trung bình
                        </div>

                        <div class="practice-stat-value">
                            <?= number_format(
                                (float)$summary['avg_score'],
                                1
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-xl">

            <div class="practice-stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="practice-stat-icon
                               practice-stat-orange"
                    >
                        <i class="bi bi-bullseye"></i>
                    </div>

                    <div>

                        <div class="practice-stat-label">
                            Độ chính xác
                        </div>

                        <div class="practice-stat-value">
                            <?= number_format(
                                (float)$summary['accuracy'],
                                1
                            ) ?>%
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-xl">

            <div class="practice-stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="practice-stat-icon
                               practice-stat-red"
                    >
                        <i class="bi bi-stopwatch"></i>
                    </div>

                    <div>

                        <div class="practice-stat-label">
                            Thời gian TB
                        </div>

                        <div class="practice-stat-value">
                            <?= number_format(
                                (float)$summary['avg_time'],
                                0
                            ) ?>s
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ===================================================
         CHART ROW 1
         =================================================== -->

    <div class="row g-4 mb-4">

        <!-- 7 ngày -->

        <div class="col-xl-8">

            <div class="practice-chart-card">

                <div class="practice-chart-header">

                    <div>

                        <h5 class="practice-chart-title">
                            Hoạt động 7 ngày gần nhất
                        </h5>

                        <div class="practice-chart-subtitle">
                            Số lượt luyện tập theo từng ngày
                        </div>

                    </div>

                    <i
                        class="bi bi-graph-up fs-4
                               text-primary"
                    ></i>

                </div>

                <div class="practice-chart-body">

                    <div class="practice-chart-box">

                        <canvas id="dailyChart"></canvas>

                    </div>

                </div>

            </div>

        </div>


        <!-- Tỷ lệ game -->

        <div class="col-xl-4">

            <div class="practice-chart-card">

                <div class="practice-chart-header">

                    <div>

                        <h5 class="practice-chart-title">
                            Phân bố trò chơi
                        </h5>

                        <div class="practice-chart-subtitle">
                            Tỷ lệ lượt chơi theo game
                        </div>

                    </div>

                    <i
                        class="bi bi-pie-chart fs-4
                               text-primary"
                    ></i>

                </div>

                <div class="practice-chart-body">

                    <div
                        class="practice-chart-box
                               practice-chart-box-sm"
                    >

                        <canvas id="gamePieChart"></canvas>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ===================================================
         CHART ROW 2
         =================================================== -->

    <div class="row g-4 mb-4">

        <!-- Điểm trung bình -->

        <div class="col-lg-6">

            <div class="practice-chart-card">

                <div class="practice-chart-header">

                    <div>

                        <h5 class="practice-chart-title">
                            Điểm trung bình theo game
                        </h5>

                        <div class="practice-chart-subtitle">
                            So sánh hiệu suất giữa các trò chơi
                        </div>

                    </div>

                </div>

                <div class="practice-chart-body">

                    <div
                        class="practice-chart-box
                               practice-chart-box-sm"
                    >

                        <canvas id="scoreChart"></canvas>

                    </div>

                </div>

            </div>

        </div>


        <!-- Đúng sai -->

        <div class="col-lg-6">

            <div class="practice-chart-card">

                <div class="practice-chart-header">

                    <div>

                        <h5 class="practice-chart-title">
                            Câu đúng và câu sai
                        </h5>

                        <div class="practice-chart-subtitle">
                            Tổng số câu trả lời theo từng game
                        </div>

                    </div>

                </div>

                <div class="practice-chart-body">

                    <div
                        class="practice-chart-box
                               practice-chart-box-sm"
                    >

                        <canvas id="answerChart"></canvas>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ===================================================
         TOP STUDENTS
         =================================================== -->

    <div class="practice-ranking">

        <div class="practice-chart-header">

            <div>

                <h5 class="practice-chart-title">
                    Top sinh viên luyện tập
                </h5>

                <div class="practice-chart-subtitle">
                    Xếp theo điểm cao nhất và điểm trung bình
                </div>

            </div>

            <i
                class="bi bi-trophy fs-4"
                style="color:#f0a500"
            ></i>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th style="width:70px">
                            #
                        </th>

                        <th>
                            Sinh viên
                        </th>

                        <th class="text-center">
                            Lượt
                        </th>

                        <th class="text-center">
                            Cao nhất
                        </th>

                        <th class="text-center">
                            Trung bình
                        </th>

                        <th class="text-center">
                            Đúng
                        </th>

                        <th class="text-center">
                            Sai
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($top): ?>

                    <?php
                    foreach ($top as $index => $row):

                        $rank = $index + 1;

                        $studentName =
                            !empty($row['student_name'])
                                ? $row['student_name']
                                : $row['username'];

                        $initial =
                            function_exists('mb_substr')
                                ? mb_substr(
                                    $studentName,
                                    0,
                                    1,
                                    'UTF-8'
                                )
                                : substr(
                                    $studentName,
                                    0,
                                    1
                                );

                        $rankClass = '';

                        if ($rank === 1) {
                            $rankClass = 'rank-1';
                        } elseif ($rank === 2) {
                            $rankClass = 'rank-2';
                        } elseif ($rank === 3) {
                            $rankClass = 'rank-3';
                        }
                    ?>

                        <tr>

                            <td>

                                <span
                                    class="rank-number
                                           <?= $rankClass ?>"
                                >
                                    <?= $rank ?>
                                </span>

                            </td>


                            <td>

                                <div
                                    class="d-flex
                                           align-items-center
                                           gap-3"
                                >

                                    <div class="student-avatar">
                                        <?= e(
                                            strtoupper($initial)
                                        ) ?>
                                    </div>

                                    <div>

                                        <div class="fw-bold">
                                            <?= e(
                                                $studentName
                                            ) ?>
                                        </div>

                                        <small
                                            class="text-muted"
                                        >
                                            <?= e(
                                                $row['email']
                                                ?: $row['username']
                                            ) ?>
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <td class="text-center fw-semibold">
                                <?= number_format(
                                    (int)$row['sessions']
                                ) ?>
                            </td>


                            <td class="text-center">

                                <span class="score-pill">

                                    <?= number_format(
                                        (float)$row['best_score'],
                                        0
                                    ) ?>

                                </span>

                            </td>


                            <td class="text-center fw-semibold">

                                <?= number_format(
                                    (float)$row['avg_score'],
                                    1
                                ) ?>

                            </td>


                            <td
                                class="text-center
                                       text-success
                                       fw-semibold"
                            >
                                <?= number_format(
                                    (int)$row['corrects']
                                ) ?>
                            </td>


                            <td
                                class="text-center
                                       text-danger
                                       fw-semibold"
                            >
                                <?= number_format(
                                    (int)$row['wrongs']
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="pa-empty text-center py-5"
                        >

                            <i
                                class="bi bi-bar-chart
                                       fs-1
                                       d-block
                                       mb-2"
                            ></i>

                            Chưa có dữ liệu luyện tập.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- =======================================================
     CHART.JS
     ======================================================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
     * Data PHP -> Javascript
     */

    const dailyLabels =
        <?= json_encode(
            $dailyLabels,
            JSON_UNESCAPED_UNICODE
        ) ?>;

    const dailySessions =
        <?= json_encode($dailySessions) ?>;

    const dailyScores =
        <?= json_encode($dailyScores) ?>;

    const gameLabels =
        <?= json_encode(
            $gameLabels,
            JSON_UNESCAPED_UNICODE
        ) ?>;

    const gameSessions =
        <?= json_encode($gameSessions) ?>;

    const gameScores =
        <?= json_encode($gameScores) ?>;

    const gameCorrect =
        <?= json_encode($gameCorrect) ?>;

    const gameWrong =
        <?= json_encode($gameWrong) ?>;


    /*
     * Global Chart defaults
     */

    Chart.defaults.font.family =
        "'Inter', 'Segoe UI', Arial, sans-serif";

    Chart.defaults.color = '#737b8c';


    /*
     * 1. Daily activity
     */

    const dailyCanvas =
        document.getElementById('dailyChart');

    if (dailyCanvas) {

        new Chart(
            dailyCanvas,
            {
                type: 'line',

                data: {
                    labels: dailyLabels,

                    datasets: [
                        {
                            label: 'Lượt luyện',

                            data: dailySessions,

                            borderColor: '#6f42c1',

                            backgroundColor:
                                'rgba(111,66,193,.12)',

                            fill: true,

                            tension: .4,

                            borderWidth: 3,

                            pointRadius: 4,

                            pointHoverRadius: 6,

                            pointBackgroundColor:
                                '#6f42c1'
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    interaction: {
                        mode: 'index',
                        intersect: false
                    },

                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {
                            padding: 12
                        }
                    },

                    scales: {

                        y: {
                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color:
                                    'rgba(0,0,0,.05)'
                            }
                        },

                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            }
        );
    }


    /*
     * 2. Game distribution
     */

    const pieCanvas =
        document.getElementById('gamePieChart');

    if (pieCanvas) {

        new Chart(
            pieCanvas,
            {
                type: 'doughnut',

                data: {

                    labels: gameLabels,

                    datasets: [
                        {
                            data: gameSessions,

                            backgroundColor: [
                                '#6f42c1',
                                '#0d6efd',
                                '#20c997',
                                '#fd7e14',
                                '#dc3545'
                            ],

                            borderWidth: 0,

                            hoverOffset: 7
                        }
                    ]
                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    cutout: '67%',

                    plugins: {

                        legend: {

                            position: 'bottom',

                            labels: {
                                usePointStyle: true,
                                padding: 18
                            }
                        }
                    }
                }
            }
        );
    }


    /*
     * 3. Average score by game
     */

    const scoreCanvas =
        document.getElementById('scoreChart');

    if (scoreCanvas) {

        new Chart(
            scoreCanvas,
            {
                type: 'bar',

                data: {

                    labels: gameLabels,

                    datasets: [
                        {
                            label: 'Điểm trung bình',

                            data: gameScores,

                            backgroundColor:
                                'rgba(111,66,193,.82)',

                            borderRadius: 8,

                            borderSkipped: false
                        }
                    ]
                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {
                            display: false
                        }
                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            grid: {
                                color:
                                    'rgba(0,0,0,.05)'
                            }
                        },

                        x: {

                            grid: {
                                display: false
                            }
                        }
                    }
                }
            }
        );
    }


    /*
     * 4. Correct / Wrong
     */

    const answerCanvas =
        document.getElementById('answerChart');

    if (answerCanvas) {

        new Chart(
            answerCanvas,
            {
                type: 'bar',

                data: {

                    labels: gameLabels,

                    datasets: [

                        {
                            label: 'Đúng',

                            data: gameCorrect,

                            backgroundColor:
                                'rgba(25,135,84,.82)',

                            borderRadius: 6
                        },

                        {
                            label: 'Sai',

                            data: gameWrong,

                            backgroundColor:
                                'rgba(220,53,69,.78)',

                            borderRadius: 6
                        }

                    ]
                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {

                            labels: {
                                usePointStyle: true
                            }
                        }
                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color:
                                    'rgba(0,0,0,.05)'
                            }
                        },

                        x: {

                            grid: {
                                display: false
                            }
                        }
                    }
                }
            }
        );
    }

});
</script>

<?php require $root . '/includes/footer.php'; ?>