<?php
$root = dirname(__DIR__);
require_once $root . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isLoggedIn()) {
    setFlash('warning', 'Vui lòng đăng nhập để luyện tập.');
    redirect('../login.php');
}
$role = strtolower(trim((string)($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? ''))));
if ($role !== 'student') {
    setFlash('danger', 'Practice dành cho sinh viên.');
    redirect('../index.php');
}
$page_title = 'Luyện tập tự do';
require_once $root . '/includes/header_student.php';
$csrf = csrf_token();
?>
<style>
    :root {
        --navy: #102542;
        --blue: #1677ff;
        --green: #148a55;
        --amber: #d88400;
        --bg: #f2f7fd;
        --line: #d8e2ef;
        --muted: #65778e
    }

    body {
        background: var(--bg)
    }

    .pv {
        max-width: 1120px;
        margin: auto;
        padding: 24px 16px 60px
    }

    .pv-head,
    .pv-panel,
    .game-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 20px;
        box-shadow: 0 4px 14px rgba(16, 37, 66, .04)
    }

    .pv-head {
        padding: 24px
    }

    .eyebrow {
        font: 700 11px 'IBM Plex Mono', monospace;
        letter-spacing: .12em;
        color: #3266a5;
        text-transform: uppercase
    }

    .pv h1 {
        font-size: 27px;
        font-weight: 800;
        color: var(--navy);
        letter-spacing: -.035em
    }

    .pv h2 {
        font-size: 20px;
        font-weight: 800;
        color: var(--navy)
    }

    .pv p {
        color: var(--muted)
    }

    .mode-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap
    }

    .mode-tabs button {
        border: 1px solid #bfd3e8;
        border-radius: 10px;
        background: #eef6ff;
        color: #1767bb;
        padding: 8px 13px;
        font-weight: 700;
        font-size: 13px
    }

    .mode-tabs .green {
        background: #edfaf3;
        color: #13764d;
        border-color: #bee7d1
    }

    .mode-tabs .amber {
        background: #fff7e8;
        color: #ad6800;
        border-color: #f0d49e
    }

    .game-card {
        padding: 22px;
        height: 100%;
        transition: .2s
    }

    .game-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(16, 37, 66, .08)
    }

    .game-card.blue {
        background: #edf6ff
    }

    .game-card.green {
        background: #effaf4
    }

    .game-card.amber {
        background: #fff8ea
    }

    .game-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #fff;
        background: var(--blue);
        font-size: 20px
    }

    .green .game-icon {
        background: var(--green)
    }

    .amber .game-icon {
        background: var(--amber)
    }

    .game-card h3 {
        font-size: 17px;
        font-weight: 800;
        color: var(--navy)
    }

    .game-card small {
        font: 700 10px 'IBM Plex Mono', monospace;
        letter-spacing: .08em
    }

    .play {
        border: 0;
        border-radius: 10px;
        color: #fff;
        background: var(--blue);
        font-weight: 700;
        padding: 9px 15px
    }

    .green .play {
        background: var(--green)
    }

    .amber .play {
        background: var(--amber)
    }

    .stat {
        padding: 14px;
        border-radius: 13px;
        background: #f7faff
    }

    .stat b {
        font-size: 18px;
        color: var(--navy);
        display: block
    }

    .stat span {
        font-size: 11px;
        color: var(--muted);
        font-weight: 700
    }

    .pv-panel {
        padding: 20px
    }

    .game-view {
        display: none
    }

    .game-view.on {
        display: block
    }

    .game-shell {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 20px;
        padding: 24px
    }

    .top-game {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap
    }

    .back {
        border: 0;
        background: var(--navy);
        color: #fff;
        border-radius: 9px;
        padding: 8px 13px;
        font-weight: 700;
        font-size: 12px
    }

    .scorebox {
        padding: 11px 14px;
        border-radius: 12px;
        background: #eef6ff;
        min-width: 80px
    }

    .scorebox.green {
        background: #effaf4
    }

    .scorebox.amber {
        background: #fff7e8
    }

    .scorebox span {
        font-size: 11px;
        color: var(--muted);
        display: block
    }

    .scorebox b {
        font: 800 16px 'IBM Plex Mono', monospace;
        color: var(--navy)
    }

    .cw-layout {
        display: grid;
        grid-template-columns: minmax(360px, 1.05fr) minmax(280px, .95fr);
        gap: 24px
    }

    .cw-grid {
        display: grid;
        gap: 3px;
        width: max-content
    }

    .cw-cellwrap {
        position: relative;
        width: 48px;
        height: 48px
    }

    .cw-cell {
        width: 48px;
        height: 48px;
        border: 1px solid #cad6e4;
        border-radius: 7px;
        background: #fff;
        text-align: center;
        text-transform: uppercase;
        font-weight: 800;
        font-size: 19px;
        color: var(--navy)
    }

    .cw-cell.active,
    .cw-cell.word-active {
        background: #ebe4ff;
        border-color: #9a7ff1
    }

    .cw-cell.word-hover {
        background: #f3efff;
        border-color: #c6b7f5
    }

    .cw-cell.cell-focus {
        background: #fff;
        border: 2px solid #7149e8;
        box-shadow: 0 0 0 4px rgba(113, 73, 232, .13)
    }

    .cw-cell.good {
        background: #dcfce7 !important;
        border-color: #22a35a !important;
        color: #12663e !important;
        box-shadow: inset 0 0 0 1px rgba(34, 163, 90, .12)
    }

    .cw-cell.bad {
        background: #fee2e2 !important;
        border-color: #ef4444 !important;
        color: #b91c1c !important
    }

    .cw-cell:focus {
        outline: 3px solid rgba(121, 86, 232, .18)
    }

    .cw-num {
        position: absolute;
        z-index: 2;
        left: 4px;
        top: 2px;
        font-size: 9px;
        color: #596b82;
        pointer-events: none
    }

    .cw-block {
        width: 48px;
        height: 48px;
        background: transparent
    }

    .clue-group h4 {
        font-size: 13px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #63748a
    }

    .clue {
        display: block;
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        padding: 8px 9px;
        border-radius: 8px;
        font-size: 13px;
        color: #243c59
    }

    .clue:hover,
    .clue.hovered {
        background: #f4f0ff;
        color: #5937c8
    }

    .clue.sel {
        background: #ede7ff;
        color: #5937c8;
        box-shadow: inset 3px 0 0 #7149e8
    }

    .solve {
        background: #7650e8;
        color: #fff;
        border: 0;
        border-radius: 10px;
        padding: 10px 17px;
        font-weight: 800
    }

    .match-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px
    }

    .match-col {
        background: #fff;
        border-radius: 16px;
        padding: 16px;
        border: 1px solid #e2e8f0
    }

    .match-item {
        width: 100%;
        border: 1px solid #cbd8e7;
        background: #fff;
        border-radius: 10px;
        padding: 13px;
        text-align: left;
        margin: 5px 0;
        color: #24405f;
        font-weight: 600;
        font-size: 13px
    }

    .match-item:hover {
        border-color: #62a987
    }

    .match-item.sel {
        outline: 3px solid rgba(20, 138, 85, .15);
        border-color: var(--green)
    }

    .match-item.ok {
        background: #e8f8ef;
        border-color: #39a975;
        pointer-events: none
    }

    .match-item.no {
        background: #fff0f0;
        border-color: #e45151;
        animation: shake .25s
    }

    .progress {
        height: 7px;
        background: #e8eef5
    }

    .progress-bar {
        background: var(--green)
    }

    .quiz-card {
        padding: 4px
    }

    .timer-ring {
        width: 62px;
        height: 62px;
        border: 6px solid var(--blue);
        border-radius: 50%;
        display: grid;
        place-items: center;
        font: 800 15px 'IBM Plex Mono', monospace;
        color: var(--navy)
    }

    .q-option {
        display: block;
        width: 100%;
        border: 1px solid #cbd8e7;
        background: #fff;
        border-radius: 10px;
        padding: 13px 15px;
        text-align: left;
        margin: 8px 0;
        color: #203a57;
        font-weight: 600
    }

    .q-option:hover {
        border-color: #72aaf0;
        background: #f7fbff
    }

    .q-option.ok {
        background: #e8f8ef;
        border-color: #27a467
    }

    .q-option.no {
        background: #fff0f0;
        border-color: #e45151
    }

    .feedback {
        border-radius: 12px;
        padding: 13px 15px;
        font-size: 13px;
        margin-top: 12px
    }

    .feedback.ok {
        background: #e8f8ef;
        color: #14663f
    }

    .feedback.no {
        background: #fff0f0;
        color: #a43434
    }

    .result-hero {
        text-align: center;
        padding: 22px
    }

    .result-score {
        font: 800 54px 'IBM Plex Mono', monospace;
        color: #6e4be7
    }

    .review {
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 13px;
        margin-top: 9px
    }

    .history td,
    .history th {
        font-size: 12px
    }

    .empty {
        text-align: center;
        padding: 32px;
        color: var(--muted);
        border: 1px dashed #bfd0e2;
        border-radius: 14px
    }

    @keyframes shake {
        25% {
            transform: translateX(-4px)
        }

        75% {
            transform: translateX(4px)
        }
    }

    @media(max-width:850px) {
        .cw-layout {
            grid-template-columns: 1fr
        }

        .match-grid {
            grid-template-columns: 1fr
        }
    }

    @media(max-width:600px) {
        .pv {
            padding: 14px 10px 50px
        }

        .pv-head,
        .game-shell {
            padding: 17px
        }

        .cw-cellwrap,
        .cw-cell,
        .cw-block {
            width: 39px;
            height: 39px
        }

        .cw-cell {
            font-size: 16px
        }

        .pv h1 {
            font-size: 23px
        }
    }

    /* ===== Concept Matcher V3 ===== */
    .match-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 16px
    }

    .match-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap
    }

    .restart-btn {
        border: 1px solid #cbd8e7;
        background: #fff;
        color: #29425f;
        border-radius: 10px;
        padding: 9px 13px;
        font-weight: 700;
        font-size: 12px;
        transition: .18s
    }

    .restart-btn:hover {
        border-color: #148a55;
        color: #148a55;
        background: #f1fbf6
    }

    .match-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 18px;
        align-items: stretch
    }

    .match-col {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #dce6ef;
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 5px 18px rgba(16, 37, 66, .035)
    }

    .match-col-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 0 2px 11px;
        border-bottom: 1px solid #edf1f5;
        margin-bottom: 8px
    }

    .match-col-head strong {
        font-size: 14px;
        color: #102542
    }

    .match-col-head span {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .07em;
        color: #718198;
        text-transform: uppercase
    }

    .match-list {
        display: grid;
        grid-template-rows: repeat(var(--match-count), minmax(68px, 1fr));
        gap: 8px;
        flex: 1
    }

    .match-item {
        width: 100%;
        height: 100%;
        min-height: 68px;
        border: 1px solid #d3deea;
        background: #fbfdff;
        border-radius: 12px;
        padding: 12px 14px;
        text-align: left;
        margin: 0;
        color: #24405f;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        transition: .18s
    }

    .match-item:hover {
        transform: translateY(-1px);
        border-color: #65b58e;
        background: #f4fcf8;
        box-shadow: 0 5px 13px rgba(20, 138, 85, .08)
    }

    .match-item.sel {
        outline: 3px solid rgba(20, 138, 85, .12);
        border-color: #148a55;
        background: #effaf4
    }

    .match-item.ok {
        background: #dcfce7 !important;
        border-color: #22a35a !important;
        color: #12663e !important;
        pointer-events: none
    }

    .match-item.no {
        background: #fee2e2 !important;
        border-color: #ef4444 !important;
        color: #b91c1c !important;
        animation: shake .25s
    }

    .match-term {
        display: block
    }

    .match-term small {
        display: block;
        margin-top: 4px;
        font-size: 10px;
        font-weight: 700;
        color: #8190a3
    }

    .match-help {
        border: 1px solid #c9ead8;
        background: #f0fbf5;
        color: #27664a;
        border-radius: 12px;
        padding: 11px 13px;
        font-size: 12px;
        margin-bottom: 14px
    }

    /* ===== Quick Quiz V3 ===== */
    .quick-shell {
        max-width: 850px;
        margin: 0 auto
    }

    .quick-status {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 18px
    }

    .quick-stat {
        border: 1px solid #e1e8f0;
        border-radius: 14px;
        background: #f8fbff;
        padding: 12px 14px
    }

    .quick-stat span {
        display: block;
        color: #75869a;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em
    }

    .quick-stat b {
        display: block;
        color: #102542;
        font: 800 18px 'IBM Plex Mono', monospace;
        margin-top: 3px
    }

    .quick-question-wrap {
        border-top: 1px solid #e5ebf2;
        padding-top: 18px
    }

    .quick-count {
        font-size: 11px;
        color: #6e8198;
        font-weight: 800;
        letter-spacing: .04em;
        margin-bottom: 8px
    }

    .quick-question {
        font-size: 19px;
        font-weight: 800;
        color: #102542;
        line-height: 1.45;
        margin-bottom: 15px
    }

    .q-option {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 11px;
        border: 1px solid #cfdae7;
        background: #fff;
        border-radius: 12px;
        padding: 13px 15px;
        text-align: left;
        margin: 8px 0;
        color: #203a57;
        font-weight: 650;
        transition: .16s
    }

    .q-option:hover {
        border-color: #7babeb;
        background: #f6faff;
        transform: translateY(-1px)
    }

    .q-option .opt-key {
        width: 27px;
        height: 27px;
        border-radius: 8px;
        background: #eef5ff;
        color: #2871ca;
        display: grid;
        place-items: center;
        font: 800 11px 'IBM Plex Mono', monospace;
        flex: 0 0 auto
    }

    .q-option.chosen {
        border-color: #6f8fb8;
        background: #f2f6fb
    }

    .quick-progress {
        height: 6px;
        background: #e8eef5;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: 18px
    }

    .quick-progress span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #1677ff, #7552e8);
        border-radius: 999px;
        transition: .25s
    }

    .timer-ring {
        width: 58px;
        height: 58px;
        border: 5px solid #1677ff;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font: 800 14px 'IBM Plex Mono', monospace;
        color: #102542
    }

    .quick-note {
        text-align: center;
        color: #8492a5;
        font-size: 11px;
        margin-top: 13px
    }

    @media(max-width:850px) {
        .match-grid {
            grid-template-columns: 1fr
        }

        .match-list {
            grid-template-rows: none
        }

        .quick-status {
            grid-template-columns: repeat(2, 1fr)
        }
    }


    /* ===== Practice V4: Quick Quiz feedback + compact result ===== */
    .quick-shell .feedback {
        display: block;
        border-radius: 12px;
        padding: 11px 13px;
        margin-top: 12px;
        font-size: 13px
    }

    .quick-shell .feedback.ok {
        background: #e8f8ef;
        border: 1px solid #b9e8cf;
        color: #12663e
    }

    .quick-shell .feedback.no {
        background: #fff1f1;
        border: 1px solid #f3c4c4;
        color: #a62f36
    }

    .result-hero {
        max-width: 760px;
        margin: 0 auto
    }
</style>
<div class="pv">
    <section id="hub">
        <div class="pv-head mb-4">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="eyebrow">Mini game • Công nghệ thông tin</div>
                    <h1 class="mb-1">Luyện tập tự do</h1>
                    <p class="mb-0 small">Mỗi ngày một bộ nội dung mới để ôn kiến thức CNTT theo cách trực quan và ngắn gọn.</p>
                </div>
                <div class="mode-tabs"><button onclick="startGame('crossword')">Ô chữ</button><button class="green" onclick="startGame('concept_match')">Nối cặp</button><button class="amber" onclick="startGame('quick_quiz')">Phản xạ nhanh</button></div>
            </div>
        </div>
        <div class="row g-3 mb-4" id="stats">
            <div class="text-muted">Đang tải dữ liệu...</div>
        </div>
        <div class="eyebrow mb-1">Chọn chế độ</div>
        <h2 class="mb-3">Bạn muốn luyện tập thế nào?</h2>
        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="game-card blue">
                    <div class="game-icon mb-4"><i class="bi bi-grid-3x3-gap-fill"></i></div><small class="text-primary">01 • DAILY CROSSWORD</small>
                    <h3 class="mt-2">Daily Crossword</h3>
                    <p class="small">Ô chữ CNTT hằng ngày với Across / Down và giải thích sau khi hoàn thành.</p>
                    <div class="d-flex justify-content-between align-items-center mt-4"><b class="small text-primary">Cao nhất: <span id="best-crossword">0</span></b><button class="play" onclick="startGame('crossword')">Bắt đầu</button></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="game-card green">
                    <div class="game-icon mb-4"><i class="bi bi-bezier2"></i></div><small style="color:#148a55">02 • CONCEPT MATCHER</small>
                    <h3 class="mt-2">Nối Cặp Từ</h3>
                    <p class="small">Ghép từ khóa ở cột A với khái niệm hoặc đặc điểm chính xác ở cột B.</p>
                    <div class="d-flex justify-content-between align-items-center mt-4"><b class="small" style="color:#148a55">Cao nhất: <span id="best-concept_match">0</span></b><button class="play" onclick="startGame('concept_match')">Bắt đầu</button></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="game-card amber">
                    <div class="game-icon mb-4"><i class="bi bi-lightning-charge-fill"></i></div><small style="color:#b36c00">03 • QUICK QUIZ</small>
                    <h3 class="mt-2">IT Core Concepts Challenge</h3>
                    <p class="small">Phản xạ nhanh với timer từng câu, chuỗi đúng, giải thích đúng/sai và điểm tốc độ.</p>
                    <div class="d-flex justify-content-between align-items-center mt-4"><b class="small" style="color:#b36c00">Cao nhất: <span id="best-quick_quiz">0</span></b><button class="play" onclick="startGame('quick_quiz')">Bắt đầu</button></div>
                </div>
            </div>
        </div>
        <div class="pv-panel">
            <div class="eyebrow">Theo dõi tiến bộ</div>
            <h2>Lịch sử luyện tập</h2>
            <div id="history"></div>
        </div>
    </section>
    <section id="game" class="game-view"></section>
</div>
<script>
    const API = '../api/practice/endpoint.php',
        CSRF = <?= json_encode($csrf) ?>;
    let S = null,
        T = null,
        term = null,
        busy = false,
        combo = 0,
        quizScore = 0;
    const E = s => String(s ?? '').replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    } [m]));
    async function api(a, d = null) {
        let o = {
            headers: {
                Accept: 'application/json',
                'X-CSRF-Token': CSRF
            }
        };
        if (d !== null) {
            o.method = 'POST';
            o.headers['Content-Type'] = 'application/json';
            o.body = JSON.stringify(d)
        }
        let r = await fetch(API + '?action=' + encodeURIComponent(a), o),
            raw = await r.text(),
            j;
        try {
            j = JSON.parse(raw)
        } catch (e) {
            console.error('Practice API raw response:', raw);
            throw new Error('Practice API không trả JSON. Kiểm tra Network/Console hoặc lỗi PHP ở endpoint.php.')
        }
        if (!r.ok || !j.ok) throw new Error(j.message || 'Không thể xử lý Practice.');
        return j.data
    }

    function name(t) {
        return t === 'crossword' ? 'Daily Crossword' : t === 'concept_match' ? 'Nối Cặp Từ' : 'IT Quick Quiz'
    }
    async function dashboard() {
        try {
            let d = await api('dashboard'),
                s = d.stats || {};
            document.getElementById('stats').innerHTML = [
                ['Lượt luyện', s.sessions || 0],
                ['Điểm trung bình', (+s.avg_score || 0).toFixed(0)],
                ['Điểm cao nhất', s.best_score || 0],
                ['Độ chính xác', (+s.avg_accuracy || 0).toFixed(0) + '%'],
                ['Tổng thời gian', Math.round((+s.total_time || 0) / 60) + ' phút']
            ].map(x => `<div class="col-6 col-lg"><div class="stat"><span>${x[0]}</span><b>${x[1]}</b></div></div>`).join('');
            (d.games || []).forEach(g => {
                let e = document.getElementById('best-' + g.game_type);
                if (e) e.textContent = g.best_score
            });
            let h = d.history || [];
            document.getElementById('history').innerHTML = h.length ? `<div class="table-responsive"><table class="table history align-middle mb-0"><thead><tr><th>Chế độ</th><th>Ngày</th><th>Điểm</th><th>Kết quả</th><th>Thời gian</th><th></th></tr></thead><tbody>${h.map(x=>`<tr><td><b>${name(x.game_type)}</b></td><td>${E(x.daily_key)}</td><td>${x.score}</td><td>${x.correct_count}/${x.total_count}</td><td>${x.duration_seconds}s</td><td><button class="btn btn-sm btn-outline-primary" onclick="result(${x.id})">Xem</button></td></tr>`).join('')}</tbody></table></div>` : `<div class="empty"><b>Chưa có lịch sử luyện tập</b><div class="small">Hãy chọn một chế độ để bắt đầu.</div></div>`
        } catch (e) {
            document.getElementById('history').innerHTML = `<div class="alert alert-danger">${E(e.message)}</div>`
        }
    }
    async function startGame(t) {
        try {
            clearInterval(T);
            S = await api('start', {
                game_type: t,
                count: 8
            });
            S.done = 0;
            combo = 0;
            quizScore = 0;
            document.getElementById('hub').style.display = 'none';
            document.getElementById('game').classList.add('on');
            render()
        } catch (e) {
            alert(e.message)
        }
    }

    function shell(label, title, body) {
        return `<div class="top-game mb-3"><div><div class="eyebrow">${label}</div><h2 class="mb-0">${title}</h2></div><button class="back" onclick="backHub()">Quay lại Luyện tập tự do</button></div>${body}`
    }

    function runClock(sec, onEnd) {
        clearInterval(T);
        let left = sec,
            el = () => document.getElementById('clock');
        if (el()) el().textContent = left;
        T = setInterval(() => {
            left--;
            if (el()) el().textContent = left;
            if (left <= 0) {
                clearInterval(T);
                onEnd && onEnd()
            }
        }, 1000)
    }

    function render() {
        if (S.game_type === 'crossword') crossword();
        else if (S.game_type === 'concept_match') matcher();
        else quiz(0)
    }

    function crossword() {
        let c = S.content,
            g = Array.from({
                length: c.rows
            }, () => Array(c.cols).fill(null)),
            nums = {};
        c.clues.forEach(x => {
            nums[x.row + '-' + x.col] = nums[x.row + '-' + x.col] || x.no;
            for (let k = 0; k < x.length; k++) {
                let r = x.row + (x.direction === 'down' ? k : 0),
                    co = x.col + (x.direction === 'across' ? k : 0);
                if (r < c.rows && co < c.cols) g[r][co] = 1
            }
        });
        let cells = g.flatMap((row, r) => row.map((v, co) => v ? `<div class="cw-cellwrap">${nums[r+'-'+co]?`<span class="cw-num">${nums[r+'-'+co]}</span>`:''}<input class="cw-cell" maxlength="1" data-r="${r}" data-c="${co}" autocomplete="off" oninput="cwInput(this)" onkeydown="cwKey(event,this)" onmouseenter="hoverCell(this,true)" onmouseleave="hoverCell(this,false)" onclick="selectCellClue(this)" onfocus="this.classList.add('cell-focus')" onblur="this.classList.remove('cell-focus')"></div>` : `<div class="cw-block"></div>`)).join('');
        let clues = d => c.clues.filter(x => x.direction === d).map(x => `<button class="clue" data-clue="${x.token}" onclick="selectClue('${x.token}')" onmouseenter="hoverClue('${x.token}',true)" onmouseleave="hoverClue('${x.token}',false)"><b>${x.no}.</b> ${E(x.clue)} <span class="text-muted">(${x.length})</span></button>`).join('');
        document.getElementById('game').innerHTML = shell('GAME 01 • DAILY CROSSWORD', 'Daily Crossword', `<div class="game-shell"><div class="d-flex justify-content-between flex-wrap gap-2 mb-4"><div><h3 class="h5 fw-bold mb-1">${E(c.title)}</h3><p class="small mb-0">${E(c.description)}</p></div><div class="d-flex gap-2"><div class="scorebox"><span>Ngày</span><b>${E(S.daily_key)}</b></div><div class="scorebox"><span>Thời gian</span><b><span id="clock">${c.time_limit}</span>s</b></div></div></div><div class="cw-layout"><div class="overflow-auto"><div class="cw-grid" style="grid-template-columns:repeat(${c.cols},48px)">${cells}</div></div><div><div class="row g-3"><div class="col-6 clue-group"><h4>Across</h4>${clues('across')}</div><div class="col-6 clue-group"><h4>Down</h4>${clues('down')}</div></div><div class="d-flex gap-2 flex-wrap mt-3"><button class="btn btn-outline-warning btn-sm" onclick="cwHint()">💡 Hint</button><button class="solve" onclick="solvePuzzle()">Solve Puzzle</button></div><div id="cwmsg" class="small mt-3 text-muted">Chọn một clue để bắt đầu.</div></div></div></div>`);
        S.current = null;
        runClock(c.time_limit, () => finish(true))
    }

    function getCellClues(row, col) {
        row = Number(row);
        col = Number(col);
        return S.content.clues.filter(clue => {
            for (let i = 0; i < clue.length; i++) {
                let r = clue.row + (clue.direction === 'down' ? i : 0),
                    c = clue.col + (clue.direction === 'across' ? i : 0);
                if (r === row && c === col) return true
            }
            return false
        })
    }

    function getClueCells(clue) {
        let a = [];
        for (let i = 0; i < clue.length; i++) {
            let r = clue.row + (clue.direction === 'down' ? i : 0),
                c = clue.col + (clue.direction === 'across' ? i : 0),
                el = document.querySelector(`[data-r="${r}"][data-c="${c}"]`);
            if (el) a.push(el)
        }
        return a
    }

    function hoverClue(tok, on) {
        let clue = S.content.clues.find(x => x.token === tok);
        if (!clue) return;
        getClueCells(clue).forEach(el => el.classList.toggle('word-hover', on));
        let q = document.querySelector(`.clue[data-clue="${tok}"]`);
        if (q) q.classList.toggle('hovered', on)
    }

    function hoverCell(cell, on) {
        getCellClues(cell.dataset.r, cell.dataset.c).forEach(clue => {
            getClueCells(clue).forEach(el => el.classList.toggle('word-hover', on));
            let q = document.querySelector(`.clue[data-clue="${clue.token}"]`);
            if (q) q.classList.toggle('hovered', on)
        })
    }

    function selectCellClue(cell) {
        let related = getCellClues(cell.dataset.r, cell.dataset.c);
        if (!related.length) return;
        let target = related[0];
        if (related.length > 1 && S.current) {
            let i = related.findIndex(x => x.token === S.current.token);
            if (i !== -1) target = related[(i + 1) % related.length]
        }
        selectClue(target.token, false)
    }

    function selectClue(tok, focusFirst = true) {
        let x = S.content.clues.find(a => a.token === tok);
        if (!x) return;
        S.current = x;
        document.querySelectorAll('.clue').forEach(e => e.classList.toggle('sel', e.dataset.clue === tok));
        document.querySelectorAll('.cw-cell').forEach(e => e.classList.remove('active', 'word-active'));
        let cells = getClueCells(x);
        cells.forEach(e => e.classList.add('word-active'));
        let msg = document.getElementById('cwmsg');
        if (msg) msg.innerHTML = `<b style="color:#7149e8">${x.no} • ${x.direction==='across'?'ACROSS':'DOWN'} • ${x.length} ký tự</b><br>${E(x.clue)}`;
        if (focusFirst && cells.length)(cells.find(e => !e.value) || cells[0]).focus()
    }

    function cwInput(el) {
        el.value = el.value.replace(/[^a-zA-Z0-9]/g, '').slice(-1).toUpperCase();
        if (!S.current || !el.value) return;
        moveInWord(el, 1)
    }

    function moveInWord(el, step) {
        let x = S.current;
        for (let k = 0; k < x.length; k++) {
            let r = x.row + (x.direction === 'down' ? k : 0),
                c = x.col + (x.direction === 'across' ? k : 0);
            if (el.dataset.r == r && el.dataset.c == c) {
                let n = k + step;
                if (n >= 0 && n < x.length) {
                    let nr = x.row + (x.direction === 'down' ? n : 0),
                        nc = x.col + (x.direction === 'across' ? n : 0),
                        e = document.querySelector(`[data-r="${nr}"][data-c="${nc}"]`);
                    if (e) e.focus()
                }
                break
            }
        }
    }

    function cwKey(ev, el) {
        if (ev.key === 'Backspace' && !el.value) {
            ev.preventDefault();
            moveInWord(el, -1)
        }
        if (ev.key === 'Enter') {
            ev.preventDefault();
            checkWord()
        }
        if (['ArrowRight', 'ArrowDown'].includes(ev.key)) {
            ev.preventDefault();
            moveInWord(el, 1)
        }
        if (['ArrowLeft', 'ArrowUp'].includes(ev.key)) {
            ev.preventDefault();
            moveInWord(el, -1)
        }
    }
    async function checkWord() {
        if (!S.current) return;
        let x = S.current,
            a = '',
            cells = [];
        for (let k = 0; k < x.length; k++) {
            let r = x.row + (x.direction === 'down' ? k : 0),
                c = x.col + (x.direction === 'across' ? k : 0),
                e = document.querySelector(`[data-r="${r}"][data-c="${c}"]`);
            cells.push(e);
            a += e ? e.value : ''
        }
        let r = await api('answer', {
            session_id: S.id,
            item_token: x.token,
            answer: a
        });
        cells.forEach(e => {
            if (!e) return;
            e.classList.remove('good', 'bad');
            e.classList.add(r.correct ? 'good' : 'bad')
        });
        document.getElementById('cwmsg').innerHTML = r.correct ? `<span class="text-success fw-bold">✓ Chính xác.</span> ${E(r.explanation)}` : `<span class="text-danger fw-bold">Chưa đúng.</span> Hãy kiểm tra lại từ này.`;
        if (r.correct) {
            S.done++;
            if (S.done >= S.total) finish(false)
        }
    }
    async function cwHint() {
        if (!S.current) return alert('Hãy chọn một clue trước.');
        let h = await api('hint', {
                session_id: S.id,
                item_token: S.current.token
            }),
            x = S.current,
            r = x.row,
            c = x.col,
            e = document.querySelector(`[data-r="${r}"][data-c="${c}"]`);
        if (e && !e.value) e.value = h.letter;
        document.getElementById('cwmsg').textContent = 'Đã mở một chữ cái. Hint trừ 20 điểm.'
    }
    async function solvePuzzle() {
        if (!S.current) {
            let first = S.content.clues[0];
            selectClue(first.token)
        }
        await checkWord();
        if (S.done < S.total) document.getElementById('cwmsg').innerHTML += '<br>Chọn các clue còn lại và nhấn Enter để kiểm tra từng từ.'
    }

    function matcher() {
        let c = S.content,
            count = Math.max(c.terms.length, c.definitions.length);
        document.getElementById('game').innerHTML = shell('GAME 02 • CONCEPT MATCHER', 'Nối Cặp Từ', `<div class="game-shell"><div class="match-toolbar"><div><div class="small text-muted fw-bold">Tiến độ ghép cặp</div><div class="h4 mb-0"><span id="done">0</span> / ${S.total}</div></div><div class="match-actions"><div class="scorebox green"><span>Điểm</span><b id="mscore">0</b></div><div class="scorebox"><span>Lỗi</span><b id="mwrong">0</b></div><div class="scorebox"><span>Thời gian</span><b><span id="clock">${c.time_limit}</span>s</b></div><button class="restart-btn" onclick="restartCurrentMatch()"><i class="bi bi-arrow-counterclockwise me-1"></i>Bắt đầu lại</button></div></div><div class="progress mb-3"><div id="mprog" class="progress-bar" style="width:0"></div></div><div class="match-help"><i class="bi bi-cursor-fill me-1"></i> Chọn một từ khóa ở Cột A, sau đó chọn khái niệm tương ứng ở Cột B. Cặp đúng chuyển xanh và được khóa.</div><div class="match-grid" style="--match-count:${count}"><div class="match-col"><div class="match-col-head"><strong>Cột A • Từ khóa</strong><span>${c.terms.length} thuật ngữ</span></div><div class="match-list">${c.terms.map(x=>`<button class="match-item" data-t="${x.token}" onclick="pickTerm(this)"><span class="match-term"><b>${E(x.term)}</b><small>${E(x.category||'Công nghệ thông tin')}</small></span></button>`).join('')}</div></div><div class="match-col"><div class="match-col-head"><strong>Cột B • Khái niệm / đặc điểm</strong><span>${c.definitions.length} mô tả</span></div><div class="match-list">${c.definitions.map(x=>`<button class="match-item" data-d="${x.token}" onclick="pickDef(this)">${E(x.definition)}</button>`).join('')}</div></div></div></div>`);
        S.score = 0;
        S.wrong = 0;
        S.done = 0;
        term = null;
        runClock(c.time_limit, () => finish(true))
    }
    async function restartCurrentMatch() {
        if (!S || S.game_type !== 'concept_match') return;
        if (!confirm('Bắt đầu lại lượt nối cặp này? Tiến độ hiện tại sẽ được làm mới.')) return;
        try {
            clearInterval(T);
            S = await api('restart', {
                session_id: S.id
            });
            S.done = 0;
            term = null;
            matcher()
        } catch (e) {
            alert(e.message)
        }
    }

    function pickTerm(e) {
        if (e.classList.contains('ok')) return;
        document.querySelectorAll('[data-t]').forEach(x => x.classList.remove('sel'));
        e.classList.add('sel');
        term = e
    }
    async function pickDef(e) {
        if (!term || busy || e.classList.contains('ok')) return;
        busy = true;
        try {
            let r = await api('answer', {
                session_id: S.id,
                item_token: term.dataset.t,
                answer: e.dataset.d
            });
            if (r.correct) {
                term.classList.add('ok');
                e.classList.add('ok');
                S.done++;
                S.score += r.points;
                document.getElementById('done').textContent = S.done;
                document.getElementById('mscore').textContent = S.score;
                document.getElementById('mprog').style.width = (S.done / S.total * 100) + '%';
                term = null;
                if (S.done >= S.total) return finish(false)
            } else {
                S.wrong++;
                e.classList.add('no');
                term.classList.add('no');
                document.getElementById('mwrong').textContent = S.wrong;
                setTimeout(() => {
                    e.classList.remove('no');
                    if (term) term.classList.remove('no', 'sel');
                    term = null
                }, 450)
            }
        } catch (x) {
            alert(x.message)
        } finally {
            busy = false
        }
    }

    function quiz(i) {
        if (i >= S.content.items.length) return finish(false);
        S.qi = i;
        let q = S.content.items[i],
            limit = 5,
            best = document.getElementById('best-quick_quiz')?.textContent || 0;
        document.getElementById('game').innerHTML = shell('GAME 03 • QUICK QUIZ', 'IT Core Concepts Challenge', `<div class="game-shell quick-shell">
  <div class="quick-status">
   <div class="quick-stat"><span>Thời gian</span><b><span id="clock">${limit}</span>s</b></div>
   <div class="quick-stat"><span>Điểm</span><b id="quickScore">${quizScore}</b></div>
   <div class="quick-stat"><span>Số câu</span><b>${i+1}/${S.total}</b></div>
   <div class="quick-stat"><span>Điểm cao nhất</span><b>${E(best)}</b></div>
  </div>
  <div class="quick-progress"><span style="width:${(i/S.total)*100}%"></span></div>
  <div class="quick-question-wrap">
   <div class="quick-count">${E(q.category||'CNTT')} • CÂU ${i+1}</div>
   <div class="quick-question">${E(q.question)}</div>
   <div id="qopts">${q.options.map((o,k)=>`<button class="q-option" data-o="${o.token}" onclick="qAnswer(this)"><span class="opt-key">${String.fromCharCode(65+k)}</span><span>${E(o.text)}</span></button>`).join('')}</div>
   <div id="qfeed"></div>
   <div class="quick-note">Mỗi câu có 5 giây • Đúng +100 điểm • Sai -25 điểm • Hết giờ 0 điểm.</div>
  </div>
 </div>`);
        S.left = limit;
        clearInterval(T);
        T = setInterval(() => {
            S.left--;
            let e = document.getElementById('clock');
            if (e) e.textContent = Math.max(0, S.left);
            if (S.left <= 0) {
                clearInterval(T);
                qTimeout()
            }
        }, 1000)
    }
    async function qAnswer(el) {
        if (busy) return;
        busy = true;
        clearInterval(T);
        let q = S.content.items[S.qi];
        try {
            document.querySelectorAll('.q-option').forEach(x => x.disabled = true);
            let r = await api('answer', {
                session_id: S.id,
                item_token: q.token,
                answer: el.dataset.o,
                combo
            });
            if (r.correct) {
                combo++;
                quizScore += Number(r.points || 100);
                el.classList.add('ok');
                document.getElementById('qfeed').innerHTML = `<div class="feedback ok"><b><i class="bi bi-check-circle-fill me-1"></i> Chính xác!</b> +${Math.abs(Number(r.points||100))} điểm</div>`;
            } else {
                combo = 0;
                quizScore = Math.max(0, quizScore + Number(r.points || -25));
                el.classList.add('no');
                document.getElementById('qfeed').innerHTML = `<div class="feedback no"><b><i class="bi bi-x-circle-fill me-1"></i> Chưa chính xác.</b> -25 điểm<div class="mt-1">Đáp án đúng: <b>${E(r.correct_answer)}</b></div></div>`;
            }
            let sc = document.getElementById('quickScore');
            if (sc) sc.textContent = quizScore;
            setTimeout(() => {
                busy = false;
                quiz(S.qi + 1)
            }, 1100);
        } catch (e) {
            busy = false;
            alert(e.message)
        }
    }
    async function qTimeout() {
        if (busy) return;
        busy = true;
        let q = S.content.items[S.qi];
        try {
            let r = await api('answer', {
                session_id: S.id,
                item_token: q.token,
                answer: '',
                timeout: true,
                combo
            });
            combo = 0;
            document.querySelectorAll('.q-option').forEach(x => x.disabled = true);
            document.getElementById('qfeed').innerHTML = `<div class="feedback no"><b><i class="bi bi-clock-history me-1"></i> Hết 5 giây.</b> Câu này 0 điểm.<div class="mt-1">Đáp án đúng: <b>${E(r.correct_answer)}</b></div></div>`;
            setTimeout(() => {
                busy = false;
                quiz(S.qi + 1)
            }, 1100);
        } catch (e) {
            busy = false;
            alert(e.message)
        }
    }
    async function finish(timeout = false) {
        clearInterval(T);
        if (busy) return;
        busy = true;
        try {
            let r = await api('finish', {
                session_id: S.id,
                timeout
            });
            showResult(r);
            dashboard()
        } catch (e) {
            alert(e.message)
        } finally {
            busy = false
        }
    }
    async function result(id) {
        try {
            let r = await api('result', {
                session_id: id
            });
            document.getElementById('hub').style.display = 'none';
            document.getElementById('game').classList.add('on');
            showResult(r)
        } catch (e) {
            alert(e.message)
        }
    }

    function showResult(r) {
        let s = r.session,
            isMatch = s.game_type === 'concept_match',
            isQuiz = s.game_type === 'quick_quiz';
        let title = isMatch ? 'Chúc mừng! Bạn đã hoàn thành Nối Cặp Từ' : (isQuiz ? 'Hoàn thành thử thách Phản Xạ Nhanh!' : 'Chúc mừng bạn đã hoàn thành Daily Crossword!');
        let message = isMatch ? 'Bạn đã ghép xong toàn bộ các cặp khái niệm.' : (isQuiz ? 'Bạn đã hoàn thành toàn bộ câu hỏi trong lượt phản xạ.' : 'Bạn đã giải xong ô chữ hôm nay.');
        document.getElementById('game').innerHTML = shell('KẾT QUẢ • ' + name(s.game_type), title, `<div class="game-shell"><div class="result-hero">
  <div class="game-icon mx-auto mb-3"><i class="bi bi-trophy-fill"></i></div>
  <p class="mb-1 fw-semibold">${E(message)}</p>
  <div class="result-score">${s.score}</div><p>ĐIỂM</p>
  <div class="row g-2 justify-content-center mt-2">
   <div class="col-6 col-md-2"><div class="stat"><span>Đúng</span><b>${s.correct_count}/${s.total_count}</b></div></div>
   <div class="col-6 col-md-2"><div class="stat"><span>Chính xác</span><b>${s.accuracy}%</b></div></div>
   <div class="col-6 col-md-2"><div class="stat"><span>Thời gian</span><b>${s.duration_seconds}s</b></div></div>
   ${isQuiz?`<div class="col-6 col-md-2"><div class="stat"><span>Combo cao nhất</span><b>${s.max_combo||0}</b></div></div>`:''}
  </div>
  <div class="mt-4">
   <button class="play me-2" onclick="restartResult(${s.id})"><i class="bi bi-arrow-counterclockwise me-1"></i>Bắt đầu lại</button>
   <button class="btn btn-outline-secondary" onclick="backHub()">Về Practice</button>
  </div>
 </div></div>`)
    }
    async function restartResult(id) {
        try {
            S = await api('restart', {
                session_id: id
            });
            S.done = 0;
            combo = 0;
            quizScore = 0;
            render()
        } catch (e) {
            alert(e.message)
        }
    }

    function backHub() {
        clearInterval(T);
        S = null;
        document.getElementById('game').classList.remove('on');
        document.getElementById('hub').style.display = 'block';
        dashboard();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        })
    }
    dashboard();
</script>
<?php require_once $root . '/includes/footer.php'; ?>