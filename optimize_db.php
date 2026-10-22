<?php
/**
 * Оптимизация базы данных
 * Добавляет индексы для ускорения запросов
 * 
 * Использование: http://localhost/optimize_db.php
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
    <title>Оптимизация БД - АвтоМастер</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #1d3557; margin-bottom: 20px; }
        .log { background: #f8f9fa; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 13px; line-height: 1.8; max-height: 400px; overflow-y: auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .btn { display: inline-block; padding: 12px 24px; background: #1d3557; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; margin-right: 10px; }
        .btn:hover { background: #457b9d; }
        .stat-box { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-num { font-size: 28px; font-weight: bold; color: #1d3557; }
        .stat-label { color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Оптимизация базы данных</h1>
        <div class="log" id="log">
<?php

try {
    Config::initDatabase();
    $pdo = Database::getConnection();
    
    echo "<span class='success'>✓ Подключение к БД установлено</span>\n\n";
    
    // Добавление индексов
    echo "<span class='info'>📊 Добавление индексов...</span>\n\n";
    
    // Индексы для products
    echo "<span class='info'>→ Таблица products:</span>\n";
    try {
        $pdo->exec("ALTER TABLE products ADD INDEX idx_category (category)");
        echo "  <span class='success'>✓ Индекс category</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка: " . $e->getMessage() . "</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE products ADD INDEX idx_price (price)");
        echo "  <span class='success'>✓ Индекс price</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE products ADD INDEX idx_name (name(50))");
        echo "  <span class='success'>✓ Индекс name</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    // Индексы для services
    echo "\n<span class='info'>→ Таблица services:</span>\n";
    try {
        $pdo->exec("ALTER TABLE services ADD INDEX idx_category (category)");
        echo "  <span class='success'>✓ Индекс category</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE services ADD INDEX idx_active (active)");
        echo "  <span class='success'>✓ Индекс active</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    // Индексы для users
    echo "\n<span class='info'>→ Таблица users:</span>\n";
    try {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_email (email)");
        echo "  <span class='success'>✓ Индекс email</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    // Индексы для orders
    echo "\n<span class='info'>→ Таблица orders:</span>\n";
    try {
        $pdo->exec("ALTER TABLE orders ADD INDEX idx_user_id (user_id)");
        echo "  <span class='success'>✓ Индекс user_id</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE orders ADD INDEX idx_status (status)");
        echo "  <span class='success'>✓ Индекс status</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE orders ADD INDEX idx_created_at (created_at)");
        echo "  <span class='success'>✓ Индекс created_at</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    // Индексы для order_items
    echo "\n<span class='info'>→ Таблица order_items:</span>\n";
    try {
        $pdo->exec("ALTER TABLE order_items ADD INDEX idx_order_id (order_id)");
        echo "  <span class='success'>✓ Индекс order_id</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE order_items ADD INDEX idx_product_id (product_id)");
        echo "  <span class='success'>✓ Индекс product_id</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    // Индексы для bookings
    echo "\n<span class='info'>→ Таблица bookings:</span>\n";
    try {
        $pdo->exec("ALTER TABLE bookings ADD INDEX idx_user_id (user_id)");
        echo "  <span class='success'>✓ Индекс user_id</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE bookings ADD INDEX idx_status (status)");
        echo "  <span class='success'>✓ Индекс status</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  <span class='error'>✗ Ошибка</span>\n";
        } else {
            echo "  <span class='info'>→ Индекс уже существует</span>\n";
        }
    }
    
    // Оптимизация таблиц
    echo "\n<span class='info'>🔧 Оптимизация таблиц...</span>\n";
    $tables = ['products', 'services', 'users', 'orders', 'order_items', 'bookings'];
    foreach ($tables as $table) {
        try {
            $pdo->exec("OPTIMIZE TABLE $table");
            echo "  <span class='success'>✓ Таблица $table оптимизирована</span>\n";
        } catch (PDOException $e) {
            echo "  <span class='warning'>⚠ Таблица $table: " . $e->getMessage() . "</span>\n";
        }
    }
    
    // Статистика
    echo "\n<span class='info'>📊 Статистика:</span>\n";
    $stats = [
        'products' => 'Товары',
        'services' => 'Услуги',
        'users' => 'Пользователи',
        'orders' => 'Заказы',
        'bookings' => 'Бронирования'
    ];
    
    echo "<div class='stat-box'>";
    foreach ($stats as $table => $name) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<div class='stat'>";
        echo "<div class='stat-num'>$count</div>";
        echo "<div class='stat-label'>$name</div>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "\n<span class='success'>\n===========================================\n";
    echo "✅ ОПТИМИЗАЦИЯ ЗАВЕРШЕНА УСПЕШНО!\n";
    echo "===========================================\n</span>";
    echo "\n<span class='info'>Сайт должен работать быстрее!</span>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</span>";
}

?>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn">🏠 На главную</a>
            <a href="optimize_db.php" class="btn" style="background: #28a745;">🔄 Повторить</a>
            <a href="http://localhost/phpmyadmin" target="_blank" class="btn" style="background: #457b9d;">📊 phpMyAdmin</a>
        </div>
    </div>
    
    <script>
        const log = document.getElementById('log');
        log.scrollTop = log.scrollHeight;
    </script>
</body>
</html>
