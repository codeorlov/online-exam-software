<?php
/** @var \App\Core\View $this */
http_response_code(500);
?>

<div class="text-center py-5">
    <h1 class="display-1">500</h1>
    <h2 class="mb-4">Внутрішня помилка сервера</h2>
    <p class="text-muted mb-4">Сталася помилка при обробці запиту.</p>
    <a href="/dashboard" class="btn btn-primary">
        <i class="bi bi-house"></i> Повернутися на головну
    </a>
</div>
