<?php
/**
 * Клас для логування подій додатку
 */

declare(strict_types=1);

namespace App\Core;

class Logger
{
    /**
     * Записати повідомлення в лог
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        $logFile = LOGS_PATH . '/' . strtolower($level) . '.log';
        
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Логувати помилку
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Логувати попередження
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Логувати інформацію
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Логувати подію безпеки
     */
    public static function security(string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] {$message}{$contextStr}" . PHP_EOL;

        $logFile = LOGS_PATH . '/security.log';
        
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Логувати аудит (дії адміна/вчителя)
     */
    public static function audit(string $action, int $userId, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] User ID: {$userId} | Action: {$action}{$contextStr}" . PHP_EOL;

        $logFile = LOGS_PATH . '/audit.log';
        
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
