<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
?>

<div class="auth-header">
    <div class="auth-icon">
        <i class="bi bi-key-fill"></i>
    </div>
    <div style="flex: 1;">
        <h2 class="auth-title">Відновлення пароля</h2>
    </div>
    <p class="auth-subtitle">Введіть email для отримання інструкції</p>
</div>

<form method="POST" action="/forgot-password" class="auth-form">
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
    
    <div class="d-grid mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-send me-2"></i>
            Надіслати інструкцію
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
