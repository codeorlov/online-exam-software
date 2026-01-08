<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
?>

<div class="auth-header">
    <div class="auth-icon">
        <i class="bi bi-person-plus-fill"></i>
    </div>
    <div style="flex: 1;">
        <h2 class="auth-title">Реєстрація</h2>
    </div>
    <p class="auth-subtitle">Створіть новий акаунт</p>
</div>

<form method="POST" action="/register" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="first_name" class="form-label">
                    <i class="bi bi-person me-2"></i>Ім'я
                </label>
                <input type="text" 
                       class="form-control form-control-lg" 
                       id="first_name" 
                       name="first_name" 
                       placeholder="Іван"
                       required>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="last_name" class="form-label">
                    <i class="bi bi-person me-2"></i>Прізвище
                </label>
                <input type="text" 
                       class="form-control form-control-lg" 
                       id="last_name" 
                       name="last_name" 
                       placeholder="Іванов"
                       required>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="email" class="form-label">
            <i class="bi bi-envelope me-2"></i>Email
        </label>
        <input type="email" 
               class="form-control form-control-lg" 
               id="email" 
               name="email" 
               placeholder="your.email@example.com"
               required>
    </div>
    
    <div class="form-group">
        <label for="password" class="form-label">
            <i class="bi bi-lock me-2"></i>Пароль
        </label>
        <div class="password-input-wrapper">
            <input type="password" 
                   class="form-control form-control-lg" 
                   id="password" 
                   name="password" 
                   placeholder="Створіть надійний пароль"
                   required>
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <i class="bi bi-eye" id="password-toggle-icon"></i>
            </button>
        </div>
        <small class="form-text">
            <i class="bi bi-info-circle me-1"></i>
            Мінімум <?= PASSWORD_MIN_LENGTH ?> символів, повинна бути велика та мала літера, цифра
        </small>
    </div>
    
    <div class="form-group">
        <label for="password_confirm" class="form-label">
            <i class="bi bi-lock-fill me-2"></i>Підтвердження пароля
        </label>
        <div class="password-input-wrapper">
            <input type="password" 
                   class="form-control form-control-lg" 
                   id="password_confirm" 
                   name="password_confirm" 
                   placeholder="Повторіть пароль"
                   required>
            <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')">
                <i class="bi bi-eye" id="password_confirm-toggle-icon"></i>
            </button>
        </div>
    </div>
    
    <div class="d-grid mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-person-plus me-2"></i>
            Зареєструватися
        </button>
    </div>
</form>

<div class="auth-divider">
        <span>або</span>
</div>
<div class="text-center">
    <p class="auth-link-text">
        Вже є акаунт? 
        <a href="/login" class="auth-link">Увійти</a>
    </p>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-toggle-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
