# QuizTech - Project Proposal

## 1. Giới thiệu

### 1.1 Tên dự án
**QuizTech** - Hệ thống thi trắc nghiệm kiến thức Công nghệ thông tin

### 1.2 Mục tiêu
Xây dựng một hệ thống thi trắc nghiệm trực tuyến cho phép:
- Thi trắc nghiệm trực tuyến với ngân hàng câu hỏi đa dạng
- Thi đấu theo phòng (Room) với thời gian thực
- AI hỗ trợ tạo câu hỏi và phân tích kết quả
- Nhận diện sinh viên qua email và thẻ sinh viên

### 1.3 Phạm vi dự án

#### Giai đoạn 1 (PHP + MySQL)
- Đăng ký/Đăng nhập
- CRUD Môn học
- CRUD Ngân hàng câu hỏi
- CRUD Đề thi
- Làm bài thi
- Xem kết quả
- Bảng xếp hạng

#### Giai đoạn 2 (WebSocket + Realtime)
- Phòng thi đấu
- Cập nhật điểm theo thời gian thực
- Leaderboard trực tiếp

#### Giai đoạn 3 (AI Integration)
- OCR nhập đề từ ảnh
- AI sinh câu hỏi
- AI giải thích đáp án
- AI Chatbot

#### Giai đoạn 4 (Face Recognition)
- Nhận diện thẻ sinh viên
- Nhận diện khuôn mặt

## 2. Đối tượng sử dụng

### 2.1 Sinh viên (Student)
- Đăng ký tài khoản với MSSV
- Làm bài thi trắc nghiệm
- Thi đấu theo phòng
- Xem kết quả và bảng xếp hạng

### 2.2 Giảng viên (Teacher)
- Quản lý ngân hàng câu hỏi
- Tạo đề thi
- Tạo phòng thi
- Theo dõi kết quả

### 2.3 Quản trị viên (Admin)
- Quản lý người dùng
- Quản lý môn học
- Quản lý AI
- Thống kê tổng quan

## 3. Công nghệ sử dụng

### Backend
- PHP 8.x
- MySQL 8.x
- RESTful API

### Frontend
- HTML5, CSS3
- Bootstrap 5
- JavaScript
- Quill Editor (cho câu hỏi)

### AI Services
- OpenAI API
- Tesseract OCR
- Face-api.js

### DevOps
- Git/GitHub
- Docker (future)
- CI/CD (future)

## 4. Lộ trình phát triển

### Tuần 1: Lên ý tưởng & Setup
- Project Proposal
- SRS Document
- User Stories
- Setup Environment

### Tuần 2: Core Backend
- Database Schema
- Auth API
- CRUD Subjects, Questions, Exams

### Tuần 3: Frontend
- Dashboard UI
- Exam Taking UI
- Result UI

### Tuần 4: Realtime Features
- Room System
- WebSocket Integration

### Tuần 5: AI Integration
- OCR
- AI Generate Questions
- AI Chatbot

### Tuần 6: Face Recognition
- Student ID Detection
- Face Detection

## 5. Rủi ro và Biện pháp

| Rủi ro | Mức độ | Biện pháp |
|--------|--------|-----------|
| Mất dữ liệu | Cao | Backup database hàng ngày |
| Lỗi bảo mật | Cao | Sanitize input, Password hashing |
| AI không chính xác | Trung bình | Fallback to manual input |
| Performance | Trung bình | Index database, Optimize query |
| Deadline trễ | Thấp | Phân chia task hợp lý |