<?php
/**
 * Модель для роботи з файлами
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class File
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Знайти файл за ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, u.first_name, u.last_name, u.email
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            WHERE f.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Створити запис про файл
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO files (name, original_name, file_path, file_size, mime_type, uploaded_by, created_at)
            VALUES (:name, :original_name, :file_path, :file_size, :mime_type, :uploaded_by, NOW())
        ");

        $stmt->execute([
            'name' => $data['name'],
            'original_name' => $data['original_name'],
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'],
            'mime_type' => $data['mime_type'] ?? null,
            'uploaded_by' => $data['uploaded_by']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Отримати всі файли (для адміністратора)
     */
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['uploaded_by'])) {
            $where[] = 'f.uploaded_by = :uploaded_by';
            $params['uploaded_by'] = $filters['uploaded_by'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(f.original_name LIKE :search OR f.name LIKE :search2)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT f.*, u.first_name, u.last_name, u.email
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            {$whereClause}
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Підрахувати кількість файлів
     */
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['uploaded_by'])) {
            $where[] = 'uploaded_by = :uploaded_by';
            $params['uploaded_by'] = $filters['uploaded_by'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(original_name LIKE :search OR name LIKE :search2)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM files {$whereClause}");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Отримати файли, доступні студенту
     */
    public function getAvailableForStudent(int $studentId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $userStmt = $this->db->prepare("SELECT group_id FROM users WHERE id = :student_id");
        $userStmt->execute(['student_id' => $studentId]);
        $user = $userStmt->fetch();
        $groupId = $user ? (int)$user['group_id'] : null;

        $whereConditions = [];
        $params = [];

        if ($groupId !== null) {
            $whereConditions[] = '(fa.user_id = :student_id OR fa.group_id = :group_id)';
            $params['student_id'] = $studentId;
            $params['group_id'] = $groupId;
        } else {
            $whereConditions[] = 'fa.user_id = :student_id';
            $params['student_id'] = $studentId;
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = '(f.original_name LIKE :search OR f.name LIKE :search2)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        $stmt = $this->db->prepare("
            SELECT DISTINCT f.*, u.first_name, u.last_name, u.email
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            INNER JOIN file_assignments fa ON f.id = fa.file_id
            {$whereClause}
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Підрахувати кількість файлів, доступних студенту
     */
    public function countAvailableForStudent(int $studentId, array $filters = []): int
    {
        $userStmt = $this->db->prepare("SELECT group_id FROM users WHERE id = :student_id");
        $userStmt->execute(['student_id' => $studentId]);
        $user = $userStmt->fetch();
        $groupId = $user ? (int)$user['group_id'] : null;

        $whereConditions = [];
        $params = [];

        if ($groupId !== null) {
            $whereConditions[] = '(fa.user_id = :student_id OR fa.group_id = :group_id)';
            $params['student_id'] = $studentId;
            $params['group_id'] = $groupId;
        } else {
            $whereConditions[] = 'fa.user_id = :student_id';
            $params['student_id'] = $studentId;
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = '(f.original_name LIKE :search OR f.name LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT f.id)
            FROM files f
            INNER JOIN file_assignments fa ON f.id = fa.file_id
            {$whereClause}
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Видалити файл
     */
    public function delete(int $id): bool
    {
        $file = $this->findById($id);
        if (!$file) {
            return false;
        }

        $filePath = $file['file_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $stmt = $this->db->prepare("DELETE FROM file_assignments WHERE file_id = :file_id");
        $stmt->execute(['file_id' => $id]);
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Призначити файл студентам або групам
     */
    public function assignToUsers(int $fileId, array $userIds): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM file_assignments WHERE file_id = :file_id AND user_id IS NOT NULL");
            $stmt->execute(['file_id' => $fileId]);
            if (!empty($userIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO file_assignments (file_id, user_id, created_at)
                    VALUES (:file_id, :user_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()
                ");
                foreach ($userIds as $userId) {
                    $stmt->execute([
                        'file_id' => $fileId,
                        'user_id' => (int)$userId
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Призначити файл групам
     */
    public function assignToGroups(int $fileId, array $groupIds): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM file_assignments WHERE file_id = :file_id AND group_id IS NOT NULL");
            $stmt->execute(['file_id' => $fileId]);
            if (!empty($groupIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO file_assignments (file_id, group_id, created_at)
                    VALUES (:file_id, :group_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()
                ");
                foreach ($groupIds as $groupId) {
                    $stmt->execute([
                        'file_id' => $fileId,
                        'group_id' => (int)$groupId
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Отримати призначення файлу
     */
    public function getAssignments(int $fileId): array
    {
        $stmt = $this->db->prepare("
            SELECT fa.*, u.first_name, u.last_name, u.email, g.name as group_name
            FROM file_assignments fa
            LEFT JOIN users u ON fa.user_id = u.id
            LEFT JOIN groups g ON fa.group_id = g.id
            WHERE fa.file_id = :file_id
        ");
        $stmt->execute(['file_id' => $fileId]);
        return $stmt->fetchAll();
    }

    /**
     * Отримати файли, призначені групі
     */
    public function getByGroupId(int $groupId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT f.*, u.first_name, u.last_name, u.email
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            INNER JOIN file_assignments fa ON f.id = fa.file_id
            WHERE fa.group_id = :group_id
            ORDER BY f.created_at DESC
        ");
        $stmt->execute(['group_id' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Отримати ID користувачів, яким призначено файл
     */
    public function getAssignedUserIds(int $fileId): array
    {
        $stmt = $this->db->prepare("SELECT user_id FROM file_assignments WHERE file_id = :file_id AND user_id IS NOT NULL");
        $stmt->execute(['file_id' => $fileId]);
        return array_column($stmt->fetchAll(), 'user_id');
    }

    /**
     * Отримати ID груп, яким призначено файл
     */
    public function getAssignedGroupIds(int $fileId): array
    {
        $stmt = $this->db->prepare("SELECT group_id FROM file_assignments WHERE file_id = :file_id AND group_id IS NOT NULL");
        $stmt->execute(['file_id' => $fileId]);
        return array_column($stmt->fetchAll(), 'group_id');
    }

    /**
     * Видалити призначення користувачам
     */
    public function removeUserAssignments(int $fileId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM file_assignments WHERE file_id = :file_id AND user_id IS NOT NULL");
        return $stmt->execute(['file_id' => $fileId]);
    }

    /**
     * Видалити призначення групам
     */
    public function removeGroupAssignments(int $fileId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM file_assignments WHERE file_id = :file_id AND group_id IS NOT NULL");
        return $stmt->execute(['file_id' => $fileId]);
    }

    /**
     * Перевірити, чи призначено файл користувачу
     */
    public function isAssignedToUser(int $fileId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM file_assignments 
            WHERE file_id = :file_id AND user_id = :user_id
        ");
        $stmt->execute(['file_id' => $fileId, 'user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
