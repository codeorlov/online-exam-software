<?php
/**
 * Модель групи/класу
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Group
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Знайти групу за ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT g.*, 
            MIN(u.first_name) as first_name, 
            MIN(u.last_name) as last_name, 
            MIN(u.email) as email,
            COUNT(DISTINCT tg.teacher_id) as teacher_count
            FROM groups g 
            LEFT JOIN teacher_groups tg ON g.id = tg.group_id
            LEFT JOIN users u ON tg.teacher_id = u.id 
            WHERE g.id = :id
            GROUP BY g.id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Створити групу
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO groups (name, description, teacher_id, created_at)
            VALUES (:name, :description, :teacher_id, NOW())
        ");

        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Оновити групу
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name', 'description', 'teacher_id'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE groups SET " . implode(', ', $updates) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Видалити групу
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM groups WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Отримати всі групи
     */
    public function getAll(int $page = 1, int $perPage = 9, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE :search OR description LIKE :search2)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $joinClause = '';
        if (isset($filters['teacher_id'])) {
            if ($filters['teacher_id'] === null) {
                $where[] = "NOT EXISTS (SELECT 1 FROM teacher_groups tg2 WHERE tg2.group_id = g.id)";
            } else {
                $joinClause = "INNER JOIN teacher_groups tg_filter ON g.id = tg_filter.group_id AND tg_filter.teacher_id = :teacher_id";
                $params['teacher_id'] = $filters['teacher_id'];
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT g.*, 
                MIN(u.first_name) as first_name, 
                MIN(u.last_name) as last_name, 
                MIN(u.email) as email,
                COUNT(DISTINCT tg.teacher_id) as teacher_count
                FROM groups g 
                {$joinClause}
                LEFT JOIN teacher_groups tg ON g.id = tg.group_id
                LEFT JOIN users u ON tg.teacher_id = u.id 
                {$whereClause} 
                GROUP BY g.id
                ORDER BY g.name ASC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Підрахувати кількість груп
     */
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE :search OR description LIKE :search2)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['teacher_id'])) {
            if ($filters['teacher_id'] === null) {
                $where[] = "teacher_id IS NULL";
            } else {
                $where[] = "teacher_id = :teacher_id";
                $params['teacher_id'] = $filters['teacher_id'];
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM groups {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Отримати студентів групи
     */
    public function getStudents(int $groupId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE group_id = :group_id AND role = 'student'
            ORDER BY last_name, first_name
        ");
        $stmt->execute(['group_id' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Синхронізувати призначення групи вчителям
     */
    public function syncTeacherAssignment(int $groupId, array $teacherIds): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM teacher_groups WHERE group_id = :group_id");
            $stmt->execute(['group_id' => $groupId]);

            if (!empty($teacherIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO teacher_groups (teacher_id, group_id, created_at)
                    VALUES (:teacher_id, :group_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()
                ");
                foreach ($teacherIds as $teacherId) {
                    if ($teacherId !== null && $teacherId !== '') {
                        $stmt->execute([
                            'teacher_id' => (int)$teacherId,
                            'group_id' => $groupId
                        ]);
                    }
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
