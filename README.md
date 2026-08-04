# QuizTech - PHP 8 + Bootstrap 5

## Chạy thử
```bash
php -S localhost:8000        # trong thư mục quiztech-php
```
Hoặc copy vào `htdocs/` (XAMPP) rồi mở http://localhost/quiztech-php

## Tài khoản demo (admin123/teacher123/student123)
| Vai trò | Email |
|---|---|
| Admin | admin@quiztech.vn |
| Giảng viên | teacher@quiztech.vn |
| Sinh viên | student@quiztech.vn |

## Cấu trúc
```
index.php subjects.php exams.php exam.php result.php leaderboard.php
login.php register.php logout.php        <- khu vực Guest (giao diện như mẫu)
includes/   head, navbar, sidebar, footer, layout_app, auth, data, functions, ui
admin/      dashboard users subjects questions exams rooms leaderboard ai settings
teacher/    dashboard questions(Question Bank) exams(Exam) rooms(Room) ai profile
student/    dashboard subjects rooms history leaderboard profile
assets/     css/style.css, js/app.js
```
Admin / Giảng viên / Sinh viên dùng chung layout `includes/layout_app.php` + `includes/sidebar.php`
(sidebar giống hệt nhau, chỉ khác danh sách menu định nghĩa trong `menu_for()` ở `includes/functions.php`).

## Kết nối CSDL thật
Dữ liệu hiện nằm trong `includes/data.php` dưới dạng mảng. Thay bằng PDO/MySQLi,
và trong `includes/auth.php` dùng `password_hash()` / `password_verify()`.
