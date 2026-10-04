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

    /**
     * @param list<string> $packages names of the packages required by the project
     *
     * @return list<Tool>
     */
    public function detect(array $packages): array
    {
        $tools = [];
        $hasTestRunner = false;

        foreach (self::definitions() as [$candidates, $tool]) {
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
    public static function definitions(): array
    {
        $clover = ' --coverage-clover='.self::COVERAGE_REPORT;

        return [
            [
                ['pestphp/pest'],
                new Tool('Pest', ToolType::Tests, 'vendor/bin/pest', 'vendor/bin/pest --coverage'.$clover),
            ],
            [['codeception/codeception'], new Tool('Codeception', ToolType::Tests, 'vendor/bin/codecept run')],
            [
                ['phpunit/phpunit'],
                new Tool('PHPUnit', ToolType::Tests, 'vendor/bin/phpunit', 'vendor/bin/phpunit --coverage-text'.$clover),
            ],
            [['symfony/phpunit-bridge'], new Tool('PHPUnit Bridge', ToolType::Tests, 'vendor/bin/simple-phpunit')],
            [
                ['phpstan/phpstan', 'larastan/larastan', 'nunomaduro/larastan'],
                new Tool('PHPStan', ToolType::StaticAnalysis, 'vendor/bin/phpstan analyse --no-progress'),
            ],
            [['vimeo/psalm'], new Tool('Psalm', ToolType::StaticAnalysis, 'vendor/bin/psalm --no-progress')],
            [['rector/rector'], new Tool('Rector', ToolType::StaticAnalysis, 'vendor/bin/rector process --dry-run')],
            [
                ['deptrac/deptrac', 'qossmic/deptrac-shim'],
                new Tool('Deptrac', ToolType::StaticAnalysis, 'vendor/bin/deptrac analyse --no-progress'),
            ],
            [
                ['friendsofphp/php-cs-fixer', 'php-cs-fixer/shim'],
                new Tool('PHP-CS-Fixer', ToolType::CodeStyle, 'vendor/bin/php-cs-fixer check --diff'),
            ],
            [['laravel/pint'], new Tool('Pint', ToolType::CodeStyle, 'vendor/bin/pint --test')],
            [['symplify/easy-coding-standard'], new Tool('ECS', ToolType::CodeStyle, 'vendor/bin/ecs check')],
            [['squizlabs/php_codesniffer'], new Tool('PHP_CodeSniffer', ToolType::CodeStyle, 'vendor/bin/phpcs')],
        ];
    }
}
