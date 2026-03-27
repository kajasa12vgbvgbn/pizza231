<?php
/**
 * Шаблон страницы каталога
 * Доступные переменные:
 * - $search - текущий поисковый запрос
 * - $searchInfo - HTML с информацией о результатах поиска
 * - $productsGrid - HTML сетки товаров
 */
?>

<div class="container py-5">
    <!-- Заголовок + Поиск -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-5 fw-bold mb-4">Каталог товаров</h1>
            
            <!-- Форма поиска -->
            <form method="GET" action="/catalog" class="col-md-6 col-lg-4 mx-auto">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control border-start-0 ps-0" 
                        placeholder="Поиск товаров..." 
                        value="<?= htmlspecialchars($search) ?>"
                        aria-label="Поиск">
                    <button class="btn btn-primary px-4" type="submit">Найти</button>
                </div>
            </form>
            
            <!-- Результаты поиска -->
            <?= $searchInfo ?>
        </div>
    </div>
    
    <!-- Сетка товаров -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?= $productsGrid ?>
    </div>
</div>

<!-- Полноэкранный Toast -->
<div id="fullscreenToast" class="fullscreen-toast" aria-hidden="true">
    <button class="toast-close" id="closeFullscreenToast" aria-label="Закрыть">
        <i class="bi bi-x-lg"></i>
    </button>
    
    <video class="toast-video" id="toastVideo" autoplay muted playsinline loop>
        <source src="/assets/img/cart_video.mp4" type="video/mp4">
        Ваш браузер не поддерживает видео.
    </video>
    
    <div class="toast-message" id="fullscreenToastMessage">
        🎉 Товар добавлен в корзину!
    </div>
</div>

<!-- Стандартный Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="toastMessage">Товар добавлен в корзину!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toastEl = document.getElementById("cartToast");
    if (toastEl && typeof bootstrap !== "undefined") {
        window.cartToast = new bootstrap.Toast(toastEl, { delay: 3000 });
    }
    
    // Закрытие полноэкранного toast
    document.getElementById('closeFullscreenToast')?.addEventListener('click', function() {
        const toast = document.getElementById('fullscreenToast');
        toast.classList.remove('show');
        toast.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('toast-open');
    });
});
</script>
