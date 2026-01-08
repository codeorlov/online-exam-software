<?php
/** @var \App\Core\View $this */
http_response_code(404);
?>

<div class="text-center py-5">
    <h1 class="display-1">404</h1>
    <h2 class="mb-4">Сторінку не знайдено</h2>
    <p class="text-muted mb-4">Запитувана сторінка не існує.</p>
    <a href="/dashboard" class="btn btn-primary">
        <i class="bi bi-house"></i> Повернутися на головну
    </a>
</div>
