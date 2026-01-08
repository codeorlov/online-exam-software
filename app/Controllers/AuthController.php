<?php
/**
 * Контролер аутентифікації
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Core\Session;
use App\Core\Logger;
use App\Models\User;
use App\Models\Settings;
use App\Models\PasswordReset;
use App\Services\EmailService;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * Показати форму входу
     */
    public function showLogin(): void
    {
        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }

        $settingsModel = new Settings();
        $registrationEnabled = $settingsModel->get('registration_enabled', true);
        
        $this->view->set('title', 'Вхід в систему');
        $this->view->set('registrationEnabled', $registrationEnabled);
        $this->view->render('auth/login', 'auth');
    }

    /**
     * Обробити вхід
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }

        $token = Security::getCsrfTokenFromRequest();
        if (!$token || !Security::validateCsrfToken($token)) {
            $this->setFlash('error', 'Помилка безпеки. Оновіть сторінку і спробуйте знову.');
            $this->redirect('/login');
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Заповніть всі поля');
            $this->redirect('/login');
        }

        if (!Security::validateEmail($email)) {
            $this->setFlash('error', 'Некорректний email');
            $this->redirect('/login');
        }

        if (!Security::checkLoginAttempts($email)) {
            $this->setFlash('error', 'Занадто багато невдалих спроб. Спробуйте пізніше.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            Security::recordLoginAttempt($email);
            Logger::security("Failed login attempt for email: {$email}");
            $this->setFlash('error', 'Невірний email або пароль');
            $this->redirect('/login');
        }

        if ($user['status'] !== 'active') {
            $this->setFlash('error', 'Ваш акаунт заблоковано');
            $this->redirect('/login');
        }

        $settings = new Settings();
        $maintenanceMode = $settings->get('maintenance_mode', false);
        
        if ($maintenanceMode && $user['role'] !== 'admin') {
            Session::destroy();
            $this->setFlash('error', 'Сайт знаходиться на обслуговуванні. Доступ дозволено тільки адміністраторам.');
            $this->redirect('/login');
        }

        Security::clearLoginAttempts($email);
        Session::regenerateId(true);
        
        Session::set('user_id', (int)$user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['first_name'] . ' ' . $user['last_name']);
        Session::set('user_role', $user['role']);

        Logger::info("User logged in: {$user['email']}");
        Logger::audit('login', (int)$user['id']);

        $this->redirect('/dashboard');
    }

    /**
     * Показати форму реєстрації
     */
    public function showRegister(): void
    {
        $settingsModel = new \App\Models\Settings();
        $registrationEnabled = $settingsModel->get('registration_enabled', true);
        
        if (!$registrationEnabled) {
            $this->setFlash('error', 'Реєстрація вимкнена. Зверніться до адміністратора для створення акаунту.');
            $this->redirect('/login');
        }

        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }

        $this->view->set('title', 'Реєстрація');
        $this->view->render('auth/register', 'auth');
    }

    /**
     * Обробити реєстрацію
     */
    public function register(): void
    {
        $settingsModel = new \App\Models\Settings();
        $registrationEnabled = $settingsModel->get('registration_enabled', true);
        
        if (!$registrationEnabled) {
            $this->setFlash('error', 'Реєстрація вимкнена. Зверніться до адміністратора для створення акаунту.');
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
        }

        $token = Security::getCsrfTokenFromRequest();
        if (!$token || !Security::validateCsrfToken($token)) {
            $this->setFlash('error', 'Помилка безпеки. Оновіть сторінку і спробуйте знову.');
            $this->redirect('/register');
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';

        $errors = [];

        if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            $errors[] = 'Заповніть всі поля';
        }

        if (!Security::validateEmail($email)) {
            $errors[] = 'Некорректний email';
        }

        if ($this->userModel->emailExists($email)) {
            $errors[] = 'Email вже використовується';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Паролі не співпадають';
        }

        $passwordErrors = Security::validatePassword($password);
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }

        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            $this->redirect('/register');
        }

        $userCount = $this->userModel->count();
        $userRole = ($userCount === 0) ? 'admin' : 'student';

        $userId = $this->userModel->create([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $userRole,
            'status' => 'active'
        ]);

        Logger::info("New user registered: {$email} (role: {$userRole})");
        Logger::audit('user_registered', $userId, ['role' => $userRole]);
        
        if ($userRole === 'admin') {
            Logger::info("First user registered as administrator: {$email}");
        }

        $this->setFlash('success', 'Реєстрація успішна! Увійдіть в систему.');
        $this->redirect('/login');
    }

    /**
     * Показати налаштування користувача
     */
    public function settings(): void
    {
        $this->requireAuth();
        
        $userId = Session::get('user_id');
        $user = $this->userModel->findById((int)$userId);
        
        if (!$user) {
            $this->setFlash('error', 'Користувача не знайдено');
            $this->redirect('/dashboard');
        }
        
        $this->view->set('title', 'Налаштування');
        $this->view->set('user', $user);
        $this->view->render('auth/settings');
    }
    
    /**
     * Оновити налаштування користувача
     */
    public function updateSettings(): void
    {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
        }
        
        $token = Security::getCsrfTokenFromRequest();
        if (!$token || !Security::validateCsrfToken($token)) {
            $this->setFlash('error', 'Помилка безпеки. Оновіть сторінку і спробуйте знову.');
            $this->redirect('/settings');
        }
        
        $userId = Session::get('user_id');
        $user = $this->userModel->findById((int)$userId);
        
        if (!$user) {
            $this->setFlash('error', 'Користувача не знайдено');
            $this->redirect('/dashboard');
        }
        
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($firstName) || empty($lastName)) {
            $errors[] = 'Ім\'я та прізвище обов\'язкові';
        }
        
        if (empty($email)) {
            $errors[] = 'Email обов\'язковий';
        } elseif (!Security::validateEmail($email)) {
            $errors[] = 'Некорректний email';
        } elseif ($email !== $user['email'] && $this->userModel->emailExists($email, (int)$userId)) {
            $errors[] = 'Email вже використовується';
        }
        
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'Введіть поточний пароль для зміни';
            } elseif (!Security::verifyPassword($currentPassword, $user['password'])) {
                $errors[] = 'Невірний поточний пароль';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'Нові паролі не співпадають';
            } else {
                $passwordErrors = Security::validatePassword($newPassword);
                if (!empty($passwordErrors)) {
                    $errors = array_merge($errors, $passwordErrors);
                }
            }
        }
        
        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            $this->redirect('/settings');
        }
        
        $updateData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0
        ];
        
        if (!empty($newPassword)) {
            $updateData['password'] = $newPassword;
        }
        
        if ($this->userModel->update((int)$userId, $updateData)) {
            Session::set('user_email', $email);
            Session::set('user_name', $firstName . ' ' . $lastName);
            
            Logger::info("User settings updated: {$user['email']}");
            Logger::audit('settings_updated', (int)$userId);
            
            $this->setFlash('success', 'Налаштування успішно оновлено');
        } else {
            $this->setFlash('error', 'Помилка при оновленні налаштувань');
        }
        
        $this->redirect('/settings');
    }

    /**
     * Вихід
     */
    public function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            Logger::audit('logout', (int)$userId);
        }

        Session::destroy();
        $this->redirect('/login');
    }

    /**
     * Показати форму запиту скидання пароля
     */
    public function showForgotPassword(): void
    {
        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }

        $this->view->set('title', 'Відновлення пароля');
        $this->view->render('auth/forgot_password', 'auth');
    }

    /**
     * Обробити запит скидання пароля
     */
    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
        }

        $token = Security::getCsrfTokenFromRequest();
        if (!$token || !Security::validateCsrfToken($token)) {
            $this->setFlash('error', 'Помилка безпеки. Оновіть сторінку і спробуйте знову.');
            $this->redirect('/forgot-password');
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $this->setFlash('error', 'Введіть email');
            $this->redirect('/forgot-password');
        }

        if (!Security::validateEmail($email)) {
            $this->setFlash('error', 'Некорректний email');
            $this->redirect('/forgot-password');
        }

        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            $this->setFlash('success', 'Якщо вказаний email існує в системі, на нього буде відправлено інструкцію зі скидання пароля.');
            $this->redirect('/login');
        }

        $resetToken = bin2hex(random_bytes(32));
        
        $passwordResetModel = new PasswordReset();
        $passwordResetModel->create($email, $resetToken, 24);

        $siteName = (new Settings())->get('site_name', 'Система онлайн-тестування');
        $resetUrl = APP_URL . '/reset-password?token=' . $resetToken;
        
        $emailSubject = "Скидання пароля - {$siteName}";
        $emailMessage = "
            <p>Вітаємо, {$user['first_name']}!</p>
            <p>Ви запросили скидання пароля для вашого акаунта.</p>
            <p>Для встановлення нового пароля перейдіть за посиланням:</p>
            <p><a href=\"{$resetUrl}\" class=\"button\">Скинути пароль</a></p>
            <p>Або скопіюйте це посилання в адресний рядок браузера:</p>
            <p style=\"word-break: break-all;\">{$resetUrl}</p>
            <p>Посилання дійсне протягом 24 годин.</p>
            <p>Якщо ви не запитували скидання пароля, просто проігноруйте цей лист.</p>
        ";

        $emailService = new EmailService();
        $emailSent = $emailService->send(
            $email,
            $emailSubject,
            $emailMessage,
            $user['first_name'] . ' ' . $user['last_name']
        );

        if ($emailSent) {
            Logger::info("Password reset requested for: {$email}");
            $this->setFlash('success', 'Інструкція зі скидання пароля відправлена на ваш email.');
        } else {
            Logger::error('password_reset_email_failed', ['email' => $email]);
            $this->setFlash('error', 'Помилка при відправці email. Спробуйте пізніше або зверніться до адміністратора.');
        }

        $this->redirect('/login');
    }

    /**
     * Показати форму скидання пароля
     */
    public function showResetPassword(): void
    {
        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }

        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->setFlash('error', 'Невірне посилання для скидання пароля');
            $this->redirect('/forgot-password');
        }

        $passwordResetModel = new PasswordReset();
        $resetRecord = $passwordResetModel->findByToken($token);

        if (!$resetRecord) {
            $this->setFlash('error', 'Посилання для скидання пароля недійсне або застаріло');
            $this->redirect('/forgot-password');
        }

        $this->view->set('title', 'Встановлення нового пароля');
        $this->view->set('token', $token);
        $this->view->render('auth/reset_password', 'auth');
    }

    /**
     * Обробити скидання пароля
     */
    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
        }

        $token = Security::getCsrfTokenFromRequest();
        if (!$token || !Security::validateCsrfToken($token)) {
            $this->setFlash('error', 'Помилка безпеки. Оновіть сторінку і спробуйте знову.');
            $this->redirect('/forgot-password');
        }

        $resetToken = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($resetToken)) {
            $this->setFlash('error', 'Невірне посилання для скидання пароля');
            $this->redirect('/forgot-password');
        }

        if (empty($password) || empty($passwordConfirm)) {
            $this->setFlash('error', 'Заповніть всі поля');
            $this->redirect('/reset-password?token=' . urlencode($resetToken));
        }

        if ($password !== $passwordConfirm) {
            $this->setFlash('error', 'Паролі не співпадають');
            $this->redirect('/reset-password?token=' . urlencode($resetToken));
        }

        $passwordErrors = Security::validatePassword($password);
        if (!empty($passwordErrors)) {
            $this->setFlash('error', implode('<br>', $passwordErrors));
            $this->redirect('/reset-password?token=' . urlencode($resetToken));
        }

        $passwordResetModel = new PasswordReset();
        $resetRecord = $passwordResetModel->findByToken($resetToken);

        if (!$resetRecord) {
            $this->setFlash('error', 'Посилання для скидання пароля недійсне або застаріло');
            $this->redirect('/forgot-password');
        }

        $user = $this->userModel->findByEmail($resetRecord['email']);
        if (!$user) {
            $this->setFlash('error', 'Користувача не знайдено');
            $this->redirect('/forgot-password');
        }

        $success = $this->userModel->update((int)$user['id'], ['password' => $password]);

        if ($success) {
            $passwordResetModel->markAsUsed($resetToken);
            
            Logger::info("Password reset completed for: {$user['email']}");
            Logger::audit('password_reset', (int)$user['id']);
            
            $this->setFlash('success', 'Пароль успішно змінено. Тепер ви можете увійти в систему.');
            $this->redirect('/login');
        } else {
            Logger::error('password_reset_failed', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'token' => substr($resetToken, 0, 10) . '...'
            ]);
            $this->setFlash('error', 'Помилка при оновленні пароля. Спробуйте ще раз або запросіть нове посилання.');
            $this->redirect('/reset-password?token=' . urlencode($resetToken));
        }
    }
}
