<?php
/**
 * Базовий клас контролера
 * Всі контролери наслідуються від цього класу
 */

declare(strict_types=1);

namespace App\Core;

use PDO;

class Controller
{
    protected View $view;
    protected PDO $db;

    public function __construct()
    {
        $this->view = new View();
        $this->db = Database::getInstance();
    }

    /**
     * Перенаправити на інший URL
     */
    protected function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }

    /**
     * Повернути JSON відповідь
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Встановити flash повідомлення
     */
    protected function setFlash(string $type, string $message): void
    {
        Session::set('flash_type', $type);
        Session::set('flash_message', $message);
    }

    /**
     * Отримати і видалити flash повідомлення
     */
    protected function getFlash(): ?array
    {
        if (Session::has('flash_message')) {
            $flash = [
                'type' => Session::get('flash_type', 'info'),
                'message' => Session::get('flash_message')
            ];
            Session::remove('flash_type');
            Session::remove('flash_message');
            return $flash;
        }
        return null;
    }

    /**
     * Перевірити, чи є користувач авторизованим
     */
    protected function requireAuth(): void
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
    }

    /**
     * Перевірити роль користувача
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();
        $userRole = Session::get('user_role');
        
        if ($userRole !== $role) {
            $this->setFlash('error', 'Доступ заборонено');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Перевірити одну з ролей
     */
    protected function requireAnyRole(array $roles): void
    {
        $this->requireAuth();
        $userRole = Session::get('user_role');
        
        if (!in_array($userRole, $roles)) {
            $this->setFlash('error', 'Доступ заборонено');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Отримати кількість елементів на сторінці з налаштувань
     */
    protected function getItemsPerPage(): int
    {
        $settings = new \App\Models\Settings();
        $itemsPerPage = $settings->get('items_per_page', 10);
        return max(1, min(25, (int)$itemsPerPage));
    }
}
