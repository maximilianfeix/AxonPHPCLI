<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\BitbucketPipelinesProvider;
use AxonPHP\Cli\Provider\PipelineOptions;
use AxonPHP\Cli\Provider\Yaml;
use AxonPHP\Cli\Tests\ReadsYaml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BitbucketPipelinesProvider::class)]
#[CoversClass(Yaml::class)]
final class BitbucketPipelinesProviderTest extends TestCase
{
    use ReadsYaml;

    public function testDescribesItself(): void
    {
        $provider = new BitbucketPipelinesProvider();

        self::assertSame('bitbucket', $provider->name());
        self::assertSame('Bitbucket Pipelines', $provider->label());
        self::assertSame('bitbucket-pipelines.yml', $provider->path());
    }

    public function testRendersQualityStepFollowedByParallelTestSteps(): void
    {
        $project = new Project(
            ['8.3', '8.4'],
            ['intl'],
            [
                new Tool('PHPUnit', ToolType::Tests, 'vendor/bin/phpunit'),
                new Tool('PHPStan', ToolType::StaticAnalysis, 'vendor/bin/phpstan analyse --no-progress'),
            ],
        );

        $pipeline = (new BitbucketPipelinesProvider())->render($project, new PipelineOptions(['main', 'develop']));

        self::assertStringStartsWith(Yaml::HEADER."\n", $pipeline);
        self::assertSame('php:8.4-cli', self::yaml($pipeline, 'image'));

        $setup = [
            'export COMPOSER_ALLOW_SUPERUSER=1',
            ...Yaml::dockerSetup(['intl']),
            Yaml::INSTALL_COMMAND,
        ];
        $quality = [
            'name' => 'Code quality',
            'image' => 'php:8.4-cli',
            'caches' => ['composer'],
            'script' => [...$setup, 'vendor/bin/phpstan analyse --no-progress'],
        ];
        $tests = static fn (string $version): array => [
            'name' => sprintf('Tests (PHP %s)', $version),
            'image' => sprintf('php:%s-cli', $version),
            'caches' => ['composer'],
            'script' => [...$setup, 'composer validate --strict', 'vendor/bin/phpunit'],
        ];

        // The parser resolves the aliases, so each run contains the full step definitions.
        $run = [
            ['step' => $quality],
            ['parallel' => [['step' => $tests('8.3')], ['step' => $tests('8.4')]]],
        ];

        self::assertSame($run, self::yaml($pipeline, 'pipelines', 'pull-requests', '**'));
        self::assertSame(['main' => $run, 'develop' => $run], self::yaml($pipeline, 'pipelines', 'branches'));
    }

    public function testAddsCoverageLowestDependenciesAndAudit(): void
    {
        $project = new Project(['8.3', '8.4'], [], [
            new Tool('PHPUnit', ToolType::Tests, 'vendor/bin/phpunit', 'vendor/bin/phpunit --coverage-clover=coverage.xml'),
        ]);

        $pipeline = (new BitbucketPipelinesProvider())->render($project, new PipelineOptions(['main'], true, true, true));
        $steps = self::yaml($pipeline, 'definitions', 'steps');

        self::assertIsArray($steps);
        self::assertSame(
            [
                'Code quality',
                'Tests (PHP 8.3)',
                'Tests (PHP 8.4)',
                'Tests (PHP 8.3, lowest dependencies)',
                'Code coverage',
            ],
            array_column(array_column($steps, 'step'), 'name'),
        );
        self::assertSame('composer audit', self::yaml($pipeline, 'definitions', 'steps', 0, 'step', 'script', 5));
        // The lowest step resolves its own dependencies instead of installing from the lock file first.
        self::assertSame(
            ['export COMPOSER_ALLOW_SUPERUSER=1', ...Yaml::dockerSetup([]), Yaml::LOWEST_COMMAND, 'vendor/bin/phpunit'],
            self::yaml($pipeline, 'definitions', 'steps', 3, 'step', 'script'),
        );
        self::assertSame(
            ['install-php-extensions pcov', 'vendor/bin/phpunit --coverage-clover=coverage.xml'],
            array_slice((array) self::yaml($pipeline, 'definitions', 'steps', 4, 'step', 'script'), 5),
        );
        self::assertCount(4, (array) self::yaml($pipeline, 'pipelines', 'branches', 'main', 1, 'parallel'));
    }

    public function testRunsASingleStepWithoutParallelBlockOrComposer(): void
    {
        $pipeline = (new BitbucketPipelinesProvider())->render(
            new Project(['8.4'], usesComposer: false),
            new PipelineOptions(['main'], true, true, true),
        );

        self::assertStringNotContainsString('composer', $pipeline);
        self::assertSame(
            [['step' => ['name' => 'Tests (PHP 8.4)', 'image' => 'php:8.4-cli', 'script' => [Yaml::LINT_COMMAND]]]],
            self::yaml($pipeline, 'pipelines', 'branches', 'main'),
        );
    }
}
