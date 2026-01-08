<?php
/**
 * Централізована обробка помилок
 */

declare(strict_types=1);

namespace App\Core;

class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        Logger::error("PHP Error: {$message}", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ]);

        if (APP_ENV === 'production') {
            return true;
        }

        return false;
    }

    public static function handleException(\Throwable $exception): void
    {
        Logger::error('Uncaught Exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        if (APP_ENV === 'production') {
            http_response_code(500);
            $view = new View();
            $view->render('errors/500');
        } else {
            http_response_code(500);
            echo '<h1>Помилка</h1>';
            echo '<pre>' . Security::escape($exception->getMessage()) . '</pre>';
            echo '<pre>' . Security::escape($exception->getTraceAsString()) . '</pre>';
        }
        exit;
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            Logger::error('Fatal Error: ' . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            if (APP_ENV === 'production') {
                http_response_code(500);
                $view = new View();
                $view->render('errors/500');
            }
        }
    }

    public static function setSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://static.cloudflareinsights.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self' https://cdn.jsdelivr.net https://static.cloudflareinsights.com; frame-ancestors 'self';";
        header("Content-Security-Policy: {$csp}");
    }

    /**
     * Перевірити, чи використовується HTTPS
     */
    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}
