<?php
// 👇 Подключаем автозагрузку или вручную нужные файлы
require_once __DIR__ . '/src/Router/Router.php';

// Контроллеры
require_once __DIR__ . '/src/Controllers/HomeController.php';
require_once __DIR__ . '/src/Controllers/AboutController.php';
require_once __DIR__ . '/src/Controllers/ProductController.php';
require_once __DIR__ . '/src/Controllers/CatalogController.php';
require_once __DIR__ . '/src/Controllers/CartController.php'; // 👈 Новый

// Модели
require_once __DIR__ . '/src/Models/Cart.php'; // 👈 Новая модель

// Шаблоны
require_once __DIR__ . '/src/Views/BaseTemplate.php';
require_once __DIR__ . '/src/Views/HomeTemplate.php';
require_once __DIR__ . '/src/Views/AboutTemplate.php';
require_once __DIR__ . '/src/Views/CartTemplate.php'; // 👈 Новый шаблон

use App\Router\Router;

$router = new Router();
$url = $_SERVER['REQUEST_URI'];

// 👇 Для API возвращаем JSON-заголовки
if (str_starts_with($url, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
}

echo $router->route($url);