<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('student');

if (!isset($_SESSION['gamified_practice'])) {
    header("Location: practice.php");
    exit;
}

$session   = $_SESSION['gamified_practice'];
$mode      = $session['mode'];
$questions = $session['questions'];
$total_q   = count($questions);

$page_title = ($mode === 'flashcard') ? 'Luyện Thẻ Ghi Nhớ' : 'Đấu Trí Tốc Độ';
require_once dirname(__DIR__) . '/includes/header_student.php';
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<style>
    :root {
        --primary-purple: #8b5cf6;
        --purple-hover: #7c3aed;
        --bg-main: #f8fafc;
    }
    body { background-color: var(--bg-main); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }

    /* Dashboard Status Header */
    .game-status-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 1rem 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px -3px rgba(0,0,0,0.05);
    }

    /* Timer Health Bar */
    .timer-container {
        height: 10px;
        background: #e2e8f0;
        border-radius: 20px;
        overflow: hidden;
        margin-top: 1rem;
    }
    .timer-bar {
        height: 100%;
        background: linear-gradient(90deg, #ef4444 0%, #f59e0b 50%, #10b981 100%);
        width: 100%;
        transition: width 0.1s linear;
    }

    /* Flashcard 3D Design */
    .flashcard-wrap { perspective: 1000px; min-height: 380px; }
    .flashcard-card {
        width: 100%; height: 100%; min-height: 380px;
        position: relative; transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;
    }
    .flashcard-card.flipped { transform: rotateY(180deg); }
    .fc-front, .fc-back {
        position: absolute; width: 100%; height: 100%;
        backface-visibility: hidden; border-radius: 24px;
        padding: 2.5rem 2rem; display: flex; flex-direction: column;
        justify-content: center; align-items: center; text-align: center;
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.08);
    }
    .fc-front { background: #ffffff; border: 2px solid #e2e8f0; color: #0f172a; }
    .fc-back { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; transform: rotateY(180deg); }

    /* Quiz Option Buttons */
    .quiz-option-btn {
        border: 2px solid #e2e8f0;
        background: #ffffff;
        border-radius: 18px;
        padding: 1.25rem 1.5rem;
        font-weight: 700;
        color: #1e293b;
        font-size: 1.05rem;
        transition: all 0.2s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        width: 100%;
        text-align: left;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .quiz-option-btn:hover:not(:disabled) {
        border-color: var(--primary-purple);
        background: #f5f3ff;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px -3px rgba(139, 92, 246, 0.15);
    }
    .quiz-option-btn.correct { background: #10b981 !important; color: #fff !important; border-color: #059669 !important; }
    .quiz-option-btn.wrong { background: #ef4444 !important; color: #fff !important; border-color: #dc2626 !important; }
    
    /* Streak & Badge Glow */
    .streak-glow {
        background: linear-gradient(135deg, #ff6b6b 0%, #ff8e53 100%);
        box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4);
    }
</style>

<div class="container py-4 max-w-4xl">
    
    <!-- THANH TRẠNG THÁI GAME -->
    <div class="game-status-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark fs-6 px-3 py-2 rounded-pill" id="progress-text">1 / <?= $total_q ?></span>
                <span class="badge streak-glow text-white fs-6 px-3 py-2 rounded-pill fw-bold" id="streak-box">
                    <i class="bi bi-fire me-1"></i> <span id="streak-count">0</span>x Combo
                </span>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <?php if ($mode === 'speed_quiz'): ?>
                    <button id="btn-5050" class="btn btn-outline-warning text-dark font-weight-bold btn-sm rounded-pill px-3 py-2" onclick="use5050()">
                        <i class="bi bi-magic me-1"></i> Trợ giúp 50/50 (<span id="count-5050">1</span>)
                    </button>
                <?php endif; ?>
                <div class="fw-extrabold text-primary fs-5">
                    <i class="bi bi-star-fill text-warning me-1"></i> <span id="score-count">0</span> Điểm
                </div>
            </div>
        </div>

        <?php if ($mode === 'speed_quiz'): ?>
            <div class="timer-container">
                <div class="timer-bar" id="timer-bar"></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 1. CHẾ ĐỘ THẺ GHI NHỚ (FLASHCARD) -->
    <?php if ($mode === 'flashcard'): ?>
        <div id="flashcard-game">
            <div class="flashcard-wrap mb-4">
                <div class="flashcard-card" id="active-card" onclick="flipCard()">
                    <div class="fc-front">
                        <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-3">MẶT TRƯỚC (CÂU HỎI)</span>
                        <h3 class="fw-bold mb-0" id="fc-question">Loading...</h3>
                        <div class="text-muted small mt-auto"><i class="bi bi-hand-index-thumb me-1"></i> Nhấn để xoay mặt sau</div>
                    </div>
                    <div class="fc-back">
                        <span class="badge bg-white text-dark fw-bold px-3 py-2 rounded-pill mb-3">MẶT SAU (ĐÁP ÁN)</span>
                        <h3 class="fw-extrabold mb-2" id="fc-answer">Loading...</h3>
                        <p class="text-white-50 small mb-0" id="fc-explain"></p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <button class="btn btn-outline-danger btn-lg w-100 rounded-4 py-3 fw-bold" onclick="nextCard(false)">
                        <i class="bi bi-x-circle me-1"></i> Chưa Thuộc
                    </button>
                </div>
                <div class="col-6">
                    <button class="btn btn-success btn-lg w-100 rounded-4 py-3 fw-bold shadow-sm" onclick="nextCard(true)">
                        <i class="bi bi-check-circle me-1"></i> Đã Ghi Nhớ
                    </button>
                </div>
            </div>
        </div>

    <!-- 2. CHẾ ĐỘ ĐẤU TRÍ TỐC ĐỘ (SPEED QUIZ) -->
    <?php else: ?>
        <div id="quiz-game" class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <h4 class="fw-extrabold text-dark mb-4 lh-base" id="quiz-question">Loading...</h4>
            
            <div class="row g-3 mb-4" id="options-box">
                <!-- Tự động load 4 đáp án -->
            </div>

            <div id="quiz-explain-box" class="p-3 rounded-4 bg-light border d-none">
                <strong class="text-primary"><i class="bi bi-lightbulb-fill me-1"></i> Giải thích chi tiết:</strong>
                <p class="small text-secondary mb-0 mt-1" id="quiz-explain"></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- MÀN HÌNH TỔNG KẾT GAME -->
    <div id="result-screen" class="card border-0 shadow-lg rounded-4 p-5 bg-white text-center d-none">
        <div class="mb-3">
            <i class="bi bi-trophy-fill text-warning display-1"></i>
        </div>
        <h2 class="fw-extrabold text-dark mb-2">HOÀN THÀNH MÀN CHƠI!</h2>
        <p class="text-muted mb-4">Bạn đã hoàn thành xuất sắc các thử thách kiến thức</p>
        
        <div class="row justify-content-center g-3 mb-4">
            <div class="col-md-5">
                <div class="p-3 bg-primary-subtle rounded-4">
                    <div class="fs-1 fw-extrabold text-primary" id="res-score">0</div>
                    <div class="small fw-bold text-secondary text-uppercase">Tổng Điểm Đạt Được</div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="p-3 bg-warning-subtle rounded-4">
                    <div class="fs-1 fw-extrabold text-warning-emphasis" id="res-max-streak">0x</div>
                    <div class="small fw-bold text-secondary text-uppercase">Chuỗi Combo Cao Nhất</div>
                </div>
            </div>
        </div>

        <div>
            <a href="practice.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow-sm" style="background: var(--primary-purple); border:none;">
                <i class="bi bi-arrow-repeat me-1"></i> Chơi Lượt Mới
            </a>
        </div>
    </div>
</div>

<script>
    const questions = <?= json_encode($questions) ?>;
    const mode = "<?= $mode ?>";
    let currentIndex = 0;
    let score = 0;
    let streak = 0;
    let maxStreak = 0;
    let available5050 = 1;
    
    const QUESTION_TIME = 15;
    let timeLeft = QUESTION_TIME;
    let timerInterval = null;

    // AM THANH HỆ THỐNG (Web Audio API)
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    function playSound(type) {
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain); gain.connect(audioCtx.destination);

        if (type === 'correct') {
            osc.frequency.setValueAtTime(523.25, audioCtx.currentTime);
            osc.frequency.setValueAtTime(659.25, audioCtx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            osc.start(); osc.stop(audioCtx.currentTime + 0.25);
        } else if (type === 'wrong') {
            osc.frequency.setValueAtTime(220, audioCtx.currentTime);
            osc.frequency.setValueAtTime(164.81, audioCtx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
            osc.start(); osc.stop(audioCtx.currentTime + 0.3);
        }
    }

    // FLASHCARD
    function flipCard() {
        document.getElementById('active-card').classList.toggle('flipped');
    }

    function renderFlashcard() {
        if (currentIndex >= questions.length) { finishGame(); return; }
        let q = questions[currentIndex];
        document.getElementById('active-card').classList.remove('flipped');
        document.getElementById('progress-text').innerText = `${currentIndex + 1} / ${questions.length}`;
        document.getElementById('fc-question').innerText = q.question;
        document.getElementById('fc-answer').innerText = `Đáp án đúng: ${q.correct_option}`;
        document.getElementById('fc-explain').innerText = q.explanation;
    }

    function nextCard(isRemembered) {
        if (isRemembered) {
            score += 10; streak++;
            if (streak > maxStreak) maxStreak = streak;
            playSound('correct');
        } else {
            streak = 0;
            playSound('wrong');
        }
        updateHeader();
        currentIndex++;
        renderFlashcard();
    }

    // SPEED QUIZ
    function startTimer() {
        clearInterval(timerInterval);
        timeLeft = QUESTION_TIME;
        const bar = document.getElementById('timer-bar');
        if(!bar) return;
        bar.style.width = '100%';

        timerInterval = setInterval(() => {
            timeLeft -= 0.1;
            let percent = (timeLeft / QUESTION_TIME) * 100;
            bar.style.width = Math.max(0, percent) + '%';

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                handleTimeout();
            }
        }, 100);
    }

    function handleTimeout() {
        let q = questions[currentIndex];
        playSound('wrong');
        streak = 0;
        updateHeader();

        let allBtns = document.querySelectorAll('.quiz-option-btn');
        allBtns.forEach(b => {
            b.disabled = true;
            if(b.dataset.key === q.correct_option) b.classList.add('correct');
        });

        document.getElementById('quiz-explain').innerText = "Hết thời gian! " + q.explanation;
        document.getElementById('quiz-explain-box').classList.remove('d-none');

        setTimeout(() => {
            currentIndex++;
            renderQuiz();
        }, 2200);
    }

    function renderQuiz() {
        if (currentIndex >= questions.length) { finishGame(); return; }
        let q = questions[currentIndex];
        document.getElementById('progress-text').innerText = `${currentIndex + 1} / ${questions.length}`;
        document.getElementById('quiz-question').innerText = `${currentIndex + 1}. ${q.question}`;
        document.getElementById('quiz-explain-box').classList.add('d-none');

        let opts = {'A': q.option_a, 'B': q.option_b, 'C': q.option_c, 'D': q.option_d};
        let html = '';
        for(let key in opts) {
            html += `
                <div class="col-md-6">
                    <button class="quiz-option-btn" data-key="${key}" onclick="checkAnswer(this, '${key}', '${q.correct_option}', '${q.explanation.replace(/'/g, "\\'")}')">
                        <span class="badge bg-light text-dark border me-3 fs-6">${key}</span> ${opts[key]}
                    </button>
                </div>`;
        }
        document.getElementById('options-box').innerHTML = html;
        startTimer();
    }

    function checkAnswer(btn, selected, correct, explain) {
        clearInterval(timerInterval);
        let allBtns = document.querySelectorAll('.quiz-option-btn');
        allBtns.forEach(b => b.disabled = true);

        if (selected === correct) {
            btn.classList.add('correct');
            playSound('correct');
            let timeBonus = Math.round(timeLeft * 2);
            score += 10 + (streak * 2) + timeBonus;
            streak++;
            if (streak > maxStreak) maxStreak = streak;
            confetti({ particleCount: 35, spread: 50, origin: { y: 0.8 } });
        } else {
            btn.classList.add('wrong');
            playSound('wrong');
            streak = 0;
            allBtns.forEach(b => {
                if(b.dataset.key === correct) b.classList.add('correct');
            });
        }
        updateHeader();

        document.getElementById('quiz-explain').innerText = explain;
        document.getElementById('quiz-explain-box').classList.remove('d-none');

        setTimeout(() => {
            currentIndex++;
            renderQuiz();
        }, 2200);
    }

    function use5050() {
        if (available5050 <= 0) return;
        let q = questions[currentIndex];
        let wrongKeys = ['A', 'B', 'C', 'D'].filter(k => k !== q.correct_option);
        wrongKeys.sort(() => Math.random() - 0.5);
        
        let removeKeys = wrongKeys.slice(0, 2);
        let allBtns = document.querySelectorAll('.quiz-option-btn');
        allBtns.forEach(b => {
            if (removeKeys.includes(b.dataset.key)) {
                b.style.visibility = 'hidden';
            }
        });

        available5050--;
        document.getElementById('count-5050').innerText = available5050;
        if(available5050 <= 0) document.getElementById('btn-5050').disabled = true;
    }

    function updateHeader() {
        document.getElementById('score-count').innerText = score;
        document.getElementById('streak-count').innerText = streak;
    }

    function finishGame() {
        clearInterval(timerInterval);
        if (document.getElementById('flashcard-game')) document.getElementById('flashcard-game').style.display = 'none';
        if (document.getElementById('quiz-game')) document.getElementById('quiz-game').style.display = 'none';
        
        document.getElementById('result-screen').classList.remove('d-none');
        document.getElementById('res-score').innerText = score;
        document.getElementById('res-max-streak').innerText = `${maxStreak}x`;

        confetti({ particleCount: 120, spread: 90, origin: { y: 0.6 } });
    }

    if (mode === 'flashcard') {
        renderFlashcard();
    } else {
        renderQuiz();
    }
</script>

<?php 
$footer_student = dirname(__DIR__) . '/includes/footer_student.php';
require_once file_exists($footer_student) ? $footer_student : dirname(__DIR__) . '/includes/footer.php';
?>