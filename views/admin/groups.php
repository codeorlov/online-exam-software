<?php
/** @var \App\Core\View $this */
?>

<div class="section-header">
    <h2>
        <i class="bi bi-collection"></i>
        Управління групами
    </h2>
    <?php if (isset($isAdmin) && $isAdmin): ?>
    <a href="/admin/groups/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Створити групу
    </a>
    <?php endif; ?>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-3 flex-grow-1">
        <div class="col-md-8">
            <input type="text" name="search" class="form-control" placeholder="Пошук..." value="<?= $this->e($_GET['search'] ?? '') ?>">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-funnel me-2"></i>Фільтр
            </button>
            <a href="/admin/groups" class="btn btn-outline-secondary">Скинути</a>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Назва</th>
                <th>Опис</th>
                <?php if (isset($isAdmin) && $isAdmin): ?>
                <th>Вчитель</th>
                <?php endif; ?>
                <th>Дата створення</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($groups)): ?>
                <tr>
                    <td colspan="<?= (isset($isAdmin) && $isAdmin) ? '6' : '5' ?>" class="text-center text-muted">Результати не знайдено</td>
                </tr>
            <?php else: ?>
                <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= $group['id'] ?></td>
                        <td><?= $this->e($group['name']) ?></td>
                        <td><?= $this->e($group['description'] ?? '') ?></td>
                        <?php if (isset($isAdmin) && $isAdmin): ?>
                        <td>
                            <?php if (!empty($group['teacher_count']) && (int)$group['teacher_count'] > 1): ?>
                                <span class="text-info">Декілька вчителів</span>
                            <?php elseif (!empty($group['first_name'])): ?>
                                <?= $this->e($group['first_name'] . ' ' . $group['last_name']) ?>
                                <br><small class="text-muted"><?= $this->e($group['email']) ?></small>
                            <?php else: ?>
                                <span class="text-muted">Не призначено</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td><?= date('d.m.Y', strtotime($group['created_at'])) ?></td>
                        <td>
                            <a href="/admin/groups/view/<?= $group['id'] ?>" class="btn btn-sm btn-info" title="Перегляд">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (isset($isAdmin) && $isAdmin): ?>
                            <a href="/admin/groups/edit/<?= $group['id'] ?>" class="btn btn-sm btn-primary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/admin/groups/delete/<?= $group['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Видалити"
                               data-confirm-delete>
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <nav aria-label="Навігація по сторінкам">
        <ul class="pagination justify-content-center mt-4">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
