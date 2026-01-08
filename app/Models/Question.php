<?php
/**
 * Модель питання
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Question
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Знайти питання за ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM questions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Створити питання
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO questions (test_id, question_text, question_type, points, order_index, created_at)
            VALUES (:test_id, :question_text, :question_type, :points, :order_index, NOW())
        ");

        $stmt->execute([
            'test_id' => $data['test_id'],
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'points' => $data['points'] ?? 1,
            'order_index' => $data['order_index'] ?? 0
        ]);

        $questionId = (int)$this->db->lastInsertId();

        if (!empty($data['options'])) {
            $this->createOptions($questionId, $data['options']);
        }

        return $questionId;
    }

    /**
     * Створити варіанти відповідей для питання
     */
    private function createOptions(int $questionId, array $options): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO question_options (question_id, option_text, is_correct, order_index)
            VALUES (:question_id, :option_text, :is_correct, :order_index)
        ");

        foreach ($options as $index => $option) {
            $optionText = trim($option['text'] ?? '');
            if ($optionText === '') {
                continue;
            }
            $stmt->execute([
                'question_id' => $questionId,
                'option_text' => $optionText,
                'is_correct' => $option['is_correct'] ? 1 : 0,
                'order_index' => $index
            ]);
        }
    }

    /**
     * Оновити питання
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['question_text', 'question_type', 'points', 'order_index'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            if (!empty($data['options'])) {
                $this->updateOptions($id, $data['options']);
                return true;
            }
            return false;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE questions SET " . implode(', ', $updates) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        if ($result && !empty($data['options'])) {
            $this->updateOptions($id, $data['options']);
        }

        return $result;
    }

    /**
     * Оновити варіанти відповідей
     */
    private function updateOptions(int $questionId, array $options): void
    {
        $stmt = $this->db->prepare("DELETE FROM question_options WHERE question_id = :question_id");
        $stmt->execute(['question_id' => $questionId]);

        $this->createOptions($questionId, $options);
    }

    /**
     * Видалити питання
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM questions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Отримати всі питання тесту
     */
    public function getByTestId(int $testId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM questions 
            WHERE test_id = :test_id 
            ORDER BY order_index ASC, id ASC
        ");
        $stmt->execute(['test_id' => $testId]);
        return $stmt->fetchAll();
    }

    /**
     * Отримати питання з варіантами відповідей
     */
    public function findWithOptions(int $id): ?array
    {
        $question = $this->findById($id);
        if (!$question) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM question_options 
            WHERE question_id = :question_id 
            ORDER BY order_index ASC
        ");
        $stmt->execute(['question_id' => $id]);
        $options = $stmt->fetchAll();
        foreach ($options as &$option) {
            $option['is_correct'] = (bool)$option['is_correct'];
        }
        $question['options'] = $options;

        return $question;
    }

    /**
     * Отримати всі питання тесту з варіантами відповідей
     */
    public function getByTestIdWithOptions(int $testId, int $page = 1, int $perPage = 10, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['test_id = :test_id'];
        $params = ['test_id' => $testId];
        
        if (!empty($search)) {
            $where[] = '(question_text LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT * FROM questions 
            {$whereClause}
            ORDER BY order_index ASC, id ASC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $questions = $stmt->fetchAll();
        
        foreach ($questions as &$question) {
            $stmt = $this->db->prepare("
                SELECT * FROM question_options 
                WHERE question_id = :question_id 
                ORDER BY order_index ASC
            ");
            $stmt->execute(['question_id' => $question['id']]);
            $options = $stmt->fetchAll();
            foreach ($options as &$option) {
                $option['is_correct'] = (bool)$option['is_correct'];
            }
            $question['options'] = $options;
        }

        return $questions;
    }

    /**
     * Підрахувати кількість питань тесту
     */
    public function countByTestId(int $testId, ?string $search = null): int
    {
        $where = ['test_id = :test_id'];
        $params = ['test_id' => $testId];
        
        if (!empty($search)) {
            $where[] = '(question_text LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM questions {$whereClause}");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
