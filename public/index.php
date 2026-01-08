<?php
/**
 * Точка входу в додаток
 * Всі запити проходять через цей файл завдяки .htaccess
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
}, true, true);

$criticalClasses = [
    'App\Core\Session' => APP_ROOT . '/app/Core/Session.php',
    'App\Core\Router' => APP_ROOT . '/app/Core/Router.php',
    'App\Core\Database' => APP_ROOT . '/app/Core/Database.php',
    'App\Core\Security' => APP_ROOT . '/app/Core/Security.php',
    'App\Core\View' => APP_ROOT . '/app/Core/View.php',
    'App\Core\Logger' => APP_ROOT . '/app/Core/Logger.php',
    'App\Core\ErrorHandler' => APP_ROOT . '/app/Core/ErrorHandler.php',
];

foreach ($criticalClasses as $className => $filePath) {
    if (!class_exists($className)) {
        if (file_exists($filePath)) {
            require_once $filePath;
        } else {
            if (defined('APP_ENV') && APP_ENV === 'development') {
                http_response_code(500);
                die("Критична помилка: Клас {$className} не знайдено. Очікуваний файл: {$filePath}");
            } else {
                http_response_code(500);
                die("Помилка системи. Зверніться до адміністратора.");
            }
        }
    }
}

use App\Core\Session;
use App\Core\Router;
use App\Core\ErrorHandler;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\TestController;
use App\Controllers\AdminController;
use App\Controllers\TeacherController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\MaintenanceMiddleware;

ErrorHandler::register();
ErrorHandler::setSecurityHeaders();
Session::start();

$maintenanceMiddleware = new MaintenanceMiddleware();
$maintenanceMiddleware->handle();

$router = new Router();
$router->get('/', function() {
    header('Location: /dashboard');
    exit;
});

$router->get('/login', AuthController::class . '::showLogin', [GuestMiddleware::class]);
$router->post('/login', AuthController::class . '::login', [GuestMiddleware::class]);
$router->get('/register', AuthController::class . '::showRegister', [GuestMiddleware::class]);
$router->post('/register', AuthController::class . '::register', [GuestMiddleware::class]);
$router->get('/forgot-password', AuthController::class . '::showForgotPassword', [GuestMiddleware::class]);
$router->post('/forgot-password', AuthController::class . '::forgotPassword', [GuestMiddleware::class]);
$router->get('/reset-password', AuthController::class . '::showResetPassword', [GuestMiddleware::class]);
$router->post('/reset-password', AuthController::class . '::resetPassword', [GuestMiddleware::class]);
$router->get('/settings', AuthController::class . '::settings', [AuthMiddleware::class]);
$router->post('/settings/update', AuthController::class . '::updateSettings', [AuthMiddleware::class]);
$router->get('/logout', AuthController::class . '::logout');

$router->get('/dashboard', DashboardController::class . '::index', [AuthMiddleware::class]);
$router->get('/dashboard/admin', DashboardController::class . '::admin', [AuthMiddleware::class]);
$router->get('/dashboard/teacher', DashboardController::class . '::teacher', [AuthMiddleware::class]);
$router->get('/dashboard/student', DashboardController::class . '::student', [AuthMiddleware::class]);
$router->get('/student/files', DashboardController::class . '::studentFiles', [AuthMiddleware::class]);

$router->get('/test/:testId/start', TestController::class . '::start', [AuthMiddleware::class]);
$router->get('/test/:testId/take/:attemptId', TestController::class . '::take', [AuthMiddleware::class]);
$router->post('/test/:testId/save/:attemptId', TestController::class . '::saveAnswer', [AuthMiddleware::class]);
$router->get('/test/:testId/complete/:attemptId', TestController::class . '::complete', [AuthMiddleware::class]);
$router->get('/test/:testId/result/:attemptId', TestController::class . '::result', [AuthMiddleware::class]);
$router->get('/test/:testId/result/:attemptId/edit', TestController::class . '::editResult', [AuthMiddleware::class]);
$router->post('/test/:testId/result/:attemptId/edit', TestController::class . '::editResult', [AuthMiddleware::class]);

$router->get('/admin/users', AdminController::class . '::users', [AuthMiddleware::class]);
$router->get('/admin/users/create', AdminController::class . '::createUser', [AuthMiddleware::class]);
$router->post('/admin/users/create', AdminController::class . '::createUser', [AuthMiddleware::class]);
$router->get('/admin/users/edit/:id', AdminController::class . '::editUser', [AuthMiddleware::class]);
$router->post('/admin/users/edit/:id', AdminController::class . '::editUser', [AuthMiddleware::class]);
$router->get('/admin/users/delete/:id', AdminController::class . '::deleteUser', [AuthMiddleware::class]);
$router->get('/admin/groups', AdminController::class . '::groups', [AuthMiddleware::class]);
$router->get('/admin/groups/create', AdminController::class . '::createGroup', [AuthMiddleware::class]);
$router->post('/admin/groups/create', AdminController::class . '::createGroup', [AuthMiddleware::class]);
$router->get('/admin/groups/view/:id', AdminController::class . '::viewGroup', [AuthMiddleware::class]);
$router->get('/admin/groups/edit/:id', AdminController::class . '::editGroup', [AuthMiddleware::class]);
$router->post('/admin/groups/edit/:id', AdminController::class . '::editGroup', [AuthMiddleware::class]);
$router->get('/admin/groups/:groupId/remove-student/:studentId', AdminController::class . '::removeStudentFromGroup', [AuthMiddleware::class]);
$router->get('/admin/groups/delete/:id', AdminController::class . '::deleteGroup', [AuthMiddleware::class]);
$router->get('/admin/subjects', AdminController::class . '::subjects', [AuthMiddleware::class]);
$router->get('/admin/subjects/create', AdminController::class . '::createSubject', [AuthMiddleware::class]);
$router->post('/admin/subjects/create', AdminController::class . '::createSubject', [AuthMiddleware::class]);
$router->get('/admin/subjects/edit/:id', AdminController::class . '::editSubject', [AuthMiddleware::class]);
$router->post('/admin/subjects/edit/:id', AdminController::class . '::editSubject', [AuthMiddleware::class]);
$router->get('/admin/subjects/delete/:id', AdminController::class . '::deleteSubject', [AuthMiddleware::class]);
$router->get('/admin/tests', AdminController::class . '::tests', [AuthMiddleware::class]);
$router->get('/admin/tests/create', AdminController::class . '::createTest', [AuthMiddleware::class]);
$router->post('/admin/tests/create', AdminController::class . '::createTest', [AuthMiddleware::class]);
$router->get('/admin/tests/edit/:id', AdminController::class . '::editTest', [AuthMiddleware::class]);
$router->post('/admin/tests/edit/:id', AdminController::class . '::editTest', [AuthMiddleware::class]);
$router->get('/admin/tests/delete/:id', AdminController::class . '::deleteTest', [AuthMiddleware::class]);
$router->get('/admin/tests/:testId/questions/add', AdminController::class . '::addQuestion', [AuthMiddleware::class]);
$router->post('/admin/tests/:testId/questions/add', AdminController::class . '::addQuestion', [AuthMiddleware::class]);
$router->get('/admin/tests/:testId/questions/edit/:questionId', AdminController::class . '::editQuestion', [AuthMiddleware::class]);
$router->post('/admin/tests/:testId/questions/edit/:questionId', AdminController::class . '::editQuestion', [AuthMiddleware::class]);
$router->get('/admin/tests/:testId/questions/delete/:questionId', AdminController::class . '::deleteQuestion', [AuthMiddleware::class]);
$router->get('/admin/tests/:testId/assign', AdminController::class . '::assignTest', [AuthMiddleware::class]);
$router->post('/admin/tests/:testId/assign', AdminController::class . '::assignTest', [AuthMiddleware::class]);
$router->get('/admin/tests/:testId/stats', AdminController::class . '::testStats', [AuthMiddleware::class]);
$router->get('/admin/tests/:testId/stats/export', AdminController::class . '::exportTestStats', [AuthMiddleware::class]);
$router->get('/admin/tests/:testId/attempts/delete/:attemptId', AdminController::class . '::deleteAttempt', [AuthMiddleware::class]);
$router->get('/admin/files', AdminController::class . '::files', [AuthMiddleware::class]);
$router->get('/admin/files/upload', AdminController::class . '::uploadFile', [AuthMiddleware::class]);
$router->post('/admin/files/upload', AdminController::class . '::uploadFile', [AuthMiddleware::class]);
$router->get('/admin/files/edit/:id', AdminController::class . '::editFile', [AuthMiddleware::class]);
$router->post('/admin/files/edit/:id', AdminController::class . '::editFile', [AuthMiddleware::class]);
$router->get('/admin/files/delete/:id', AdminController::class . '::deleteFile', [AuthMiddleware::class]);
$router->get('/admin/files/download/:id', AdminController::class . '::downloadFile', [AuthMiddleware::class]);
$router->get('/admin/settings', AdminController::class . '::settings', [AuthMiddleware::class]);
$router->post('/admin/settings/save', AdminController::class . '::saveSettings', [AuthMiddleware::class]);

$router->get('/teacher/tests', function() {
    header('Location: /dashboard/teacher');
    exit;
}, [AuthMiddleware::class]);
$router->get('/teacher/tests/create', TeacherController::class . '::createTest', [AuthMiddleware::class]);
$router->post('/teacher/tests/create', TeacherController::class . '::createTest', [AuthMiddleware::class]);
$router->get('/teacher/tests/edit/:id', TeacherController::class . '::editTest', [AuthMiddleware::class]);
$router->post('/teacher/tests/edit/:id', TeacherController::class . '::editTest', [AuthMiddleware::class]);
$router->get('/teacher/tests/delete/:id', TeacherController::class . '::deleteTest', [AuthMiddleware::class]);
$router->get('/teacher/tests/:testId/questions/add', TeacherController::class . '::addQuestion', [AuthMiddleware::class]);
$router->post('/teacher/tests/:testId/questions/add', TeacherController::class . '::addQuestion', [AuthMiddleware::class]);
$router->get('/teacher/tests/:testId/questions/edit/:questionId', TeacherController::class . '::editQuestion', [AuthMiddleware::class]);
$router->post('/teacher/tests/:testId/questions/edit/:questionId', TeacherController::class . '::editQuestion', [AuthMiddleware::class]);
$router->get('/teacher/tests/:testId/questions/delete/:questionId', TeacherController::class . '::deleteQuestion', [AuthMiddleware::class]);
$router->get('/teacher/tests/:testId/assign', TeacherController::class . '::assignTest', [AuthMiddleware::class]);
$router->post('/teacher/tests/:testId/assign', TeacherController::class . '::assignTest', [AuthMiddleware::class]);
$router->get('/teacher/tests/:testId/stats', TeacherController::class . '::testStats', [AuthMiddleware::class]);
$router->get('/teacher/tests/:testId/stats/export', TeacherController::class . '::exportTestStats', [AuthMiddleware::class]);
$router->get('/teacher/tests/:testId/attempts/delete/:attemptId', TeacherController::class . '::deleteAttempt', [AuthMiddleware::class]);
$router->get('/teacher/files', TeacherController::class . '::files', [AuthMiddleware::class]);
$router->get('/teacher/files/upload', TeacherController::class . '::uploadFile', [AuthMiddleware::class]);
$router->post('/teacher/files/upload', TeacherController::class . '::uploadFile', [AuthMiddleware::class]);
$router->get('/teacher/files/edit/:id', TeacherController::class . '::editFile', [AuthMiddleware::class]);
$router->post('/teacher/files/edit/:id', TeacherController::class . '::editFile', [AuthMiddleware::class]);
$router->get('/teacher/files/delete/:id', TeacherController::class . '::deleteFile', [AuthMiddleware::class]);
$router->get('/teacher/files/download/:id', AdminController::class . '::downloadFile', [AuthMiddleware::class]);

try {
    $router->dispatch();
} catch (\Exception $e) {
    ErrorHandler::handleException($e);
}
