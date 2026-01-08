<?php
/**
 * Клас для рендерингу представлень (Views)
 */

declare(strict_types=1);

namespace App\Core;

class View
{
    private array $data = [];

    /**
     * Встановити дані для представлення
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Отримати дані представлення
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Рендерити представлення
     */
    public function render(string $view, string $layout = 'main'): void
    {
        extract($this->data);

        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file not found: {$view}");
        }

        $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout file not found: {$layout}");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        include $layoutFile;
    }

    /**
     * Рендерити представлення без layout
     */
    public function renderPartial(string $view): void
    {
        extract($this->data);
        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file not found: {$view}");
        }

        include $viewFile;
    }

    /**
     * Екранувати вивід (XSS захист)
     */
    public function e(string $string): string
    {
        return \App\Core\Security::escape($string);
    }

    /**
     * Отримати і видалити flash повідомлення
     */
    public function getFlash(): ?array
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
     * Форматувати розмір файлу
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}
