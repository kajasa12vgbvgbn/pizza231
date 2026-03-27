<?php
namespace App\Controllers;

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Views/AuthTemplate.php';

use App\Models\User;
use App\Views\AuthTemplate;

class AuthController
{
    private User $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
        
        // Запуск сессии
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Страница регистрации
     */
    public function register(): void
    {
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $name = trim($_POST['name'] ?? '');
            
            // Валидация
            if (empty($email) || empty($password) || empty($name)) {
                $error = 'Заполните все поля';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Введите корректный email';
            } elseif (strlen($password) < 6) {
                $error = 'Пароль должен быть не менее 6 символов';
            } else {
                $result = $this->userModel->create($email, $password, $name);
                
                if ($result) {
                    $success = 'Регистрация успешна! Теперь вы можете войти.';
                } else {
                    $error = 'Пользователь с таким email уже существует';
                }
            }
        }
        
        echo AuthTemplate::renderRegister($error, $success);
    }
    
    /**
     * Страница входа
     */
    public function login(): void
    {
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Заполните все поля';
            } else {
                $user = $this->userModel->verifyPassword($email, $password);
                
                if ($user) {
                    // Установка сессии
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'] ?? false;
                    
                    // Записать сессию
                    session_write_close();
                    
                    // Перенаправление
                    header('Location: /');
                    exit;
                } else {
                    $error = 'Неверный email или пароль';
                }
            }
        }
        
        echo AuthTemplate::renderLogin($error);
    }
    
    /**
     * Выход
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_destroy();
        header('Location: /');
        exit;
    }
    
    /**
     * Получить текущего пользователя
     */
    public static function getCurrentUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            $isAdmin = $_SESSION['is_admin'] ?? false;
            
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'is_admin' => $isAdmin
            ];
        }
        
        return null;
    }
    
    /**
     * API: регистрация
     */
    public function apiRegister(): string
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $name = trim($input['name'] ?? '');
        
        if (empty($email) || empty($password) || empty($name)) {
            http_response_code(400);
            return json_encode(['error' => 'Заполните все поля'], JSON_UNESCAPED_UNICODE);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return json_encode(['error' => 'Введите корректный email'], JSON_UNESCAPED_UNICODE);
        }
        
        if (strlen($password) < 6) {
            http_response_code(400);
            return json_encode(['error' => 'Пароль должен быть не менее 6 символов'], JSON_UNESCAPED_UNICODE);
        }
        
        $result = $this->userModel->create($email, $password, $name);
        
        if ($result) {
            return json_encode(['success' => true, 'message' => 'Регистрация успешна'], JSON_UNESCAPED_UNICODE);
        }
        
        http_response_code(400);
        return json_encode(['error' => 'Пользователь с таким email уже существует'], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * API: вход
     */
    public function apiLogin(): string
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            http_response_code(400);
            return json_encode(['error' => 'Заполните все поля'], JSON_UNESCAPED_UNICODE);
        }
        
        $user = $this->userModel->verifyPassword($email, $password);
        
        if ($user) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? false;
            
            session_write_close();
            
            return json_encode([
                'success' => true, 
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'is_admin' => $user['is_admin'] ?? false
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        
        http_response_code(401);
        return json_encode(['error' => 'Неверный email или пароль'], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * API: выход
     */
    public function apiLogout(): string
    {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_destroy();
        
        return json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * API: получить текущего пользователя
     */
    public function apiGetCurrent(): string
    {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $user = self::getCurrentUser();
        
        // Если есть user, но нет is_admin - проверить через модель
        if ($user) {
            $isAdmin = $this->userModel->isAdmin($user['id']);
            $user['is_admin'] = $isAdmin;
            
            // Обновить сессию
            $_SESSION['is_admin'] = $isAdmin;
        }
        
        if ($user) {
            return json_encode(['user' => $user], JSON_UNESCAPED_UNICODE);
        }
        
        return json_encode(['user' => null], JSON_UNESCAPED_UNICODE);
    }
}
