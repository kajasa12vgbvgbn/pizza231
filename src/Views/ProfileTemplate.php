<?php
namespace App\Views;

require_once __DIR__ . '/BaseTemplate.php';

class ProfileTemplate extends BaseTemplate
{
    /**
     * Путь к файлу с текстами
     */
    private const TEXTS_PATH = __DIR__ . '/../../storage/templates/profile.json';
    
    /**
     * Загрузить тексты из JSON
     */
    private static function loadTexts(): array
    {
        $file = self::TEXTS_PATH;
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return [];
    }
    
    /**
     * Страница профиля
     */
    public static function render(array $profile): void
    {
        $texts = self::loadTexts();
        
        $name = htmlspecialchars($profile['name'] ?? '');
        $email = htmlspecialchars($profile['email'] ?? '');
        $phone = htmlspecialchars($profile['phone'] ?? '');
        $address = htmlspecialchars($profile['address'] ?? '');
        $avatar = $profile['avatar'] ?? '';
        
        // Если аватар пустой - показываем заглушку
        $avatarHtml = '';
        if (!empty($avatar)) {
            $avatarHtml = '<img id="avatar-preview" src="' . htmlspecialchars($avatar) . '" alt="Аватар" class="rounded-circle profile-avatar">';
        } else {
            $avatarHtml = '<div id="avatar-preview" class="profile-avatar-placeholder rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-person-fill fs-1"></i>
            </div>';
        }
        
        // Извлекаем тексты для использования в heredoc
        $pageTitle = $texts['pageTitle'] ?? 'Настройки профиля';
        $avatarTitle = $texts['avatar']['title'] ?? 'Аватар';
        $avatarChange = $texts['avatar']['change'] ?? 'Изменить аватар';
        $avatarFormats = $texts['avatar']['formats'] ?? 'JPEG, PNG, GIF, WebP (макс. 2MB)';
        $formTitle = $texts['form']['title'] ?? 'Информация';
        $nameLabel = $texts['form']['name'] ?? 'Имя';
        $namePlaceholder = $texts['form']['namePlaceholder'] ?? 'Ваше имя';
        $emailLabel = $texts['form']['email'] ?? 'Email';
        $phoneLabel = $texts['form']['phone'] ?? 'Телефон';
        $addressLabel = $texts['form']['address'] ?? 'Адрес доставки';
        $addressPlaceholder = $texts['form']['addressPlaceholder'] ?? 'Улица, дом, квартира, индекс';
        $saveBtnText = $texts['form']['save'] ?? 'Сохранить изменения';
        $emailNote = $texts['info']['emailNote'] ?? 'Email нельзя изменить';
        $phoneNote = $texts['info']['phoneNote'] ?? 'Телефон нельзя изменить';
        
        $content = <<<HTML
<main>
    <div class="container py-4">
        <h1 class="mb-4">{$pageTitle}</h1>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <!-- Секция аватара -->
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-3">{$avatarTitle}</h5>
                        
                        <div class="avatar-container mb-3">
                            {$avatarHtml}
                        </div>
                        
                        <form id="avatar-form" enctype="multipart/form-data">
                            <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('avatar-input').click()">
                                <i class="bi bi-camera me-2"></i>{$avatarChange}
                            </button>
                        </form>
                        
                        <p class="text-muted small mt-2 mb-0">
                            {$avatarFormats}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Форма профиля -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">{$formTitle}</h5>
                        
                        <form id="profile-form">
                            <!-- Имя -->
                            <div class="mb-3">
                                <label for="name" class="form-label">{$nameLabel}</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{$name}" placeholder="{$namePlaceholder}" required>
                            </div>
                            
                            <!-- Email (только чтение) -->
                            <div class="mb-3">
                                <label for="email" class="form-label">{$emailLabel}</label>
                                <input type="email" class="form-control" id="email" value="{$email}" readonly>
                                <div class="form-text">{$emailNote}</div>
                            </div>
                            
                            <!-- Телефон (только чтение) -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">{$phoneLabel}</label>
                                <input type="tel" class="form-control" id="phone" value="{$phone}" readonly>
                                <div class="form-text">{$phoneNote}</div>
                            </div>
                            
                            <!-- Адрес доставки -->
                            <div class="mb-3">
                                <label for="address" class="form-label">{$addressLabel}</label>
                                <textarea class="form-control" id="address" name="address" rows="3" 
                                          placeholder="{$addressPlaceholder}">{$address}</textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" id="save-btn">
                                <span class="btn-text">{$saveBtnText}</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </form>
                        
                        <!-- Toast для уведомлений -->
                        <div class="toast-container position-fixed bottom-0 end-0 p-3">
                            <div id="profile-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header">
                                    <strong class="me-auto">Профиль</strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                                </div>
                                <div class="toast-body"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
HTML;
        
        // Добавить CSS для профиля
        $css = '<link rel="stylesheet" href="/assets/css/profile.css">';
        
        // Добавить JS для профиля
        $js = '<script src="/assets/js/profile.js"></script>';
        
        // Используем родительский метод для рендеринга
        $html = parent::getTemplate($content, $texts);
        
        // Добавляем CSS и JS перед </body>
        $html = str_replace('</body>', $css . $js . '</body>', $html);
        
        echo $html;
    }
}
