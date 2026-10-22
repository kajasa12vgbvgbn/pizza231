<?php
/**
 * Зелёные тесты конфигурации CI/CD (testsuite "Pipeline").
 *
 * Проверяют, что пайплайн собран корректно:
 *  - CI-воркфлоп содержит все обязательные job'ы и шаги;
 *  - CD-воркфлоп имеет допуск, проверку пакета, выкладку, smoke-тест, очистку;
 *  - composer.json / phpunit.xml валидны и содержат нужные секции;
 *  - структура проекта чистая (тесты не лежат в src/), секреты не в git.
 *
 * Падение любого теста из этого файла означает, что пайплайн настроен
 * неправильно и не выполнит свою работу.
 *
 * @group pipeline
 */
declare(strict_types=1);

namespace Tests\Pipeline;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class PipelineConfigTest extends TestCase
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

    // =================================================================
    // CI: существование и job'ы
    // =================================================================

    public function testCiWorkflowExists(): void
    {
        $this->assertTrue(
            $this->ci->exists(),
            'Файл .github/workflows/ci.yml должен существовать — иначе CI не запускается'
        );
    }

    /**
     * @dataProvider requiredCiJobsProvider
     */
    public function testCiWorkflowHasRequiredJob(string $jobName): void
    {
        $this->assertTrue(
            $this->ci->hasJob($jobName),
            sprintf(
                'CI-воркфлоп должен содержать job "%s". Доступные job\'ы: %s',
                $jobName,
                implode(', ', $this->ci->jobNames())
            )
        );
    }

    public function requiredCiJobsProvider(): array
    {
        return [
            'lint'            => ['lint'],
            'test'            => ['test'],
            'pipeline-config' => ['pipeline-config'],
            'red-demo'        => ['red-demo'],
            'report'          => ['report'],
            'package'         => ['package'],
        ];
    }

    /**
     * @dataProvider requiredCdJobsProvider
     */
    public function testCdWorkflowHasRequiredJob(string $jobName): void
    {
        $this->assertTrue(
            $this->cd->hasJob($jobName),
            sprintf(
                'CD-воркфлоп должен содержать job "%s". Доступные job\'ы: %s',
                $jobName,
                implode(', ', $this->cd->jobNames())
            )
        );
    }

    public function requiredCdJobsProvider(): array
    {
        return [
            'gate'            => ['gate'],
            'verify-artifact' => ['verify-artifact'],
            'deploy'          => ['deploy'],
            'smoke-test'      => ['smoke-test'],
            'cleanup'         => ['cleanup'],
            'notify'          => ['notify'],
        ];
    }

    public function testCdWorkflowExists(): void
    {
        $this->assertTrue(
            $this->cd->exists(),
            'Файл .github/workflows/deploy.yml должен существовать'
        );
    }

    // =================================================================
    // CI: триггеры, окружение, распараллеливание
    // =================================================================

    public function testCiWorkflowTriggersOnPushAndPullRequest(): void
    {
        $triggers = $this->ci->triggerNames();
        $this->assertContains('push', $triggers, 'CI должен запускаться на push');
        $this->assertContains('pull_request', $triggers, 'CI должен запускаться на pull_request');
    }

    public function testCiWorkflowCanBeStartedManually(): void
    {
        $this->assertContains(
            'workflow_dispatch',
            $this->ci->triggerNames(),
            'CI должен допускать ручной запуск (workflow_dispatch)'
        );
    }

    public function testCiWorkflowCancelsSupersededRuns(): void
    {
        $this->assertTrue(
            $this->ci->contains('cancel-in-progress: true'),
            'CI должен отменять устаревшие запуски той же ветки (concurrency.cancel-in-progress)'
        );
    }

    public function testAllCiJobsRunOnUbuntu(): void
    {
        $jobs = $this->ci->jobNames();
        $this->assertNotEmpty($jobs, 'В CI-воркфлопе должны быть job\'ы');

        foreach ($jobs as $jobName) {
            $this->assertStringContainsString(
                'ubuntu',
                $this->ci->jobRunsOn($jobName),
                "Job '$jobName' должен выполняться на ubuntu-latest"
            );
        }
    }

    public function testCiWorkflowHasPhpVersionMatrix(): void
    {
        $versions = $this->ci->matrixPhpVersions('test');
        $this->assertNotEmpty($versions, 'Job test должен задавать матрицу версий PHP');
        $this->assertContains('8.2', $versions, 'Матрица PHP должна включать 8.2');
    }

    // =================================================================
    // CI: содержимое job'ов
    // =================================================================

    public function testLintJobChecksPhpSyntax(): void
    {
        $this->assertStringContainsString(
            'php -l',
            $this->ci->jobRunCommands('lint'),
            'Job lint должен проверять синтаксис PHP через php -l'
        );
    }

    public function testLintJobUsesCheckoutAndSetupPhp(): void
    {
        $uses = $this->ci->jobStepUses('lint');
        $this->assertTrue(
            $this->anyStepUses($uses, 'actions/checkout'),
            'Job lint должен выполнять actions/checkout'
        );
        $this->assertTrue(
            $this->anyStepUses($uses, 'shivammathur/setup-php'),
            'Job lint должен устанавливать PHP через shivammathur/setup-php'
        );
    }

    public function testTestJobInstallsDependenciesAndRunsPhpunit(): void
    {
        $commands = $this->ci->jobRunCommands('test');
        $this->assertStringContainsString('composer install', $commands, 'Job test должен ставить зависимости');
        $this->assertStringContainsString('phpunit', $commands, 'Job test должен запускать PHPUnit');
        $this->assertStringContainsString(
            '--testsuite Unit',
            $commands,
            'Job test должен запускать именно testsuite Unit'
        );
    }

    public function testTestJobProducesCoverageReport(): void
    {
        $commands = $this->ci->jobRunCommands('test');
        $this->assertStringContainsString(
            'coverage-clover',
            $commands,
            'Job test должен генерировать clover-отчёт покрытия'
        );
    }

    public function testTestJobDependsOnLintJob(): void
    {
        $this->assertContains(
            'lint',
            $this->ci->jobNeeds('test'),
            'Job test не должен запускаться, если линт упал'
        );
    }

    public function testPipelineConfigJobRunsGreenSuite(): void
    {
        $commands = $this->ci->jobRunCommands('pipeline-config');
        $this->assertStringContainsString(
            '--testsuite Pipeline',
            $commands,
            'Job pipeline-config должен запускать зелёный набор Pipeline'
        );
    }

    public function testRedDemoJobRunsRedSuiteAndIsNonBlocking(): void
    {
        $commands = $this->ci->jobRunCommands('red-demo');
        $this->assertStringContainsString(
            '--testsuite Red',
            $commands,
            'Job red-demo должен запускать красный набор тестов'
        );
        $this->assertTrue(
            $this->ci->contains('continue-on-error: true'),
            'Job red-demo не должен блокировать пайплайн (continue-on-error: true)'
        );
    }

    public function testPackageJobBuildsReleaseArchive(): void
    {
        $commands = $this->ci->jobRunCommands('package');
        $this->assertStringContainsString('tar', $commands, 'Job package должен собирать архив релиза');
        $this->assertStringContainsString(
            'deploy-package',
            $this->ci->raw(),
            'Job package должен публиковать артефакт deploy-package для CD'
        );
    }

    // =================================================================
    // CD: содержимое job'ов
    // =================================================================

    public function testCdWorkflowWaitsForSuccessfulCi(): void
    {
        $this->assertContains(
            'workflow_run',
            $this->cd->triggerNames(),
            'CD должен запускаться по завершении CI (workflow_run)'
        );
        $this->assertTrue(
            $this->cd->contains("github.event.workflow_run.conclusion == 'success'"),
            'CD должен пускать деплой только при успешном CI'
        );
    }

    public function testCdWorkflowSerializesDeploys(): void
    {
        $this->assertTrue(
            $this->cd->contains('cancel-in-progress: false'),
            'Деплои не должны отменяться на середине — concurrency.cancel-in-progress: false'
        );
    }

    public function testCdDeployJobIsProtectedByEnvironment(): void
    {
        $this->assertTrue(
            $this->cd->contains('environment:'),
            'Job deploy должен использовать environment (ручное подтверждение / защита)'
        );
    }

    public function testCdDeployJobUsesSshSecretsNotHardcodedValues(): void
    {
        foreach (['DEPLOY_HOST', 'DEPLOY_USER', 'DEPLOY_SSH_KEY'] as $secret) {
            $this->assertTrue(
                $this->cd->usesSecret($secret),
                "CD должен читать $secret из secrets, а не хардкодить"
            );
        }
    }

    public function testCdDeployJobPerformsAtomicReleaseSwitch(): void
    {
        $commands = $this->cd->jobRunCommands('deploy');
        $this->assertStringContainsString('releases', $commands, 'Деплой должен писать в каталог releases/');
        $this->assertStringContainsString('ln -nfn', $commands, 'Деплой должен переключать symlink current');
        $this->assertStringContainsString('mv -Tf', $commands, 'Переключение symlink должно быть атомарным');
    }

    public function testCdDeployJobHasRollback(): void
    {
        $deployBlock = $this->cd->jobBlock('deploy');
        $this->assertStringContainsString(
            'if: failure()',
            implode("\n", $deployBlock),
            'Job deploy должен содержать шаг отката при падении'
        );
    }

    public function testCdSmokeTestChecksKeyRoutes(): void
    {
        $commands = $this->cd->jobRunCommands('smoke-test');
        $this->assertStringContainsString('curl', $commands, 'Smoke-тест должен дёргать HTTP-маршруты');
        $this->assertStringContainsString('/catalog', $commands, 'Smoke-тест должен проверять /catalog');
        $this->assertStringContainsString('/about', $commands, 'Smoke-тест должен проверять /about');
    }

    // =================================================================
    // Composer
    // =================================================================

    public function testComposerJsonIsValid(): void
    {
        $composer = $this->readComposerJson();
        $this->assertIsArray($composer, 'composer.json должен быть валидным JSON');
    }

    public function testComposerJsonRequiresPhpUnit(): void
    {
        $composer = $this->readComposerJson();
        $this->assertArrayHasKey('require-dev', $composer, 'composer.json должен иметь require-dev');
        $this->assertArrayHasKey(
            'phpunit/phpunit',
            $composer['require-dev'],
            'PHPUnit должен быть в require-dev — на нём держится CI'
        );
    }

    public function testComposerJsonDeclaresAutoload(): void
    {
        $composer = $this->readComposerJson();
        $this->assertArrayHasKey('autoload', $composer);
        $this->assertArrayHasKey('App\\', $composer['autoload']['psr-4']);
    }

    public function testComposerJsonDeclaresTestAutoload(): void
    {
        $composer = $this->readComposerJson();
        $this->assertArrayHasKey(
            'autoload-dev',
            $composer,
            'Пространство Tests\\ должно быть объявлено в autoload-dev, '
            . 'иначе тесты в tests/ не будут находиться авто loader'
        );
        $this->assertArrayHasKey('Tests\\', $composer['autoload-dev']['psr-4']);
    }

    public function testComposerJsonHasTestScripts(): void
    {
        $composer = $this->readComposerJson();
        $this->assertArrayHasKey('scripts', $composer);
        foreach (['test', 'test-pipeline'] as $script) {
            $this->assertArrayHasKey(
                $script,
                $composer['scripts'],
                "Composer-скрипт \"$script\" должен существовать — его вызывает локальная разработка и CI"
            );
        }
    }

    public function testComposerLockIsPresent(): void
    {
        $this->assertFileExists(
            $this->rootPath('composer.lock'),
            'composer.lock обязателен: CI выполняет composer install по зафиксированным версиям'
        );
    }

    // =================================================================
    // PHPUnit
    // =================================================================

    public function testPhpunitXmlIsValidXml(): void
    {
        $xml = $this->readFile('phpunit.xml');
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document, 'phpunit.xml должен быть валидным XML');
        $this->assertCount(0, $errors, 'phpunit.xml не должен содержать ошибок разбора XML');
    }

    public function testPhpunitXmlDeclaresAllTestsuites(): void
    {
        $xml = $this->readFile('phpunit.xml');
        foreach (['Unit', 'Pipeline', 'Red', 'All'] as $suite) {
            $this->assertStringContainsString(
                'name="' . $suite . '"',
                $xml,
                "phpunit.xml должен объявлять testsuite \"$suite\", который вызывают job'ы CI"
            );
        }
    }

    public function testPhpunitXmlBootstrapFileExists(): void
    {
        $xml = $this->readFile('phpunit.xml');

        $this->assertSame(
            1,
            preg_match('/bootstrap="([^"]+)"/', $xml, $matches),
            'phpunit.xml должен указывать bootstrap-файл'
        );

        $this->assertFileExists(
            $this->rootPath($matches[1]),
            'Указанный в phpunit.xml bootstrap-файл должен существовать'
        );
    }

    public function testPhpunitXmlConfiguresCoverage(): void
    {
        $xml = $this->readFile('phpunit.xml');
        $this->assertStringContainsString('<coverage>', $xml, 'Должна быть настроена секция <coverage>');
        $this->assertStringContainsString(
            'suffix=".php">src<',
            $xml,
            'Покрытие должно считаться по каталогу src/'
        );
    }

    // =================================================================
    // Структура проекта
    // =================================================================

    public function testNoTestFilesLiveInsideSrc(): void
    {
        $strayTests = $this->findFilesBySuffix($this->rootPath('src'), 'Test.php');

        $this->assertSame(
            [],
            $strayTests,
            "Тесты внутри src/ не запускаются PHPUnit'ом и мусорят в авто loader. Найдено: "
            . implode(', ', $strayTests)
        );
    }

    public function testStorageLogsDirectoryIsPresent(): void
    {
        $this->assertDirectoryExists($this->rootPath('storage/logs'), 'Каталог storage/logs должен существовать');
    }

    public function testGitignoreProtectsVendorAndSecrets(): void
    {
        $gitignore = $this->readFile('.gitignore');

        foreach (
            [
                'vendor'                => 'vendor/ не должен попадать в git — его ставит composer в CI',
                'config/email.php'      => 'config/email.php содержит SMTP-пароль и не должен попадать в git',
                'storage/logs'          => 'логи не должны попадать в git',
                '.phpunit.result.cache' => 'кеш PHPUnit не должен попадать в git',
                'build'                 => 'отчёты тестов и покрытия (build/) не должны попадать в git',
            ] as $pattern => $reason
        ) {
            $this->assertStringContainsString($pattern, $gitignore, $reason);
        }
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
        $this->assertFileExists($absolute);
        return (string) file_get_contents($absolute);
    }

    /** @return array<string,mixed>|null */
    private function readComposerJson(): ?array
    {
        return json_decode($this->readFile('composer.json'), true);
    }

    /**
     * @param string[] $uses
     */
    private function anyStepUses(array $uses, string $needle): bool
    {
        foreach ($uses as $action) {
            if (strpos($action, $needle) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Рекурсивный поиск файлов с суффиксом (PHP glob не понимает **).
     * @return string[]
     */
    private function findFilesBySuffix(string $directory, string $suffix): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $found    = [];

        foreach (new RegexIterator($iterator, '/.*' . preg_quote($suffix, '/') . '$/') as $file) {
            $found[] = str_replace('\\', '/', $file->getPathname());
        }

        sort($found);
        return $found;
    }
}
