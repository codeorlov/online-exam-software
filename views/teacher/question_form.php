<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$isAdmin = $this->get('isAdmin') ?? false;
$basePath = $isAdmin ? '/admin/tests' : '/teacher/tests';
?>

<div class="section-header">
    <h2>
        <i class="bi bi-plus-circle"></i>
        Додати питання до тесту: <?= $this->e($test['title']) ?>
    </h2>
</div>

<form method="POST" action="<?= $basePath ?>/<?= $test['id'] ?>/questions/add">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="mb-3">
        <label for="question_text" class="form-label">Текст питання *</label>
        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="question_type" class="form-label">Тип питання *</label>
            <select class="form-select" id="question_type" name="question_type" required onchange="toggleQuestionOptions()">
                <option value="single_choice">Лише одна правильна відповідь</option>
                <option value="multiple_choice">Кілька правильних відповідей</option>
                <option value="true_false">Так/Ні</option>
                <option value="short_answer">Коротка відповідь</option>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="points" class="form-label">Бали *</label>
            <input type="number" class="form-control" id="points" name="points" value="1" min="0.1" step="0.1" required>
        </div>
    </div>
    
    <div id="options-container">
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Додати питання
        </button>
        <a href="<?= $basePath ?>/edit/<?= $test['id'] ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Скасувати
        </a>
    </div>
</form>

<script>
function toggleQuestionOptions() {
    const type = document.getElementById('question_type').value;
    const container = document.getElementById('options-container');
    
    container.innerHTML = '';
    
    if (type === 'single_choice' || type === 'multiple_choice') {
        container.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Варіанти відповідей *</label>
                <div id="options-list">
                    <div class="input-group mb-2">
                        <div class="input-group-text">
                            <input type="${type === 'single_choice' ? 'radio' : 'checkbox'}" name="option_correct[0]" value="1">
                        </div>
                        <input type="text" class="form-control" name="option_text[0]" placeholder="Варіант відповіді" required>
                        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">Видалити</button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption()">
                    <i class="bi bi-plus"></i> Додати варіант
                </button>
            </div>
        `;
    } else if (type === 'true_false') {
        container.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Правильна відповідь *</label>
                <select class="form-select" name="correct_answer" required>
                    <option value="true">Так</option>
                    <option value="false">Ні</option>
                </select>
            </div>
        `;
    } else if (type === 'short_answer') {
        container.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Правильні варіанти відповіді *</label>
                <div id="answers-list">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="correct_answer[0]" placeholder="Правильна відповідь" required>
                        <button type="button" class="btn btn-outline-danger" onclick="removeAnswer(this)">Видалити</button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAnswer()">
                    <i class="bi bi-plus"></i> Додати варіант
                </button>
                <small class="form-text text-muted">Можна вказати кілька правильних варіантів (наприклад, "5" та "п\'ять")</small>
            </div>
        `;
    }
}

let optionIndex = 1;
function addOption() {
    const type = document.getElementById('question_type').value;
    const list = document.getElementById('options-list');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <div class="input-group-text">
            <input type="${type === 'single_choice' ? 'radio' : 'checkbox'}" name="option_correct[${optionIndex}]" value="1">
        </div>
        <input type="text" class="form-control" name="option_text[${optionIndex}]" placeholder="Варіант відповіді" required>
        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">Видалити</button>
    `;
    list.appendChild(div);
    optionIndex++;
}

function removeOption(btn) {
    btn.closest('.input-group').remove();
}

let answerIndex = 1;
function addAnswer() {
    const list = document.getElementById('answers-list');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="correct_answer[${answerIndex}]" placeholder="Правильна відповідь" required>
        <button type="button" class="btn btn-outline-danger" onclick="removeAnswer(this)">Видалити</button>
    `;
    list.appendChild(div);
    answerIndex++;
}

function removeAnswer(btn) {
    btn.closest('.input-group').remove();
}

document.addEventListener('DOMContentLoaded', function() {
    toggleQuestionOptions();
    
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const type = document.getElementById('question_type').value;
            
            if (type === 'single_choice' || type === 'multiple_choice') {
                const optionInputs = form.querySelectorAll('input[name^="option_text"]');
                let hasEmpty = false;
                let hasNonEmpty = false;
                
                optionInputs.forEach(function(input) {
                    const value = input.value.trim();
                    if (value === '') {
                        hasEmpty = true;
                    } else {
                        hasNonEmpty = true;
                    }
                });
                
                if (hasEmpty && hasNonEmpty) {
                    e.preventDefault();
                    alert('Будь ласка, видаліть або заповніть всі порожні варіанти відповідей перед збереженням.');
                    return false;
                }
                
                if (!hasNonEmpty) {
                    e.preventDefault();
                    alert('Питання повинно містити хоча б один варіант відповіді.');
                    return false;
                }
            } else if (type === 'short_answer') {
                const answerInputs = form.querySelectorAll('input[name^="correct_answer"]');
                let hasEmpty = false;
                let hasNonEmpty = false;
                
                answerInputs.forEach(function(input) {
                    const value = input.value.trim();
                    if (value === '') {
                        hasEmpty = true;
                    } else {
                        hasNonEmpty = true;
                    }
                });
                
                if (hasEmpty && hasNonEmpty) {
                    e.preventDefault();
                    alert('Будь ласка, видаліть або заповніть всі порожні правильні відповіді перед збереженням.');
                    return false;
                }
                
                if (!hasNonEmpty) {
                    e.preventDefault();
                    alert('Питання повинно містити хоча б один правильний варіант відповіді.');
                    return false;
                }
            }
        });
    }
});
</script>
