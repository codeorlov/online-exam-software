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
<body class="auth-body">
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
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-logo">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </div>
                    <h1 class="auth-logo-title"><?= $this->e($siteName) ?></h1>
                </div>
                
                <div class="auth-card-body">
                    <?php
                    $flash = $this->getFlash();
                    if ($flash):
                    ?>
                        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info') ?> alert-dismissible fade show" role="alert">
                            <i class="bi bi-<?= $flash['type'] === 'error' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'info-circle') ?>-fill me-2"></i>
                            <?= $flash['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?= $content ?>
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
