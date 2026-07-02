<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;
use App\Helpers\Response;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            Response::setFlash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            Response::redirect('/login');
            exit;
        }
    }
}