<?php
/**
 * Модель спроби проходження тесту
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Attempt
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Знайти спробу за ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, t.title as test_title, u.first_name, u.last_name, u.email
            FROM attempts a
            LEFT JOIN tests t ON a.test_id = t.id
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Створити нову спробу
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO attempts (test_id, user_id, started_at, status)
            VALUES (:test_id, :user_id, NOW(), 'in_progress')
        ");

        $stmt->execute([
            'test_id' => $data['test_id'],
            'user_id' => $data['user_id']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Перевірити, чи вийшов час на тест
     */
    public function isTimeExpired(int $attemptId, int $testDurationMinutes): bool
    {
        if (!$testDurationMinutes) {
            return false;
        }

        $attempt = $this->findById($attemptId);
        if (!$attempt || !$attempt['started_at']) {
            return false;
        }

        $startedAt = strtotime($attempt['started_at']);
        $durationSeconds = $testDurationMinutes * 60;
        $endTime = $startedAt + $durationSeconds;
        $currentTime = time();

        return ($currentTime + 1) >= $endTime;
    }

    /**
     * Отримати залишковий час на тест у секундах
     */
    public function getTimeLeft(int $attemptId, int $testDurationMinutes): ?int
    {
        if (!$testDurationMinutes) {
            return null;
        }

        $attempt = $this->findById($attemptId);
        if (!$attempt || !$attempt['started_at']) {
            return null;
        }

        $startedAt = strtotime($attempt['started_at']);
        $durationSeconds = $testDurationMinutes * 60;
        $endTime = $startedAt + $durationSeconds;
        $currentTime = time();
        $timeLeft = $endTime - $currentTime;

        return max(0, $timeLeft);
    }

    /**
     * Завершити спробу
     */
    public function complete(int $id, float $score, float $maxScore): bool
    {
        $stmt = $this->db->prepare("
            UPDATE attempts 
            SET status = 'completed', 
                score = :score, 
                max_score = :max_score,
                completed_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'score' => $score,
            'max_score' => $maxScore
        ]);
    }

    /**
     * Зберегти відповідь на питання
     */
    public function saveAnswer(int $attemptId, int $questionId, mixed $answer, ?array $questionWithOptions = null): bool
    {
        $stmt = $this->db->prepare("DELETE FROM attempt_answers WHERE attempt_id = :attempt_id AND question_id = :question_id");
        $stmt->execute(['attempt_id' => $attemptId, 'question_id' => $questionId]);

        $stmt = $this->db->prepare("
            INSERT INTO attempt_answers (attempt_id, question_id, answer_data, created_at)
            VALUES (:attempt_id, :question_id, :answer_data, NOW())
        ");

        $answerData = '';
        if ($answer !== null) {
            $questionType = $questionWithOptions['question_type'] ?? null;
            
            if ($questionType === 'single_choice' && is_numeric($answer)) {
                $optionId = (int)$answer;
                $optionText = null;
                if ($questionWithOptions && !empty($questionWithOptions['options'])) {
                    foreach ($questionWithOptions['options'] as $option) {
                        if ((int)$option['id'] === $optionId) {
                            $optionText = $option['option_text'];
                            break;
                        }
                    }
                }
                $answerData = json_encode(['id' => $optionId, 'text' => $optionText]);
            } elseif ($questionType === 'multiple_choice' && is_array($answer) && !empty($answer)) {
                $optionsData = [];
                if ($questionWithOptions && !empty($questionWithOptions['options'])) {
                    foreach ($answer as $optionId) {
                        $optionId = (int)$optionId;
                        $optionText = null;
                        foreach ($questionWithOptions['options'] as $option) {
                            if ((int)$option['id'] === $optionId) {
                                $optionText = $option['option_text'];
                                break;
                            }
                        }
                        $optionsData[] = ['id' => $optionId, 'text' => $optionText];
                    }
                } else {
                    foreach ($answer as $optionId) {
                        $optionsData[] = ['id' => (int)$optionId, 'text' => null];
                    }
                }
                $answerData = json_encode($optionsData);
            } elseif (is_array($answer)) {
                $answerData = !empty($answer) ? json_encode($answer) : '';
            } else {
                $answerData = (string)$answer;
            }
        }
        
        return $stmt->execute([
            'attempt_id' => $attemptId,
            'question_id' => $questionId,
            'answer_data' => $answerData
        ]);
    }

    /**
     * Отримати відповіді спроби
     */
    public function getAnswers(int $attemptId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM attempt_answers 
            WHERE attempt_id = :attempt_id
        ");
        $stmt->execute(['attempt_id' => $attemptId]);
        return $stmt->fetchAll();
    }

    /**
     * Отримати спроби користувача
     */
    public function getByUserId(int $userId, int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['a.user_id = :user_id'];
        $params = ['user_id' => $userId];

        if (!empty($filters['status'])) {
            $where[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['test_id'])) {
            $where[] = 'a.test_id = :test_id';
            $params['test_id'] = $filters['test_id'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT a.*, t.title as test_title, t.passing_score
            FROM attempts a
            LEFT JOIN tests t ON a.test_id = t.id
            {$whereClause}
            ORDER BY a.started_at DESC
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
     * Підрахувати кількість спроб користувача
     */
    public function countByUserId(int $userId, array $filters = []): int
    {
        $where = ['a.user_id = :user_id'];
        $params = ['user_id' => $userId];

        if (!empty($filters['status'])) {
            $where[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['test_id'])) {
            $where[] = 'a.test_id = :test_id';
            $params['test_id'] = $filters['test_id'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM attempts a
            {$whereClause}
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Отримати спроби за тестом
     */
    public function getByTestId(int $testId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['a.test_id = :test_id'];
        $params = ['test_id' => $testId];

        if (!empty($filters['status'])) {
            $where[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email
            FROM attempts a
            LEFT JOIN users u ON a.user_id = u.id
            {$whereClause}
            ORDER BY a.started_at DESC
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
     * Підрахувати кількість спроб за тестом
     */
    public function countByTestId(int $testId, array $filters = []): int
    {
        $where = ['a.test_id = :test_id'];
        $params = ['test_id' => $testId];

        if (!empty($filters['status'])) {
            $where[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM attempts a
            {$whereClause}
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Підрахувати кількість спроб за тестом та користувачем
     */
    public function countByTestAndUser(int $testId, int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM attempts 
            WHERE test_id = :test_id AND user_id = :user_id
        ");
        $stmt->execute(['test_id' => $testId, 'user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Отримати активну спробу користувача для тесту
     */
    public function getActiveAttempt(int $testId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM attempts 
            WHERE test_id = :test_id 
            AND user_id = :user_id 
            AND status = 'in_progress'
            ORDER BY started_at DESC
            LIMIT 1
        ");
        $stmt->execute(['test_id' => $testId, 'user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Оновити спробу
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['status', 'score', 'max_score', 'completed_at', 'started_at'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'completed_at' || $field === 'started_at') {
                    if ($data[$field] === null || $data[$field] === '') {
                        $updates[] = "{$field} = NULL";
                    } else {
                        $updates[] = "{$field} = :{$field}";
                        $params[$field] = $data[$field];
                    }
                } else {
                    $updates[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE attempts SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Видалити спробу
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM attempts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
