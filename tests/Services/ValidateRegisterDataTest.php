<?php

namespace Tests\Services;

use App\Services\ValidateRegisterData;
use PHPUnit\Framework\TestCase;

class ValidateRegisterDataTest extends TestCase
{
    private ValidateRegisterData $validator;

    protected function setUp(): void
    {
        // По умолчанию email считается уникальным
        $this->validator = new ValidateRegisterData(fn (string $email) => false);
    }

    public function testValidDataReturnsNoErrors(): void
    {
        $data = [
            'username' => 'ivan_dev',
            'email' => 'ivan@example.com',
            'password' => 'strongPass123',
            'password_confirm' => 'strongPass123',
        ];
        $this->assertEmpty($this->validator->validate($data));
    }

    public function testEmptyUsername(): void
    {
        $data = ['username' => '', 'email' => 't@t.com', 'password' => '123456', 'password_confirm' => '123456'];
        $errors = $this->validator->validate($data);
        $this->assertContains('Имя пользователя должно быть заполнено.', $errors);
    }

    public function testInvalidEmailFormat(): void
    {
        $data = ['username' => 'u', 'email' => 'not-email', 'password' => '123456', 'password_confirm' => '123456'];
        $errors = $this->validator->validate($data);
        $this->assertContains('Некорректный формат email.', $errors);
    }

    public function testEmailAlreadyExists(): void
    {
        $validator = new ValidateRegisterData(fn (string $email) => $email === 'exists@test.com');
        $data = ['username' => 'u', 'email' => 'exists@test.com', 'password' => '123456', 'password_confirm' => '123456'];
        $errors = $validator->validate($data);
        $this->assertContains('Данный email уже зарегистрирован.', $errors);
    }

    public function testShortPassword(): void
    {
        $data = ['username' => 'u', 'email' => 't@t.com', 'password' => '123', 'password_confirm' => '123'];
        $errors = $this->validator->validate($data);
        $this->assertContains('Пароль должен быть не менее 6 символов.', $errors);
    }

    public function testPasswordMismatch(): void
    {
        $data = ['username' => 'u', 'email' => 't@t.com', 'password' => 'pass1', 'password_confirm' => 'pass2'];
        $errors = $this->validator->validate($data);
        $this->assertContains('Пароли не совпадают.', $errors);
    }

    public function testSanitizationRemovesScriptsAndSpaces(): void
    {
        $data = [
            'username' => '  <script>alert(1)</script>user  ',
            'email' => '  mail@example.com  ',
            'password' => '  pass123  ',
            'password_confirm' => '  pass123  ',
        ];

        $sanitized = $this->validator->sanitize($data);

        // sanitize() делает trim -> strip_tags -> htmlspecialchars,
        // поэтому теги вырезаются целиком, а не экранируются
        $this->assertEquals('alert(1)user', $sanitized['username']);
        $this->assertEquals('mail@example.com', $sanitized['email']);
        $this->assertEquals('pass123', $sanitized['password']);
    }
}
