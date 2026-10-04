<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Tests\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;
use AxonPHP\Cli\Provider\GitLabCiProvider;
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

        $pipeline = (new GitLabCiProvider())->render($project, ['main', 'release/1.x']);

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
        $pipeline = (new GitLabCiProvider())->render(new Project(['8.4']), ['main']);

        self::assertSame(['test'], self::yaml($pipeline, 'stages'));
        self::assertArrayNotHasKey('quality', (array) self::yaml($pipeline));
        self::assertSame(
            ['composer validate --strict', Yaml::LINT_COMMAND],
            self::yaml($pipeline, 'tests', 'script'),
        );
    }

    public function testSkipsComposerSetupWithoutComposerFile(): void
    {
        $pipeline = (new GitLabCiProvider())->render(new Project(['8.4'], usesComposer: false), ['main']);

        self::assertStringNotContainsString('composer', $pipeline);
        self::assertSame([Yaml::LINT_COMMAND], self::yaml($pipeline, 'tests', 'script'));
    }
}
