<?php

namespace App\Services;

class ValidateRegisterData
{
    /**
     * @var callable|null Функция проверки уникальности email в БД
     */
    private $checkEmailExists;

    /**
     * @param callable|null $checkEmailExists Принимает email, возвращает bool
     */
    public function __construct(?callable $checkEmailExists = null)
    {
        // По умолчанию считаем email уникальным (для упрощения тестов)
        $this->checkEmailExists = $checkEmailExists ?? fn (string $email): bool => false;
    }

    /**
     * Санитизация входных данных.
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Порядок важен: сначала trim, потом удаление тегов, потом экранирование
                $sanitized[$key] = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Валидация данных регистрации
     * Возвращает массив ошибок. Если пустой — данные корректны.
     */
    public function validate(array $data): array
    {
        $errors = [];
        $data = $this->sanitize($data);

        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        // 1. Проверка имени
        if ($username === '') {
            $errors[] = 'Имя пользователя должно быть заполнено.';
        }

        // 2. Проверка email
        if ($email === '') {
            $errors[] = 'Email должен быть заполнен.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный формат email.';
        } elseif (($this->checkEmailExists)($email)) {
            $errors[] = 'Данный email уже зарегистрирован.';
        }

        // 3. Проверка пароля
        if ($password === '') {
            $errors[] = 'Пароль должен быть заполнен.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Пароль должен быть не менее 6 символов.';
        }

        // 4. Проверка подтверждения пароля
        if ($passwordConfirm === '') {
            $errors[] = 'Подтверждение пароля должно быть заполнено.';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Пароли не совпадают.';
        }

        return $errors;
    }
}
