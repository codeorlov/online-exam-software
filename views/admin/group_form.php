<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$isEdit = isset($group);
?>

<div class="section-header">
    <h2>
        <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?>"></i>
        <?= $isEdit ? 'Редагувати' : 'Створити' ?> группу
    </h2>
</div>

<form method="POST" action="/admin/groups/<?= $isEdit ? 'edit/' . $group['id'] : 'create' ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="mb-3">
        <label for="name" class="form-label">Назва групи *</label>
        <input type="text" class="form-control" id="name" name="name" 
               value="<?= $isEdit ? $this->e($group['name']) : '' ?>" 
               required maxlength="100">
    </div>
    
    <div class="mb-3">
        <label for="description" class="form-label">Опис</label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= $isEdit ? $this->e($group['description'] ?? '') : '' ?></textarea>
    </div>
    
    <?php if (isset($isAdmin) && $isAdmin && !empty($teachers)): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-person-check"></i> Призначити вчителям
            </h5>
            <?php if (!empty($teachers)): ?>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllTeachers()">
                    <i class="bi bi-check-all"></i> Вибрати всі
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllTeachers()">
                    <i class="bi bi-x-square"></i> Зняти вибір
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="teacher-search" class="form-control" placeholder="Пошук вчителів..." onkeyup="filterTeachers()">
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all-teachers" onchange="toggleAllTeachers(this)">
                            </th>
                            <th>ID</th>
                            <th>ПІБ</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr class="teacher-row" 
                                data-name="<?= mb_strtolower($this->e($teacher['first_name'] . ' ' . $teacher['last_name'])) ?>"
                                data-email="<?= mb_strtolower($this->e($teacher['email'])) ?>">
                                <td>
                                    <input class="form-check-input teacher-checkbox" 
                                           type="checkbox" 
                                           name="teacher_ids[]" 
                                           id="teacher_<?= $teacher['id'] ?>" 
                                           value="<?= $teacher['id'] ?>"
                                           <?= ($isEdit && isset($assignedTeacherIds) && in_array($teacher['id'], $assignedTeacherIds)) ? 'checked' : '' ?>>
                                </td>
                                <td><?= $teacher['id'] ?></td>
                                <td>
                                    <label for="teacher_<?= $teacher['id'] ?>" class="mb-0 cursor-pointer">
                                        <?= $this->e($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                                    </label>
                                </td>
                                <td>
                                    <small><?= $this->e($teacher['email']) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> <?= $isEdit ? 'Зберегти' : 'Створити' ?>
        </button>
        <a href="/admin/groups" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Скасувати
        </a>
    </div>
</form>

<?php if (isset($isAdmin) && $isAdmin && !empty($teachers)): ?>
<script>
function filterTeachers() {
    const search = document.getElementById('teacher-search').value.toLowerCase();
    const rows = document.querySelectorAll('.teacher-row');
    
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

function toggleAllTeachers(checkbox) {
    const checkboxes = document.querySelectorAll('.teacher-row:not([style*="display: none"]) .teacher-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function selectAllTeachers() {
    const checkboxes = document.querySelectorAll('.teacher-row:not([style*="display: none"]) .teacher-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    const selectAll = document.getElementById('select-all-teachers');
    if (selectAll) selectAll.checked = true;
}

function deselectAllTeachers() {
    const checkboxes = document.querySelectorAll('.teacher-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('select-all-teachers');
    if (selectAll) selectAll.checked = false;
}

document.addEventListener('DOMContentLoaded', function() {
    const teacherCheckboxes = document.querySelectorAll('.teacher-checkbox');
    const selectAllTeachers = document.getElementById('select-all-teachers');
    
    if (teacherCheckboxes.length > 0 && selectAllTeachers) {
        teacherCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectAllTeachers();
            });
        });
    }
    
    function updateSelectAllTeachers() {
        if (!selectAllTeachers) return;
        const visible = Array.from(document.querySelectorAll('.teacher-row:not([style*="display: none"]) .teacher-checkbox'));
        const checked = visible.filter(cb => cb.checked);
        selectAllTeachers.checked = visible.length > 0 && checked.length === visible.length;
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
<?php endif; ?>
