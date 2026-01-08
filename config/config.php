<?php
/**
 * Конфігурація додатку
 * Налаштуйте під ваше середовище
 */

if (!defined('APP_ROOT')) {
    die('Прямий доступ заборонено');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'my_database');
define('DB_USER', 'my_database_user');
define('DB_PASS', 'my_database_user_password');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Онлайн-тестування');
define('APP_URL', 'https://inrage.alwaysdata.net/');
define('APP_ENV', 'production');

define('SESSION_LIFETIME', 3600);
define('SESSION_NAME', 'QUIZ_SESSION');

define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

define('REGISTRATION_ENABLED', true);

define('BASE_PATH', dirname(__DIR__));
define('VIEWS_PATH', BASE_PATH . '/views');
define('LOGS_PATH', BASE_PATH . '/logs');

define('APP_TIMEZONE', 'Europe/Kyiv');
date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}
