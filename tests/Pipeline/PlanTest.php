<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Pipeline;

use AxonPHP\Cli\Pipeline\Plan;
use AxonPHP\Cli\Pipeline\Step;
use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolCatalog;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\PipelineOptions;
use AxonPHP\Cli\Provider\ProviderRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(Plan::class)]
#[CoversClass(Step::class)]
#[CoversClass(Tool::class)]
#[CoversClass(PipelineOptions::class)]
final class PlanTest extends TestCase
{
    public function testPutsEveryCheckThatRunsOnceIntoTheQualityJob(): void
    {
        $project = new Project(['8.3', '8.5'], ['intl'], (new ToolCatalog())->detect([
            'phpunit/phpunit',
            'phpstan/phpstan',
            'laravel/pint',
            'icanhazstring/composer-unused',
        ]));

        $plan = Plan::from($project, new PipelineOptions(['main', 'develop'], audit: true, lowest: true));

        self::assertSame(['main', 'develop'], $plan->branches);
        self::assertSame(['8.3', '8.5'], $plan->phpVersions);
        self::assertSame('8.5', $plan->latestPhp);
        self::assertSame('8.3', $plan->oldestPhp);
        self::assertSame(['intl'], $plan->extensions);
        self::assertTrue($plan->lowest);
        self::assertSame(
            ['Security audit (Composer)', 'Static analysis (PHPStan)', 'Code style (Pint)', 'Dependencies (Composer Unused)'],
            array_map(static fn (Step $step): string => $step->name, $plan->quality),
        );
        self::assertSame(['vendor/bin/phpunit'], Plan::commands($plan->tests));
        self::assertNull($plan->coverage);
        self::assertNull($plan->coverageThreshold);
    }

    public function testLintsWhenTheProjectHasNoTestRunnerAndSkipsComposerOnlyJobsWithoutComposer(): void
    {
        $plan = Plan::from(new Project(['8.4'], usesComposer: false), new PipelineOptions(coverage: true, lowest: true, audit: true));

        self::assertSame([], $plan->quality);
        self::assertSame([Plan::LINT_COMMAND], Plan::commands($plan->tests));
        self::assertFalse($plan->lowest);
        self::assertNull($plan->coverage);
    }

    public function testChecksTheCloverReportWhenTheRunnerCannotEnforceAMinimum(): void
    {
        $project = new Project(['8.4'], [], (new ToolCatalog())->detect(['phpunit/phpunit']));

        $plan = Plan::from($project, new PipelineOptions(coverage: true, minCoverage: 80.0));

        self::assertNotNull($plan->coverage);
        self::assertSame('vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml', $plan->coverage->command);
        self::assertNotNull($plan->coverageThreshold);
        self::assertSame('Require 80% line coverage', $plan->coverageThreshold->name);
        self::assertSame(Plan::thresholdCommand('80'), $plan->coverageThreshold->command);
    }

    public function testLetsPestEnforceTheMinimumItself(): void
    {
        $project = new Project(['8.4'], [], (new ToolCatalog())->detect(['pestphp/pest']));

        $plan = Plan::from($project, new PipelineOptions(coverage: true, minCoverage: 92.5));

        self::assertNotNull($plan->coverage);
        self::assertSame('vendor/bin/pest --coverage --coverage-clover=coverage.xml --min=92.5', $plan->coverage->command);
        self::assertNull($plan->coverageThreshold);
    }

    public function testAMinimumWithoutCoverageHasNoEffect(): void
    {
        $project = new Project(['8.4'], [], [new Tool('PHPUnit', ToolType::Tests, 'phpunit', 'phpunit --coverage-clover=coverage.xml')]);

        $plan = Plan::from($project, new PipelineOptions(minCoverage: 80.0));

        self::assertNull($plan->coverage);
        self::assertNull($plan->coverageThreshold);
    }

    public function testFormatsPercentagesWithoutTrailingZeros(): void
    {
        self::assertSame('100', Plan::percentage(100.0));
        self::assertSame('92.5', Plan::percentage(92.5));
        self::assertSame('0', Plan::percentage(0.0));
        self::assertSame('66.67', Plan::percentage(66.666));
    }

    /**
     * The command sits in the pipelines as a plain YAML scalar, in a mapping and in a list.
     */
    public function testTheThresholdCommandSurvivesEveryProvidersYaml(): void
    {
        $command = Plan::thresholdCommand('87.5');
        $project = new Project(['8.4'], [], (new ToolCatalog())->detect(['phpunit/phpunit']));
        $options = new PipelineOptions(coverage: true, minCoverage: 87.5);

        self::assertSame(['run' => $command], Yaml::parse('run: '.$command));
        self::assertSame([$command], Yaml::parse('- '.$command));

        foreach (ProviderRegistry::default()->all() as $provider) {
            $pipeline = $provider->render($project, $options);

            $parsed = Yaml::parse($provider->render($project, $options));
            self::assertIsArray($parsed);

            $values = [];
            array_walk_recursive($parsed, static function (mixed $value) use (&$values): void {
                $values[] = $value;
            });

            self::assertContains($command, $values, $provider->name());
        }
    }

    #[RequiresOperatingSystemFamily('Linux')]
    public function testTheThresholdCommandPassesAndFailsOnARealReport(): void
    {
        $directory = sys_get_temp_dir().'/axonphp-threshold-'.bin2hex(random_bytes(4));
        mkdir($directory);
        file_put_contents(
            $directory.'/coverage.xml',
            '<coverage><project><metrics statements="200" coveredstatements="180"/></project></coverage>',
        );

        try {
            exec(sprintf('cd %s && %s 2>&1', escapeshellarg($directory), Plan::thresholdCommand('90')), $output, $exitCode);
            self::assertSame(0, $exitCode, implode("\n", $output));
            self::assertSame(['Line coverage 90.00%, required 90%'], $output);

            exec(sprintf('cd %s && %s 2>&1', escapeshellarg($directory), Plan::thresholdCommand('90.5')), $output, $exitCode);
            self::assertSame(1, $exitCode);
        } finally {
            unlink($directory.'/coverage.xml');
            rmdir($directory);
        }
    }
}
