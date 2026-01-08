<?php
/**
 * Клас для безпечної роботи з сесіями
 * Захист від session fixation, httponly, secure, samesite
 */

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static bool $started = false;

    /**
     * Ініціалізація сесії з безпечними налаштуваннями
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', self::isHttps() ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_lifetime', (string)SESSION_LIFETIME);

        session_name(SESSION_NAME);
        session_start();

        if (!isset($_SESSION['created'])) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > SESSION_LIFETIME) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }

        self::$started = true;
    }

    /**
     * Перевірити, чи запущена сесія
     */
    public static function isStarted(): bool
    {
        return self::$started;
    }

    /**
     * Отримати значення з сесії
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Встановити значення в сесію
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Видалити значення з сесії
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Перевірити наявність ключа в сесії
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Очистити всі дані сесії
     */
    public static function clear(): void
    {
        self::ensureStarted();
        $_SESSION = [];
    }

    /**
     * Знищити сесію
     */
    public static function destroy(): void
    {
        self::ensureStarted();
        session_destroy();
        self::$started = false;
    }

    /**
     * Отримати ID сесії
     */
    public static function getId(): string
    {
        self::ensureStarted();
        return session_id();
    }

    /**
     * Регенерувати ID сесії (захист від fixation)
     */
    public static function regenerateId(bool $deleteOld = true): void
    {
        self::ensureStarted();
        session_regenerate_id($deleteOld);
        $_SESSION['created'] = time();
    }

    /**
     * Переконатися, що сесія запущена
     */
    private static function ensureStarted(): void
    {
        if (!self::$started) {
            self::start();
        }
    }

    /**
     * Перевірити, чи використовується HTTPS
     */
    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443;
    }
}
