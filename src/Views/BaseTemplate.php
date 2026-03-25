<?php
namespace App\Views;

class BaseTemplate
{
    /**
     * Путь к файлу базового шаблона
     */
    private const TEMPLATE_PATH = __DIR__ . '/templates/base.html.php';

    public static function getTemplate(string $content): string
    {
        // Буферизация вывода для подключения PHP-шаблона
        ob_start();
        include self::TEMPLATE_PATH;
        return ob_get_clean();
    }
}