<?php
/**
 * Миграция базы данных АвтоМастер
 * Создаёт базу данных MySQL, таблицы и переносит данные из SQLite
 * 
 * Использование: откройте http://localhost/migrate.php в браузере
 */

// Настройки подключения к MySQL
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'automaster';

// Путь к SQLite файлу (оригинальная база)
$sqliteFile = __DIR__ . '/database.sqlite';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Миграция БД - АвтоМастер</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1d3557, #457b9d);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .log {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.8;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #1d3557;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        .btn:hover { background: #457b9d; }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status-box.success { background: #d4edda; border: 2px solid #c3e6cb; color: #155724; }
        .status-box.error { background: #f8d7da; border: 2px solid #f5c6cb; color: #721c24; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1d3557;
        }
        .stat-label {
            color: #6c757d;
            font-size: 13px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Миграция базы данных</h1>
            <p>АвтоМастер - Перенос данных из SQLite в MySQL</p>
        </div>
        <div class="content">
            <div class="log" id="log">
<?php

// Функция логирования
function log_message($message, $type = 'info') {
    $colors = [
        'success' => 'success',
        'error' => 'error',
        'warning' => 'warning',
        'info' => 'info'
    ];
    $color = $colors[$type] ?? 'info';
    $timestamp = date('H:i:s');
    echo "<span class='$color'>[$timestamp] $message</span>\n";
    ob_flush();
    flush();
}

// Отключаем буферизацию для отображения в реальном времени
ob_implicit_flush(true);
ob_end_flush();

try {
    // Шаг 1: Подключение к MySQL
    log_message("Подключение к MySQL...", 'info');
    
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    log_message("✓ Успешное подключение к MySQL", 'success');
    
    // Шаг 2: Создание базы данных
    log_message("Создание базы данных '$dbname'...", 'info');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    log_message("✓ База данных '$dbname' создана/открыта", 'success');
    
    // Шаг 3: Создание таблиц
    log_message("Создание таблиц...", 'info');
    
    // Таблица services
    $pdo->exec("DROP TABLE IF EXISTS services");
    $pdo->exec("CREATE TABLE services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price INT DEFAULT 0,
        image VARCHAR(255) DEFAULT '',
        category VARCHAR(100) DEFAULT 'general',
        active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    log_message("  ✓ Таблица 'services'", 'success');
    
    // Таблица products
    $pdo->exec("DROP TABLE IF EXISTS products");
    $pdo->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) DEFAULT 0,
        image VARCHAR(255) DEFAULT '',
        category VARCHAR(100) DEFAULT 'general',
        in_stock TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    log_message("  ✓ Таблица 'products'", 'success');
    
    // Таблица users
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        email_verified TINYINT DEFAULT 0,
        verification_token VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    log_message("  ✓ Таблица 'users'", 'success');
    
    // Таблица bookings
    $pdo->exec("DROP TABLE IF EXISTS bookings");
    $pdo->exec("CREATE TABLE bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        email VARCHAR(255),
        service_id INT,
        car_model VARCHAR(255),
        message TEXT,
        user_id INT,
        status VARCHAR(50) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    log_message("  ✓ Таблица 'bookings'", 'success');
    
    // Таблица orders
    $pdo->exec("DROP TABLE IF EXISTS orders");
    $pdo->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    log_message("  ✓ Таблица 'orders'", 'success');
    
    // Таблица order_items
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        quantity INT DEFAULT 1,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    log_message("  ✓ Таблица 'order_items'", 'success');
    
    log_message("✓ Все таблицы созданы!", 'success');
    
    // Шаг 4: Перенос данных из SQLite
    log_message("\n📥 Перенос данных из SQLite...", 'info');
    
    if (file_exists($sqliteFile)) {
        $sqlite = new PDO("sqlite:$sqliteFile", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Перенос услуг
        log_message("  → Перенос услуг...", 'info');
        $services = $sqlite->query("SELECT * FROM services")->fetchAll();
        $stmt = $pdo->prepare("INSERT INTO services (id, title, description, price, image, category, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($services as $service) {
            $stmt->execute([
                $service['id'],
                $service['title'],
                $service['description'],
                $service['price'],
                $service['image'] ?? '',
                $service['category'] ?? 'general',
                $service['active'] ?? 1
            ]);
        }
        log_message("    ✓ Перенесено услуг: " . count($services), 'success');
        
        // Перенос товаров
        log_message("  → Перенос товаров...", 'info');
        $products = $sqlite->query("SELECT * FROM products")->fetchAll();
        $stmt = $pdo->prepare("INSERT INTO products (id, name, description, price, image, category, in_stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($products as $product) {
            $stmt->execute([
                $product['id'],
                $product['name'],
                $product['description'],
                $product['price'],
                $product['image'] ?? '',
                $product['category'] ?? 'general',
                $product['in_stock'] ?? 1
            ]);
        }
        log_message("    ✓ Перенесено товаров: " . count($products), 'success');
        
        // Перенос пользователей
        log_message("  → Перенос пользователей...", 'info');
        $users = $sqlite->query("SELECT * FROM users")->fetchAll();
        if (count($users) > 0) {
            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, phone, email_verified, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($users as $user) {
                $stmt->execute([
                    $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['password'],
                    $user['phone'] ?? '',
                    $user['email_verified'] ?? 0,
                    $user['verification_token'] ?? null
                ]);
            }
            log_message("    ✓ Перенесено пользователей: " . count($users), 'success');
        } else {
            log_message("    → Пользователей нет", 'info');
        }
        
        // Перенос бронирований
        log_message("  → Перенос бронирований...", 'info');
        $bookings = $sqlite->query("SELECT * FROM bookings")->fetchAll();
        if (count($bookings) > 0) {
            $stmt = $pdo->prepare("INSERT INTO bookings (id, name, phone, email, service_id, car_model, message, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($bookings as $booking) {
                $stmt->execute([
                    $booking['id'],
                    $booking['name'],
                    $booking['phone'],
                    $booking['email'] ?? null,
                    $booking['service_id'] ?? null,
                    $booking['car_model'] ?? null,
                    $booking['message'] ?? null,
                    $booking['user_id'] ?? null,
                    $booking['status'] ?? 'new'
                ]);
            }
            log_message("    ✓ Перенесено бронирований: " . count($bookings), 'success');
        } else {
            log_message("    → Бронирований нет", 'info');
        }
        
        // Перенос заказов
        log_message("  → Перенос заказов...", 'info');
        $orders = $sqlite->query("SELECT * FROM orders")->fetchAll();
        if (count($orders) > 0) {
            $stmt = $pdo->prepare("INSERT INTO orders (id, user_id, total_amount, status) VALUES (?, ?, ?, ?)");
            foreach ($orders as $order) {
                $stmt->execute([
                    $order['id'],
                    $order['user_id'],
                    $order['total_amount'],
                    $order['status'] ?? 'pending'
                ]);
            }
            log_message("    ✓ Перенесено заказов: " . count($orders), 'success');
            
            // Перенос элементов заказа
            log_message("  → Перенос элементов заказов...", 'info');
            $orderItems = $sqlite->query("SELECT * FROM order_items")->fetchAll();
            $stmt = $pdo->prepare("INSERT INTO order_items (id, order_id, product_id, product_name, product_price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($orderItems as $item) {
                $stmt->execute([
                    $item['id'],
                    $item['order_id'],
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_price'],
                    $item['quantity']
                ]);
            }
            log_message("    ✓ Перенесено элементов заказов: " . count($orderItems), 'success');
        } else {
            log_message("    → Заказов нет", 'info');
        }
        
        $sqlite = null;
        log_message("\n✓ Данные успешно перенесены из SQLite!", 'success');
        
    } else {
        log_message("⚠ SQLite файл не найден. Заполнение тестовыми данными...", 'warning');
        
        // Заполнение услугами
        $services = [
            ['Замена масла', 'Полная замена масла в двигателе и масляного фильтра', 1500, 'service-1.jpg'],
            ['Диагностика двигателя', 'Компьютерная диагностика всех систем двигателя', 2000, 'service-2.jpg'],
            ['Замена тормозных колодок', 'Замена передних и задних тормозных колодок', 2500, 'service-3.jpg'],
            ['Развал-схождение', 'Регулировка углов установки колёс на 3D стенде', 3000, 'service-4.jpg'],
            ['Замена ремня ГРМ', 'Замена ремня ГРМ и роликов натяжителя', 5000, 'service-5.jpg'],
            ['Техническое обслуживание', 'Полное ТО автомобиля по регламенту', 4000, 'service-6.jpg'],
            ['Ремонт подвески', 'Диагностика и ремонт ходовой части', 3500, 'service-7.jpg'],
            ['Замена аккумулятора', 'Установка нового аккумулятора с утилизацией старого', 800, 'service-8.jpg']
        ];
        $stmt = $pdo->prepare("INSERT INTO services (title, description, price, image) VALUES (?, ?, ?, ?)");
        foreach ($services as $service) {
            $stmt->execute($service);
        }
        log_message("  ✓ Добавлено тестовых услуг: 8", 'success');
        
        // Заполнение товарами (используем реальные файлы из assets/img/)
        $products = [
            ['Моторное масло Shell 5W-30', 'Синтетическое моторное масло 5 литров', 2500, '1.jpg', 'Масла'],
            ['Фильтр масляный Mann', 'Масляный фильтр для большинства иномарок', 450, '123.jpg', 'Фильтры'],
            ['Фильтр воздушный Bosch', 'Воздушный фильтр двигателя', 380, '124.jpg', 'Фильтры'],
            ['Фильтр салонный Camel', 'Салонный фильтр с активированным углём', 520, '125.png', 'Фильтры'],
            ['Тормозные колодки передние TRW', 'Керамические тормозные колодки', 2800, '2.jpg', 'Тормоза'],
            ['Тормозные колодки задние TRW', 'Керамические тормозные колодки', 2200, '3.jpg', 'Тормоза'],
            ['Аккумулятор Bosch 60Ач', 'Автомобильный аккумулятор 60 А/ч', 5500, '4.jpg', 'Электрика'],
            ['Свечи зажигания NGK', 'Комплект свечей зажигания (4 шт)', 1200, '5.jpg', 'Электрика'],
            ['Ремень ГРМ ContiTech', 'Ремень ГРМ с роликом в комплекте', 3500, '6.jpg', 'ГРМ'],
            ['Антифриз Coolstream', 'Охлаждающая жидкость 5 литров', 890, 'error.jpg', 'Жидкости'],
            ['Жидкость тормозная DOT4', 'Тормозная жидкость 500мл', 350, '1.jpg', 'Жидкости'],
            ['Щётки стеклоочистителя 500мм', 'Бескаркасные щётки дворников (2 шт)', 1100, '2.jpg', 'Дворники']
        ];
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category) VALUES (?, ?, ?, ?, ?)");
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        log_message("  ✓ Добавлено тестовых товаров: 12", 'success');
    }
    
    // Шаг 5: Итоговая статистика
    log_message("\n" . str_repeat('=', 50), 'info');
    log_message("📊 ИТОГОВАЯ СТАТИСТИКА", 'info');
    log_message(str_repeat('=', 50), 'info');
    
    $stats = [];
    $tables = ['services' => 'Услуги', 'products' => 'Товары', 'users' => 'Пользователи', 'bookings' => 'Бронирования', 'orders' => 'Заказы', 'order_items' => 'Элементы заказов'];
    
    foreach ($tables as $table => $name) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $stats[] = ['name' => $name, 'count' => $count];
        log_message("  $name: $count записей", 'info');
    }
    
    log_message("\n" . str_repeat('=', 50), 'success');
    log_message("✅ МИГРАЦИЯ ЗАВЕРШЕНА УСПЕШНО!", 'success');
    log_message(str_repeat('=', 50), 'success');
    
    echo "</div>"; // Закрываем log div
    
    echo '<div class="status-box success">';
    echo '<h2 style="margin-bottom: 15px;">🎉 Миграция завершена!</h2>';
    
    echo '<div class="stats-grid">';
    foreach ($stats as $stat) {
        echo '<div class="stat-card">';
        echo '<div class="stat-number">' . $stat['count'] . '</div>';
        echo '<div class="stat-label">' . $stat['name'] . '</div>';
        echo '</div>';
    }
    echo '</div>';
    
    echo '<p style="margin-top: 20px;"><strong>База данных:</strong> ' . htmlspecialchars($dbname) . '</p>';
    echo '<p><strong>Хост:</strong> ' . htmlspecialchars($host) . '</p>';
    echo '<p><strong>Пользователь:</strong> ' . htmlspecialchars($username) . '</p>';
    
    echo '<h3 style="margin-top: 25px; margin-bottom: 10px;">📋 Следующие шаги:</h3>';
    echo '<ol style="margin-left: 20px; line-height: 2;">';
    echo '<li>Проверьте сайт: <a href="index.php" target="_blank" style="color: #1d3557;">http://localhost/</a></li>';
    echo '<li>Откройте phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank" style="color: #1d3557;">http://localhost/phpmyadmin</a></li>';
    echo '<li>При необходимости настройте SMTP для email-уведомлений в config/database.php</li>';
    echo '</ol>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo "</div>"; // Закрываем log div
    
    echo '<div class="status-box error">';
    echo '<h2 style="margin-bottom: 15px;">⚠ Ошибка миграции!</h2>';
    echo '<p><strong>Ошибка:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<h3 style="margin-top: 20px; margin-bottom: 10px;">Возможные причины:</h3>';
    echo '<ul style="margin-left: 20px; line-height: 2;">';
    echo '<li>MySQL не запущен в XAMPP</li>';
    echo '<li>Неверный пароль пользователя root</li>';
    echo '<li>Порт 3306 занят другой программой</li>';
    echo '</ul>';
    echo '<h3 style="margin-top: 20px; margin-bottom: 10px;">Решение:</h3>';
    echo '<ol style="margin-left: 20px; line-height: 2;">';
    echo '<li>Откройте XAMPP Control Panel</li>';
    echo '<li>Нажмите Stop, затем Start для MySQL</li>';
    echo '<li>Обновите эту страницу (F5)</li>';
    echo '</ol>';
    echo '</div>';
    
} catch (Exception $e) {
    echo "</div>"; // Закрываем log div
    
    echo '<div class="status-box error">';
    echo '<h2 style="margin-bottom: 15px;">❌ Неизвестная ошибка!</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

?>
            
            <div style="text-align: center; margin-top: 25px;">
                <a href="index.php" class="btn">🏠 На главную</a>
                <a href="migrate.php" class="btn" style="background: #28a745;">🔄 Повторить миграцию</a>
                <a href="http://localhost/phpmyadmin" target="_blank" class="btn" style="background: #457b9d;">📊 Открыть phpMyAdmin</a>
            </div>
        </div>
    </div>
    
    <script>
        // Автопрокрутка лога вниз
        const log = document.getElementById('log');
        log.scrollTop = log.scrollHeight;
    </script>
</body>
</html>
