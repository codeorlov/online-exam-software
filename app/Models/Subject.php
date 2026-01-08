<?php
/**
 * Модель предмета
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Subject
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Знайти предмет за ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, 
            MIN(u.first_name) as first_name, 
            MIN(u.last_name) as last_name, 
            MIN(u.email) as email,
            COUNT(DISTINCT ts.teacher_id) as teacher_count
            FROM subjects s 
            LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id
            LEFT JOIN users u ON ts.teacher_id = u.id 
            WHERE s.id = :id
            GROUP BY s.id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Створити предмет
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO subjects (name, description, teacher_id, created_at)
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
     * Оновити предмет
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
        $sql = "UPDATE subjects SET " . implode(', ', $updates) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Видалити предмет
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM subjects WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Отримати всі предмети
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
                $where[] = "NOT EXISTS (SELECT 1 FROM teacher_subjects ts2 WHERE ts2.subject_id = s.id)";
            } else {
                $joinClause = "INNER JOIN teacher_subjects ts_filter ON s.id = ts_filter.subject_id AND ts_filter.teacher_id = :teacher_id";
                $params['teacher_id'] = $filters['teacher_id'];
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT s.*, 
                MIN(u.first_name) as first_name, 
                MIN(u.last_name) as last_name, 
                MIN(u.email) as email,
                COUNT(DISTINCT ts.teacher_id) as teacher_count
                FROM subjects s 
                {$joinClause}
                LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id
                LEFT JOIN users u ON ts.teacher_id = u.id 
                {$whereClause} 
                GROUP BY s.id
                ORDER BY s.name ASC 
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
     * Підрахувати кількість предметів
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
        $sql = "SELECT COUNT(*) FROM subjects {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Синхронізувати призначення предмета вчителям
     */
    public function syncTeacherAssignment(int $subjectId, array $teacherIds): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM teacher_subjects WHERE subject_id = :subject_id");
            $stmt->execute(['subject_id' => $subjectId]);

            if (!empty($teacherIds)) {
                $stmt = $this->db->prepare("
                    INSERT INTO teacher_subjects (teacher_id, subject_id, created_at)
                    VALUES (:teacher_id, :subject_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()
                ");
                foreach ($teacherIds as $teacherId) {
                    if ($teacherId !== null && $teacherId !== '') {
                        $stmt->execute([
                            'teacher_id' => (int)$teacherId,
                            'subject_id' => $subjectId
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
