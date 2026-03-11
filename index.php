<?php
require 'vendor/autoload.php'; // Если используете Composer, иначе подключите вручную
use App\BaseTemplate;

$template = new BaseTemplate();
$template->render("<h1>Главная страница в стиле MVC</h1>");