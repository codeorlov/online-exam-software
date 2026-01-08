<?php
/**
 * Сервіс для відправки email
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Settings;

class EmailService
{
    private Settings $settings;

    public function __construct()
    {
        $this->settings = new Settings();
    }

    /**
     * Відправити email
     */
    public function send(string $to, string $subject, string $message, string $toName = ''): bool
    {
        $smtpEnabled = (bool)$this->settings->get('smtp_enabled', false);

        \App\Core\Logger::info('Sending email', [
            'to' => $to,
            'toName' => $toName,
            'subject' => $subject,
            'smtp_enabled' => $smtpEnabled
        ]);

        if ($smtpEnabled) {
            $result = $this->sendViaSmtp($to, $subject, $message, $toName);
        } else {
            $result = $this->sendViaMail($to, $subject, $message, $toName);
        }

        if (!$result) {
            \App\Core\Logger::error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'smtp_enabled' => $smtpEnabled
            ]);
        }

        return $result;
    }

    /**
     * Відправити email через SMTP
     */
    private function sendViaSmtp(string $to, string $subject, string $message, string $toName = ''): bool
    {
        $host = $this->settings->get('smtp_host', '');
        $port = (int)$this->settings->get('smtp_port', 587);
        $username = $this->settings->get('smtp_username', '');
        $password = $this->settings->get('smtp_password', '');
        $encryption = $this->settings->get('smtp_encryption', 'tls');
        $fromEmail = $this->settings->get('smtp_from_email', $username);
        $fromName = $this->settings->get('smtp_from_name', '');

        if (empty($host) || empty($username) || empty($password)) {
            return false;
        }

        try {
            if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendViaPHPMailer($to, $subject, $message, $toName, $host, $port, $username, $password, $encryption, $fromEmail, $fromName);
            } else {
                return $this->sendViaSocket($to, $subject, $message, $toName, $host, $port, $username, $password, $encryption, $fromEmail, $fromName);
            }
        } catch (\Exception $e) {
            \App\Core\Logger::error('smtp_send_error', [
                'error' => $e->getMessage(),
                'to' => $to
            ]);
            return false;
        }
    }

    /**
     * Відправити email через socket
     */
    private function sendViaSocket(string $to, string $subject, string $message, string $toName, string $host, int $port, string $username, string $password, string $encryption, string $fromEmail, string $fromName): bool
    {
        return $this->sendViaMail($to, $subject, $message, $toName);
    }

    /**
     * Відправити email через mail()
     */
    private function sendViaMail(string $to, string $subject, string $message, string $toName = ''): bool
    {
        $siteName = $this->settings->get('site_name', 'Система онлайн-тестування');
        $fromEmail = $this->settings->get('smtp_from_email', 'noreply@example.com');
        $fromName = $this->settings->get('smtp_from_name', $siteName);
        
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = $fromName ? '=?UTF-8?B?' . base64_encode($fromName) . '?=' : '';
        $encodedToName = $toName ? '=?UTF-8?B?' . base64_encode($toName) . '?=' : '';
        
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . ($encodedFromName ? "{$encodedFromName} <{$fromEmail}>" : $fromEmail);
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'X-Priority: 3';
        $headers[] = 'Date: ' . date('r');

        $toHeader = $encodedToName ? "{$encodedToName} <{$to}>" : $to;

        $htmlMessage = $this->wrapInHtmlTemplate($message, $siteName);

        $result = @mail($toHeader, $encodedSubject, $htmlMessage, implode("\r\n", $headers));
        
        if ($result) {
            \App\Core\Logger::info('Email sent successfully via mail()', [
                'to' => $to,
                'subject' => $subject,
                'from' => $fromEmail
            ]);
        } else {
            $lastError = error_get_last();
            \App\Core\Logger::error('Failed to send email via mail()', [
                'to' => $to,
                'subject' => $subject,
                'from' => $fromEmail,
                'error' => $lastError ? $lastError['message'] : 'Unknown error',
                'php_mail_error' => $lastError
            ]);
        }
        
        return $result;
    }

    /**
     * Обгорнути повідомлення в HTML шаблон
     */
    private function wrapInHtmlTemplate(string $message, string $siteName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$siteName}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .content {
            background: white;
            padding: 20px;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$siteName}</h1>
        </div>
        <div class="content">
            {$message}
        </div>
        <div class="footer">
            <p>Це автоматичне повідомлення, будь ласка, не відповідайте на нього.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Відправити email через PHPMailer (якщо доступний)
     */
    private function sendViaPHPMailer(string $to, string $subject, string $message, string $toName, string $host, int $port, string $username, string $password, string $encryption, string $fromEmail, string $fromName): bool
    {
        return $this->sendViaMail($to, $subject, $message, $toName);
    }

    /**
     * Відправити сповіщення студенту про завершення тесту
     */
    public function sendTestCompletedNotification(string $to, string $toName, string $testName, string $resultUrl): bool
    {
        $siteName = $this->settings->get('site_name', 'Система онлайн-тестування');
        $subject = "Тест завершено: {$testName}";
        
        $message = <<<HTML
            <h2>Тест завершено</h2>
            <p>Вітаємо, {$toName}!</p>
            <p>Ви успішно завершили тест <strong>{$testName}</strong>.</p>
            <p>Ви можете переглянути результати, перейшовши за посиланням нижче:</p>
            <p><a href="{$resultUrl}" class="button">Переглянути результати</a></p>
            <p>Або скопіюйте це посилання в браузер:</p>
            <p style="word-break: break-all;">{$resultUrl}</p>
HTML;

        return $this->send($to, $subject, $message, $toName);
    }

    /**
     * Відправити сповіщення студенту про новий файл
     */
    public function sendFileSharedNotification(string $to, string $toName, string $fileName, string $uploadedBy): bool
    {
        $siteName = $this->settings->get('site_name', 'Система онлайн-тестування');
        $subject = "Новий файл доступний: {$fileName}";
        
        $message = <<<HTML
            <h2>Новий файл доступний</h2>
            <p>Вітаємо, {$toName}!</p>
            <p>З вами поділилися новим файлом <strong>{$fileName}</strong>.</p>
            <p>Файл завантажено користувачем: <strong>{$uploadedBy}</strong></p>
            <p>Ви можете переглянути файл в розділі "Доступні файли" у вашому особистому кабінеті.</p>
            <p><a href="/student/files" class="button">Перейти до файлів</a></p>
HTML;

        return $this->send($to, $subject, $message, $toName);
    }

    /**
     * Відправити сповіщення вчителю про призначення групи
     */
    public function sendGroupAssignedNotification(string $to, string $toName, string $groupName): bool
    {
        $siteName = $this->settings->get('site_name', 'Система онлайн-тестування');
        $subject = "Вам призначено групу: {$groupName}";
        
        $message = <<<HTML
            <h2>Нову групу призначено</h2>
            <p>Вітаємо, {$toName}!</p>
            <p>Вам призначено групу <strong>{$groupName}</strong>.</p>
            <p>Тепер ви можете керувати цією групою та призначати тести студентам з цієї групи.</p>
            <p><a href="/admin/groups" class="button">Перейти до груп</a></p>
HTML;

        return $this->send($to, $subject, $message, $toName);
    }

    /**
     * Відправити сповіщення вчителю про призначення предмета
     */
    public function sendSubjectAssignedNotification(string $to, string $toName, string $subjectName): bool
    {
        $siteName = $this->settings->get('site_name', 'Система онлайн-тестування');
        $subject = "Вам призначено предмет: {$subjectName}";
        
        $message = <<<HTML
            <h2>Новий предмет призначено</h2>
            <p>Вітаємо, {$toName}!</p>
            <p>Вам призначено предмет <strong>{$subjectName}</strong>.</p>
            <p>Тепер ви можете створювати тести з цього предмета.</p>
            <p><a href="/admin/subjects" class="button">Перейти до предметів</a></p>
HTML;

        return $this->send($to, $subject, $message, $toName);
    }

    /**
     * Відправити сповіщення адміністратору про створення нового користувача
     */
    public function sendNewUserNotification(string $to, string $toName, string $newUserName, string $newUserEmail, string $newUserRole): bool
    {
        $siteName = $this->settings->get('site_name', 'Система онлайн-тестування');
        $subject = "Створено нового користувача: {$newUserName}";
        
        $roleNames = [
            'admin' => 'Адміністратор',
            'teacher' => 'Вчитель',
            'student' => 'Студент'
        ];
        $roleName = $roleNames[$newUserRole] ?? $newUserRole;
        
        $message = <<<HTML
            <h2>Нового користувача створено</h2>
            <p>Вітаємо, {$toName}!</p>
            <p>На сайті створено нового користувача:</p>
            <ul>
                <li><strong>Ім'я:</strong> {$newUserName}</li>
                <li><strong>Email:</strong> {$newUserEmail}</li>
                <li><strong>Роль:</strong> {$roleName}</li>
            </ul>
            <p><a href="/admin/users" class="button">Перейти до користувачів</a></p>
HTML;

        return $this->send($to, $subject, $message, $toName);
    }
}
