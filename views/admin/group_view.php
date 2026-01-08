<?php
/** @var \App\Core\View $this */
?>

<div class="section-header">
    <h2>
        <i class="bi bi-eye"></i>
        Перегляд групи: <?= $this->e($group['name']) ?>
    </h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Інформація про групу</h5>
        <p><strong>Назва:</strong> <?= $this->e($group['name']) ?></p>
        <?php if ($group['description']): ?>
            <p><strong>Опис:</strong> <?= $this->e($group['description']) ?></p>
        <?php endif; ?>
        <?php if (isset($isAdmin) && $isAdmin): ?>
            <p><strong>Призначений вчитель:</strong> 
                <?php if (!empty($group['teacher_count']) && (int)$group['teacher_count'] > 1): ?>
                    <span class="text-info">Декілька вчителів</span>
                <?php elseif (!empty($group['first_name'])): ?>
                    <?= $this->e($group['first_name'] . ' ' . $group['last_name']) ?> 
                    (<?= $this->e($group['email']) ?>)
                <?php else: ?>
                    <span class="text-muted">Не призначено</span>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <p><strong>Дата створення:</strong> <?= date('d.m.Y H:i', strtotime($group['created_at'])) ?></p>
    </div>
</div>

    <div class="section-header">
        <h2>
            <i class="bi bi-people"></i>
            Члени групи (<?= $totalStudents ?? count($students ?? []) ?>)
        </h2>
    </div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>ПІБ</th>
                <th>Email</th>
                <th>Статус</th>
                <th>Дата реєстрації</th>
                <?php if (isset($isAdmin) && $isAdmin): ?>
                <th>Дії</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="<?= (isset($isAdmin) && $isAdmin) ? '6' : '5' ?>" class="text-center text-muted">Результати не знайдено</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= $student['id'] ?></td>
                        <td><?= $this->e($student['first_name'] . ' ' . $student['last_name']) ?></td>
                        <td><?= $this->e($student['email']) ?></td>
                        <td>
                            <?php if ($student['status'] === 'active'): ?>
                                <span class="badge bg-success">Активний</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Заблокований</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d.m.Y', strtotime($student['created_at'])) ?></td>
                        <td>
                            <?php if (isset($isAdmin) && $isAdmin): ?>
                            <a href="/admin/users/edit/<?= $student['id'] ?>" class="btn btn-sm btn-primary" title="Редагувати студента">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/admin/groups/<?= $group['id'] ?>/remove-student/<?= $student['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Видалити з групи"
                               data-confirm-delete>
                                <i class="bi bi-person-dash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (($totalStudentPages ?? 1) > 1): ?>
    <nav aria-label="Навігація по сторінкам студентів">
        <ul class="pagination justify-content-center mt-4">
            <?php if (($studentPage ?? 1) > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?spage=<?= ($studentPage ?? 1) - 1 ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, ($studentPage ?? 1) - 2); $i <= min($totalStudentPages ?? 1, ($studentPage ?? 1) + 2); $i++): ?>
                <li class="page-item <?= $i === ($studentPage ?? 1) ? 'active' : '' ?>">
                    <a class="page-link" href="?spage=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if (($studentPage ?? 1) < ($totalStudentPages ?? 1)): ?>
                <li class="page-item">
                    <a class="page-link" href="?spage=<?= ($studentPage ?? 1) + 1 ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<div class="section-header mt-4">
    <h2>
        <i class="bi bi-file-earmark"></i>
        Файли групи (<?= $totalFiles ?? count($files ?? []) ?>)
    </h2>
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
                            <?php if (isset($isAdmin) && $isAdmin): ?>
                            <a href="/admin/files/edit/<?= $file['id'] ?>" 
                               class="btn btn-sm btn-warning" 
                               title="Редагувати призначення">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/admin/files/delete/<?= $file['id'] ?>" 
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

<?php if (($totalFilePages ?? 1) > 1): ?>
    <nav aria-label="Навігація по сторінкам файлів">
        <ul class="pagination justify-content-center mt-4">
            <?php if (($filePage ?? 1) > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?fpage=<?= ($filePage ?? 1) - 1 ?>">Попередня</a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1, ($filePage ?? 1) - 2); $i <= min($totalFilePages ?? 1, ($filePage ?? 1) + 2); $i++): ?>
                <li class="page-item <?= $i === ($filePage ?? 1) ? 'active' : '' ?>">
                    <a class="page-link" href="?fpage=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if (($filePage ?? 1) < ($totalFilePages ?? 1)): ?>
                <li class="page-item">
                    <a class="page-link" href="?fpage=<?= ($filePage ?? 1) + 1 ?>">Наступна</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<div class="mt-4">
    <?php if (isset($isStudent) && $isStudent): ?>
        <a href="/dashboard/student" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Повернутися на головну
        </a>
    <?php else: ?>
        <a href="/admin/groups" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Повернутися до списку груп
        </a>
        <?php if (isset($isAdmin) && $isAdmin): ?>
        <a href="/admin/groups/edit/<?= $group['id'] ?>" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Редагувати групу
        </a>
        <?php endif; ?>
    <?php endif; ?>
</div>
