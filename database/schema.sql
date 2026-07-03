-- database/schema.sql
CREATE DATABASE IF NOT EXISTS quiztech1;
USE quiztech1;

-- Bảng người dùng
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng môn học
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng đề thi
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    time_limit INT DEFAULT 20,
    question_count INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Bảng câu hỏi
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT,
    question_text TEXT NOT NULL,
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    correct_answer INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Bảng lịch sử làm bài
CREATE TABLE exam_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    exam_id INT,
    score INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    time_taken INT DEFAULT 0,
    answers JSON,
    is_completed BOOLEAN DEFAULT FALSE,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

-- === DỮ LIỆU MẪU ===

-- Môn học
INSERT INTO subjects (name, description, icon) VALUES
('Lập trình C', 'Ngôn ngữ C - Cơ bản đến nâng cao', '📚'),
('PHP', 'Lập trình web với PHP', '🐘'),
('JavaScript', 'JavaScript hiện đại (ES6+)', '🟨'),
('SQL', 'Truy vấn cơ sở dữ liệu SQL', '🗄️'),
('Kiểm thử phần mềm', 'Testing, QA, Test Case', '🧪');

-- Đề thi
INSERT INTO exams (subject_id, title, description, time_limit, question_count) VALUES
(1, 'Đề thi Lập trình C - Cơ bản', 'Kiểm tra kiến thức C cơ bản', 20, 5),
(2, 'Đề thi PHP - Cơ bản', 'PHP cú pháp và mảng', 20, 5),
(3, 'Đề thi JavaScript - Cơ bản', 'JS ES6+', 20, 5),
(4, 'Đề thi SQL - Cơ bản', 'SELECT, JOIN, WHERE', 20, 5),
(5, 'Đề thi Kiểm thử phần mềm', 'Cơ bản về QA/Testing', 20, 5);

-- Câu hỏi cho môn Lập trình C (exam_id = 1)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(1, 'C là ngôn ngữ lập trình gì?', 'Thông dịch', 'Biên dịch', 'Script', 'Markup', 1),
(1, 'Hàm printf() dùng để làm gì?', 'Nhập dữ liệu', 'Xuất dữ liệu', 'Khởi tạo biến', 'Kết thúc chương trình', 1),
(1, 'Kiểu dữ liệu int trong C chiếm bao nhiêu byte?', '2', '4', '8', '16', 1),
(1, 'Cấu trúc if...else dùng để làm gì?', 'Vòng lặp', 'Rẽ nhánh', 'Khai báo hàm', 'Định nghĩa biến', 1),
(1, 'Hàm main() trong C là gì?', 'Hàm bắt buộc', 'Hàm tùy chọn', 'Hàm không có', 'Hàm thư viện', 0);

-- Câu hỏi cho môn PHP (exam_id = 2)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(2, 'PHP là viết tắt của?', 'Personal Home Page', 'PHP: Hypertext Preprocessor', 'Preprocessor Hypertext PHP', 'Programming Home Page', 1),
(2, 'Cú pháp đúng để khai báo biến trong PHP?', '$var', 'var', '&var', '@var', 0),
(2, 'Hàm nào dùng để kết nối MySQL trong PHP?', 'mysql_connect()', 'mysqli_connect()', 'pdo_connect()', 'db_connect()', 1),
(2, 'PHP là ngôn ngữ?', 'Phía client', 'Phía server', 'Cả hai', 'Không phải ngôn ngữ lập trình', 1),
(2, 'Cách comment đúng trong PHP?', '// comment', '/* comment */', 'Cả hai đều đúng', 'Không có cách nào', 2);

-- Câu hỏi cho môn JavaScript (exam_id = 3)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(3, 'JavaScript được phát triển bởi ai?', 'Microsoft', 'Google', 'Netscape', 'Apple', 2),
(3, 'Phương thức nào dùng để thêm phần tử vào cuối mảng?', 'push()', 'pop()', 'shift()', 'unshift()', 0),
(3, 'Từ khóa nào dùng để khai báo biến trong ES6?', 'var', 'let', 'const', 'Tất cả đều đúng', 3),
(3, 'Hàm setTimeout() thuộc loại gì?', 'Đồng bộ', 'Bất đồng bộ', 'Callback', 'Promise', 1),
(3, 'DOM là viết tắt của?', 'Document Object Model', 'Data Object Model', 'Document Oriented Model', 'Data Oriented Model', 0);

-- Câu hỏi cho môn SQL (exam_id = 4)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(4, 'SQL dùng để làm gì?', 'Thiết kế web', 'Quản lý CSDL', 'Viết ứng dụng', 'Xử lý ảnh', 1),
(4, 'Câu lệnh SELECT dùng để?', 'Xóa dữ liệu', 'Thêm dữ liệu', 'Truy vấn dữ liệu', 'Cập nhật dữ liệu', 2),
(4, 'JOIN dùng để?', 'Kết nối bảng', 'Tách bảng', 'Xóa bảng', 'Tạo bảng', 0),
(4, 'WHERE dùng để?', 'Lọc dữ liệu', 'Sắp xếp dữ liệu', 'Nhóm dữ liệu', 'Thêm dữ liệu', 0),
(4, 'Câu lệnh INSERT dùng để?', 'Xóa dữ liệu', 'Thêm dữ liệu', 'Truy vấn dữ liệu', 'Cập nhật dữ liệu', 1);

-- Câu hỏi cho môn Kiểm thử (exam_id = 5)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(5, 'Kiểm thử phần mềm là gì?', 'Quá trình tìm lỗi', 'Quá trình viết code', 'Quá trình thiết kế', 'Quá trình triển khai', 0),
(5, 'Unit test là kiểm thử?', 'Từng đơn vị', 'Toàn hệ thống', 'Giao diện', 'Hiệu năng', 0),
(5, 'QA là viết tắt của?', 'Quality Assurance', 'Quantity Assurance', 'Quick Action', 'Quality Action', 0),
(5, 'Bug là gì?', 'Lỗi phần mềm', 'Tính năng mới', 'Yêu cầu khách hàng', 'Tài liệu', 0),
(5, 'TDD là viết tắt của?', 'Test Driven Development', 'Test Design Document', 'Technical Design Document', 'Test Data Definition', 0);