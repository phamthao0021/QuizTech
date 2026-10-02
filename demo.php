<?php

// demo.php - Hệ thống Đánh giá Năng lực IT 2026

require_once 'includes/config.php';

require_once 'includes/functions.php';

$page_title = 'Đánh giá Năng lực CNTT 2026';

// Ngân hàng 20 câu hỏi đánh giá diện rộng

$demo_exam = [

  'id' => 101,

  'title' => 'Đánh giá Năng lực Hiểu biết Ngành CNTT 2026',

  'time_limit' => 20,

  'description' => 'Bài test toàn diện gồm 20 câu hỏi bao quát các lĩnh vực Lập trình, Web, Cơ sở dữ liệu, Mạng máy tính, Cloud, AI và DevOps nhằm phân tích chính xác mức độ hiểu biết ngành IT của bạn.',

  'questions' => [

    [

      'id' => 1,

      'category' => 'Lập trình C++',

      'question' => 'Đoạn mã C++ sau đây sẽ xuất ra màn hình kết quả gì?',

      'code' => "int a = 5;\nint b = a++;\ncout << a << \" \" << b;",

      'options' => ['A' => '5 5', 'B' => '6 5', 'C' => '5 6', 'D' => '6 6'],

      'correct' => 'B',

      'explanation' => 'Phép toán `a++` là toán tử hậu tố. Giá trị của `a` (5) được gán cho `b` trước, sau đó `a` mới tăng lên 6.'

    ],

    [

      'id' => 2,

      'category' => 'Lập trình Web',

      'question' => 'Phương thức nào trong JavaScript dùng để chuyển đổi một chuỗi JSON thành một Object?',

      'code' => null,

      'options' => ['A' => 'JSON.stringify()', 'B' => 'JSON.parse()', 'C' => 'JSON.toObject()', 'D' => 'JSON.convert()'],

      'correct' => 'B',

      'explanation' => '`JSON.parse()` dùng để parse chuỗi JSON thành JavaScript Object.'

    ],

    [

      'id' => 3,

      'category' => 'Cơ sở dữ liệu',

      'question' => 'Từ khóa nào trong SQL được dùng để loại bỏ các bản ghi trùng lặp?',

      'code' => "SELECT ______ Country FROM Customers;",

      'options' => ['A' => 'UNIQUE', 'B' => 'NO REPEAT', 'C' => 'DISTINCT', 'D' => 'DIFFERENT'],

      'correct' => 'C',

      'explanation' => '`DISTINCT` giúp lọc các giá trị duy nhất trong câu lệnh SELECT.'

    ],

    [

      'id' => 4,

      'category' => 'Mạng máy tính',

      'question' => 'Giao thức nào hoạt động ở Tầng Giao vận (Transport Layer) đảm bảo truyền tải dữ liệu tin cậy?',

      'code' => null,

      'options' => ['A' => 'UDP', 'B' => 'IP', 'C' => 'TCP', 'D' => 'HTTP'],

      'correct' => 'C',

      'explanation' => 'TCP (Transmission Control Protocol) đảm bảo kết nối an toàn và tin cậy ở tầng Transport.'

    ],

    [

      'id' => 5,

      'category' => 'Khái niệm CNTT',

      'question' => 'Thuật ngữ "API" trong lập trình phần mềm là viết tắt của từ nào?',

      'code' => null,

      'options' => ['A' => 'Application Programming Interface', 'B' => 'Applied Protocol Integration', 'C' => 'Automated Program Interaction', 'D' => 'Advanced Program Instruction'],

      'correct' => 'A',

      'explanation' => 'API (Application Programming Interface) là giao diện lập trình ứng dụng.'

    ],

    [

      'id' => 6,

      'category' => 'An toàn thông tin',

      'question' => 'Hình thức tấn công giả mạo email/website uy tín nhằm lừa đảo lấy tài khoản người dùng gọi là gì?',

      'code' => null,

      'options' => ['A' => 'DDoS', 'B' => 'Phishing', 'C' => 'Man-in-the-middle', 'D' => 'SQL Injection'],

      'correct' => 'B',

      'explanation' => 'Phishing là hình thức lừa đảo giả mạo tổ chức uy tín để thu thập thông tin nhạy cảm.'

    ],

    [

      'id' => 7,

      'category' => 'Lập trình Web',

      'question' => 'Thuộc tính CSS nào dùng để thay đổi màu chữ của một phần tử HTML?',

      'code' => null,

      'options' => ['A' => 'font-color', 'B' => 'text-color', 'C' => 'color', 'D' => 'style-color'],

      'correct' => 'C',

      'explanation' => 'Thuộc tính `color` trong CSS dùng để quy định màu chữ.'

    ],

    [

      'id' => 8,

      'category' => 'Cơ sở dữ liệu',

      'question' => 'Đặc điểm nào bắt buộc phải có đối với một Khóa chính (Primary Key)?',

      'code' => null,

      'options' => ['A' => 'Có thể chứa giá trị NULL', 'B' => 'Chứa giá trị duy nhất và không được NULL', 'C' => 'Có thể trùng lặp', 'D' => 'Phải là kiểu dữ liệu chuỗi'],

      'correct' => 'B',

      'explanation' => 'Khóa chính dùng để định danh duy nhất cho từng dòng dữ liệu và không chấp nhận NULL.'

    ],

    [

      'id' => 9,

      'category' => 'Điện toán đám mây',

      'question' => 'Mô hình dịch vụ cloud nào cung cấp cho người dùng máy chủ ảo, hạ tầng mạng và ổ cứng?',

      'code' => null,

      'options' => ['A' => 'SaaS', 'B' => 'PaaS', 'C' => 'IaaS', 'D' => 'DaaS'],

      'correct' => 'C',

      'explanation' => 'IaaS (Infrastructure as a Service) cung cấp hạ tầng phần cứng/máy chủ trên đám mây.'

    ],

    [

      'id' => 10,

      'category' => 'Thuật toán',

      'question' => 'Độ phức tạp thời gian trung bình của thuật toán Tìm kiếm nhị phân (Binary Search) là bao nhiêu?',

      'code' => null,

      'options' => ['A' => 'O(1)', 'B' => 'O(N)', 'C' => 'O(log N)', 'D' => 'O(N²)'],

      'correct' => 'C',

      'explanation' => 'Tìm kiếm nhị phân chia đôi danh sách sau mỗi bước nên có độ phức tạp O(log N).'

    ],

    [

      'id' => 11,

      'category' => 'Hệ điều hành',

      'question' => 'Thành phần lõi quản lý tài nguyên phần cứng của Hệ điều hành gọi là gì?',

      'code' => null,

      'options' => ['A' => 'Shell', 'B' => 'Kernel', 'C' => 'Driver', 'D' => 'BIOS'],

      'correct' => 'B',

      'explanation' => 'Kernel (Nhân hệ điều hành) trực tiếp giao tiếp và quản lý tài nguyên phần cứng.'

    ],

    [

      'id' => 12,

      'category' => 'Lập trình OOP',

      'question' => 'Tính chất nào trong OOP cho phép các lớp con định nghĩa lại cách thực thi của một phương thức ở lớp cha?',

      'code' => null,

      'options' => ['A' => 'Đóng gói (Encapsulation)', 'B' => 'Kế thừa (Inheritance)', 'C' => 'Đa hình (Polymorphism)', 'D' => 'Trừu tượng (Abstraction)'],

      'correct' => 'C',

      'explanation' => 'Tính Đa hình (Polymorphism) cho phép hành vi/phương thức được thực thi theo nhiều hình thái khác nhau (Override).'

    ],

    [

      'id' => 13,

      'category' => 'Công nghệ Mới (AI)',

      'question' => 'Mô hình ngôn ngữ lớn (LLM) như ChatGPT hoạt động dựa trên kiến trúc mạng nơ-ron nào?',

      'code' => null,

      'options' => ['A' => 'CNN', 'B' => 'RNN', 'C' => 'Transformer', 'D' => 'GAN'],

      'correct' => 'C',

      'explanation' => 'Transformer là kiến trúc nền tảng cho sự phát triển của các mô hình LLM hiện đại.'

    ],

    [

      'id' => 14,

      'category' => 'DevOps & Git',

      'question' => 'Lệnh Git nào dùng để tải mã nguồn mới nhất từ kho lưu trữ từ xa (Remote repository) về máy cục bộ?',

      'code' => null,

      'options' => ['A' => 'git push', 'B' => 'git commit', 'C' => 'git pull', 'D' => 'git checkout'],

      'correct' => 'C',

      'explanation' => '`git pull` cập nhật và hợp nhất các thay đổi từ remote server về máy cá nhân.'

    ],

    [

      'id' => 15,

      'category' => 'Mạng máy tính',

      'question' => 'Cổng mặc định (Port) của giao thức truyền tải web bảo mật HTTPS là bao nhiêu?',

      'code' => null,

      'options' => ['A' => '80', 'B' => '21', 'C' => '443', 'D' => '8080'],

      'correct' => 'C',

      'explanation' => 'HTTPS chạy trên cổng chuẩn 443, trong khi HTTP chạy trên cổng 80.'

    ],

    [

      'id' => 16,

      'category' => 'Cấu trúc dữ liệu',

      'question' => 'Cấu trúc dữ liệu nào hoạt động theo nguyên tắc LIFO (Last In, First Out)?',

      'code' => null,

      'options' => ['A' => 'Queue (Hàng đợi)', 'B' => 'Stack (Ngăn xếp)', 'C' => 'Linked List', 'D' => 'Array'],

      'correct' => 'B',

      'explanation' => 'Stack (Ngăn xếp) hoạt động theo nguyên tắc "Vào sau, Ra trước" (LIFO).'

    ],

    [

      'id' => 17,

      'category' => 'Lập trình Python',

      'question' => 'Trong Python, kiểu dữ liệu List khác với Tuple ở điểm cơ bản nào?',

      'code' => null,

      'options' => ['A' => 'List không thể thay đổi giá trị (Immutable)', 'B' => 'Tuple có thể thay đổi giá trị (Mutable)', 'C' => 'List cho phép chỉnh sửa (Mutable), còn Tuple thì không (Immutable)', 'D' => 'Không có sự khác biệt'],

      'correct' => 'C',

      'explanation' => 'List trong Python có thể thêm/sửa/xóa phần tử (mutable), trong khi Tuple là hằng số dữ liệu (immutable).'

    ],

    [

      'id' => 18,

      'category' => 'Kiến trúc Phần mềm',

      'question' => 'Mô hình thiết kế phần mềm chia ứng dụng thành Model - View - Controller gọi tắt là gì?',

      'code' => null,

      'options' => ['A' => 'MVVM', 'B' => 'MVC', 'C' => 'Microservices', 'D' => 'Monolithic'],

      'correct' => 'B',

      'explanation' => 'MVC là viết tắt của Model - View - Controller, kiến trúc thiết kế phổ biến trong lập trình.'

    ],

    [

      'id' => 19,

      'category' => 'An toàn thông tin',

      'question' => 'Hình thức mã hóa nào sử dụng một cặp khóa: Cặp khóa công khai (Public key) và Khóa bí mật (Private key)?',

      'code' => null,

      'options' => ['A' => 'Mã hóa đối xứng (Symmetric)', 'B' => 'Mã hóa bất đối xứng (Asymmetric)', 'C' => 'Hàm băm (Hashing)', 'D' => 'Mã hóa một chiều'],

      'correct' => 'B',

      'explanation' => 'Mã hóa bất đối xứng dùng cặp Public Key (để mã hóa) và Private Key (để giải mã).'

    ],

    [

      'id' => 20,

      'category' => 'Quản lý Dự án IT',

      'question' => 'Khung làm việc (Framework) phổ biến nhất áp dụng phương pháp luận Agile trong phát triển phần mềm là gì?',

      'code' => null,

      'options' => ['A' => 'Waterfall', 'B' => 'Scrum', 'C' => 'Kanban', 'D' => 'PRINCE2'],

      'correct' => 'B',

      'explanation' => 'Scrum là khung làm việc Agile phổ biến hàng đầu trong các đội ngũ phát triển phần mềm hiện đại.'

    ]

  ]

];

include 'includes/header_guest.php';

?>

<style>

  :root {

    --primary: #6366f1;

    --accent: #8b5cf6;

    --bg: #f8fafc;

  }

  body {

    background: var(--bg);

    color: #0f172a;

  }

  .btn-purple {

    background: linear-gradient(135deg, #8b5cf6, #6366f1);

    color: #fff !important;

    border: none;

  }

  .btn-purple:hover {

    background: linear-gradient(135deg, #7c3aed, #4f46e5);

  }

  .text-purple {

    color: var(--accent) !important;

  }

  .avatar-circle {

    width: 48px;

    height: 48px;

    border-radius: 50%;

    background: rgba(139, 92, 246, .12);

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;

    color: var(--accent);

  }

  .code-block {

    background: #1e293b;

    color: #38bdf8;

    padding: 12px 16px;

    border-radius: 8px;

    font-family: monospace;

    font-size: 0.9rem;

    margin-top: 10px;

  }

  .option-box {

    border: 1px solid #cbd5e1;

    border-radius: 10px;

    padding: 10px 14px;

    margin-bottom: 8px;

    cursor: pointer;

    transition: all .2s ease;

  }

  .option-box:hover {

    background: #f1f5f9;

    border-color: var(--accent);

  }

  .option-box input:checked+label {

    font-weight: bold;

    color: var(--accent);

  }

  .progress {

    height: 10px;

    border-radius: 20px;

  }

  .q-palette-grid {

    display: grid;

    grid-template-columns: repeat(4, 1fr);

    gap: 8px;

  }

  .q-palette-btn {

    width: 100%;

    height: 38px;

    border-radius: 8px;

    border: 1px solid #cbd5e1;

    background: #fff;

    font-weight: 600;

    font-size: 0.85rem;

    display: flex;

    align-items: center;

    justify-content: center;

    position: relative;

    transition: all .2s ease;

  }

  .q-palette-btn.answered {

    background-color: #6366f1;

    color: #fff;

    border-color: #6366f1;

  }

  .q-palette-btn.bookmarked::after {

    content: '';

    position: absolute;

    top: 2px;

    right: 2px;

    width: 8px;

    height: 8px;

    background-color: #ef4444;

    border-radius: 50%;

  }

  .q-palette-btn.res-correct {

    background-color: #22c55e !important;

    color: #fff !important;

    border-color: #22c55e !important;

  }

  .q-palette-btn.res-wrong {

    background-color: #ef4444 !important;

    color: #fff !important;

    border-color: #ef4444 !important;

  }

  .btn-bookmark {

    background: transparent;

    border: none;

    color: #94a3b8;

    font-size: 1.2rem;

  }

  .btn-bookmark.active {

    color: #ef4444;

  }

  .sticky-sidebar {

    position: sticky;

    top: 20px;

  }

  .level-badge {

    font-size: 1.15rem;

    padding: 10px 20px;

    border-radius: 50px;

    font-weight: bold;

    display: inline-block;

  }

  /* ===== DEMO MODERN UI 2026 ===== */

  :root {

    --demo-primary: #6d4aff;

    --demo-primary-2: #8b5cf6;

    --demo-ink: #17133c;

    --demo-muted: #697386;

    --demo-line: #e8e7f4

  }

  body {

    background: radial-gradient(circle at 8% 8%, rgba(139, 92, 246, .10), transparent 28%), radial-gradient(circle at 92% 24%, rgba(99, 102, 241, .08), transparent 24%), #f8f9fd

  }

  .container.py-4 {

    padding-top: 34px !important;

    padding-bottom: 56px !important

  }

  #tab-waiting-room .card {

    overflow: hidden;

    position: relative;

    border: 1px solid rgba(109, 74, 255, .10) !important;

    background: rgba(255, 255, 255, .95);

    box-shadow: 0 24px 70px rgba(46, 32, 105, .10) !important

  }

  #tab-waiting-room .card::before {

    content: "";

    position: absolute;

    left: 0;

    right: 0;

    top: 0;

    height: 5px;

    background: linear-gradient(90deg, #5b4bff, #8b5cf6, #d946ef)

  }

  #tab-waiting-room h2 {

    font-size: clamp(1.55rem, 3vw, 2.15rem);

    letter-spacing: -.035em;

    color: var(--demo-ink)

  }

  #tab-waiting-room p {

    font-size: .95rem !important

  }

  .demo-kicker {

    display: inline-flex;

    align-items: center;

    padding: 8px 13px;

    border-radius: 999px;

    background: #f0edff;

    color: #6547e8;

    border: 1px solid #e3ddff;

    font-size: .75rem;

    font-weight: 800;

    letter-spacing: .04em

  }

  .demo-overview-grid {

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 12px;

    text-align: left

  }

  .demo-overview-item {

    min-height: 118px;

    padding: 16px;

    border: 1px solid var(--demo-line);

    border-radius: 18px;

    background: linear-gradient(145deg, #fff, #faf9ff);

    transition: .25s ease

  }

  .demo-overview-item:hover {

    transform: translateY(-4px);

    box-shadow: 0 14px 30px rgba(91, 75, 255, .10);

    border-color: #d9d1ff

  }

  .demo-overview-icon {

    width: 38px;

    height: 38px;

    border-radius: 12px;

    display: grid;

    place-items: center;

    margin-bottom: 10px;

    background: linear-gradient(135deg, #ede9fe, #f5e8ff);

    color: #7047eb

  }

  .demo-overview-item strong {

    display: block;

    color: #221a4d;

    font-size: .9rem

  }

  .demo-overview-item span {

    display: block;

    color: #7b8191;

    font-size: .76rem;

    margin-top: 3px;

    line-height: 1.45

  }

  .btn-purple {

    background: linear-gradient(135deg, #6047f5, #8b5cf6 55%, #a855f7) !important;

    box-shadow: 0 10px 24px rgba(109, 74, 255, .22) !important;

    transition: .22s ease

  }

  .btn-purple:hover {

    transform: translateY(-2px);

    box-shadow: 0 14px 30px rgba(109, 74, 255, .30) !important

  }

  #exam-header-bar .row {

    background: #fff;

    border: 1px solid var(--demo-line);

    border-radius: 20px;

    padding: 18px 20px;

    box-shadow: 0 8px 24px rgba(30, 20, 70, .05)

  }

  #exam-header-bar h3 {

    font-size: 1.25rem;

    letter-spacing: -.02em;

    color: var(--demo-ink)

  }

  #demo-timer {

    font-family: 'IBM Plex Mono', monospace;

    font-size: 1.2rem !important

  }

  #exam-header-bar .card {

    border: 1px solid #efe8ff !important;

    border-radius: 14px !important;

    box-shadow: none !important;

    background: #faf8ff

  }

  .progress {

    height: 7px;

    background: #eceaf5;

    overflow: hidden

  }

  .progress-bar {

    background: linear-gradient(90deg, #6047f5, #a855f7) !important

  }

  .single-q-box {

    border: 1px solid var(--demo-line) !important;

    box-shadow: 0 8px 25px rgba(32, 25, 68, .045) !important;

    transition: .2s ease

  }

  .single-q-box:hover {

    transform: translateY(-2px);

    box-shadow: 0 14px 32px rgba(32, 25, 68, .075) !important;

    border-color: #dcd6ff !important

  }

  .single-q-box h5 {

    font-size: 1rem;

    line-height: 1.55;

    color: #25203f

  }

  .single-q-box .badge.btn-purple {

    font-size: .72rem;

    padding: 7px 9px;

    box-shadow: none !important

  }

  .option-box {

    border: 1px solid #e2e5ee;

    border-radius: 14px;

    padding: 12px 14px;

    margin-bottom: 10px;

    background: #fff;

    transition: .18s ease

  }

  .option-box:hover {

    background: #faf9ff;

    border-color: #bcb1ff;

    transform: translateX(2px)

  }

  .option-box:has(input:checked) {

    background: #f3f0ff;

    border-color: #8b73ff;

    box-shadow: 0 0 0 3px rgba(109, 74, 255, .07)

  }

  .form-check-input:checked {

    background-color: #6d4aff;

    border-color: #6d4aff

  }

  .code-block {

    background: #15142b;

    color: #d8d7ff;

    border: 1px solid #29264b;

    border-radius: 14px;

    padding: 15px 17px;

    font-size: .82rem;

    line-height: 1.6

  }

  .sticky-sidebar {

    top: 88px;

    border: 1px solid var(--demo-line) !important;

    box-shadow: 0 10px 30px rgba(30, 20, 70, .06) !important

  }

  .q-palette-grid {

    grid-template-columns: repeat(5, 1fr);

    gap: 7px

  }

  .q-palette-btn {

    height: 38px;

    border-radius: 10px;

    border-color: #e0deeb;

    font-size: .78rem

  }

  .q-palette-btn:hover {

    border-color: #8b73ff;

    color: #6047f5;

    background: #f5f2ff

  }

  .q-palette-btn.answered {

    background: linear-gradient(135deg, #6047f5, #8b5cf6);

    border-color: transparent

  }

  #demo-result-top-box>div:first-child {

    border: 1px solid #e5e0ff !important;

    background: radial-gradient(circle at 50% 0%, rgba(139, 92, 246, .12), transparent 32%), #fff !important

  }

  #demo-result-top-box h3 {

    font-size: 1.55rem;

    letter-spacing: -.025em;

    color: var(--demo-ink)

  }

  .level-badge {

    font-size: .9rem !important;

    padding: 9px 16px !important

  }

  .explanation-box {

    font-size: .88rem;

    line-height: 1.65

  }

  .pagination .page-link {

    border-radius: 9px !important;

    margin: 0 2px;

    color: #6047f5;

    border-color: #e6e3f0

  }

  .pagination .active>.page-link {

    background: #6d4aff;

    border-color: #6d4aff

  }

  .modal-content {

    border: 1px solid #ebe7ff !important

  }

  @keyframes demoEnter {

    from {

      opacity: 0;

      transform: translateY(14px)

    }

    to {

      opacity: 1;

      transform: none

    }

  }

  #tab-waiting-room,

  #tab-exam-room:not(.d-none) {

    animation: demoEnter .45s ease both

  }

  @media(max-width:991.98px) {

    .sticky-sidebar {

      position: static

    }

    .q-palette-grid {

      grid-template-columns: repeat(10, 1fr)

    }

  }

  @media(max-width:767.98px) {

    .container.py-4 {

      padding-top: 20px !important

    }

    #tab-waiting-room .card {

      padding: 26px 18px !important

    }

    .demo-overview-grid {

      grid-template-columns: 1fr

    }

    .demo-overview-item {

      min-height: auto

    }

    .single-q-box {

      padding: 18px !important

    }

    .q-palette-grid {

      grid-template-columns: repeat(5, 1fr)

    }

    #exam-header-bar .row {

      padding: 15px

    }

  }

  @media (max-width: 991.98px) {
    .practice-demo-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .practice-demo-card:last-child { grid-column:1/-1; max-width:520px; width:100%; justify-self:center; }
  }
  @media (max-width: 575.98px) {
    .practice-demo-section { padding-top:24px; }
    .practice-demo-grid { grid-template-columns:1fr; gap:12px; }
    .practice-demo-card, .practice-demo-card:last-child { min-height:0; max-width:none; grid-column:auto; }
    .practice-demo-card { padding:20px; }
    .practice-demo-card p { min-height:0; }
    .practice-demo-box { padding:16px; border-radius:18px; }
    .practice-game-title .game-mini-icon { width:34px; height:34px; flex:0 0 34px; }
  }

  @media(prefers-reduced-motion:reduce) {

    * {

      scroll-behavior: auto !important;

      animation: none !important;

      transition: none !important

    }

  }

  .practice-demo-section {
    max-width: 1080px;
    margin: 0 auto;
    padding: 34px 0 8px;
  }

  .practice-demo-head { text-align: center; margin-bottom: 24px; }
  .practice-demo-head h3 {
    font-size: clamp(1.35rem, 2.5vw, 1.7rem);
    font-weight: 800; color: var(--demo-ink); margin: 10px 0 7px; letter-spacing: -.025em;
  }
  .practice-demo-head p {
    color: #73798a; max-width: 720px; margin: auto; font-size: .92rem; line-height: 1.65;
  }
  .practice-demo-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 18px; }
  .practice-demo-card {
    position: relative; overflow: hidden; min-height: 275px; display: flex; flex-direction: column;
    background: #fff; border: 1px solid var(--demo-line); border-radius: 22px; padding: 24px;
    text-align: left; box-shadow: 0 8px 26px rgba(32,25,68,.055);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  }
  .practice-demo-card::after {
    content: ''; position: absolute; right: -35px; bottom: -45px; width: 125px; height: 125px;
    border-radius: 50%; background: rgba(104,71,245,.045); pointer-events: none;
  }
  .practice-demo-card:hover { transform: translateY(-5px); border-color: #d8d1ff; box-shadow: 0 18px 38px rgba(74,54,165,.11); }
  .practice-number { position:absolute; top:22px; right:22px; font-size:.72rem; font-weight:800; color:#a2a0b0; letter-spacing:.08em; }
  .practice-demo-icon {
    width:52px; height:52px; border-radius:15px; display:grid; place-items:center;
    background:#f0edff; color:#6847f5; margin-bottom:18px; font-size:1.3rem;
  }
  .practice-demo-icon.green { background:#ecfdf5; color:#059669; }
  .practice-demo-icon.amber { background:#fffbeb; color:#d97706; }
  .practice-demo-card h4 { font-size:1.08rem; font-weight:800; color:var(--demo-ink); margin-bottom:9px; }
  .practice-demo-card p { font-size:.84rem; color:#73798a; line-height:1.65; min-height:68px; margin-bottom:20px; }
  .practice-demo-card .btn { margin-top:auto; align-self:flex-start; min-width:145px; font-weight:700; }
  .practice-demo-box {
    margin-top:20px; background:#fff; border:1px solid #e4e0ff; border-radius:22px;
    padding:clamp(18px,3vw,28px); box-shadow:0 14px 38px rgba(74,54,165,.09);
  }
  .practice-game-title { display:flex; align-items:center; gap:10px; }
  .practice-game-title .game-mini-icon { width:38px; height:38px; display:grid; place-items:center; border-radius:11px; background:#f0edff; color:#6847f5; }
  .practice-columns-title { font-size:.72rem; font-weight:800; color:#77758a; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
  .practice-column { background:#fafaff; border:1px solid #ece9fa; border-radius:16px; padding:12px; height:100%; }
  .practice-timer { min-width:70px; text-align:center; border-radius:999px; padding:7px 12px; background:#fff7ed; color:#c2410c; font-weight:800; font-size:.82rem; }

  .pd-option,

  .pd-match {

    display: block;

    width: 100%;

    border: 1px solid #e4e4ed;

    background: #fff;

    border-radius: 11px;

    padding: 11px 13px;

    margin: 8px 0;

    text-align: left

  }

  .pd-option:hover,

  .pd-match:hover {

    border-color: #9d8cff;

    background: #faf9ff

  }

  .pd-good {

    background: #ecfdf5 !important;

    border-color: #22c55e !important

  }

  .pd-bad {

    background: #fef2f2 !important;

    border-color: #ef4444 !important

  }

  .pd-selected {

    outline: 3px solid rgba(104, 71, 245, .15);

    border-color: #6847f5 !important

  }

  .pd-locked {

    background: #ecfdf5 !important;

    border-color: #86efac !important;

    pointer-events: none

  }

  .pd-cw {

    display: flex;

    gap: 6px;

    flex-wrap: wrap

  }

  .pd-cw input {

    width: 46px;

    height: 46px;

    text-align: center;

    text-transform: uppercase;

    font-weight: 800;

    border: 1.5px solid #d9d6e5;

    border-radius: 8px

  }

  @media(max-width:767px) {

    .practice-demo-grid {

      grid-template-columns: 1fr

    }

    .practice-demo-card p {

      min-height: auto

    }

  }

</style>

<div class="container py-4">

  <!-- MÀN HÌNH 1: PHÒNG CHỜ THI (MỤC 3) -->

  <div id="tab-waiting-room">

    <div class="row justify-content-center">

      <div class="col-lg-8">

        <div class="card border-0 shadow-sm p-4 p-md-5 text-center rounded-4">

          <div class="mb-3">

            <span class="demo-kicker"><i class="bi bi-stars me-1"></i> IT SKILL CHECK • MIỄN PHÍ</span>

          </div>

          <!-- Tên Đề Thi & Mô Tả -->

          <h2 class="fw-bold mb-3"><?= e($demo_exam['title']) ?></h2>

          <p class="text-secondary fs-6 mb-4 leading-relaxed" style="max-width: 650px; margin: 0 auto;">

            <?= e($demo_exam['description']) ?>

          </p>

          <!-- Tổng quan bài đánh giá -->

          <div class="demo-overview-grid mb-4">

            <div class="demo-overview-item">

              <div class="demo-overview-icon"><i class="bi bi-ui-checks-grid"></i></div><strong><?= count($demo_exam['questions']) ?> câu hỏi</strong><span>Kiến thức CNTT đa lĩnh vực</span>

            </div>

            <div class="demo-overview-item">

              <div class="demo-overview-icon"><i class="bi bi-clock-history"></i></div><strong><?= (int)$demo_exam['time_limit'] ?> phút</strong><span>Tự động nộp khi hết giờ</span>

            </div>

            <div class="demo-overview-item">

              <div class="demo-overview-icon"><i class="bi bi-graph-up-arrow"></i></div><strong>Phân tích kỹ năng</strong><span>Nhận định hướng sau bài làm</span>

            </div>

          </div>

          <!-- Quy định -->

          <div class="text-start bg-body-tertiary p-3 rounded-3 mb-4 small">

            <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Thông tin bài test:</div>

            <ul class="mb-0 ps-3 text-muted">

              <li>Thời gian làm bài: <strong><?= $demo_exam['time_limit'] ?> phút</strong>.</li>

              <li>Số lượng: <strong><?= count($demo_exam['questions']) ?> câu hỏi trắc nghiệm</strong>.</li>

              <li>Hệ thống sẽ tự động tổng hợp phân tích trình độ IT ngay khi hoàn thành.</li>

            </ul>

          </div>

          <!-- Nút Vào Làm Bài -->

          <button onclick="startExamNow()" class="btn btn-purple btn-lg fw-bold rounded-pill px-5 py-3 shadow">

            <i class="bi bi-play-circle-fill me-2 fs-5"></i> Bắt đầu đánh giá

          </button>

        </div>

      </div>

    </div>

    <!-- TRẢI NGHIỆM PRACTICE -->

    <section class="practice-demo-section mt-4">

      <div class="practice-demo-head">
        <div>
          <span class="demo-kicker"><i class="bi bi-controller me-1"></i> PRACTICE PREVIEW</span>
          <h3>3 chế độ luyện tập</h3>
          <p>Chọn một hình thức để luyện tập kiến thức CNTT. Mỗi chế độ tập trung vào một kỹ năng khác nhau: ghi nhớ, ghép nghĩa và phản xạ nhanh.</p>
        </div>
      </div>

      <div class="practice-demo-grid">
        <article class="practice-demo-card">
          <span class="practice-number">01</span>
          <div class="practice-demo-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
          <h4>Daily Crossword</h4>
          <p>Điền từ vựng và thuật ngữ CNTT dựa trên gợi ý. Rèn khả năng ghi nhớ và hiểu nghĩa từ chuyên ngành.</p>
          <button type="button" class="btn btn-outline-primary rounded-pill" onclick="openPracticeDemo('crossword')"><i class="bi bi-play-fill me-1"></i>Bắt đầu</button>
        </article>

        <article class="practice-demo-card">
          <span class="practice-number">02</span>
          <div class="practice-demo-icon green"><i class="bi bi-diagram-2-fill"></i></div>
          <h4>Nối cặp từ</h4>
          <p>Chọn một từ ở cột bên trái và ghép với nghĩa tương ứng ở cột bên phải. Ghép đúng để hoàn thành lượt chơi.</p>
          <button type="button" class="btn btn-outline-primary rounded-pill" onclick="openPracticeDemo('match')"><i class="bi bi-play-fill me-1"></i>Bắt đầu</button>
        </article>

        <article class="practice-demo-card">
          <span class="practice-number">03</span>
          <div class="practice-demo-icon amber"><i class="bi bi-lightning-charge-fill"></i></div>
          <h4>Phản xạ nhanh</h4>
          <p>Trả lời nhanh các câu hỏi CNTT trong thời gian giới hạn để rèn tốc độ phản xạ và khả năng ghi nhớ.</p>
          <button type="button" class="btn btn-outline-primary rounded-pill" onclick="openPracticeDemo('quiz')"><i class="bi bi-play-fill me-1"></i>Bắt đầu</button>
        </article>
      </div>

      <div id="practice-demo-box" class="practice-demo-box d-none"></div>

    </section>

  </div>

  <!-- MÀN HÌNH 2: PHÒNG THI & BÀI LÀM (MẶC ĐỊNH ẨN) -->

  <div id="tab-exam-room" class="d-none">

    <!-- HEADER BẢNG KẾT QUẢ ĐƯỢC ĐƯA LÊN TRÊN CÙNG KHI NỘP BÀI -->

    <div id="demo-result-top-box" class="d-none mb-4">

      <!-- 1. KHỐI HOÀN THÀNH BÀI THI -->

      <div class="card border-0 shadow-lg p-4 p-md-5 text-center rounded-4 mb-4 bg-white">

        <div class="fs-1 text-purple mb-2"><i class="bi bi-trophy-fill"></i></div>

        <h3 class="fw-bold mb-1">Hoàn thành bài đánh giá!</h3>

        <p class="text-muted">Xem mức độ hiểu biết và các nội dung nên ưu tiên cải thiện.</p>

        <div class="row justify-content-center my-3 g-3">

          <div class="col-6 col-md-3">

            <div class="p-3 border rounded-3 bg-light">

              <small class="text-muted d-block fw-semibold">Điểm số</small>

              <span id="res-score" class="fs-2 fw-bold text-purple">0/10</span>

            </div>

          </div>

          <div class="col-6 col-md-3">

            <div class="p-3 border rounded-3 bg-light">

              <small class="text-muted d-block fw-semibold">Số câu đúng</small>

              <span id="res-correct" class="fs-2 fw-bold text-success">0/20</span>

            </div>

          </div>

        </div>

        <!-- 2. KHỐI MỨC ĐỘ HIỂU BIẾT NGÀNH IT -->

        <div class="card border-0 bg-light p-4 rounded-4 my-3 text-start">

          <div class="text-center mb-3">

            <small class="text-uppercase fw-bold text-muted d-block mb-1">Mức độ hiểu biết ngành IT của bạn</small>

            <div id="level-badge-container"></div>

          </div>

          <hr>

          <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-search me-2 text-purple"></i>Nhận xét & Định hướng phát triển:</h6>

          <p id="evaluation-text" class="mb-0 text-secondary" style="line-height: 1.7;"></p>

        </div>

        <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">

          <button onclick="location.reload()" class="btn btn-outline-secondary fw-bold px-4 rounded-pill">

            <i class="bi bi-arrow-counterclockwise me-1"></i> Làm lại bài

          </button>

          <a href="login.php" class="btn btn-purple fw-bold px-4 rounded-pill">

            <i class="bi bi-person-plus-fill me-1"></i> Đăng ký để luyện tập thêm

          </a>

        </div>

      </div>

      <div class="text-center my-4">

        <h4 class="fw-bold text-dark"><i class="bi bi-journal-check me-2 text-purple"></i>CHI TIẾT CÂU HỎI & GIẢI THÍCH ĐÁP ÁN</h4>

        <p class="text-muted small">Xem lại các câu trả lời đúng/sai của bạn ở danh sách bên dưới</p>

      </div>

    </div>

    <!-- Header Đồng hồ & Tiến trình -->

    <div id="exam-header-bar">

      <div class="row align-items-center mb-3">

        <div class="col-md-7">

          <h3 class="fw-bold mb-1"><?= e($demo_exam['title']) ?></h3>

          <span class="badge bg-primary">Bài đánh giá năng lực CNTT</span>

        </div>

        <div class="col-md-5 text-md-end mt-3 mt-md-0">

          <div class="card p-2 text-center d-inline-block border-danger shadow-sm">

            <small class="text-muted fw-semibold d-block">THỜI GIAN CÒN LẠI</small>

            <span id="demo-timer" class="fs-4 fw-bold text-danger">20:00</span>

          </div>

        </div>

      </div>

      <div class="card border-0 shadow-sm p-3 mb-4 rounded-4">

        <div class="d-flex justify-content-between align-items-center mb-2 small fw-bold">

          <span>Tiến độ làm bài: <span id="progress-text" class="text-purple">0/<?= count($demo_exam['questions']) ?></span> câu</span>

          <span id="progress-percent" class="text-muted">0%</span>

        </div>

        <div class="progress">

          <div id="progress-bar" class="progress-bar bg-purple" role="progressbar" style="width: 0%"></div>

        </div>

      </div>

    </div>

    <!-- Layout làm bài -->

    <div class="row g-4">

      <!-- Cột Trái: Danh sách câu hỏi -->

      <div class="col-lg-8">

        <form id="demo-quiz-form">

          <div id="questions-container">

            <?php foreach ($demo_exam['questions'] as $index => $q): ?>

              <div class="card border-0 shadow-sm p-4 mb-4 rounded-4 single-q-box" id="demo-q-box-<?= $q['id'] ?>" data-qindex="<?= $index ?>">

                <div class="d-flex justify-content-between align-items-center mb-2">

                  <span class="badge bg-secondary-subtle text-secondary small"><?= e($q['category']) ?></span>

                  <div class="d-flex align-items-center gap-2">

                    <button type="button" class="btn-bookmark" id="bookmark-btn-<?= $q['id'] ?>" onclick="toggleBookmark(<?= $q['id'] ?>)" title="Đánh dấu xem lại">

                      <i class="bi bi-bookmark-star-fill"></i>

                    </button>

                    <small class="text-muted">Câu <?= $index + 1 ?>/<?= count($demo_exam['questions']) ?></small>

                  </div>

                </div>

                <h5 class="fw-bold mb-2">

                  <span class="badge btn-purple me-2"><?= $index + 1 ?></span>

                  <?= e($q['question']) ?>

                </h5>

                <?php if (!empty($q['code'])): ?>

                  <pre class="code-block"><code><?= e($q['code']) ?></code></pre>

                <?php endif; ?>

                <div class="mt-3">

                  <?php foreach ($q['options'] as $key => $opt): ?>

                    <div class="option-box" onclick="selectOption(<?= $q['id'] ?>, '<?= $key ?>')">

                      <div class="form-check">

                        <input class="form-check-input quiz-radio" type="radio"

                          name="q_<?= $q['id'] ?>"

                          id="demo_q<?= $q['id'] ?>_<?= $key ?>"

                          value="<?= $key ?>"

                          onchange="onAnswerChange(<?= $q['id'] ?>)">

                        <label class="form-check-label w-100" for="demo_q<?= $q['id'] ?>_<?= $key ?>">

                          <strong><?= $key ?>.</strong> <?= e($opt) ?>

                        </label>

                      </div>

                    </div>

                  <?php endforeach; ?>

                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">

                  <button type="button" class="btn btn-sm btn-outline-secondary unselect-btn" onclick="unselectOption(<?= $q['id'] ?>)">

                    <i class="bi bi-x-circle me-1"></i> Bỏ chọn

                  </button>

                </div>

                <!-- 3. KHỐI GIẢI THÍCH ĐÚNG SAi -->

                <div class="explanation-box mt-3 p-3 rounded-3 d-none"></div>

              </div>

            <?php endforeach; ?>

          </div>

        </form>

        <!-- Phân trang -->

        <div class="card border-0 shadow-sm p-3 rounded-4 d-flex flex-row justify-content-between align-items-center mb-4 flex-wrap gap-2">

          <div class="d-flex align-items-center gap-2">

            <label class="small text-muted fw-semibold">Hiển thị:</label>

            <select id="items-per-page" class="form-select form-select-sm w-auto" onchange="changePerPage()">

              <option value="5" selected>5 câu / trang</option>

              <option value="10">10 câu / trang</option>

              <option value="<?= count($demo_exam['questions']) ?>">Hiển thị tất cả <?= count($demo_exam['questions']) ?> câu</option>

            </select>

          </div>

          <nav>

            <ul class="pagination pagination-sm mb-0" id="pagination-list"></ul>

          </nav>

        </div>

      </div>

      <!-- Cột Phải: Navigation Palette -->

      <div class="col-lg-4">

        <div class="card border-0 shadow-sm p-3 rounded-4 sticky-sidebar">

          <h6 class="fw-bold mb-3"><i class="bi bi-grid-3x3-gap-fill me-2 text-purple"></i>Danh sách câu hỏi</h6>

          <div class="q-palette-grid mb-3" id="q-palette">

            <?php foreach ($demo_exam['questions'] as $index => $q): ?>

              <button type="button" class="q-palette-btn" id="palette-btn-<?= $q['id'] ?>" onclick="jumpToQuestion(<?= $index ?>)">

                <?= $index + 1 ?>

              </button>

            <?php endforeach; ?>

          </div>

          <div class="border-top pt-3 small mb-3">

            <div class="d-flex align-items-center gap-2 mb-1">

              <span class="d-inline-block bg-primary rounded" style="width:12px; height:12px;"></span> Đã làm

            </div>

            <div class="d-flex align-items-center gap-2 mb-1">

              <span class="d-inline-block border rounded" style="width:12px; height:12px; background:#fff;"></span> Chưa làm

            </div>

            <div class="d-flex align-items-center gap-2">

              <span class="d-inline-block bg-danger rounded-circle" style="width:10px; height:10px;"></span> Đã đánh dấu

            </div>

          </div>

          <!-- Khu vực Nộp bài (Sẽ biến mất hoàn toàn sau khi nộp) -->

          <div id="submit-section">

            <hr>

            <button type="button" onclick="confirmSubmitModal()" class="btn btn-purple btn-lg w-100 fw-bold rounded-3 shadow-sm">

              <i class="bi bi-send-fill me-2"></i> Nộp bài kiểm tra

            </button>

          </div>

        </div>

      </div>

    </div>

  </div>

</div>

<!-- POPUP MODAL XÁC NHẬN NỘP BÀI -->

<div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered">

    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-body text-center p-4">

        <div class="mb-3 text-warning">

          <i class="bi bi-exclamation-circle-fill display-3"></i>

        </div>

        <h4 class="fw-bold mb-2">Bạn có chắc chắn muốn nộp bài?</h4>

        <p class="text-muted mb-3" id="modal-submit-stats">Bạn chưa trả lời câu hỏi nào.</p>

        <div id="modal-warning-text" class="alert alert-warning py-2 px-3 small d-none">

          <i class="bi bi-info-circle me-1"></i> Bạn vẫn còn một số câu chưa hoàn thành!

        </div>

        <div class="d-flex justify-content-center gap-2 mt-4">

          <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">

            Làm tiếp

          </button>

          <button type="button" onclick="executeSubmitExam()" class="btn btn-purple rounded-pill px-4 fw-bold">

            Xác nhận Nộp Bài

          </button>

        </div>

      </div>

    </div>

  </div>

</div>

<script>

  const PRACTICE_DEMO = {
    crossword: [
      { answer: 'STACK', clue: 'Cấu trúc dữ liệu hoạt động theo nguyên tắc LIFO.' },
      { answer: 'ROUTER', clue: 'Thiết bị định tuyến dữ liệu giữa các mạng.' },
      { answer: 'PYTHON', clue: 'Ngôn ngữ lập trình phổ biến trong AI và Data Science.' },
      { answer: 'KERNEL', clue: 'Thành phần lõi của hệ điều hành.' },
      { answer: 'CLOUD', clue: 'Mô hình cung cấp tài nguyên tính toán qua Internet.' }
    ],
    match: [
      ['API', 'Giao diện cho phép các phần mềm giao tiếp với nhau.'],
      ['GIT', 'Hệ thống quản lý phiên bản phân tán.'],
      ['FIREWALL', 'Cơ chế kiểm soát lưu lượng mạng theo quy tắc.'],
      ['SQL', 'Ngôn ngữ dùng để truy vấn và thao tác dữ liệu quan hệ.'],
      ['DNS', 'Hệ thống phân giải tên miền thành địa chỉ IP.']
    ],
    quiz: [
      { q: 'HTTP status code 404 thường biểu thị điều gì?', opts: ['OK', 'Unauthorized', 'Not Found', 'Server Error'], ans: 2 },
      { q: 'Trong SQL, từ khóa nào loại bỏ các dòng kết quả trùng lặp?', opts: ['UNIQUE', 'DISTINCT', 'FILTER', 'GROUP'], ans: 1 },
      { q: 'Cấu trúc dữ liệu nào hoạt động theo FIFO?', opts: ['Stack', 'Tree', 'Queue', 'Graph'], ans: 2 },
      { q: 'Lệnh Git nào tải và hợp nhất thay đổi mới từ remote?', opts: ['git push', 'git pull', 'git init', 'git status'], ans: 1 },
      { q: 'Phương thức HTTP nào thường được dùng để gửi dữ liệu tạo mới tài nguyên?', opts: ['GET', 'POST', 'DELETE', 'HEAD'], ans: 1 }
    ]
  };

  let pdTerm = null, pdCrosswordIndex = 0, pdCrosswordScore = 0;
  let pdMatchScore = 0, pdMatchTotal = 0;
  let pdQuizIndex = 0, pdQuizScore = 0, pdQuizTimer = null, pdQuizTime = 5, pdQuizLocked = false;

  function pdShuffle(arr) { return [...arr].sort(() => Math.random() - 0.5); }

  function pdHeader(title, subtitle, stat = '', icon = 'bi-controller') {
    return `<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
      <div class="practice-game-title">
        <div class="game-mini-icon"><i class="bi ${icon}"></i></div>
        <div><b class="fs-5">${title}</b><div class="small text-muted mt-1">${subtitle}</div></div>
      </div>
      <div class="d-flex align-items-center gap-2 flex-shrink-0">${stat}<button type="button" class="btn-close" aria-label="Đóng" onclick="closePracticeDemo()"></button></div>
    </div>`;
  }

  function pdFinish(title, score, total, type) {
    const percent = total ? Math.round((score / total) * 100) : 0;
    return `${pdHeader(title, 'Bạn đã hoàn thành lượt luyện tập.', `<span class="badge text-bg-light">${score}/${total}</span>`)}
      <div class="text-center py-3">
        <div class="fs-1 text-purple mb-2"><i class="bi bi-trophy-fill"></i></div>
        <h4 class="fw-bold">Hoàn thành!</h4><div class="display-6 fw-bold text-purple">${score}/${total}</div>
        <div class="text-muted mb-4">Độ chính xác ${percent}%</div>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <button type="button" class="btn btn-purple rounded-pill px-4" onclick="openPracticeDemo('${type}')"><i class="bi bi-arrow-counterclockwise me-1"></i>Chơi lại</button>
          <a href="login.php" class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-person-check me-1"></i>Đăng nhập để lưu kết quả</a>
          <button type="button" class="btn btn-light rounded-pill px-4" onclick="closePracticeDemo()">Đóng</button>
        </div>
      </div>`;
  }

  function openPracticeDemo(type) {
    clearInterval(pdQuizTimer);
    const box = document.getElementById('practice-demo-box'); if (!box) return;
    box.classList.remove('d-none');
    if (type === 'crossword') { pdCrosswordIndex = 0; pdCrosswordScore = 0; renderPracticeCrossword(); }
    else if (type === 'match') { pdMatchScore = 0; pdMatchTotal = PRACTICE_DEMO.match.length; pdTerm = null; renderPracticeMatch(); }
    else { pdQuizIndex = 0; pdQuizScore = 0; renderPracticeQuiz(); }
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function closePracticeDemo() {
    clearInterval(pdQuizTimer);
    const box = document.getElementById('practice-demo-box'); if (!box) return;
    box.classList.add('d-none'); box.innerHTML = '';
  }

  function renderPracticeCrossword() {
    const box = document.getElementById('practice-demo-box');
    if (pdCrosswordIndex >= PRACTICE_DEMO.crossword.length) { box.innerHTML = pdFinish('Daily Crossword', pdCrosswordScore, PRACTICE_DEMO.crossword.length, 'crossword'); return; }
    const x = PRACTICE_DEMO.crossword[pdCrosswordIndex];
    box.innerHTML = `${pdHeader('Daily Crossword', 'Điền từ khóa dựa trên gợi ý để hoàn thành thử thách.', `<span class="badge text-bg-light">${pdCrosswordIndex + 1}/${PRACTICE_DEMO.crossword.length}</span>`, 'bi-grid-3x3-gap-fill')}
      <div class="d-flex justify-content-between align-items-center small mb-3"><span>Điểm: <b class="text-success">${pdCrosswordScore}</b></span><span class="text-muted">Điền ${x.answer.length} ký tự</span></div>
      <div class="p-3 p-md-4 rounded-4 bg-light border mb-3"><div class="small text-uppercase fw-bold text-muted mb-2">Gợi ý</div><div class="fw-semibold">${x.clue}</div></div>
      <div class="pd-cw mb-3 justify-content-center">${[...x.answer].map((_,i)=>`<input maxlength="1" data-pdcw="${i}" autocomplete="off" oninput="pdCrosswordInput(this,${i})" onkeydown="pdCrosswordKey(event,${i})">`).join('')}</div>
      <div class="d-flex align-items-center flex-wrap gap-2"><button type="button" class="btn btn-purple rounded-pill px-4" onclick="checkPracticeCrossword()"><i class="bi bi-check2-circle me-1"></i>Kiểm tra</button><span id="pd-msg" class="small"></span></div>`;
    document.querySelector('[data-pdcw="0"]')?.focus();
  }

  function pdCrosswordInput(el, i) { el.value = el.value.replace(/[^a-zA-Z]/g,'').slice(-1).toUpperCase(); el.classList.remove('pd-good','pd-bad'); if(el.value) document.querySelector(`[data-pdcw="${i+1}"]`)?.focus(); }
  function pdCrosswordKey(e, i) { if(e.key==='Backspace' && !e.target.value && i>0) document.querySelector(`[data-pdcw="${i-1}"]`)?.focus(); if(e.key==='Enter') checkPracticeCrossword(); }
  function checkPracticeCrossword() {
    const x=PRACTICE_DEMO.crossword[pdCrosswordIndex], inputs=[...document.querySelectorAll('[data-pdcw]')], answer=inputs.map(el=>el.value).join(''), msg=document.getElementById('pd-msg'); if(!msg) return;
    if(answer.length<x.answer.length){ msg.className='small text-danger'; msg.textContent='Hãy nhập đủ các ô.'; return; }
    const ok=answer===x.answer; inputs.forEach((el,i)=>{el.classList.remove('pd-good','pd-bad');el.classList.add(el.value===x.answer[i]?'pd-good':'pd-bad');});
    if(ok){ pdCrosswordScore++; msg.className='small text-success fw-semibold'; msg.textContent='Chính xác!'; setTimeout(()=>{pdCrosswordIndex++;renderPracticeCrossword();},650); }
    else { msg.className='small text-danger'; msg.textContent='Chưa đúng, kiểm tra lại các ô màu đỏ.'; }
  }

  function renderPracticeMatch() {
    const box=document.getElementById('practice-demo-box'); if(pdMatchScore>=pdMatchTotal){box.innerHTML=pdFinish('Nối cặp từ',pdMatchScore,pdMatchTotal,'match');return;}
    const d=PRACTICE_DEMO.match, defs=pdShuffle(d);
    box.innerHTML=`${pdHeader('Nối cặp từ','Chọn từ ở cột trái, sau đó chọn nghĩa tương ứng ở cột phải.',`<span class="badge text-bg-light">${pdMatchScore}/${pdMatchTotal}</span>`,'bi-diagram-2-fill')}
      <div class="row g-3"><div class="col-12 col-md-6"><div class="practice-column"><div class="practice-columns-title">Từ vựng</div><div class="d-grid gap-2">${d.map((x,i)=>`<button type="button" class="pd-match text-start p-3" data-i="${i}" onclick="pdPickTerm(this)"><span class="fw-bold">${x[0]}</span><span class="small text-muted d-block mt-1">Chọn từ này</span></button>`).join('')}</div></div></div>
      <div class="col-12 col-md-6"><div class="practice-column"><div class="practice-columns-title">Nghĩa / Định nghĩa</div><div class="d-grid gap-2">${defs.map(x=>{const i=d.findIndex(y=>y[0]===x[0]);return `<button type="button" class="pd-match text-start p-3" data-i="${i}" onclick="pdPickDef(this)">${x[1]}</button>`}).join('')}</div></div></div></div>
      <div class="d-flex align-items-center gap-2 mt-3"><i class="bi bi-info-circle text-primary"></i><div id="pd-msg" class="small text-muted">Chọn một từ ở cột trái để bắt đầu.</div></div>`;
    pdTerm=null;
  }

  function pdPickTerm(el){ if(el.classList.contains('pd-locked')) return; document.querySelectorAll('.pd-match').forEach(x=>x.classList.remove('pd-selected')); el.classList.add('pd-selected'); pdTerm=el; const msg=document.getElementById('pd-msg'); if(msg) msg.textContent='Bây giờ chọn nghĩa tương ứng ở cột bên phải.'; }
  function pdPickDef(el){
    if(!pdTerm || el.classList.contains('pd-locked')) return; const msg=document.getElementById('pd-msg'), ok=pdTerm.dataset.i===el.dataset.i;
    if(ok){ pdTerm.classList.add('pd-locked','pd-good'); el.classList.add('pd-locked','pd-good'); pdTerm.classList.remove('pd-selected'); pdTerm=null; pdMatchScore++; if(msg){msg.className='small text-success fw-semibold';msg.textContent='Ghép đúng!';} setTimeout(renderPracticeMatch,450); }
    else { el.classList.add('pd-bad'); if(msg){msg.className='small text-danger';msg.textContent='Chưa đúng. Hãy thử lại.';} setTimeout(()=>el.classList.remove('pd-bad'),450); }
  }

  function renderPracticeQuiz(){
    clearInterval(pdQuizTimer); const box=document.getElementById('practice-demo-box'); if(pdQuizIndex>=PRACTICE_DEMO.quiz.length){box.innerHTML=pdFinish('Phản xạ nhanh',pdQuizScore,PRACTICE_DEMO.quiz.length,'quiz');return;}
    const q=PRACTICE_DEMO.quiz[pdQuizIndex]; pdQuizTime=5; pdQuizLocked=false;
    box.innerHTML=`${pdHeader('Phản xạ nhanh','Trả lời trong 5 giây. Càng nhanh và chính xác càng tốt.',`<span class="practice-timer" id="pd-timer"><i class="bi bi-stopwatch me-1"></i>5s</span>`,'bi-lightning-charge-fill')}
      <div class="d-flex justify-content-between align-items-center mb-3 small text-muted"><span>Câu ${pdQuizIndex+1}/${PRACTICE_DEMO.quiz.length}</span><span>Điểm: <b class="text-success">${pdQuizScore}</b></span></div>
      <div class="p-3 p-md-4 rounded-4 bg-light border mb-3"><div class="small text-uppercase fw-bold text-muted mb-2">Câu hỏi</div><h5 class="fw-bold mb-0">${q.q}</h5></div>
      <div class="d-grid gap-2">${q.opts.map((x,i)=>`<button type="button" class="pd-option text-start p-3" onclick="pdQuiz(this,${i})"><b>${String.fromCharCode(65+i)}.</b> ${x}</button>`).join('')}</div><div id="pd-msg" class="small mt-3"></div>`;
    pdQuizTimer=setInterval(()=>{pdQuizTime--;const timer=document.getElementById('pd-timer');if(timer)timer.innerHTML=`<i class="bi bi-stopwatch me-1"></i>${pdQuizTime}s`;if(pdQuizTime<=0){clearInterval(pdQuizTimer);if(!pdQuizLocked)pdQuiz(null,-1);}},1000);
  }

  function pdQuiz(el,i){
    if(pdQuizLocked)return; pdQuizLocked=true; clearInterval(pdQuizTimer); const q=PRACTICE_DEMO.quiz[pdQuizIndex], options=document.querySelectorAll('.pd-option'); options.forEach(x=>x.disabled=true);
    const ok=i===q.ans; if(el)el.classList.add(ok?'pd-good':'pd-bad'); if(!ok && options[q.ans])options[q.ans].classList.add('pd-good');
    const msg=document.getElementById('pd-msg'); if(msg){msg.className=`small mt-3 ${ok?'text-success fw-semibold':'text-danger'}`;msg.textContent=ok?'Chính xác!':(i===-1?'Hết thời gian!':'Chưa đúng!');}
    setTimeout(()=>{pdQuizIndex++;renderPracticeQuiz();},850);
  }

</script>

<?php include 'includes/footer.php'; ?>

<script>

  let timerInterval = null;

  const examData = <?= json_encode($demo_exam) ?>;

  let currentPage = 1;

  let itemsPerPage = 5;

  let bookmarkedQs = new Set();

  let isSubmitted = false;

  let submitModalInstance = null;

  document.addEventListener('DOMContentLoaded', function() {

    submitModalInstance = new bootstrap.Modal(document.getElementById('submitConfirmModal'));

  });

  // Bắt đầu làm bài từ Mục 3 (Phòng chờ)

  function startExamNow() {

    document.getElementById('tab-waiting-room').classList.add('d-none');

    document.getElementById('tab-exam-room').classList.remove('d-none');

    startTimer();

    renderPage();

    window.scrollTo({

      top: 0,

      behavior: 'smooth'

    });

  }

  // Xử lý Phân trang

  function renderPage() {

    const totalItems = examData.questions.length;

    const totalPages = Math.ceil(totalItems / itemsPerPage);

    if (currentPage > totalPages) currentPage = totalPages;

    if (currentPage < 1) currentPage = 1;

    const startIdx = (currentPage - 1) * itemsPerPage;

    const endIdx = startIdx + itemsPerPage;

    examData.questions.forEach((q, idx) => {

      const qBox = document.getElementById(`demo-q-box-${q.id}`);

      if (idx >= startIdx && idx < endIdx) {

        qBox.classList.remove('d-none');

      } else {

        qBox.classList.add('d-none');

      }

    });

    const pageList = document.getElementById('pagination-list');

    pageList.innerHTML = '';

    for (let i = 1; i <= totalPages; i++) {

      const li = document.createElement('li');

      li.className = `page-item ${i === currentPage ? 'active' : ''}`;

      li.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="goToPage(${i})">${i}</a>`;

      pageList.appendChild(li);

    }

  }

  function goToPage(page) {

    currentPage = page;

    renderPage();

    window.scrollTo({

      top: 100,

      behavior: 'smooth'

    });

  }

  function changePerPage() {

    itemsPerPage = parseInt(document.getElementById('items-per-page').value);

    currentPage = 1;

    renderPage();

  }

  function jumpToQuestion(qIndex) {

    currentPage = Math.floor(qIndex / itemsPerPage) + 1;

    renderPage();

    const qId = examData.questions[qIndex].id;

    const target = document.getElementById(`demo-q-box-${qId}`);

    if (target) {

      target.scrollIntoView({

        behavior: 'smooth',

        block: 'center'

      });

    }

  }

  function selectOption(qId, key) {

    if (isSubmitted) return;

    const radio = document.getElementById(`demo_q${qId}_${key}`);

    if (radio) {

      radio.checked = true;

      onAnswerChange(qId);

    }

  }

  function unselectOption(qId) {

    if (isSubmitted) return;

    const checked = document.querySelector(`input[name="q_${qId}"]:checked`);

    if (checked) {

      checked.checked = false;

      onAnswerChange(qId);

    }

  }

  function toggleBookmark(qId) {

    const btn = document.getElementById(`bookmark-btn-${qId}`);

    const paletteBtn = document.getElementById(`palette-btn-${qId}`);

    if (bookmarkedQs.has(qId)) {

      bookmarkedQs.delete(qId);

      btn.classList.remove('active');

      paletteBtn.classList.remove('bookmarked');

    } else {

      bookmarkedQs.add(qId);

      btn.classList.add('active');

      paletteBtn.classList.add('bookmarked');

    }

  }

  function onAnswerChange(qId) {

    const isAnswered = !!document.querySelector(`input[name="q_${qId}"]:checked`);

    const paletteBtn = document.getElementById(`palette-btn-${qId}`);

    if (isAnswered) {

      paletteBtn.classList.add('answered');

    } else {

      paletteBtn.classList.remove('answered');

    }

    updateProgress();

  }

  function getAnsweredCount() {

    let answered = 0;

    examData.questions.forEach(q => {

      if (document.querySelector(`input[name="q_${q.id}"]:checked`)) answered++;

    });

    return answered;

  }

  function updateProgress() {

    const total = examData.questions.length;

    const answered = getAnsweredCount();

    const percent = Math.round((answered / total) * 100);

    document.getElementById('progress-text').textContent = `${answered}/${total}`;

    document.getElementById('progress-percent').textContent = `${percent}%`;

    document.getElementById('progress-bar').style.width = `${percent}%`;

  }

  function startTimer() {

    clearInterval(timerInterval);

    let timeLeft = examData.time_limit * 60;

    const timerEl = document.getElementById('demo-timer');

    timerInterval = setInterval(() => {

      timeLeft--;

      const m = Math.floor(timeLeft / 60);

      const s = timeLeft % 60;

      if (timerEl) timerEl.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;

      if (timeLeft <= 0) {

        clearInterval(timerInterval);

        alert('Đã hết thời gian làm bài!');

        executeSubmitExam();

      }

    }, 1000);

  }

  function confirmSubmitModal() {

    if (isSubmitted) return;

    const total = examData.questions.length;

    const answered = getAnsweredCount();

    document.getElementById('modal-submit-stats').textContent = `Bạn đã hoàn thành ${answered}/${total} câu hỏi.`;

    const warningText = document.getElementById('modal-warning-text');

    if (answered < total) {

      warningText.classList.remove('d-none');

    } else {

      warningText.classList.add('d-none');

    }

    submitModalInstance.show();

  }

  // XỬ LÝ NỘP BÀI & TỔNG HỢP KẾT QUẢ ĐƯA LÊN TRÊN

  function executeSubmitExam() {

    if (isSubmitted) return;

    isSubmitted = true;

    if (submitModalInstance) submitModalInstance.hide();

    clearInterval(timerInterval);

    // 1. Biến mất hoàn toàn nút Nộp bài & Nút Bỏ chọn & Thanh thời gian

    document.getElementById('submit-section').style.display = 'none';

    document.getElementById('exam-header-bar').style.display = 'none';

    document.querySelectorAll('.unselect-btn').forEach(b => b.classList.add('d-none'));

    // 2. Mở toàn bộ 20 câu để người dùng kéo xuống xem chi tiết

    itemsPerPage = examData.questions.length;

    renderPage();

    let correctCount = 0;

    const total = examData.questions.length;

    examData.questions.forEach(q => {

      const selectedInput = document.querySelector(`input[name="q_${q.id}"]:checked`);

      const selected = selectedInput ? selectedInput.value : null;

      const qBox = document.getElementById(`demo-q-box-${q.id}`);

      const expBox = qBox.querySelector('.explanation-box');

      const paletteBtn = document.getElementById(`palette-btn-${q.id}`);

      expBox.classList.remove('d-none');

      if (selected === q.correct) {

        correctCount++;

        qBox.classList.add('border', 'border-success', 'bg-success-subtle');

        expBox.className = 'explanation-box mt-3 p-3 rounded-3 bg-success bg-opacity-10 text-success';

        expBox.innerHTML = `<strong><i class="bi bi-check-circle-fill me-1"></i> Chính xác!</strong> ${q.explanation}`;

        paletteBtn.classList.add('res-correct');

      } else {

        qBox.classList.add('border', 'border-danger', 'bg-danger-subtle');

        expBox.className = 'explanation-box mt-3 p-3 rounded-3 bg-danger bg-opacity-10 text-danger';

        expBox.innerHTML = `<strong><i class="bi bi-x-circle-fill me-1"></i> Chưa đúng!</strong> Đáp án đúng là <strong>${q.correct}</strong>. <br>${q.explanation}`;

        paletteBtn.classList.add('res-wrong');

      }

      qBox.querySelectorAll('input').forEach(i => i.disabled = true);

    });

    const score = ((correctCount / total) * 10).toFixed(1);

    document.getElementById('res-score').textContent = `${score}/10`;

    document.getElementById('res-correct').textContent = `${correctCount}/${total}`;

    // 3. Đánh giá suy ra Mức độ hiểu biết ngành IT

    const badgeContainer = document.getElementById('level-badge-container');

    const evalEl = document.getElementById('evaluation-text');

    if (score >= 9.0) {

      badgeContainer.innerHTML = '<span class="level-badge bg-primary text-white"><i class="bi bi-award-fill me-1"></i> Chuyên Gia / Senior IT Level</span>';

      evalEl.innerHTML = '<strong>Sự hiểu biết xuất sắc!</strong> Bạn sở hữu tư duy toàn diện từ Thuật toán, Lập trình Web/OOP, Hệ điều hành đến Cloud và AI. Bạn có nền tảng vững chắc để đảm nhận các vị trí Kỹ sư Phần mềm Chuyên nghiệp hoặc Kiến trúc sư Hệ thống.';

    } else if (score >= 7.0) {

      badgeContainer.innerHTML = '<span class="level-badge bg-success text-white"><i class="bi bi-check-circle-fill me-1"></i> Khá - Tốt / Junior - Mid Level</span>';

      evalEl.innerHTML = '<strong>Mức độ hiểu biết Tốt!</strong> Bạn nắm vững hầu hết các khái niệm cốt lõi của ngành CNTT như Mạng máy tính, CSDL, Quy trình Agile và Git. Chỉ cần củng cố thêm một số chủ đề chuyên sâu về Thuật toán tối ưu và Security.';

    } else if (score >= 5.0) {

      badgeContainer.innerHTML = '<span class="level-badge bg-warning text-dark"><i class="bi bi-lightning-fill me-1"></i> Trung Bình / IT Fresher / Newbie</span>';

      evalEl.innerHTML = '<strong>Hiểu biết ở mức Nền tảng!</strong> Bạn có khái niệm cơ bản về CNTT và Lập trình nhưng còn hạn chế ở các phần Mạng máy tính, Điện toán đám mây và Kiến trúc phần mềm. Bạn nên tập trung thực hành viết code thực tế nhiều hơn.';

    } else {

      badgeContainer.innerHTML = '<span class="level-badge bg-secondary text-white"><i class="bi bi-book-fill me-1"></i> Mới Bắt Đầu / Non-IT User</span>';

      evalEl.innerHTML = '<strong>Mức độ Người mới tìm hiểu!</strong> Bạn mới chỉ chạm ngõ vào ngành Công nghệ Thông tin. Đừng lo lắng, hãy bắt đầu lộ trình học từ các thuật ngữ cơ bản, Tư duy Lập trình C++/Python trước khi tiến xa hơn.';

    }

    // 4. Đưa bảng kết quả lên trên cùng và cuộn mượt

    document.getElementById('demo-result-top-box').classList.remove('d-none');

    window.scrollTo({

      top: 0,

      behavior: 'smooth'

    });

  }

</script>