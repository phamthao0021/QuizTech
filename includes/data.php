    <?php
    // includes/data.php

    require_once __DIR__ . '/config.php';

    /* ==========================
    SUBJECTS (MÔN HỌC)
    ========================== */

    if (!function_exists('getSubjects')) {
        function getSubjects($teacher_id = null) {
            global $pdo;
            try {
                if ($teacher_id !== null) {
                    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE created_by = ? ORDER BY name ASC");
                    $stmt->execute([$teacher_id]);
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                return $pdo->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return [];
            }
        }
    }

    if (!function_exists('getSubject')) {
        function getSubject($id) {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    /* ==========================
    EXAMS (ĐỀ THI)
    ========================== */

    if (!function_exists('getExams')) {
        function getExams($teacher_id = null) {
            global $pdo;
            if ($teacher_id !== null) {
                $stmt = $pdo->prepare("
                    SELECT e.*, s.name AS subject_name 
                    FROM exams e 
                    LEFT JOIN subjects s ON s.id = e.subject_id 
                    WHERE e.created_by = ?
                    ORDER BY e.id DESC
                ");
                $stmt->execute([$teacher_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $pdo->query("
                SELECT e.*, s.name AS subject_name 
                FROM exams e 
                LEFT JOIN subjects s ON s.id = e.subject_id 
                ORDER BY e.id DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!function_exists('getExamById')) {
        function getExamById($id) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT e.*, s.name AS subject_name 
                FROM exams e 
                LEFT JOIN subjects s ON s.id = e.subject_id 
                WHERE e.id = ? 
                LIMIT 1
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if (!function_exists('getExamsBySubject')) {
        function getExamsBySubject($subject_id) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT e.*, s.name AS subject_name,
                    (SELECT COUNT(*) FROM questions q WHERE q.subject_id = e.subject_id) AS question_count
                FROM exams e
                LEFT JOIN subjects s ON s.id = e.subject_id
                WHERE e.subject_id = ?
                ORDER BY e.created_at DESC
            ");
            $stmt->execute([$subject_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /* ==========================
    QUESTIONS (CÂU HỎI)
    ========================== */

    if (!function_exists('getQuestions')) {
        function getQuestions($teacher_id = null) {
            global $pdo;
            if ($teacher_id !== null) {
                $stmt = $pdo->prepare("
                    SELECT q.*, s.name AS subject_name 
                    FROM questions q 
                    LEFT JOIN subjects s ON s.id = q.subject_id 
                    WHERE q.user_id = ? OR q.created_by = ?
                    ORDER BY q.id DESC
                ");
                $stmt->execute([$teacher_id, $teacher_id]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rows = $pdo->query("
                    SELECT q.*, s.name AS subject_name 
                    FROM questions q 
                    LEFT JOIN subjects s ON s.id = q.subject_id 
                    ORDER BY q.id DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
            }

            // Tự động chuẩn hóa dữ liệu trả về cho 2 tên cột (type/content)
            return array_map('formatQuestionRow', $rows);
        }
    }

    if (!function_exists('getQuestionsByExam')) {
        function getQuestionsByExam($exam_id) {
            global $pdo;
            
            // Kiểm tra xem bảng trung gian exam_questions hay truy vấn trực tiếp bảng questions
            try {
                $stmt = $pdo->prepare("
                    SELECT q.*, eq.question_order, eq.score 
                    FROM exam_questions eq
                    INNER JOIN questions q ON q.id = eq.question_id
                    WHERE eq.exam_id = ?
                    ORDER BY eq.question_order ASC
                ");
                $stmt->execute([$exam_id]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    return array_map('formatQuestionRow', $rows);
                }
            } catch (PDOException $e) {}

            // Fallback truy vấn trực tiếp bảng questions
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
            $stmt->execute([$exam_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map('formatQuestionRow', $rows);
        }
    }

    /* ==========================
    USERS (NGƯỜI DÙNG)
    ========================== */

    if (!function_exists('getUsers')) {
        function getUsers($role = null) {
            global $pdo;
            $sql = "
                SELECT id, name, email, username, avatar, role, status, is_protected,
                       student_code, teacher_code, phone, last_login, created_at
                FROM users
                WHERE deleted_at IS NULL
            ";
            if ($role !== null) {
                $stmt = $pdo->prepare($sql . " AND role = ? ORDER BY created_at DESC");
                $stmt->execute([$role]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rows = $pdo->query($sql . " ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            }
            // Alias tương thích UI cũ (fullname)
            foreach ($rows as &$u) {
                $u['fullname'] = $u['name'] ?? '';
                if (empty($u['username'])) {
                    $u['username'] = strtolower(explode('@', (string)($u['email'] ?? 'user'))[0]);
                }
            }
            unset($u);
            return $rows;
        }
    }

    if (!function_exists('getUserById')) {
        function getUserById($id) {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([(int)$id]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                $u['fullname'] = $u['name'] ?? '';
            }
            return $u ?: null;
        }
    }

    if (!function_exists('addUser')) {
        function addUser(array $data) {
            global $pdo;
            $name     = trim((string)($data['fullname'] ?? $data['name'] ?? ''));
            $email    = trim((string)($data['email'] ?? ''));
            $username = trim((string)($data['username'] ?? ''));
            $password = (string)($data['password'] ?? '');
            $role     = function_exists('normalizeRole')
                ? normalizeRole($data['role'] ?? 'student')
                : strtolower(trim((string)($data['role'] ?? 'student')));
            $avatar   = trim((string)($data['avatar'] ?? ''));
            $statusRaw = $data['status'] ?? 'active';
            $status    = in_array($statusRaw, ['active', 'inactive', 'blocked'], true)
                ? $statusRaw : 'active';

            if ($name === '' || $password === '') {
                return false;
            }
            if ($username === '') {
                $username = $email !== ''
                    ? strtolower(explode('@', $email)[0])
                    : ('user' . substr(md5($name . microtime()), 0, 8));
            }
            if ($email === '') {
                $email = $username . '@quiztech.local';
            }
            if (function_exists('can_assign_role') && !can_assign_role($role)) {
                return false;
            }
            // Không cho tạo Super Admin protected qua addUser thường
            $is_protected = 0;

            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, username, password_hash, role, avatar, status, is_protected, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                return $stmt->execute([$name, $email, $username, $hash, $role, $avatar ?: null, $status, $is_protected]);
            } catch (PDOException $e) {
                error_log('addUser: ' . $e->getMessage());
                return false;
            }
        }
    }

    if (!function_exists('updateUser')) {
        function updateUser($id, array $data) {
            global $pdo;
            $id = (int)$id;
            $existing = getUserById($id);
            if (!$existing) {
                return false;
            }
            if (function_exists('can_manage_user') && !can_manage_user($existing, 'edit')) {
                return false;
            }

            $name   = trim((string)($data['fullname'] ?? $data['name'] ?? $existing['name']));
            $email  = trim((string)($data['email'] ?? $existing['email']));
            $avatar = array_key_exists('avatar', $data) ? trim((string)$data['avatar']) : ($existing['avatar'] ?? '');
            $role   = array_key_exists('role', $data)
                ? (function_exists('normalizeRole') ? normalizeRole($data['role']) : $data['role'])
                : $existing['role'];

            if (array_key_exists('role', $data)) {
                if (function_exists('can_manage_user') && !can_manage_user($existing, 'change_role')) {
                    $role = $existing['role'];
                } elseif (function_exists('can_assign_role') && !can_assign_role($role)) {
                    return false;
                }
            }

            // Không cho admin_test / bất kỳ ai ngoài Super Admin đổi is_protected
            $is_protected = (int)($existing['is_protected'] ?? 0);
            if (array_key_exists('is_protected', $data) && function_exists('can_manage_user') && can_manage_user($existing, 'set_protected')) {
                $is_protected = (int)!empty($data['is_protected']);
            }

            $fields = ['name = ?', 'email = ?', 'role = ?', 'avatar = ?', 'is_protected = ?'];
            $params = [$name, $email, $role, $avatar !== '' ? $avatar : null, $is_protected];

            if (!empty($data['password'])) {
                $fields[] = 'password_hash = ?';
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if (isset($data['status']) && in_array($data['status'], ['active', 'inactive', 'blocked'], true)) {
                if (function_exists('can_manage_user') && !can_manage_user($existing, 'lock')) {
                    // bỏ qua status nếu không có quyền
                } else {
                    $fields[] = 'status = ?';
                    $params[] = $data['status'];
                }
            }
            if (isset($data['username']) && trim($data['username']) !== '') {
                $fields[] = 'username = ?';
                $params[] = trim($data['username']);
            }

            $params[] = $id;
            try {
                $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ? AND deleted_at IS NULL';
                $stmt = $pdo->prepare($sql);
                return $stmt->execute($params);
            } catch (PDOException $e) {
                error_log('updateUser: ' . $e->getMessage());
                return false;
            }
        }
    }

    if (!function_exists('deleteUser')) {
        function deleteUser($id) {
            global $pdo;
            $id = (int)$id;
            $existing = getUserById($id);
            if (!$existing) {
                return false;
            }
            if (function_exists('can_manage_user') && !can_manage_user($existing, 'delete')) {
                return false;
            }
            try {
                // Soft delete để giữ toàn vẹn lịch sử; fallback hard delete nếu cột thiếu
                $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW(), status = 'inactive' WHERE id = ?");
                return $stmt->execute([$id]);
            } catch (PDOException $e) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    return $stmt->execute([$id]);
                } catch (PDOException $e2) {
                    error_log('deleteUser: ' . $e2->getMessage());
                    return false;
                }
            }
        }
    }

    /* ==========================
    ROOMS (PHÒNG THI)
    ========================== */

    if (!function_exists('getRooms')) {
        function getRooms($teacher_id = null) {
            global $pdo;
            if ($teacher_id !== null) {
                $stmt = $pdo->prepare("
                    SELECT r.*, e.title AS exam_title,
                        (SELECT COUNT(*) FROM room_members rm WHERE rm.room_id = r.id) AS current_students
                    FROM exam_rooms r
                    LEFT JOIN exams e ON e.id = r.exam_id
                    WHERE r.created_by = ?
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute([$teacher_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $pdo->query("
                SELECT r.*, e.title AS exam_title,
                    (SELECT COUNT(*) FROM room_members rm WHERE rm.room_id = r.id) AS current_students
                FROM exam_rooms r
                LEFT JOIN exams e ON e.id = r.exam_id
                ORDER BY r.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /* ==========================
    LEADERBOARD & STATS
    ========================== */

    if (!function_exists('getLeaderboard')) {
        function getLeaderboard($limit = 10) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.email, u.student_code,
                    COUNT(a.id) AS exam_count,
                    ROUND(AVG(a.score), 2) AS avg_score,
                    MAX(a.score) AS best_score
                FROM users u
                LEFT JOIN exam_attempts a ON a.student_id = u.id AND a.status = 'submitted'
                WHERE u.role = 'student'
                GROUP BY u.id
                ORDER BY avg_score DESC, best_score DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!function_exists('getStats')) {
        function getStats($teacher_id = null) {
            global $pdo;

            if ($teacher_id !== null) {
                $stmtExams = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE created_by = ?");
                $stmtExams->execute([$teacher_id]);
                
                $stmtQuestions = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE user_id = ? OR created_by = ?");
                $stmtQuestions->execute([$teacher_id, $teacher_id]);

                return [
                    'subjects'  => (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
                    'exams'     => (int)$stmtExams->fetchColumn(),
                    'questions' => (int)$stmtQuestions->fetchColumn(),
                    'users'     => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
                    'results'   => (int)$pdo->query("SELECT COUNT(*) FROM exam_attempts WHERE status = 'submitted'")->fetchColumn()
                ];
            }

            return [
                'subjects'  => (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
                'exams'     => (int)$pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
                'questions' => (int)$pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
                'users'     => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'results'   => (int)$pdo->query("SELECT COUNT(*) FROM exam_attempts WHERE status = 'submitted'")->fetchColumn()
            ];
        }
    }

    /* ==========================
    SAVE RESULT & HISTORY
    ========================== */

    if (!function_exists('saveResult')) {
        function saveResult($user_id, $exam_id, $score, $correct, $total, $time_taken, $answers = []) {
            global $pdo;

            $unanswered = 0;
            foreach ($answers as $ans) {
                if ($ans === '' || $ans === null) {
                    $unanswered++;
                }
            }

            $wrong = max(0, $total - $correct - $unanswered);
            $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

            $stmt = $pdo->prepare("
                INSERT INTO exam_attempts
                (room_id, exam_id, student_id, started_at, submitted_at, duration_seconds, score, total_questions, correct_answers, wrong_answers, unanswered, percentage, status, answers_json)
                VALUES
                (NULL, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, 'submitted', ?)
            ");

            return $stmt->execute([
                $exam_id, $user_id, $time_taken, $score, $total, $correct, $wrong, $unanswered, $percentage, json_encode($answers, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    if (!function_exists('getHistory')) {
        function getHistory($user_id) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT a.*, e.title AS exam_title, s.name AS subject_name
                FROM exam_attempts a
                INNER JOIN exams e ON e.id = a.exam_id
                LEFT JOIN subjects s ON s.id = e.subject_id
                WHERE a.student_id = ?
                ORDER BY a.submitted_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!function_exists('getHistoryByUser')) {
        function getHistoryByUser($student_id) {
            return getHistory($student_id);
        }
    }