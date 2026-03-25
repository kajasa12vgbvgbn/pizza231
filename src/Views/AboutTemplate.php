<?php
namespace App\Views;

use App\Views\BaseTemplate;

class AboutTemplate extends BaseTemplate
{
    /**
     * Путь к файлу шаблона
     */
    private const TEMPLATE_PATH = __DIR__ . '/templates/about.html.php';

    public static function getTemplate(string $content = ''): string
    {
        // Подготовка переменных для шаблона
        $address = '650070, г. Кемерово, ул. Тухачевского, 32';
        $phone = '+7 (3842) 21-56-61';
        $email = 'info@coopteh.ru';
        $director = 'Теребова Наталья Владимировна';
        $mapId = 'YOUR_CONSTRUCTOR_ID';

        // Подключаем шаблон
        ob_start();
        include self::TEMPLATE_PATH;
        $ourContent = ob_get_clean();
        
        return parent::getTemplate($ourContent);
    }
}