<?php
/**
 * Модель користувача
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Security;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Знайти користувача за email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Знайти користувача за ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Створити нового користувача
     */
    public function create(array $data): int
    {
        $fields = ['email', 'password', 'first_name', 'last_name', 'role', 'status'];
        $values = [':email', ':password', ':first_name', ':last_name', ':role', ':status'];
        $params = [
            'email' => $data['email'],
            'password' => Security::hashPassword($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => $data['role'] ?? 'student',
            'status' => $data['status'] ?? 'active'
        ];

        if (isset($data['group_id']) && $data['group_id'] !== null) {
            $fields[] = 'group_id';
            $values[] = ':group_id';
            $params['group_id'] = $data['group_id'];
        }

        $fields[] = 'created_at';
        $values[] = 'NOW()';

        $sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Оновити користувача
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['first_name', 'last_name', 'email', 'role', 'status', 'group_id', 'email_notifications'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $updates[] = "password = :password";
            $params['password'] = Security::hashPassword($data['password']);
        }

        if (empty($updates)) {
            return false;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Видалити користувача
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Отримати всіх користувачів з пагінацією
     */
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = "role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(email LIKE :search OR first_name LIKE :search2 OR last_name LIKE :search3)";
            $searchValue = '%' . $filters['search'] . '%';
            $params['search'] = $searchValue;
            $params['search2'] = $searchValue;
            $params['search3'] = $searchValue;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM users {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Отримати кількість користувачів
     */
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = "role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(email LIKE :search OR first_name LIKE :search2 OR last_name LIKE :search3)";
            $searchValue = '%' . $filters['search'] . '%';
            $params['search'] = $searchValue;
            $params['search2'] = $searchValue;
            $params['search3'] = $searchValue;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) FROM users {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Перевірити, чи існує email
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Отримати групи вчителя
     */
    public function getTeacherGroups(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT g.* 
            FROM groups g
            INNER JOIN teacher_groups tg ON g.id = tg.group_id
            WHERE tg.teacher_id = :teacher_id
            ORDER BY g.name ASC
        ");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetchAll();
    }

    /**
     * Призначити групи вчителю
     */
    public function assignGroupsToTeacher(int $teacherId, array $groupIds): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM teacher_groups WHERE teacher_id = :teacher_id");
            $stmt->execute(['teacher_id' => $teacherId]);

            if (!empty($groupIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO teacher_groups (teacher_id, group_id, created_at)
                    VALUES (:teacher_id, :group_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()
                ");
                foreach ($groupIds as $groupId) {
                    $stmt->execute([
                        'teacher_id' => $teacherId,
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
     * Отримати предмети вчителя
     */
    public function getTeacherSubjects(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT s.* 
            FROM subjects s
            INNER JOIN teacher_subjects ts ON s.id = ts.subject_id
            WHERE ts.teacher_id = :teacher_id
            ORDER BY s.name ASC
        ");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetchAll();
    }

    /**
     * Призначити предмети вчителю
     */
    public function assignSubjectsToTeacher(int $teacherId, array $subjectIds): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM teacher_subjects WHERE teacher_id = :teacher_id");
            $stmt->execute(['teacher_id' => $teacherId]);

            if (!empty($subjectIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO teacher_subjects (teacher_id, subject_id, created_at)
                    VALUES (:teacher_id, :subject_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()
                ");
                foreach ($subjectIds as $subjectId) {
                    $stmt->execute([
                        'teacher_id' => $teacherId,
                        'subject_id' => (int)$subjectId
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
}
