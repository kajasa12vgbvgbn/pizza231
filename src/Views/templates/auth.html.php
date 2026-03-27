<?php
/**
 * Шаблон страниц авторизации (регистрация и вход)
 * Доступные переменные:
 * - $error - сообщение об ошибке
 * - $success - сообщение об успехе
 * - $mode - 'register' или 'login'
 * - $texts - массив текстов из storage/templates/auth.json
 */

$texts = $texts ?? [];
$mode = $mode ?? 'login';

if ($mode === 'register') {
    $formText = $texts['register'] ?? [];
} else {
    $formText = $texts['login'] ?? [];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4"><?= htmlspecialchars($formText['title'] ?? ($mode === 'register' ? 'Регистрация' : 'Вход')) ?></h2>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($mode === 'register'): ?>
                        <form method="POST" action="/register">
                            <div class="mb-3">
                                <label for="name" class="form-label"><?= htmlspecialchars($formText['name'] ?? 'Имя') ?></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= htmlspecialchars($formText['email'] ?? 'Email') ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?= htmlspecialchars($formText['password'] ?? 'Пароль') ?></label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text"><?= htmlspecialchars($formText['passwordHint'] ?? 'Минимум 6 символов') ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label"><?= htmlspecialchars($formText['passwordConfirm'] ?? 'Подтверждение пароля') ?></label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars($formText['submit'] ?? 'Зарегистрироваться') ?></button>
                        </form>

                        <div class="text-center mt-3">
                            <span class="text-muted"><?= htmlspecialchars($formText['hasAccount'] ?? 'Уже есть аккаунт?') ?></span>
                            <a href="/login"><?= htmlspecialchars($formText['loginLink'] ?? 'Войти') ?></a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="/login">
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= htmlspecialchars($formText['email'] ?? 'Email') ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?= htmlspecialchars($formText['password'] ?? 'Пароль') ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars($formText['submit'] ?? 'Войти') ?></button>
                        </form>

                        <div class="text-center mt-3">
                            <span class="text-muted"><?= htmlspecialchars($formText['noAccount'] ?? 'Нет аккаунта?') ?></span>
                            <a href="/register"><?= htmlspecialchars($formText['registerLink'] ?? 'Регистрация') ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
