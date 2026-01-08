<?php
/** @var \App\Core\View $this */
?>

<div class="section-header">
    <h2>
        <i class="bi bi-list-check"></i>
        Доступні тести
    </h2>
    <?php if (!empty($group)): ?>
        <a href="/admin/groups/view/<?= $group['id'] ?>" class="btn btn-outline-primary">
            <i class="bi bi-collection me-2"></i>Моя група: <?= $this->e($group['name']) ?>
        </a>
    <?php endif; ?>
</div>

<?php if (empty($availableTests)): ?>
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>Немає доступних тестів</p>
    </div>
<?php else: ?>
    <div class="mb-3">
        <input type="text" id="test-search" class="form-control" placeholder="Пошук тестів..." onkeyup="filterTests()">
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Назва тесту</th>
                    <th>Предмет</th>
                    <th>Опис</th>
                    <th>Тривалість</th>
                    <th>Спроби</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($availableTests as $test): ?>
                    <tr class="test-row" 
                        data-title="<?= mb_strtolower($this->e($test['title'])) ?>"
                        data-subject="<?= mb_strtolower($this->e($test['subject_name'] ?? '')) ?>"
                        data-description="<?= mb_strtolower($this->e($test['description'] ?? '')) ?>">
                        <td><?= $test['id'] ?></td>
                        <td>
                            <strong><?= $this->e($test['title']) ?></strong>
                        </td>
                        <td>
                            <?php if ($test['subject_name']): ?>
                                <span class="badge bg-info">
                                    <i class="bi bi-book me-1"></i><?= $this->e($test['subject_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($test['description']): ?>
                                <div class="text-truncate" style="max-width: 300px;" title="<?= $this->e($test['description']) ?>">
                                    <?= $this->e($test['description']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($test['duration']): ?>
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-clock me-1"></i><?= $test['duration'] ?> хв
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($test['max_attempts']): ?>
                                <small>
                                    <?= $test['user_attempts_count'] ?? 0 ?> / <?= $test['max_attempts'] ?>
                                    <?php if (isset($test['remaining_attempts']) && $test['remaining_attempts'] !== null): ?>
                                        <br><span class="text-muted">(залишилось: <?= $test['remaining_attempts'] ?>)</span>
                                    <?php endif; ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">Без обмежень</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/test/<?= $test['id'] ?>/start" class="btn btn-sm btn-primary" title="Почати тест">
                                <i class="bi bi-play-circle me-1"></i>Почати
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
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
<?php endif; ?>

<script>
function filterTests() {
    const search = document.getElementById('test-search').value.toLowerCase();
    const rows = document.querySelectorAll('.test-row');
    
    rows.forEach(row => {
        const title = row.getAttribute('data-title');
        const subject = row.getAttribute('data-subject');
        const description = row.getAttribute('data-description');
        if (title.includes(search) || subject.includes(search) || description.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
</style>

    <div class="section-header mt-5">
        <h2>
            <i class="bi bi-clock-history"></i>
            Мої спроби
        </h2>
    </div>

<div class="filter-bar">
    <form method="GET" class="row g-3 flex-grow-1">
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">Всі статуси</option>
                <option value="completed" <?= (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'selected' : '' ?>>Завершені</option>
                <option value="in_progress" <?= (isset($_GET['status']) && $_GET['status'] === 'in_progress') ? 'selected' : '' ?>>В процесі</option>
            </select>
        </div>
        <div class="col-md-4">
            <select name="test_id" class="form-select">
                <option value="">Всі тести</option>
                <?php foreach ($allTests as $test): ?>
                    <option value="<?= $test['id'] ?>" <?= (isset($_GET['test_id']) && (int)$_GET['test_id'] == $test['id']) ? 'selected' : '' ?>>
                        <?= $this->e($test['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-funnel me-2"></i>Фільтр
            </button>
            <a href="/dashboard" class="btn btn-outline-secondary">Скинути</a>
        </div>
        <input type="hidden" name="page" value="<?= $page ?>">
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Тест</th>
                <th>Дата</th>
                <th>Статус</th>
                <th>Бали</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attempts)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">Результати не знайдено</td>
                </tr>
            <?php else: ?>
                <?php foreach ($attempts as $attempt): ?>
                    <tr>
                        <td><?= $attempt['id'] ?></td>
                        <td><?= $this->e($attempt['test_title']) ?></td>
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
                                <?php 
                                $score = $attempt['score'] ?? 0;
                                $maxScore = $attempt['max_score'] ?? 0;
                                ?>
                                <?= number_format((float)$score, 2) ?> / <?= number_format((float)$maxScore, 2) ?>
                                <?php if ($maxScore > 0): ?>
                                    (<?= round(($score / $maxScore) * 100, 1) ?>%)
                                <?php else: ?>
                                    (0%)
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($attempt['status'] === 'completed'): ?>
                                <a href="/test/<?= $attempt['test_id'] ?>/result/<?= $attempt['id'] ?>" class="btn btn-sm btn-info" title="Результат">
                                    <i class="bi bi-eye"></i>
                                </a>
                            <?php elseif (isset($attempt['time_expired']) && $attempt['time_expired']): ?>
                                <span class="text-muted" title="Час на проходження тесту вийшов">-</span>
                            <?php else: ?>
                                <a href="/test/<?= $attempt['test_id'] ?>/take/<?= $attempt['id'] ?>" class="btn btn-sm btn-primary" title="Продовжити">
                                    <i class="bi bi-play"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($totalAttemptPages) && $totalAttemptPages > 1): ?>
    <nav aria-label="Навігація по сторінкам спроб">
        <ul class="pagination justify-content-center mt-4">
            <?php if ($attemptPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?apage=<?= $attemptPage - 1 ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&test_id=<?= $_GET['test_id'] ?? '' ?>&page=<?= $page ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, $attemptPage - 2); $i <= min($totalAttemptPages, $attemptPage + 2); $i++): ?>
                <li class="page-item <?= $i === $attemptPage ? 'active' : '' ?>">
                    <a class="page-link" href="?apage=<?= $i ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&test_id=<?= $_GET['test_id'] ?? '' ?>&page=<?= $page ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($attemptPage < $totalAttemptPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?apage=<?= $attemptPage + 1 ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&test_id=<?= $_GET['test_id'] ?? '' ?>&page=<?= $page ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
