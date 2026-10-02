<?php
// admin/exam_questions.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$exam_id = intval($_GET['exam_id'] ?? 0);
if ($exam_id <= 0) {
    header('Location: exams.php');
    exit;
}

$stmtExam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmtExam->execute([$exam_id]);
$exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Đề thi không tồn tại!");
}

$message = '';
$message_type = '';

// ==========================================
// XỬ LÝ LƯU / SỬA CÂU HỎI (TƯƠNG THÍCH MỌI DẠNG)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action        = $_POST['action'] ?? '';
    $q_id          = intval($_POST['question_id'] ?? 0);
    $question_type = $_POST['question_type'] ?? 'single';
    $content       = trim($_POST['content'] ?? '');
    $difficulty    = $_POST['difficulty'] ?? 'easy';
    $explanation   = trim($_POST['explanation'] ?? '');
    $created_by    = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;

    $options_data = [];
    $correct_answer = '';

    // Chuẩn hóa loại câu hỏi về 'single'
    if (in_array($question_type, ['single', 'single_choice', 'multiple_choice'])) {
        $question_type = 'single';
    }

    if ($question_type === 'single') {
        $options_data = [
            'A' => trim($_POST['single_a'] ?? ''),
            'B' => trim($_POST['single_b'] ?? ''),
            'C' => trim($_POST['single_c'] ?? ''),
            'D' => trim($_POST['single_d'] ?? '')
        ];
        $correct_answer = strtoupper(trim($_POST['single_correct'] ?? 'A'));

    } elseif ($question_type === 'multiple') {
        $options_data = [
            'A' => trim($_POST['multi_a'] ?? ''),
            'B' => trim($_POST['multi_b'] ?? ''),
            'C' => trim($_POST['multi_c'] ?? ''),
            'D' => trim($_POST['multi_d'] ?? '')
        ];
        $corrects = $_POST['multi_correct'] ?? [];
        if (!is_array($corrects)) $corrects = [];
        sort($corrects);
        $correct_answer = json_encode($corrects);

    } elseif ($question_type === 'true_false') {
        $options_data = ['TRUE' => 'Đúng', 'FALSE' => 'Sai'];
        $correct_answer = $_POST['tf_correct'] ?? 'TRUE';

    } elseif ($question_type === 'fill_blank') {
        $options_data = [];
        $correct_answer = trim($_POST['fill_correct'] ?? '');

    } elseif ($question_type === 'matching') {
        $left_items  = $_POST['match_left'] ?? [];
        $right_items = $_POST['match_right'] ?? [];
        $pairs = [];
        if (is_array($left_items)) {
            foreach ($left_items as $idx => $left_val) {
                $l = trim($left_val);
                $r = trim($right_items[$idx] ?? '');
                if ($l !== '' || $r !== '') {
                    $pairs[] = ['left' => $l, 'right' => $r];
                }
            }
        }
        $options_data = $pairs;
        $correct_answer = 'MATCHING_AUTO';
    }

    $options_json = json_encode($options_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!empty($content)) {
        try {
            if ($action === 'add_question') {
                $pdo->beginTransaction();

                // Lưu song song cả cột options_json lẫn các cột cũ (nếu DB có) để tránh crash
                $sqlQ = "INSERT INTO questions (subject_id, created_by, question_type, content, options_json, correct_answer, explanation, difficulty, created_at)
                         VALUES (:subject_id, :created_by, :question_type, :content, :options_json, :correct_answer, :explanation, :difficulty, NOW())";
                $stmtQ = $pdo->prepare($sqlQ);
                $stmtQ->execute([
                    ':subject_id'     => $exam['subject_id'] ?? 1,
                    ':created_by'     => $created_by,
                    ':question_type'  => $question_type,
                    ':content'        => $content,
                    ':options_json'   => $options_json,
                    ':correct_answer' => $correct_answer,
                    ':explanation'    => $explanation,
                    ':difficulty'     => $difficulty
                ]);
                $question_id = $pdo->lastInsertId();

                $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(question_order), 0) FROM exam_questions WHERE exam_id = ?");
                $stmtMax->execute([$exam_id]);
                $maxOrder = intval($stmtMax->fetchColumn()) + 1;

                $stmtLink = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES (?, ?, ?)");
                $stmtLink->execute([$exam_id, $question_id, $maxOrder]);

                $stmtUpdateCount = $pdo->prepare("UPDATE exams SET total_questions = (SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?) WHERE id = ?");
                $stmtUpdateCount->execute([$exam_id, $exam_id]);

                $pdo->commit();
                header("Location: exam_questions.php?exam_id={$exam_id}&msg=added");
                exit;

            } elseif ($action === 'edit_question' && $q_id > 0) {
                $sqlEdit = "UPDATE questions 
                            SET question_type = :question_type, content = :content, 
                                options_json = :options_json, correct_answer = :correct_answer, 
                                explanation = :explanation, difficulty = :difficulty 
                            WHERE id = :id";
                $stmtEdit = $pdo->prepare($sqlEdit);
                $stmtEdit->execute([
                    ':question_type'  => $question_type,
                    ':content'        => $content,
                    ':options_json'   => $options_json,
                    ':correct_answer' => $correct_answer,
                    ':explanation'    => $explanation,
                    ':difficulty'     => $difficulty,
                    ':id'             => $q_id
                ]);

                header("Location: exam_questions.php?exam_id={$exam_id}&msg=updated");
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Lỗi Database: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Xóa câu hỏi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['q_id'])) {
    $q_id = intval($_GET['q_id']);
    $stmtDel = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ? AND question_id = ?");
    $stmtDel->execute([$exam_id, $q_id]);
    header("Location: exam_questions.php?exam_id={$exam_id}&msg=deleted");
    exit;
}

// Lấy danh sách câu hỏi
$sqlQuestions = "SELECT q.*, eq.question_order 
                 FROM questions q
                 INNER JOIN exam_questions eq ON q.id = eq.question_id
                 WHERE eq.exam_id = ?
                 ORDER BY eq.question_order ASC";
$stmtQ = $pdo->prepare($sqlQuestions);
$stmtQ->execute([$exam_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_admin.php';
?>

<style>
    :root {
        --purple-primary: #6f42c1;
        --purple-gradient: linear-gradient(135deg, #6f42c1 0%, #8540f5 100%);
        --purple-soft: #f3effb;
        --purple-border: #e2d9f3;
    }
    body { background-color: #f4f5f9; }
    .purple-gradient-card {
        background: var(--purple-gradient);
        color: #ffffff;
        border-radius: 1.25rem;
        box-shadow: 0 10px 25px rgba(111, 66, 193, 0.2);
    }
    .btn-purple { background: var(--purple-gradient); color: #fff !important; border: none; }
    .question-card { border: 1px solid var(--purple-border); border-radius: 1rem; background: #ffffff; }
    .option-box { border-radius: 0.75rem; padding: 0.75rem 1rem; border: 1px solid #e9ecef; background-color: #fcfcfd; }
    .option-box.correct { background-color: #e8f5e9 !important; border-color: #a5d6a7 !important; color: #1b5e20 !important; font-weight: 600; }
</style>

<div class="container-fluid px-4 py-4">
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Top Header -->
    <div class="purple-gradient-card admin-page-head p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-dark mb-2"><i class="bi bi-journal-text me-1"></i> Mã đề: <?= htmlspecialchars($exam['exam_code'] ?? 'N/A') ?></span>
                <h3 class="fw-bold mb-1 text-white"><?= htmlspecialchars($exam['title']) ?></h3>
                <p class="mb-0 text-white-50 small">Tự động nhận diện cấu trúc đáp án linh hoạt</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light text-purple fw-semibold rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#questionModal" onclick="prepareAddModal()">
                    <i class="bi bi-plus-circle-fill me-1"></i> Thêm Câu Hỏi Mới
                </button>
                <a href="exams.php" class="btn btn-outline-light rounded-3 px-3">Quay lại</a>
            </div>
        </div>
    </div>

    <!-- Danh sách câu hỏi -->
    <div class="row g-4">
        <?php foreach ($questions as $index => $q): ?>
            <?php 
                // ========================================================
                // 🛠 LOGIC TỰ ĐỘNG NHẬN DIỆN CẤU TRÚC ĐÁP ÁN (CŨ & MỚI)
                // ========================================================
                $rawType = strtolower($q['question_type'] ?? 'single');
                
                // 1. Chuẩn hóa Type
                if (in_array($rawType, ['single', 'single_choice', 'mcq', 'trac_nghiem'])) {
                    $qType = 'single';
                } elseif (in_array($rawType, ['multiple', 'multiple_choice'])) {
                    $qType = 'multiple';
                } else {
                    $qType = $rawType;
                }

                // 2. Tự động lấy danh sách đáp án A, B, C, D
                $options = [];
                if (!empty($q['options_json'])) {
                    $options = json_decode($q['options_json'], true);
                }
                
                // Nếu options_json rỗng, tự động lấy từ các cột CSDL cũ (option_a, option_b...)
                if (empty($options) || !is_array($options)) {
                    $options = [
                        'A' => $q['option_a'] ?? $q['option_A'] ?? $q['ans_a'] ?? '',
                        'B' => $q['option_b'] ?? $q['option_B'] ?? $q['ans_b'] ?? '',
                        'C' => $q['option_c'] ?? $q['option_C'] ?? $q['ans_c'] ?? '',
                        'D' => $q['option_d'] ?? $q['option_D'] ?? $q['ans_d'] ?? ''
                    ];
                }

                // Gán lại options chuẩn hóa cho JavaScript dùng trên Modal
                $q['options_json'] = json_encode($options, JSON_UNESCAPED_UNICODE);
                $q['question_type'] = $qType;
            ?>

            <div class="col-12">
                <div class="card question-card p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-purple bg-light px-3 py-1 rounded-3 border">Câu <?= $index + 1 ?></span>
                            <span class="badge bg-secondary">
                                <?= [
                                    'single'     => 'Trắc nghiệm (1 đáp án)',
                                    'multiple'   => 'Chọn nhiều đáp án',
                                    'true_false' => 'Đúng / Sai',
                                    'fill_blank' => 'Điền từ',
                                    'matching'   => 'Nối câu'
                                ][$qType] ?? $qType ?>
                            </span>
                        </div>
                        <div>
                            <script id="qdata-<?= $q['id'] ?>" type="application/json"><?= json_encode($q, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
                            <button class="btn btn-sm btn-light text-purple me-1" data-bs-toggle="modal" data-bs-target="#questionModal" onclick="prepareEditModal(<?= $q['id'] ?>)">
                                <i class="bi bi-pencil me-1"></i> Sửa
                            </button>
                            <a href="exam_questions.php?exam_id=<?= $exam_id ?>&action=delete&q_id=<?= $q['id'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Xóa câu hỏi này?')">
                                <i class="bi bi-trash me-1"></i> Xóa
                            </a>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-3"><?= nl2br(htmlspecialchars($q['content'])) ?></h5>

                    <!-- DẠNG 1: TRẮC NGHIỆM CHỌN 1 -->
                    <?php if ($qType === 'single'): ?>
                        <div class="row g-2">
                            <?php foreach (['A', 'B', 'C', 'D'] as $optKey): ?>
                                <?php if (isset($options[$optKey]) && $options[$optKey] !== ''): ?>
                                    <div class="col-md-6">
                                        <div class="option-box <?= strtoupper(trim($q['correct_answer'])) === $optKey ? 'correct' : '' ?>">
                                            <strong class="me-2"><?= $optKey ?>.</strong> <?= htmlspecialchars($options[$optKey]) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                    <!-- DẠNG 2: CHỌN NHIỀU ĐÁP ÁN -->
                    <?php elseif ($qType === 'multiple'): ?>
                        <?php $corrects = json_decode($q['correct_answer'] ?? '[]', true); if (!is_array($corrects)) $corrects = []; ?>
                        <div class="row g-2">
                            <?php foreach (['A', 'B', 'C', 'D'] as $optKey): ?>
                                <?php if (isset($options[$optKey]) && $options[$optKey] !== ''): ?>
                                    <div class="col-md-6">
                                        <div class="option-box <?= in_array($optKey, $corrects) ? 'correct' : '' ?>">
                                            <i class="bi <?= in_array($optKey, $corrects) ? 'bi-check-square-fill text-success' : 'bi-square' ?> me-2"></i>
                                            <strong class="me-1"><?= $optKey ?>.</strong> <?= htmlspecialchars($options[$optKey]) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                    <!-- DẠNG 3: ĐÚNG / SAI -->
                    <?php elseif ($qType === 'true_false'): ?>
                        <div class="d-flex gap-3">
                            <div class="option-box px-4 <?= strtoupper($q['correct_answer']) === 'TRUE' ? 'correct' : '' ?>">
                                <i class="bi bi-check-circle me-1"></i> ĐÚNG
                            </div>
                            <div class="option-box px-4 <?= strtoupper($q['correct_answer']) === 'FALSE' ? 'correct' : '' ?>">
                                <i class="bi bi-x-circle me-1"></i> SAI
                            </div>
                        </div>

                    <!-- DẠNG 4: ĐIỀN TỪ -->
                    <?php elseif ($qType === 'fill_blank'): ?>
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-secondary me-2">Đáp án đúng:</span>
                            <span class="badge bg-success fs-6 fw-normal"><?= htmlspecialchars($q['correct_answer']) ?></span>
                        </div>

                    <!-- DẠNG 5: NỐI CÂU -->
                    <?php elseif ($qType === 'matching'): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cột A (Vế trái)</th>
                                        <th>Cột B (Vế khớp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (is_array($options)): foreach ($options as $pair): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pair['left'] ?? '') ?></td>
                                            <td class="text-success fw-semibold"><i class="bi bi-arrow-right me-2"></i><?= htmlspecialchars($pair['right'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($q['explanation'])): ?>
                        <div class="mt-3 p-2 px-3 bg-light rounded-3 border-start border-3 border-warning small text-secondary">
                            <strong><i class="bi bi-lightbulb text-warning me-1"></i> Lời giải chi tiết:</strong> <?= htmlspecialchars($q['explanation']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL THÊM / SỬA CÂU HỎI -->
<div class="modal fade" id="questionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="exam_questions.php?exam_id=<?= $exam_id ?>" class="modal-content border-0 shadow">
            <input type="hidden" name="action" id="modal_action" value="add_question">
            <input type="hidden" name="question_id" id="modal_question_id" value="0">
            
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalTitle">Thêm Câu Hỏi Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Dạng câu hỏi</label>
                        <select class="form-select" name="question_type" id="question_type_select" onchange="switchQuestionType(this.value)">
                            <option value="single">1. Trắc nghiệm (Chọn 1 đáp án)</option>
                            <option value="multiple">2. Trắc nghiệm (Chọn nhiều đáp án)</option>
                            <option value="true_false">3. Đúng / Sai</option>
                            <option value="fill_blank">4. Điền từ vào chỗ trống</option>
                            <option value="matching">5. Nối câu (Cột A - Cột B)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Độ khó</label>
                        <select class="form-select" name="difficulty" id="modal_difficulty">
                            <option value="easy">Dễ</option>
                            <option value="medium" selected>Trung bình</option>
                            <option value="hard">Khó</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nội dung câu hỏi / Đề bài <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="content" id="modal_content" rows="3" required placeholder="Nhập câu hỏi..."></textarea>
                </div>

                <!-- BOX 1: SINGLE -->
                <div id="box_single" class="type-box">
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="text" class="form-control" name="single_a" id="single_a" placeholder="Đáp án A"></div>
                        <div class="col-6"><input type="text" class="form-control" name="single_b" id="single_b" placeholder="Đáp án B"></div>
                        <div class="col-6"><input type="text" class="form-control" name="single_c" id="single_c" placeholder="Đáp án C"></div>
                        <div class="col-6"><input type="text" class="form-control" name="single_d" id="single_d" placeholder="Đáp án D"></div>
                    </div>
                    <label class="form-label fw-semibold">Đáp án đúng</label>
                    <select class="form-select" name="single_correct" id="single_correct">
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                    </select>
                </div>

                <!-- BOX 2: MULTIPLE -->
                <div id="box_multiple" class="type-box d-none">
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="text" class="form-control" name="multi_a" id="multi_a" placeholder="Đáp án A"></div>
                        <div class="col-6"><input type="text" class="form-control" name="multi_b" id="multi_b" placeholder="Đáp án B"></div>
                        <div class="col-6"><input type="text" class="form-control" name="multi_c" id="multi_c" placeholder="Đáp án C"></div>
                        <div class="col-6"><input type="text" class="form-control" name="multi_d" id="multi_d" placeholder="Đáp án D"></div>
                    </div>
                    <label class="form-label fw-semibold">Chọn các đáp án đúng</label>
                    <div class="d-flex gap-3">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="multi_correct[]" value="A" id="mc_a"><label for="mc_a">A</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="multi_correct[]" value="B" id="mc_b"><label for="mc_b">B</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="multi_correct[]" value="C" id="mc_c"><label for="mc_c">C</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="multi_correct[]" value="D" id="mc_d"><label for="mc_d">D</label></div>
                    </div>
                </div>

                <!-- BOX 3: TRUE / FALSE -->
                <div id="box_true_false" class="type-box d-none">
                    <label class="form-label fw-semibold">Mệnh đề này là:</label>
                    <select class="form-select" name="tf_correct" id="tf_correct">
                        <option value="TRUE">ĐÚNG</option>
                        <option value="FALSE">SAI</option>
                    </select>
                </div>

                <!-- BOX 4: FILL BLANK -->
                <div id="box_fill_blank" class="type-box d-none">
                    <label class="form-label fw-semibold">Đáp án từ/cụm từ chính xác</label>
                    <input type="text" class="form-control" name="fill_correct" id="fill_correct" placeholder="Nhập đáp án...">
                </div>

                <!-- BOX 5: MATCHING -->
                <div id="box_matching" class="type-box d-none">
                    <label class="form-label fw-semibold">Cặp vế A - vế B khớp nhau</label>
                    <div id="matching_wrapper"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addMatchPair()"><i class="bi bi-plus-lg"></i> Thêm cặp nối</button>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-semibold">Lời giải chi tiết (Tùy chọn)</label>
                    <textarea class="form-control" name="explanation" id="modal_explanation" rows="2"></textarea>
                </div>
            </div>

            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-purple px-4">Lưu Câu Hỏi</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchQuestionType(type) {
    document.querySelectorAll('.type-box').forEach(el => el.classList.add('d-none'));
    const targetBox = document.getElementById('box_' + type);
    if (targetBox) targetBox.classList.remove('d-none');

    if (type === 'matching' && document.getElementById('matching_wrapper').children.length === 0) {
        addMatchPair();
        addMatchPair();
    }
}

function addMatchPair(left = '', right = '') {
    const wrapper = document.getElementById('matching_wrapper');
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 align-items-center match-row';
    div.innerHTML = `
        <div class="col-5"><input type="text" class="form-control" name="match_left[]" value="${escapeHtml(left)}" placeholder="Vế A"></div>
        <div class="col-1 text-center"><i class="bi bi-arrow-right text-purple"></i></div>
        <div class="col-5"><input type="text" class="form-control" name="match_right[]" value="${escapeHtml(right)}" placeholder="Vế B"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-light text-danger rounded-circle" onclick="this.closest('.match-row').remove()"><i class="bi bi-x"></i></button></div>
    `;
    wrapper.appendChild(div);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function prepareAddModal() {
    document.getElementById('modal_action').value = 'add_question';
    document.getElementById('modal_question_id').value = '0';
    document.getElementById('modalTitle').innerText = 'Thêm Câu Hỏi Mới';
    document.getElementById('modal_content').value = '';
    document.getElementById('modal_explanation').value = '';
    document.getElementById('question_type_select').value = 'single';
    document.getElementById('matching_wrapper').innerHTML = '';
    
    ['single_a', 'single_b', 'single_c', 'single_d', 'multi_a', 'multi_b', 'multi_c', 'multi_d', 'fill_correct'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    ['mc_a', 'mc_b', 'mc_c', 'mc_d'].forEach(id => {
        const el = document.getElementById(id); if (el) el.checked = false;
    });

    switchQuestionType('single');
}

function prepareEditModal(qId) {
    const scriptTag = document.getElementById('qdata-' + qId);
    if (!scriptTag) return;

    const q = JSON.parse(scriptTag.textContent);
    let options = {};
    try {
        options = typeof q.options_json === 'string' ? JSON.parse(q.options_json || '{}') : (q.options_json || {});
    } catch(e) { options = {}; }

    const type = q.question_type || 'single';

    document.getElementById('modal_action').value = 'edit_question';
    document.getElementById('modal_question_id').value = q.id;
    document.getElementById('modalTitle').innerText = 'Chỉnh Sửa Câu Hỏi';
    document.getElementById('modal_content').value = q.content || '';
    document.getElementById('modal_explanation').value = q.explanation || '';
    document.getElementById('modal_difficulty').value = q.difficulty || 'medium';
    document.getElementById('question_type_select').value = type;

    switchQuestionType(type);

    if (type === 'single') {
        document.getElementById('single_a').value = options['A'] || '';
        document.getElementById('single_b').value = options['B'] || '';
        document.getElementById('single_c').value = options['C'] || '';
        document.getElementById('single_d').value = options['D'] || '';
        document.getElementById('single_correct').value = (q.correct_answer || 'A').toUpperCase();
    } else if (type === 'multiple') {
        document.getElementById('multi_a').value = options['A'] || '';
        document.getElementById('multi_b').value = options['B'] || '';
        document.getElementById('multi_c').value = options['C'] || '';
        document.getElementById('multi_d').value = options['D'] || '';
        
        let corrects = [];
        try { corrects = JSON.parse(q.correct_answer || '[]'); } catch(e) { corrects = []; }
        ['A', 'B', 'C', 'D'].forEach(k => {
            const el = document.getElementById('mc_' + k.toLowerCase());
            if (el) el.checked = corrects.includes(k);
        });
    } else if (type === 'true_false') {
        document.getElementById('tf_correct').value = (q.correct_answer || 'TRUE').toUpperCase();
    } else if (type === 'fill_blank') {
        document.getElementById('fill_correct').value = q.correct_answer || '';
    } else if (type === 'matching') {
        const wrapper = document.getElementById('matching_wrapper');
        wrapper.innerHTML = '';
        if (Array.isArray(options)) {
            options.forEach(p => addMatchPair(p.left || '', p.right || ''));
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>