<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$isAdmin = $this->get('isAdmin') ?? false;
?>

<div class="section-header">
    <h2>
        <i class="bi bi-person-check"></i>
        Призначити тест: <?= $this->e($test['title']) ?>
    </h2>
</div>

<form method="POST" action="<?= $isAdmin ? '/admin/tests/' : '/teacher/tests/' ?><?= $test['id'] ?>/assign" id="assign-form">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-collection"></i> Групи
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
                    <?php if (empty($groups)): ?>
                        <p class="text-muted mb-0">Немає доступних груп</p>
                    <?php else: ?>
                        <div class="mb-3">
                            <input type="text" id="group-search" class="form-control" placeholder="Пошук груп..." onkeyup="filterGroups()">
                        </div>
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
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
                                        <tr class="group-row" data-name="<?= strtolower($this->e($group['name'])) ?>">
                                            <td>
                                                <input class="form-check-input group-checkbox" 
                                                       type="checkbox" 
                                                       name="group_ids[]" 
                                                       id="group_<?= $group['id'] ?>" 
                                                       value="<?= $group['id'] ?>"
                                                       <?= in_array($group['id'], $assignedGroupIds) ? 'checked' : '' ?>>
                                            </td>
                                            <td><?= $group['id'] ?></td>
                                            <td>
                                                <label for="group_<?= $group['id'] ?>" class="mb-0 cursor-pointer">
                                                    <?= $this->e($group['name']) ?>
                                                </label>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= $this->e($group['description'] ?? '') ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> Окремі студенти
                    </h5>
                    <?php if (!empty($students)): ?>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllStudents()">
                            <i class="bi bi-check-all"></i> Вибрати всі
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllStudents()">
                            <i class="bi bi-x-square"></i> Зняти вибір
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <p class="text-muted mb-0">Немає доступних студентів</p>
                    <?php else: ?>
                        <div class="mb-3">
                            <input type="text" id="student-search" class="form-control" placeholder="Пошук студентів..." onkeyup="filterStudents()">
                        </div>
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="select-all-students" onchange="toggleAllStudents(this)">
                                        </th>
                                        <th>ID</th>
                                        <th>ПІБ</th>
                                        <th>Email</th>
                                        <th>Група</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr class="student-row" 
                                            data-name="<?= strtolower($this->e($student['first_name'] . ' ' . $student['last_name'])) ?>"
                                            data-email="<?= strtolower($this->e($student['email'])) ?>">
                                            <td>
                                                <input class="form-check-input student-checkbox" 
                                                       type="checkbox" 
                                                       name="user_ids[]" 
                                                       id="user_<?= $student['id'] ?>" 
                                                       value="<?= $student['id'] ?>"
                                                       <?= in_array($student['id'], $assignedUserIds) ? 'checked' : '' ?>>
                                            </td>
                                            <td><?= $student['id'] ?></td>
                                            <td>
                                                <label for="user_<?= $student['id'] ?>" class="mb-0 cursor-pointer">
                                                    <?= $this->e($student['first_name'] . ' ' . $student['last_name']) ?>
                                                </label>
                                            </td>
                                            <td>
                                                <small><?= $this->e($student['email']) ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= $this->e($student['group_name'] ?? 'Без групи') ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Зберегти призначення
        </button>
        <a href="<?= $isAdmin ? '/admin/tests' : '/teacher/tests' ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Скасувати
        </a>
    </div>
</form>

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

function filterStudents() {
    const search = document.getElementById('student-search').value.toLowerCase();
    const rows = document.querySelectorAll('.student-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const email = row.getAttribute('data-email');
        if (name.includes(search) || email.includes(search)) {
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

function toggleAllStudents(checkbox) {
    const checkboxes = document.querySelectorAll('.student-row:not([style*="display: none"]) .student-checkbox');
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

function selectAllStudents() {
    const checkboxes = document.querySelectorAll('.student-row:not([style*="display: none"]) .student-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    const selectAll = document.getElementById('select-all-students');
    if (selectAll) selectAll.checked = true;
}

function deselectAllStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('select-all-students');
    if (selectAll) selectAll.checked = false;
}

document.addEventListener('DOMContentLoaded', function() {
    const groupCheckboxes = document.querySelectorAll('.group-checkbox');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    const selectAllGroups = document.getElementById('select-all-groups');
    const selectAllStudents = document.getElementById('select-all-students');
    
    if (groupCheckboxes.length > 0 && selectAllGroups) {
        groupCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectAllGroups();
            });
        });
    }
    
    if (studentCheckboxes.length > 0 && selectAllStudents) {
        studentCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectAllStudents();
            });
        });
    }
    
    function updateSelectAllGroups() {
        if (!selectAllGroups) return;
        const visible = Array.from(document.querySelectorAll('.group-row:not([style*="display: none"]) .group-checkbox'));
        const checked = visible.filter(cb => cb.checked);
        selectAllGroups.checked = visible.length > 0 && checked.length === visible.length;
    }
    
    function updateSelectAllStudents() {
        if (!selectAllStudents) return;
        const visible = Array.from(document.querySelectorAll('.student-row:not([style*="display: none"]) .student-checkbox'));
        const checked = visible.filter(cb => cb.checked);
        selectAllStudents.checked = visible.length > 0 && checked.length === visible.length;
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
