<?php
// includes/header_teacher.php
// Header & Sidebar cho Giảng viên QuizTech

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'QuizTech - Giảng viên';

$scriptPath = str_replace(
    '\\',
    '/',
    filter_input(INPUT_SERVER, 'SCRIPT_NAME') ?: ''
);

$current_page = basename($scriptPath);

$teacherPos = strpos($scriptPath, '/teacher/');
$in_teacher = $teacherPos !== false;

$afterTeacher = $in_teacher
    ? trim(substr($scriptPath, $teacherPos + 9), '/')
    : '';

$teacherDepth = ($in_teacher && $afterTeacher !== '')
    ? substr_count($afterTeacher, '/')
    : 0;

$prefix = $in_teacher
    ? str_repeat('../', $teacherDepth + 1)
    : '';

$base_url = $in_teacher
    ? str_repeat('../', $teacherDepth)
    : 'teacher/';

$user = currentUser();
$is_logged_in = isLoggedIn();

$user_name = $user['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'Giảng viên';

$user_email = $user['email']
    ?? $_SESSION['email']
    ?? '';

$qtTeacherStyles = [
    'Quản lý đề thi' => 'qt-teacher-exams.css',
    'Quản lý câu hỏi' => 'qt-teacher-questions.css',
    'Import câu hỏi' => 'qt-teacher-import.css'
];

$qtTeacherStyle = $qtTeacherStyles[$page_title] ?? null;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($page_title) ?> - QuizTech Teacher
    </title>

    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    >

    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
    >

    <!-- Google Fonts -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- CSS chung -->

    <link
        rel="stylesheet"
        href="<?= e($prefix) ?>assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= e($prefix) ?>assets/css/guest.css"
    >

    <link
        rel="stylesheet"
        href="<?= e($prefix) ?>assets/css/qt-crud-v39.css"
    >

    <script
        defer
        src="<?= e($prefix) ?>assets/js/qt-crud-v39.js"
    ></script>

    <!-- CSS Teacher -->

    <link
        rel="stylesheet"
        href="<?= e($prefix) ?>assets/css/qt-teacher-unified.css?v=20261025"
    >

    <link
        rel="stylesheet"
        href="<?= e($prefix) ?>assets/css/teacher-dashboard-pro.css?v=20261025"
    >

    <?php if ($qtTeacherStyle): ?>

        <link
            rel="stylesheet"
            href="<?= e($prefix) ?>assets/css/<?= e($qtTeacherStyle) ?>?v=20261025"
        >

    <?php endif; ?>

    <link
        rel="stylesheet"
        href="<?= e($prefix) ?>assets/css/qt-teacher-pages.css?v=20261025"
    >

    <link
        rel="stylesheet"
        href="<?= e($prefix) ?>assets/css/qt-teacher-filters.css?v=20261025b"
    >

    <style>
        :root {
            --teacher-main: #6366f1;
            --teacher-dark: #4f46e5;
            --teacher-deep: #3730a3;
            --teacher-night: #1e1b4b;
            --teacher-glow: rgba(99, 102, 241, .35);
            --teacher-light: #e0e7ff;

            --purple-main: var(--teacher-main);
            --purple-dark: var(--teacher-dark);
            --purple-deep: var(--teacher-deep);
            --purple-night: var(--teacher-night);
            --purple-glow: var(--teacher-glow);
            --purple-light: var(--teacher-light);

            --bg-canvas: #f8fafc;
            --text-dark: #1e1b4b;

            --sidebar-width: 270px;
            --sidebar-collapsed-width: 82px;

            --sidebar-transition:
                all .28s cubic-bezier(.4, 0, .2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family:
                'Plus Jakarta Sans',
                'Inter',
                system-ui,
                sans-serif;

            background: var(--bg-canvas);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .teacher-wrapper {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* SIDEBAR */

        aside.teacher-sidebar,
        #mobileTeacherSidebar {
            background: linear-gradient(
                180deg,
                #2e1065 0%,
                #4c1d95 60%,
                #3b0764 100%
            );

            color: #fff;
        }

        aside.teacher-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            flex: 0 0 var(--sidebar-width);

            transition: var(--sidebar-transition);

            z-index: 1020;

            box-shadow:
                4px 0 25px rgba(0, 0, 0, .08);

            display: flex;
            flex-direction: column;

            overflow: hidden;

            position: sticky;
            top: 0;

            min-height: 100vh;
            align-self: stretch;
        }

        .teacher-wrapper.sidebar-collapsed
        aside.teacher-sidebar {
            width: var(--sidebar-collapsed-width);
            min-width: var(--sidebar-collapsed-width);
            flex-basis: var(--sidebar-collapsed-width);
        }

        /* BRAND */

        .sidebar-brand-wrapper {
            height: 78px;
            min-height: 78px;

            padding: 0 1rem 0 1.35rem;

            background: rgba(0, 0, 0, .15);

            backdrop-filter: blur(10px);

            border-bottom:
                1px solid rgba(255, 255, 255, .08);

            display: flex;
            align-items: center;
            gap: .7rem;

            transition: var(--sidebar-transition);

            overflow: hidden;
            white-space: nowrap;
        }

        .brand-logo-img {
            height: 38px;
            width: auto;
            flex-shrink: 0;
        }

        .brand-text {
            min-width: 0;
            transition: all .25s ease;
        }

        .teacher-wrapper.sidebar-collapsed
        .sidebar-brand-wrapper {
            height: 110px;
            min-height: 110px;

            flex-direction: column;
            justify-content: center;
            gap: .4rem;

            padding: .55rem;
        }

        .teacher-wrapper.sidebar-collapsed
        .brand-text {
            display: none !important;
        }

        /* MENU */

        .sidebar-menu-wrapper {
            padding: 1rem 0;
            flex-grow: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .08em;

            color: #c084fc;

            font-weight: 700;

            padding: .85rem 1.5rem .35rem;

            opacity: .85;
            white-space: nowrap;
        }

        .teacher-wrapper.sidebar-collapsed
        .sidebar-label {
            font-size: 0;
            height: 14px;
            padding: 0;
            margin: 0;
            opacity: 0;
        }

        .teacher-sidebar .nav-link,
        #mobileTeacherSidebar .nav-link {
            color: #e9d5ff !important;

            padding: .75rem 1.15rem;

            border-radius: 14px;
            margin: .25rem 1rem;

            font-weight: 500;
            font-size: .925rem;

            display: flex;
            align-items: center;
            gap: .85rem;

            transition: all .25s ease;

            white-space: nowrap;
            overflow: hidden;
        }

        .teacher-sidebar .nav-link i,
        #mobileTeacherSidebar .nav-link i {
            font-size: 1.15rem;
            min-width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .teacher-sidebar .nav-link:hover,
        #mobileTeacherSidebar .nav-link:hover {
            color: #fff !important;

            background:
                rgba(255, 255, 255, .12) !important;

            transform: translateX(4px);
        }

        .teacher-sidebar .nav-link.active,
        #mobileTeacherSidebar .nav-link.active {
            color: #fff !important;

            background: linear-gradient(
                135deg,
                #a855f7,
                #7c3aed
            ) !important;

            box-shadow:
                0 8px 20px rgba(168, 85, 247, .35);

            font-weight: 600;
        }

        .teacher-wrapper.sidebar-collapsed
        .teacher-sidebar .nav-link {
            justify-content: center;

            padding-left: .5rem;
            padding-right: .5rem;

            margin-left: .65rem;
            margin-right: .65rem;

            gap: 0;
        }

        .teacher-wrapper.sidebar-collapsed
        .teacher-sidebar .nav-text {
            display: none;
        }

        /* MAIN CONTENT */

        .teacher-content {
            flex-grow: 1;
            min-width: 0;

            display: flex;
            flex-direction: column;

            background: var(--bg-canvas);
        }

        .teacher-navbar {
            height: 78px;
            min-height: 78px;

            background:
                rgba(255, 255, 255, .85) !important;

            backdrop-filter: blur(12px);

            border-bottom:
                1px solid rgba(226, 232, 240, .8);

            padding: 0 1.5rem;

            z-index: 1010;
        }

        /* SIDEBAR TOGGLE */

        .sidebar-toggle-btn {
            width: 30px;
            height: 30px;

            flex: 0 0 30px;
            margin-left: auto;

            padding: 0;
            border: 0;
            border-radius: 8px;

            background: transparent;
            color: rgba(255, 255, 255, .92);

            display: inline-flex;
            align-items: center;
            justify-content: center;

            transition: all .2s ease;
        }

        .sidebar-toggle-btn:hover {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .sidebar-toggle-btn i {
            font-size: 1.1rem;
            transition: transform .25s ease;
        }

        .teacher-wrapper.sidebar-collapsed
        .sidebar-toggle-btn i {
            transform: rotate(180deg);
        }

        /* PROFILE */

        .user-profile-badge {
            padding: .35rem .85rem .35rem .4rem;

            background: #fff;

            border: 1px solid #e2e8f0;
            border-radius: 50px;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, .04);

            transition: all .25s ease;
        }

        .user-profile-badge:hover {
            border-color: #c4b5fd;

            box-shadow:
                0 6px 18px rgba(109, 40, 217, .1);

            transform: translateY(-1px);
        }

        .avatar-img-header,
        .avatar-placeholder-header {
            width: 38px;
            height: 38px;
            border-radius: 50%;
        }

        .avatar-img-header {
            object-fit: cover;
            border: 2px solid var(--purple-main);
        }

        .avatar-placeholder-header {
            background: linear-gradient(
                135deg,
                #a855f7,
                #4f46e5
            );

            color: #fff;
            font-weight: 700;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-menu-teacher {
            border-radius: 20px;

            border:
                1px solid rgba(226, 232, 240, .9);

            box-shadow:
                0 20px 35px rgba(15, 23, 42, .08);

            padding: .6rem;
            min-width: 240px;
        }

        .dropdown-menu-teacher .dropdown-item {
            border-radius: 10px;
            padding: .6rem;
        }

        .dropdown-menu-teacher .dropdown-item:hover {
            background: #f5f3ff;
        }

        /* FLASH */

        .alert-modern {
            border-radius: 16px;
            border: none;

            box-shadow:
                0 8px 20px rgba(0, 0, 0, .04);

            padding: 1rem 1.25rem;
        }

        /* RESPONSIVE */

        @media (max-width: 991.98px) {
            aside.teacher-sidebar {
                display: none !important;
            }

            .teacher-navbar {
                padding: .75rem 1rem;
            }

            .sidebar-toggle-btn {
                display: none !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .teacher-sidebar,
            .teacher-sidebar *,
            .sidebar-toggle-btn {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<div class="teacher-wrapper" id="teacherWrapper">

    <!-- =====================================
         SIDEBAR DESKTOP
    ====================================== -->

    <aside
        class="teacher-sidebar d-none d-lg-flex"
        id="teacherSidebar"
    >

        <!-- BRAND -->

        <div class="sidebar-brand-wrapper">

            <img
                src="<?= e($prefix) ?>assets/images/Cardmoi_PLT_Trang.png"
                alt="QuizTech"
                class="brand-logo-img"
            >

            <div class="brand-text d-flex flex-column">

                <span class="fw-bold fs-5 text-white lh-1">
                    QuizTech
                </span>

                <span
                    class="text-white-50 small mt-1"
                    style="font-size:.7rem;letter-spacing:.05em"
                >
                    GIẢNG VIÊN
                </span>

            </div>

            <button
                class="sidebar-toggle-btn"
                type="button"
                id="sidebarToggle"
                aria-controls="teacherSidebar"
                aria-expanded="true"
                aria-label="Thu gọn sidebar"
                title="Thu gọn sidebar"
            >
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

        </div>

        <!-- MENU DESKTOP -->

        <div class="sidebar-menu-wrapper">

            <div class="sidebar-label">
                Tổng Quan
            </div>

            <ul class="nav nav-pills flex-column mb-2">

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>dashboard.php"
                        title="Tổng quan"
                    >
                        <i class="bi bi-speedometer2"></i>
                        <span class="nav-text">Tổng quan</span>
                    </a>

                </li>

            </ul>

            <div class="sidebar-label">
                Giảng Dạy & Thi
            </div>

            <ul class="nav nav-pills flex-column mb-2">

                <!-- ĐỀ THI -->

                <li class="nav-item">

                    <a
                        class="nav-link <?= in_array(
                            $current_page,
                            [
                                'exams.php',
                                'exam_detail.php',
                                'create_exam.php'
                            ],
                            true
                        ) ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>exams.php"
                        title="Quản lý đề thi"
                    >
                        <i class="bi bi-file-earmark-text"></i>
                        <span class="nav-text">
                            Quản lý đề thi
                        </span>
                    </a>

                </li>

                <!-- CÂU HỎI -->

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'questions.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>questions.php"
                        title="Ngân hàng câu hỏi"
                    >
                        <i class="bi bi-question-circle"></i>
                        <span class="nav-text">
                            Ngân hàng câu hỏi
                        </span>
                    </a>

                </li>

                <!-- IMPORT -->

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'import.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>import.php"
                        title="Import câu hỏi"
                    >
                        <i class="bi bi-upload"></i>
                        <span class="nav-text">
                            Import câu hỏi
                        </span>
                    </a>

                </li>

                <!-- PHÒNG THI -->

                <li class="nav-item">

                    <a
                        class="nav-link <?= in_array(
                            $current_page,
                            ['rooms.php', 'monitor_room.php'],
                            true
                        ) ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>rooms.php"
                        title="Quản lý phòng thi"
                    >
                        <i class="bi bi-door-open"></i>
                        <span class="nav-text">
                            Phòng thi
                        </span>
                    </a>

                </li>

                <!-- MÔN HỌC -->

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'subjects.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>subjects.php"
                        title="Quản lý môn học"
                    >
                        <i class="bi bi-book"></i>
                        <span class="nav-text">
                            Quản lý môn học
                        </span>
                    </a>

                </li>

            </ul>

            <div class="sidebar-label">
                Thống Kê & Báo Cáo
            </div>

            <ul class="nav nav-pills flex-column mb-2">

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'results.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>results.php"
                        title="Kết quả & Thống kê"
                    >
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="nav-text">
                            Kết quả & Thống kê
                        </span>
                    </a>

                </li>

            </ul>

            <div class="sidebar-label">
                Luyện Tập & Tiện Ích
            </div>

            <ul class="nav nav-pills flex-column">

                <!-- PRACTICE -->

                <li class="nav-item">

                    <a
                        class="nav-link <?= strpos(
                            $scriptPath,
                            '/teacher/practice/'
                        ) !== false ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>practice/index.php"
                        title="Quản lý Practice"
                    >
                        <i class="bi bi-controller"></i>
                        <span class="nav-text">
                            Quản lý Practice
                        </span>
                    </a>

                </li>

                <!-- THÔNG BÁO -->

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>notifications.php"
                        title="Thông báo"
                    >
                        <i class="bi bi-bell"></i>
                        <span class="nav-text">
                            Thông báo
                        </span>
                    </a>

                </li>

            </ul>

        </div>

    </aside>

    <!-- =====================================
         SIDEBAR MOBILE
    ====================================== -->

    <div
        class="offcanvas offcanvas-start text-white border-0"
        tabindex="-1"
        id="mobileTeacherSidebar"
        style="width:var(--sidebar-width)"
    >

        <div
            class="offcanvas-header border-bottom border-white border-opacity-10 p-3"
            style="background:rgba(0,0,0,.15)"
        >

            <div class="d-flex align-items-center gap-2">

                <img
                    src="<?= e($prefix) ?>assets/images/Cardmoi_PLT_Trang.png"
                    alt="QuizTech"
                    style="height:35px;width:auto"
                >

                <span class="fw-bold fs-4 text-light">
                    QuizTech
                </span>

            </div>

            <button
                type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                aria-label="Đóng menu"
            ></button>

        </div>

        <div class="offcanvas-body p-0 py-3">

            <div class="sidebar-label">
                Tổng Quan
            </div>

            <ul class="nav nav-pills flex-column mb-2">

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>dashboard.php"
                    >
                        <i class="bi bi-speedometer2"></i>
                        <span class="nav-text">
                            Tổng quan
                        </span>
                    </a>

                </li>

            </ul>

            <div class="sidebar-label">
                Giảng Dạy & Thi
            </div>

            <ul class="nav nav-pills flex-column mb-2">

                <li class="nav-item">

                    <a
                        class="nav-link <?= in_array(
                            $current_page,
                            [
                                'exams.php',
                                'exam_detail.php',
                                'create_exam.php'
                            ],
                            true
                        ) ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>exams.php"
                    >
                        <i class="bi bi-file-earmark-text"></i>
                        <span class="nav-text">
                            Quản lý đề thi
                        </span>
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'questions.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>questions.php"
                    >
                        <i class="bi bi-question-circle"></i>
                        <span class="nav-text">
                            Ngân hàng câu hỏi
                        </span>
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'import.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>import.php"
                    >
                        <i class="bi bi-upload"></i>
                        <span class="nav-text">
                            Import câu hỏi
                        </span>
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link <?= in_array(
                            $current_page,
                            ['rooms.php', 'monitor_room.php'],
                            true
                        ) ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>rooms.php"
                    >
                        <i class="bi bi-door-open"></i>
                        <span class="nav-text">
                            Quản lý phòng thi
                        </span>
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'subjects.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>subjects.php"
                    >
                        <i class="bi bi-book"></i>
                        <span class="nav-text">
                            Quản lý môn học
                        </span>
                    </a>

                </li>

            </ul>

            <div class="sidebar-label">
                Thống Kê & Báo Cáo
            </div>

            <ul class="nav nav-pills flex-column mb-2">

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'results.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>results.php"
                    >
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="nav-text">
                            Kết quả & Thống kê
                        </span>
                    </a>

                </li>

            </ul>

            <div class="sidebar-label">
                Luyện Tập & Tiện Ích
            </div>

            <ul class="nav nav-pills flex-column">

                <li class="nav-item">

                    <a
                        class="nav-link <?= strpos(
                            $scriptPath,
                            '/teacher/practice/'
                        ) !== false ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>practice/index.php"
                    >
                        <i class="bi bi-controller"></i>
                        <span class="nav-text">
                            Quản lý Practice
                        </span>
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>"
                        href="<?= e($base_url) ?>notifications.php"
                    >
                        <i class="bi bi-bell"></i>
                        <span class="nav-text">
                            Thông báo
                        </span>
                    </a>

                </li>

            </ul>

        </div>

    </div>

    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <div class="teacher-content">

        <!-- TOP NAVBAR -->

        <header
            class="navbar navbar-expand teacher-navbar sticky-top"
        >

            <div class="d-flex align-items-center gap-2">

                <!-- Nút mở menu trên mobile -->

                <button
                    class="btn btn-light rounded-circle p-2 d-lg-none border"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileTeacherSidebar"
                    aria-label="Mở menu"
                >
                    <i class="bi bi-list fs-5"></i>
                </button>

                <div
                    class="d-flex align-items-center gap-2 d-lg-none ms-1"
                >

                    <img
                        src="<?= e($prefix) ?>assets/images/Cardmoi_PLT_Trang.png"
                        alt="QuizTech"
                        style="height:28px;width:auto"
                    >

                    <span class="fw-bold fs-6 text-dark">
                        QuizTech
                    </span>

                </div>

            </div>

            <!-- RIGHT HEADER -->

            <div class="ms-auto d-flex align-items-center gap-3">

                <!-- Thông báo -->

                <?php
                include __DIR__ . '/qt_notice_widget.php';
                ?>

                <!-- Dropdown tài khoản -->

                <div class="dropdown">

                    <a
                        class="d-flex align-items-center gap-2 text-decoration-none user-profile-badge"
                        href="#"
                        id="teacherProfileDrop"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <?php
                        $raw_avatar = $user['avatar']
                            ?? $_SESSION['avatar']
                            ?? $_SESSION['user']['avatar']
                            ?? '';

                        $clean_avatar_path = ltrim(
                            str_replace(
                                '\\',
                                '/',
                                (string)$raw_avatar
                            ),
                            '/'
                        );

                        if (
                            $clean_avatar_path !== ''
                            && strpos(
                                $clean_avatar_path,
                                '/'
                            ) === false
                        ) {
                            $clean_avatar_path =
                                'uploads/avatars/'
                                . $clean_avatar_path;
                        }

                        if (
                            strpos(
                                $clean_avatar_path,
                                '..'
                            ) !== false
                            || !preg_match(
                                '~^uploads/avatars/[a-zA-Z0-9._/-]+$~',
                                $clean_avatar_path
                            )
                        ) {
                            $clean_avatar_path = '';
                        }

                        $server_file_path =
                            __DIR__
                            . '/../'
                            . $clean_avatar_path;

                        $has_avatar =
                            $clean_avatar_path !== ''
                            && file_exists(
                                $server_file_path
                            );
                        ?>

                        <?php if ($has_avatar): ?>

                            <img
                                src="<?= e(
                                    $prefix . $clean_avatar_path
                                ) ?>?v=<?= time() ?>"
                                alt="Avatar"
                                class="avatar-img-header"
                            >

                        <?php else: ?>

                            <div class="avatar-placeholder-header">

                                <?= e(
                                    strtoupper(
                                        mb_substr(
                                            $user_name,
                                            0,
                                            1,
                                            'UTF-8'
                                        )
                                    )
                                ) ?>

                            </div>

                        <?php endif; ?>

                        <div
                            class="d-none d-md-block text-start pe-1"
                        >

                            <div
                                class="fw-semibold text-dark small lh-1"
                            >
                                <?= e($user_name) ?>
                            </div>

                        </div>

                        <i
                            class="bi bi-chevron-down text-muted small ms-1 d-none d-md-inline"
                        ></i>

                    </a>

                    <!-- MENU TÀI KHOẢN -->

                    <ul
                        class="dropdown-menu dropdown-menu-end dropdown-menu-teacher"
                        aria-labelledby="teacherProfileDrop"
                    >

                        <li class="px-3 py-2 border-bottom mb-1">

                            <div class="fw-bold text-dark mb-0">
                                <?= e($user_name) ?>
                            </div>

                            <div
                                class="text-muted small text-truncate"
                                style="max-width:200px"
                            >
                                <?= e($user_email) ?>
                            </div>

                        </li>

                        <!-- HỒ SƠ -->

                        <li>

                            <a
                                class="dropdown-item d-flex align-items-center gap-2"
                                href="<?= e($base_url) ?>profile.php"
                            >
                                <i
                                    class="bi bi-person-badge text-primary fs-6"
                                ></i>

                                Hồ sơ cá nhân
                            </a>

                        </li>

                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>

                        <!-- ĐĂNG XUẤT -->

                        <li>

                            <a
                                class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                href="<?= e($prefix) ?>logout.php"
                            >
                                <i
                                    class="bi bi-box-arrow-right fs-6"
                                ></i>

                                Đăng xuất
                            </a>

                        </li>

                    </ul>

                </div>

            </div>

        </header>

        <!-- =====================================
             FLASH MESSAGES
        ====================================== -->

        <?php if (function_exists('getFlash')): ?>

            <?php $flash = getFlash(); ?>

            <?php if (
                $flash
                && isset($flash['type'])
                && isset($flash['message'])
            ): ?>

                <div class="px-3 px-lg-4 mt-3">

                    <div
                        class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show alert-modern mb-0"
                        role="alert"
                    >

                        <i
                            class="bi bi-info-circle-fill me-2"
                        ></i>

                        <?= e($flash['message']) ?>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Đóng thông báo"
                        ></button>

                    </div>

                </div>

            <?php endif; ?>

        <?php endif; ?>

        <!-- =====================================
             MAIN
        ====================================== -->

        <main class="p-3 p-lg-4">

            <!-- SIDEBAR JAVASCRIPT -->

            <script>
                (function () {
                    const wrapper =
                        document.getElementById(
                            'teacherWrapper'
                        );

                    const toggle =
                        document.getElementById(
                            'sidebarToggle'
                        );

                    const sidebar =
                        document.getElementById(
                            'teacherSidebar'
                        );

                    const storageKey =
                        'quiztech_teacher_sidebar_collapsed';

                    if (
                        !wrapper
                        || !toggle
                        || !sidebar
                    ) {
                        return;
                    }

                    function applySidebarState(collapsed) {
                        wrapper.classList.toggle(
                            'sidebar-collapsed',
                            collapsed
                        );

                        toggle.setAttribute(
                            'aria-expanded',
                            String(!collapsed)
                        );

                        sidebar
                            .querySelectorAll('.nav-link')
                            .forEach(function (link) {
                                if (collapsed) {
                                    const navText =
                                        link.querySelector(
                                            '.nav-text'
                                        );

                                    link.title = navText
                                        ? navText.textContent.trim()
                                        : '';
                                } else {
                                    link.removeAttribute(
                                        'title'
                                    );
                                }
                            });

                        toggle.setAttribute(
                            'aria-label',
                            collapsed
                                ? 'Mở rộng sidebar'
                                : 'Thu gọn sidebar'
                        );

                        toggle.setAttribute(
                            'title',
                            collapsed
                                ? 'Mở rộng sidebar'
                                : 'Thu gọn sidebar'
                        );
                    }

                    let collapsed = false;

                    try {
                        collapsed =
                            localStorage.getItem(
                                storageKey
                            ) === '1';
                    } catch (error) {
                        collapsed = false;
                    }

                    applySidebarState(collapsed);

                    toggle.addEventListener(
                        'click',
                        function () {
                            collapsed =
                                !wrapper.classList.contains(
                                    'sidebar-collapsed'
                                );

                            applySidebarState(
                                collapsed
                            );

                            try {
                                localStorage.setItem(
                                    storageKey,
                                    collapsed ? '1' : '0'
                                );
                            } catch (error) {
                                // Không làm gián đoạn UI.
                            }
                        }
                    );
                })();
            </script>