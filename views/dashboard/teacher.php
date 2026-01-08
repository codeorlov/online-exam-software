<?php
/** @var \App\Core\View $this */
?>

<div class="section-header">
    <h2>
        <i class="bi bi-file-text"></i>
        Управління тестами
    </h2>
    <a href="/teacher/tests/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Створити тест
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-3 flex-grow-1">
        <div class="col-md-4">
            <select name="subject_id" class="form-select">
                <option value="">Всі предмети</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>" <?= (isset($_GET['subject_id']) && (int)$_GET['subject_id'] == $subject['id']) ? 'selected' : '' ?>>
                        <?= $this->e($subject['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select name="is_published" class="form-select">
                <option value="">Всі статуси</option>
                <option value="1" <?= (isset($_GET['is_published']) && $_GET['is_published'] === '1') ? 'selected' : '' ?>>Опубліковані</option>
                <option value="0" <?= (isset($_GET['is_published']) && $_GET['is_published'] === '0') ? 'selected' : '' ?>>Чернетки</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-funnel me-2"></i>Фільтр
            </button>
            <a href="/dashboard/teacher" class="btn btn-outline-secondary">Скинути</a>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Назва</th>
                <th>Предмет</th>
                <th>Статус</th>
                <th>Спроб</th>
                <th>Дата створення</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tests)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">Тести не знайдено</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><?= $test['id'] ?></td>
                        <td><?= $this->e($test['title']) ?></td>
                        <td><?= $this->e($test['subject_name'] ?? 'Без предмета') ?></td>
                        <td>
                            <?php if ($test['is_published']): ?>
                                <span class="badge bg-success">Опубліковано</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Чернетка</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $attemptsCount = $attemptModel->countByTestId($test['id']);
                            echo $attemptsCount;
                            ?>
                        </td>
                        <td><?= date('d.m.Y', strtotime($test['created_at'])) ?></td>
                        <td>
                            <a href="/teacher/tests/edit/<?= $test['id'] ?>" class="btn btn-sm btn-primary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/teacher/tests/<?= $test['id'] ?>/stats" class="btn btn-sm btn-success" title="Статистика">
                                <i class="bi bi-graph-up"></i>
                            </a>
                            <a href="/teacher/tests/<?= $test['id'] ?>/assign" class="btn btn-sm btn-warning" title="Призначити">
                                <i class="bi bi-person-check"></i>
                            </a>
                            <a href="/teacher/tests/delete/<?= $test['id'] ?>" 
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

<?php if (isset($totalPages) && $totalPages > 1): ?>
    <nav aria-label="Навігація по сторінкам">
        <ul class="pagination justify-content-center mt-4">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&subject_id=<?= $_GET['subject_id'] ?? '' ?>&is_published=<?= $_GET['is_published'] ?? '' ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&subject_id=<?= $filters['subject_id'] ?? '' ?>&is_published=<?= $filters['is_published'] ?? '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&subject_id=<?= $filters['subject_id'] ?? '' ?>&is_published=<?= $filters['is_published'] ?? '' ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
