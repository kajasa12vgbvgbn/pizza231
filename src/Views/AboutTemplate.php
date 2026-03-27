<?php
namespace App\Views;

use App\Views\BaseTemplate;

class AboutTemplate extends BaseTemplate
{
    /**
     * Путь к файлу шаблона
     */
    private const TEMPLATE_PATH = __DIR__ . '/templates/about.html.php';

    /**
     * Путь к файлу с текстами
     */
    private const TEXTS_PATH = __DIR__ . '/../../storage/templates/about.json';

    /**
     * Загружает тексты из JSON файла
     */
    private static function loadTexts(): array
    {
        $path = self::TEXTS_PATH;
        if (!file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        return json_decode($json, true) ?? [];
    }

    public static function getTemplate(string $content = '', array $texts = []): string
    {
        // Загружаем тексты (если не переданы)
        if (empty($texts)) {
            $texts = self::loadTexts();
        }

        // Значения по умолчанию, если файл не загружен
        $address = $texts['contacts']['addressValue'] ?? '650070, г. Кемерово, ул. Тухачевского, 32';
        $phone = $texts['contacts']['phoneValue'] ?? '+7 (3842) 21-56-61';
        $email = $texts['contacts']['emailValue'] ?? 'info@coopteh.ru';
        $director = $texts['contacts']['directorName'] ?? 'Теребова Наталья Владимировна';
        $mapId = 'YOUR_CONSTRUCTOR_ID';

        // Подключаем шаблон
        ob_start();
        include self::TEMPLATE_PATH;
        $ourContent = ob_get_clean();
        
        return parent::getTemplate($ourContent, $texts);
    }
}