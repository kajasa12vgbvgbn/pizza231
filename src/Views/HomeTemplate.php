<?php
namespace App\Views;

require_once __DIR__ . '/BaseTemplate.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Config/Config.php';

use App\Models\Product;
use App\Config\Config;

class HomeTemplate extends BaseTemplate
{
    /**
     * Путь к файлу шаблона
     */
    private const TEMPLATE_PATH = __DIR__ . '/templates/home.html.php';

    public static function getTemplate(string $content = ''): string 
    {
        // Загружаем продукты через модель
        $productModel = new Product();
        $products = $productModel->loadData() ?? [];
        
        // Генерируем HTML для карточек товаров
        $productsHtml = self::renderProducts($products);

        // Показывать ли каталог на главной
        $showCatalog = Config::SHOW_CATALOG_AT_HOME;

        // Подключаем шаблон
        ob_start();
        include self::TEMPLATE_PATH;
        $content = ob_get_clean();
        
        return parent::getTemplate($content);
    }
    
    /**
     * Рендерит карточки товаров
     */
    private static function renderProducts(array $products): string
    {
        if (empty($products)) {
            return '<p class="text-center text-muted">Товары временно отсутствуют</p>';
        }

        $html = '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
        
        foreach ($products as $product) {
            $name = htmlspecialchars($product['name'] ?? 'Без названия');
            $description = htmlspecialchars($product['description'] ?? '');
            $price = number_format($product['price'] ?? 0, 0, '.', ' ');
            $image = htmlspecialchars($product['image'] ?? '/assets/img/no-image.jpg');
            $id = (int)($product['id'] ?? 0);
            
            $html .= '
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="' . $image . '" 
                         class="card-img-top" 
                         alt="' . $name . '"
                         style="height: 200px; object-fit: cover;"
                         onerror="this.src=\'/assets/img/error.jpg\';">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">' . $name . '</h5>
                        <p class="card-text text-muted small flex-grow-1">' . $description . '</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h5 mb-0 text-primary">' . $price . ' ₽</span>
                            <a href="/product/' . $id . '" class="btn btn-outline-primary btn-sm">Подробнее</a>
                        </div>
                    </div>
                </div>
            </div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}