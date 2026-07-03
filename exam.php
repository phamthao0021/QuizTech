<?php
// exam.php - Làm bài thi
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: auth.php');
    exit();
}

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($exam_id <= 0) {
    header('Location: exams.php');
    exit();
}

// Lấy thông tin đề thi
$stmt = $pdo->prepare("SELECT e.*, s.name as subject_name FROM exams e JOIN subjects s ON e.subject_id = s.id WHERE e.id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: exams.php');
    exit();
}

// Lấy câu hỏi
$questions = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
$questions->execute([$exam_id]);
$questions = $questions->fetchAll();

if (empty($questions)) {
    die('Đề thi chưa có câu hỏi!');
}

// Kiểm tra bài làm đang làm dở
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE user_id = ? AND exam_id = ? AND is_completed = 0 ORDER BY started_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $exam_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    // Tạo bài làm mới
    $answers = json_encode(array_fill(0, count($questions), null));
    $stmt = $pdo->prepare("INSERT INTO exam_attempts (user_id, exam_id, total_questions, answers) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $exam_id, count($questions), $answers]);
    $attempt_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ?");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
}

$attempt_id = $attempt['id'];
$answers = json_decode($attempt['answers'], true);
$total_questions = count($questions);
$time_limit = $exam['time_limit'] * 60; // Đổi sang giây
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Làm bài thi - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .exam-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .timer-badge {
            font-size: 28px;
            font-weight: 700;
            color: #dc3545;
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
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .question-nav .nav-btn.current {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-code-square"></i> QuizTech
            </a>
            <div class="text-white">
                <span class="me-3"><?= htmlspecialchars($exam['title']) ?></span>
                <span class="timer-badge" id="timerDisplay">
                    <?= str_pad($exam['time_limit'], 2, '0', STR_PAD_LEFT) ?>:00
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="exam-container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6>Câu hỏi <span id="currentQuestion">1</span>/<?= $total_questions ?></h6>
                                <span class="badge bg-primary">Điểm: <span id="currentScore">0</span></span>
                            </div>
                            
                            <div id="questionContainer">
                                <!-- Câu hỏi sẽ được load bằng JS -->
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button class="btn btn-outline-secondary" id="prevBtn" disabled>
                                    <i class="bi bi-chevron-left"></i> Trước
                                </button>
                                <button class="btn btn-primary" id="nextBtn">
                                    Tiếp <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6>Danh sách câu hỏi</h6>
                            <div class="question-nav" id="questionNav">
                                <!-- Navigation sẽ được generate -->
                            </div>
                            <hr>
                            <button class="btn btn-success w-100" onclick="submitExam()">
                                <i class="bi bi-check-circle"></i> Nộp bài
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dữ liệu từ PHP
        const questions = <?= json_encode($questions) ?>;
        const examId = <?= $exam_id ?>;
        const attemptId = <?= $attempt_id ?>;
        const totalQuestions = <?= $total_questions ?>;
        const timeLimit = <?= $time_limit ?>;
        
        let currentQuestion = 0;
        let answers = <?= json_encode($answers) ?>;
        let timer = timeLimit;
        let timerInterval;

        // Render câu hỏi
        function renderQuestion(index) {
            const q = questions[index];
            if (!q) return;
            
            const container = document.getElementById('questionContainer');
            container.innerHTML = `
                <h5 class="mb-3">Câu hỏi ${index + 1}:</h5>
                <p class="fw-bold">${q.question_text}</p>
                <div class="mt-3">
                    ${['A', 'B', 'C', 'D'].map((letter, i) => {
                        const option = q['option_' + letter.toLowerCase()];
                        if (!option) return '';
                        const isSelected = answers[index] === i;
                        return `
                            <button class="option-btn ${isSelected ? 'selected' : ''}" 
                                    data-index="${i}" 
                                    onclick="selectOption(${i})">
                                ${letter}. ${option}
                            </button>
                        `;
                    }).join('')}
                </div>
            `;
            
            document.getElementById('currentQuestion').textContent = index + 1;
            updateNav();
            updateButtons();
            updateScore();
        }

        // Chọn đáp án
        function selectOption(index) {
            answers[currentQuestion] = index;
            renderQuestion(currentQuestion);
            saveProgress();
        }

        // Cập nhật navigation
        function updateNav() {
            const nav = document.getElementById('questionNav');
            nav.innerHTML = questions.map((q, i) => `
                <button class="nav-btn ${answers[i] !== null ? 'answered' : ''} ${i === currentQuestion ? 'current' : ''}" 
                        onclick="goToQuestion(${i})">
                    ${i + 1}
                </button>
            `).join('');
        }

        // Cập nhật nút điều hướng
        function updateButtons() {
            document.getElementById('prevBtn').disabled = currentQuestion === 0;
            const nextBtn = document.getElementById('nextBtn');
            if (currentQuestion === totalQuestions - 1) {
                nextBtn.innerHTML = '<i class="bi bi-check-circle"></i> Nộp bài';
                nextBtn.className = 'btn btn-success';
                nextBtn.onclick = submitExam;
            } else {
                nextBtn.innerHTML = 'Tiếp <i class="bi bi-chevron-right"></i>';
                nextBtn.className = 'btn btn-primary';
                nextBtn.onclick = () => goToQuestion(currentQuestion + 1);
            }
        }

        // Cập nhật điểm
        function updateScore() {
            let score = 0;
            questions.forEach((q, i) => {
                if (answers[i] !== null && answers[i] === q.correct_answer) {
                    score++;
                }
            });
            document.getElementById('currentScore').textContent = score;
        }

        // Đi đến câu hỏi
        function goToQuestion(index) {
            if (index >= 0 && index < totalQuestions) {
                currentQuestion = index;
                renderQuestion(index);
            }
        }

        // Lưu tiến trình
        function saveProgress() {
            fetch('api/save-exam.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    attempt_id: attemptId,
                    answers: answers
                })
            });
        }

        // Bắt đầu đồng hồ
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
                
                if (timer === 60) {
                    alert('⚠️ Còn 1 phút! Hãy nộp bài sớm.');
                }
            }, 1000);
        }

        // Nộp bài
        function submitExam() {
            if (!confirm('Bạn có chắc muốn nộp bài?')) return;
            
            clearInterval(timerInterval);
            
            // Tính điểm
            let score = 0;
            questions.forEach((q, i) => {
                if (answers[i] !== null && answers[i] === q.correct_answer) {
                    score++;
                }
            });
            
            const timeTaken = timeLimit - timer;
            
            fetch('api/submit-exam.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    attempt_id: attemptId,
                    answers: answers,
                    score: score,
                    time_taken: timeTaken
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Bạn đã hoàn thành bài thi!\nĐiểm: ${score}/${totalQuestions}`);
                    window.location.href = 'dashboard.php';
                }
            })
            .catch(() => {
                alert('Có lỗi xảy ra khi nộp bài!');
            });
        }

        // Khởi tạo
        renderQuestion(0);
        startTimer();
    </script>
</body>
</html>