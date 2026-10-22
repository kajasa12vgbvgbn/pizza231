<?php
/**
 * =====================================================================
 * КРАСНЫЙ набор тестов пайплайна (testsuite "Red") — красная фаза TDD.
 * =====================================================================
 *
 * КАЖДЫЙ тест в этом файле НАМЕРЕННО ПАДАЕТ. Это не баг — это фича:
 * они описывают требования к CI/CD, которые ещё не реализованы.
 *
 * В CI они запускаются job'ом `red-demo` с `continue-on-error: true`,
 * поэтому в UI виден красный крест, но релиз не блокируется.
 *
 * Как делать зелёную фазу:
 *   1. реализовать требование из блока TODO ниже теста;
 *   2. тест сам позеленеет — удалять его не нужно;
 *   3. когда весь набор позеленеет, уберите `continue-on-error: true`
 *      в .github/workflows/ci.yml, и набор станет блокирующим.
 *
 * @group red-demo
 * @group pipeline
 */
declare(strict_types=1);

namespace Tests\RedDemo;

use PHPUnit\Framework\TestCase;
use Tests\Pipeline\WorkflowParser;

class PipelineRedDemoTest extends TestCase
{
    /** @var WorkflowParser */
    private $ci;

    /** @var WorkflowParser */
    private $cd;

    protected function setUp(): void
    {
        $this->ci = WorkflowParser::load($this->rootPath('.github/workflows/ci.yml'));
        $this->cd = WorkflowParser::load($this->rootPath('.github/workflows/deploy.yml'));
    }

    /**
     * ТРЕБУЕТСЯ: в CI должен быть job безопасности с аудитом зависимостей.
     * Сейчас такого job'а нет — уязвимости в phpmailer и т.д. не ловятся.
     */
    public function testCiMustContainSecurityAuditJob(): void
    {
        $this->assertTrue(
            $this->ci->hasJob('security'),
            'TODO: добавить в .github/workflows/ci.yml job "security" '
            . 'с шагом `composer audit`, чтобы CI падал на известных уязвимостей зависимостей.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: job security должен блокировать пайплайн при уязвимости.
     */
    public function testSecurityJobMustBlockThePipeline(): void
    {
        $this->assertTrue(
            $this->ci->hasJob('security'),
            'TODO: job "security" должен быть в needs у job\'а "report", '
            . 'иначе найденная уязвимость не остановит релиз.'
        );

        $this->assertContains(
            'security',
            $this->ci->jobNeeds('report'),
            'TODO: добавить "security" в needs job\'а report.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: покрытие кода должно быть не ниже 80 % и проверяться
     * автоматически — сейчас CI зелёный даже при 0 % покрытия.
     *
     * В PHPUnit 9.6 (текущая версия) порог задаётся не в XML, а на этапе
     * сборки: CI должен содержать шаг, который читает clover.xml и падает
     * при покрытии < 80 %.
     */
    public function testCoverageMustHaveEnforcedThreshold(): void
    {
        $this->assertTrue(
            $this->ci->matches('/coverage-gate|coverage-min|php-coveralls|phpunit-coverage-gate/i'),
            'TODO: добавить в job test шаг проверки порога покрытия (coverage gate), '
            . 'который читает build/reports/clover.xml и падает, если покрыт < 80 % кода. '
            . 'В PHPUnit 9.6 это делается внешним скриптом (php-coveralls / custom gate), '
            . 'а не атрибутом XML — поэтому проверяем наличие шага в ci.yml.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: CI должен проверять приложение на PHP 8.3 — он уже стандарт
     * для новых проектов, а приложение когда-нибудь придётся переносить.
     */
    public function testTestMatrixMustIncludePhp83(): void
    {
        $versions = $this->ci->matrixPhpVersions('test');

        $this->assertContains(
            '8.3',
            $versions,
            'TODO: добавить \'8.3\' в strategy.matrix.php job\'а test '
            . '(сейчас матрица: ' . implode(', ', $versions) . ').'
        );
    }

    /**
     * ТРЕБУЕТСЯ: у роутера — самого горячего места приложения — должны быть тесты.
     * Сейчас tests/Router/ не существует вообще.
     */
    public function testRouterMustHaveUnitTests(): void
    {
        $this->assertFileExists(
            $this->rootPath('tests/Router/RouterTest.php'),
            'TODO: написать tests/Router/RouterTest.php — проверить матчинг /catalog, '
            . '/product/{id}, /api/cart/add и возврат 404 для неизвестного маршрута.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: отправка писем должна быть покрыта тестами,
     * иначе CI не заметит сломанного шаблона уведомления о заказе.
     */
    public function testEmailSenderMustHaveUnitTests(): void
    {
        $this->assertFileExists(
            $this->rootPath('tests/Models/EmailSenderTest.php'),
            'TODO: написать tests/Models/EmailSenderTest.php — тестировать генерацию '
            . 'HTML/TEXT тела письма и экранирование данных клиента.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: статический анализ типов. PHPUnit не ловает ошибки типов и
     * несуществующие методы, а PHPStan поймает.
     */
    public function testStaticAnalysisToolMustBeInstalled(): void
    {
        $composer = json_decode($this->readFile('composer.json'), true);

        $this->assertArrayHasKey(
            'phpstan/phpstan',
            $composer['require-dev'] ?? [],
            'TODO: добавить phpstan/phpstan в require-dev и запустить его в job\'е lint — '
            . 'он найдёт обращения к несуществующим методам раньше, чем это сделает продакшен.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: деплой не должен ронять сайт на битом конфиге.
     * В CD нет шага валидации конфигурации перед переключением symlink.
     */
    public function testCdMustValidateConfigBeforeSwitchingSymlink(): void
    {
        $deployCommands = $this->cd->jobRunCommands('deploy');

        $this->assertStringContainsString(
            'validate-config',
            $deployCommands,
            'TODO: добавить в job deploy шаг проверки config/email.php и доступности БД '
            . 'ДО атомарного переключения symlink current.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: CI должен автоматически искать секреты в диффе.
     * Сейчас config/email.php с реальным SMTP-паролем закрыт только .gitignore,
     * а это хрупкая защита: достаточно `git add -f`, и пароль уедет в историю.
     */
    public function testCiMustScanDiffForSecrets(): void
    {
        $this->assertTrue(
            $this->ci->matches('/gitleaks|detect-secrets|secretlint/i'),
            'TODO: добавить в CI проверку диффа на секреты (gitleaks / detect-secrets), '
            . 'чтобы SMTP-пароль из config/email.php не мог попасть в репозиторий.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: CI должен собирать docker-образ, чтобы окружение сборки
     * и продакшен совпадали.
     */
    public function testCiMustBuildDockerImage(): void
    {
        $this->assertTrue(
            $this->ci->hasJob('docker'),
            'TODO: добавить job "docker" со сборкой образа и Dockerfile в корень проекта — '
            . 'сейчас деплой зависит от того, что установлено на сервере.'
        );
    }

    /**
     * ТРЕБУЕТСЯ: красный набор должен быть блокирующим. Пока это утверждение
     * ложно — значит, пайплайн ещё в красной фазе.
     */
    public function testRedSuiteMustBecomeBlocking(): void
    {
        $this->assertFalse(
            $this->ci->contains('continue-on-error: true'),
            'TODO: когда все тесты этого набора позеленеют — убери continue-on-error: true '
            . 'у job\'а red-demo, и он станет блокирующим. Сейчас он не блокирует.'
        );
    }

    // =================================================================
    // Вспомогательные методы
    // =================================================================

    private function rootPath(string $relative = ''): string
    {
        $root = defined('ROOT_PATH') ? (string) ROOT_PATH : getcwd();
        return $relative === '' ? $root : rtrim($root, '/\\') . DIRECTORY_SEPARATOR . $relative;
    }

    private function readFile(string $relativePath): string
    {
        $absolute = $this->rootPath($relativePath);
        return is_file($absolute) ? (string) file_get_contents($absolute) : '';
    }
}
