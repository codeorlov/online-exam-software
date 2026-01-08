<?php
/**
 * Middleware для перевірки аутентифікації
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }
        return true;
    }
}
