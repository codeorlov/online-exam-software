<?php
/**
 * Контролер головної сторінки (Dashboard)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Test;
use App\Models\Attempt;
use App\Models\User;
use App\Models\Subject;
use App\Models\File;
use App\Models\Group;

class DashboardController extends Controller
{
    /**
     * Головна сторінка (редирект залежно від ролі)
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $role = Session::get('user_role');
        
        switch ($role) {
            case 'admin':
                $this->admin();
                break;
            case 'teacher':
                $this->teacher();
                break;
            case 'student':
                $this->student();
                break;
            default:
                $this->redirect('/login');
        }
    }

    /**
     * Dashboard для адміністратора
     */
    public function admin(): void
    {
        $this->requireRole('admin');

        $userModel = new User();
        $testModel = new Test();
        $attemptModel = new Attempt();
        $fileModel = new File();

        $stats = [
            'total_users' => $userModel->count(),
            'total_students' => $userModel->count(['role' => 'student']),
            'total_teachers' => $userModel->count(['role' => 'teacher']),
            'total_tests' => count($testModel->getAll(1, 1000)),
            'total_attempts' => count($attemptModel->getByTestId(1)),
            'total_files' => $fileModel->count()
        ];

        $this->view->set('title', 'Панель адміністратора');
        $this->view->set('stats', $stats);
        $this->view->render('dashboard/admin');
    }

    /**
     * Dashboard для вчителя
     */
    public function teacher(): void
    {
        $this->requireRole('teacher');

        $testModel = new Test();
        $subjectModel = new \App\Models\Subject();
        $attemptModel = new \App\Models\Attempt();
        $userId = Session::get('user_id');
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        
        $filters = ['created_by' => $userId];
        if (isset($_GET['subject_id']) && $_GET['subject_id'] !== '') {
            $filters['subject_id'] = (int)$_GET['subject_id'];
        }
        if (isset($_GET['is_published']) && $_GET['is_published'] !== '') {
            $filters['is_published'] = (int)$_GET['is_published'];
        }
        
        $tests = $testModel->getAll($page, $perPage, $filters);
        $total = $testModel->count($filters);
        $totalPages = ceil($total / $perPage);
        
        $subjects = $subjectModel->getAll(1, 1000);

        $this->view->set('title', 'Управління тестами');
        $this->view->set('tests', $tests);
        $this->view->set('subjects', $subjects);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->set('filters', $filters);
        $this->view->set('attemptModel', $attemptModel);
        $this->view->render('dashboard/teacher');
    }

    /**
     * Dashboard для студента
     */
    public function student(): void
    {
        $this->requireRole('student');

        $testModel = new Test();
        $attemptModel = new Attempt();
        $userId = Session::get('user_id');
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();

        $availableTests = $testModel->getAvailableForStudent($userId, $page, $perPage);
        $total = $testModel->countAvailableForStudent($userId);
        $totalPages = ceil($total / $perPage);
        
        foreach ($availableTests as &$test) {
            $test['user_attempts_count'] = $attemptModel->countByTestAndUser((int)$test['id'], $userId);
            $test['remaining_attempts'] = $test['max_attempts'] > 0 
                ? max(0, (int)$test['max_attempts'] - $test['user_attempts_count']) 
                : null;
        }
        unset($test);
        
        $attemptPage = (int)($_GET['apage'] ?? 1);
        $attemptPerPage = $this->getItemsPerPage();
        $attemptFilters = [
            'status' => !empty($_GET['status']) ? $_GET['status'] : null,
            'test_id' => !empty($_GET['test_id']) ? (int)$_GET['test_id'] : null
        ];
        $myAttempts = $attemptModel->getByUserId($userId, $attemptPage, $attemptPerPage, array_filter($attemptFilters));
        $totalAttempts = $attemptModel->countByUserId($userId, array_filter($attemptFilters));
        $totalAttemptPages = ceil($totalAttempts / $attemptPerPage);
        
        $testService = new \App\Services\TestService();
        foreach ($myAttempts as &$attempt) {
            if ($attempt['status'] === 'in_progress') {
                $test = $testModel->findById((int)$attempt['test_id']);
                if ($test && $test['duration']) {
                    $isExpired = $attemptModel->isTimeExpired((int)$attempt['id'], (int)$test['duration']);
                    $attempt['time_expired'] = $isExpired;
                    
                    if ($isExpired) {
                        try {
                            $testService->gradeAttempt((int)$attempt['id']);
                            $updatedAttempt = $attemptModel->findById((int)$attempt['id']);
                            if ($updatedAttempt) {
                                $attempt = $updatedAttempt;
                            }
                        } catch (\Exception $e) {
                        }
                    }
                } else {
                    $attempt['time_expired'] = false;
                }
            } else {
                $attempt['time_expired'] = false;
            }
        }
        unset($attempt);
        
        $allTests = $testModel->getAll(1, 1000);

        $userModel = new User();
        $user = $userModel->findById($userId);
        $group = null;
        if (!empty($user['group_id'])) {
            $groupModel = new Group();
            $group = $groupModel->findById((int)$user['group_id']);
        }

        $this->view->set('title', 'Мої тести');
        $this->view->set('availableTests', $availableTests);
        $this->view->set('attempts', $myAttempts);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->set('attemptPage', $attemptPage);
        $this->view->set('totalAttemptPages', $totalAttemptPages);
        $this->view->set('attemptFilters', $attemptFilters);
        $this->view->set('allTests', $allTests);
        $this->view->set('group', $group);
        $this->view->render('dashboard/student');
    }

    /**
     * Файли для студента
     */
    public function studentFiles(): void
    {
        $this->requireRole('student');

        $fileModel = new File();
        $userId = Session::get('user_id');
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $filters = [
            'search' => !empty($_GET['search']) ? trim($_GET['search']) : null
        ];

        $files = $fileModel->getAvailableForStudent($userId, $page, $perPage, array_filter($filters));
        $total = $fileModel->countAvailableForStudent($userId, array_filter($filters));
        $totalPages = ceil($total / $perPage);

        $this->view->set('title', 'Доступні файли');
        $this->view->set('files', $files);
        $this->view->set('filters', $filters);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->render('dashboard/student_files');
    }
}
