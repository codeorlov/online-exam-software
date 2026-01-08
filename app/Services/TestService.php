<?php
/**
 * Сервіс для роботи з тестами та оцінюванням
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Test;
use App\Models\Question;
use App\Models\Attempt;

class TestService
{
    private Test $testModel;
    private Question $questionModel;
    private Attempt $attemptModel;

    public function __construct()
    {
        $this->testModel = new Test();
        $this->questionModel = new Question();
        $this->attemptModel = new Attempt();
    }

    /**
     * Оцінити відповіді спроби
     */
    public function gradeAttempt(int $attemptId): array
    {
        $attempt = $this->attemptModel->findById($attemptId);
        if (!$attempt) {
            throw new \RuntimeException('Спробу не знайдено');
        }

        $test = $this->testModel->findById((int)$attempt['test_id']);
        if (!$test) {
            throw new \RuntimeException('Тест не знайдено');
        }

        $questions = $this->questionModel->getByTestIdWithOptions((int)$test['id']);
        $answers = $this->attemptModel->getAnswers($attemptId);
        
        $answersMap = [];
        foreach ($answers as $answer) {
            $decoded = json_decode($answer['answer_data'], true);
            $answersMap[(int)$answer['question_id']] = ($decoded !== null || $answer['answer_data'] === 'null') 
                ? $decoded 
                : $answer['answer_data'];
        }

        $totalScore = 0;
        $maxScore = 0;
        $results = [];

        foreach ($questions as $question) {
            $maxScore += (float)$question['points'];
            $userAnswer = $answersMap[(int)$question['id']] ?? null;
            $isCorrect = $this->checkAnswer($question, $userAnswer);
            
            if ($isCorrect) {
                $totalScore += (float)$question['points'];
            }

            $results[] = [
                'question_id' => (int)$question['id'],
                'question_text' => $question['question_text'],
                'user_answer' => $userAnswer,
                'correct' => $isCorrect,
                'points' => $isCorrect ? (float)$question['points'] : 0,
                'max_points' => (float)$question['points']
            ];
        }

        $attempt = $this->attemptModel->findById($attemptId);
        $currentStatus = $attempt['status'] ?? 'completed';
        
        $completedAt = date('Y-m-d H:i:s');
        if ($currentStatus === 'in_progress' && $test['duration'] && $attempt['started_at']) {
            $startedAt = strtotime($attempt['started_at']);
            $durationSeconds = (int)$test['duration'] * 60;
            $expiredAt = $startedAt + $durationSeconds;
            $currentTime = time();
            
            if ($currentTime > $expiredAt) {
                $completedAt = date('Y-m-d H:i:s', $expiredAt);
            }
        }
        
        if ($currentStatus === 'in_progress') {
            $this->attemptModel->update($attemptId, [
                'score' => $totalScore,
                'max_score' => $maxScore,
                'status' => 'completed',
                'completed_at' => $completedAt
            ]);
        } else {
            $this->attemptModel->update($attemptId, [
                'score' => $totalScore,
                'max_score' => $maxScore
            ]);
        }

        return [
            'score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0,
            'passed' => ($totalScore / $maxScore * 100) >= (float)$test['passing_score'],
            'passing_score' => (float)$test['passing_score'],
            'results' => $results
        ];
    }

    /**
     * Перерахувати всі завершені спроби для тесту
     * Використовується при зміні балів питань
     */
    public function recalculateTestAttempts(int $testId): int
    {
        $attemptModel = new Attempt();
        $test = $this->testModel->findById($testId);
        
        if (!$test) {
            return 0;
        }

        $attempts = $attemptModel->getByTestId($testId, 1, 10000, ['status' => 'completed']);
        
        $recalculated = 0;
        foreach ($attempts as $attempt) {
            try {
                $this->gradeAttempt((int)$attempt['id']);
                $recalculated++;
            } catch (\Exception $e) {
            }
        }
        
        return $recalculated;
    }

    /**
     * Перевірити правильність відповіді
     */
    public function checkAnswer(array $question, mixed $userAnswer): bool
    {
        if ($userAnswer === null || $userAnswer === '' || (is_array($userAnswer) && empty($userAnswer))) {
            return false;
        }
        
        if (is_string($userAnswer) && trim($userAnswer) === '') {
            return false;
        }

        $type = $question['question_type'];
        $options = $question['options'] ?? [];

        switch ($type) {
            case 'single_choice':
                $correctOptionId = null;
                foreach ($options as $option) {
                    if ((int)$option['is_correct'] === 1) {
                        $correctOptionId = (int)$option['id'];
                        break;
                    }
                }
                
                if ($correctOptionId === null) {
                    return false;
                }
                
                $userAnswerId = null;
                $savedText = null;
                if (is_array($userAnswer) && isset($userAnswer['id'])) {
                    $userAnswerId = (int)$userAnswer['id'];
                    $savedText = $userAnswer['text'] ?? null;
                } else {
                    $userAnswerId = is_numeric($userAnswer) ? (int)$userAnswer : null;
                }
                
                if ($userAnswerId === $correctOptionId) {
                    return true;
                }
                
                if ($savedText !== null && $savedText !== '') {
                    $savedTextNormalized = mb_strtolower(trim($savedText));
                    foreach ($options as $option) {
                        $optionTextNormalized = mb_strtolower(trim($option['option_text']));
                        if ($optionTextNormalized === $savedTextNormalized) {
                            return (int)$option['id'] === $correctOptionId;
                        }
                    }
                }
                
                return false;

            case 'multiple_choice':
                $correctIds = [];
                foreach ($options as $option) {
                    if ((int)$option['is_correct'] === 1) {
                        $correctIds[] = (int)$option['id'];
                    }
                }
                
                if (!is_array($userAnswer)) {
                    if (is_string($userAnswer)) {
                        $userAnswer = json_decode($userAnswer, true) ?? [$userAnswer];
                    } else {
                        $userAnswer = [$userAnswer];
                    }
                }
                
                $userAnswersData = [];
                foreach ($userAnswer as $val) {
                    if (is_array($val) && isset($val['id'])) {
                        $userAnswersData[] = [
                            'id' => (int)$val['id'],
                            'text' => $val['text'] ?? null
                        ];
                    } elseif (is_numeric($val)) {
                        $userAnswersData[] = [
                            'id' => (int)$val,
                            'text' => null
                        ];
                    }
                }
                
                if (empty($userAnswersData) || count($userAnswersData) !== count($correctIds)) {
                    return false;
                }
                
                $userIds = [];
                foreach ($userAnswersData as $answerData) {
                    $userAnswerId = $answerData['id'];
                    $savedText = $answerData['text'];
                    
                    $foundById = false;
                    foreach ($options as $option) {
                        if ((int)$option['id'] === $userAnswerId) {
                            $userIds[] = $userAnswerId;
                            $foundById = true;
                            break;
                        }
                    }
                    
                    if (!$foundById && $savedText !== null && $savedText !== '') {
                        $savedTextNormalized = mb_strtolower(trim($savedText));
                        foreach ($options as $option) {
                            $optionTextNormalized = mb_strtolower(trim($option['option_text']));
                            if ($optionTextNormalized === $savedTextNormalized) {
                                $userIds[] = (int)$option['id'];
                                break;
                            }
                        }
                    }
                }
                
                if (empty($userIds) || count($userIds) !== count($correctIds)) {
                    return false;
                }
                
                sort($correctIds);
                sort($userIds);
                
                return $correctIds === $userIds;

            case 'true_false':
                $correctAnswer = null;
                foreach ($options as $option) {
                    if ((int)$option['is_correct'] === 1) {
                        $correctAnswer = $option['option_text'];
                        break;
                    }
                }
                
                if ($correctAnswer === null) {
                    return false;
                }
                
                $normalizedUserAnswer = $userAnswer;
                if ($userAnswer === true || $userAnswer === 'true' || $userAnswer === '1' || $userAnswer === 1) {
                    $normalizedUserAnswer = 'true';
                } elseif ($userAnswer === false || $userAnswer === 'false' || $userAnswer === '0' || $userAnswer === 0) {
                    $normalizedUserAnswer = 'false';
                } else {
                    $normalizedUserAnswer = (string)$userAnswer;
                }
                
                $normalizedCorrectAnswer = $correctAnswer;
                if ($correctAnswer === true || $correctAnswer === 'true' || $correctAnswer === '1' || $correctAnswer === 1) {
                    $normalizedCorrectAnswer = 'true';
                } elseif ($correctAnswer === false || $correctAnswer === 'false' || $correctAnswer === '0' || $correctAnswer === 0) {
                    $normalizedCorrectAnswer = 'false';
                } else {
                    $normalizedCorrectAnswer = (string)$correctAnswer;
                }
                
                return $normalizedUserAnswer === $normalizedCorrectAnswer;

            case 'short_answer':
                if (is_numeric($userAnswer)) {
                    $userAnswer = (string)$userAnswer;
                } elseif (!is_string($userAnswer)) {
                    return false;
                }
                
                $userAnswerNormalized = trim($userAnswer);
                
                foreach ($options as $option) {
                    if ((int)$option['is_correct'] === 1) {
                        $correctAnswer = trim($option['option_text'] ?? '');
                        
                        if (mb_strtolower($userAnswerNormalized) === mb_strtolower($correctAnswer)) {
                            return true;
                        }
                        
                        if (is_numeric($userAnswerNormalized) && is_numeric($correctAnswer)) {
                            if ((float)$userAnswerNormalized === (float)$correctAnswer) {
                                return true;
                            }
                        }
                    }
                }
                return false;

            default:
                return false;
        }
    }
}
