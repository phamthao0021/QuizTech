<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\User;
use App\Helpers\Auth;
use App\Helpers\Validation;
use App\Helpers\Response;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/login', [
            'page_title' => 'Đăng nhập',
            'layout' => 'guest'
        ]);
    }

    public function postLogin(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmailOrStudentCode($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Email hoặc mật khẩu không đúng.'];
            $this->redirect('/login');
            return;
        }

        if (!$user['is_active']) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tài khoản đã bị khóa.'];
            $this->redirect('/login');
            return;
        }

        Auth::login($user);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Đăng nhập thành công!'];
        $this->redirect('/dashboard');
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/register', [
            'page_title' => 'Đăng ký',
            'layout' => 'guest'
        ]);
    }

    public function postRegister(): void
    {
        $studentCode = $_POST['student_code'] ?? '';
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate
        $validator = new Validation();
        $validator->required('student_code', $studentCode)->minLength('student_code', $studentCode, 5);
        $validator->required('fullname', $fullname)->minLength('fullname', $fullname, 2);
        $validator->required('email', $email)->email('email', $email);
        $validator->required('password', $password)->minLength('password', $password, 8);

        if ($password !== $confirmPassword) {
            $validator->addError('confirm_password', 'Mật khẩu xác nhận không khớp.');
        }

        // Check if email or student code exists
        if ($this->userModel->findByEmail($email)) {
            $validator->addError('email', 'Email đã được đăng ký.');
        }
        if ($this->userModel->findByStudentCode($studentCode)) {
            $validator->addError('student_code', 'MSSV đã được đăng ký.');
        }

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            $this->redirect('/register');
            return;
        }

        $hash = $this->userModel->hashPassword($password);
        $userId = $this->userModel->create([
            'student_code' => $studentCode,
            'fullname' => $fullname,
            'email' => $email,
            'password_hash' => $hash,
            'role' => 'student'
        ]);

        $user = $this->userModel->findById($userId);
        Auth::login($user);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Đăng ký thành công!'];
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Đã đăng xuất.'];
        $this->redirect('/');
    }
}