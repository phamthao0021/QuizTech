<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;
use App\Helpers\Response;

class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(): void
    {
        $userRole = Auth::role();
        if (!in_array($userRole, $this->allowedRoles)) {
            Response::setFlash('danger', 'Bạn không có quyền truy cập.');
            Response::redirect('/dashboard');
            exit;
        }
    }
}