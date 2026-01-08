<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$isEdit = isset($question);
$isAdmin = $this->get('isAdmin') ?? false;
$basePath = $isAdmin ? '/admin/tests' : '/teacher/tests';
?>

<div class="section-header">
    <h2>
        <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?>"></i>
        <?= $isEdit ? 'Редагувати' : 'Додати' ?> питання до тесту: <?= $this->e($test['title']) ?>
    </h2>
</div>

<form method="POST" action="<?= $basePath ?>/<?= $test['id'] ?>/questions/<?= $isEdit ? 'edit/' . $question['id'] : 'add' ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="mb-3">
        <label for="question_text" class="form-label">Текст питання *</label>
        <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?= $isEdit ? $this->e($question['question_text']) : '' ?></textarea>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="question_type" class="form-label">Тип питання *</label>
            <select class="form-select" id="question_type" name="question_type" required onchange="toggleQuestionOptions()">
                <option value="single_choice" <?= ($isEdit && $question['question_type'] === 'single_choice') ? 'selected' : '' ?>>Лише одна правильна відповідь</option>
                <option value="multiple_choice" <?= ($isEdit && $question['question_type'] === 'multiple_choice') ? 'selected' : '' ?>>Кілька правильних відповідей</option>
                <option value="true_false" <?= ($isEdit && $question['question_type'] === 'true_false') ? 'selected' : '' ?>>Так/Ні</option>
                <option value="short_answer" <?= ($isEdit && $question['question_type'] === 'short_answer') ? 'selected' : '' ?>>Коротка відповідь</option>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="points" class="form-label">Бали *</label>
            <input type="number" class="form-control" id="points" name="points" 
                   value="<?= $isEdit ? $question['points'] : '1' ?>" min="0.1" step="0.1" required>
        </div>
    </div>
    
    <div id="options-container">
        <!-- Варіанти відповідей будуть додані через JavaScript -->
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> <?= $isEdit ? 'Зберегти' : 'Додати' ?> питання
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
    
    <?php if ($isEdit): ?>
    const existingOptions = <?= json_encode($question['options'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    console.log('Existing options:', existingOptions);
    <?php else: ?>
    const existingOptions = [];
    <?php endif; ?>
    
    if (type === 'single_choice' || type === 'multiple_choice') {
        let html = '<div class="mb-3"><label class="form-label">Варіанти відповідей *</label><div id="options-list">';
        
        if (existingOptions && existingOptions.length > 0) {
            existingOptions.forEach((opt, idx) => {
                const isCorrect = opt.is_correct === true || opt.is_correct === 1 || opt.is_correct === '1';
                const optionText = opt.option_text || opt.text || '';
                html += `
                    <div class="input-group mb-2">
                        <div class="input-group-text">
                            <input type="${type === 'single_choice' ? 'radio' : 'checkbox'}" name="${type === 'single_choice' ? 'option_correct' : 'option_correct[' + idx + ']'}" value="${idx}" ${isCorrect ? 'checked' : ''}>
                        </div>
                        <input type="text" class="form-control" name="option_text[${idx}]" value="${escapeHtmlAttr(String(optionText))}" placeholder="Варіант відповіді" required>
                        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">Видалити</button>
                    </div>
                `;
            });
            optionIndex = existingOptions.length;
        } else {
            html += `
                <div class="input-group mb-2">
                    <div class="input-group-text">
                        <input type="${type === 'single_choice' ? 'radio' : 'checkbox'}" name="${type === 'single_choice' ? 'option_correct' : 'option_correct[0]'}" value="0">
                    </div>
                    <input type="text" class="form-control" name="option_text[0]" placeholder="Варіант відповіді" required>
                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">Видалити</button>
                </div>
            `;
            optionIndex = 1;
        }
        
        html += '</div><button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption()"><i class="bi bi-plus"></i> Додати варіант</button></div>';
        container.innerHTML = html;
        
    } else if (type === 'true_false') {
        const correctAnswer = existingOptions.find(opt => opt.is_correct)?.option_text || 'true';
        container.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Правильна відповідь *</label>
                <select class="form-select" name="correct_answer" required>
                    <option value="true" ${correctAnswer === 'true' ? 'selected' : ''}>Так</option>
                    <option value="false" ${correctAnswer === 'false' ? 'selected' : ''}>Ні</option>
                </select>
            </div>
        `;
    } else if (type === 'short_answer') {
        let html = '<div class="mb-3"><label class="form-label">Правильні варіанти відповіді *</label><div id="answers-list">';
        
        if (existingOptions && existingOptions.length > 0) {
            existingOptions.forEach((opt, idx) => {
                const optionText = opt.option_text || opt.text || '';
                html += `
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="correct_answer[${idx}]" value="${escapeHtmlAttr(String(optionText))}" placeholder="Правильна відповідь" required>
                        <button type="button" class="btn btn-outline-danger" onclick="removeAnswer(this)">Видалити</button>
                    </div>
                `;
            });
            answerIndex = existingOptions.length;
        } else {
            html += `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="correct_answer[0]" placeholder="Правильна відповідь" required>
                    <button type="button" class="btn btn-outline-danger" onclick="removeAnswer(this)">Видалити</button>
                </div>
            `;
            answerIndex = 1;
        }
        
        html += '</div><button type="button" class="btn btn-sm btn-outline-primary" onclick="addAnswer()"><i class="bi bi-plus"></i> Додати варіант</button><small class="form-text text-muted d-block">Можна вказати кілька правильних варіантів (наприклад, "5" та "п\'ять")</small></div>';
        container.innerHTML = html;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeHtmlAttr(text) {
    if (text === null || text === undefined) {
        return '';
    }
    const str = String(text);
    return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

let optionIndex = 1;
function addOption() {
    const type = document.getElementById('question_type').value;
    const list = document.getElementById('options-list');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <div class="input-group-text">
            <input type="${type === 'single_choice' ? 'radio' : 'checkbox'}" name="${type === 'single_choice' ? 'option_correct' : 'option_correct[' + optionIndex + ']'}" value="${optionIndex}">
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
    console.log('DOM loaded, calling toggleQuestionOptions');
    if (document.getElementById('question_type')) {
        toggleQuestionOptions();
    } else {
        console.error('question_type element not found');
    }
    
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
