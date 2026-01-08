<?php
/** @var \App\Core\View $this */
?>

<div class="section-header">
    <h2>
        <i class="bi bi-graph-up"></i>
        Статистика теста: <?= $this->e($test['title']) ?>
    </h2>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card stats-card text-primary">
            <div class="card-body">
                <h3><?= $stats['total_attempts'] ?></h3>
                <p class="card-text">Всього спроб</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-success">
            <div class="card-body">
                <h3><?= $stats['completed'] ?></h3>
                <p class="card-text">Завершено</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-info">
            <div class="card-body">
                <h3><?= $stats['passed'] ?></h3>
                <p class="card-text">Пройдено</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-warning">
            <div class="card-body">
                <h3><?= $stats['average_score'] ?></h3>
                <p class="card-text">Середній бал</p>
            </div>
        </div>
    </div>
</div>

<div class="section-header">
    <h2>
        <i class="bi bi-list-ul"></i>
        Деталі спроб
    </h2>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-3 flex-grow-1">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Всі статуси</option>
                <option value="completed" <?= (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'selected' : '' ?>>Завершені</option>
                <option value="in_progress" <?= (isset($_GET['status']) && $_GET['status'] === 'in_progress') ? 'selected' : '' ?>>В процесі</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="user_id" class="form-select">
                <option value="">Всі студенти</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>" <?= (isset($_GET['user_id']) && (int)$_GET['user_id'] == $student['id']) ? 'selected' : '' ?>>
                        <?= $this->e($student['first_name'] . ' ' . $student['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-funnel me-2"></i>Фільтр
            </button>
            <a href="<?= isset($isAdmin) && $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $test['id'] ?>/stats" class="btn btn-outline-secondary">Скинути</a>
        </div>
        <div class="col-md-3 d-flex justify-content-end">
            <a href="<?= isset($isAdmin) && $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $test['id'] ?>/stats/export?format=csv<?= !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?><?= !empty($_GET['user_id']) ? '&user_id=' . $_GET['user_id'] : '' ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> Експорт CSV
            </a>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Студент</th>
                <th>Email</th>
                <th>Дата початку</th>
                <th>Статус</th>
                <th>Бали</th>
                <th>Результат</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attempts)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">Результати не знайдено</td>
                </tr>
            <?php else: ?>
                <?php foreach ($attempts as $attempt): ?>
                    <tr>
                        <td><?= $this->e($attempt['first_name'] . ' ' . $attempt['last_name']) ?></td>
                        <td><?= $this->e($attempt['email']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($attempt['started_at'])) ?></td>
                        <td>
                            <?php if ($attempt['status'] === 'completed'): ?>
                                <span class="badge bg-success">Завершено</span>
                            <?php else: ?>
                                <span class="badge bg-warning">В процесі</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($attempt['status'] === 'completed'): ?>
                                <?= $attempt['score'] ?> / <?= $attempt['max_score'] ?>
                                <?php if ($attempt['max_score'] > 0): ?>
                                    (<?= round(($attempt['score'] / $attempt['max_score']) * 100, 1) ?>%)
                                <?php else: ?>
                                    (0%)
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($attempt['status'] === 'completed'): ?>
                                <?php
                                $percentage = $attempt['max_score'] > 0 
                                    ? ($attempt['score'] / $attempt['max_score']) * 100 
                                    : 0;
                                ?>
                                <?php if ($percentage >= (float)$test['passing_score']): ?>
                                    <span class="badge bg-success">Пройдено</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Не пройдено</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($attempt['status'] === 'completed'): ?>
                                <a href="/test/<?= $test['id'] ?>/result/<?= $attempt['id'] ?>" class="btn btn-sm btn-info" title="Перегляд результатів">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="/test/<?= $test['id'] ?>/result/<?= $attempt['id'] ?>/edit" class="btn btn-sm btn-primary" title="Редагувати результат">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                            <a href="<?= isset($isAdmin) && $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $test['id'] ?>/attempts/delete/<?= $attempt['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Видалити спробу"
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

<?php if (isset($totalPages) && $totalPages > 1): ?>
    <nav aria-label="Навігація по сторінкам">
        <ul class="pagination justify-content-center mt-4">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&user_id=<?= $_GET['user_id'] ?? '' ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&user_id=<?= $_GET['user_id'] ?? '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($filters['status'] ?? '') ?>&user_id=<?= $filters['user_id'] ?? '' ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<div class="mt-4">
    <a href="<?= isset($isAdmin) && $isAdmin ? '/admin/tests' : '/teacher/tests' ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Повернутися до списку тестів
    </a>
</div>
