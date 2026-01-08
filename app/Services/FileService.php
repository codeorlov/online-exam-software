<?php
/**
 * Сервіс для роботи з файлами
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use App\Models\User;
use App\Models\Group;
use App\Models\Settings;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;

class FileService
{
    private File $fileModel;
    private User $userModel;
    private Group $groupModel;
    private Settings $settings;
    private EmailService $emailService;

    public function __construct()
    {
        $this->fileModel = new File();
        $this->userModel = new User();
        $this->groupModel = new Group();
        $this->settings = new Settings();
        $this->emailService = new EmailService();
    }

    /**
     * Завантажити файл
     */
    public function uploadFile(array $fileData, array $userIds = [], array $groupIds = []): array
    {
        // Перевірка розміру запиту на рівні PHP
        if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 104857600) {
            throw new \RuntimeException('Розмір запиту перевищує максимально дозволений');
        }

        $maxFileSize = (int)$this->settings->get('max_file_size', 10485760);
        $allowedTypes = $this->settings->get('allowed_file_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif');
        $allowedTypesArray = array_map('trim', explode(',', strtolower($allowedTypes)));

        // Перевірка, чи файл дійсно був завантажений через HTTP POST
        if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
            throw new \RuntimeException('Невірний файл або файл не був завантажений через HTTP POST');
        }

        if ($fileData['size'] > $maxFileSize) {
            throw new \RuntimeException("Розмір файлу перевищує " . round($maxFileSize / 1048576, 1) . " MB");
        }

        // Перевірка MIME-типу через finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);

        // Мапінг дозволених розширень до MIME-типів
        $allowedMimeTypes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
            '7z' => ['application/x-7z-compressed'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif']
        ];

        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypesArray)) {
            throw new \RuntimeException("Тип файлу не дозволено. Дозволені типи: {$allowedTypes}");
        }

        // Перевірка MIME-типу
        if (!isset($allowedMimeTypes[$extension]) || !in_array($detectedMimeType, $allowedMimeTypes[$extension])) {
            throw new \RuntimeException("MIME-тип файлу не відповідає розширенню. Виявлено: {$detectedMimeType}");
        }

        $originalName = basename($fileData['name']);
        $originalName = preg_replace('/[\/\\\\:\*\?"<>\|]/', '_', $originalName);
        $originalName = preg_replace('/\.\./', '_', $originalName);
        $originalName = trim($originalName);
        if (empty($originalName)) {
            $originalName = 'file';
        }

        $uploadDir = BASE_PATH . '/public/uploads/files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Захист від Race Condition - перевірка на унікальність імені файлу
        $attempts = 0;
        do {
            $fileName = uniqid('file_', true) . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            $attempts++;
            if ($attempts > 10) {
                throw new \RuntimeException('Не вдалося створити унікальне ім\'я файлу');
            }
        } while (file_exists($filePath));

        if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
            throw new \RuntimeException('Помилка при збереженні файлу');
        }

        $userId = Session::get('user_id');
        $fileId = $this->fileModel->create([
            'name' => $fileName,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $fileData['size'],
            'mime_type' => $detectedMimeType,
            'uploaded_by' => $userId
        ]);

        $uploader = $this->userModel->findById($userId);
        $uploaderName = $uploader ? ($uploader['first_name'] . ' ' . $uploader['last_name']) : 'Адміністратор';

        if (!empty($userIds)) {
            $this->fileModel->assignToUsers($fileId, $userIds);
            $this->sendNotificationsToUsers($fileId, $userIds, $originalName, $uploaderName);
        }

        if (!empty($groupIds)) {
            $this->fileModel->assignToGroups($fileId, $groupIds);
            $this->sendNotificationsToGroups($fileId, $groupIds, $originalName, $uploaderName);
        }

        Logger::audit('file_uploaded', $userId, ['file_id' => $fileId, 'file_name' => $originalName]);

        return ['file_id' => $fileId, 'file_name' => $fileName];
    }

    /**
     * Видалити файл
     */
    public function deleteFile(int $fileId, int $userId): bool
    {
        $file = $this->fileModel->findById($fileId);
        if (!$file) {
            return false;
        }

        $filePath = $file['file_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $result = $this->fileModel->delete($fileId);
        if ($result) {
            Logger::audit('file_deleted', $userId, ['file_id' => $fileId]);
        }

        return $result;
    }

    /**
     * Оновити призначення файлу
     */
    public function updateAssignments(int $fileId, array $userIds, array $groupIds, int $userId): void
    {
        $file = $this->fileModel->findById($fileId);
        if (!$file) {
            throw new \RuntimeException('Файл не знайдено');
        }

        $oldUserIds = $this->fileModel->getAssignedUserIds($fileId);
        $oldGroupIds = $this->fileModel->getAssignedGroupIds($fileId);

        if (!empty($userIds)) {
            $this->fileModel->assignToUsers($fileId, $userIds);
        } else {
            $this->fileModel->removeUserAssignments($fileId);
        }

        if (!empty($groupIds)) {
            $this->fileModel->assignToGroups($fileId, $groupIds);
        } else {
            $this->fileModel->removeGroupAssignments($fileId);
        }

        $newUserIds = array_diff($userIds, $oldUserIds);
        $uploader = $this->userModel->findById((int)$file['uploaded_by']);
        $uploaderName = $uploader ? ($uploader['first_name'] . ' ' . $uploader['last_name']) : 'Адміністратор';

        if (!empty($newUserIds)) {
            $this->sendNotificationsToUsers($fileId, $newUserIds, $file['original_name'] ?? 'Файл', $uploaderName);
        }

        Logger::audit('file_assignments_updated', $userId, ['file_id' => $fileId]);
    }

    /**
     * Перевірити права доступу до файлу
     */
    public function canAccessFile(int $fileId, int $userId, string $userRole): bool
    {
        if ($userRole === 'admin') {
            return true;
        }

        $file = $this->fileModel->findById($fileId);
        if (!$file) {
            return false;
        }

        if ($userRole === 'teacher' && (int)$file['uploaded_by'] === $userId) {
            return true;
        }

        if ($userRole === 'student') {
            return $this->fileModel->isAssignedToUser($fileId, $userId);
        }

        return false;
    }

    /**
     * Відправити сповіщення користувачам
     */
    private function sendNotificationsToUsers(int $fileId, array $userIds, string $fileName, string $uploaderName): void
    {
        foreach ($userIds as $studentId) {
            $student = $this->userModel->findById((int)$studentId);
            if ($student && (int)($student['email_notifications'] ?? 1) === 1 && $student['role'] === 'student') {
                $emailSent = $this->emailService->sendFileSharedNotification(
                    $student['email'],
                    $student['first_name'] . ' ' . $student['last_name'],
                    $fileName,
                    $uploaderName
                );

                if (!$emailSent) {
                    Logger::error('Failed to send file shared notification', [
                        'student_id' => $studentId,
                        'file_id' => $fileId,
                        'email' => $student['email']
                    ]);
                }
            }
        }
    }

    /**
     * Відправити сповіщення групам
     */
    private function sendNotificationsToGroups(int $fileId, array $groupIds, string $fileName, string $uploaderName): void
    {
        $db = Database::getInstance();
        foreach ($groupIds as $groupId) {
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'student' AND group_id = :group_id");
            $stmt->execute(['group_id' => $groupId]);
            $students = $stmt->fetchAll();

            foreach ($students as $student) {
                if ((int)($student['email_notifications'] ?? 1) === 1) {
                    $emailSent = $this->emailService->sendFileSharedNotification(
                        $student['email'],
                        $student['first_name'] . ' ' . $student['last_name'],
                        $fileName,
                        $uploaderName
                    );

                    if (!$emailSent) {
                        Logger::error('Failed to send file shared notification to group student', [
                            'student_id' => $student['id'],
                            'file_id' => $fileId,
                            'group_id' => $groupId,
                            'email' => $student['email']
                        ]);
                    }
                }
            }
        }
    }
}
