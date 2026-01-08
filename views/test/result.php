<?php
/** @var \App\Core\View $this */
$percentage = $attempt['max_score'] > 0 
    ? round(($attempt['score'] / $attempt['max_score']) * 100, 1) 
    : 0;
$passed = $percentage >= (float)$test['passing_score'];
?>

<div class="section-header">
    <h2>
        <i class="bi bi-trophy"></i>
        Результат тесту: <?= $this->e($test['title']) ?>
    </h2>
    <?php if (isset($canEdit) && $canEdit): ?>
    <a href="/test/<?= $test['id'] ?>/result/<?= $attempt['id'] ?>/edit" class="btn btn-primary">
        <i class="bi bi-pencil me-2"></i>Редагувати результат
    </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body text-center">
        <h3 class="result-score mb-3 <?= $passed ? 'text-success' : 'text-danger' ?>">
            <?= $attempt['score'] ?> / <?= $attempt['max_score'] ?>
        </h2>
        <h4 class="mb-3">
            <span class="badge <?= $passed ? 'bg-success' : 'bg-danger' ?>" style="font-size: 1.5rem; padding: 0.5rem 1rem;">
                <?= $percentage ?>%
            </span>
            <?php if ($passed): ?>
                <span class="badge bg-success ms-2">Пройдено</span>
            <?php else: ?>
                <span class="badge bg-danger ms-2">Не пройдено</span>
            <?php endif; ?>
        </h4>
        <div class="progress mb-3" style="height: 30px;">
            <div class="progress-bar <?= $passed ? 'bg-success' : 'bg-danger' ?>" 
                 role="progressbar" 
                 style="width: <?= min($percentage, 100) ?>%; font-size: 1rem; line-height: 30px; font-weight: bold;"
                 aria-valuenow="<?= $percentage ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                <?= $percentage ?>%
            </div>
        </div>
        <p class="text-muted mb-2">
            <strong>Прохідний бал:</strong> <?= $test['passing_score'] ?>%
        </p>
        <?php if (!empty($attempt['completed_at'])): ?>
        <p class="text-muted mb-2">
            <strong>Дата проходження:</strong> <?= date('d.m.Y H:i', strtotime($attempt['completed_at'])) ?>
        </p>
        <?php endif; ?>
        <?php
        if (!empty($attempt['started_at']) && !empty($attempt['completed_at'])) {
            $startedAt = strtotime($attempt['started_at']);
            $completedAt = strtotime($attempt['completed_at']);
            $timeSpent = $completedAt - $startedAt;
            
            if ($test['duration']) {
                $maxDurationSeconds = (int)$test['duration'] * 60;
                $timeSpent = min($timeSpent, $maxDurationSeconds);
            }
            
            $minutes = floor($timeSpent / 60);
            $seconds = $timeSpent % 60;
            $timeSpentFormatted = sprintf('%d хв %02d сек', $minutes, $seconds);
        } else {
            $timeSpentFormatted = 'Не завершено';
        }
        ?>
        <p class="text-muted mb-0">
            <strong>Час на проходження:</strong> <?= $timeSpentFormatted ?>
            <?php if ($test['duration'] && !empty($attempt['completed_at'])): ?>
                (з <?= $test['duration'] ?> хв)
            <?php endif; ?>
        </p>
    </div>
</div>

    <div class="section-header">
        <h2>
            <i class="bi bi-list-check"></i>
            Деталі відповідей
        </h2>
    </div>

<?php foreach ($results as $index => $result): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h5>Питання <?= $index + 1 ?></h5>
                <?php if ($result['is_correct']): ?>
                    <span class="badge bg-success">Правильно (+<?= $result['question']['points'] ?> балів)</span>
                <?php else: ?>
                    <span class="badge bg-danger">Неправильно (0 балів)</span>
                <?php endif; ?>
            </div>
            
            <p class="mb-3"><strong><?= $this->e($result['question']['question_text']) ?></strong></p>
            
            <div class="mb-2">
                <strong>Ваша відповідь:</strong>
                <?php
                $userAnswer = $result['user_answer'];
                $question = $result['question'];
                $questionType = $question['question_type'] ?? '';
                
                if ($userAnswer === null || $userAnswer === '' || (is_array($userAnswer) && empty($userAnswer))): ?>
                    <em>Немає відповіді</em>
                <?php else: ?>
                    <?php
                    $answerTexts = [];
                    
                    if ($questionType === 'single_choice') {
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
                            foreach ($question['options'] ?? [] as $option) {
                                if ((int)$option['id'] === $userAnswerId) {
                                    $answerTexts[] = $option['option_text'];
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if (!$found && $savedText !== null && $savedText !== '') {
                                $savedTextNormalized = mb_strtolower(trim($savedText));
                                foreach ($question['options'] ?? [] as $option) {
                                    $optionTextNormalized = mb_strtolower(trim($option['option_text']));
                                    if ($optionTextNormalized === $savedTextNormalized) {
                                        $answerTexts[] = $option['option_text'];
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if (!$found && $userAnswerId !== null) {
                            if ($savedText !== null && $savedText !== '') {
                                $answerTexts[] = $savedText . ' (видалено)';
                            } else {
                                $answerTexts[] = 'Варіант #' . $userAnswerId . ' (видалено)';
                            }
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
                            } else {
                                $userAnswersData[] = [
                                    'id' => is_numeric($decoded) ? (int)$decoded : 0,
                                    'text' => null
                                ];
                            }
                        }
                        
                        $foundIds = [];
                        foreach ($userAnswersData as $answerData) {
                            $userAnswerId = $answerData['id'];
                            $savedText = $answerData['text'] ?? null;
                            $found = false;
                            
                            foreach ($question['options'] ?? [] as $option) {
                                if ((int)$option['id'] === $userAnswerId) {
                                    $answerTexts[] = $option['option_text'];
                                    $foundIds[] = $userAnswerId;
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if (!$found && $savedText !== null && $savedText !== '') {
                                $savedTextNormalized = mb_strtolower(trim($savedText));
                                foreach ($question['options'] ?? [] as $option) {
                                    $optionTextNormalized = mb_strtolower(trim($option['option_text']));
                                    if ($optionTextNormalized === $savedTextNormalized) {
                                        $answerTexts[] = $option['option_text'];
                                        $foundIds[] = (int)$option['id'];
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            
                            if (!$found) {
                                if ($savedText !== null && $savedText !== '') {
                                    $answerTexts[] = $savedText . ' (видалено)';
                                } else {
                                    $answerTexts[] = 'Варіант #' . $userAnswerId . ' (видалено)';
                                }
                            }
                        }
                    } elseif ($questionType === 'true_false') {
                        if ($userAnswer === 'true' || $userAnswer === '1' || $userAnswer === 1 || $userAnswer === true) {
                            $answerTexts[] = 'Так';
                        } elseif ($userAnswer === 'false' || $userAnswer === '0' || $userAnswer === 0 || $userAnswer === false) {
                            $answerTexts[] = 'Ні';
                        } elseif ($userAnswer !== null && $userAnswer !== '') {
                            $answerTexts[] = $this->e($userAnswer);
                        }
                    } else {
                        $answerTexts[] = is_array($userAnswer) ? implode(', ', $userAnswer) : $userAnswer;
                    }
                    
                    echo !empty($answerTexts) ? implode(', ', array_map([$this, 'e'], $answerTexts)) : '<em>Немає відповіді</em>';
                    ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($result['question']['options'])): ?>
                <div class="mt-2">
                    <strong>Правильна відповідь:</strong>
                    <?php
                    $correctOptions = [];
                    foreach ($result['question']['options'] as $option) {
                        if ((int)$option['is_correct'] === 1) {
                            if ($questionType === 'true_false') {
                                $optionText = $option['option_text'];
                                if ($optionText === 'true' || $optionText === '1' || $optionText === 1 || $optionText === true) {
                                    $correctOptions[] = 'Так';
                                } elseif ($optionText === 'false' || $optionText === '0' || $optionText === 0 || $optionText === false) {
                                    $correctOptions[] = 'Ні';
                                } else {
                                    $correctOptions[] = $this->e($optionText);
                                }
                            } else {
                                $correctOptions[] = $this->e($option['option_text']);
                            }
                        }
                    }
                    echo implode(', ', $correctOptions);
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<div class="mt-4">
    <a href="/dashboard" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Повернутися на головну
    </a>
    <?php if (isset($canEdit) && $canEdit): ?>
    <a href="/test/<?= $test['id'] ?>/result/<?= $attempt['id'] ?>/edit" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Редагувати результат
    </a>
    <?php endif; ?>
</div>
