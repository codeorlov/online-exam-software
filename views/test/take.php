<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$totalQuestions = count($questions);
$progress = $totalQuestions > 0 ? round((($currentQuestionIndex + 1) / $totalQuestions) * 100) : 0;
?>

<div class="progress-container mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div>
            <strong>Питання <?= $currentQuestionIndex + 1 ?> з <?= $totalQuestions ?></strong>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (isset($maxAttempts) && $maxAttempts > 0): ?>
                <div class="badge bg-info text-dark">
                    <i class="bi bi-repeat me-1"></i>
                    Спроби: <?= $userAttemptsCount ?> / <?= $maxAttempts ?>
                    <?php if (isset($remainingAttempts) && $remainingAttempts !== null): ?>
                        (залишилось: <?= $remainingAttempts ?>)
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($test['duration'] && isset($endTime) && $endTime): ?>
                <div id="test-timer" class="badge bg-warning text-dark" 
                     data-end-time="<?= $endTime ?>" 
                     data-server-time="<?= time() ?>"
                     data-time-left="<?= $timeLeft !== null ? max(0, $timeLeft) : 0 ?>">
                    <span id="timer-display"><?= $timeLeft !== null && $timeLeft > 0 ? sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) : '00:00' ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="progress" style="height: 8px;">
        <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%"></div>
    </div>
</div>

<div class="question-card">
    <div class="question-number mb-3">Питання <?= $currentQuestionIndex + 1 ?></div>
    <h4 class="mb-4"><?= $this->e($currentQuestion['question_text']) ?></h4>
    
    <?php
    $userAnswer = $answers[$currentQuestion['id']] ?? null;
    $questionType = $currentQuestion['question_type'];
    $options = $currentQuestion['options'] ?? [];
    
    $questionTypeDescriptions = [
        'single_choice' => 'У цьому питанні може бути лише одна правильна відповідь.',
        'multiple_choice' => 'У цьому питанні може бути кілька правильних відповідей.',
        'true_false' => 'Оберіть один варіант: Так або Ні.',
        'short_answer' => 'Введіть вашу відповідь у текстовому полі.'
    ];
    ?>
    
    <?php if (isset($questionTypeDescriptions[$questionType])): ?>
        <div class="alert alert-info mb-4" role="alert" data-persistent="true">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Підказка:</strong> <?= $questionTypeDescriptions[$questionType] ?>
        </div>
    <?php endif; ?>
    
    <form class="test-question-form" method="POST" action="/test/<?= $test['id'] ?>/save/<?= $attemptId ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="question_id" value="<?= $currentQuestion['id'] ?>">
        <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
        <input type="hidden" name="test_id" value="<?= $test['id'] ?>">
        <input type="hidden" name="question_type" value="<?= $currentQuestion['question_type'] ?>">
        
        <?php if ($questionType === 'single_choice'): ?>
            <?php 
            $userAnswerId = null;
            if (is_array($userAnswer) && isset($userAnswer['id'])) {
                $userAnswerId = (int)$userAnswer['id'];
            } elseif (is_numeric($userAnswer)) {
                $userAnswerId = (int)$userAnswer;
            }
            ?>
            <?php foreach ($options as $option): ?>
                <div class="option-item" onclick="document.getElementById('option_<?= $option['id'] ?>').checked = true; this.classList.add('selected'); document.querySelectorAll('.option-item').forEach(el => { if (el !== this) el.classList.remove('selected'); });">
                    <input type="radio" 
                           name="answer" 
                           id="option_<?= $option['id'] ?>" 
                           value="<?= $option['id'] ?>"
                           <?= $userAnswerId === (int)$option['id'] ? 'checked' : '' ?>
                           onchange="this.closest('.option-item').classList.toggle('selected', this.checked);">
                    <label for="option_<?= $option['id'] ?>" class="ms-2" style="cursor: pointer; flex: 1;">
                        <?= $this->e($option['option_text']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
            
        <?php elseif ($questionType === 'multiple_choice'): ?>
            <?php 
            $selectedAnswerIds = [];
            if (is_array($userAnswer)) {
                foreach ($userAnswer as $val) {
                    if (is_array($val) && isset($val['id'])) {
                        $selectedAnswerIds[] = (int)$val['id'];
                    } elseif (is_numeric($val)) {
                        $selectedAnswerIds[] = (int)$val;
                    }
                }
            } elseif ($userAnswer) {
                $decoded = json_decode($userAnswer, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $val) {
                        if (is_array($val) && isset($val['id'])) {
                            $selectedAnswerIds[] = (int)$val['id'];
                        } elseif (is_numeric($val)) {
                            $selectedAnswerIds[] = (int)$val;
                        }
                    }
                } elseif (is_numeric($decoded)) {
                    $selectedAnswerIds[] = (int)$decoded;
                }
            }
            ?>
            <?php foreach ($options as $option): ?>
                <div class="option-item" onclick="const cb = document.getElementById('option_<?= $option['id'] ?>'); cb.checked = !cb.checked; this.classList.toggle('selected', cb.checked);">
                    <input type="checkbox" 
                           name="answer[]" 
                           id="option_<?= $option['id'] ?>" 
                           value="<?= $option['id'] ?>"
                           <?= in_array((int)$option['id'], $selectedAnswerIds, true) ? 'checked' : '' ?>
                           onchange="this.closest('.option-item').classList.toggle('selected', this.checked);">
                    <label for="option_<?= $option['id'] ?>" class="ms-2" style="cursor: pointer; flex: 1;">
                        <?= $this->e($option['option_text']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
            
        <?php elseif ($questionType === 'true_false'): ?>
            <?php foreach ($options as $option): ?>
                <div class="option-item" onclick="document.getElementById('option_<?= $option['id'] ?>').checked = true; this.classList.add('selected'); document.querySelectorAll('.option-item').forEach(el => { if (el !== this) el.classList.remove('selected'); });">
                    <input type="radio" 
                           name="answer" 
                           id="option_<?= $option['id'] ?>" 
                           value="<?= $this->e($option['option_text']) ?>"
                           <?= $userAnswer === $option['option_text'] ? 'checked' : '' ?>
                           onchange="this.closest('.option-item').classList.toggle('selected', this.checked);">
                    <label for="option_<?= $option['id'] ?>" class="ms-2" style="cursor: pointer; flex: 1;">
                        <?= $option['option_text'] === 'true' ? 'Так' : 'Ні' ?>
                    </label>
                </div>
            <?php endforeach; ?>
            
        <?php elseif ($questionType === 'short_answer'): ?>
            <div class="mb-3">
                <textarea class="form-control" 
                          name="answer" 
                          rows="3" 
                          placeholder="Введіть вашу відповідь"><?= $userAnswer ? $this->e($userAnswer) : '' ?></textarea>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 d-flex justify-content-between">
            <?php if ($currentQuestionIndex > 0): ?>
                <button type="button" class="btn btn-outline-secondary" onclick="goToQuestion(<?= $currentQuestionIndex - 1 ?>)">
                    <i class="bi bi-arrow-left"></i> Назад
                </button>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            
            <?php if ($currentQuestionIndex < $totalQuestions - 1): ?>
                <button type="button" class="btn btn-primary" onclick="saveAndNext(<?= $currentQuestionIndex + 1 ?>)">
                    Далі <i class="bi bi-arrow-right"></i>
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-success" id="completeTestBtn" onclick="saveAndComplete()">
                    <i class="bi bi-check-circle"></i> Завершити тест
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.test-question-form');
    if (form) {
        const inputs = form.querySelectorAll('input[type="radio"], input[type="checkbox"], textarea, input[type="text"]');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                saveAnswer(form);
            });
        });
    }

<?php if ($test['duration'] && isset($endTime) && $endTime): ?>
    const timerElement = document.getElementById('test-timer');
    if (timerElement) {
        const endTime = parseInt(timerElement.getAttribute('data-end-time'));
        const serverTime = parseInt(timerElement.getAttribute('data-server-time'));
        const initialTimeLeft = parseInt(timerElement.getAttribute('data-time-left'));
        const testId = <?= $test['id'] ?>;
        const attemptId = <?= $attemptId ?>;
        
        const clientTime = Math.floor(Date.now() / 1000);
        const timeDiff = clientTime - serverTime;
        
        function updateTimer() {
            const currentTime = Math.floor(Date.now() / 1000) - timeDiff;
            const timeLeft = Math.max(0, endTime - currentTime);
            
            if (timeLeft <= 0) {
                clearInterval(interval);
                const modal = new bootstrap.Modal(document.getElementById('messageModal'));
                document.getElementById('messageModalLabel').textContent = 'Час вийшов';
                document.getElementById('messageModalBody').textContent = 'Час на проходження тесту вийшов. Тест буде автоматично завершено.';
                document.getElementById('messageModal').querySelector('.btn-primary').onclick = function() {
                    window.location.href = '/test/' + testId + '/complete/' + attemptId;
                };
                modal.show();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const display = document.getElementById('timer-display');
            if (display) {
                display.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
            
            if (timeLeft <= 300) {
                timerElement.classList.remove('bg-warning');
                timerElement.classList.add('bg-danger');
            }
        }
        
        updateTimer();
        const interval = setInterval(updateTimer, 1000);
        
        window.addEventListener('beforeunload', function() {
            clearInterval(interval);
        });
    }
<?php endif; ?>
});

function saveAndNext(nextIndex) {
    const form = document.querySelector('.test-question-form');
    if (form) {
        saveAnswer(form).then(function() {
            window.location.href = '/test/<?= $test['id'] ?>/take/<?= $attemptId ?>?q=' + nextIndex;
        }).catch(function(error) {
            console.error('Error saving answer:', error);
            window.location.href = '/test/<?= $test['id'] ?>/take/<?= $attemptId ?>?q=' + nextIndex;
        });
    } else {
        window.location.href = '/test/<?= $test['id'] ?>/take/<?= $attemptId ?>?q=' + nextIndex;
    }
}

function saveAndComplete() {
    const form = document.querySelector('.test-question-form');
    if (form) {
        saveAnswer(form).then(function() {
            const modal = new bootstrap.Modal(document.getElementById('messageModal'));
            document.getElementById('messageModalLabel').textContent = 'Завершення тесту';
            document.getElementById('messageModalBody').textContent = 'Ви впевнені, що хочете завершити тест? Після завершення ви не зможете змінити відповіді.';
            const confirmBtn = document.getElementById('messageModal').querySelector('.btn-primary');
            confirmBtn.textContent = 'Завершити';
            confirmBtn.onclick = function() {
                window.location.href = '/test/<?= $test['id'] ?>/complete/<?= $attemptId ?>';
            };
            modal.show();
        }).catch(function(error) {
            console.error('Error saving answer:', error);
            const modal = new bootstrap.Modal(document.getElementById('messageModal'));
            document.getElementById('messageModalLabel').textContent = 'Завершення тесту';
            document.getElementById('messageModalBody').textContent = 'Ви впевнені, що хочете завершити тест? Після завершення ви не зможете змінити відповіді.';
            const confirmBtn = document.getElementById('messageModal').querySelector('.btn-primary');
            confirmBtn.textContent = 'Завершити';
            confirmBtn.onclick = function() {
                window.location.href = '/test/<?= $test['id'] ?>/complete/<?= $attemptId ?>';
            };
            modal.show();
        });
    } else {
        const modal = new bootstrap.Modal(document.getElementById('messageModal'));
        document.getElementById('messageModalLabel').textContent = 'Завершение теста';
        document.getElementById('messageModalBody').textContent = 'Ви впевнені, що хочете завершити тест? Після завершення ви не зможете змінити відповіді.';
        const confirmBtn = document.getElementById('messageModal').querySelector('.btn-primary');
        confirmBtn.textContent = 'Завершить';
        confirmBtn.onclick = function() {
            window.location.href = '/test/<?= $test['id'] ?>/complete/<?= $attemptId ?>';
        };
        modal.show();
    }
}

function goToQuestion(prevIndex) {
    const form = document.querySelector('.test-question-form');
    if (form) {
        saveAnswer(form);
    }
    window.location.href = '/test/<?= $test['id'] ?>/take/<?= $attemptId ?>?q=' + prevIndex;
}
</script>
