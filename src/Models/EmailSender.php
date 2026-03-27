<?php
namespace App\Models;

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Logger;

/**
 * Класс для отправки email-уведомлений
 */
class EmailSender
{
    /**
     * Конфигурация SMTP
     */
    private static function getConfig(): array
    {
        $configFile = __DIR__ . '/../../config/email.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            if (is_array($config)) {
                return $config;
            }
        }
        
        // Возвращаем пустой массив, если файл не найден
        Logger::error('Email config file not found or invalid', ['file' => $configFile]);
        return [];
    }
    
    /**
     * Создать экземпляр PHPMailer с настройками SMTP
     */
    private static function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $config = self::getConfig();
        
        if (empty($config)) {
            throw new \Exception('Email configuration not found');
        }
        
        $smtpConfig = $config['smtp'] ?? [];
        
        // Настройки SMTP
        $mail->isSMTP();
        $mail->Host = $smtpConfig['host'] ?? 'smtp.gmail.com';
        $mail->Port = $smtpConfig['port'] ?? 587;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpConfig['username'] ?? '';
        $mail->Password = $smtpConfig['password'] ?? '';
        
        // Шифрование
        $encryption = $smtpConfig['encryption'] ?? 'tls';
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        // Настройки отправителя
        $mail->setFrom(
            $smtpConfig['from_email'] ?? $smtpConfig['username'] ?? '',
            $smtpConfig['from_name'] ?? 'Магазин автозапчастей'
        );
        
        // Дополнительные настройки
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        
        return $mail;
    }
    
    /**
     * Отправить уведомление о новом заказе
     */
    public static function sendOrderNotification(array $order, ?string $customerEmail = null): bool
    {
        try {
            $config = self::getConfig();
            $notificationsConfig = $config['notifications'] ?? [];
            
            $mail = self::createMailer();
            
            // Тема письма
            $mail->Subject = 'Новый заказ #' . substr($order['id'], -8);
            
            // HTML-тело письма
            $mail->Body = self::generateOrderEmailHtml($order);
            
            // Альтернативное текстовое тело
            $mail->AltBody = self::generateOrderEmailText($order);
            
            $sent = false;
            
            // Отправка клиенту
            if ($customerEmail && ($notificationsConfig['send_to_customer'] ?? true)) {
                $mail->addAddress($customerEmail);
                $mail->send();
                Logger::info('Order notification sent to customer', [
                    'order_id' => $order['id'],
                    'email' => $customerEmail
                ]);
                $sent = true;
                
                // Очищаем получателей для следующего письма
                $mail->clearAddresses();
            }
            
            // Отправка администратору
            if ($notificationsConfig['send_to_admin'] ?? true) {
                $adminEmail = $notificationsConfig['admin_email'] ?? '';
                if ($adminEmail) {
                    $mail->addAddress($adminEmail);
                    $mail->send();
                    Logger::info('Order notification sent to admin', [
                        'order_id' => $order['id'],
                        'email' => $adminEmail
                    ]);
                    $sent = true;
                }
            }
            
            return $sent;
            
        } catch (Exception $e) {
            Logger::error('Failed to send order notification email: ' . $e->getMessage(), [
                'order_id' => $order['id'] ?? 'unknown',
                'customer_email' => $customerEmail
            ]);
            return false;
        }
    }
    
    /**
     * Сгенерировать HTML-версию письма о заказе
     */
    private static function generateOrderEmailHtml(array $order): string
    {
        $orderId = $order['id'] ?? '';
        $shortId = substr($orderId, -8);
        $date = $order['created_at'] ?? date('Y-m-d H:i:s');
        $dateFormatted = date('d.m.Y H:i', strtotime($date));
        $total = number_format($order['total'] ?? 0, 0, '.', ' ');
        $status = $order['status'] ?? 'new';
        $statusText = self::getStatusText($status);
        
        $itemsHtml = '';
        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                $itemTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                $itemsHtml .= '
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($item['name'] ?? '') . '</td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">' . number_format($item['price'] ?? 0, 0, '.', ' ') . ' ₽</td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">' . ($item['quantity'] ?? 1) . '</td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">' . number_format($itemTotal, 0, '.', ' ') . ' ₽</td>
                </tr>';
            }
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Заказ #' . $shortId . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .order-info { margin-bottom: 20px; }
                .order-details table { width: 100%; border-collapse: collapse; }
                .order-details th { background-color: #f2f2f2; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; }
                .order-details td { padding: 8px; border-bottom: 1px solid #ddd; }
                .total { font-size: 18px; font-weight: bold; color: #28a745; text-align: right; margin-top: 20px; }
                .status { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
                .status-new { background-color: #007bff; color: white; }
                .status-processing { background-color: #ffc107; color: #212529; }
                .status-completed { background-color: #28a745; color: white; }
                .status-cancelled { background-color: #dc3545; color: white; }
                .customer-info { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Заказ #' . $shortId . ' оформлен</h1>
                </div>
                <div class="content">
                    <div class="order-info">
                        <p><strong>Дата заказа:</strong> ' . $dateFormatted . '</p>
                        <p><strong>Статус:</strong> <span class="status status-' . $status . '">' . $statusText . '</span></p>
                        <p><strong>Способ оплаты:</strong> ' . (($order['payment'] ?? '') === 'cash' ? 'Наличными' : 'Картой') . '</p>
                    </div>
                    
                    <div class="customer-info">
                        <h3>Информация о доставке</h3>
                        <p><strong>ФИО:</strong> ' . htmlspecialchars($order['fio'] ?? '') . '</p>
                        <p><strong>Телефон:</strong> ' . htmlspecialchars($order['phone'] ?? '') . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($order['email'] ?? '') . '</p>
                        <p><strong>Адрес доставки:</strong> ' . htmlspecialchars($order['address'] ?? '') . '</p>
                    </div>
                    
                    <div class="order-details">
                        <h3>Состав заказа</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th style="text-align: right;">Цена</th>
                                    <th style="text-align: center;">Количество</th>
                                    <th style="text-align: right;">Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . $itemsHtml . '
                            </tbody>
                        </table>
                        <div class="total">
                            Итого: ' . $total . ' ₽
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 14px; color: #666;">
                        <p>Спасибо за ваш заказ! Мы свяжемся с вами в ближайшее время для подтверждения.</p>
                        <p>Вы можете отслеживать статус заказа в своем профиле на сайте.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Сгенерировать текстовую версию письма о заказе
     */
    private static function generateOrderEmailText(array $order): string
    {
        $orderId = $order['id'] ?? '';
        $shortId = substr($orderId, -8);
        $date = $order['created_at'] ?? date('Y-m-d H:i:s');
        $dateFormatted = date('d.m.Y H:i', strtotime($date));
        $total = number_format($order['total'] ?? 0, 0, '.', ' ');
        $statusText = self::getStatusText($order['status'] ?? 'new');
        
        $text = "ЗАКАЗ #{$shortId}\n";
        $text .= "Дата: {$dateFormatted}\n";
        $text .= "Статус: {$statusText}\n";
        $text .= "Способ оплаты: " . (($order['payment'] ?? '') === 'cash' ? 'Наличными' : 'Картой') . "\n\n";
        
        $text .= "ИНФОРМАЦИЯ О ДОСТАВКЕ:\n";
        $text .= "ФИО: " . ($order['fio'] ?? '') . "\n";
        $text .= "Телефон: " . ($order['phone'] ?? '') . "\n";
        $text .= "Email: " . ($order['email'] ?? '') . "\n";
        $text .= "Адрес: " . ($order['address'] ?? '') . "\n\n";
        
        $text .= "СОСТАВ ЗАКАЗА:\n";
        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                $itemTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                $text .= sprintf(
                    "- %s: %d ₽ × %d = %d ₽\n",
                    $item['name'] ?? '',
                    $item['price'] ?? 0,
                    $item['quantity'] ?? 1,
                    $itemTotal
                );
            }
        }
        
        $text .= "\nИТОГО: {$total} ₽\n\n";
        $text .= "Спасибо за ваш заказ! Мы свяжемся с вами в ближайшее время для подтверждения.\n";
        $text .= "Вы можете отслеживать статус заказа в своем профиле на сайте.\n";
        
        return $text;
    }
    
    /**
     * Получить текстовое описание статуса
     */
    private static function getStatusText(string $status): string
    {
        $statuses = [
            'new' => 'Новый',
            'processing' => 'В обработке',
            'completed' => 'Выполнен',
            'cancelled' => 'Отменён'
        ];
        
        return $statuses[$status] ?? $status;
    }
    
    /**
     * Проверить конфигурацию email (для админки)
     */
    public static function testConnection(): array
    {
        try {
            $mail = self::createMailer();
            
            // Простая проверка - попытка подключиться к SMTP серверу
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                Logger::info('SMTP Debug: ' . $str);
            };
            
            $mail->smtpConnect();
            $mail->smtpClose();
            
            return [
                'success' => true,
                'message' => 'SMTP соединение успешно установлено'
            ];
            
        } catch (Exception $e) {
            Logger::error('SMTP connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>