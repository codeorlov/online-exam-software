<?php
/** @var \App\Core\View $this */
?>

<div class="section-header">
    <h2>
        <i class="bi bi-file-earmark"></i>
        Доступні файли
    </h2>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-3 flex-grow-1">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" 
                   placeholder="Пошук за назвою файлу" 
                   value="<?= $this->e($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-funnel me-2"></i>Фільтр
            </button>
            <a href="/student/files" class="btn btn-outline-secondary">Скинути</a>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Назва файлу</th>
                <th>Розмір</th>
                <th>Завантажено</th>
                <th>Завантажив</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($files)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">Результати не знайдено</td>
                </tr>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?= $file['id'] ?></td>
                        <td>
                            <i class="bi bi-file-earmark me-2"></i>
                            <?= $this->e($file['original_name']) ?>
                        </td>
                        <td><?= $this->formatFileSize((int)$file['file_size']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($file['created_at'])) ?></td>
                        <td>
                            <?= $this->e($file['first_name'] . ' ' . $file['last_name']) ?>
                            (<?= $this->e($file['email']) ?>)
                        </td>
                        <td>
                            <a href="/admin/files/download/<?= $file['id'] ?>" 
                               class="btn btn-sm btn-primary" 
                               title="Завантажити"
                               target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
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
            <?php 
            $queryParams = [];
            if (!empty($filters['search'])) $queryParams[] = 'search=' . urlencode($filters['search']);
            $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
            ?>
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $queryString ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $queryString ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
