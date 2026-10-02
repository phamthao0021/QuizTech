-- QuizTech Practice V2 - MySQL 5.7+
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS practice_crosswords(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(180) NOT NULL,description VARCHAR(500) NULL,
 rows_count TINYINT UNSIGNED NOT NULL,cols_count TINYINT UNSIGNED NOT NULL,difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
 time_limit_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 420,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS practice_crossword_clues(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,crossword_id INT UNSIGNED NOT NULL,clue_no SMALLINT UNSIGNED NOT NULL,
 direction ENUM('across','down') NOT NULL,row_start TINYINT UNSIGNED NOT NULL,col_start TINYINT UNSIGNED NOT NULL,
 answer VARCHAR(60) NOT NULL,clue VARCHAR(500) NOT NULL,explanation TEXT NULL,sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 KEY(crossword_id),CONSTRAINT fk_pv2_cw FOREIGN KEY(crossword_id) REFERENCES practice_crosswords(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS practice_concepts(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,term VARCHAR(180) NOT NULL,definition VARCHAR(700) NOT NULL,category VARCHAR(80) NULL,
 explanation TEXT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS practice_quiz_questions(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,question VARCHAR(900) NOT NULL,option_a VARCHAR(500) NOT NULL,option_b VARCHAR(500) NOT NULL,
 option_c VARCHAR(500) NOT NULL,option_d VARCHAR(500) NOT NULL,correct_answer ENUM('A','B','C','D') NOT NULL,
 explanation TEXT NULL,category VARCHAR(80) NULL,time_limit_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 15,is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS practice_sessions(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT(10) UNSIGNED NOT NULL,game_type ENUM('crossword','concept_match','quick_quiz') NOT NULL,
 daily_key DATE NOT NULL,content_id INT UNSIGNED NULL,snapshot LONGTEXT NOT NULL,status ENUM('playing','completed','timeout','cancelled') NOT NULL DEFAULT 'playing',
 score INT NOT NULL DEFAULT 0,correct_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,wrong_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,total_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 hints_used SMALLINT UNSIGNED NOT NULL DEFAULT 0,max_combo SMALLINT UNSIGNED NOT NULL DEFAULT 0,duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
 started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,completed_at DATETIME NULL,KEY idx_ps_user(user_id,daily_key),CONSTRAINT fk_pv2_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS practice_answers(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,session_id BIGINT UNSIGNED NOT NULL,item_token VARCHAR(64) NOT NULL,user_answer TEXT NULL,
 is_correct TINYINT(1) NOT NULL DEFAULT 0,is_timeout TINYINT(1) NOT NULL DEFAULT 0,score_awarded INT NOT NULL DEFAULT 0,explanation TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,KEY(session_id),CONSTRAINT fk_pv2_ans FOREIGN KEY(session_id) REFERENCES practice_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO practice_concepts(term,definition,category,explanation)
SELECT * FROM (SELECT 'DNS','Hệ thống phân giải tên miền thành địa chỉ IP.','Network','DNS giúp ánh xạ tên miền dễ nhớ sang địa chỉ IP.' UNION ALL
SELECT 'HTTP','Giao thức truyền siêu văn bản dùng trong Web.','Web','HTTP là giao thức tầng ứng dụng cho trao đổi tài nguyên Web.' UNION ALL
SELECT 'HTML','Ngôn ngữ đánh dấu tạo cấu trúc nội dung trang web.','Web','HTML mô tả cấu trúc và ngữ nghĩa nội dung.' UNION ALL
SELECT 'CPU','Bộ xử lý trung tâm thực thi các chỉ thị của chương trình.','Hardware','CPU thực hiện tính toán và điều khiển hoạt động hệ thống.' UNION ALL
SELECT 'RAM','Bộ nhớ truy cập ngẫu nhiên dùng lưu dữ liệu tạm thời khi chương trình chạy.','Hardware','RAM là bộ nhớ khả biến, mất dữ liệu khi mất điện.' UNION ALL
SELECT 'URL','Địa chỉ định vị một tài nguyên trên Internet.','Web','URL mô tả vị trí và cách truy cập tài nguyên.' UNION ALL
SELECT 'SQL','Ngôn ngữ truy vấn dùng để thao tác cơ sở dữ liệu quan hệ.','Database','SQL hỗ trợ truy vấn và thay đổi dữ liệu.' UNION ALL
SELECT 'IP','Địa chỉ logic định danh thiết bị/giao diện trong mạng IP.','Network','IP address được dùng để định tuyến dữ liệu.' UNION ALL
SELECT 'API','Giao diện cho phép các phần mềm giao tiếp theo quy ước.','Software','API định nghĩa cách hệ thống trao đổi dữ liệu/chức năng.' UNION ALL
SELECT 'GIT','Hệ thống quản lý phiên bản phân tán.','DevOps','Git theo dõi lịch sử thay đổi mã nguồn.' UNION ALL
SELECT 'FIREWALL','Cơ chế kiểm soát lưu lượng mạng theo tập luật.','Security','Firewall cho phép hoặc chặn lưu lượng theo chính sách.' UNION ALL
SELECT 'INDEX','Cấu trúc dữ liệu giúp tăng tốc nhiều truy vấn cơ sở dữ liệu.','Database','Index giảm lượng dữ liệu phải quét khi tìm kiếm.')
s WHERE NOT EXISTS(SELECT 1 FROM practice_concepts LIMIT 1);

INSERT INTO practice_quiz_questions(question,option_a,option_b,option_c,option_d,correct_answer,explanation,category)
SELECT * FROM (SELECT 'Ngôn ngữ nào tạo cấu trúc nội dung cho một trang web?','SQL','HTML','DNS','Python','B','HTML dùng để mô tả cấu trúc và nội dung trang web.','Web' UNION ALL
SELECT 'Cấu trúc dữ liệu nào hoạt động theo nguyên tắc LIFO?','Queue','Stack','Tree','Graph','B','Stack là Last In, First Out.','Data Structure' UNION ALL
SELECT 'Mã trạng thái HTTP 404 biểu thị điều gì?','Thành công','Không tìm thấy tài nguyên','Lỗi xác thực','Lỗi DNS','B','404 Not Found nghĩa là tài nguyên yêu cầu không được tìm thấy.','Web' UNION ALL
SELECT 'Trong SQL, mệnh đề nào dùng để lọc các dòng theo điều kiện?','ORDER BY','WHERE','GROUP BY','CREATE','B','WHERE lọc bản ghi thỏa điều kiện.','Database' UNION ALL
SELECT 'Công cụ nào là hệ thống quản lý phiên bản phân tán?','Git','Docker','Selenium','MySQL','A','Git lưu lịch sử thay đổi và hỗ trợ cộng tác mã nguồn.','DevOps' UNION ALL
SELECT 'HTTPS bảo vệ dữ liệu truyền trên Web chủ yếu bằng công nghệ nào?','TLS','DNS','FTP','DHCP','A','HTTPS sử dụng TLS để mã hóa và xác thực kênh truyền.','Security' UNION ALL
SELECT 'Trong OOP, object thường là gì?','Một instance của class','Một database','Một HTTP header','Một CSS selector','A','Object là một thể hiện được tạo từ class.','OOP' UNION ALL
SELECT 'Selenium WebDriver thường dùng cho mục đích nào?','Thiết kế UI','Tự động hóa trình duyệt để kiểm thử','Quản trị CSDL','Biên dịch Java','B','WebDriver điều khiển trình duyệt phục vụ automation testing.','Testing' UNION ALL
SELECT 'PRIMARY KEY có đặc điểm quan trọng nào?','Có thể trùng tùy ý','Định danh duy nhất mỗi bản ghi','Chỉ lưu số thực','Luôn là mật khẩu','B','Primary key định danh duy nhất từng dòng trong bảng.','Database' UNION ALL
SELECT 'Thiết bị nào chuyển tiếp gói tin giữa các mạng IP?','Router','Keyboard','Monitor','Printer','A','Router định tuyến packet giữa các mạng.','Network' UNION ALL
SELECT 'CSRF tấn công dựa vào yếu tố nào?','Phiên xác thực sẵn có của người dùng','Ổ cứng đầy','CPU chậm','CSS lỗi','A','CSRF lợi dụng trạng thái đăng nhập để gửi yêu cầu ngoài ý muốn.','Security' UNION ALL
SELECT 'Docker image được dùng để làm gì?','Làm mẫu tạo container','Thay thế DNS','Tạo bảng SQL','Mã hóa HTTPS','A','Image đóng gói filesystem và metadata cần để chạy container.','DevOps')
s WHERE NOT EXISTS(SELECT 1 FROM practice_quiz_questions LIMIT 1);

INSERT INTO practice_crosswords(title,description,rows_count,cols_count,difficulty,time_limit_seconds)
SELECT 'Web & Data Fundamentals','Ô chữ hằng ngày về Web, cơ sở dữ liệu và phần cứng.',9,9,'medium',420
WHERE NOT EXISTS(SELECT 1 FROM practice_crosswords LIMIT 1);
SET @cw=(SELECT id FROM practice_crosswords ORDER BY id LIMIT 1);
INSERT INTO practice_crossword_clues(crossword_id,clue_no,direction,row_start,col_start,answer,clue,explanation,sort_order)
SELECT @cw,1,'across',0,0,'HTML','Ngôn ngữ đánh dấu tạo cấu trúc trang web.','HTML mô tả cấu trúc và ngữ nghĩa nội dung Web.',1 WHERE NOT EXISTS(SELECT 1 FROM practice_crossword_clues WHERE crossword_id=@cw)
UNION ALL SELECT @cw,2,'down',0,0,'HTTP','Giao thức nền tảng để trao đổi tài nguyên Web.','HTTP là giao thức tầng ứng dụng của Web.',2
UNION ALL SELECT @cw,3,'down',0,1,'MYSQL','Hệ quản trị cơ sở dữ liệu quan hệ phổ biến, tên gồm 5 ký tự.','MySQL là RDBMS phổ biến trong ứng dụng Web.',3
UNION ALL SELECT @cw,4,'down',0,2,'LINUX','Họ hệ điều hành mã nguồn mở phổ biến trên máy chủ.','Linux được sử dụng rộng rãi cho server và cloud.',4
UNION ALL SELECT @cw,5,'down',0,3,'LOGIC','Nền tảng suy luận dùng trong thuật toán và lập trình.','Logic giúp mô hình hóa điều kiện và suy luận.',5
UNION ALL SELECT @cw,6,'across',3,0,'API','Giao diện giúp các phần mềm giao tiếp với nhau.','API định nghĩa hợp đồng giao tiếp giữa các hệ thống.',6
UNION ALL SELECT @cw,7,'across',4,0,'RAM','Bộ nhớ tạm thời, truy cập ngẫu nhiên.','RAM lưu dữ liệu đang được CPU sử dụng.',7
UNION ALL SELECT @cw,8,'across',5,0,'SQL','Ngôn ngữ truy vấn cơ sở dữ liệu quan hệ.','SQL dùng để truy vấn và thao tác dữ liệu.',8;
