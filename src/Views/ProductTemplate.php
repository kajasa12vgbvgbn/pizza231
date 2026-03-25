<?php
namespace App\Views;

class ProductTemplate extends BaseTemplate
{
    /**
     * Путь к файлу HTML-шаблона
     */
    private const TEMPLATE_PATH = __DIR__ . '/templates/product.html.php';

    public static function getCardTemplate($data): string
    {
        // Подготовка переменных для шаблона
        if (!$data) {
            $notFound = true;
            $id = 0;
            $title = '';
            $description = '';
            $price = 0;
            $priceFormatted = '';
            $image = '';
            $fallbackImage = '/assets/img/error.jpg';
            $productJson = '{}';
        } else {
            $notFound = false;
            $id = (int)($data['id'] ?? 0);
            $title = htmlspecialchars($data['name'] ?? 'Без названия');
            $description = htmlspecialchars($data['description'] ?? 'Описание отсутствует.');
            $price = (float)($data['price'] ?? 0);
            $priceFormatted = number_format($price, 0, '.', ' ');
            
            $fallbackImage = '/assets/img/error.jpg';
            $image = !empty($data['image']) ? htmlspecialchars($data['image']) : $fallbackImage;

            // Данные для JS (безопасно через json_encode)
            $productJson = htmlspecialchars(json_encode([
                'id' => $id,
                'name' => $data['name'] ?? '',
                'price' => $price,
                'image' => $data['image'] ?? ''
            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        }

        // Буферизация вывода для подключения PHP-шаблона
        ob_start();
        include self::TEMPLATE_PATH;
        $content = ob_get_clean();

        return parent::getTemplate($content);
    }
}