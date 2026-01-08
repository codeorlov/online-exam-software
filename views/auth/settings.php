<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
?>

<div class="section-header">
    <h2>
        <i class="bi bi-gear"></i>
        Налаштування
    </h2>
</div>

<form method="POST" action="/settings/update">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-person me-2"></i>Особиста інформація
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">Ім'я *</label>
                    <input type="text" 
                           class="form-control" 
                           id="first_name" 
                           name="first_name" 
                           value="<?= $this->e($user['first_name']) ?>"
                           required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Прізвище *</label>
                    <input type="text" 
                           class="form-control" 
                           id="last_name" 
                           name="last_name" 
                           value="<?= $this->e($user['last_name']) ?>"
                           required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       value="<?= $this->e($user['email']) ?>"
                       required>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-lock me-2"></i>Зміна пароля
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="current_password" class="form-label">Поточний пароль</label>
                <input type="password" 
                       class="form-control" 
                       id="current_password" 
                       name="current_password" 
                       placeholder="Залиште порожнім, якщо не хочете змінювати пароль">
            </div>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">Новий пароль</label>
                <input type="password" 
                       class="form-control" 
                       id="new_password" 
                       name="new_password" 
                       placeholder="Залиште порожнім, якщо не хочете змінювати пароль">
                <small class="form-text text-muted">
                    Мінімум <?= PASSWORD_MIN_LENGTH ?> символів, повинна бути велика та мала літера, цифра
                </small>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Підтвердження нового пароля</label>
                <input type="password" 
                       class="form-control" 
                       id="confirm_password" 
                       name="confirm_password" 
                       placeholder="Повторіть новий пароль">
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-envelope me-2"></i>Сповіщення
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                           value="1" <?= !empty($user['email_notifications']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="email_notifications">
                        Отримувати email сповіщення
                    </label>
                </div>
                <small class="form-text text-muted">
                    Ви будете отримувати сповіщення на email про важливі події (завершення тестів, нові файли тощо)
                </small>
            </div>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Зберегти зміни
        </button>
        <a href="/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-2"></i>Скасувати
        </a>
    </div>
</form>
