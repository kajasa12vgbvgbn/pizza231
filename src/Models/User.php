<?php
namespace App\Models;

require_once __DIR__ . '/../Config/Config.php';

use App\Config\Config;

class User
{
    private const FILE_USERS = ".\storage\users.json";
    
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
}
