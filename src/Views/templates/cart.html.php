<?php
/**
 * Шаблон страницы корзины
 * Доступные переменные:
 * - $isEmpty - boolean, корзина пуста
 * - $cartRows - HTML строк таблицы
 * - $count - количество товаров
 * - $totalFormatted - итоговая сумма
 */
?>

<?php if ($isEmpty): ?>
<div class="container py-5">
    <div class="text-center py-5">
        <i class="bi bi-cart-x display-1 text-muted mb-3"></i>
        <h3>Ваша корзина пуста 😔</h3>
        <p class="text-muted">Добавьте товары из каталога</p>
        <a href="/catalog" class="btn btn-primary mt-3">Перейти в каталог</a>
    </div>
</div>
<?php else: ?>
<div class="container py-5">
    <h1 class="text-center mb-4">🛒 Ваша корзина</h1>
    
    <div class="card shadow-lg">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Товар</th>
                            <th>Цена</th>
                            <th>Кол-во</th>
                            <th>Сумма</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cart-items">
                        <?= $cartRows ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div>
                    <span class="text-muted">Всего товаров: </span>
                    <span class="fw-bold"><?= $count ?></span>
                </div>
                <div class="text-end">
                    <span class="text-muted d-block">Итого:</span>
                    <span class="h3 fw-bold text-success mb-0"><?= $totalFormatted ?> ₽</span>
                </div>
            </div>
            
            <div class="d-flex gap-3 justify-content-end mt-4">
                <button class="btn btn-outline-dark" id="clear-cart">
                    <i class="bi bi-trash me-2"></i>Очистить
                </button>
                <button class="btn btn-success btn-lg px-4" id="checkout-btn">
                    <i class="bi bi-check-circle me-2"></i>Оформить заказ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast уведомления -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="toastMessage">Изменения сохранены!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>
