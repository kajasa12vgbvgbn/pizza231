<?php
namespace App\Views;

require_once __DIR__ . '/BaseTemplate.php';

class AuthTemplate extends BaseTemplate
{
    private const TEMPLATE_PATH = __DIR__ . '/templates/base.html.php';
    
    /**
     * Рендер страницы регистрации
     */
    public static function renderRegister(string $error = '', string $success = ''): string
    {
        $content = '
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="text-center mb-4">Регистрация</h2>
                            
                            ' . ($success ? '<div class="alert alert-success">' . $success . '</div>' : '') . '
                            ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                            
                            <form method="POST" action="/register">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Имя</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    <div class="form-text">Минимум 6 символов</div>
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <span class="text-muted">Уже есть аккаунт?</span>
                                <a href="/login">Войти</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        return parent::getTemplate($content);
    }
    
    /**
     * Рендер страницы входа
     */
    public static function renderLogin(string $error = ''): string
    {
        $content = '
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="text-center mb-4">Вход</h2>
                            
                            ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                            
                            <form method="POST" action="/login">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Войти</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <span class="text-muted">Нет аккаунта?</span>
                                <a href="/register">Регистрация</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        return parent::getTemplate($content);
    }
}
