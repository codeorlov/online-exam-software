<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
?>

<div class="auth-header">
    <div class="auth-icon">
        <i class="bi bi-shield-lock-fill"></i>
    </div>
    <div style="flex: 1;">
        <h2 class="auth-title">Встановлення нового пароля</h2>
    </div>
    <p class="auth-subtitle">Введіть новий пароль</p>
</div>

<form method="POST" action="/reset-password" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="token" value="<?= $this->e($token) ?>">
    
    <div class="form-group">
        <label for="password" class="form-label">
            <i class="bi bi-lock me-2"></i>Новий пароль
        </label>
        <div class="password-input-wrapper">
            <input type="password" 
                   class="form-control form-control-lg" 
                   id="password" 
                   name="password" 
                   placeholder="Введіть новий пароль"
                   required
                   minlength="8"
                   autofocus>
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <i class="bi bi-eye" id="password-toggle-icon"></i>
            </button>
        </div>
        <small class="form-text text-muted">
            Пароль повинен містити мінімум 8 символів
        </small>
    </div>
    
    <div class="form-group">
        <label for="password_confirm" class="form-label">
            <i class="bi bi-lock-fill me-2"></i>Підтвердіть пароль
        </label>
        <div class="password-input-wrapper">
            <input type="password" 
                   class="form-control form-control-lg" 
                   id="password_confirm" 
                   name="password_confirm" 
                   placeholder="Повторіть новий пароль"
                   required
                   minlength="8">
            <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')">
                <i class="bi bi-eye" id="password_confirm-toggle-icon"></i>
            </button>
        </div>
    </div>
    
    <div class="d-grid mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-circle me-2"></i>
            Встановити новий пароль
        </button>
    </div>
</form>

<div class="text-center">
    <p class="auth-link-text">
        <a href="/login" class="auth-link">
            <i class="bi bi-arrow-left me-2"></i>Повернутися до входу
        </a>
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
