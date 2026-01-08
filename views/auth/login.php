<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
?>

<div class="auth-header">
    <div class="auth-icon">
        <i class="bi bi-shield-lock-fill"></i>
    </div>
    <div style="flex: 1;">
        <h2 class="auth-title">Вхід до системи</h2>
    </div>
    <p class="auth-subtitle">Введіть свої дані для входу</p>
</div>

<form method="POST" action="/login" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="form-group">
        <label for="email" class="form-label">
            <i class="bi bi-envelope me-2"></i>Email
        </label>
        <input type="email" 
               class="form-control form-control-lg" 
               id="email" 
               name="email" 
               placeholder="your.email@example.com"
               required 
               autofocus>
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
                   placeholder="Введіть пароль"
                   required>
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <i class="bi bi-eye" id="password-toggle-icon"></i>
            </button>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="/forgot-password" class="auth-link">
            <i class="bi bi-question-circle me-1"></i>Забули пароль?
        </a>
    </div>
    
    <div class="d-grid mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-box-arrow-in-right me-2"></i>
            Увійти
        </button>
    </div>
</form>

<?php if (isset($registrationEnabled) && $registrationEnabled): ?>
    <div class="auth-divider">
        <span>або</span>
    </div>
    <div class="text-center">
        <p class="auth-link-text">
            Немає акаунта? 
            <a href="/register" class="auth-link">Зареєструватися</a>
        </p>
    </div>
<?php endif; ?>

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
