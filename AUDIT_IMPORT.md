# Đối chiếu chức năng tạo/import – source quiztech-php(6).zip

| Trang | Tạo thủ công | Tạo/import file hiện có | Điểm mở giữ nguyên | Xác nhận |
|---|---|---|---|---|
| Người dùng | Thêm tài khoản | **Import Excel/CSV** | `#modalImportUser`, action `import_users` trong `admin/users.php` | Giữ nguyên nút, form, handler |
| Môn học | Thêm môn học | **Import file** | `#modalImportSubject`, action `import` trong `admin/subjects.php`; có tải tệp mẫu | Giữ nguyên |
| Đề thi | Tạo đề thi theo các lựa chọn trang | **Tạo / Import bộ đề** | `admin/create_exam.php` (link hiện có); không đổi sang modal | Giữ nguyên nguyên văn nhãn và đường dẫn |
| Câu hỏi | Thêm câu hỏi | **Không có nút import trên `admin/questions.php`** | `#addModal` | Không tạo nút import giả |
| Phòng thi | Tạo phòng | **Import Excel/CSV** | `#roomImportModal`, action `import_rooms` trong `admin/rooms.php` | Giữ nguyên |

**Phạm vi kiểm tra:** Đối chiếu tĩnh source trước/sau, PHP lint. Không chạy trình duyệt/WAMP, không thực hiện import Excel/CSV, không xác nhận CRUD hoặc phân quyền hoạt động. Các form, action, CSRF, tệp mẫu, logic validation không sửa. Đây là bản UI bảo toàn chức năng, không phải bản kiểm thử tích hợp.
