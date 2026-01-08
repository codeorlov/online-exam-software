<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$isEdit = isset($test);
$isAdmin = $this->get('isAdmin') ?? false;
?>

<div class="section-header">
    <h2>
        <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?>"></i>
        <?= $isEdit ? 'Редагувати' : 'Створити' ?> тест
    </h2>
</div>

<form method="POST" action="<?= $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $isEdit ? 'edit/' . $test['id'] : 'create' ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="mb-3">
        <label for="title" class="form-label">Назва тесту *</label>
        <input type="text" class="form-control" id="title" name="title" 
               value="<?= $isEdit ? $this->e($test['title']) : '' ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="description" class="form-label">Опис</label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= $isEdit ? $this->e($test['description'] ?? '') : '' ?></textarea>
    </div>
    
    <?php if ($isAdmin && !empty($teachers)): ?>
    <div class="mb-3">
        <label for="created_by" class="form-label">Створив тест</label>
        <select class="form-select" id="created_by" name="created_by">
            <?php 
            $currentUserId = \App\Core\Session::get('user_id');
            $selectedCreatorId = $isEdit ? ($test['created_by'] ?? $currentUserId) : $currentUserId;
            foreach ($teachers as $teacher): 
                $selected = ($teacher['id'] == $selectedCreatorId) ? 'selected' : '';
            ?>
                <option value="<?= $teacher['id'] ?>" <?= $selected ?>>
                    <?= $this->e($teacher['first_name'] . ' ' . $teacher['last_name']) ?> 
                    (<?= $this->e($teacher['email']) ?>) 
                    <?= $teacher['role'] === 'admin' ? '[Адміністратор]' : '[Вчитель]' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Виберіть створювача тесту (за замовчуванням вибрано поточного користувача)</small>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="subject_id" class="form-label">Предмет</label>
            <select class="form-select" id="subject_id" name="subject_id">
                <option value="">Без предмета</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>" 
                            <?= ($isEdit && $test['subject_id'] == $subject['id']) ? 'selected' : '' ?>>
                        <?= $this->e($subject['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="duration" class="form-label">Тривалість (хвилин)</label>
            <input type="number" class="form-control" id="duration" name="duration" 
                   value="<?= $isEdit ? $test['duration'] : '' ?>" min="1" placeholder="Наприклад: 30">
            <small class="form-text text-muted">Час на проходження тесту. Залиште порожнім для необмеженого часу</small>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="max_attempts" class="form-label">Максимум спроб *</label>
            <input type="number" class="form-control" id="max_attempts" name="max_attempts" 
                   value="<?= $isEdit ? $test['max_attempts'] : '1' ?>" min="1" required>
            <small class="form-text text-muted">Кількість разів, яку студент може пройти цей тест</small>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="passing_score" class="form-label">Прохідний бал (%) *</label>
            <input type="number" class="form-control" id="passing_score" name="passing_score" 
                   value="<?= $isEdit ? $test['passing_score'] : '60' ?>" min="0" max="100" step="0.1" required>
            <small class="form-text text-muted">Мінімальний відсоток правильних відповідей для успішного проходження тесту</small>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="start_date" class="form-label">Дата початку</label>
            <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                   value="<?= $isEdit && $test['start_date'] ? date('Y-m-d\TH:i', strtotime($test['start_date'])) : '' ?>">
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="end_date" class="form-label">Дата закінчення</label>
            <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                   value="<?= $isEdit && $test['end_date'] ? date('Y-m-d\TH:i', strtotime($test['end_date'])) : '' ?>">
        </div>
    </div>
    
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" 
                   <?= ($isEdit && $test['is_published']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_published">
                Опублікувати тест
            </label>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> <?= $isEdit ? 'Зберегти' : 'Створити' ?>
        </button>
        <a href="<?= $isAdmin ? '/admin/tests' : '/teacher/tests' ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Скасувати
        </a>
    </div>
</form>

<?php if ($isEdit): ?>
    <hr class="my-5">
    <div class="section-header">
        <h2>
            <i class="bi bi-question-circle"></i>
            Питання тесту (<?= $totalQuestions ?? count($questions ?? []) ?>)
        </h2>
        <a href="<?= $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $test['id'] ?>/questions/add" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Додати питання
        </a>
    </div>
    
    <div class="filter-bar">
        <form method="GET" class="row g-3 flex-grow-1">
            <div class="col-md-8">
                <input type="text" name="qsearch" class="form-control" placeholder="Пошук за текстом питання..." value="<?= $this->e($questionSearch ?? '') ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-funnel me-2"></i>Фільтр
                </button>
                <a href="<?= $isAdmin ? '/admin/tests/edit/' : '/teacher/tests/edit/' ?><?= $test['id'] ?>" class="btn btn-outline-secondary">Скинути</a>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Текст питання</th>
                    <th>Тип</th>
                    <th>Бали</th>
                    <th>Варіанти відповідей</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Питання не знайдено</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $questionPerPage = $this->get('questionPerPage') ?? 20;
                    $startIndex = ($questionPage - 1) * $questionPerPage;
                    foreach ($questions as $index => $question): 
                        $questionNumber = $startIndex + $index + 1;
                        $typeNames = [
                            'single_choice' => 'Одна правильна',
                            'multiple_choice' => 'Кілька правильних',
                            'true_false' => 'Так/Ні',
                            'short_answer' => 'Коротка відповідь'
                        ];
                        $typeName = $typeNames[$question['question_type']] ?? $question['question_type'];
                    ?>
                        <tr>
                            <td><?= $questionNumber ?></td>
                            <td>
                                <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= $this->e($question['question_text']) ?>">
                                    <?= $this->e($question['question_text']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $typeName ?></span>
                            </td>
                            <td><?= $question['points'] ?></td>
                            <td>
                                <?php if (!empty($question['options'])): ?>
                                    <small>
                                        <?php 
                                        $correctCount = 0;
                                        $totalCount = count($question['options']);
                                        foreach ($question['options'] as $option) {
                                            if ($option['is_correct']) {
                                                $correctCount++;
                                            }
                                        }
                                        echo $totalCount . ' варіантів, ' . $correctCount . ' правильних';
                                        ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">Немає варіантів</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $test['id'] ?>/questions/edit/<?= $question['id'] ?>" class="btn btn-sm btn-primary" title="Редагувати">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $test['id'] ?>/questions/delete/<?= $question['id'] ?>" 
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

    <?php if (isset($totalQuestionPages) && $totalQuestionPages > 1): ?>
        <nav aria-label="Навігація по сторінкам питань">
            <ul class="pagination justify-content-center mt-4">
                <?php 
                $queryParams = [];
                if (!empty($questionSearch)) {
                    $queryParams['qsearch'] = $questionSearch;
                }
                $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                ?>
                <?php if ($questionPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?qpage=<?= $questionPage - 1 ?><?= $queryString ?>">Попередня</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = max(1, $questionPage - 2); $i <= min($totalQuestionPages, $questionPage + 2); $i++): ?>
                    <li class="page-item <?= $i === $questionPage ? 'active' : '' ?>">
                        <a class="page-link" href="?qpage=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($questionPage < $totalQuestionPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?qpage=<?= $questionPage + 1 ?><?= $queryString ?>">Наступна</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
