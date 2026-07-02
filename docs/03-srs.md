# QuizTech - Software Requirements Specification (SRS)

## 1. Giới thiệu

### 1.1 Mục đích
Tài liệu này mô tả chi tiết các yêu cầu phần mềm cho hệ thống QuizTech.

### 1.2 Phạm vi
Hệ thống thi trắc nghiệm trực tuyến với hỗ trợ AI.

## 2. Yêu cầu chức năng (45 Functional Requirements)

### FR01: Đăng ký tài khoản
**Mô tả**: Người dùng có thể đăng ký tài khoản với MSSV, họ tên, email, mật khẩu
**Ưu tiên**: Cao
**Input**: MSSV, họ tên, email, mật khẩu
**Output**: Tài khoản được tạo, chuyển đến dashboard

### FR02: Đăng nhập
**Mô tả**: Người dùng đăng nhập bằng email hoặc MSSV
**Ưu tiên**: Cao
**Input**: Email/MSSV, mật khẩu
**Output**: Session được tạo, chuyển đến dashboard

### FR03: Đăng xuất
**Mô tả**: Người dùng đăng xuất khỏi hệ thống
**Ưu tiên**: Cao
**Input**: Click nút đăng xuất
**Output**: Session bị hủy, chuyển đến trang chủ

### FR04: Xem hồ sơ
**Mô tả**: Người dùng xem thông tin cá nhân
**Ưu tiên**: Trung bình
**Output**: Hiển thị hồ sơ người dùng

### FR05: Cập nhật hồ sơ
**Mô tả**: Người dùng cập nhật thông tin cá nhân
**Ưu tiên**: Trung bình
**Input**: Họ tên, email, avatar
**Output**: Thông tin được cập nhật

### FR06: Quản lý môn học - Tạo mới
**Mô tả**: Giảng viên tạo môn học mới
**Ưu tiên**: Cao
**Input**: Tên, slug, mô tả, icon
**Output**: Môn học được tạo

### FR07: Quản lý môn học - Xem danh sách
**Mô tả**: Xem danh sách tất cả môn học
**Ưu tiên**: Cao
**Output**: Danh sách môn học

### FR08: Quản lý môn học - Cập nhật
**Mô tả**: Cập nhật thông tin môn học
**Ưu tiên**: Cao
**Input**: Tên, slug, mô tả, icon
**Output**: Môn học được cập nhật

### FR09: Quản lý môn học - Xóa
**Mô tả**: Xóa môn học
**Ưu tiên**: Trung bình
**Input**: ID môn học
**Output**: Môn học bị xóa

### FR10: Quản lý câu hỏi - Tạo mới
**Mô tả**: Tạo câu hỏi trắc nghiệm mới
**Ưu tiên**: Cao
**Input**: Môn học, câu hỏi, 4 đáp án, đáp án đúng, độ khó
**Output**: Câu hỏi được tạo

### FR11: Quản lý câu hỏi - Xem danh sách
**Mô tả**: Xem danh sách câu hỏi theo môn học
**Ưu tiên**: Cao
**Output**: Danh sách câu hỏi

### FR12: Quản lý câu hỏi - Cập nhật
**Mô tả**: Cập nhật câu hỏi
**Ưu tiên**: Cao
**Input**: Tất cả thông tin câu hỏi
**Output**: Câu hỏi được cập nhật

### FR13: Quản lý câu hỏi - Xóa
**Mô tả**: Xóa câu hỏi
**Ưu tiên**: Trung bình
**Input**: ID câu hỏi
**Output**: Câu hỏi bị xóa

### FR14: Quản lý câu hỏi - Lọc theo môn học
**Mô tả**: Lọc câu hỏi theo môn học
**Ưu tiên**: Trung bình
**Input**: Subject ID
**Output**: Danh sách câu hỏi đã lọc

### FR15: Quản lý câu hỏi - Lọc theo độ khó
**Mô tả**: Lọc câu hỏi theo độ khó
**Ưu tiên**: Trung bình
**Input**: Difficulty
**Output**: Danh sách câu hỏi đã lọc

### FR16: Quản lý đề thi - Tạo mới
**Mô tả**: Tạo đề thi mới
**Ưu tiên**: Cao
**Input**: Môn học, tiêu đề, mô tả, thời gian, số câu hỏi
**Output**: Đề thi được tạo

### FR17: Quản lý đề thi - Xem danh sách
**Mô tả**: Xem danh sách đề thi
**Ưu tiên**: Cao
**Output**: Danh sách đề thi

### FR18: Quản lý đề thi - Cập nhật
**Mô tả**: Cập nhật thông tin đề thi
**Ưu tiên**: Cao
**Input**: Tất cả thông tin đề thi
**Output**: Đề thi được cập nhật

### FR19: Quản lý đề thi - Xóa
**Mô tả**: Xóa đề thi
**Ưu tiên**: Trung bình
**Input**: ID đề thi
**Output**: Đề thi bị xóa

### FR20: Quản lý đề thi - Thêm câu hỏi
**Mô tả**: Thêm câu hỏi vào đề thi
**Ưu tiên**: Cao
**Input**: Exam ID, Question IDs
**Output**: Câu hỏi được thêm vào đề thi

### FR21: Quản lý đề thi - Xóa câu hỏi khỏi đề
**Mô tả**: Xóa câu hỏi khỏi đề thi
**Ưu tiên**: Trung bình
**Input**: Exam Question ID
**Output**: Câu hỏi bị xóa khỏi đề thi

### FR22: Làm bài thi - Bắt đầu
**Mô tả**: Bắt đầu làm bài thi
**Ưu tiên**: Cao
**Input**: Exam ID
**Output**: Hiển thị câu hỏi, bắt đầu đếm giờ

### FR23: Làm bài thi - Chọn đáp án
**Mô tả**: Chọn đáp án cho câu hỏi
**Ưu tiên**: Cao
**Input**: Question ID, Answer
**Output**: Đáp án được lưu

### FR24: Làm bài thi - Đánh dấu câu hỏi
**Mô tả**: Đánh dấu câu hỏi để xem lại sau
**Ưu tiên**: Trung bình
**Input**: Question ID
**Output**: Câu hỏi được đánh dấu

### FR25: Làm bài thi - Nộp bài
**Mô tả**: Nộp bài thi
**Ưu tiên**: Cao
**Input**: Xác nhận nộp bài
**Output**: Bài thi được nộp, hiển thị kết quả

### FR26: Làm bài thi - Xem kết quả
**Mô tả**: Xem kết quả bài thi
**Ưu tiên**: Cao
**Output**: Điểm, số câu đúng/sai

### FR27: Bảng xếp hạng - Xem top 10
**Mô tả**: Xem top 10 điểm cao nhất
**Ưu tiên**: Trung bình
**Output**: Bảng xếp hạng

### FR28: Bảng xếp hạng - Lọc theo môn học
**Mô tả**: Lọc bảng xếp hạng theo môn học
**Ưu tiên**: Trung bình
**Input**: Subject ID
**Output**: Bảng xếp hạng đã lọc

### FR29: Phòng thi - Tạo phòng
**Mô tả**: Tạo phòng thi đấu
**Ưu tiên**: Cao
**Input**: Exam ID, mã phòng, số lượng người
**Output**: Phòng thi được tạo

### FR30: Phòng thi - Tham gia phòng
**Mô tả**: Tham gia phòng thi bằng mã code
**Ưu tiên**: Cao
**Input**: Room Code
**Output**: Tham gia phòng thi

### FR31: Phòng thi - Bắt đầu
**Mô tả**: Host bắt đầu phòng thi
**Ưu tiên**: Cao
**Input**: Room ID
**Output**: Bắt đầu thi đấu

### FR32: Phòng thi - Xem leaderboard
**Mô tả**: Xem bảng xếp hạng trong phòng thi
**Ưu tiên**: Trung bình
**Output**: Leaderboard update theo thời gian thực

### FR33: AI - Sinh câu hỏi
**Mô tả**: AI sinh câu hỏi từ chủ đề
**Ưu tiên**: Thấp
**Input**: Subject, difficulty, số lượng
**Output**: Danh sách câu hỏi

### FR34: AI - OCR nhập đề
**Mô tả**: Đọc đề từ hình ảnh
**Ưu tiên**: Thấp
**Input**: Image file
**Output**: Câu hỏi được tạo

### FR35: AI - Giải thích đáp án
**Mô tả**: AI giải thích đáp án đúng
**Ưu tiên**: Thấp
**Input**: Question ID
**Output**: Giải thích chi tiết

### FR36: AI - Phân tích kết quả
**Mô tả**: AI phân tích điểm yếu
**Ưu tiên**: Thấp
**Input**: User ID
**Output**: Phân tích và đề xuất

### FR37: AI - Chatbot
**Mô tả**: Chat với AI về kiến thức
**Ưu tiên**: Thấp
**Input**: Câu hỏi
**Output**: Trả lời từ AI

### FR38: Admin - Xem users
**Mô tả**: Xem danh sách người dùng
**Ưu tiên**: Trung bình
**Output**: Danh sách users

### FR39: Admin - Block/Unblock users
**Mô tả**: Khóa/mở khóa người dùng
**Ưu tiên**: Trung bình
**Input**: User ID
**Output**: User bị khóa/mở khóa

### FR40: Admin - Xem thống kê
**Mô tả**: Xem thống kê hệ thống
**Ưu tiên**: Thấp
**Output**: Biểu đồ thống kê

### FR41: Admin - Xem AI logs
**Mô tả**: Xem lịch sử AI
**Ưu tiên**: Thấp
**Output**: Danh sách AI logs

### FR42: Admin - Backup database
**Mô tả**: Sao lưu database
**Ưu tiên**: Thấp
**Output**: File backup

### FR43: Admin - Cấu hình hệ thống
**Mô tả**: Cấu hình settings
**Ưu tiên**: Thấp
**Input**: Các config values
**Output**: Settings được cập nhật

### FR44: Profile - Upload avatar
**Mô tả**: Upload avatar người dùng
**Ưu tiên**: Thấp
**Input**: Image file
**Output**: Avatar được update

### FR45: Exam - Random đề thi
**Mô tả**: Tạo đề thi ngẫu nhiên từ ngân hàng
**Ưu tiên**: Trung bình
**Input**: Subject, số lượng, difficulty
**Output**: Đề thi được tạo

## 3. Yêu cầu phi chức năng (5 Non-functional Requirements)

### NFR01: Performance
- Thời gian tải trang < 2s
- Hỗ trợ 100+ người dùng đồng thời
- API response < 500ms

### NFR02: Security
- Password hash với Bcrypt
- CSRF protection
- XSS prevention
- SQL injection prevention

### NFR03: Usability
- Giao diện thân thiện, responsive
- Hỗ trợ tiếng Việt
- Dark/Light mode

### NFR04: Reliability
- Uptime 99.9%
- Auto backup hàng ngày
- Error handling

### NFR05: Maintainability
- Code theo MVC pattern
- Comment code đầy đủ
- Có tài liệu hướng dẫn