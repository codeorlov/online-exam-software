<?php
/** @var \App\Core\View $this */
$csrfToken = \App\Core\Security::generateCsrfToken();
?>

<div class="section-header">
    <h2>
        <i class="bi bi-gear"></i>
        Налаштування системи
    </h2>
</div>

<form method="POST" action="/admin/settings/save">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <!-- Загальні налаштування -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-info-circle me-2"></i>Загальні налаштування
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="site_name" class="form-label">Назва сайту *</label>
                    <input type="text" class="form-control" id="site_name" name="site_name" 
                           value="<?= $this->e($settings['site_name']['value'] ?? 'Online Testing System') ?>" required>
                    <small class="form-text text-muted"><?= $this->e($settings['site_name']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="items_per_page" class="form-label">Елементів на сторінці *</label>
                    <input type="number" class="form-control" id="items_per_page" name="items_per_page" 
                           value="<?= (int)($settings['items_per_page']['value'] ?? 10) ?>" min="1" max="25" required>
                    <small class="form-text text-muted"><?= $this->e($settings['items_per_page']['description'] ?? '') ?> (від 1 до 25)</small>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                           <?= !empty($settings['maintenance_mode']['value']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="maintenance_mode">
                        Режим обслуговування
                    </label>
                </div>
                <small class="form-text text-muted"><?= $this->e($settings['maintenance_mode']['description'] ?? '') ?></small>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="registration_enabled" name="registration_enabled" 
                           <?= !isset($settings['registration_enabled']['value']) || !empty($settings['registration_enabled']['value']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="registration_enabled">
                        Дозволити реєстрацію користувачів
                    </label>
                </div>
                <small class="form-text text-muted"><?= $this->e($settings['registration_enabled']['description'] ?? 'Якщо вимкнено, тільки адміністратори зможуть створювати нових користувачів') ?></small>
            </div>
        </div>
    </div>

    <!-- Налаштування файлів -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-file-earmark me-2"></i>Налаштування файлів
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="allowed_file_types" class="form-label">Дозволені типи файлів *</label>
                    <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                           value="<?= $this->e($settings['allowed_file_types']['value'] ?? '') ?>" 
                           placeholder="pdf,doc,docx,xls,xlsx,jpg,png" required>
                    <small class="form-text text-muted"><?= $this->e($settings['allowed_file_types']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="max_file_size" class="form-label">Максимальний розмір файлу (МБ) *</label>
                    <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                           value="<?= (int)(($settings['max_file_size']['value'] ?? 10485760) / 1048576) ?>" 
                           min="1" max="25" required>
                    <small class="form-text text-muted">
                        <?= $this->e($settings['max_file_size']['description'] ?? '') ?>
                        (від 1 до 25 МБ)
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Налаштування SMTP -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-envelope me-2"></i>Налаштування SMTP (для відправки листів)
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" 
                           <?= !empty($settings['smtp_enabled']['value']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="smtp_enabled">
                        Увімкнути SMTP
                    </label>
                </div>
                <small class="form-text text-muted"><?= $this->e($settings['smtp_enabled']['description'] ?? '') ?></small>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="smtp_host" class="form-label">SMTP хост</label>
                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                           value="<?= $this->e($settings['smtp_host']['value'] ?? '') ?>" 
                           placeholder="smtp.example.com">
                    <small class="form-text text-muted"><?= $this->e($settings['smtp_host']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="smtp_port" class="form-label">SMTP порт</label>
                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                           value="<?= (int)($settings['smtp_port']['value'] ?? 587) ?>" 
                           min="1" max="65535">
                    <small class="form-text text-muted"><?= $this->e($settings['smtp_port']['description'] ?? '') ?></small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="smtp_username" class="form-label">SMTP ім'я користувача</label>
                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                           value="<?= $this->e($settings['smtp_username']['value'] ?? '') ?>">
                    <small class="form-text text-muted"><?= $this->e($settings['smtp_username']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="smtp_password" class="form-label">SMTP пароль</label>
                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                           value="<?= $this->e($settings['smtp_password']['value'] ?? '') ?>">
                    <small class="form-text text-muted"><?= $this->e($settings['smtp_password']['description'] ?? '') ?></small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="smtp_encryption" class="form-label">Шифрування</label>
                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                        <option value="tls" <?= ($settings['smtp_encryption']['value'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= ($settings['smtp_encryption']['value'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="" <?= empty($settings['smtp_encryption']['value']) ? 'selected' : '' ?>>Без шифрування</option>
                    </select>
                    <small class="form-text text-muted"><?= $this->e($settings['smtp_encryption']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="smtp_from_email" class="form-label">Email відправника</label>
                    <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" 
                           value="<?= $this->e($settings['smtp_from_email']['value'] ?? '') ?>">
                    <small class="form-text text-muted"><?= $this->e($settings['smtp_from_email']['description'] ?? '') ?></small>
                </div>
            </div>
            <div class="mb-3">
                <label for="smtp_from_name" class="form-label">Ім'я відправника</label>
                <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" 
                       value="<?= $this->e($settings['smtp_from_name']['value'] ?? '') ?>">
                <small class="form-text text-muted"><?= $this->e($settings['smtp_from_name']['description'] ?? '') ?></small>
            </div>
        </div>
    </div>

    <!-- Налаштування кольорів -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-palette me-2"></i>Налаштування кольорів
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">Налаштуйте основні кольори інтерфейсу. Використовуйте формат #000000 (шестизначний HEX код).</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="color_primary" class="form-label">Основний колір (Primary) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_primary_picker" 
                               value="<?= $this->e($settings['color_primary']['value'] ?? '#6366f1') ?>"
                               onchange="document.getElementById('color_primary').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_primary" name="color_primary" 
                               value="<?= $this->e($settings['color_primary']['value'] ?? '#6366f1') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#6366F1" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_primary']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="color_primary_dark" class="form-label">Темний основний колір (Primary Dark) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_primary_dark_picker" 
                               value="<?= $this->e($settings['color_primary_dark']['value'] ?? '#4f46e5') ?>"
                               onchange="document.getElementById('color_primary_dark').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_primary_dark" name="color_primary_dark" 
                               value="<?= $this->e($settings['color_primary_dark']['value'] ?? '#4f46e5') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#4F46E5" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_primary_dark']['description'] ?? '') ?></small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="color_primary_light" class="form-label">Світлий основний колір (Primary Light) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_primary_light_picker" 
                               value="<?= $this->e($settings['color_primary_light']['value'] ?? '#818cf8') ?>"
                               onchange="document.getElementById('color_primary_light').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_primary_light" name="color_primary_light" 
                               value="<?= $this->e($settings['color_primary_light']['value'] ?? '#818cf8') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#818CF8" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_primary_light']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="color_secondary" class="form-label">Вторинний колір (Secondary) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_secondary_picker" 
                               value="<?= $this->e($settings['color_secondary']['value'] ?? '#64748b') ?>"
                               onchange="document.getElementById('color_secondary').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_secondary" name="color_secondary" 
                               value="<?= $this->e($settings['color_secondary']['value'] ?? '#64748b') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#64748B" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_secondary']['description'] ?? '') ?></small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="color_success" class="form-label">Колір успіху (Success) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_success_picker" 
                               value="<?= $this->e($settings['color_success']['value'] ?? '#10b981') ?>"
                               onchange="document.getElementById('color_success').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_success" name="color_success" 
                               value="<?= $this->e($settings['color_success']['value'] ?? '#10b981') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#10B981" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_success']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="color_danger" class="form-label">Колір небезпеки (Danger) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_danger_picker" 
                               value="<?= $this->e($settings['color_danger']['value'] ?? '#ef4444') ?>"
                               onchange="document.getElementById('color_danger').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_danger" name="color_danger" 
                               value="<?= $this->e($settings['color_danger']['value'] ?? '#ef4444') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#EF4444" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_danger']['description'] ?? '') ?></small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="color_warning" class="form-label">Колір попередження (Warning) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_warning_picker" 
                               value="<?= $this->e($settings['color_warning']['value'] ?? '#f59e0b') ?>"
                               onchange="document.getElementById('color_warning').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_warning" name="color_warning" 
                               value="<?= $this->e($settings['color_warning']['value'] ?? '#f59e0b') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#F59E0B" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_warning']['description'] ?? '') ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="color_info" class="form-label">Інформаційний колір (Info) *</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color_info_picker" 
                               value="<?= $this->e($settings['color_info']['value'] ?? '#06b6d4') ?>"
                               onchange="document.getElementById('color_info').value = this.value.toUpperCase()">
                        <input type="text" class="form-control" id="color_info" name="color_info" 
                               value="<?= $this->e($settings['color_info']['value'] ?? '#06b6d4') ?>" 
                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#06B6D4" required>
                    </div>
                    <small class="form-text text-muted"><?= $this->e($settings['color_info']['description'] ?? '') ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Зберегти налаштування
        </button>
        <a href="/dashboard/admin" class="btn btn-secondary">
            <i class="bi bi-x-circle me-2"></i>Скасувати
        </a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorFields = [
        'color_primary', 'color_primary_dark', 'color_primary_light', 
        'color_secondary', 'color_success', 'color_danger', 
        'color_warning', 'color_info'
    ];
    
    colorFields.forEach(function(fieldName) {
        const textInput = document.getElementById(fieldName);
        const colorPicker = document.getElementById(fieldName + '_picker');
        
        if (textInput && colorPicker) {
            textInput.addEventListener('input', function() {
                let value = this.value.trim().toUpperCase();
                if (value && /^#[0-9A-F]{6}$/.test(value)) {
                    colorPicker.value = value;
                }
            });
            
            colorPicker.addEventListener('input', function() {
                textInput.value = this.value.toUpperCase();
            });
            
            textInput.addEventListener('blur', function() {
                let value = this.value.trim().toUpperCase();
                if (value && !/^#[0-9A-F]{6}$/.test(value)) {
                    this.setCustomValidity('Используйте формат #000000');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    });
});
</script>
