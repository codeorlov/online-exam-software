<?php
/**
 * Контролер адміністратора
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Core\Logger;
use App\Core\Session;
use App\Models\User;
use App\Models\Test;
use App\Models\Group;
use App\Models\Subject;
use App\Models\Question;
use App\Models\Attempt;
use App\Models\File;
use App\Models\Settings;
use App\Core\Database;
use App\Services\EmailService;
use App\Services\FileService;

class AdminController extends Controller
{
    /**
     * Управління користувачами
     */
    public function users(): void
    {
        $this->requireRole('admin');

        $userModel = new User();
        $page = (int)($_GET['page'] ?? 1);
        $filters = [
            'role' => $_GET['role'] ?? null,
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $perPage = $this->getItemsPerPage();
        $users = $userModel->getAll($page, $perPage, array_filter($filters));
        $total = $userModel->count(array_filter($filters));
        $totalPages = ceil($total / $perPage);

        $this->view->set('title', 'Управління користувачами');
        $this->view->set('users', $users);
        $this->view->set('filters', $filters);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->render('admin/users');
    }

    /**
     * Створити користувача
     */
    public function createUser(): void
    {
        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/admin/users');
            }

            $userModel = new User();
            $errors = [];

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $role = $_POST['role'] ?? 'student';
            $status = $_POST['status'] ?? 'active';
            $groupId = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
            
            if ($role === 'student' && empty($groupId)) {
                $errors[] = 'Для студента необхідно вказати групу';
            }
            
            if ($role !== 'student') {
                $groupId = null;
            }

            if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
                $errors[] = 'Заповніть всі обов\'язкові поля';
            }

            if (!Security::validateEmail($email)) {
                $errors[] = 'Некорректний email';
            }

            if ($userModel->emailExists($email)) {
                $errors[] = 'Email вже використовується';
            }

            $passwordErrors = Security::validatePassword($password);
            if (!empty($passwordErrors)) {
                $errors = array_merge($errors, $passwordErrors);
            }

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('/admin/users');
            }

            $userId = $userModel->create([
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => $role,
                'status' => $status,
                'group_id' => $groupId
            ]);

            if ($role === 'teacher') {
                $teacherGroupIds = !empty($_POST['teacher_group_ids']) && is_array($_POST['teacher_group_ids'])
                    ? array_map('intval', array_filter($_POST['teacher_group_ids']))
                    : [];
                if (!$userModel->assignGroupsToTeacher($userId, $teacherGroupIds)) {
                    Logger::error('Failed to assign groups to teacher', ['teacher_id' => $userId, 'group_ids' => $teacherGroupIds]);
                }
                
                $teacherSubjectIds = !empty($_POST['teacher_subject_ids']) && is_array($_POST['teacher_subject_ids'])
                    ? array_map('intval', array_filter($_POST['teacher_subject_ids']))
                    : [];
                if (!$userModel->assignSubjectsToTeacher($userId, $teacherSubjectIds)) {
                    Logger::error('Failed to assign subjects to teacher', ['teacher_id' => $userId, 'subject_ids' => $teacherSubjectIds]);
                }
            }

            Logger::audit('user_created', Session::get('user_id'), ['created_user_id' => $userId]);
            
            $adminUsers = $userModel->getAll(1, 1000, ['role' => 'admin']);
            $newUser = $userModel->findById($userId);
            $currentUser = $userModel->findById(Session::get('user_id'));
            
            if ($newUser && $currentUser) {
                $emailService = new EmailService();
                $newUserName = $newUser['first_name'] . ' ' . $newUser['last_name'];
                
                foreach ($adminUsers as $admin) {
                    if ((int)($admin['email_notifications'] ?? 1) === 1 && (int)$admin['id'] !== (int)$currentUser['id']) {
                        $emailSent = $emailService->sendNewUserNotification(
                            $admin['email'],
                            $admin['first_name'] . ' ' . $admin['last_name'],
                            $newUserName,
                            $newUser['email'],
                            $newUser['role']
                        );
                        
                        if (!$emailSent) {
                            Logger::error('Failed to send new user notification to admin', [
                                'admin_id' => $admin['id'],
                                'new_user_id' => $userId,
                                'email' => $admin['email']
                            ]);
                        }
                    }
                }
            }
            
            $this->setFlash('success', 'Користувача створено');
            $this->redirect('/admin/users');
        }

        $groupModel = new Group();
        $groups = $groupModel->getAll(1, 1000);
        
        $subjectModel = new Subject();
        $subjects = $subjectModel->getAll(1, 1000);

        $this->view->set('title', 'Створити користувача');
        $this->view->set('groups', $groups);
        $this->view->set('subjects', $subjects);
        $this->view->set('teacherGroupIds', []);
        $this->view->set('teacherSubjectIds', []);
        $this->view->render('admin/user_form');
    }

    /**
     * Редагувати користувача
     */
    public function editUser(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/users');
        }

        $userModel = new User();
        $user = $userModel->findById($id);

        if (!$user) {
            $this->setFlash('error', 'Користувача не знайдено');
            $this->redirect('/admin/users');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/admin/users');
            }

            $role = $_POST['role'] ?? $user['role'];
            $groupId = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
            
            if ($role === 'student' && empty($groupId)) {
                $this->setFlash('error', 'Для студента необхідно вказати групу');
                $this->redirect('/admin/users/edit/' . $id);
            }
            
            if ($role !== 'student') {
                $groupId = null;
            }
            
            $data = [
                'first_name' => $_POST['first_name'] ?? $user['first_name'],
                'last_name' => $_POST['last_name'] ?? $user['last_name'],
                'email' => $_POST['email'] ?? $user['email'],
                'role' => $role,
                'status' => $_POST['status'] ?? $user['status'],
                'group_id' => $groupId
            ];

            if ($role === 'teacher') {
                $oldTeacherGroups = $userModel->getTeacherGroups($id);
                $oldTeacherGroupIds = array_column($oldTeacherGroups, 'id');
                $oldTeacherSubjects = $userModel->getTeacherSubjects($id);
                $oldTeacherSubjectIds = array_column($oldTeacherSubjects, 'id');
                
                $teacherGroupIds = !empty($_POST['teacher_group_ids']) && is_array($_POST['teacher_group_ids'])
                    ? array_map('intval', array_filter($_POST['teacher_group_ids']))
                    : [];
                if (!$userModel->assignGroupsToTeacher($id, $teacherGroupIds)) {
                    Logger::error('Failed to assign groups to teacher', ['teacher_id' => $id, 'group_ids' => $teacherGroupIds]);
                }
                
                $teacherSubjectIds = !empty($_POST['teacher_subject_ids']) && is_array($_POST['teacher_subject_ids'])
                    ? array_map('intval', array_filter($_POST['teacher_subject_ids']))
                    : [];
                if (!$userModel->assignSubjectsToTeacher($id, $teacherSubjectIds)) {
                    Logger::error('Failed to assign subjects to teacher', ['teacher_id' => $id, 'subject_ids' => $teacherSubjectIds]);
                }
                
                if ((int)($user['email_notifications'] ?? 1) === 1) {
                    $emailService = new EmailService();
                    $groupModel = new Group();
                    $subjectModel = new Subject();
                    $teacherName = $user['first_name'] . ' ' . $user['last_name'];
                    
                    $newGroupIds = array_diff($teacherGroupIds, $oldTeacherGroupIds);
                    foreach ($newGroupIds as $groupId) {
                        $group = $groupModel->findById($groupId);
                        if ($group) {
                            $emailSent = $emailService->sendGroupAssignedNotification(
                                $user['email'],
                                $teacherName,
                                $group['name']
                            );
                            
                            if (!$emailSent) {
                                Logger::error('Failed to send group assigned notification', [
                                    'teacher_id' => $id,
                                    'group_id' => $groupId,
                                    'email' => $user['email']
                                ]);
                            }
                        }
                    }
                    
                    $newSubjectIds = array_diff($teacherSubjectIds, $oldTeacherSubjectIds);
                    foreach ($newSubjectIds as $subjectId) {
                        $subject = $subjectModel->findById($subjectId);
                        if ($subject) {
                            $emailSent = $emailService->sendSubjectAssignedNotification(
                                $user['email'],
                                $teacherName,
                                $subject['name']
                            );
                            
                            if (!$emailSent) {
                                Logger::error('Failed to send subject assigned notification', [
                                    'teacher_id' => $id,
                                    'subject_id' => $subjectId,
                                    'email' => $user['email']
                                ]);
                            }
                        }
                    }
                }
            }

            if (!empty($_POST['password'])) {
                $passwordErrors = Security::validatePassword($_POST['password']);
                if (empty($passwordErrors)) {
                    $data['password'] = $_POST['password'];
                } else {
                    $this->setFlash('error', implode('<br>', $passwordErrors));
                    $this->redirect('/admin/users/edit/' . $id);
                }
            }

            if ($data['email'] !== $user['email'] && $userModel->emailExists($data['email'], $id)) {
                $this->setFlash('error', 'Email вже використовується');
                $this->redirect('/admin/users/edit/' . $id);
            }

            $userModel->update($id, $data);
            Logger::audit('user_updated', Session::get('user_id'), ['updated_user_id' => $id]);
            $this->setFlash('success', 'Користувача оновлено');
            $this->redirect('/admin/users');
        }

        $groupModel = new Group();
        $groups = $groupModel->getAll(1, 1000);
        
        $subjectModel = new Subject();
        $subjects = $subjectModel->getAll(1, 1000);
        
        $teacherGroups = [];
        $teacherSubjects = [];
        if ($user['role'] === 'teacher') {
            $teacherGroups = $userModel->getTeacherGroups($id);
            $teacherGroupIds = array_column($teacherGroups, 'id');
            
            $teacherSubjects = $userModel->getTeacherSubjects($id);
            $teacherSubjectIds = array_column($teacherSubjects, 'id');
        } else {
            $teacherGroupIds = [];
            $teacherSubjectIds = [];
        }

        $this->view->set('title', 'Редагувати користувача');
        $this->view->set('user', $user);
        $this->view->set('groups', $groups);
        $this->view->set('subjects', $subjects);
        $this->view->set('teacherGroups', $teacherGroups);
        $this->view->set('teacherGroupIds', $teacherGroupIds);
        $this->view->set('teacherSubjects', $teacherSubjects);
        $this->view->set('teacherSubjectIds', $teacherSubjectIds);
        $this->view->render('admin/user_form');
    }

    /**
     * Видалити користувача
     */
    public function deleteUser(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/users');
        }

        $userModel = new User();
        $user = $userModel->findById($id);

        if (!$user) {
            $this->setFlash('error', 'Користувача не знайдено');
            $this->redirect('/admin/users');
        }

        $userModel->delete($id);
        Logger::audit('user_deleted', Session::get('user_id'), ['deleted_user_id' => $id]);
        $this->setFlash('success', 'Користувача видалено');
        $this->redirect('/admin/users');
    }

    /**
     * Управління групами
     */
    public function groups(): void
    {
        $this->requireAnyRole(['admin', 'teacher']);

        $groupModel = new Group();
        $userModel = new User();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');
        
        $filters = [
            'search' => $_GET['search'] ?? null
        ];
        
        if ($userRole === 'teacher') {
            $filters['teacher_id'] = $userId;
        }
        
        $groups = $groupModel->getAll($page, $perPage, array_filter($filters));
        $total = $groupModel->count(array_filter($filters));
        $totalPages = ceil($total / $perPage);

        $teachers = [];
        if ($userRole === 'admin') {
            $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
        }

        $this->view->set('title', 'Управління групами');
        $this->view->set('groups', $groups);
        $this->view->set('filters', $filters);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->set('teachers', $teachers);
        $this->view->set('isAdmin', $userRole === 'admin');
        $this->view->render('admin/groups');
    }

    /**
     * Створити групу
     */
    public function createGroup(): void
    {
        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Помилка безпеки');
                $this->redirect('/admin/groups');
            }

            $groupModel = new Group();
            $errors = [];

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $errors[] = 'Назва групи обов\'язкова';
            }

            if (strlen($name) > 100) {
                $errors[] = 'Назва групи не повинна перевищувати 100 символів';
            }

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('/admin/groups/create');
            }

            $teacherIds = !empty($_POST['teacher_ids']) && is_array($_POST['teacher_ids']) 
                ? array_map('intval', array_filter($_POST['teacher_ids'])) 
                : [];

            try {
                $groupId = $groupModel->create([
                    'name' => $name,
                    'description' => $description ?: null,
                    'teacher_id' => null
                ]);

                $groupModel->syncTeacherAssignment($groupId, $teacherIds);

                Logger::audit('group_created', Session::get('user_id'), ['group_id' => $groupId]);
                $this->setFlash('success', 'Групу створено');
                $this->redirect('/admin/groups');
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000') {
                    $this->setFlash('error', 'Група з такою назвою вже існує');
                } else {
                    $this->setFlash('error', 'Помилка при створенні групи');
                }
                $this->redirect('/admin/groups/create');
            }
        }

        $userModel = new User();
        $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);

        $this->view->set('title', 'Створити групу');
        $this->view->set('teachers', $teachers);
        $this->view->set('isAdmin', true);
        $this->view->render('admin/group_form');
    }

    /**
     * Редагувати групу
     */
    public function editGroup(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/groups');
        }

        $groupModel = new Group();
        $group = $groupModel->findById($id);

        if (!$group) {
            $this->setFlash('error', 'Групу не знайдено');
            $this->redirect('/admin/groups');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/groups');
            }

            $errors = [];
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $errors[] = 'Назва групи обов\'язкова';
            }

            if (strlen($name) > 100) {
                $errors[] = 'Назва групи не повинна перевищувати 100 символів';
            }

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('/admin/groups/edit/' . $id);
            }

            $teacherIds = !empty($_POST['teacher_ids']) && is_array($_POST['teacher_ids']) 
                ? array_map('intval', array_filter($_POST['teacher_ids'])) 
                : [];
            $updateData = [
                'name' => $name,
                'description' => $description ?: null,
                'teacher_id' => null
            ];

            $groupModel->update($id, $updateData);
            
            $groupModel->syncTeacherAssignment($id, $teacherIds);

            Logger::audit('group_updated', Session::get('user_id'), ['group_id' => $id]);
            $this->setFlash('success', 'Групу оновлено');
            $this->redirect('/admin/groups');
        }

        $userModel = new User();
        $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
        
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT teacher_id FROM teacher_groups WHERE group_id = :group_id");
        $stmt->execute(['group_id' => $id]);
        $assignedTeacherIds = array_column($stmt->fetchAll(), 'teacher_id');

        $this->view->set('title', 'Редагувати групу');
        $this->view->set('group', $group);
        $this->view->set('teachers', $teachers);
        $this->view->set('assignedTeacherIds', $assignedTeacherIds);
        $this->view->set('isAdmin', true);
        $this->view->render('admin/group_form');
    }

    /**
     * Перегляд групи
     */
    public function viewGroup(int $id): void
    {
        $this->requireAnyRole(['admin', 'teacher', 'student']);

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/dashboard');
        }

        $groupModel = new Group();
        $group = $groupModel->findById($id);
        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');

        if (!$group) {
            $this->setFlash('error', 'Групу не знайдено');
            $this->redirect('/dashboard');
        }

        if ($userRole === 'student') {
            $userModel = new User();
            $user = $userModel->findById($userId);
            if (!$user || (int)$user['group_id'] !== $id) {
                $this->setFlash('error', 'Доступ заборонено');
                $this->redirect('/dashboard');
            }
        }

        if ($userRole === 'teacher') {
            $userModel = new User();
            $teacherGroups = $userModel->getTeacherGroups($userId);
            $hasAccess = false;
            foreach ($teacherGroups as $tg) {
                if ((int)$tg['id'] === $id) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                $this->setFlash('error', 'Доступ заборонено');
                $this->redirect('/admin/groups');
            }
        }

        $studentPage = (int)($_GET['spage'] ?? 1);
        $studentPerPage = 10;
        $allStudents = $groupModel->getStudents($id);
        $totalStudents = count($allStudents);
        $totalStudentPages = ceil($totalStudents / $studentPerPage);
        $students = array_slice($allStudents, ($studentPage - 1) * $studentPerPage, $studentPerPage);

        $filePage = (int)($_GET['fpage'] ?? 1);
        $filePerPage = 10;
        $fileModel = new File();
        $allFiles = $fileModel->getByGroupId($id);
        $totalFiles = count($allFiles);
        $totalFilePages = ceil($totalFiles / $filePerPage);
        $files = array_slice($allFiles, ($filePage - 1) * $filePerPage, $filePerPage);

        $this->view->set('title', 'Перегляд групи: ' . $group['name']);
        $this->view->set('group', $group);
        $this->view->set('students', $students);
        $this->view->set('files', $files);
        $this->view->set('studentPage', $studentPage);
        $this->view->set('totalStudentPages', $totalStudentPages);
        $this->view->set('filePage', $filePage);
        $this->view->set('totalFilePages', $totalFilePages);
        $this->view->set('isAdmin', $userRole === 'admin');
        $this->view->set('isStudent', $userRole === 'student');
        $this->view->render('admin/group_view');
    }

    /**
     * Видалити студента з групи
     */
    public function removeStudentFromGroup(int $groupId, int $studentId): void
    {
        $this->requireAnyRole(['admin', 'teacher']);

        if (!Security::validateId($groupId) || !Security::validateId($studentId)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/groups/view/' . $groupId);
        }

        $userModel = new User();
        $user = $userModel->findById($studentId);

        if (!$user || (int)$user['group_id'] !== $groupId) {
            $this->setFlash('error', 'Студента не знайдено в цій групі');
            $this->redirect('/admin/groups/view/' . $groupId);
        }

        $userModel->update($studentId, ['group_id' => null]);
        Logger::audit('student_removed_from_group', Session::get('user_id'), ['group_id' => $groupId, 'student_id' => $studentId]);
        $this->setFlash('success', 'Студента видалено з групи');
        $this->redirect('/admin/groups/view/' . $groupId);
    }

    /**
     * Видалити групу
     */
    public function deleteGroup(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/groups');
        }

        $groupModel = new Group();
        $group = $groupModel->findById($id);

        if (!$group) {
            $this->setFlash('error', 'Групу не знайдено');
            $this->redirect('/admin/groups');
        }

        $students = $groupModel->getStudents($id);
        if (!empty($students)) {
            $this->setFlash('error', 'Неможливо видалити групу: в ній є студенти. Спочатку перемістіть студентів в інші групи.');
            $this->redirect('/admin/groups');
        }

        $groupModel->delete($id);
        Logger::audit('group_deleted', Session::get('user_id'), ['group_id' => $id]);
        $this->setFlash('success', 'Групу видалено');
        $this->redirect('/admin/groups');
    }

    /**
     * Управління предметами
     */
    public function subjects(): void
    {
        $this->requireAnyRole(['admin', 'teacher']);

        $subjectModel = new Subject();
        $userModel = new User();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');
        
        $filters = [
            'search' => $_GET['search'] ?? null
        ];
        
        if ($userRole === 'teacher') {
            $filters['teacher_id'] = $userId;
        }
        
        $subjects = $subjectModel->getAll($page, $perPage, array_filter($filters));
        $total = $subjectModel->count(array_filter($filters));
        $totalPages = ceil($total / $perPage);

        $teachers = [];
        if ($userRole === 'admin') {
            $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
        }

        $this->view->set('title', 'Управління предметами');
        $this->view->set('subjects', $subjects);
        $this->view->set('filters', $filters);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->set('teachers', $teachers);
        $this->view->set('isAdmin', $userRole === 'admin');
        $this->view->render('admin/subjects');
    }

    /**
     * Створити предмет
     */
    public function createSubject(): void
    {
        $this->requireAnyRole(['admin', 'teacher']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/subjects');
            }

            $subjectModel = new Subject();
            $errors = [];

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $errors[] = 'Назва предмета обов\'язкова';
            }

            if (strlen($name) > 100) {
                $errors[] = 'Назва предмета не повинна перевищувати 100 символів';
            }

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('/admin/subjects/create');
            }

            $userRole = Session::get('user_role');
            $teacherId = null;
            
            $teacherIds = [];
            if ($userRole === 'admin' && !empty($_POST['teacher_ids']) && is_array($_POST['teacher_ids'])) {
                $teacherIds = array_map('intval', array_filter($_POST['teacher_ids']));
            } elseif ($userRole === 'teacher') {
                $teacherIds = [Session::get('user_id')];
            }

            try {
                $subjectId = $subjectModel->create([
                    'name' => $name,
                    'description' => $description ?: null,
                    'teacher_id' => null
                ]);

                $subjectModel->syncTeacherAssignment($subjectId, $teacherIds);

                Logger::audit('subject_created', Session::get('user_id'), ['subject_id' => $subjectId]);
                $this->setFlash('success', 'Предмет створено');
                $this->redirect('/admin/subjects');
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000') {
                    $this->setFlash('error', 'Предмет з такою назвою вже існує');
                } else {
                    $this->setFlash('error', 'Помилка при створенні предмета');
                }
                $this->redirect('/admin/subjects/create');
            }
        }

        $userModel = new User();
        $teachers = [];
        $userRole = Session::get('user_role');
        
        if ($userRole === 'admin') {
            $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
        }

        $this->view->set('title', 'Створити предмет');
        $this->view->set('teachers', $teachers);
        $this->view->set('assignedTeacherIds', []);
        $this->view->set('isAdmin', $userRole === 'admin');
        $this->view->render('admin/subject_form');
    }

    /**
     * Редагувати предмет
     */
    public function editSubject(int $id): void
    {
        $this->requireAnyRole(['admin', 'teacher']);

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/subjects');
        }

        $subjectModel = new Subject();
        $subject = $subjectModel->findById($id);
        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');

        if (!$subject) {
            $this->setFlash('error', 'Предмет не знайдено');
            $this->redirect('/admin/subjects');
        }

        if ($userRole === 'teacher') {
            $userModel = new User();
            $teacherSubjects = $userModel->getTeacherSubjects($userId);
            $teacherSubjectIds = array_column($teacherSubjects, 'id');
            if (!in_array($id, $teacherSubjectIds)) {
                $this->setFlash('error', 'Доступ заборонено');
                $this->redirect('/admin/subjects');
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/subjects');
            }

            $errors = [];
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $errors[] = 'Назва предмета обов\'язкова';
            }

            if (strlen($name) > 100) {
                $errors[] = 'Назва предмета не повинна перевищувати 100 символів';
            }

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('/admin/subjects/edit/' . $id);
            }

            $updateData = [
                'name' => $name,
                'description' => $description ?: null
            ];

            $teacherIds = [];
            if ($userRole === 'admin') {
                $teacherIds = !empty($_POST['teacher_ids']) && is_array($_POST['teacher_ids']) 
                    ? array_map('intval', array_filter($_POST['teacher_ids'])) 
                    : [];
                $updateData['teacher_id'] = null;
            }

            $subjectModel->update($id, $updateData);
            
            if ($userRole === 'admin') {
                $subjectModel->syncTeacherAssignment($id, $teacherIds);
            }

            Logger::audit('subject_updated', Session::get('user_id'), ['subject_id' => $id]);
            $this->setFlash('success', 'Предмет оновлено');
            $this->redirect('/admin/subjects');
        }

        $userModel = new User();
        $teachers = [];
        
        if ($userRole === 'admin') {
            $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
            
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT teacher_id FROM teacher_subjects WHERE subject_id = :subject_id");
            $stmt->execute(['subject_id' => $id]);
            $assignedTeacherIds = array_column($stmt->fetchAll(), 'teacher_id');
        } else {
            $assignedTeacherIds = [];
        }

        $this->view->set('title', 'Редагувати предмет');
        $this->view->set('subject', $subject);
        $this->view->set('assignedTeacherIds', $assignedTeacherIds);
        $this->view->set('teachers', $teachers);
        $this->view->set('isAdmin', $userRole === 'admin');
        $this->view->render('admin/subject_form');
    }

    /**
     * Видалити предмет
     */
    public function deleteSubject(int $id): void
    {
        $this->requireAnyRole(['admin', 'teacher']);

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/subjects');
        }

        $subjectModel = new Subject();
        $subject = $subjectModel->findById($id);
        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');

        if (!$subject) {
            $this->setFlash('error', 'Предмет не знайдено');
            $this->redirect('/admin/subjects');
        }

        if ($userRole === 'teacher') {
            $userModel = new User();
            $teacherSubjects = $userModel->getTeacherSubjects($userId);
            $teacherSubjectIds = array_column($teacherSubjects, 'id');
            if (!in_array($id, $teacherSubjectIds)) {
                $this->setFlash('error', 'Доступ заборонено');
                $this->redirect('/admin/subjects');
            }
        }

        $testModel = new Test();
        $tests = $testModel->getAll(1, 1, ['subject_id' => $id]);
        if (!empty($tests)) {
            $this->setFlash('error', 'Неможливо видалити предмет: до нього прив\'язані тести. Спочатку видаліть або змініть тести.');
            $this->redirect('/admin/subjects');
        }

        $subjectModel->delete($id);
        Logger::audit('subject_deleted', Session::get('user_id'), ['subject_id' => $id]);
        $this->setFlash('success', 'Предмет видалено');
        $this->redirect('/admin/subjects');
    }

    /**
     * Управління тестами (перегляд всіх тестів)
     */
    public function tests(): void
    {
        $this->requireRole('admin');

        $testModel = new Test();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $filters = [
            'subject_id' => !empty($_GET['subject_id']) ? (int)$_GET['subject_id'] : null,
            'created_by' => !empty($_GET['created_by']) ? (int)$_GET['created_by'] : null,
            'is_published' => isset($_GET['is_published']) && $_GET['is_published'] !== '' ? (int)$_GET['is_published'] : null
        ];

        $tests = $testModel->getAll($page, $perPage, array_filter($filters, fn($v) => $v !== null));
        $total = $testModel->count(array_filter($filters, fn($v) => $v !== null));
        $totalPages = ceil($total / $perPage);
        
        $userModel = new User();
        $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
        
        $subjectModel = new Subject();
        $subjects = $subjectModel->getAll(1, 1000);

        $this->view->set('title', 'Управління тестами');
        $this->view->set('tests', $tests);
        $this->view->set('teachers', $teachers);
        $this->view->set('subjects', $subjects);
        $this->view->set('filters', $filters);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->render('admin/tests');
    }

    /**
     * Видалити тест (адміністратор)
     */
    public function deleteTest(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($id);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
        }

        $testModel->delete($id);
        Logger::audit('test_deleted', Session::get('user_id'), ['test_id' => $id, 'deleted_by' => 'admin']);
        $this->setFlash('success', 'Тест видалено');
        $this->redirect('/admin/tests');
    }

    /**
     * Створити тест (адміністратор)
     */
    public function createTest(): void
    {
        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/tests');
            }

            $testModel = new Test();
            $userId = Session::get('user_id');
            $createdBy = !empty($_POST['created_by']) ? (int)$_POST['created_by'] : $userId;

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
                'created_by' => $createdBy
            ]);

            Logger::audit('test_created', $userId, ['test_id' => $testId, 'created_by_admin' => true]);
                $this->setFlash('success', 'Тест створено');
            $this->redirect('/admin/tests/edit/' . $testId);
        }

        $subjectModel = new Subject();
        $subjects = $subjectModel->getAll();

        $userModel = new User();
        $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
        $admins = $userModel->getAll(1, 1000, ['role' => 'admin']);
        $creators = array_merge($admins, $teachers);

        $this->view->set('title', 'Створити тест');
        $this->view->set('subjects', $subjects);
        $this->view->set('teachers', $creators);
        $this->view->set('isAdmin', true);
        $this->view->render('teacher/test_form');
    }

    /**
     * Редагувати тест (адміністратор)
     */
    public function editTest(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($id);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/tests');
            }

            $updateData = [
                'title' => $_POST['title'] ?? $test['title'],
                'description' => $_POST['description'] ?? $test['description'],
                'subject_id' => !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null,
                'duration' => !empty($_POST['duration']) ? (int)$_POST['duration'] : null,
                'max_attempts' => !empty($_POST['max_attempts']) ? (int)$_POST['max_attempts'] : 1,
                'passing_score' => !empty($_POST['passing_score']) ? (float)$_POST['passing_score'] : 60,
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];
            
            if (!empty($_POST['created_by'])) {
                $updateData['created_by'] = (int)$_POST['created_by'];
            }
            
            $testModel->update($id, $updateData);

            Logger::audit('test_updated', Session::get('user_id'), ['test_id' => $id, 'updated_by_admin' => true]);
            $this->setFlash('success', 'Тест оновлено');
            $this->redirect('/admin/tests/edit/' . $id);
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

        $userModel = new User();
        $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);
        $admins = $userModel->getAll(1, 1000, ['role' => 'admin']);
        $creators = array_merge($admins, $teachers);

        $this->view->set('title', 'Редагувати тест');
        $this->view->set('test', $test);
        $this->view->set('questions', $questions);
        $this->view->set('questionPage', $questionPage);
        $this->view->set('totalQuestionPages', $totalQuestionPages);
        $this->view->set('totalQuestions', $totalQuestions);
        $this->view->set('questionSearch', $questionSearch);
        $this->view->set('questionPerPage', $questionPerPage);
        $this->view->set('subjects', $subjects);
        $this->view->set('teachers', $creators);
        $this->view->set('isAdmin', true);
        $this->view->render('teacher/test_form');
    }

    /**
     * Додати питання до тесту (адміністратор)
     */
    public function addQuestion(int $testId): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/tests/edit/' . $testId);
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
                $this->redirect('/admin/tests/edit/' . $testId);
            }

            $questionId = $questionModel->create([
                'test_id' => $testId,
                'question_text' => $_POST['question_text'] ?? '',
                'question_type' => $questionType,
                'points' => !empty($_POST['points']) ? (float)$_POST['points'] : 1,
                'order_index' => !empty($_POST['order_index']) ? (int)$_POST['order_index'] : 0,
                'options' => $options
            ]);

            Logger::audit('question_created', Session::get('user_id'), ['test_id' => $testId, 'question_id' => $questionId, 'created_by_admin' => true]);
            $this->setFlash('success', 'Питання додано');
            $this->redirect('/admin/tests/edit/' . $testId);
        }

        $this->view->set('title', 'Додати питання');
        $this->view->set('test', $test);
        $this->view->set('isAdmin', true);
        $this->view->render('teacher/question_form');
    }

    /**
     * Редагувати питання (адміністратор)
     */
    public function editQuestion(int $testId, int $questionId): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($testId) || !Security::validateId($questionId)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
        }

        $questionModel = new Question();
        $question = $questionModel->findWithOptions($questionId);

        if (!$question || (int)$question['test_id'] !== $testId) {
            $this->setFlash('error', 'Питання не знайдено');
            $this->redirect('/admin/tests/edit/' . $testId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/tests/edit/' . $testId);
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
                $this->redirect('/admin/tests/edit/' . $testId);
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
                Logger::audit('question_updated_recalculated', Session::get('user_id'), [
                    'test_id' => $testId, 
                    'question_id' => $questionId,
                    'points_changed' => $pointsChanged,
                    'options_changed' => $optionsChanged,
                    'old_points' => $oldPoints,
                    'new_points' => $newPoints,
                    'attempts_recalculated' => $recalculatedCount,
                    'updated_by_admin' => true
                ]);
            }

            Logger::audit('question_updated', Session::get('user_id'), ['test_id' => $testId, 'question_id' => $questionId, 'updated_by_admin' => true]);
            $flashMessage = 'Питання оновлено';
            if ($pointsChanged || $optionsChanged) {
                $flashMessage .= '. Перераховано спроб: ' . ($recalculatedCount ?? 0);
            }
            $this->setFlash('success', $flashMessage);
            $this->redirect('/admin/tests/edit/' . $testId);
        }

        $this->view->set('title', 'Редагувати питання');
        $this->view->set('test', $test);
        $this->view->set('question', $question);
        $this->view->set('isAdmin', true);
        $this->view->render('teacher/question_edit_form');
    }

    /**
     * Видалити питання (адміністратор)
     */
    public function deleteQuestion(int $testId, int $questionId): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($testId) || !Security::validateId($questionId)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
        }

        $questionModel = new Question();
        $question = $questionModel->findById($questionId);

        if (!$question || (int)$question['test_id'] !== $testId) {
            $this->setFlash('error', 'Питання не знайдено');
            $this->redirect('/admin/tests/edit/' . $testId);
        }

        $questionModel->delete($questionId);
        Logger::audit('question_deleted', Session::get('user_id'), ['test_id' => $testId, 'question_id' => $questionId, 'deleted_by_admin' => true]);
        $this->setFlash('success', 'Питання видалено');
        $this->redirect('/admin/tests/edit/' . $testId);
    }

    /**
     * Призначити тест групам/студентам (адміністратор)
     */
    public function assignTest(int $testId): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/tests/assign/' . $testId);
            }

            $db = Database::getInstance();

            $stmt = $db->prepare("DELETE FROM test_assignments WHERE test_id = :test_id");
            $stmt->execute(['test_id' => $testId]);

            $stmt = $db->prepare("INSERT INTO test_assignments (test_id, user_id, group_id, created_at) VALUES (:test_id, :user_id, :group_id, NOW())");

            $groupIds = $_POST['group_ids'] ?? [];
            foreach ($groupIds as $groupId) {
                $stmt->execute([
                    'test_id' => $testId,
                    'user_id' => null,
                    'group_id' => (int)$groupId
                ]);
            }

            $userIds = $_POST['user_ids'] ?? [];
            foreach ($userIds as $userId) {
                $stmt->execute([
                    'test_id' => $testId,
                    'user_id' => (int)$userId,
                    'group_id' => null
                ]);
            }

            Logger::audit('test_assigned', Session::get('user_id'), ['test_id' => $testId, 'assigned_by_admin' => true]);
            $this->setFlash('success', 'Тест призначено');
            $this->redirect('/admin/tests');
        }

        $groupModel = new Group();
        $groups = $groupModel->getAll();

        $userModel = new User();
        $studentsData = $userModel->getAll(1, 1000, ['role' => 'student']);
        
        $db = Database::getInstance();
        $students = [];
        foreach ($studentsData as $student) {
            if (!empty($student['group_id'])) {
                $groupStmt = $db->prepare("SELECT name FROM groups WHERE id = :group_id");
                $groupStmt->execute(['group_id' => $student['group_id']]);
                $group = $groupStmt->fetch();
                $student['group_name'] = $group ? $group['name'] : null;
            } else {
                $student['group_name'] = null;
            }
            $students[] = $student;
        }

        $db = Database::getInstance();
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
        $this->view->set('isAdmin', true);
        $this->view->render('teacher/assign_test');
    }

    /**
     * Статистика по тесту (адміністратор)
     */
    public function testStats(int $testId): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
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
        
        $userModel = new User();
        $students = $userModel->getAll(1, 1000, ['role' => 'student']);

        $this->view->set('title', 'Статистика теста');
        $this->view->set('test', $test);
        $this->view->set('attempts', $attempts);
        $this->view->set('stats', $stats);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->set('filters', $filters);
        $this->view->set('students', $students);
        $this->view->set('isAdmin', true);
        $this->view->render('teacher/test_stats');
    }

    /**
     * Експорт статистики тесту (адміністратор)
     */
    public function exportTestStats(int $testId): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($testId)) {
            $this->setFlash('error', 'Невірний ID тесту');
            $this->redirect('/admin/tests');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
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
        
        $this->redirect('/admin/tests/' . $testId . '/stats');
    }

    /**
     * Видалити спробу (адміністратор)
     */
    public function deleteAttempt(int $testId, int $attemptId): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($testId) || !Security::validateId($attemptId)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/tests/' . $testId . '/stats');
        }

        $testModel = new Test();
        $test = $testModel->findById($testId);

        if (!$test) {
            $this->setFlash('error', 'Тест не знайдено');
            $this->redirect('/admin/tests');
        }

        $attemptModel = new \App\Models\Attempt();
        $attempt = $attemptModel->findById($attemptId);

        if (!$attempt || (int)$attempt['test_id'] !== $testId) {
            $this->setFlash('error', 'Спробу не знайдено');
            $this->redirect('/admin/tests/' . $testId . '/stats');
        }

        if ($attemptModel->delete($attemptId)) {
            Logger::audit('attempt_deleted', Session::get('user_id'), [
                'attempt_id' => $attemptId,
                'test_id' => $testId,
                'user_id' => $attempt['user_id']
            ]);
            $this->setFlash('success', 'Спробу видалено');
        } else {
            $this->setFlash('error', 'Не вдалося видалити спробу');
        }

        $this->redirect('/admin/tests/' . $testId . '/stats');
    }

    /**
     * Управління файлами (адміністратор)
     */
    public function files(): void
    {
        $this->requireRole('admin');

        $fileModel = new File();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->getItemsPerPage();
        $filters = [
            'uploaded_by' => !empty($_GET['uploaded_by']) ? (int)$_GET['uploaded_by'] : null,
            'search' => !empty($_GET['search']) ? trim($_GET['search']) : null
        ];

        $files = $fileModel->getAll($page, $perPage, array_filter($filters));
        $total = $fileModel->count(array_filter($filters));
        $totalPages = ceil($total / $perPage);

        $userModel = new User();
        $teachers = $userModel->getAll(1, 1000, ['role' => 'teacher']);

        $this->view->set('title', 'Управління файлами');
        $this->view->set('files', $files);
        $this->view->set('filters', $filters);
        $this->view->set('teachers', $teachers);
        $this->view->set('page', $page);
        $this->view->set('totalPages', $totalPages);
        $this->view->render('admin/files');
    }

    /**
     * Завантажити файл (адміністратор)
     */
    public function uploadFile(): void
    {
        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/files');
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'Файл перевищує максимальний розмір, встановлений у php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'Файл перевищує максимальний розмір, встановлений у формі',
                    UPLOAD_ERR_PARTIAL => 'Файл був завантажений частково',
                    UPLOAD_ERR_NO_FILE => 'Файл не був завантажений',
                    UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова папка',
                    UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск',
                    UPLOAD_ERR_EXTENSION => 'Завантаження файлу було зупинено розширенням'
                ];
                $error = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
                $this->setFlash('error', $errorMessages[$error] ?? 'Помилка завантаження файлу');
                $this->redirect('/admin/files');
            }

            try {
                $userIds = !empty($_POST['user_ids']) && is_array($_POST['user_ids']) 
                    ? array_map('intval', array_filter($_POST['user_ids'])) 
                    : [];
                $groupIds = !empty($_POST['group_ids']) && is_array($_POST['group_ids']) 
                    ? array_map('intval', array_filter($_POST['group_ids'])) 
                    : [];

                $fileService = new FileService();
                $fileService->uploadFile($_FILES['file'], $userIds, $groupIds);
                
                $this->setFlash('success', 'Файл успішно завантажено');
            } catch (\RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                Logger::error('File upload error', ['error' => $e->getMessage()]);
                $this->setFlash('error', 'Помилка при завантаженні файлу');
            }
            
            $this->redirect('/admin/files');
        }
        
        $userModel = new User();
        $allStudents = $userModel->getAll(1, 1000, ['role' => 'student']);
        
        $students = [];
        foreach ($allStudents as $student) {
            $studentData = $student;
            if (!empty($student['group_id'])) {
                $groupModel = new Group();
                $group = $groupModel->findById((int)$student['group_id']);
                $studentData['group_name'] = $group ? $group['name'] : null;
            }
            $students[] = $studentData;
        }
        
        $groupModel = new Group();
        $groups = $groupModel->getAll(1, 1000);

        $this->view->set('title', 'Завантажити файл');
        $this->view->set('students', $students);
        $this->view->set('groups', $groups);
        $this->view->render('admin/file_upload');
    }

    /**
     * Видалити файл (адміністратор)
     */
    public function deleteFile(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/files');
        }

        try {
            $fileService = new FileService();
            $userId = Session::get('user_id');
            
            if (!$fileService->canAccessFile($id, $userId, 'admin')) {
                $this->setFlash('error', 'Доступ заборонено');
                $this->redirect('/admin/files');
            }
            
            if ($fileService->deleteFile($id, $userId)) {
                $this->setFlash('success', 'Файл видалено');
            } else {
                $this->setFlash('error', 'Помилка при видаленні файлу');
            }
        } catch (\Exception $e) {
            Logger::error('File delete error', ['error' => $e->getMessage(), 'file_id' => $id]);
            $this->setFlash('error', 'Ошибка при удалении файла');
        }
        
        $this->redirect('/admin/files');
    }

    /**
     * Завантажити файл
     */
    public function downloadFile(int $id): void
    {
        $this->requireAnyRole(['admin', 'teacher', 'student']);

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/dashboard');
        }

        $fileModel = new File();
        $file = $fileModel->findById($id);

        if (!$file) {
            $this->setFlash('error', 'Файл не знайдено');
            $this->redirect('/dashboard');
        }

        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');

        if ($userRole === 'student') {
            $availableFiles = $fileModel->getAvailableForStudent($userId, 1, 10000);
            $hasAccess = false;
            foreach ($availableFiles as $availableFile) {
                if ((int)$availableFile['id'] === $id) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                $this->setFlash('error', 'Доступ заборонено');
                $this->redirect('/dashboard');
            }
        } elseif ($userRole === 'teacher') {
            if ((int)$file['uploaded_by'] !== $userId) {
                $this->setFlash('error', 'Доступ заборонено');
                $this->redirect('/teacher/files');
            }
        }

        if (!file_exists($file['file_path'])) {
            $this->setFlash('error', 'Файл не знайдено на сервері');
            $this->redirect('/dashboard');
        }

        $filename = $file['original_name'];
        $filename = preg_replace('/[\/\\\\:\*\?"<>\|]/', '_', $filename);
        $filename = preg_replace('/\.\./', '_', $filename);
        $filename = trim($filename);
        if (empty($filename)) {
            $filename = 'file';
        }

        $filenameSafe = rawurlencode($filename);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . $filenameSafe);
        header('Content-Length: ' . filesize($file['file_path']));
        readfile($file['file_path']);
        exit;
    }

    /**
     * Редагувати призначення файлу (адміністратор)
     */
    public function editFile(int $id): void
    {
        $this->requireRole('admin');

        if (!Security::validateId($id)) {
            $this->setFlash('error', 'Невірний ID');
            $this->redirect('/admin/files');
        }

        $fileModel = new File();
        $file = $fileModel->findById($id);

        if (!$file) {
            $this->setFlash('error', 'Файл не знайдено');
            $this->redirect('/admin/files');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = Security::getCsrfTokenFromRequest();
            if (!$token || !Security::validateCsrfToken($token)) {
                $this->setFlash('error', 'Ошибка безопасности');
                $this->redirect('/admin/files');
            }

            $userIds = !empty($_POST['user_ids']) && is_array($_POST['user_ids']) 
                ? array_map('intval', array_filter($_POST['user_ids'])) 
                : [];
            $groupIds = !empty($_POST['group_ids']) && is_array($_POST['group_ids']) 
                ? array_map('intval', array_filter($_POST['group_ids'])) 
                : [];

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
                $stmt->execute(['file_id' => $id   ]);
            }
            
            $userModel = new User();
            $uploader = $userModel->findById((int)$file['uploaded_by']);
            $uploaderName = $uploader ? ($uploader['first_name'] . ' ' . $uploader['last_name']) : 'Адміністратор';
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
                            Logger::error('Failed to send file shared notification (edit)', [
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
                                Logger::error('Failed to send file shared notification to group student (edit)', [
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

            Logger::audit('file_assignments_updated', Session::get('user_id'), ['file_id' => $id]);
                $this->setFlash('success', 'Призначення файлу оновлено');
            $this->redirect('/admin/files');
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
        
        $userModel = new User();
        $allStudents = $userModel->getAll(1, 1000, ['role' => 'student']);
        
        $students = [];
        foreach ($allStudents as $student) {
            $studentData = $student;
            if (!empty($student['group_id'])) {
                $groupModel = new Group();
                $group = $groupModel->findById((int)$student['group_id']);
                $studentData['group_name'] = $group ? $group['name'] : null;
            }
            $students[] = $studentData;
        }
        
        $groupModel = new Group();
        $groups = $groupModel->getAll(1, 1000);

        $this->view->set('title', 'Редагувати призначення файлу');
        $this->view->set('file', $file);
        $this->view->set('students', $students);
        $this->view->set('groups', $groups);
        $this->view->set('assignedUserIds', $assignedUserIds);
        $this->view->set('assignedGroupIds', $assignedGroupIds);
        $this->view->render('admin/file_edit');
    }

    /**
     * Управління налаштуваннями
     */
    public function settings(): void
    {
        $this->requireRole('admin');

        $settingsModel = new Settings();
        $settings = $settingsModel->getAll();

        $settingsData = [];
        foreach ($settings as $setting) {
            $settingsData[$setting['key']] = [
                'value' => $setting['value'],
                'type' => $setting['type'],
                'description' => $setting['description']
            ];
        }

        $this->view->set('title', 'Налаштування системи');
        $this->view->set('settings', $settingsData);
        $this->view->render('admin/settings');
    }

    /**
     * Зберегти налаштування
     */
    public function saveSettings(): void
    {
        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/settings');
        }

        $token = Security::getCsrfTokenFromRequest();
        if (!$token || !Security::validateCsrfToken($token)) {
            $this->setFlash('error', 'Помилка безпеки');
            $this->redirect('/admin/settings');
        }

        $settingsModel = new Settings();
        $settingsToUpdate = [];

        $settingsToUpdate['site_name'] = [
            'value' => trim($_POST['site_name'] ?? 'Система онлайн-тестування'),
            'type' => 'string',
            'description' => 'Назва сайту'
        ];

        $settingsToUpdate['allowed_file_types'] = [
            'value' => trim($_POST['allowed_file_types'] ?? ''),
            'type' => 'string',
            'description' => 'Дозволені типи файлів (через кому)'
        ];

        $maxFileSizeMB = (int)($_POST['max_file_size'] ?? 10);
        $maxFileSizeMB = max(1, min(25, $maxFileSizeMB));
        $settingsToUpdate['max_file_size'] = [
            'value' => $maxFileSizeMB * 1048576,
            'type' => 'integer',
            'description' => 'Максимальний розмір файлу в байтах'
        ];

        $itemsPerPage = (int)($_POST['items_per_page'] ?? 10);
        $itemsPerPage = max(1, min(25, $itemsPerPage));
        $settingsToUpdate['items_per_page'] = [
            'value' => $itemsPerPage,
            'type' => 'integer',
            'description' => 'Кількість елементів на сторінці за замовчуванням'
        ];

        $settingsToUpdate['smtp_enabled'] = [
            'value' => isset($_POST['smtp_enabled']) ? 1 : 0,
            'type' => 'boolean',
            'description' => 'Увімкнути SMTP для відправки листів'
        ];

        $settingsToUpdate['smtp_host'] = [
            'value' => trim($_POST['smtp_host'] ?? ''),
            'type' => 'string',
            'description' => 'SMTP хост'
        ];

        $settingsToUpdate['smtp_port'] = [
            'value' => (int)($_POST['smtp_port'] ?? 587),
            'type' => 'integer',
            'description' => 'SMTP порт'
        ];

        $settingsToUpdate['smtp_username'] = [
            'value' => trim($_POST['smtp_username'] ?? ''),
            'type' => 'string',
            'description' => 'SMTP ім\'я користувача'
        ];

        if (!empty($_POST['smtp_password'])) {
            $settingsToUpdate['smtp_password'] = [
                'value' => trim($_POST['smtp_password']),
                'type' => 'string',
                'description' => 'SMTP пароль'
            ];
        }

        $settingsToUpdate['smtp_encryption'] = [
            'value' => trim($_POST['smtp_encryption'] ?? 'tls'),
            'type' => 'string',
            'description' => 'SMTP шифрування (tls/ssl)'
        ];

        $settingsToUpdate['smtp_from_email'] = [
            'value' => trim($_POST['smtp_from_email'] ?? ''),
            'type' => 'string',
            'description' => 'Email відправника'
        ];

        $settingsToUpdate['smtp_from_name'] = [
            'value' => trim($_POST['smtp_from_name'] ?? ''),
            'type' => 'string',
            'description' => 'Ім\'я відправника'
        ];

        $settingsToUpdate['maintenance_mode'] = [
            'value' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'type' => 'boolean',
            'description' => 'Режим обслуговування'
        ];

        $settingsToUpdate['registration_enabled'] = [
            'value' => isset($_POST['registration_enabled']) ? 1 : 0,
            'type' => 'boolean',
            'description' => 'Дозволити реєстрацію користувачів. Якщо вимкнено, тільки адміністратори зможуть створювати нових користувачів'
        ];

        $colorSettings = [
            'color_primary' => 'Основний колір (Primary)',
            'color_primary_dark' => 'Темний основний колір (Primary Dark)',
            'color_primary_light' => 'Світлий основний колір (Primary Light)',
            'color_secondary' => 'Вторинний колір (Secondary)',
            'color_success' => 'Колір успіху (Success)',
            'color_danger' => 'Колір небезпеки (Danger)',
            'color_warning' => 'Колір попередження (Warning)',
            'color_info' => 'Інформаційний колір (Info)'
        ];

        $defaultColors = [
            'color_primary' => '#6366F1',
            'color_primary_dark' => '#4F46E5',
            'color_primary_light' => '#818CF8',
            'color_secondary' => '#64748B',
            'color_success' => '#10B981',
            'color_danger' => '#EF4444',
            'color_warning' => '#F59E0B',
            'color_info' => '#06B6D4'
        ];

        foreach ($colorSettings as $key => $description) {
            $colorValue = trim($_POST[$key] ?? '');
            if (!empty($colorValue)) {
                if (preg_match('/^#[0-9A-Fa-f]{6}$/', $colorValue)) {
                    $settingsToUpdate[$key] = [
                        'value' => strtoupper($colorValue),
                        'type' => 'string',
                        'description' => $description
                    ];
                } else {
                    $this->setFlash('error', "Невірний формат кольору для {$description}. Використовуйте формат #000000");
                    $this->redirect('/admin/settings');
                    return;
                }
            } else {
                $settingsToUpdate[$key] = [
                    'value' => $defaultColors[$key] ?? '#000000',
                    'type' => 'string',
                    'description' => $description
                ];
            }
        }

        try {
            if ($settingsModel->updateMultiple($settingsToUpdate)) {
                $colorKeys = ['color_primary', 'color_primary_dark', 'color_primary_light', 
                             'color_secondary', 'color_success', 'color_danger', 
                             'color_warning', 'color_info'];
                $hasColorChanges = !empty(array_intersect($colorKeys, array_keys($settingsToUpdate)));
                
                if ($hasColorChanges) {
                    $settingsModel->set('css_version', time(), 'integer', 'Версія CSS для інвалідації кешу');
                }
                
                Logger::audit('settings_updated', Session::get('user_id'), ['settings' => array_keys($settingsToUpdate)]);
                $this->setFlash('success', 'Налаштування успішно збережено');
            } else {
                Logger::error('settings_save_failed', [
                    'user_id' => Session::get('user_id'),
                    'settings_count' => count($settingsToUpdate)
                ]);
                $this->setFlash('error', 'Помилка при збереженні налаштувань. Перевірте логи для деталей.');
            }
        } catch (\Exception $e) {
            Logger::error('settings_save_exception', [
                'user_id' => Session::get('user_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->setFlash('error', 'Помилка при збереженні налаштувань: ' . $e->getMessage());
        }

        $this->redirect('/admin/settings');
    }
}
