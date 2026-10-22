<?php
/**
 * Исправление путей к изображениям в базе данных
 */

require_once __DIR__ . '/src/Config/Config.php';
require_once __DIR__ . '/src/Database/Database.php';

use App\Config\Config;
use App\Database\Database;

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Исправление изображений</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #1d3557; }
        .log { background: #f8f9fa; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 13px; line-height: 1.8; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .btn { display: inline-block; padding: 12px 24px; background: #1d3557; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; margin-right: 10px; }
        img { max-width: 100px; border: 1px solid #ddd; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Исправление изображений</h1>
        <div class="log">
<?php

try {
    Config::initDatabase();
    $pdo = Database::getConnection();
    
    echo "<span class='success'>✓ Подключение к БД</span>\n\n";
    
    // Проверяем какие файлы есть
    $imgDir = __DIR__ . '/assets/img/';
    $images = [];
    if (is_dir($imgDir)) {
        $files = scandir($imgDir);
        foreach ($files as $file) {
            if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
                $images[] = $file;
            }
        }
    }
    
    echo "<span class='info'>📁 Найдено файлов в assets/img/: " . count($images) . "</span>\n";
    foreach ($images as $img) {
        echo "  - $img\n";
    }
    
    // Обновляем products
    echo "\n<span class='info'>🔧 Обновление товаров...</span>\n";
    
    $products = $pdo->query("SELECT id, name, image FROM products")->fetchAll();
    $updated = 0;
    
    foreach ($products as $product) {
        $currentImage = $product['image'];
        
        // Если изображение не начинается с /assets/img/, добавляем префикс
        if (!str_starts_with($currentImage, '/assets/img/') && !empty($currentImage)) {
            $newImage = '/assets/img/' . $currentImage;
            $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
            $stmt->execute([$newImage, $product['id']]);
            echo "  <span class='success'>✓ ID {$product['id']}: $currentImage → $newImage</span>\n";
            $updated++;
        } else if (empty($currentImage)) {
            $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
            $stmt->execute(['/assets/img/error.jpg', $product['id']]);
            echo "  <span class='info'>→ ID {$product['id']}: установлено error.jpg</span>\n";
            $updated++;
        }
    }
    
    echo "\n<span class='success'>✅ Обновлено товаров: $updated</span>\n";
    
    // Обновляем services
    echo "\n<span class='info'>🔧 Обновление услуг...</span>\n";
    
    $services = $pdo->query("SELECT id, title, image FROM services")->fetchAll();
    $updatedServices = 0;
    
    foreach ($services as $service) {
        $currentImage = $service['image'];
        
        if (!str_starts_with($currentImage, '/assets/img/') && !empty($currentImage)) {
            $newImage = '/assets/img/' . $currentImage;
            $stmt = $pdo->prepare("UPDATE services SET image = ? WHERE id = ?");
            $stmt->execute([$newImage, $service['id']]);
            echo "  <span class='success'>✓ ID {$service['id']}: $currentImage → $newImage</span>\n";
            $updatedServices++;
        }
    }
    
    echo "\n<span class='success'>✅ Обновлено услуг: $updatedServices</span>\n";
    
    // Показываем результат
    echo "\n<span class='info'>📊 Результат:</span>\n";
    $products = $pdo->query("SELECT id, name, image FROM products LIMIT 6")->fetchAll();
    echo "<div style='margin-top: 15px;'>";
    foreach ($products as $p) {
        echo "<div style='display: inline-block; margin: 10px; text-align: center;'>";
        echo "<img src='" . htmlspecialchars($p['image']) . "' alt='Product' onerror='this.src=\"/assets/img/error.jpg\"'>";
        echo "<br><small>" . htmlspecialchars($p['name']) . "</small>";
        echo "<br><small style='color: #6c757d;'>" . htmlspecialchars($p['image']) . "</small>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "\n\n<span class='success'>===========================================\n";
    echo "✅ ИСПРАВЛЕНИЕ ЗАВЕРШЕНО!\n";
    echo "===========================================\n</span>";
    echo "\n<span class='info'>Проверьте каталог: <a href='/catalog'>/catalog</a></span>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</span>";
}

?>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn">🏠 На главную</a>
            <a href="catalog" class="btn" style="background: #28a745;">📦 Каталог</a>
            <a href="fix_images.php" class="btn" style="background: #457b9d;">🔄 Повторить</a>
        </div>
    </div>
</body>
</html>
