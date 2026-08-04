<?php
// includes/data.php

require_once __DIR__ . '/config.php';

/* ==========================
   SUBJECTS
========================== */

function getSubjects()
{
    global $pdo;

    return $pdo->query("
        SELECT *
        FROM subjects
        ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/* ==========================
   EXAMS
========================== */

function getExams()
{
    global $pdo;

    return $pdo->query("
        SELECT
            e.*,
            s.name AS subject_name
        FROM exams e
        LEFT JOIN subjects s
            ON s.id = e.subject_id
        ORDER BY e.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getExamById($id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            e.*,
            s.name AS subject_name
        FROM exams e
        LEFT JOIN subjects s
            ON s.id=e.subject_id
        WHERE e.id=?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================
   QUESTIONS
========================== */

function getQuestions()
{
    global $pdo;

    return $pdo->query("
        SELECT
            q.*,
            s.name AS subject_name
        FROM questions q
        LEFT JOIN subjects s
            ON s.id=q.subject_id
        ORDER BY q.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getQuestionsByExam($exam_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            q.*,
            eq.question_order,
            eq.score
        FROM exam_questions eq
        INNER JOIN questions q
            ON q.id=eq.question_id
        WHERE eq.exam_id=?
        ORDER BY eq.question_order
    ");

    $stmt->execute([$exam_id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ==========================
   USERS
========================== */

function getUsers()
{
    global $pdo;

    return $pdo->query("
        SELECT
            id,
            name,
            email,
            role,
            status,
            student_code,
            created_at
        FROM users
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/* ==========================
   ROOMS
========================== */

function getRooms()
{
    global $pdo;

    return $pdo->query("
        SELECT
            r.*,
            e.title AS exam_title,
            (
                SELECT COUNT(*)
                FROM room_members rm
                WHERE rm.room_id=r.id
            ) AS current_students
        FROM exam_rooms r
        LEFT JOIN exams e
            ON e.id=r.exam_id
        ORDER BY r.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/* ==========================
   LEADERBOARD
========================== */

function getLeaderboard()
{
    global $pdo;

    return $pdo->query("
        SELECT
            u.id,
            u.name,
            u.email,
            u.student_code,

            COUNT(a.id) AS exam_count,

            ROUND(AVG(a.score),2) AS avg_score,

            MAX(a.score) AS best_score

        FROM users u

        LEFT JOIN exam_attempts a
            ON a.student_id=u.id
            AND a.status='submitted'

        WHERE u.role='student'

        GROUP BY u.id

        ORDER BY avg_score DESC,
                 best_score DESC

        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/* ==========================
   DASHBOARD STATS
========================== */

function getStats()
{
    global $pdo;

    return [

        'subjects'=>(int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),

        'exams'=>(int)$pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn(),

        'questions'=>(int)$pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),

        'users'=>(int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),

        'results'=>(int)$pdo->query("
            SELECT COUNT(*)
            FROM exam_attempts
            WHERE status='submitted'
        ")->fetchColumn()

    ];
}

/* ==========================
   SAVE RESULT
========================== */

function saveResult(
    $user_id,
    $exam_id,
    $score,
    $correct,
    $total,
    $time_taken,
    $answers = []
)
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO exam_attempts
        (
            room_id,
            exam_id,
            student_id,
            started_at,
            submitted_at,
            duration_seconds,
            score,
            total_questions,
            correct_answers,
            wrong_answers,
            unanswered,
            percentage,
            status,
            answers_json
        )
        VALUES
        (
            NULL,
            ?,
            ?,
            NOW(),
            NOW(),
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'submitted',
            ?
        )
    ");

    $unanswered = 0;
    foreach ($answers as $ans) {
        if ($ans === '' || $ans === null) {
            $unanswered++;
        }
    }

    $wrong = max(0, $total - $correct - $unanswered);

    $percentage = $total > 0
        ? round(($correct / $total) * 100, 2)
        : 0;

    return $stmt->execute([
        $exam_id,
        $user_id,
        $time_taken,
        $score,
        $total,
        $correct,
        $wrong,
        $unanswered,
        $percentage,
        json_encode($answers, JSON_UNESCAPED_UNICODE)
    ]);
}

/* ==========================
   HISTORY
========================== */

function getHistory($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            a.*,
            e.title AS exam_title,
            s.name AS subject_name
        FROM exam_attempts a

        INNER JOIN exams e
            ON e.id=a.exam_id

        LEFT JOIN subjects s
            ON s.id=e.subject_id

        WHERE a.student_id=?

        ORDER BY a.submitted_at DESC
    ");

    $stmt->execute([$user_id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getExamsBySubject($subject_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            e.*,
            s.name AS subject_name,
            (
                SELECT COUNT(*)
                FROM questions q
                WHERE q.subject_id = e.id
            ) AS question_count
        FROM exams e
        LEFT JOIN subjects s
            ON s.id = e.subject_id
        WHERE e.subject_id = ?
        ORDER BY e.created_at DESC
    ");

    $stmt->execute([$subject_id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getSubject($id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT *
        FROM subjects
        WHERE id=?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
/**
 * Lấy danh sách lịch sử làm bài thi của 1 sinh viên
 */
function getHistoryByUser($student_id) {
    global $pdo;
    
    if (isset($pdo)) {
        // Đã sửa h.user_id thành h.student_id
        $stmt = $pdo->prepare("
            SELECT h.*, e.title AS exam_title 
            FROM exam_attempts h
            LEFT JOIN exams e ON h.exam_id = e.id
            WHERE h.student_id = ?
            ORDER BY h.id DESC
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll();
    }
    
    return [];
}