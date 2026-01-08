<?php
/**
 * Модель тесту
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Test
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Знайти тест за ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, s.name as subject_name, u.first_name, u.last_name, u.email as creator_email
            FROM tests t
            LEFT JOIN subjects s ON t.subject_id = s.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Створити тест
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO tests (title, description, subject_id, duration, max_attempts, 
                             passing_score, start_date, end_date, is_published, created_by, created_at)
            VALUES (:title, :description, :subject_id, :duration, :max_attempts, 
                   :passing_score, :start_date, :end_date, :is_published, :created_by, NOW())
        ");

        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'duration' => $data['duration'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? 1,
            'passing_score' => $data['passing_score'] ?? 60,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_published' => $data['is_published'] ?? 0,
            'created_by' => $data['created_by']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Оновити тест
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['title', 'description', 'subject_id', 'duration', 'max_attempts', 
                         'passing_score', 'start_date', 'end_date', 'is_published', 'created_by'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE tests SET " . implode(', ', $updates) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Видалити тест
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tests WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Отримати всі тести з фільтрами
     */
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['subject_id'])) {
            $where[] = "t.subject_id = :subject_id";
            $params['subject_id'] = $filters['subject_id'];
        }

        if (!empty($filters['created_by'])) {
            $where[] = "t.created_by = :created_by";
            $params['created_by'] = $filters['created_by'];
        }

        if (isset($filters['is_published'])) {
            $where[] = "t.is_published = :is_published";
            $params['is_published'] = $filters['is_published'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT t.*, s.name as subject_name, u.first_name, u.last_name
                FROM tests t
                LEFT JOIN subjects s ON t.subject_id = s.id
                LEFT JOIN users u ON t.created_by = u.id
                {$whereClause}
                ORDER BY t.created_at DESC 
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
     * Підрахувати кількість тестів
     */
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['subject_id'])) {
            $where[] = "t.subject_id = :subject_id";
            $params['subject_id'] = $filters['subject_id'];
        }

        if (!empty($filters['created_by'])) {
            $where[] = "t.created_by = :created_by";
            $params['created_by'] = $filters['created_by'];
        }

        if (isset($filters['is_published'])) {
            $where[] = "t.is_published = :is_published";
            $params['is_published'] = $filters['is_published'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) FROM tests t {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Отримати доступні тести для студента
     */
    public function getAvailableForStudent(int $studentId, int $page = 1, int $perPage = 9): array
    {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT DISTINCT t.*, s.name as subject_name
            FROM tests t
            LEFT JOIN subjects s ON t.subject_id = s.id
            LEFT JOIN test_assignments ta ON t.id = ta.test_id
            LEFT JOIN users u ON u.group_id = ta.group_id
            WHERE t.is_published = 1
            AND (ta.user_id = :student_id OR u.id = :student_id2)
            AND (t.start_date IS NULL OR t.start_date <= NOW())
            AND (t.end_date IS NULL OR t.end_date >= NOW())
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id2', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Підрахувати доступні тести для студента
     */
    public function countAvailableForStudent(int $studentId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT t.id)
            FROM tests t
            LEFT JOIN test_assignments ta ON t.id = ta.test_id
            LEFT JOIN users u ON u.group_id = ta.group_id
            WHERE t.is_published = 1
            AND (ta.user_id = :student_id OR u.id = :student_id2)
            AND (t.start_date IS NULL OR t.start_date <= NOW())
            AND (t.end_date IS NULL OR t.end_date >= NOW())
        ");
        
        $stmt->execute([
            'student_id' => $studentId,
            'student_id2' => $studentId
        ]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Перевірити, чи може студент пройти тест
     */
    public function canStudentTakeTest(int $testId, int $studentId): array
    {
        $test = $this->findById($testId);
        
        if (!$test) {
            return ['allowed' => false, 'reason' => 'Тест не знайдено'];
        }

        if (!$test['is_published']) {
            return ['allowed' => false, 'reason' => 'Тест не опубліковано'];
        }

        if ($test['start_date'] && strtotime($test['start_date']) > time()) {
            return ['allowed' => false, 'reason' => 'Тест ще не почався'];
        }

        if ($test['end_date'] && strtotime($test['end_date']) < time()) {
            return ['allowed' => false, 'reason' => 'Тест вже завершено'];
        }

        $attemptModel = new Attempt();
        $attemptsCount = $attemptModel->countByTestAndUser($testId, $studentId);
        
        if ($attemptsCount >= (int)$test['max_attempts']) {
            return ['allowed' => false, 'reason' => 'Перевищено максимальну кількість спроб'];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM test_assignments
            WHERE test_id = :test_id
            AND (user_id = :user_id OR group_id IN (SELECT group_id FROM users WHERE id = :user_id2))
        ");
        $stmt->execute(['test_id' => $testId, 'user_id' => $studentId, 'user_id2' => $studentId]);
        
        if ((int)$stmt->fetchColumn() === 0) {
            return ['allowed' => false, 'reason' => 'Тест не призначено вам'];
        }

        return ['allowed' => true];
    }
}
