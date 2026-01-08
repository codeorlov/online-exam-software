<?php
/** @var \App\Core\View $this */
?>

<h2 class="mb-4">Мої тести</h2>

<div class="mb-3">
    <a href="/teacher/tests/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Створити тест
    </a>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Назва</th>
                <th>Предмет</th>
                <th>Статус</th>
                <th>Питань</th>
                <th>Спроб</th>
                <th>Дата створення</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tests)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">У вас поки немає тестів</td>
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
                            $questionModel = new \App\Models\Question();
                            $questions = $questionModel->getByTestIdWithOptions($test['id']);
                            echo count($questions);
                            ?>
                        </td>
                        <td>
                            <?php
                            $attemptModel = new \App\Models\Attempt();
                            $attemptsCount = $attemptModel->countByTestId($test['id']);
                            echo $attemptsCount;
                            ?>
                        </td>
                        <td><?= date('d.m.Y', strtotime($test['created_at'])) ?></td>
                        <td>
                            <a href="/teacher/tests/edit/<?= $test['id'] ?>" class="btn btn-sm btn-outline-primary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/teacher/tests/<?= $test['id'] ?>/stats" class="btn btn-sm btn-outline-success" title="Статистика">
                                <i class="bi bi-graph-up"></i>
                            </a>
                            <a href="/teacher/tests/<?= $test['id'] ?>/assign" class="btn btn-sm btn-outline-warning" title="Призначити">
                                <i class="bi bi-person-plus"></i>
                            </a>
                            <a href="/teacher/tests/delete/<?= $test['id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
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
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
