<?php
/**
 * Контролер для роботи з тестами
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Security;
use App\Core\Logger;
use App\Models\Test;
use App\Models\Question;
use App\Models\Attempt;
use App\Models\User;
use App\Services\TestService;
use App\Services\EmailService;

class TestController extends Controller
{
    private Test $testModel;
    private Question $questionModel;
    private Attempt $attemptModel;
    private TestService $testService;

    public function __construct()
    {
        parent::__construct();
        $this->testModel = new Test();
        $this->questionModel = new Question();
        $this->attemptModel = new Attempt();
        $this->testService = new TestService();
    }

    /**
     * Почати проходження тесту
     */
    public function start(int $testId): void
    {
        $this->requireAuth();
        
        $userId = Session::get('user_id');
        $canTake = $this->testModel->canStudentTakeTest($testId, $userId);

        if (!$canTake['allowed']) {
            $this->setFlash('error', $canTake['reason']);
            $this->redirect('/dashboard');
        }

        $activeAttempt = $this->attemptModel->getActiveAttempt($testId, $userId);
        
        if ($activeAttempt) {
            $test = $this->testModel->findById($testId);
            
            if ($test['duration'] && $activeAttempt['started_at']) {
                $startedAt = strtotime($activeAttempt['started_at']);
                $currentTime = time();
                $elapsed = $currentTime - $startedAt;
                
                if ($elapsed > 5 && $this->attemptModel->isTimeExpired($activeAttempt['id'], (int)$test['duration'])) {
                    try {
                        $this->testService->gradeAttempt($activeAttempt['id']);
                    } catch (\Exception $e) {
                    }
                } else {
                    $this->redirect('/test/' . $testId . '/take/' . $activeAttempt['id']);
                }
            } else {
                    $this->redirect('/test/' . $testId . '/take/' . $activeAttempt['id']);
            }
        }

        $attemptId = $this->attemptModel->create([
            'test_id' => $testId,
            'user_id' => $userId
        ]);

        Logger::audit('test_started', $userId, ['test_id' => $testId, 'attempt_id' => $attemptId]);

        $this->redirect('/test/' . $testId . '/take/' . $attemptId);
    }

    /**
     * Проходження тесту
     */
    public function take(int $testId, int $attemptId): void
    {
        $this->requireAuth();
        
        $userId = Session::get('user_id');
        $attempt = $this->attemptModel->findById($attemptId);

        if (!$attempt || (int)$attempt['user_id'] !== $userId || (int)$attempt['test_id'] !== $testId) {
            $this->setFlash('error', 'Спроба не знайдена');
            $this->redirect('/dashboard');
        }

        if ($attempt['status'] !== 'in_progress') {
            $this->redirect('/test/' . $testId . '/result/' . $attemptId);
        }

        $test = $this->testModel->findById($testId);
        
        if ($test['duration'] && $attempt['started_at']) {
            $startedAt = strtotime($attempt['started_at']);
            $currentTime = time();
            $elapsed = $currentTime - $startedAt;
            
            if ($elapsed > 5 && $this->attemptModel->isTimeExpired($attemptId, (int)$test['duration'])) {
                $this->complete($testId, $attemptId);
                return;
            }
        }

        $questions = $this->questionModel->getByTestIdWithOptions($testId, 1, 10000);
        $answers = $this->attemptModel->getAnswers($attemptId);

        $answersMap = [];
        foreach ($answers as $answer) {
            $answersMap[(int)$answer['question_id']] = json_decode($answer['answer_data'], true) ?? $answer['answer_data'];
        }

        $currentQuestionIndex = (int)($_GET['q'] ?? 0);
        $currentQuestion = $questions[$currentQuestionIndex] ?? null;

        if (!$currentQuestion) {
            $this->complete($testId, $attemptId);
            return;
        }

        $timeLeft = null;
        $endTime = null;
        if ($test['duration'] && $attempt['started_at']) {
            $startedAt = strtotime($attempt['started_at']);
            $durationSeconds = (int)$test['duration'] * 60;
            $endTime = $startedAt + $durationSeconds;
            $currentTime = time();
            $timeLeft = max(0, $endTime - $currentTime);
        }

        $userAttemptsCount = $this->attemptModel->countByTestAndUser($testId, $userId);
        $maxAttempts = (int)($test['max_attempts'] ?? 0);
        $remainingAttempts = $maxAttempts > 0 ? max(0, $maxAttempts - $userAttemptsCount) : null;

        $this->view->set('title', 'Проходження тесту: ' . $test['title']);
        $this->view->set('test', $test);
        $this->view->set('questions', $questions);
        $this->view->set('currentQuestionIndex', $currentQuestionIndex);
        $this->view->set('currentQuestion', $currentQuestion);
        $this->view->set('answers', $answersMap);
        $this->view->set('attemptId', $attemptId);
        $this->view->set('attempt', $attempt);
        $this->view->set('timeLeft', $timeLeft);
        $this->view->set('endTime', $endTime);
        $this->view->set('remainingAttempts', $remainingAttempts);
        $this->view->set('maxAttempts', $maxAttempts);
        $this->view->set('userAttemptsCount', $userAttemptsCount);
        $this->view->render('test/take');
    }

    /**
     * Зберегти відповідь
     */
    public function saveAnswer(int $testId, int $attemptId): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Метод не дозволено'], 405);
        }

        $token = Security::getCsrfTokenFromRequest();
        if (!$token || !Security::validateCsrfToken($token)) {
            $this->json(['success' => false, 'message' => 'Помилка безпеки'], 403);
        }

        $userId = Session::get('user_id');
        $attempt = $this->attemptModel->findById($attemptId);

        if (!$attempt || (int)$attempt['user_id'] !== $userId || (int)$attempt['test_id'] !== $testId) {
            $this->json(['success' => false, 'message' => 'Спробу не знайдено'], 404);
        }

        if ($attempt['status'] !== 'in_progress') {
            $this->json(['success' => false, 'message' => 'Тест вже завершено'], 400);
        }

        $test = $this->testModel->findById($testId);
        if ($test['duration'] && $this->attemptModel->isTimeExpired($attemptId, (int)$test['duration'])) {
            $this->complete($testId, $attemptId);
            $this->json(['success' => false, 'message' => 'Час на тест вийшов'], 400);
        }

        $questionId = (int)($_POST['question_id'] ?? 0);
        $answer = $_POST['answer'] ?? null;

        if (!$questionId) {
            $this->json(['success' => false, 'message' => 'Не вказано питання'], 400);
        }

        $question = $this->questionModel->findWithOptions($questionId);
        
        $this->attemptModel->saveAnswer($attemptId, $questionId, $answer, $question);
        $this->json(['success' => true]);
    }

    /**
     * Завершити тест
     */
    public function complete(int $testId, int $attemptId): void
    {
        $this->requireAuth();

        $userId = Session::get('user_id');
        $attempt = $this->attemptModel->findById($attemptId);

        if (!$attempt || (int)$attempt['user_id'] !== $userId || (int)$attempt['test_id'] !== $testId) {
            $this->setFlash('error', 'Спробу не знайдено');
            $this->redirect('/dashboard');
        }

        if ($attempt['status'] !== 'in_progress') {
            $this->redirect('/test/' . $testId . '/result/' . $attemptId);
        }

        $result = $this->testService->gradeAttempt($attemptId);

        Logger::audit('test_completed', $userId, [
            'test_id' => $testId,
            'attempt_id' => $attemptId,
            'score' => $result['score'],
            'max_score' => $result['max_score']
        ]);
        $userModel = new User();
        $user = $userModel->findById($userId);
        if ($user && (int)($user['email_notifications'] ?? 1) === 1) {
            $test = $this->testModel->findById($testId);
            if ($test) {
                $resultUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                    . '://' . $_SERVER['HTTP_HOST'] . '/test/' . $testId . '/result/' . $attemptId;
                
                $emailService = new EmailService();
                $emailSent = $emailService->sendTestCompletedNotification(
                    $user['email'],
                    $user['first_name'] . ' ' . $user['last_name'],
                    $test['title'] ?? 'Тест',
                    $resultUrl
                );
                
                if (!$emailSent) {
                    Logger::error('Failed to send test completion notification', [
                        'user_id' => $userId,
                        'test_id' => $testId,
                        'email' => $user['email']
                    ]);
                }
            }
        }

        $this->redirect('/test/' . $testId . '/result/' . $attemptId);
    }

    /**
     * Показати результат тесту
     */
    public function result(int $testId, int $attemptId): void
    {
        $this->requireAuth();

        $userId = Session::get('user_id');
        $userRole = Session::get('user_role');
        $attempt = $this->attemptModel->findById($attemptId);

        if (!$attempt || (int)$attempt['test_id'] !== $testId) {
            $this->setFlash('error', 'Спробу не знайдено');
            $this->redirect('/dashboard');
        }

        if ($userRole === 'student' && (int)$attempt['user_id'] !== $userId) {
            $this->setFlash('error', 'Доступ заборонено');
            $this->redirect('/dashboard');
        }

        $test = $this->testModel->findById($testId);
        $questions = $this->questionModel->getByTestIdWithOptions($testId, 1, 10000);
        $answers = $this->attemptModel->getAnswers($attemptId);

        $answersMap = [];
        foreach ($answers as $answer) {
            $answersMap[(int)$answer['question_id']] = json_decode($answer['answer_data'], true) ?? $answer['answer_data'];
        }

        $results = [];
        foreach ($questions as $question) {
            $userAnswer = $answersMap[(int)$question['id']] ?? null;
            $questionWithOptions = $this->questionModel->findWithOptions((int)$question['id']);
            if ($questionWithOptions) {
                $isCorrect = $this->testService->checkAnswer($questionWithOptions, $userAnswer);
            } else {
                $isCorrect = false;
            }
            
            $results[] = [
                'question' => $questionWithOptions ?: $question,
                'user_answer' => $userAnswer,
                'is_correct' => $isCorrect
            ];
        }

        $userRole = Session::get('user_role');
        $canEdit = ($userRole === 'admin' || $userRole === 'teacher');

        $this->view->set('title', 'Результат тесту: ' . $test['title']);
        $this->view->set('test', $test);
        $this->view->set('attempt', $attempt);
        $this->view->set('results', $results);
        $this->view->set('canEdit', $canEdit);
        $this->view->render('test/result');
    }

    /**
     * Редагувати результат тесту
     */
    public function editResult(int $testId, int $attemptId): void
    {
        $this->requireAnyRole(['admin', 'teacher']);

        $attempt = $this->attemptModel->findById($attemptId);

        if (!$attempt || (int)$attempt['test_id'] !== $testId) {
            $this->setFlash('error', 'Спробу не знайдено');
            $this->redirect('/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/test/' . $testId . '/result/' . $attemptId . '/edit');
            }

            $answersChanged = false;
            $recalculatedScore = null;
            $recalculatedMaxScore = null;
            
            $oldScore = (float)$attempt['score'];
            $oldMaxScore = (float)$attempt['max_score'];

            $currentAnswers = $this->attemptModel->getAnswers($attemptId);
            $currentAnswersMap = [];
            foreach ($currentAnswers as $answer) {
                $currentAnswersMap[(int)$answer['question_id']] = json_decode($answer['answer_data'], true) ?? $answer['answer_data'];
            }

            if (isset($_POST['answers']) && is_array($_POST['answers'])) {
                $test = $this->testModel->findById($testId);
                $questions = $this->questionModel->getByTestIdWithOptions($testId, 1, 10000);
                
                foreach ($_POST['answers'] as $questionId => $answerData) {
                    $questionId = (int)$questionId;
                    
                    $question = null;
                    foreach ($questions as $q) {
                        if ((int)$q['id'] === $questionId) {
                            $question = $q;
                            break;
                        }
                    }
                    
                    if (!$question) {
                        continue;
                    }
                    
                    $answer = null;
                    $questionType = $question['question_type'];
                    
                    if ($questionType === 'single_choice') {
                        $answer = (!empty($answerData) && $answerData !== '') ? (int)$answerData : null;
                    } elseif ($questionType === 'multiple_choice') {
                        if (is_array($answerData) && !empty($answerData)) {
                            $filtered = array_filter($answerData, function($val) {
                                return $val !== '' && $val !== null;
                            });
                            $answer = !empty($filtered) ? array_map('intval', $filtered) : [];
                        } else {
                            $answer = [];
                        }
                    } elseif ($questionType === 'true_false') {
                        $answer = (!empty($answerData) && $answerData !== '') ? $answerData : null;
                    } elseif ($questionType === 'short_answer') {
                        $trimmed = is_string($answerData) ? trim($answerData) : '';
                        $answer = !empty($trimmed) ? $trimmed : null;
                    }
                    
                    $currentAnswer = $currentAnswersMap[$questionId] ?? null;
                    $answerChanged = false;
                    
                    if ($questionType === 'multiple_choice') {
                        $currentArray = is_array($currentAnswer) ? $currentAnswer : [];
                        sort($currentArray);
                        $newArray = is_array($answer) ? $answer : [];
                        sort($newArray);
                        $answerChanged = $currentArray !== $newArray;
                    } else {
                        $answerChanged = $currentAnswer !== $answer;
                    }
                    
                    if ($answerChanged) {
                        if ($this->attemptModel->saveAnswer($attemptId, $questionId, $answer, $question)) {
                            $answersChanged = true;
                        }
                    }
                }
                
                if ($answersChanged) {
                    $result = $this->testService->gradeAttempt($attemptId);
                    $recalculatedScore = $result['score'];
                    $recalculatedMaxScore = $result['max_score'];
                    
                    Logger::audit('attempt_answers_updated', Session::get('user_id'), [
                        'attempt_id' => $attemptId,
                        'test_id' => $testId,
                        'old_score' => $oldScore,
                        'new_score' => $result['score'],
                        'old_max_score' => $oldMaxScore,
                        'new_max_score' => $result['max_score']
                    ]);
                }
            }

            $updateData = [];

            if (isset($_POST['status'])) {
                $updateData['status'] = $_POST['status'];
            }

            if (isset($_POST['completed_at'])) {
                if (!empty($_POST['completed_at'])) {
                    $dateTime = str_replace('T', ' ', $_POST['completed_at']);
                    $updateData['completed_at'] = $dateTime . ':00';
                } else {
                    $updateData['completed_at'] = null;
                }
            }

            if (isset($_POST['started_at'])) {
                if (!empty($_POST['started_at'])) {
                    $dateTime = str_replace('T', ' ', $_POST['started_at']);
                    $updateData['started_at'] = $dateTime . ':00';
                } else {
                    $updateData['started_at'] = null;
                }
            }

            if (!empty($updateData)) {
                if ($this->attemptModel->update($attemptId, $updateData)) {
                    Logger::audit('attempt_updated', Session::get('user_id'), [
                        'attempt_id' => $attemptId,
                        'test_id' => $testId
                    ]);
                }
            }

            if ($answersChanged || !empty($updateData)) {
                $this->setFlash('success', 'Результат тесту оновлено' . ($answersChanged ? ' (відповіді перераховано)' : ''));
                $this->redirect('/test/' . $testId . '/result/' . $attemptId);
            }
        }

        $test = $this->testModel->findById($testId);
        $questions = $this->questionModel->getByTestIdWithOptions($testId, 1, 10000);
        $answers = $this->attemptModel->getAnswers($attemptId);

        $answersMap = [];
        foreach ($answers as $answer) {
            $answersMap[(int)$answer['question_id']] = json_decode($answer['answer_data'], true) ?? $answer['answer_data'];
        }

        $questionData = [];
        foreach ($questions as $question) {
            $questionWithOptions = $this->questionModel->findWithOptions((int)$question['id']);
            $userAnswer = $answersMap[(int)$question['id']] ?? null;
            
            $questionData[] = [
                'question' => $questionWithOptions ?: $question,
                'user_answer' => $userAnswer
            ];
        }

        $this->view->set('title', 'Редагувати результат тесту: ' . $test['title']);
        $this->view->set('test', $test);
        $this->view->set('attempt', $attempt);
        $this->view->set('questionData', $questionData);
        $this->view->render('test/edit_result');
    }

}
