<?php
/**
 * Middleware для перевірки ролі користувача
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(): bool
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }

        $userRole = Session::get('user_role');
        
        if (!in_array($userRole, $this->allowedRoles)) {
            header('Location: /dashboard');
            exit;
        }

        return true;
    }
}
