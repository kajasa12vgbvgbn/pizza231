<?php
namespace App\Views;

require_once __DIR__ . '/BaseTemplate.php';

class CatalogTemplate extends BaseTemplate
{
    /**
     * Путь к файлу шаблона
     */
    private const TEMPLATE_PATH = __DIR__ . '/templates/catalog.html.php';

    /**
     * Метод должен совпадать с родителем (принимает строку)
     */
    public static function getTemplate(string $content): string 
    {
        return parent::getTemplate($content);
    }

    /**
     * Основной метод для запуска каталога
     * @param array $products Массив товаров
     * @param string $search Поисковый запрос
     */
    public static function render(array $products = [], string $search = ''): string
    {
        // Генерируем HTML контента
        $productsGrid = self::renderProductsGrid($products);
        $searchInfo = self::renderSearchInfo(count($products), $search);

        // Подключаем шаблон
        ob_start();
        include self::TEMPLATE_PATH;
        $content = ob_get_clean();
        
        return self::getTemplate($content);
    }
    
    /**
     * Рендерит сетку карточек товаров
     */
    private static function renderProductsGrid(array $products): string
    {
        if (empty($products)) {
            return self::renderEmptyState();
        }

        $html = '';
        
        foreach ($products as $product) {
            $name = htmlspecialchars($product['name'] ?? 'Без названия');
            $description = htmlspecialchars($product['description'] ?? '');
            $price = number_format($product['price'] ?? 0, 0, '.', ' ');
            $image = htmlspecialchars($product['image'] ?? '/assets/img/no-image.jpg');
            $id = (int)($product['id'] ?? 0);
            
            // Короткое описание (макс. 100 символов)
            $shortDesc = mb_strlen($description) > 100 
                ? mb_substr($description, 0, 100) . '...' 
                : $description;
            
            $html .= '
            <div class="col">
                <div class="card h-100 bg-glass border-0 shadow-sm">
                    <div class="position-relative">
                        <img src="' . $image . '" 
                             class="card-img-top p-3" 
                             alt="' . $name . '"
                             style="height: 220px; object-fit: contain;"
                             onerror="this.src=\'/assets/img/no-image.jpg\';">
                        <span class="badge bg-success position-absolute top-0 end-0 m-3">В наличии</span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-white">' . $name . '</h5>
                        <p class="card-text text-white-50 small flex-grow-1">' . $shortDesc . '</p>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-white border-opacity-10">
                            <span class="h5 mb-0 text-white">' . $price . ' ₽</span>
                            <a href="/product/' . $id . '" class="btn btn-outline-light btn-sm px-3">Подробнее</a>
                        </div>
                    </div>
                </div>
            </div>';
        }
        
        return $html;
    }
    
    /**
     * Сообщение, когда товары не найдены
     */
    private static function renderEmptyState(): string
    {
        return '
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-white-50 mb-3"></i>
                <h4 class="text-white">Ничего не найдено 😔</h4>
                <p class="text-white-50">Попробуйте изменить поисковый запрос</p>
                <a href="/catalog" class="btn btn-outline-light mt-3">Сбросить фильтр</a>
            </div>
        </div>';
    }
    
    /**
     * Инфо о результатах поиска
     */
    private static function renderSearchInfo(int $count, string $search): string
    {
        if (empty($search)) {
            return '<p class="text-white-50 mt-3">Всего товаров: <strong class="text-white">' . $count . '</strong></p>';
        }
        
        $query = htmlspecialchars($search);
        return '<p class="text-white-50 mt-3">Найдено по запросу "' . $query . '": <strong class="text-white">' . $count . '</strong></p>';
    }
}