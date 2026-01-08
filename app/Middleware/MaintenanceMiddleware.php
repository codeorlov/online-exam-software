<?php
/**
 * Middleware для перевірки режиму обслуговування
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;
use App\Models\Settings;

class MaintenanceMiddleware
{
    public function handle(): bool
    {
        $settings = new Settings();
        $maintenanceMode = $settings->get('maintenance_mode', false);

        if (!$maintenanceMode) {
            return true;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (!Session::has('user_id') && (strpos($uri, '/login') !== false || strpos($uri, '/register') !== false)) {
            return true;
        }

        $userRole = Session::get('user_role');
        if ($userRole === 'admin') {
            return true;
        }

        if (Session::has('user_id')) {
            Session::destroy();
        }

        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        
        $siteName = $settings->get('site_name', 'Система онлайн-тестування');
        
        echo <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Режим обслуговування - {$siteName}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .maintenance-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
        }
        .maintenance-icon {
            font-size: 5rem;
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <i class="bi bi-tools maintenance-icon"></i>
        <h1>Сайт знаходиться на обслуговуванні</h1>
        <p>Наразі проводяться технічні роботи. Будь ласка, спробуйте зайти пізніше.</p>
        <p class="text-muted mt-3">
            <small>Ми приносимо вибачення за тимчасові незручності.</small>
        </p>
        <div class="mt-4">
            <a href="/login" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Повернутися до форми входу
            </a>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }
}
