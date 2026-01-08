<?php
/**
 * Клас для забезпечення безпеки додатку
 * CSRF, XSS захист, валідація, brute force захист
 */

declare(strict_types=1);

namespace App\Core;

class Security
{
    /**
     * Генерувати CSRF токен
     */
    public static function generateCsrfToken(): string
    {
        if (!Session::has(CSRF_TOKEN_NAME)) {
            Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
        }
        return Session::get(CSRF_TOKEN_NAME);
    }

    /**
     * Перевірити CSRF токен
     */
    public static function validateCsrfToken(string $token): bool
    {
        $sessionToken = Session::get(CSRF_TOKEN_NAME);
        return $sessionToken !== null && hash_equals($sessionToken, $token);
    }

    /**
     * Отримати CSRF токен з запиту
     */
    public static function getCsrfTokenFromRequest(): ?string
    {
        return $_POST[CSRF_TOKEN_NAME] ?? $_GET[CSRF_TOKEN_NAME] ?? null;
    }

    /**
     * Екранувати вивід для захисту від XSS
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Очистити вхідні дані
     */
    public static function sanitize(string $input): string
    {
        $input = trim($input);
        $input = stripslashes($input);
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Валідація email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Валідація пароля (мінімальна довжина, складність)
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Пароль повинен містити мінімум " . PASSWORD_MIN_LENGTH . " символів";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Пароль повинен містити хоча б одну велику літеру";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Пароль повинен містити хоча б одну малу літеру";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Пароль повинен містити хоча б одну цифру";
        }

        return $errors;
    }

    /**
     * Хешування пароля
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Перевірка пароля
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Перевірити кількість спроб входу (захист від brute force)
     */
    public static function checkLoginAttempts(string $identifier): bool
    {
        $key = 'login_attempts_' . md5($identifier);
        $attempts = Session::get($key, []);

        $attempts = array_filter($attempts, function($timestamp) {
            return time() - $timestamp < LOGIN_LOCKOUT_TIME;
        });

        if (count($attempts) >= LOGIN_MAX_ATTEMPTS) {
            Logger::warning("Brute force attempt detected for: " . $identifier);
            return false;
        }

        return true;
    }

    /**
     * Записати невдалу спробу входу
     */
    public static function recordLoginAttempt(string $identifier): void
    {
        $key = 'login_attempts_' . md5($identifier);
        $attempts = Session::get($key, []);
        $attempts[] = time();
        Session::set($key, $attempts);
    }

    /**
     * Очистити спроби входу після успішного входу
     */
    public static function clearLoginAttempts(string $identifier): void
    {
        $key = 'login_attempts_' . md5($identifier);
        Session::remove($key);
    }

    /**
     * Генерувати випадковий рядок
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Валідація ID (захист від IDOR)
     */
    public static function validateId(mixed $id): bool
    {
        return is_numeric($id) && (int)$id > 0;
    }
}
