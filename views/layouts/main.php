<?php
/** @var \App\Core\View $this */
$settings = new \App\Models\Settings();
$siteName = $settings->get('site_name', 'Online Testing System');
$siteDescription = isset($description) ? $description : 'Система онлайн-тестування для навчальних закладів';
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$ogImage = isset($ogImage) ? $ogImage : $baseUrl . '/favicon.svg';
$pageTitle = isset($title) ? $this->e($title) . ' - ' . $this->e($siteName) : $this->e($siteName);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Basic Meta Tags -->
    <meta name="description" content="<?= $this->e($siteDescription) ?>">
    <meta name="keywords" content="онлайн тестування, освіта, тести, навчання, система тестування">
    <meta name="author" content="<?= $this->e($siteName) ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= $this->e($pageTitle) ?>">
    <meta property="og:description" content="<?= $this->e($siteDescription) ?>">
    <meta property="og:image" content="<?= $this->e($ogImage) ?>">
    <meta property="og:url" content="<?= $this->e($currentUrl) ?>">
    <meta property="og:site_name" content="<?= $this->e($siteName) ?>">
    <meta property="og:locale" content="uk_UA">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= $this->e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= $this->e($siteDescription) ?>">
    <meta name="twitter:image" content="<?= $this->e($ogImage) ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= $this->e($currentUrl) ?>">
    
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Dynamic CSS with color settings -->
    <?php
    $cssVersion = $settings->get('css_version', time());
    ?>
    <link rel="stylesheet" href="/assets/css/dynamic.php?v=<?= $cssVersion ?>">
    <style>
        .no-js-warning {
            display: none;
        }
        .js-required-content {
            display: block;
        }
        html.no-js .js-required-content {
            display: none !important;
        }
        html.no-js .no-js-warning {
            display: block !important;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fff;
            z-index: 99999;
            padding: 50px 20px;
            text-align: center;
        }
    </style>
    <noscript>
        <style>
            .js-required-content {
                display: none !important;
            }
            .no-js-warning {
                display: block !important;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: #fff;
                z-index: 99999;
                padding: 50px 20px;
                text-align: center;
            }
        </style>
    </noscript>
    <script>
        (function() {
            document.documentElement.className = document.documentElement.className.replace(/\bno-js\b/, 'js');
        })();
    </script>
</head>
<body>
    <div class="no-js-warning">
        <div style="max-width: 600px; margin: 0 auto;">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 64px; color: #dc3545; margin-bottom: 20px;"></i>
            <h1 style="color: #dc3545; margin-bottom: 20px;">JavaScript вимкнено</h1>
            <p style="font-size: 18px; margin-bottom: 30px; color: #333;">
                Для роботи цього сайту необхідно увімкнути JavaScript у вашому браузері.
            </p>
            <p style="font-size: 16px; color: #666; margin-bottom: 30px;">
                Будь ласка, увімкніть JavaScript у налаштуваннях браузера та оновіть сторінку.
            </p>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: left; margin-top: 30px;">
                <h3 style="font-size: 16px; margin-bottom: 15px;">Як увімкнути JavaScript:</h2>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><strong>Chrome/Edge:</strong> Налаштування → Конфіденційність → Налаштування сайту → JavaScript</li>
                    <li style="margin-bottom: 10px;"><strong>Firefox:</strong> Налаштування → Конфіденційність → Захист → Увімкнути JavaScript</li>
                    <li style="margin-bottom: 10px;"><strong>Safari:</strong> Налаштування → Safari → Увімкнути JavaScript</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="js-required-content">
    <?php if (\App\Core\Session::has('user_id')): ?>
        <!-- Навігація для авторизованих користувачів -->
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="/dashboard">
                    <i class="bi bi-clipboard-check-fill me-2"></i>
                    <?= $this->e($siteName) ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/dashboard">
                                <i class="bi bi-house-door"></i> Головна
                            </a>
                        </li>
                        <?php if (\App\Core\Session::get('user_role') === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/users">
                                    <i class="bi bi-people"></i> Користувачі
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/groups">
                                    <i class="bi bi-collection"></i> Групи
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/subjects">
                                    <i class="bi bi-book"></i> Предмети
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/tests">
                                    <i class="bi bi-file-text"></i> Тести
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/files">
                                    <i class="bi bi-file-earmark"></i> Файли
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/settings">
                                    <i class="bi bi-gear"></i> Налаштування
                                </a>
                            </li>
                        <?php elseif (\App\Core\Session::get('user_role') === 'teacher'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/groups">
                                    <i class="bi bi-collection"></i> Групи
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/subjects">
                                    <i class="bi bi-book"></i> Предмети
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/teacher/files">
                                    <i class="bi bi-file-earmark"></i> Файли
                                </a>
                            </li>
                        <?php elseif (\App\Core\Session::get('user_role') === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/student/files">
                                    <i class="bi bi-file-earmark"></i> Файли
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-2"></i>
                                <span><?= $this->e(\App\Core\Session::get('user_name', 'Користувач')) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="/settings">
                                        <i class="bi bi-gear me-2"></i> Налаштування
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/logout">
                                        <i class="bi bi-box-arrow-right me-2"></i> Вихід
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <main class="container-fluid py-4">
        <?php
        $flash = $this->getFlash();
        if ($flash):
        ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info') ?> alert-dismissible fade show" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="mt-5 py-4 text-center">
        <div class="container">
            <small class="text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?>. Всі права захищені.</small>
        </div>
    </footer>

    <!-- Модальне вікно підтвердження видалення -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Підтвердження видалення</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>
                <div class="modal-body">
                    Ви впевнені, що хочете видалити цей елемент? Цю дію неможливо скасувати.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Видалити</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальне вікно для повідомлень -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Повідомлення</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>
                <div class="modal-body" id="messageModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">ОК</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
    </div>
</body>
</html>
