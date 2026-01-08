<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
?>

<div class="section-header">
    <h2>
        <i class="bi bi-people"></i>
        Управління користувачами
    </h2>
    <a href="/admin/users/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Створити користувача
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-3 flex-grow-1">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Пошук..." value="<?= $this->e($_GET['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">Всі ролі</option>
                <option value="admin" <?= (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : '' ?>>Адміністратор</option>
                <option value="teacher" <?= (isset($_GET['role']) && $_GET['role'] === 'teacher') ? 'selected' : '' ?>>Вчитель</option>
                <option value="student" <?= (isset($_GET['role']) && $_GET['role'] === 'student') ? 'selected' : '' ?>>Студент</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Всі статуси</option>
                <option value="active" <?= (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : '' ?>>Активні</option>
                <option value="banned" <?= (isset($_GET['status']) && $_GET['status'] === 'banned') ? 'selected' : '' ?>>Заблоковані</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-funnel me-2"></i>Фільтр
            </button>
            <a href="/admin/users" class="btn btn-outline-secondary">Скинути</a>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ім'я</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Статус</th>
                <th>Дата реєстрації</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">Результати не знайдено</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= $this->e($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td><?= $this->e($user['email']) ?></td>
                        <td>
                            <?php
                            $roleNames = ['admin' => 'Адміністратор', 'teacher' => 'Вчитель', 'student' => 'Студент'];
                            echo $roleNames[$user['role']] ?? $user['role'];
                            ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge bg-success">Активний</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Заблокований</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <a href="/admin/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-primary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/admin/users/delete/<?= $user['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Видалити"
                               data-confirm-delete>
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&role=<?= $filters['role'] ?? '' ?>&status=<?= $filters['status'] ?? '' ?>&search=<?= urlencode($filters['search'] ?? '') ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
