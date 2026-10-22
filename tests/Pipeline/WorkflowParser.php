<?php
/**
 * Минимальный парсер GitHub Actions workflow'ов для PHPUnit-тестов.
 *
 * Сознательно НЕ использует расширение yaml и сторонние библиотеки:
 * проверки идут по отступам (2 пробела) и ключам, поэтому тесты
 * работают на любом раннере без дополнительных зависимостей.
 */
declare(strict_types=1);

namespace Tests\Pipeline;

final class WorkflowParser
{
    /** @var string */
    private $raw;

    /** @var string[] */
    private $lines;

    /** @var bool */
    private $loaded;

    /**
     * @param string        $path  путь к workflow-файлу
     * @param string[]|null $lines готовый набор строк (для собственных тестов парсера)
     */
    public function __construct($path = '', $lines = null)
    {
        if (is_array($lines)) {
            $this->lines  = $lines;
            $this->raw    = implode("\n", $lines);
            $this->loaded = true;
            return;
        }

        $this->raw    = '';
        $this->lines  = [];
        $this->loaded = false;

        if ($path !== '' && is_file($path)) {
            $this->raw    = (string) file_get_contents($path);
            $this->lines  = preg_split('/\r\n|\r|\n/', $this->raw);
            $this->loaded = true;
        }
    }

    public static function load($relativePath): self
    {
        return new self($relativePath);
    }

    public function exists(): bool
    {
        return $this->loaded;
    }

    public function raw(): string
    {
        return $this->raw;
    }

    /** @return string[] */
    public function lines(): array
    {
        return $this->lines;
    }

    /**
     * Имена job'ов — ключи с отступом ровно 2 внутри блока `jobs:`.
     * @return string[]
     */
    public function jobNames(): array
    {
        $names  = [];
        $inJobs = false;

        foreach ($this->lines as $line) {
            $trimmed = ltrim($line, ' ');
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }

            $indent = strlen($line) - strlen($trimmed);
            if ($indent === 0) {
                $inJobs = ($trimmed === 'jobs:');
                continue;
            }

            if ($inJobs && $indent === 2 && preg_match('/^([A-Za-z0-9_.-]+):\s*(#.*)?$/', $trimmed, $m)) {
                $names[] = $m[1];
            }
        }

        return $names;
    }

    public function hasJob($name): bool
    {
        return in_array($name, $this->jobNames(), true);
    }

    /**
     * Строки тела job'а (без строки самого ключа).
     * Блок заканчивается на следующем ключе с отступом <= 2.
     *
     * @return string[]
     */
    public function jobBlock($name): array
    {
        $block = [];
        $found = false;

        foreach ($this->lines as $line) {
            $trimmed = ltrim($line, ' ');

            if ($trimmed === '') {
                if ($found) {
                    $block[] = $line;
                }
                continue;
            }

            if ($trimmed[0] === '#') {
                if ($found && (strlen($line) - strlen($trimmed)) > 2) {
                    $block[] = $line;
                }
                continue;
            }

            $indent = strlen($line) - strlen($trimmed);

            if (!$found) {
                $pattern = '/^' . preg_quote($name, '/') . ':\s*(#.*)?$/';
                $found   = ($indent === 2 && preg_match($pattern, $trimmed) === 1);
                continue;
            }

            if ($indent <= 2) {
                break;
            }

            $block[] = $line;
        }

        return $block;
    }

    /**
     * Все значения `run:` внутри job'а, склеенные в один текст
     * (многострочные блоки `run: |` разворачиваются построчно).
     */
    public function jobRunCommands($name): string
    {
        $block     = $this->jobBlock($name);
        $commands  = [];
        $collect   = false;
        $runIndent = -1;

        foreach ($block as $line) {
            $trimmed = ltrim($line, ' ');

            if ($trimmed === '') {
                if ($collect) {
                    $commands[] = $line;
                }
                continue;
            }

            $indent = strlen($line) - strlen($trimmed);

            if (preg_match('/^run:\s*(.*)$/', $trimmed, $m)) {
                $collect   = true;
                $runIndent = $indent;
                $value     = rtrim($m[1]);
                // Блок-индикаторы YAML (|, >, со штрихами) — сама команда в следующих строках
                if ($value === '' || preg_match('/^[|>][-+]?$/', $value) === 1) {
                    continue;
                }
                $commands[] = $value;
                continue;
            }

            if ($collect) {
                if ($indent <= $runIndent) {
                    $collect = false;
                    continue;
                }
                $commands[] = $trimmed;
            }
        }

        return implode("\n", $commands);
    }

    /** Значение `runs-on:` для job'а. */
    public function jobRunsOn($name): string
    {
        foreach ($this->jobBlock($name) as $line) {
            if (preg_match('/^\s*runs-on:\s*(.+)$/', $line, $m)) {
                return trim($m[1]);
            }
        }
        return '';
    }

    /**
     * Список зависимостей job'а (`needs:`).
     * @return string[]
     */
    public function jobNeeds($name): array
    {
        foreach ($this->jobBlock($name) as $line) {
            if (preg_match('/^\s*needs:\s*\[(.*)\]/', $line, $m)) {
                return array_map('trim', explode(',', $m[1]));
            }
            if (preg_match('/^\s*needs:\s*([A-Za-z0-9_.-]+)\s*(#.*)?$/', $line, $m)) {
                return [$m[1]];
            }
        }
        return [];
    }

    /**
     * Все значения `uses:` внутри job'а.
     * @return string[]
     */
    public function jobStepUses($name): array
    {
        $uses = [];
        foreach ($this->jobBlock($name) as $line) {
            if (preg_match('/^\s*uses:\s*(.+)$/', $line, $m)) {
                $uses[] = trim($m[1]);
            }
        }
        return $uses;
    }

    /**
     * Верхнеуровневые триггеры из блока `on:`.
     * @return string[]
     */
    public function triggerNames(): array
    {
        $triggers = [];
        $inOn     = false;

        foreach ($this->lines as $line) {
            $trimmed = ltrim($line, ' ');
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }

            $indent = strlen($line) - strlen($trimmed);

            if ($indent === 0) {
                if (preg_match('/^(?:"on"|\'on\'|on):\s*(.*)$/', $trimmed, $m)) {
                    $inOn = true;
                    $val  = trim($m[1]);
                    if ($val !== '' && $val[0] !== '|' && $val[0] !== '>') {
                        $val = trim($val, '[]');
                        foreach (explode(',', $val) as $item) {
                            $triggers[] = trim($item, " '\"");
                        }
                    }
                } else {
                    $inOn = false;
                }
                continue;
            }

            if (!$inOn || $indent !== 2) {
                continue;
            }

            if (preg_match('/^([A-Za-z_]+):/', $trimmed, $m)) {
                $triggers[] = $m[1];
            }
        }

        return $triggers;
    }

    /**
     * Значения `php:` из матрицы job'а.
     * @return string[]
     */
    public function matrixPhpVersions($name): array
    {
        foreach ($this->jobBlock($name) as $line) {
            if (preg_match('/^\s*php:\s*\[(.*)\]/', $line, $m)) {
                return array_map(function ($version) {
                    return trim($version, " \t'\"");
                }, explode(',', $m[1]));
            }
        }
        return [];
    }

    /** Есть ли в тексте упоминание секрета `secrets.NAME`. */
    public function usesSecret($secretName): bool
    {
        return strpos($this->raw, 'secrets.' . $secretName) !== false;
    }

    public function contains($needle): bool
    {
        return strpos($this->raw, $needle) !== false;
    }

    public function matches($pattern): bool
    {
        return preg_match($pattern, $this->raw) === 1;
    }
}
