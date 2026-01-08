<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$isEdit = isset($user);
?>

<div class="section-header">
    <h2>
        <i class="bi bi-<?= $isEdit ? 'pencil' : 'person-plus' ?>"></i>
        <?= $isEdit ? 'Редагувати' : 'Створити' ?> користувача
    </h2>
</div>

<form method="POST" action="/admin/users/<?= $isEdit ? 'edit/' . $user['id'] : 'create' ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="first_name" class="form-label">Ім'я *</label>
            <input type="text" class="form-control" id="first_name" name="first_name" 
                   value="<?= $isEdit ? $this->e($user['first_name']) : '' ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="last_name" class="form-label">Прізвище *</label>
            <input type="text" class="form-control" id="last_name" name="last_name" 
                   value="<?= $isEdit ? $this->e($user['last_name']) : '' ?>" required>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email *</label>
        <input type="email" class="form-control" id="email" name="email" 
               value="<?= $isEdit ? $this->e($user['email']) : '' ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Пароль <?= $isEdit ? '(залиште порожнім, щоб не змінювати)' : '*' ?></label>
        <input type="password" class="form-control" id="password" name="password" 
               <?= $isEdit ? '' : 'required' ?>>
        <?php if (!$isEdit): ?>
            <small class="form-text text-muted">Мінімум <?= PASSWORD_MIN_LENGTH ?> символів</small>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="role" class="form-label">Роль *</label>
            <select class="form-select" id="role" name="role" required>
                <option value="student" <?= ($isEdit && $user['role'] === 'student') ? 'selected' : '' ?>>Студент</option>
                <option value="teacher" <?= ($isEdit && $user['role'] === 'teacher') ? 'selected' : '' ?>>Вчитель</option>
                <option value="admin" <?= ($isEdit && $user['role'] === 'admin') ? 'selected' : '' ?>>Адміністратор</option>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="status" class="form-label">Статус *</label>
            <select class="form-select" id="status" name="status" required>
                <option value="active" <?= ($isEdit && $user['status'] === 'active') ? 'selected' : '' ?>>Активний</option>
                <option value="banned" <?= ($isEdit && $user['status'] === 'banned') ? 'selected' : '' ?>>Заблокований</option>
            </select>
        </div>
    </div>
    
    <div class="mb-3" id="group-field" style="display: none;">
        <label for="group_id" class="form-label">Група *</label>
        <select class="form-select" id="group_id" name="group_id">
            <option value="">Виберіть групу</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?= $group['id'] ?>" 
                        <?= ($isEdit && $user['group_id'] == $group['id']) ? 'selected' : '' ?>>
                    <?= $this->e($group['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Група обов'язкова для студентів</small>
    </div>

    <div class="card mb-4" id="teacher-groups-field" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-collection"></i> Призначені групи
            </h5>
            <?php if (!empty($groups)): ?>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllGroups()">
                    <i class="bi bi-check-all"></i> Вибрати всі
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllGroups()">
                    <i class="bi bi-x-square"></i> Зняти вибір
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="group-search" class="form-control" placeholder="Пошук груп..." onkeyup="filterGroups()">
            </div>
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-hover">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all-groups" onchange="toggleAllGroups(this)">
                            </th>
                            <th>ID</th>
                            <th>Назва групи</th>
                            <th>Опис</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                            <tr class="group-row" data-name="<?= mb_strtolower($this->e($group['name'])) ?>">
                                <td>
                                    <input class="form-check-input group-checkbox" 
                                           type="checkbox" 
                                           name="teacher_group_ids[]" 
                                           id="group_<?= $group['id'] ?>" 
                                           value="<?= $group['id'] ?>"
                                           <?= ($isEdit && in_array($group['id'], $teacherGroupIds ?? [])) ? 'checked' : '' ?>>
                                </td>
                                <td><?= $group['id'] ?></td>
                                <td>
                                    <label for="group_<?= $group['id'] ?>" class="mb-0 cursor-pointer">
                                        <?= $this->e($group['name']) ?>
                                    </label>
                                </td>
                                <td>
                                    <small class="text-muted"><?= $this->e($group['description'] ?? '') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4" id="teacher-subjects-field" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-book"></i> Призначені предмети
            </h5>
            <?php if (!empty($subjects)): ?>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllSubjects()">
                    <i class="bi bi-check-all"></i> Вибрати всі
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllSubjects()">
                    <i class="bi bi-x-square"></i> Зняти вибір
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="subject-search" class="form-control" placeholder="Пошук предметів..." onkeyup="filterSubjects()">
            </div>
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-hover">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all-subjects" onchange="toggleAllSubjects(this)">
                            </th>
                            <th>ID</th>
                            <th>Назва предмета</th>
                            <th>Опис</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr class="subject-row" data-name="<?= mb_strtolower($this->e($subject['name'])) ?>">
                                <td>
                                    <input class="form-check-input subject-checkbox" 
                                           type="checkbox" 
                                           name="teacher_subject_ids[]" 
                                           id="subject_<?= $subject['id'] ?>" 
                                           value="<?= $subject['id'] ?>"
                                           <?= ($isEdit && in_array($subject['id'], $teacherSubjectIds ?? [])) ? 'checked' : '' ?>>
                                </td>
                                <td><?= $subject['id'] ?></td>
                                <td>
                                    <label for="subject_<?= $subject['id'] ?>" class="mb-0 cursor-pointer">
                                        <?= $this->e($subject['name']) ?>
                                    </label>
                                </td>
                                <td>
                                    <small class="text-muted"><?= $this->e($subject['description'] ?? '') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
function filterGroups() {
    const search = document.getElementById('group-search').value.toLowerCase();
    const rows = document.querySelectorAll('.group-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if (name.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterSubjects() {
    const search = document.getElementById('subject-search').value.toLowerCase();
    const rows = document.querySelectorAll('.subject-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if (name.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleAllGroups(checkbox) {
    const checkboxes = document.querySelectorAll('.group-row:not([style*="display: none"]) .group-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function toggleAllSubjects(checkbox) {
    const checkboxes = document.querySelectorAll('.subject-row:not([style*="display: none"]) .subject-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function selectAllGroups() {
    const checkboxes = document.querySelectorAll('.group-row:not([style*="display: none"]) .group-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    const selectAll = document.getElementById('select-all-groups');
    if (selectAll) selectAll.checked = true;
}

function deselectAllGroups() {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('select-all-groups');
    if (selectAll) selectAll.checked = false;
}

function selectAllSubjects() {
    const checkboxes = document.querySelectorAll('.subject-row:not([style*="display: none"]) .subject-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    const selectAll = document.getElementById('select-all-subjects');
    if (selectAll) selectAll.checked = true;
}

function deselectAllSubjects() {
    const checkboxes = document.querySelectorAll('.subject-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('select-all-subjects');
    if (selectAll) selectAll.checked = false;
}

document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const groupField = document.getElementById('group-field');
    const groupSelect = document.getElementById('group_id');
    const teacherGroupsField = document.getElementById('teacher-groups-field');
    const teacherSubjectsField = document.getElementById('teacher-subjects-field');
    
    function toggleGroupFields() {
        if (roleSelect.value === 'student') {
            groupField.style.display = 'block';
            groupSelect.required = true;
            teacherGroupsField.style.display = 'none';
            teacherSubjectsField.style.display = 'none';
        } else if (roleSelect.value === 'teacher') {
            groupField.style.display = 'none';
            groupSelect.required = false;
            groupSelect.value = '';
            teacherGroupsField.style.display = 'block';
            teacherSubjectsField.style.display = 'block';
        } else {
            groupField.style.display = 'none';
            groupSelect.required = false;
            groupSelect.value = '';
            teacherGroupsField.style.display = 'none';
            teacherSubjectsField.style.display = 'none';
        }
    }
    
    roleSelect.addEventListener('change', toggleGroupFields);
    toggleGroupFields();
    
    const groupCheckboxes = document.querySelectorAll('.group-checkbox');
    const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');
    const selectAllGroups = document.getElementById('select-all-groups');
    const selectAllSubjects = document.getElementById('select-all-subjects');
    
    if (groupCheckboxes.length > 0 && selectAllGroups) {
        groupCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectAllGroups();
            });
        });
    }
    
    if (subjectCheckboxes.length > 0 && selectAllSubjects) {
        subjectCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectAllSubjects();
            });
        });
    }
    
    function updateSelectAllGroups() {
        if (!selectAllGroups) return;
        const visible = Array.from(document.querySelectorAll('.group-row:not([style*="display: none"]) .group-checkbox'));
        const checked = visible.filter(cb => cb.checked);
        selectAllGroups.checked = visible.length > 0 && checked.length === visible.length;
    }
    
    function updateSelectAllSubjects() {
        if (!selectAllSubjects) return;
        const visible = Array.from(document.querySelectorAll('.subject-row:not([style*="display: none"]) .subject-checkbox'));
        const checked = visible.filter(cb => cb.checked);
        selectAllSubjects.checked = visible.length > 0 && checked.length === visible.length;
    }
});
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> <?= $isEdit ? 'Зберегти' : 'Створити' ?>
        </button>
        <a href="/admin/users" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Скасувати
        </a>
    </div>
</form>
