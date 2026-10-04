<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

/**
 * Maps Composer packages to the tools they provide.
 */
final class ToolCatalog
{
    /** The file every coverage command writes its Clover report to. */
    public const COVERAGE_REPORT = 'coverage.xml';

    /** Where Composer puts the tools' executables unless the project configures another directory. */
    public const DEFAULT_BIN_DIR = 'vendor/bin';

    /**
     * @param list<string> $packages names of the packages required by the project
     * @param string       $binDir   the project's Composer "bin-dir", relative to its root
     *
     * @return list<Tool>
     */
    public function detect(array $packages, string $binDir = self::DEFAULT_BIN_DIR): array
    {
        $tools = [];
        $hasTestRunner = false;

        foreach (self::definitions($binDir) as [$candidates, $tool]) {
            if ([] === array_intersect($candidates, $packages)) {
                continue;
            }

            // Pest and Codeception ship with PHPUnit, so only the first matching test runner counts.
            if (ToolType::Tests === $tool->type) {
                if ($hasTestRunner) {
                    continue;
                }

                $hasTestRunner = true;
            }

            $tools[] = $tool;
        }

        return $tools;
    }

    /**
     * @return list<array{list<string>, Tool}> package names => the tool they provide, in order of precedence
     */
    public static function definitions(string $binDir = self::DEFAULT_BIN_DIR): array
    {
        $clover = ' --coverage-clover='.self::COVERAGE_REPORT;
        $bin = static fn (string $executable): string => $binDir.'/'.$executable;

        return [
            [
                ['pestphp/pest'],
                new Tool('Pest', ToolType::Tests, $bin('pest'), $bin('pest').' --coverage'.$clover, '--min'),
            ],
            [['codeception/codeception'], new Tool('Codeception', ToolType::Tests, $bin('codecept').' run')],
            [
                ['phpunit/phpunit'],
                new Tool('PHPUnit', ToolType::Tests, $bin('phpunit'), $bin('phpunit').' --coverage-text'.$clover),
            ],
            [['symfony/phpunit-bridge'], new Tool('PHPUnit Bridge', ToolType::Tests, $bin('simple-phpunit'))],
            [
                ['phpstan/phpstan', 'larastan/larastan', 'nunomaduro/larastan'],
                new Tool('PHPStan', ToolType::StaticAnalysis, $bin('phpstan').' analyse --no-progress'),
            ],
            [['vimeo/psalm'], new Tool('Psalm', ToolType::StaticAnalysis, $bin('psalm').' --no-progress')],
            [['rector/rector'], new Tool('Rector', ToolType::StaticAnalysis, $bin('rector').' process --dry-run')],
            [
                ['deptrac/deptrac', 'qossmic/deptrac-shim'],
                new Tool('Deptrac', ToolType::StaticAnalysis, $bin('deptrac').' analyse --no-progress'),
            ],
            [['phparkitect/phparkitect'], new Tool('PHPArkitect', ToolType::StaticAnalysis, $bin('phparkitect').' check')],
            [
                ['friendsofphp/php-cs-fixer', 'php-cs-fixer/shim'],
                new Tool('PHP-CS-Fixer', ToolType::CodeStyle, $bin('php-cs-fixer').' check --diff'),
            ],
            [['laravel/pint'], new Tool('Pint', ToolType::CodeStyle, $bin('pint').' --test')],
            [['symplify/easy-coding-standard'], new Tool('ECS', ToolType::CodeStyle, $bin('ecs').' check')],
            [['squizlabs/php_codesniffer'], new Tool('PHP_CodeSniffer', ToolType::CodeStyle, $bin('phpcs'))],
            [['vincentlanglet/twig-cs-fixer'], new Tool('Twig-CS-Fixer', ToolType::CodeStyle, $bin('twig-cs-fixer').' lint')],
            [
                ['ergebnis/composer-normalize'],
                new Tool('Composer Normalize', ToolType::Dependencies, 'composer normalize --dry-run'),
            ],
            [
                ['maglnet/composer-require-checker'],
                new Tool('Composer Require Checker', ToolType::Dependencies, $bin('composer-require-checker').' check'),
            ],
            [
                ['icanhazstring/composer-unused'],
                new Tool('Composer Unused', ToolType::Dependencies, $bin('composer-unused').' --no-progress'),
            ],
            [
                ['shipmonk/composer-dependency-analyser'],
                new Tool('Composer Dependency Analyser', ToolType::Dependencies, $bin('composer-dependency-analyser')),
            ],
        ];
    }
}
