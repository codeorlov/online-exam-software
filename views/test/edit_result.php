<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
$percentage = $attempt['max_score'] > 0 
    ? round(($attempt['score'] / $attempt['max_score']) * 100, 1) 
    : 0;
?>

<div class="section-header">
    <h2>
        <i class="bi bi-pencil"></i>
        Редагувати результат тесту: <?= $this->e($test['title']) ?>
    </h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Інформація про спробу</h5>
        <p><strong>Студент:</strong> <?= $this->e($attempt['first_name'] . ' ' . $attempt['last_name']) ?> (<?= $this->e($attempt['email']) ?>)</p>
        <p><strong>Тест:</strong> <?= $this->e($test['title']) ?></p>
        <p><strong>Поточний результат:</strong> <?= $attempt['score'] ?> / <?= $attempt['max_score'] ?> (<?= $percentage ?>%)</p>
        <p class="text-muted"><small><i class="bi bi-info-circle"></i> Після зміни відповідей результат буде автоматично перераховано</small></p>
    </div>
</div>

<form method="POST" action="/test/<?= $test['id'] ?>/result/<?= $attempt['id'] ?>/edit">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Основні параметри</h5>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <p><strong>Статус:</strong> 
                        <?php if ($attempt['status'] === 'completed'): ?>
                            <span class="badge bg-success">Завершено</span>
                        <?php elseif ($attempt['status'] === 'in_progress'): ?>
                            <span class="badge bg-warning">В процесі</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Перервано</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="started_at" class="form-label">Дата початку</label>
                    <input type="datetime-local" class="form-control" id="started_at" name="started_at" 
                           value="<?= $attempt['started_at'] ? date('Y-m-d\TH:i', strtotime($attempt['started_at'])) : '' ?>">
                    <small class="form-text text-muted">Дата та час початку проходження тесту</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="completed_at" class="form-label">Дата завершення</label>
                    <input type="datetime-local" class="form-control" id="completed_at" name="completed_at" 
                           value="<?= $attempt['completed_at'] ? date('Y-m-d\TH:i', strtotime($attempt['completed_at'])) : '' ?>">
                    <small class="form-text text-muted">Дата та час завершення тесту (залиште порожнім, якщо тест не завершено)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="section-header">
        <h2>
            <i class="bi bi-list-check"></i>
            Редагування відповідей
        </h2>
    </div>

    <?php foreach ($questionData as $index => $item): ?>
        <?php 
        $question = $item['question'];
        $userAnswer = $item['user_answer'];
        $questionType = $question['question_type'] ?? '';
        $options = $question['options'] ?? [];
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5>Питання <?= $index + 1 ?> (<?= $question['points'] ?> балів)</h5>
                    <span class="badge bg-secondary"><?= $this->e($questionType === 'single_choice' ? 'Один варіант' : ($questionType === 'multiple_choice' ? 'Кілька варіантів' : ($questionType === 'true_false' ? 'Так/Ні' : 'Коротка відповідь'))) ?></span>
                </div>
                
                <p class="mb-3"><strong><?= $this->e($question['question_text']) ?></strong></p>
                
                <?php if ($userAnswer !== null && $userAnswer !== ''): ?>
                <div class="mb-3 p-2 bg-light rounded">
                    <strong>Поточна відповідь:</strong>
                    <?php
                    if ($questionType === 'true_false') {
                        if ($userAnswer === 'true' || $userAnswer === '1' || $userAnswer === 1 || $userAnswer === true) {
                            echo 'Так';
                        } elseif ($userAnswer === 'false' || $userAnswer === '0' || $userAnswer === 0 || $userAnswer === false) {
                            echo 'Ні';
                        } else {
                            echo $this->e($userAnswer);
                        }
                    } elseif ($questionType === 'single_choice') {
                        $userAnswerId = null;
                        $savedText = null;
                        if (is_array($userAnswer) && isset($userAnswer['id'])) {
                            $userAnswerId = (int)$userAnswer['id'];
                            $savedText = $userAnswer['text'] ?? null;
                        } else {
                            $userAnswerId = is_numeric($userAnswer) ? (int)$userAnswer : null;
                        }
                        
                        $found = false;
                        if ($userAnswerId !== null) {
                            foreach ($options as $option) {
                                if ((int)$option['id'] === $userAnswerId) {
                                    echo $this->e($option['option_text']);
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if (!$found && $savedText !== null && $savedText !== '') {
                                $savedTextNormalized = mb_strtolower(trim($savedText));
                                foreach ($options as $option) {
                                    $optionTextNormalized = mb_strtolower(trim($option['option_text']));
                                    if ($optionTextNormalized === $savedTextNormalized) {
                                        echo $this->e($option['option_text']);
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if (!$found && $userAnswerId !== null) {
                            if ($savedText !== null && $savedText !== '') {
                                echo '<em>' . $this->e($savedText) . ' (видалено)</em>';
                            } else {
                                echo '<em>Варіант #' . $userAnswerId . ' (видалено)</em>';
                            }
                        } elseif (!$found) {
                            echo '<em>Варіант не знайдено</em>';
                        }
                    } elseif ($questionType === 'multiple_choice') {
                        $userAnswersData = [];
                        if (is_array($userAnswer)) {
                            foreach ($userAnswer as $val) {
                                if (is_array($val) && isset($val['id'])) {
                                    $userAnswersData[] = [
                                        'id' => (int)$val['id'],
                                        'text' => $val['text'] ?? null
                                    ];
                                } elseif (is_numeric($val)) {
                                    $userAnswersData[] = [
                                        'id' => (int)$val,
                                        'text' => null
                                    ];
                                }
                            }
                        } elseif (is_string($userAnswer)) {
                            $decoded = json_decode($userAnswer, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $val) {
                                    if (is_array($val) && isset($val['id'])) {
                                        $userAnswersData[] = [
                                            'id' => (int)$val['id'],
                                            'text' => $val['text'] ?? null
                                        ];
                                    } elseif (is_numeric($val)) {
                                        $userAnswersData[] = [
                                            'id' => (int)$val,
                                            'text' => null
                                        ];
                                    }
                                }
                            } elseif (is_numeric($decoded)) {
                                $userAnswersData[] = [
                                    'id' => (int)$decoded,
                                    'text' => null
                                ];
                            }
                        }
                        
                        $answerTexts = [];
                        foreach ($userAnswersData as $answerData) {
                            $userAnswerId = $answerData['id'];
                            $savedText = $answerData['text'] ?? null;
                            $found = false;
                            
                            foreach ($options as $option) {
                                if ((int)$option['id'] === $userAnswerId) {
                                    $answerTexts[] = $this->e($option['option_text']);
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if (!$found && $savedText !== null && $savedText !== '') {
                                $savedTextNormalized = mb_strtolower(trim($savedText));
                                foreach ($options as $option) {
                                    $optionTextNormalized = mb_strtolower(trim($option['option_text']));
                                    if ($optionTextNormalized === $savedTextNormalized) {
                                        $answerTexts[] = $this->e($option['option_text']);
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            
                            if (!$found) {
                                if ($savedText !== null && $savedText !== '') {
                                    $answerTexts[] = $this->e($savedText) . ' (видалено)';
                                } else {
                                    $answerTexts[] = 'Варіант #' . $userAnswerId . ' (видалено)';
                                }
                            }
                        }
                        echo !empty($answerTexts) ? implode(', ', $answerTexts) : '<em>Немає відповіді</em>';
                    } else {
                        echo $this->e($userAnswer);
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
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
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                       name="answers[<?= $question['id'] ?>]" 
                                       id="answer_<?= $question['id'] ?>_<?= $option['id'] ?>" 
                                       value="<?= $option['id'] ?>"
                                       <?= ($userAnswerId !== null && $userAnswerId === (int)$option['id']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="answer_<?= $question['id'] ?>_<?= $option['id'] ?>">
                                    <?= $this->e($option['option_text']) ?>
                                    <?php if ((int)$option['is_correct'] === 1): ?>
                                        <span class="badge bg-success ms-2">Правильний</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   id="answer_<?= $question['id'] ?>_none" 
                                   value=""
                                   <?= ($userAnswerId === null || $userAnswer === null || $userAnswer === '') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="answer_<?= $question['id'] ?>_none">
                                <em>Немає відповіді</em>
                            </label>
                        </div>
                        
                    <?php elseif ($questionType === 'multiple_choice'): ?>
                        <?php 
                        $userAnswerIds = [];
                        if ($userAnswer !== null) {
                            if (is_array($userAnswer)) {
                                foreach ($userAnswer as $val) {
                                    if (is_array($val) && isset($val['id'])) {
                                        $userAnswerIds[] = (int)$val['id'];
                                    } elseif (is_numeric($val)) {
                                        $userAnswerIds[] = (int)$val;
                                    }
                                }
                            } else {
                                $decoded = json_decode($userAnswer, true);
                                if (is_array($decoded)) {
                                    foreach ($decoded as $val) {
                                        if (is_array($val) && isset($val['id'])) {
                                            $userAnswerIds[] = (int)$val['id'];
                                        } elseif (is_numeric($val)) {
                                            $userAnswerIds[] = (int)$val;
                                        }
                                    }
                                } elseif (is_numeric($decoded)) {
                                    $userAnswerIds[] = (int)$decoded;
                                }
                            }
                        }
                        ?>
                        <?php foreach ($options as $option): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="answers[<?= $question['id'] ?>][]" 
                                       id="answer_<?= $question['id'] ?>_<?= $option['id'] ?>" 
                                       value="<?= $option['id'] ?>"
                                       <?= in_array((int)$option['id'], $userAnswerIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="answer_<?= $question['id'] ?>_<?= $option['id'] ?>">
                                    <?= $this->e($option['option_text']) ?>
                                    <?php if ((int)$option['is_correct'] === 1): ?>
                                        <span class="badge bg-success ms-2">Правильний</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div class="form-text text-muted mt-2">
                            <em>Для відсутності відповіді зніміть всі галочки</em>
                        </div>
                        
                    <?php elseif ($questionType === 'true_false'): ?>
                        <?php 
                        $currentAnswer = $userAnswer;
                        if ($currentAnswer === '1' || $currentAnswer === 1 || $currentAnswer === true) {
                            $currentAnswer = 'true';
                        } elseif ($currentAnswer === '0' || $currentAnswer === 0 || $currentAnswer === false) {
                            $currentAnswer = 'false';
                        } elseif ($currentAnswer === null || $currentAnswer === '') {
                            $currentAnswer = '';
                        }
                        ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   id="answer_<?= $question['id'] ?>_true" 
                                   value="true"
                                   <?= ($currentAnswer === 'true') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="answer_<?= $question['id'] ?>_true">
                                Так
                                <?php 
                                $correctOption = null;
                                foreach ($options as $opt) {
                                    if ((int)$opt['is_correct'] === 1 && $opt['option_text'] === 'true') {
                                        $correctOption = true;
                                        break;
                                    }
                                }
                                if ($correctOption): ?>
                                    <span class="badge bg-success ms-2">Правильний</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   id="answer_<?= $question['id'] ?>_false" 
                                   value="false"
                                   <?= ($currentAnswer === 'false') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="answer_<?= $question['id'] ?>_false">
                                Ні
                                <?php 
                                $correctOption = null;
                                foreach ($options as $opt) {
                                    if ((int)$opt['is_correct'] === 1 && $opt['option_text'] === 'false') {
                                        $correctOption = true;
                                        break;
                                    }
                                }
                                if ($correctOption): ?>
                                    <span class="badge bg-success ms-2">Правильний</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   id="answer_<?= $question['id'] ?>_none" 
                                   value=""
                                   <?= ($currentAnswer === null || $currentAnswer === '') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="answer_<?= $question['id'] ?>_none">
                                <em>Немає відповіді</em>
                            </label>
                        </div>
                        
                    <?php elseif ($questionType === 'short_answer'): ?>
                        <div class="mb-2">
                            <label for="answer_<?= $question['id'] ?>" class="form-label">Відповідь:</label>
                            <input type="text" class="form-control" 
                                   id="answer_<?= $question['id'] ?>" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   value="<?= $userAnswer !== null ? $this->e($userAnswer) : '' ?>"
                                   placeholder="Введіть відповідь або залиште порожнім">
                            <small class="form-text text-muted">
                                Правильні варіанти: 
                                <?php 
                                $correctAnswers = [];
                                foreach ($options as $opt) {
                                    if ((int)$opt['is_correct'] === 1) {
                                        $correctAnswers[] = $this->e($opt['option_text']);
                                    }
                                }
                                echo !empty($correctAnswers) ? implode(', ', $correctAnswers) : 'не вказано';
                                ?>
                            </small>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" 
                                       id="clear_answer_<?= $question['id'] ?>" 
                                       onchange="document.getElementById('answer_<?= $question['id'] ?>').value = this.checked ? '' : document.getElementById('answer_<?= $question['id'] ?>').value">
                                <label class="form-check-label" for="clear_answer_<?= $question['id'] ?>">
                                    <em>Очистити відповідь (Немає відповіді)</em>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Зберегти зміни та перерахувати результат
        </button>
        <a href="/test/<?= $test['id'] ?>/result/<?= $attempt['id'] ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Скасувати
        </a>
    </div>
</form>
