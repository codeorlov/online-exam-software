<?php
/**
 * Контролер вчителя
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
use App\Models\Group;
use App\Models\Subject;
use App\Models\File;
use App\Models\User;
use App\Core\Database;
use App\Services\EmailService;

class TeacherController extends Controller
{
    /**
     * Список тестів вчителя
     */
    public function tests(): void
    {
        $this->requireRole('teacher');

        $testModel = new Test();
        $userId = Session::get('user_id');
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $filters = ['created_by' => $userId];
        
        $tests = $testModel->getAll($page, $perPage, $filters);
        $total = $testModel->count($filters);
        $totalPages = ceil($total / $perPage);

        $this->view->set('title', 'Мої тести');
        $this->view->set('tests', $tests);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->render('teacher/tests');
    }

    /**
     * Створити тест
     */
    public function createTest(): void
    {
        $this->requireRole('teacher');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/teacher/tests');
            }

            $testModel = new Test();
            $userId = Session::get('user_id');

            $testId = $testModel->create([
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? null,
                'subject_id' => !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null,
                'duration' => !empty($_POST['duration']) ? (int)$_POST['duration'] : null,
                'max_attempts' => !empty($_POST['max_attempts']) ? (int)$_POST['max_attempts'] : 1,
                'passing_score' => !empty($_POST['passing_score']) ? (float)$_POST['passing_score'] : 60,
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
                'created_by' => $userId
            ]);

            Logger::audit('test_created', $userId, ['test_id' => $testId]);
            $this->setFlash('success', 'Тест створено');
            $this->redirect('/teacher/tests/edit/' . $testId);
        }

        $subjectModel = new Subject();
        $subjects = $subjectModel->getAll();

        $this->view->set('title', 'Створити тест');
        $this->view->set('subjects', $subjects);
        $this->view->render('teacher/test_form');
    }

    /**
     * Редагувати тест
     */
    public function editTest(int $id): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($id);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/teacher/tests');
            }

            $testModel->update($id, [
                'title' => $_POST['title'] ?? $test['title'],
                'description' => $_POST['description'] ?? $test['description'],
                'subject_id' => !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null,
                'duration' => !empty($_POST['duration']) ? (int)$_POST['duration'] : null,
                'max_attempts' => !empty($_POST['max_attempts']) ? (int)$_POST['max_attempts'] : 1,
                'passing_score' => !empty($_POST['passing_score']) ? (float)$_POST['passing_score'] : 60,
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ]);

            Logger::audit('test_updated', $userId, ['test_id' => $id]);
            $this->setFlash('success', 'Тест оновлено');
            $this->redirect('/teacher/tests/edit/' . $id);
        }

        $questionModel = new Question();
        $questionPage = (int)($_GET['qpage'] ?? 1);
        $questionPerPage = $this->getItemsPerPage();
        $questionSearch = !empty($_GET['qsearch']) ? trim($_GET['qsearch']) : null;
        $questions = $questionModel->getByTestIdWithOptions($id, $questionPage, $questionPerPage, $questionSearch);
        $totalQuestions = $questionModel->countByTestId($id, $questionSearch);
        $totalQuestionPages = ceil($totalQuestions / $questionPerPage);

        $subjectModel = new Subject();
        $subjects = $subjectModel->getAll(1, 1000);

        $this->view->set('title', 'Редагувати тест');
        $this->view->set('test', $test);
        $this->view->set('questions', $questions);
        $this->view->set('subjects', $subjects);
        $this->view->set('questionPage', $questionPage);
        $this->view->set('totalQuestionPages', $totalQuestionPages);
        $this->view->set('totalQuestions', $totalQuestions);
        $this->view->set('questionSearch', $questionSearch);
        $this->view->set('questionPerPage', $questionPerPage);
        $this->view->render('teacher/test_form');
    }

    /**
     * Видалити тест
     */
    public function deleteTest(int $id): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($id);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        $testModel->delete($id);
        Logger::audit('test_deleted', $userId, ['test_id' => $id]);
        $this->setFlash('success', 'Тест видалено');
        $this->redirect('/dashboard/teacher');
    }

    /**
     * Додати питання до тесту
     */
    public function addQuestion(int $testId): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/teacher/tests/edit/' . $testId);
            }

            $questionModel = new Question();
            $options = [];

            $questionType = $_POST['question_type'] ?? 'single_choice';

            if ($questionType === 'single_choice' || $questionType === 'multiple_choice') {
                $optionTexts = $_POST['option_text'] ?? [];
                $optionCorrects = $_POST['option_correct'] ?? [];

                foreach ($optionTexts as $index => $text) {
                    $trimmedText = is_string($text) ? trim($text) : '';
                    if ($trimmedText !== '') {
                        $isCorrect = false;
                        if ($questionType === 'single_choice') {
                            $isCorrect = (string)$optionCorrects === (string)$index;
                        } else {
                            $isCorrect = isset($optionCorrects[$index]);
                        }
                        
                        $options[] = [
                            'text' => $trimmedText,
                            'is_correct' => $isCorrect
                        ];
                    }
                }
            } elseif ($questionType === 'true_false') {
                $correctAnswer = $_POST['correct_answer'] ?? 'true';
                $options = [
                    ['text' => 'true', 'is_correct' => $correctAnswer === 'true'],
                    ['text' => 'false', 'is_correct' => $correctAnswer === 'false']
                ];
            } elseif ($questionType === 'short_answer') {
                $correctAnswers = $_POST['correct_answer'] ?? [];
                foreach ($correctAnswers as $answer) {
                    $trimmedAnswer = is_string($answer) ? trim($answer) : '';
                    if ($trimmedAnswer !== '') {
                        $options[] = [
                            'text' => $trimmedAnswer,
                            'is_correct' => true
                        ];
                    }
                }
            }

            if (($questionType === 'single_choice' || $questionType === 'multiple_choice' || $questionType === 'short_answer') && empty($options)) {
                $this->setFlash('error', 'Питання повинно містити хоча б один варіант відповіді');
                $this->redirect('/teacher/tests/edit/' . $testId);
            }

            $questionId = $questionModel->create([
                'test_id' => $testId,
                'question_text' => $_POST['question_text'] ?? '',
                'question_type' => $questionType,
                'points' => !empty($_POST['points']) ? (float)$_POST['points'] : 1,
                'order_index' => !empty($_POST['order_index']) ? (int)$_POST['order_index'] : 0,
                'options' => $options
            ]);

            Logger::audit('question_created', $userId, ['test_id' => $testId, 'question_id' => $questionId]);
            $this->setFlash('success', 'Питання додано');
            $this->redirect('/teacher/tests/edit/' . $testId);
        }

        $this->view->set('title', 'Додати питання');
        $this->view->set('test', $test);
        $this->view->set('isAdmin', false);
        $this->view->render('teacher/question_form');
    }

    /**
     * Редагувати питання
     */
    public function editQuestion(int $testId, int $questionId): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($testId) || !Security::validateId($questionId)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        $questionModel = new Question();
        $question = $questionModel->findWithOptions($questionId);

        if (!$question || (int)$question['test_id'] !== $testId) {
            $this->setFlash('error', 'Питання не знайдено');
            $this->redirect('/teacher/tests/edit/' . $testId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/teacher/tests/edit/' . $testId);
            }

            $options = [];
            $questionType = $_POST['question_type'] ?? $question['question_type'];

            if ($questionType === 'single_choice' || $questionType === 'multiple_choice') {
                $optionTexts = $_POST['option_text'] ?? [];
                $optionCorrects = $_POST['option_correct'] ?? [];

                foreach ($optionTexts as $index => $text) {
                    $trimmedText = is_string($text) ? trim($text) : '';
                    if ($trimmedText !== '') {
                        $isCorrect = false;
                        if ($questionType === 'single_choice') {
                            $isCorrect = (string)$optionCorrects === (string)$index;
                        } else {
                            $isCorrect = isset($optionCorrects[$index]);
                        }
                        
                        $options[] = [
                            'text' => $trimmedText,
                            'is_correct' => $isCorrect
                        ];
                    }
                }
            } elseif ($questionType === 'true_false') {
                $correctAnswer = $_POST['correct_answer'] ?? 'true';
                $options = [
                    ['text' => 'true', 'is_correct' => $correctAnswer === 'true'],
                    ['text' => 'false', 'is_correct' => $correctAnswer === 'false']
                ];
            } elseif ($questionType === 'short_answer') {
                $correctAnswers = $_POST['correct_answer'] ?? [];
                foreach ($correctAnswers as $answer) {
                    $trimmedAnswer = is_string($answer) ? trim($answer) : '';
                    if ($trimmedAnswer !== '') {
                        $options[] = [
                            'text' => $trimmedAnswer,
                            'is_correct' => true
                        ];
                    }
                }
            }

            if (($questionType === 'single_choice' || $questionType === 'multiple_choice' || $questionType === 'short_answer') && empty($options)) {
                $this->setFlash('error', 'Питання повинно містити хоча б один варіант відповіді');
                $this->redirect('/teacher/tests/edit/' . $testId);
            }

            $oldPoints = (float)$question['points'];
            $newPoints = !empty($_POST['points']) ? (float)$_POST['points'] : $oldPoints;
            $pointsChanged = abs($oldPoints - $newPoints) > 0.001;

            $oldOptions = $question['options'] ?? [];
            $optionsChanged = false;
            if (!empty($options)) {
                $oldCorrectOptions = array_filter($oldOptions, fn($opt) => $opt['is_correct'] ?? false);
                $newCorrectOptions = array_filter($options, fn($opt) => $opt['is_correct'] ?? false);
                
                if (count($oldCorrectOptions) !== count($newCorrectOptions)) {
                    $optionsChanged = true;
                } else {
                    $oldTexts = array_map(fn($opt) => $opt['text'] ?? '', $oldCorrectOptions);
                    $newTexts = array_map(fn($opt) => $opt['text'] ?? '', $newCorrectOptions);
                    sort($oldTexts);
                    sort($newTexts);
                    $optionsChanged = $oldTexts !== $newTexts;
                }
            }

            $questionModel->update($questionId, [
                'question_text' => $_POST['question_text'] ?? $question['question_text'],
                'question_type' => $questionType,
                'points' => $newPoints,
                'order_index' => !empty($_POST['order_index']) ? (int)$_POST['order_index'] : $question['order_index'],
                'options' => $options
            ]);

            if ($pointsChanged || $optionsChanged) {
                $testService = new \App\Services\TestService();
                $recalculatedCount = $testService->recalculateTestAttempts($testId);
                Logger::audit('question_updated_recalculated', $userId, [
                    'test_id' => $testId, 
                    'question_id' => $questionId,
                    'points_changed' => $pointsChanged,
                    'options_changed' => $optionsChanged,
                    'old_points' => $oldPoints,
                    'new_points' => $newPoints,
                    'attempts_recalculated' => $recalculatedCount
                ]);
            }

            Logger::audit('question_updated', $userId, ['test_id' => $testId, 'question_id' => $questionId]);
            $flashMessage = 'Питання оновлено';
            if ($pointsChanged || $optionsChanged) {
                $flashMessage .= '. Перераховано спроб: ' . ($recalculatedCount ?? 0);
            }
            $this->setFlash('success', $flashMessage);
            $this->redirect('/teacher/tests/edit/' . $testId);
        }

        $this->view->set('title', 'Редагувати питання');
        $this->view->set('test', $test);
        $this->view->set('question', $question);
        $this->view->render('teacher/question_edit_form');
    }

    /**
     * Видалити питання
     */
    public function deleteQuestion(int $testId, int $questionId): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($testId) || !Security::validateId($questionId)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        $questionModel = new Question();
        $question = $questionModel->findById($questionId);

        if (!$question || (int)$question['test_id'] !== $testId) {
            $this->setFlash('error', 'Питання не знайдено');
            $this->redirect('/teacher/tests/edit/' . $testId);
        }

        $questionModel->delete($questionId);
        Logger::audit('question_deleted', $userId, ['test_id' => $testId, 'question_id' => $questionId]);
        $this->setFlash('success', 'Питання видалено');
        $this->redirect('/teacher/tests/edit/' . $testId);
    }

    /**
     * Призначити тест групам/студентам
     */
    public function assignTest(int $testId): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/teacher/tests/assign/' . $testId);
            }

            $db = Database::getInstance();
            
            // Отримати групи, призначені вчителю
            $userModel = new \App\Models\User();
            $teacherGroups = $userModel->getTeacherGroups($userId);
            $teacherGroupIds = array_column($teacherGroups, 'id');
            
            // Перевірити групи
            $groupIds = $_POST['group_ids'] ?? [];
            foreach ($groupIds as $groupId) {
                $groupId = (int)$groupId;
                if (!in_array($groupId, $teacherGroupIds)) {
                    $this->setFlash('error', 'Спроба призначити тест групі, яка не призначена вам');
                    $this->redirect('/teacher/tests/assign/' . $testId);
                }
            }
            
            // Перевірити студентів - чи належать вони до груп вчителя
            $userIds = $_POST['user_ids'] ?? [];
            if (!empty($userIds)) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $stmt = $db->prepare("SELECT id, group_id FROM users WHERE id IN ($placeholders) AND role = 'student'");
                $stmt->execute($userIds);
                $students = $stmt->fetchAll();
                
                foreach ($students as $student) {
                    $studentGroupId = $student['group_id'] ? (int)$student['group_id'] : null;
                    if ($studentGroupId === null || !in_array($studentGroupId, $teacherGroupIds)) {
                        $this->setFlash('error', 'Спроба призначити тест студенту, який не належить до ваших груп');
                        $this->redirect('/teacher/tests/assign/' . $testId);
                    }
                }
            }

            $stmt = $db->prepare("DELETE FROM test_assignments WHERE test_id = :test_id");
            $stmt->execute(['test_id' => $testId]);

            $stmt = $db->prepare("INSERT INTO test_assignments (test_id, user_id, group_id, created_at) VALUES (:test_id, :user_id, :group_id, NOW())");

            foreach ($groupIds as $groupId) {
                $stmt->execute([
                    'test_id' => $testId,
                    'user_id' => null,
                    'group_id' => (int)$groupId
                ]);
            }

            foreach ($userIds as $userId) {
                $stmt->execute([
                    'test_id' => $testId,
                    'user_id' => (int)$userId,
                    'group_id' => null
                ]);
            }

            Logger::audit('test_assigned', $userId, ['test_id' => $testId]);
            $this->setFlash('success', 'Тест призначено');
            $this->redirect('/teacher/tests');
        }

        // Отримати лише групи, призначені вчителю
        $userModel = new \App\Models\User();
        $groups = $userModel->getTeacherGroups($userId);
        
        // Отримати студентів з груп вчителя
        $teacherGroupIds = array_column($groups, 'id');
        $db = Database::getInstance();
        $students = [];
        
        if (!empty($teacherGroupIds)) {
            $placeholders = implode(',', array_fill(0, count($teacherGroupIds), '?'));
            $stmt = $db->prepare("
                SELECT u.*, g.name as group_name 
                FROM users u
                LEFT JOIN groups g ON u.group_id = g.id
                WHERE u.role = 'student' AND u.group_id IN ($placeholders)
                ORDER BY u.last_name, u.first_name
            ");
            $stmt->execute($teacherGroupIds);
            $students = $stmt->fetchAll();
        }

        $stmt = $db->prepare("SELECT user_id, group_id FROM test_assignments WHERE test_id = :test_id");
        $stmt->execute(['test_id' => $testId]);
        $assignments = $stmt->fetchAll();

        $assignedGroupIds = [];
        $assignedUserIds = [];
        foreach ($assignments as $assignment) {
            if ($assignment['group_id']) {
                $assignedGroupIds[] = (int)$assignment['group_id'];
            }
            if ($assignment['user_id']) {
                $assignedUserIds[] = (int)$assignment['user_id'];
            }
        }

        $this->view->set('title', 'Призначити тест');
        $this->view->set('test', $test);
        $this->view->set('groups', $groups);
        $this->view->set('students', $students);
        $this->view->set('assignedGroupIds', $assignedGroupIds);
        $this->view->set('assignedUserIds', $assignedUserIds);
        $this->view->render('teacher/assign_test');
    }

    /**
     * Статистика по тесту
     */
    public function testStats(int $testId): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        $attemptModel = new Attempt();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $filters = [
            'status' => !empty($_GET['status']) ? $_GET['status'] : null,
            'user_id' => !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null
        ];
        
        $attempts = $attemptModel->getByTestId($testId, $page, $perPage, array_filter($filters));
        $total = $attemptModel->countByTestId($testId, array_filter($filters));
        $totalPages = ceil($total / $perPage);
        
        $allAttempts = $attemptModel->getByTestId($testId, 1, 10000);

        $stats = [
            'total_attempts' => count($allAttempts),
            'completed' => 0,
            'passed' => 0,
            'average_score' => 0
        ];

        $totalScore = 0;
        foreach ($allAttempts as $attempt) {
            if ($attempt['status'] === 'completed') {
                $stats['completed']++;
                $totalScore += (float)$attempt['score'];
                
                $percentage = $attempt['max_score'] > 0 
                    ? ($attempt['score'] / $attempt['max_score']) * 100 
                    : 0;
                
                if ($percentage >= (float)$test['passing_score']) {
                    $stats['passed']++;
                }
            }
        }

        if ($stats['completed'] > 0) {
            $stats['average_score'] = round($totalScore / $stats['completed'], 2);
        }
        
        $userModel = new \App\Models\User();
        $students = $userModel->getAll(1, 1000, ['role' => 'student']);

        $this->view->set('title', 'Статистика теста');
        $this->view->set('test', $test);
        $this->view->set('attempts', $attempts);
        $this->view->set('stats', $stats);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->set('filters', $filters);
        $this->view->set('students', $students);
        $this->view->render('teacher/test_stats');
    }

    /**
     * Експорт статистики тесту
     */
    public function exportTestStats(int $testId): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/teacher/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);
        $userId = Session::get('user_id');

        if (!$test || (int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/teacher/tests');
        }

        $format = $_GET['format'] ?? 'csv';
        $filters = [
            'status' => !empty($_GET['status']) ? $_GET['status'] : null,
            'user_id' => !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null
        ];

        $attemptModel = new Attempt();
        $attempts = $attemptModel->getByTestId($testId, 1, 10000, array_filter($filters));

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="test_stats_' . $testId . '_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['Студент', 'Email', 'Дата початку', 'Дата завершення', 'Статус', 'Бали', 'Макс. бали', 'Відсоток', 'Результат'], ';');
            
            foreach ($attempts as $attempt) {
                $percentage = $attempt['max_score'] > 0 
                    ? round(($attempt['score'] / $attempt['max_score']) * 100, 1) 
                    : 0;
                $result = $percentage >= (float)$test['passing_score'] ? 'Пройдено' : 'Не пройдено';
                
                fputcsv($output, [
                    $attempt['first_name'] . ' ' . $attempt['last_name'],
                    $attempt['email'],
                    date('d.m.Y H:i', strtotime($attempt['started_at'])),
                    $attempt['completed_at'] ? date('d.m.Y H:i', strtotime($attempt['completed_at'])) : '-',
                    $attempt['status'] === 'completed' ? 'Завершено' : 'В процесі',
                    $attempt['score'] ?? '-',
                    $attempt['max_score'] ?? '-',
                    $attempt['status'] === 'completed' ? $percentage . '%' : '-',
                    $attempt['status'] === 'completed' ? $result : '-'
                ], ';');
            }
            
            fclose($output);
            exit;
        }
        
        $this->redirect('/teacher/tests/' . $testId . '/stats');
    }

    /**
     * Видалити спробу (вчитель)
     */
    public function deleteAttempt(int $testId, int $attemptId): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($testId) || !Security::validateId($attemptId)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/teacher/tests/' . $testId . '/stats');
        }

        $testModel = new \App\Models\Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/dashboard/teacher');
        }

        $userId = Session::get('user_id');
        if ((int)$test['created_by'] !== $userId) {
            $this->setFlash('error', 'Доступ заборонено');
            $this->redirect('/dashboard/teacher');
        }

        $attemptModel = new \App\Models\Attempt();
        $attempt = $attemptModel->findById($attemptId);

        if (!$attempt || (int)$attempt['test_id'] !== $testId) {
            $this->setFlash('error', 'Спробу не знайдено');
            $this->redirect('/teacher/tests/' . $testId . '/stats');
        }

        if ($attemptModel->delete($attemptId)) {
            Logger::audit('attempt_deleted', $userId, [
                'attempt_id' => $attemptId,
                'test_id' => $testId,
                'user_id' => $attempt['user_id']
            ]);
            $this->setFlash('success', 'Спробу видалено');
        } else {
            $this->setFlash('error', 'Не вдалося видалити спробу');
        }

        $this->redirect('/teacher/tests/' . $testId . '/stats');
    }

    /**
     * Управління файлами (вчитель)
     */
    public function files(): void
    {
        $this->requireRole('teacher');

        $fileModel = new File();
        $userId = Session::get('user_id');
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $filters = [
            'uploaded_by' => $userId,
            'search' => !empty($_GET['search']) ? trim($_GET['search']) : null
        ];

        $files = $fileModel->getAll($page, $perPage, array_filter($filters));
        $total = $fileModel->count(array_filter($filters));
        $totalPages = ceil($total / $perPage);

        $this->view->set('title', 'Управління файлами');
        $this->view->set('files', $files);
        $this->view->set('filters', $filters);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->render('teacher/files');
    }

    /**
     * Завантажити файл (вчитель)
     */
    public function uploadFile(): void
    {
        $this->requireRole('teacher');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/teacher/files');
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'Файл перевищує максимальний розмір, встановлений у php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'Файл перевищує максимальний розмір, встановлений у формі',
                    UPLOAD_ERR_PARTIAL => 'Файл був завантажений частково',
                    UPLOAD_ERR_NO_FILE => 'Файл не був завантажений',
                    UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
                    UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск',
                    UPLOAD_ERR_EXTENSION => 'Завантаження файлу було зупинено розширенням'
                ];
                $error = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
                $this->setFlash('error', $errorMessages[$error] ?? 'Помилка завантаження файлу');
                $this->redirect('/teacher/files');
            }

            $file = $_FILES['file'];
            
            if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 104857600) {
                $this->setFlash('error', 'Розмір запиту перевищує максимально дозволений');
                $this->redirect('/teacher/files');
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                $this->setFlash('error', 'Невірний файл або файл не був завантажений через HTTP POST');
                $this->redirect('/teacher/files');
            }
            
            $settings = new \App\Models\Settings();
            $maxFileSize = (int)$settings->get('max_file_size', 10485760);
            $allowedTypes = $settings->get('allowed_file_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif');
            $allowedTypesArray = array_map('trim', explode(',', strtolower($allowedTypes)));

            if ($file['size'] > $maxFileSize) {
                $maxFileSizeMB = round($maxFileSize / 1048576, 1);
                $this->setFlash('error', "Розмір файлу перевищує {$maxFileSizeMB} MB");
                $this->redirect('/teacher/files');
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypesArray)) {
                $this->setFlash('error', 'Тип файлу не дозволено. Дозволені типи: ' . $allowedTypes);
                $this->redirect('/teacher/files');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

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

            if (!isset($allowedMimeTypes[$extension]) || !in_array($detectedMimeType, $allowedMimeTypes[$extension])) {
                $this->setFlash('error', "MIME-тип файлу не відповідає розширенню. Виявлено: {$detectedMimeType}");
                $this->redirect('/teacher/files');
            }

            $originalName = basename($file['name']);
            $originalName = preg_replace('/[\/\\\\:\*\?"<>\|]/', '_', $originalName);
            $originalName = preg_replace('/\.\./', '_', $originalName);
            $originalName = trim($originalName);
            if (empty($originalName)) {
                $originalName = 'file';
            }
            
            $userId = Session::get('user_id');

            $uploadDir = BASE_PATH . '/public/uploads/files/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $attempts = 0;
            do {
                $fileName = uniqid('file_', true) . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                $attempts++;
                if ($attempts > 10) {
                    $this->setFlash('error', 'Не вдалося створити унікальне ім\'я файлу');
                    $this->redirect('/teacher/files');
                }
            } while (file_exists($filePath));

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                $this->setFlash('error', 'Помилка при збереженні файлу');
                $this->redirect('/teacher/files');
            }

            $fileModel = new File();
            $fileId = $fileModel->create([
                'name' => $fileName,
                'original_name' => $originalName,
                'file_path' => $filePath,
                'file_size' => $file['size'],
                'mime_type' => $detectedMimeType,
                'uploaded_by' => $userId
            ]);

            $userIds = !empty($_POST['user_ids']) && is_array($_POST['user_ids']) 
                ? array_map('intval', array_filter($_POST['user_ids'])) 
                : [];
            $groupIds = !empty($_POST['group_ids']) && is_array($_POST['group_ids']) 
                ? array_map('intval', array_filter($_POST['group_ids'])) 
                : [];

            // Отримати групи, призначені вчителю
            $userModel = new User();
            $teacherGroups = $userModel->getTeacherGroups($userId);
            $teacherGroupIds = array_column($teacherGroups, 'id');
            
            // Перевірити групи
            foreach ($groupIds as $groupId) {
                if (!in_array($groupId, $teacherGroupIds)) {
                    $this->setFlash('error', 'Спроба призначити файл групі, яка не призначена вам');
                    $this->redirect('/teacher/files');
                }
            }
            
            // Перевірити студентів - чи належать вони до груп вчителя
            if (!empty($userIds)) {
                $db = Database::getInstance();
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $stmt = $db->prepare("SELECT id, group_id FROM users WHERE id IN ($placeholders) AND role = 'student'");
                $stmt->execute($userIds);
                $students = $stmt->fetchAll();
                
                foreach ($students as $student) {
                    $studentGroupId = $student['group_id'] ? (int)$student['group_id'] : null;
                    if ($studentGroupId === null || !in_array($studentGroupId, $teacherGroupIds)) {
                        $this->setFlash('error', 'Спроба призначити файл студенту, який не належить до ваших груп');
                        $this->redirect('/teacher/files');
                    }
                }
            }

            $uploader = $userModel->findById($userId);
            $uploaderName = $uploader ? ($uploader['first_name'] . ' ' . $uploader['last_name']) : 'Учитель';
            
            if (!empty($userIds)) {
                $fileModel->assignToUsers($fileId, $userIds);
                
                $emailService = new EmailService();
                foreach ($userIds as $studentId) {
                    $student = $userModel->findById((int)$studentId);
                    if ($student && (int)($student['email_notifications'] ?? 1) === 1 && $student['role'] === 'student') {
                        $emailSent = $emailService->sendFileSharedNotification(
                            $student['email'],
                            $student['first_name'] . ' ' . $student['last_name'],
                            $originalName,
                            $uploaderName
                        );
                        
                        if (!$emailSent) {
                            Logger::error('Failed to send file shared notification (teacher upload)', [
                                'student_id' => $studentId,
                                'file_id' => $fileId,
                                'email' => $student['email']
                            ]);
                        }
                    }
                }
            }
            if (!empty($groupIds)) {
                $fileModel->assignToGroups($fileId, $groupIds);
                
                $emailService = new EmailService();
                $db = Database::getInstance();
                foreach ($groupIds as $groupId) {
                    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'student' AND group_id = :group_id");
                    $stmt->execute(['group_id' => $groupId]);
                    $students = $stmt->fetchAll();
                    
                    foreach ($students as $student) {
                        if ((int)($student['email_notifications'] ?? 1) === 1) {
                        $emailSent = $emailService->sendFileSharedNotification(
                            $student['email'],
                            $student['first_name'] . ' ' . $student['last_name'],
                            $originalName,
                            $uploaderName
                        );
                            
                            if (!$emailSent) {
                                Logger::error('Failed to send file shared notification to group student (teacher)', [
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

            Logger::audit('file_uploaded', $userId, ['file_id' => $fileId, 'file_name' => $originalName]);
            $this->setFlash('success', 'Файл успішно завантажено');
            $this->redirect('/teacher/files');
        }

        $userModel = new \App\Models\User();
        $userId = Session::get('user_id');
        $teacherGroups = $userModel->getTeacherGroups($userId);
        $groupIds = array_column($teacherGroups, 'id');
        
        $students = [];
        if (!empty($groupIds)) {
            $db = Database::getInstance();
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'student' AND group_id IN ($placeholders)");
            $stmt->execute($groupIds);
            $allStudents = $stmt->fetchAll();
            
            foreach ($allStudents as $student) {
                $studentData = $student;
                if (!empty($student['group_id'])) {
                    $groupModel = new Group();
                    $group = $groupModel->findById((int)$student['group_id']);
                    $studentData['group_name'] = $group ? $group['name'] : null;
                }
                $students[] = $studentData;
            }
        }

        $groupModel = new Group();
        $groups = $teacherGroups;

        $this->view->set('title', 'Завантажити файл');
        $this->view->set('students', $students);
        $this->view->set('groups', $groups);
        $this->view->render('teacher/file_upload');
    }

    /**
     * Видалити файл (учитель)
     */
    public function deleteFile(int $id): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/teacher/files');
        }

        $fileModel = new File();
        $file = $fileModel->findById($id);
        $userId = Session::get('user_id');

        if (!$file) {
            $this->setFlash('error', 'Файл не знайдено');
            $this->redirect('/teacher/files');
        }

        if ((int)$file['uploaded_by'] !== $userId) {
            $this->setFlash('error', 'Доступ заборонено');
            $this->redirect('/teacher/files');
        }

        if ($fileModel->delete($id)) {
            Logger::audit('file_deleted', $userId, ['file_id' => $id, 'file_name' => $file['original_name']]);
            $this->setFlash('success', 'Файл видалено');
        } else {
            $this->setFlash('error', 'Помилка при видаленні файлу');
        }

        $this->redirect('/teacher/files');
    }

    /**
     * Редагувати призначення файлу (вчитель)
     */
    public function editFile(int $id): void
    {
        $this->requireRole('teacher');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/teacher/files');
        }

        $fileModel = new File();
        $file = $fileModel->findById($id);
        $userId = Session::get('user_id');

        if (!$file) {
            $this->setFlash('error', 'Файл не знайдено');
            $this->redirect('/teacher/files');
        }

        if ((int)$file['uploaded_by'] !== $userId) {
            $this->setFlash('error', 'Доступ заборонено');
            $this->redirect('/teacher/files');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/teacher/files');
            }

            $userIds = !empty($_POST['user_ids']) && is_array($_POST['user_ids']) 
                ? array_map('intval', array_filter($_POST['user_ids'])) 
                : [];
            $groupIds = !empty($_POST['group_ids']) && is_array($_POST['group_ids']) 
                ? array_map('intval', array_filter($_POST['group_ids'])) 
                : [];
            
            // Отримати групи, призначені вчителю
            $userModel = new \App\Models\User();
            $teacherGroups = $userModel->getTeacherGroups($userId);
            $teacherGroupIds = array_column($teacherGroups, 'id');
            
            // Перевірити групи
            foreach ($groupIds as $groupId) {
                if (!in_array($groupId, $teacherGroupIds)) {
                    $this->setFlash('error', 'Спроба призначити файл групі, яка не призначена вам');
                    $this->redirect('/teacher/files');
                }
            }
            
            // Перевірити студентів - чи належать вони до груп вчителя
            if (!empty($userIds)) {
                $db = Database::getInstance();
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $stmt = $db->prepare("SELECT id, group_id FROM users WHERE id IN ($placeholders) AND role = 'student'");
                $stmt->execute($userIds);
                $students = $stmt->fetchAll();
                
                foreach ($students as $student) {
                    $studentGroupId = $student['group_id'] ? (int)$student['group_id'] : null;
                    if ($studentGroupId === null || !in_array($studentGroupId, $teacherGroupIds)) {
                        $this->setFlash('error', 'Спроба призначити файл студенту, який не належить до ваших груп');
                        $this->redirect('/teacher/files');
                    }
                }
            }
            
            $oldAssignments = $fileModel->getAssignments($id);
            $oldUserIds = [];
            $oldGroupIds = [];
            foreach ($oldAssignments as $assignment) {
                if (!empty($assignment['user_id'])) {
                    $oldUserIds[] = (int)$assignment['user_id'];
                }
                if (!empty($assignment['group_id'])) {
                    $oldGroupIds[] = (int)$assignment['group_id'];
                }
            }
            
            if (!empty($userIds)) {
                $fileModel->assignToUsers($id, $userIds);
            } else {
                $db = Database::getInstance();
                $stmt = $db->prepare("DELETE FROM file_assignments WHERE file_id = :file_id AND user_id IS NOT NULL");
                $stmt->execute(['file_id' => $id]);
            }

            if (!empty($groupIds)) {
                $fileModel->assignToGroups($id, $groupIds);
            } else {
                $db = Database::getInstance();
                $stmt = $db->prepare("DELETE FROM file_assignments WHERE file_id = :file_id AND group_id IS NOT NULL");
                $stmt->execute(['file_id' => $id]);
            }
            
            $uploader = $userModel->findById((int)$file['uploaded_by']);
            $uploaderName = $uploader ? ($uploader['first_name'] . ' ' . $uploader['last_name']) : 'Вчитель';
            $newUserIds = array_diff($userIds, $oldUserIds);
            $newGroupIds = array_diff($groupIds, $oldGroupIds);
            
            if (!empty($newUserIds)) {
                $emailService = new EmailService();
                foreach ($newUserIds as $studentId) {
                    $student = $userModel->findById((int)$studentId);
                    if ($student && (int)($student['email_notifications'] ?? 1) === 1 && $student['role'] === 'student') {
                        $emailSent = $emailService->sendFileSharedNotification(
                            $student['email'],
                            $student['first_name'] . ' ' . $student['last_name'],
                            $file['original_name'] ?? 'Файл',
                            $uploaderName
                        );
                        
                        if (!$emailSent) {
                            Logger::error('Failed to send file shared notification (teacher edit)', [
                                'student_id' => $studentId,
                                'file_id' => $id,
                                'email' => $student['email']
                            ]);
                        }
                    }
                }
            }
            
            if (!empty($newGroupIds)) {
                $emailService = new EmailService();
                $db = Database::getInstance();
                foreach ($newGroupIds as $groupId) {
                    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'student' AND group_id = :group_id");
                    $stmt->execute(['group_id' => $groupId]);
                    $students = $stmt->fetchAll();
                    
                    foreach ($students as $student) {
                        if ((int)($student['email_notifications'] ?? 1) === 1) {
                            $emailSent = $emailService->sendFileSharedNotification(
                                $student['email'],
                                $student['first_name'] . ' ' . $student['last_name'],
                                $file['original_name'] ?? 'Файл',
                                $uploaderName
                            );
                            
                            if (!$emailSent) {
                                Logger::error('Failed to send file shared notification to group student (teacher edit)', [
                                    'student_id' => $student['id'],
                                    'file_id' => $id,
                                    'group_id' => $groupId,
                                    'email' => $student['email']
                                ]);
                            }
                        }
                    }
                }
            }

            Logger::audit('file_assignments_updated', $userId, ['file_id' => $id]);
            $this->setFlash('success', 'Призначення файлу оновлено');
            $this->redirect('/teacher/files');
        }

        $assignments = $fileModel->getAssignments($id);
        $assignedUserIds = [];
        $assignedGroupIds = [];
        foreach ($assignments as $assignment) {
            if ($assignment['user_id']) {
                $assignedUserIds[] = (int)$assignment['user_id'];
            }
            if ($assignment['group_id']) {
                $assignedGroupIds[] = (int)$assignment['group_id'];
            }
        }

        $userModel = new \App\Models\User();
        $teacherGroups = $userModel->getTeacherGroups($userId);
        $groupIds = array_column($teacherGroups, 'id');
        
        $students = [];
        if (!empty($groupIds)) {
            $db = Database::getInstance();
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'student' AND group_id IN ($placeholders)");
            $stmt->execute($groupIds);
            $allStudents = $stmt->fetchAll();
            
            foreach ($allStudents as $student) {
                $studentData = $student;
                if (!empty($student['group_id'])) {
                    $groupModel = new Group();
                    $group = $groupModel->findById((int)$student['group_id']);
                    $studentData['group_name'] = $group ? $group['name'] : null;
                }
                $students[] = $studentData;
            }
        }

        $groupModel = new Group();
        $groups = $teacherGroups;

        $this->view->set('title', 'Редагувати призначення файлу');
        $this->view->set('file', $file);
        $this->view->set('students', $students);
        $this->view->set('groups', $groups);
        $this->view->set('assignedUserIds', $assignedUserIds);
        $this->view->set('assignedGroupIds', $assignedGroupIds);
        $this->view->render('teacher/file_edit');
    }
}
