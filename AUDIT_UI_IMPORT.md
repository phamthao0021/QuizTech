# AUDIT: Giao diện Admin / bảo toàn thao tác

Baseline: quiztech-php(6).zip (extracted quiztech_work), riêng 5 trang quản lý dùng bản vá ImportPreserved trước đó.

| Trang | Form action trước/sau | Form method trước/sau | Số nút trước/sau |
|---|---|---|---|
| dashboard.php | True | True | 1/1 |
| users.php | True | True | 20/20 |
| subjects.php | True | True | 19/19 |
| exams.php | True | True | 12/12 |
| questions.php | True | True | 13/13 |
| rooms.php | True | True | 18/18 |
| approve_items.php | True | True | 18/18 |
| leaderboard.php | True | True | 0/0 |
| notifications.php | True | True | 0/0 |
| create_exam.php | True | True | 12/12 |

## Giới hạn kiểm tra
- Đã chạy PHP lint; đối chiếu tĩnh thuộc tính action/method và số nút trong mã nguồn.
- Chưa chạy WAMP/MySQL hoặc thao tác thực tế trên trình duyệt; chưa xác nhận import, CRUD, CSRF, phân quyền, preview và lưu đề hoạt động đúng.
- CSS chung không thay đổi form, endpoint, validation, session, SQL.
- Gói này không chứa bất kỳ file Practice, Student hoặc Teacher nào.
- Các nút tạo/import vẫn sử dụng tên, handler và cơ chế gốc; không thêm import giả.