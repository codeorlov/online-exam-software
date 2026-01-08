<?php
/**
 * Middleware для перевірки, що користувач НЕ авторизований
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class GuestMiddleware
{
    public function handle(): bool
    {
        if (Session::has('user_id')) {
            header('Location: /dashboard');
            exit;
        }
        return true;
    }
}
