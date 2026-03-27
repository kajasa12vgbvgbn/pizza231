<?php
namespace App\Models;

require_once __DIR__ . '/../Config/Config.php';

use App\Config\Config;

class User
{
    private const FILE_USERS = __DIR__ . '/../../storage/users.json';
    private const FILE_ADMINS = __DIR__ . '/../../storage/admins.json';
    
    /**
     * Загрузить всех пользователей
     */
    public function loadData(): array
    {
        if (!file_exists(self::FILE_USERS)) {
            return [];
        }

        $data = file_get_contents(self::FILE_USERS);
        $users = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($users)) {
            return [];
        }

        return $users;
    }
    
    /**
     * Загрузить всех администраторов
     */
    public function loadAdmins(): array
    {
        if (!file_exists(self::FILE_ADMINS)) {
            return [];
        }
        
        $data = file_get_contents(self::FILE_ADMINS);
        $admins = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($admins)) {
            return [];
        }

        return $admins;
    }
    
    /**
     * Сохранить всех пользователей
     */
    private function saveData(array $users): bool
    {
        $dir = dirname(self::FILE_USERS);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return file_put_contents(self::FILE_USERS, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
    
    /**
     * Найти пользователя по email
     */
    public function findByEmail(string $email): ?array
    {
        // Сначала проверяем среди админов
        $admins = $this->loadAdmins();
        foreach ($admins as $key => $admin) {
            if (strtolower($admin['email']) === strtolower($email)) {
                $admin['is_admin'] = true;
                return $admin;
            }
        }
        
        // Потом среди обычных пользователей
        $users = $this->loadData();
        
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        
        return null;
    }
    
    /**
     * Найти пользователя по ID
     */
    public function findById(int $id): ?array
    {
        $users = $this->loadData();
        
        foreach ($users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        
        return null;
    }
    
    /**
     * Проверить, является ли пользователь админом
     */
    public function isAdmin(int $userId): bool
    {
        // Если id == 0, это админ из admins.json
        if ($userId === 0) {
            return true;
        }
        
        // Проверяем по email пользователя
        $user = $this->findById($userId);
        if (!$user) return false;
        
        $admins = $this->loadAdmins();
        foreach ($admins as $admin) {
            if (strtolower($admin['email']) === strtolower($user['email'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Создать нового пользователя
     */
    public function create(string $email, string $password, string $name): ?array
    {
        // Проверка, что email не занят
        if ($this->findByEmail($email) !== null) {
            return null;
        }
        
        $users = $this->loadData();
        
        // Генерация ID
        $maxId = 0;
        foreach ($users as $user) {
            if ($user['id'] > $maxId) {
                $maxId = $user['id'];
            }
        }
        
        $newUser = [
            'id' => $maxId + 1,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $users[] = $newUser;
        
        if ($this->saveData($users)) {
            // Возвращаем пользователя без пароля
            unset($newUser['password']);
            return $newUser;
        }
        
        return null;
    }
    
    /**
     * Проверить пароль
     */
    public function verifyPassword(string $email, string $password): ?array
    {
        // Проверяем сначала среди админов
        $admins = $this->loadAdmins();
        foreach ($admins as $key => $admin) {
            if (strtolower($admin['email']) === strtolower($email)) {
                if ($password === $admin['password']) {
                    return [
                        'id' => 0,
                        'email' => $admin['email'],
                        'name' => $admin['name'],
                        'is_admin' => true
                    ];
                }
                return null;
            }
        }
        
        // Потом среди обычных пользователей
        $user = $this->findByEmail($email);
        
        if ($user === null) {
            return null;
        }
        
        if (password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        
        return null;
    }

    /**
     * Обновить профиль пользователя
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $users = $this->loadData();
        
        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                // Обновляем только разрешённые поля
                if (isset($data['name'])) {
                    $user['name'] = trim($data['name']);
                }
                if (isset($data['phone'])) {
                    $user['phone'] = trim($data['phone']);
                }
                if (isset($data['address'])) {
                    $user['address'] = trim($data['address']);
                }
                if (isset($data['avatar'])) {
                    $user['avatar'] = $data['avatar'];
                }
                
                $user['updated_at'] = date('Y-m-d H:i:s');
                
                return $this->saveData($users);
            }
        }
        
        return false;
    }

    /**
     * Получить полный профиль пользователя с доп. полями
     */
    public function getFullProfile(int $userId): ?array
    {
        $user = $this->findById($userId);
        
        if ($user) {
            // Добавляем значения по умолчанию для отсутствующих полей
            return array_merge([
                'phone' => '',
                'address' => '',
                'avatar' => ''
            ], $user);
        }
        
        return null;
    }
    
    /**
     * Получить историю заказов пользователя
     */
    public function getOrders(int $userId): array
    {
        $user = $this->findById($userId);
        if (!$user) {
            return [];
        }
        
        $ordersFile = __DIR__ . '/../../storage/orders.json';
        if (!file_exists($ordersFile)) {
            return [];
        }
        
        $content = file_get_contents($ordersFile);
        $allOrders = json_decode($content, true) ?: [];
        
        $userOrders = [];
        foreach ($allOrders as $order) {
            // Проверяем по user_id (для новых заказов)
            if (isset($order['user_id']) && $order['user_id'] === $userId) {
                $userOrders[] = $order;
                continue;
            }
            
            // Для старых заказов без user_id проверяем по email
            if (!isset($order['user_id']) && isset($order['email']) && 
                strtolower($order['email']) === strtolower($user['email'])) {
                $userOrders[] = $order;
            }
        }
        
        // Сортировка по дате (новые первые)
        usort($userOrders, function($a, $b) {
            $dateA = $a['created_at'] ?? '';
            $dateB = $b['created_at'] ?? '';
            return strtotime($dateB) - strtotime($dateA);
        });
        
        return $userOrders;
    }
}
