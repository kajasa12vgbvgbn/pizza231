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
