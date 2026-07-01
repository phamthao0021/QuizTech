-- QuizTech Seed Data
USE quiztech;

-- ============================================
-- Insert Users (password: Admin@123, Teacher@123, Student@123)
-- ============================================
INSERT INTO users (student_code, fullname, email, password_hash, role) VALUES
('ADMIN001', 'Quản trị viên', 'admin@quiztech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('TEACH001', 'Nguyễn Văn Thầy', 'teacher@quiztech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
('STU001', 'Sinh viên A', 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('STU002', 'Sinh viên B', 'student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- ============================================
-- Insert Subjects
-- ============================================
INSERT INTO subjects (name, slug, description, icon, color) VALUES
('Lập trình C', 'lap-trinh-c', 'Ngôn ngữ lập trình C cơ bản và nâng cao', 'fa-code', '#2c3e50'),
('Lập trình Java', 'lap-trinh-java', 'Lập trình hướng đối tượng với Java', 'fa-coffee', '#f89820'),
('PHP', 'php', 'PHP - Ngôn ngữ lập trình web', 'fa-php', '#777bb4'),
('JavaScript', 'javascript', 'JavaScript - Ngôn ngữ lập trình web', 'fa-js', '#f7df1e'),
('HTML/CSS', 'html-css', 'HTML5 và CSS3 - Thiết kế web', 'fa-html5', '#e34c26'),
('SQL', 'sql', 'Structured Query Language', 'fa-database', '#00758f'),
('MySQL', 'mysql', 'Hệ quản trị cơ sở dữ liệu MySQL', 'fa-database', '#00758f'),
('Kiểm thử phần mềm', 'kiem-thu-phan-mem', 'Kiểm thử phần mềm và đảm bảo chất lượng', 'fa-vial', '#28a745'),
('Mạng máy tính', 'mang-may-tinh', 'Mạng máy tính cơ bản và nâng cao', 'fa-network-wired', '#007bff'),
('An toàn thông tin', 'an-toan-thong-tin', 'Bảo mật và an toàn thông tin', 'fa-shield-alt', '#dc3545'),
('Machine Learning', 'machine-learning', 'Học máy cơ bản và ứng dụng', 'fa-brain', '#6f42c1'),
('Trí tuệ nhân tạo', 'tri-tue-nhan-tao', 'AI - Trí tuệ nhân tạo', 'fa-robot', '#20c997');

-- ============================================
-- Insert Questions - Lập trình C
-- ============================================
SET @subject_id = (SELECT id FROM subjects WHERE slug = 'lap-trinh-c');

INSERT INTO questions (subject_id, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, points, explanation) VALUES
(@subject_id, 'Hàm nào dùng để xuất dữ liệu trong C?', 'printf()', 'scanf()', 'print()', 'console.log()', 'A', 'easy', 1, 'printf() là hàm xuất dữ liệu trong C'),
(@subject_id, 'Toán tử nào dùng để lấy địa chỉ biến trong C?', '&', '*', '#', '@', 'A', 'easy', 1, '& là toán tử lấy địa chỉ trong C'),
(@subject_id, 'Kiểu dữ liệu nào có kích thước nhỏ nhất trong C?', 'char', 'int', 'float', 'double', 'A', 'easy', 1, 'char chiếm 1 byte'),
(@subject_id, 'Mảng trong C có chỉ số bắt đầu từ?', '0', '1', '2', 'Tùy ý', 'A', 'easy', 1, 'Chỉ số mảng trong C bắt đầu từ 0'),
(@subject_id, 'Con trỏ NULL trong C có giá trị?', '0', '1', 'null', 'undefined', 'A', 'medium', 2, 'NULL trong C là macro có giá trị 0');

-- ============================================
-- Insert Questions - PHP
-- ============================================
SET @subject_id = (SELECT id FROM subjects WHERE slug = 'php');

INSERT INTO questions (subject_id, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, points, explanation) VALUES
(@subject_id, 'PHP là viết tắt của?', 'Personal Home Page', 'Pre Hypertext Processor', 'Programming Hypertext Protocol', 'Public Hosting Platform', 'B', 'easy', 1, 'PHP ban đầu là Personal Home Page, nay là PHP: Hypertext Preprocessor'),
(@subject_id, 'Hàm nào dùng để kết nối MySQL trong PHP?', 'mysqli_connect()', 'mysql_connect()', 'PDO::connect()', 'Cả A và B', 'D', 'easy', 1, 'mysqli_connect() và mysql_connect() đều dùng để kết nối MySQL'),
(@subject_id, 'Session trong PHP được bắt đầu bằng hàm?', 'session_start()', 'session_begin()', 'start_session()', 'init_session()', 'A', 'easy', 1, 'session_start() khởi tạo session trong PHP'),
(@subject_id, '$_POST và $_GET trong PHP là gì?', 'Mảng chứa dữ liệu từ form', 'Hàm xử lý dữ liệu', 'Class trong PHP', 'Thư viện PHP', 'A', 'easy', 1, '$_POST và $_GET là mảng siêu toàn cục chứa dữ liệu từ form'),
(@subject_id, 'Hàm nào dùng để include file trong PHP?', 'include()', 'require()', 'include_once()', 'Tất cả đều đúng', 'D', 'easy', 1, 'include(), require(), include_once() đều dùng để include file');

-- ============================================
-- Insert Questions - JavaScript
-- ============================================
SET @subject_id = (SELECT id FROM subjects WHERE slug = 'javascript');

INSERT INTO questions (subject_id, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, points, explanation) VALUES
(@subject_id, 'JavaScript là ngôn ngữ gì?', 'Trình duyệt', 'Lập trình web', 'Server-side', 'Tất cả đều đúng', 'D', 'easy', 1, 'JavaScript dùng cho cả front-end và back-end (Node.js)'),
(@subject_id, 'Hàm nào in ra màn hình trong JavaScript?', 'console.log()', 'print()', 'echo()', 'document.write()', 'A', 'easy', 1, 'console.log() in ra console'),
(@subject_id, 'Kiểu dữ liệu nào không có trong JavaScript?', 'String', 'Integer', 'Boolean', 'Undefined', 'B', 'easy', 1, 'JavaScript không có kiểu Integer riêng, dùng Number'),
(@subject_id, 'let và var khác nhau ở điểm nào?', 'Phạm vi', 'Kiểu dữ liệu', 'Tốc độ', 'Không khác', 'A', 'medium', 2, 'let có phạm vi block, var có phạm vi function'),
(@subject_id, 'Promise trong JavaScript dùng để làm gì?', 'Xử lý bất đồng bộ', 'Tạo mảng', 'Định nghĩa class', 'Tạo vòng lặp', 'A', 'medium', 2, 'Promise xử lý các tác vụ bất đồng bộ');

-- ============================================
-- Insert Questions - SQL
-- ============================================
SET @subject_id = (SELECT id FROM subjects WHERE slug = 'sql');

INSERT INTO questions (subject_id, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, points, explanation) VALUES
(@subject_id, 'SQL là viết tắt của?', 'Structured Query Language', 'Simple Query Language', 'Sequential Query Language', 'System Query Language', 'A', 'easy', 1, 'SQL là Structured Query Language'),
(@subject_id, 'Lệnh nào dùng để truy vấn dữ liệu?', 'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'A', 'easy', 1, 'SELECT dùng để truy vấn dữ liệu'),
(@subject_id, 'JOIN trong SQL dùng để làm gì?', 'Kết hợp bảng', 'Tạo bảng', 'Xóa bảng', 'Cập nhật bảng', 'A', 'medium', 2, 'JOIN kết hợp dữ liệu từ nhiều bảng'),
(@subject_id, 'GROUP BY dùng để làm gì?', 'Nhóm dữ liệu', 'Sắp xếp', 'Lọc dữ liệu', 'Kết hợp bảng', 'A', 'medium', 2, 'GROUP BY nhóm các hàng có cùng giá trị'),
(@subject_id, 'Hàm COUNT() trong SQL trả về gì?', 'Số lượng hàng', 'Tổng giá trị', 'Trung bình', 'Giá trị lớn nhất', 'A', 'easy', 1, 'COUNT() đếm số lượng hàng');

-- ============================================
-- Insert Exams
-- ============================================
INSERT INTO exams (subject_id, title, description, duration, total_questions, difficulty, created_by) VALUES
((SELECT id FROM subjects WHERE slug = 'lap-trinh-c'), 'Đề thi Lập trình C - Cơ bản', 'Đề thi trắc nghiệm 20 câu Lập trình C cơ bản', 30, 20, 'easy', 1),
((SELECT id FROM subjects WHERE slug = 'php'), 'Đề thi PHP - Cơ bản', 'Đề thi trắc nghiệm 20 câu PHP cơ bản', 30, 20, 'easy', 1),
((SELECT id FROM subjects WHERE slug = 'javascript'), 'Đề thi JavaScript - Cơ bản', 'Đề thi trắc nghiệm 20 câu JavaScript cơ bản', 30, 20, 'easy', 1),
((SELECT id FROM subjects WHERE slug = 'sql'), 'Đề thi SQL - Cơ bản', 'Đề thi trắc nghiệm 20 câu SQL cơ bản', 30, 20, 'easy', 1);

-- ============================================
-- Insert Exam Questions
-- ============================================
-- Lập trình C exam: 5 câu hỏi đầu tiên (làm mẫu, thực tế sẽ nhiều hơn)
SET @exam_id = (SELECT id FROM exams WHERE title = 'Đề thi Lập trình C - Cơ bản');
INSERT INTO exam_questions (exam_id, question_id, question_order)
SELECT @exam_id, id, ROW_NUMBER() OVER (ORDER BY id)
FROM questions WHERE subject_id = (SELECT id FROM subjects WHERE slug = 'lap-trinh-c')
LIMIT 5;

-- PHP exam
SET @exam_id = (SELECT id FROM exams WHERE title = 'Đề thi PHP - Cơ bản');
INSERT INTO exam_questions (exam_id, question_id, question_order)
SELECT @exam_id, id, ROW_NUMBER() OVER (ORDER BY id)
FROM questions WHERE subject_id = (SELECT id FROM subjects WHERE slug = 'php')
LIMIT 5;

-- JavaScript exam
SET @exam_id = (SELECT id FROM exams WHERE title = 'Đề thi JavaScript - Cơ bản');
INSERT INTO exam_questions (exam_id, question_id, question_order)
SELECT @exam_id, id, ROW_NUMBER() OVER (ORDER BY id)
FROM questions WHERE subject_id = (SELECT id FROM subjects WHERE slug = 'javascript')
LIMIT 5;

-- SQL exam
SET @exam_id = (SELECT id FROM exams WHERE title = 'Đề thi SQL - Cơ bản');
INSERT INTO exam_questions (exam_id, question_id, question_order)
SELECT @exam_id, id, ROW_NUMBER() OVER (ORDER BY id)
FROM questions WHERE subject_id = (SELECT id FROM subjects WHERE slug = 'sql')
LIMIT 5;

-- ============================================
-- Insert Sample Results
-- ============================================
INSERT INTO results (user_id, exam_id, score, correct_answers, total_questions, time_taken, answers) VALUES
(3, 1, 8.5, 17, 20, 1200, '{"1":"A","2":"B","3":"A","4":"C","5":"A"}'),
(4, 2, 9.0, 18, 20, 900, '{"1":"B","2":"D","3":"C","4":"A","5":"B"}');