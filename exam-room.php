<?php
// exam-room.php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$exam_id = isset($_GET['exam']) ? (int)$_GET['exam'] : 0;
$attempt_id = isset($_GET['attempt']) ? (int)$_GET['attempt'] : 0;

if ($exam_id > 0) {
    $exam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $exam->execute([$exam_id]);
    $exam = $exam->fetch();
    
    if (!$exam) {
        header('Location: /QuizTech/exams.php');
        exit();
    }
    
    $questions = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
    $questions->execute([$exam_id]);
    $questions = $questions->fetchAll();
    
    // Create or get attempt
    if ($attempt_id > 0) {
        $attempt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ? AND user_id = ?");
        $attempt->execute([$attempt_id, $_SESSION['user_id']]);
        $attempt = $attempt->fetch();
    } else {
        // Check for existing incomplete attempt
        $stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE user_id = ? AND exam_id = ? AND is_completed = 0");
        $stmt->execute([$_SESSION['user_id'], $exam_id]);
        $attempt = $stmt->fetch();
        
        if (!$attempt) {
            // Create new attempt
            $stmt = $pdo->prepare("INSERT INTO exam_attempts (user_id, exam_id, total_questions, answers) VALUES (?, ?, ?, ?)");
            $answers = json_encode(array_fill(0, count($questions), null));
            $stmt->execute([$_SESSION['user_id'], $exam_id, count($questions), $answers]);
            $attempt_id = $pdo->lastInsertId();
            $attempt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ?");
            $attempt->execute([$attempt_id]);
            $attempt = $attempt->fetch();
        }
    }
    
    if (!$attempt) {
        header('Location: /QuizTech/exams.php');
        exit();
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
.timer-badge {
    font-size: 28px;
    font-weight: 700;
    color: #ff4757;
}
.option-btn {
    width: 100%;
    text-align: left;
    padding: 12px 20px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    background: white;
    transition: all 0.3s ease;
    margin-bottom: 10px;
}
.option-btn:hover {
    border-color: #667eea;
    background: #f8f9ff;
}
.option-btn.selected {
    border-color: #667eea;
    background: #667eea;
    color: white;
}
.option-btn.correct {
    border-color: #28a745;
    background: #d4edda;
}
.option-btn.wrong {
    border-color: #dc3545;
    background: #f8d7da;
}
.question-nav {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
}
.question-nav .nav-btn {
    padding: 8px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}
.question-nav .nav-btn:hover {
    border-color: #667eea;
}
.question-nav .nav-btn.answered {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
.question-nav .nav-btn.current {
    border-color: #ff4757;
    background: #fff5f5;
}
</style>

<div class="header">
    <h4><i class="bi bi-people-fill"></i> Phòng thi</h4>
    <div class="user-info">
        <span>Xin chào, <?= htmlspecialchars($user['name']) ?></span>
        <div class="user-avatar"><?= substr($user['name'], 0, 1) ?></div>
        <a href="/QuizTech/logout.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i> Đăng xuất
        </a>
    </div>
</div>

<?php if ($exam_id > 0 && $exam && $attempt): ?>
<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6><?= htmlspecialchars($exam['title']) ?></h6>
                        <span class="badge bg-primary">Câu <span id="currentQuestion">1</span>/<?= count($questions) ?></span>
                    </div>
                    <div>
                        <span class="timer-badge" id="timerDisplay">
                            <?= str_pad($exam['time_limit'], 2, '0', STR_PAD_LEFT) ?>:00
                        </span>
                        <span class="text-muted ms-1">còn lại</span>
                    </div>
                </div>
                
                <div id="questionContainer">
                    <!-- Questions will be loaded dynamically -->
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-outline-secondary" id="prevBtn" disabled>
                        <i class="bi bi-chevron-left"></i> Trước
                    </button>
                    <button class="btn btn-primary-custom" id="nextBtn">
                        Tiếp <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6>Danh sách câu hỏi</h6>
                <div class="question-nav" id="questionNav">
                    <!-- Navigation buttons will be generated -->
                </div>
                <hr>
                <button class="btn btn-success w-100" onclick="submitExam()">
                    <i class="bi bi-check-circle"></i> Nộp bài
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const questions = <?= json_encode($questions) ?>;
const examId = <?= $exam_id ?>;
const attemptId = <?= $attempt['id'] ?>;
let currentQuestion = 0;
let answers = <?= $attempt['answers'] ?: json_encode(array_fill(0, count($questions), null)) ?>;
let timer = <?= $exam['time_limit'] * 60 ?>;
let timerInterval;

function renderQuestion(index) {
    const q = questions[index];
    if (!q) return;
    
    const container = document.getElementById('questionContainer');
    container.innerHTML = `
        <h5 class="mb-3">Câu hỏi ${index + 1}:</h5>
        <p class="fw-bold">${q.question_text}</p>
        <div class="mt-3">
            ${['A', 'B', 'C', 'D'].map((letter, i) => `
                <button class="option-btn" data-index="${i}" onclick="selectOption(${i})">
                    ${letter}. ${q['option_' + letter.toLowerCase()]}
                </button>
            `).join('')}
        </div>
    `;
    
    // Highlight selected answer
    if (answers[index] !== null) {
        const btns = container.querySelectorAll('.option-btn');
        btns.forEach((btn, i) => {
            if (i === answers[index]) {
                btn.classList.add('selected');
            }
        });
    }
    
    // Update navigation
    document.getElementById('currentQuestion').textContent = index + 1;
    updateNav();
    updateButtons();
}

function selectOption(index) {
    answers[currentQuestion] = index;
    renderQuestion(currentQuestion);
    
    // Save to server
    saveProgress();
}

function updateNav() {
    const nav = document.getElementById('questionNav');
    nav.innerHTML = questions.map((q, i) => `
        <button class="nav-btn ${answers[i] !== null ? 'answered' : ''} ${i === currentQuestion ? 'current' : ''}" 
                onclick="goToQuestion(${i})">
            ${i + 1}
        </button>
    `).join('');
}

function updateButtons() {
    document.getElementById('prevBtn').disabled = currentQuestion === 0;
    const nextBtn = document.getElementById('nextBtn');
    if (currentQuestion === questions.length - 1) {
        nextBtn.innerHTML = '<i class="bi bi-check-circle"></i> Nộp bài';
        nextBtn.className = 'btn btn-success';
        nextBtn.onclick = submitExam;
    } else {
        nextBtn.innerHTML = 'Tiếp <i class="bi bi-chevron-right"></i>';
        nextBtn.className = 'btn btn-primary-custom';
        nextBtn.onclick = () => goToQuestion(currentQuestion + 1);
    }
}

function goToQuestion(index) {
    if (index >= 0 && index < questions.length) {
        currentQuestion = index;
        renderQuestion(index);
    }
}

function saveProgress() {
    fetch('/QuizTech/api/submit-exam.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'save',
            attempt_id: attemptId,
            answers: answers
        })
    });
}

function startTimer() {
    timerInterval = setInterval(() => {
        timer--;
        const mins = Math.floor(timer / 60);
        const secs = timer % 60;
        document.getElementById('timerDisplay').textContent = 
            `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        
        if (timer <= 0) {
            clearInterval(timerInterval);
            submitExam();
        }
        
        if (timer === 300) {
            alert('⚠️ Còn 5 phút! Hãy nộp bài sớm.');
        }
    }, 1000);
}

function submitExam() {
    if (!confirm('Bạn có chắc muốn nộp bài?')) return;
    
    clearInterval(timerInterval);
    
    // Calculate score
    let score = 0;
    questions.forEach((q, i) => {
        if (answers[i] !== null && answers[i] === q.correct_answer) {
            score++;
        }
    });
    
    const timeTaken = <?= $exam['time_limit'] * 60 ?> - timer;
    
    fetch('/QuizTech/api/submit-exam.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'submit',
            attempt_id: attemptId,
            answers: answers,
            score: score,
            time_taken: timeTaken
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`🎉 Bạn đã hoàn thành bài thi! Điểm: ${score}/${questions.length}`);
            window.location.href = '/QuizTech/dashboard.php';
        }
    });
}

// Initialize
renderQuestion(0);
startTimer();
</script>

<?php else: ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Không tìm thấy đề thi.
    <a href="/QuizTech/exams.php" class="alert-link">Quay lại danh sách đề thi</a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>