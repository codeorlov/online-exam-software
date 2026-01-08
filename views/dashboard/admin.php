<?php
/** @var \App\Core\View $this */
?>

<div class="section-header">
    <h2>
        <i class="bi bi-speedometer2"></i>
        Панель адміністратора
    </h2>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card stats-card text-primary">
            <div class="card-body">
                <h3><?= $stats['total_users'] ?></h3>
                <p class="card-text">Всього користувачів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-success">
            <div class="card-body">
                <h3><?= $stats['total_students'] ?></h3>
                <p class="card-text">Студентів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-info">
            <div class="card-body">
                <h3><?= $stats['total_teachers'] ?></h3>
                <p class="card-text">Вчителів</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-warning">
            <div class="card-body">
                <h3><?= $stats['total_tests'] ?></h3>
                <p class="card-text">Тестів</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card action-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-people"></i> Управління користувачами
                </h5>
                <p class="card-text">Створення, редагування та видалення користувачів</p>
                <a href="/admin/users" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-2"></i>Перейти
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card action-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-collection"></i> Управління групами
                </h5>
                <p class="card-text">Створення та управління групами студентів</p>
                <a href="/admin/groups" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-2"></i>Перейти
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card action-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-book"></i> Управління предметами
                </h5>
                <p class="card-text">Створення та управління предметами</p>
                <a href="/admin/subjects" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-2"></i>Перейти
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card action-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-text"></i> Управління тестами
                </h5>
                <p class="card-text">Перегляд та управління всіма тестами</p>
                <a href="/admin/tests" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-2"></i>Перейти
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card action-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark"></i> Управління файлами
                </h5>
                <p class="card-text">Завантаження та управління файлами</p>
                <a href="/admin/files" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-2"></i>Перейти
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card action-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-gear"></i> Управління налаштуваннями
                </h5>
                <p class="card-text">Налаштування системи, SMTP, файли</p>
                <a href="/admin/settings" class="btn btn-primary">
                    <i class="bi bi-arrow-right me-2"></i>Перейти
                </a>
            </div>
        </div>
    </div>
</div>
