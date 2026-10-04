<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\GitLabCiProvider;
use AxonPHP\Cli\Provider\PipelineOptions;
use AxonPHP\Cli\Provider\Yaml;
use AxonPHP\Cli\Tests\ReadsYaml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitLabCiProvider::class)]
#[CoversClass(Yaml::class)]
final class GitLabCiProviderTest extends TestCase
{
    use ReadsYaml;

    public function testDescribesItself(): void
    {
        $provider = new GitLabCiProvider();

        self::assertSame('gitlab', $provider->name());
        self::assertSame('GitLab CI', $provider->label());
        self::assertSame('.gitlab-ci.yml', $provider->path());
    }

    public function testRendersQualityAndTestJobs(): void
    {
        $project = new Project(
            ['8.3', '8.4'],
            ['intl'],
            [
                new Tool('Pest', ToolType::Tests, 'vendor/bin/pest'),
                new Tool('Psalm', ToolType::StaticAnalysis, 'vendor/bin/psalm --no-progress'),
            ],
        );

        $pipeline = (new GitLabCiProvider())->render($project, new PipelineOptions(['main', 'release/1.x']));

        self::assertStringStartsWith(Yaml::HEADER."\n", $pipeline);
        self::assertSame(['quality', 'test'], self::yaml($pipeline, 'stages'));
        self::assertSame(
            [
                ['if' => "\$CI_PIPELINE_SOURCE == 'merge_request_event'"],
                ['if' => '$CI_COMMIT_TAG'],
                ['if' => "\$CI_COMMIT_BRANCH == 'main'"],
                ['if' => "\$CI_COMMIT_BRANCH == 'release/1.x'"],
            ],
            self::yaml($pipeline, 'workflow', 'rules'),
        );
        self::assertSame(
            'install-php-extensions @composer zip intl',
            self::yaml($pipeline, 'default', 'before_script', 2),
        );
        self::assertSame(['.composer-cache/'], self::yaml($pipeline, 'default', 'cache', 'paths'));
        self::assertSame(
            ['stage' => 'quality', 'image' => 'php:8.4-cli', 'script' => ['vendor/bin/psalm --no-progress']],
            self::yaml($pipeline, 'quality'),
        );
        self::assertSame(
            [
                'stage' => 'test',
                'image' => 'php:${PHP_VERSION}-cli',
                'parallel' => ['matrix' => [['PHP_VERSION' => ['8.3', '8.4']]]],
                'script' => ['composer validate --strict', 'vendor/bin/pest'],
            ],
            self::yaml($pipeline, 'tests'),
        );
    }

    public function testOmitsTheQualityStageWithoutQualityTools(): void
    {
        $pipeline = (new GitLabCiProvider())->render(new Project(['8.4']), new PipelineOptions(['main']));

        self::assertSame(['test'], self::yaml($pipeline, 'stages'));
        self::assertArrayNotHasKey('quality', (array) self::yaml($pipeline));
        self::assertSame(
            ['composer validate --strict', Yaml::LINT_COMMAND],
            self::yaml($pipeline, 'tests', 'script'),
        );
    }

    public function testSkipsComposerSetupWithoutComposerFile(): void
    {
        $pipeline = (new GitLabCiProvider())->render(new Project(['8.4'], usesComposer: false), new PipelineOptions(['main']));

        self::assertStringNotContainsString('composer', $pipeline);
        self::assertSame([Yaml::LINT_COMMAND], self::yaml($pipeline, 'tests', 'script'));
    }

    public function testAddsCoverageLowestDependenciesAndAudit(): void
    {
        $project = new Project(['8.3', '8.4'], [], [
            new Tool('PHPUnit', ToolType::Tests, 'vendor/bin/phpunit', 'vendor/bin/phpunit --coverage-clover=coverage.xml'),
        ]);

        $pipeline = (new GitLabCiProvider())->render($project, new PipelineOptions(['main'], true, true, true));

        self::assertSame(['quality', 'test'], self::yaml($pipeline, 'stages'));
        self::assertSame(['composer audit'], self::yaml($pipeline, 'quality', 'script'));
        self::assertSame(
            [
                'stage' => 'test',
                'image' => 'php:8.3-cli',
                'script' => [Yaml::LOWEST_COMMAND, 'vendor/bin/phpunit'],
            ],
            self::yaml($pipeline, 'tests:lowest'),
        );
        self::assertSame(
            [
                'stage' => 'test',
                'image' => 'php:8.4-cli',
                'script' => ['install-php-extensions pcov', 'vendor/bin/phpunit --coverage-clover=coverage.xml'],
                'coverage' => '/^\s*(?:Lines|Total):\s*\d+\.\d+\s*%/',
                'artifacts' => ['paths' => ['coverage.xml']],
            ],
            self::yaml($pipeline, 'coverage'),
        );
    }

    public function testCoveragePatternMatchesTheSummaryOfPhpUnitAndPest(): void
    {
        $project = new Project(['8.4'], [], [new Tool('PHPUnit', ToolType::Tests, 'phpunit', 'phpunit --coverage-text')]);
        $pattern = self::yaml((new GitLabCiProvider())->render($project, new PipelineOptions(coverage: true)), 'coverage', 'coverage');

        self::assertIsString($pattern);
        self::assertNotSame('', $pattern);
        self::assertSame(1, preg_match($pattern.'m', "Summary:\n  Lines:   91.30% (42/46)\n"));
        self::assertSame(1, preg_match($pattern.'m', "  Total: 91.3 %\n"));
        self::assertSame(0, preg_match($pattern.'m', "  Classes: 80.00% (4/5)\n"));
    }
}
