<?php
namespace App\Router;

// 👇 Существующие контроллеры
require_once __DIR__ . '/../Controllers/HomeController.php';
require_once __DIR__ . '/../Controllers/AboutController.php';
require_once __DIR__ . '/../Controllers/ProductController.php';
require_once __DIR__ . '/../Controllers/CatalogController.php';

// 👇 НОВЫЕ: Контроллер и модель корзины
require_once __DIR__ . '/../Models/Cart.php';
require_once __DIR__ . '/../Controllers/CartController.php';

use App\Controllers\HomeController;
use App\Controllers\AboutController;
use App\Controllers\ProductController;
use App\Controllers\CatalogController;
use App\Controllers\CartController; // 👈 Новый контроллер

class Router
{
    public function route(string $url): ?string 
    {
        $path = parse_url($url, PHP_URL_PATH);
        $pieces = explode("/", $path);
        
        $resource = $pieces[1] ?? '';

        switch ($resource) {
            case "about":
                $controller = new AboutController();
                return $controller->get();
            
            case "home":
            case "":
                $controller = new HomeController();
                return $controller->get();

            case "product":
                $product = new ProductController();
                $id = isset($pieces[2]) ? intval($pieces[2]) : 0;
                return $product->get($id);

            case "catalog":
                $catalog = new CatalogController();
                return $catalog->get();
            
            // 👇 НОВЫЕ МАРШРУТЫ ДЛЯ КОРЗИНЫ
            case "cart":
                $cart = new CartController();
                return $cart->get(); // Страница корзины
                
            case "api":
                // Обработка API-запросов
                $subResource = $pieces[2] ?? '';
                
                // API товара
                if ($subResource === 'product') {
                    $id = isset($pieces[3]) ? intval($pieces[3]) : 0;
                    $productController = new ProductController();
                    return $productController->apiGet($id);
                }
                
                // API корзины
                $cart = new CartController();
                
                if ($subResource === 'cart') {
                    $action = $pieces[3] ?? '';
                    
                    // Читаем JSON-тело запроса
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    switch ($action) {
                        case 'add':
                            return $cart->add($input);
                        case 'update':
                            return $cart->update($input);
                        case 'remove':
                            return $cart->remove($input);
                        case 'clear':
                            return $cart->clear();
                        case 'order':
                            return $cart->order($input);
                        default:
                            http_response_code(400);
                            return json_encode(['error' => 'Неизвестное действие']);
                    }
                }
                // Если не наш под маршрут — 404
                http_response_code(404);
                return json_encode(['error' => 'API endpoint not found']);
                
            default:
                http_response_code(404);
                echo "404 - Страница не найдена";
                break;
        }
        
        return null;
    }
}