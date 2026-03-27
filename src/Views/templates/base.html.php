<?php
/**
 * Базовый шаблон страницы
 * Доступные переменные:
 * - $content - основной контент страницы
 */

// Запуск сессии для проверки авторизации
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'name' => $_SESSION['user_name'] ?? 'Пользователь',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кемеровский кооперативный техникум</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container"> 
            <a class="navbar-brand" href="/">
                <img src="/assets/img/logo.svg" alt="Логотип" width="120" height="40">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/catalog">Каталог</a>
                    </li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="/cart">
                            <i class="bi bi-cart"></i>
                            <span class="cart-counter badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" 
                                style="display: none; font-size: 0.7rem;">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about">О нас</a>
                    </li>
                    <?php if ($currentUser): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($currentUser['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($currentUser['email']) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/logout">Выйти</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login">
                            <i class="bi bi-person me-1"></i>Вход
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <?= $content ?>
    </main>

    <footer class="footer text-center py-3">
        <div class="container">
            <p class="mb-0">&copy; 2026 Кемеровский кооперативный техникум. Все права защищены.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/cart.js"></script>
</body>
</html>
