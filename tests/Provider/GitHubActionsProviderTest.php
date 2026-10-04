<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\GitHubActionsProvider;
use AxonPHP\Cli\Provider\Yaml;
use AxonPHP\Cli\Tests\ReadsYaml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitHubActionsProvider::class)]
#[CoversClass(Yaml::class)]
final class GitHubActionsProviderTest extends TestCase
{
    use ReadsYaml;

    public function testDescribesItself(): void
    {
        $provider = new GitHubActionsProvider();

        self::assertSame('github', $provider->name());
        self::assertSame('GitHub Actions', $provider->label());
        self::assertSame('.github/workflows/ci.yml', $provider->path());
    }

    public function testRendersQualityAndTestJobs(): void
    {
        $project = new Project(
            ['8.3', '8.4'],
            ['intl', 'pdo'],
            [
                new Tool('PHPUnit', ToolType::Tests, 'vendor/bin/phpunit'),
                new Tool('PHPStan', ToolType::StaticAnalysis, 'vendor/bin/phpstan analyse --no-progress'),
                new Tool('PHP-CS-Fixer', ToolType::CodeStyle, 'vendor/bin/php-cs-fixer check --diff'),
            ],
        );

        $workflow = (new GitHubActionsProvider())->render($project, ['main', 'develop']);

        self::assertStringStartsWith(Yaml::HEADER."\n", $workflow);
        self::assertSame('CI', self::yaml($workflow, 'name'));
        self::assertSame(
            ['push' => ['branches' => ['main', 'develop']], 'pull_request' => null],
            self::yaml($workflow, 'on'),
        );
        self::assertSame(['contents' => 'read'], self::yaml($workflow, 'permissions'));
        self::assertSame(
            ['group' => '${{ github.workflow }}-${{ github.ref }}', 'cancel-in-progress' => true],
            self::yaml($workflow, 'concurrency'),
        );

        self::assertSame(
            ['php-version' => '8.4', 'extensions' => 'intl, pdo', 'coverage' => 'none'],
            self::yaml($workflow, 'jobs', 'quality', 'steps', 1, 'with'),
        );
        self::assertSame(
            ['vendor/bin/phpstan analyse --no-progress', 'vendor/bin/php-cs-fixer check --diff'],
            self::commands(self::yaml($workflow, 'jobs', 'quality', 'steps')),
        );

        self::assertSame(
            ['fail-fast' => false, 'matrix' => ['php' => ['8.3', '8.4']]],
            self::yaml($workflow, 'jobs', 'tests', 'strategy'),
        );
        self::assertSame(
            '${{ matrix.php }}',
            self::yaml($workflow, 'jobs', 'tests', 'steps', 1, 'with', 'php-version'),
        );

        $steps = self::yaml($workflow, 'jobs', 'tests', 'steps');
        self::assertIsArray($steps);
        self::assertSame(['composer validate --strict', 'vendor/bin/phpunit'], self::commands($steps));
        self::assertSame(
            ['actions/checkout@v7', 'shivammathur/setup-php@v2', 'ramsey/composer-install@v4'],
            array_column($steps, 'uses'),
        );
    }

    public function testFallsBackToLintingWhenNoToolsAreInstalled(): void
    {
        $workflow = (new GitHubActionsProvider())->render(new Project(['8.4']), ['main']);

        self::assertSame(['tests'], array_keys((array) self::yaml($workflow, 'jobs')));
        self::assertSame(
            ['composer validate --strict', Yaml::LINT_COMMAND],
            self::commands(self::yaml($workflow, 'jobs', 'tests', 'steps')),
        );
        self::assertSame(
            ['php-version' => '${{ matrix.php }}', 'coverage' => 'none'],
            self::yaml($workflow, 'jobs', 'tests', 'steps', 1, 'with'),
        );
    }

    public function testSkipsComposerStepsWithoutComposerFile(): void
    {
        $workflow = (new GitHubActionsProvider())->render(new Project(['8.4'], usesComposer: false), ['main']);

        self::assertStringNotContainsString('composer', $workflow);
        self::assertSame([Yaml::LINT_COMMAND], self::commands(self::yaml($workflow, 'jobs', 'tests', 'steps')));
    }
}
