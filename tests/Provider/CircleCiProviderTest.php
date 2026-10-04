<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Provider;

use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\CircleCiProvider;
use AxonPHP\Cli\Provider\PipelineOptions;
use AxonPHP\Cli\Provider\Yaml;
use AxonPHP\Cli\Tests\ReadsYaml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CircleCiProvider::class)]
#[CoversClass(Plan::class)]
#[CoversClass(Yaml::class)]
final class CircleCiProviderTest extends TestCase
{
    use ReadsYaml;

    public function testDescribesItself(): void
    {
        $provider = new CircleCiProvider();

        self::assertSame('circleci', $provider->name());
        self::assertSame('CircleCI', $provider->label());
        self::assertSame('.circleci/config.yml', $provider->path());
    }

    public function testRendersQualityAndTestJobs(): void
    {
        $project = new Project(
            ['8.3', '8.4'],
            ['intl'],
            [
                new Tool('PHPUnit', ToolType::Tests, 'vendor/bin/phpunit'),
                new Tool('PHPStan', ToolType::StaticAnalysis, 'vendor/bin/phpstan analyse --no-progress'),
            ],
        );

        $config = (new CircleCiProvider())->render($project, new PipelineOptions(['main']));

        self::assertStringStartsWith(Yaml::HEADER."\n", $config);
        self::assertSame(2.1, self::yaml($config, 'version'));
        self::assertSame(['quality', 'tests'], array_keys((array) self::yaml($config, 'jobs')));

        self::assertSame([['image' => 'php:8.4-cli']], self::yaml($config, 'jobs', 'quality', 'docker'));
        self::assertSame(
            ['COMPOSER_ALLOW_SUPERUSER' => '1', 'COMPOSER_NO_INTERACTION' => '1', 'COMPOSER_CACHE_DIR' => '/tmp/composer-cache'],
            self::yaml($config, 'jobs', 'quality', 'environment'),
        );
        self::assertSame(
            [
                'checkout',
                ['run' => ['name' => 'Set up PHP', 'command' => implode("\n", Yaml::dockerSetup(['intl']))."\n"]],
                ['restore_cache' => ['keys' => ['composer-{{ checksum "composer.json" }}', 'composer-']]],
                ['run' => ['name' => 'Install dependencies', 'command' => Plan::INSTALL_COMMAND]],
                ['save_cache' => ['key' => 'composer-{{ checksum "composer.json" }}', 'paths' => ['/tmp/composer-cache']]],
                ['run' => ['name' => 'Static analysis (PHPStan)', 'command' => 'vendor/bin/phpstan analyse --no-progress']],
            ],
            self::yaml($config, 'jobs', 'quality', 'steps'),
        );

        self::assertSame(['php' => ['type' => 'string']], self::yaml($config, 'jobs', 'tests', 'parameters'));
        self::assertSame([['image' => 'php:<< parameters.php >>-cli']], self::yaml($config, 'jobs', 'tests', 'docker'));
        self::assertSame(
            [Plan::INSTALL_COMMAND, Plan::VALIDATE_COMMAND, 'vendor/bin/phpunit'],
            self::runCommands(self::yaml($config, 'jobs', 'tests', 'steps')),
        );

        self::assertSame(
            [
                'quality',
                ['tests' => ['name' => 'tests-php-<< matrix.php >>', 'matrix' => ['parameters' => ['php' => ['8.3', '8.4']]]]],
            ],
            self::yaml($config, 'workflows', 'ci', 'jobs'),
        );
    }

    public function testAddsCoverageLowestDependenciesAndAudit(): void
    {
        $project = new Project(['8.3', '8.4'], [], [
            new Tool('PHPUnit', ToolType::Tests, 'vendor/bin/phpunit', 'vendor/bin/phpunit --coverage-clover=coverage.xml'),
        ]);

        $config = (new CircleCiProvider())->render($project, new PipelineOptions(['main'], true, true, true, 90.0));

        self::assertSame(
            ['quality', ['tests' => ['name' => 'tests-php-<< matrix.php >>', 'matrix' => ['parameters' => ['php' => ['8.3', '8.4']]]]], 'tests-lowest', 'coverage'],
            self::yaml($config, 'workflows', 'ci', 'jobs'),
        );
        self::assertSame([Plan::INSTALL_COMMAND, 'composer audit'], self::runCommands(self::yaml($config, 'jobs', 'quality', 'steps')));

        self::assertSame([['image' => 'php:8.3-cli']], self::yaml($config, 'jobs', 'tests-lowest', 'docker'));
        // No "composer install" here: the lock file may not be installable on the oldest PHP version.
        self::assertSame(
            [Plan::LOWEST_COMMAND, 'vendor/bin/phpunit'],
            self::runCommands(self::yaml($config, 'jobs', 'tests-lowest', 'steps')),
        );

        $steps = self::yaml($config, 'jobs', 'coverage', 'steps');
        self::assertIsArray($steps);
        self::assertSame(
            [
                ['run' => ['name' => 'Install PCOV', 'command' => 'install-php-extensions pcov']],
                ['run' => ['name' => 'Code coverage (PHPUnit)', 'command' => 'vendor/bin/phpunit --coverage-clover=coverage.xml']],
                ['store_artifacts' => ['path' => 'coverage.xml']],
                ['run' => ['name' => 'Require 90% line coverage', 'command' => Plan::thresholdCommand('90')]],
            ],
            array_slice($steps, -4),
        );
    }

    public function testLintsProjectsWithoutComposer(): void
    {
        $config = (new CircleCiProvider())->render(new Project(['8.4'], usesComposer: false), new PipelineOptions(['main']));

        self::assertSame(
            ['docker' => [['image' => 'php:<< parameters.php >>-cli']], 'parameters' => ['php' => ['type' => 'string']], 'steps' => [
                'checkout',
                ['run' => ['name' => 'Lint PHP files', 'command' => Plan::LINT_COMMAND]],
            ]],
            self::sorted(self::yaml($config, 'jobs', 'tests')),
        );
    }

    /**
     * @return list<mixed> the commands of the "run" steps that follow the PHP setup
     */
    private static function runCommands(mixed $steps): array
    {
        self::assertIsArray($steps);
        $commands = [];

        foreach ($steps as $step) {
            if (is_array($step) && is_array($step['run'] ?? null) && 'Set up PHP' !== $step['run']['name']) {
                $commands[] = $step['run']['command'];
            }
        }

        return $commands;
    }

    /**
     * @return array<mixed>
     */
    private static function sorted(mixed $value): array
    {
        self::assertIsArray($value);
        ksort($value);

        return $value;
    }
}
