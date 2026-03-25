<?php
/**
 * Шаблон главной страницы
 * Доступные переменные:
 * - $productsHtml - HTML карточек товаров
 */
?>

<!-- Герой-блок -->
<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold">Добро пожаловать на сайт запчастей для всех марок авто!</h1>
        <p class="lead">Запчасти разных марок в наличии и под заказ.</p>
        <a href="/catalog" class="btn btn-dark btn-lg mt-3">Каталог</a>
    </div>
</div>

<div class="container">
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h2 class="mb-3">Почему выбирают нас?</h2>
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success"></i> Оригинальные запчасти</li>
                <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success"></i> Наличие большого количества деталей</li>
                <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success"></i> Доступные цены</li>
            </ul>
        </div>
        <div class="col-md-6">
            <img src="/assets/img/123.jpg" 
                 alt="Запчасти" 
                 class="img-fluid rounded shadow-lg"
                 onerror="this.src='/assets/img/error.jpg';">
        </div>
    </div>
    
    <!-- Секция с товарами -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-4">Каталог</h2>
            <?= $productsHtml ?>
        </div>
    </div>
</div>
