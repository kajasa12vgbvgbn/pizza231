<?php
namespace App\Controllers;

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Views/AdminTemplate.php';

use App\Models\User;
use App\Models\Product;
use App\Views\AdminTemplate;

class AdminController
{
    private User $userModel;
    private Product $productModel;
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->productModel = new Product();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Проверить, является ли текущий пользователь админом
     */
    private function checkAdmin(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Сначала проверяем сессию
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            return true;
        }
        
        // Если нет в сессии - проверяем через модель
        return $this->userModel->isAdmin($_SESSION['user_id']);
    }
    
    /**
     * Главная страница админки
     */
    public function index(): void
    {
        if (!$this->checkAdmin()) {
            header('Location: /login');
            exit;
        }
        
        $stats = $this->getStats();
        echo AdminTemplate::renderDashboard($stats);
    }
    
    /**
     * Страница заказов
     */
    public function orders(): void
    {
        if (!$this->checkAdmin()) {
            header('Location: /login');
            exit;
        }
        
        $orders = $this->getOrders();
        echo AdminTemplate::renderOrders($orders);
    }
    
    /**
     * Страница пользователей
     */
    public function users(): void
    {
        if (!$this->checkAdmin()) {
            header('Location: /login');
            exit;
        }
        
        $users = $this->userModel->loadData();
        echo AdminTemplate::renderUsers($users);
    }
    
    /**
     * Получить статистику
     */
    private function getStats(): array
    {
        $users = $this->userModel->loadData();
        $products = $this->productModel->loadData() ?? [];
        $orders = $this->getOrders();
        
        $totalRevenue = 0;
        foreach ($orders as $order) {
            $totalRevenue += $order['total'] ?? 0;
        }
        
        return [
            'total_users' => count($users),
            'total_products' => count($products),
            'total_orders' => count($orders),
            'total_revenue' => $totalRevenue
        ];
    }
    
    /**
     * Получить все заказы
     */
    private function getOrders(): array
    {
        $file = '.\storage\orders.json';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $data = file_get_contents($file);
        $orders = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($orders)) {
            return [];
        }
        
        // Сортировка по дате (новые первые)
        usort($orders, function($a, $b) {
            return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
        });
        
        return $orders;
    }
    
    /**
     * API: получить статистику
     */
    public function apiStats(): string
    {
        header('Content-Type: application/json');
        
        if (!$this->checkAdmin()) {
            http_response_code(403);
            return json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
        }
        
        return json_encode($this->getStats(), JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * API: получить заказы
     */
    public function apiOrders(): string
    {
        header('Content-Type: application/json');
        
        if (!$this->checkAdmin()) {
            http_response_code(403);
            return json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
        }
        
        return json_encode($this->getOrders(), JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * API: получить пользователей
     */
    public function apiUsers(): string
    {
        header('Content-Type: application/json');
        
        if (!$this->checkAdmin()) {
            http_response_code(403);
            return json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
        }
        
        $users = $this->userModel->loadData();
        
        // Удаляем пароли из данных
        foreach ($users as &$user) {
            unset($user['password']);
        }
        
        return json_encode($users, JSON_UNESCAPED_UNICODE);
    }
}
