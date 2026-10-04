<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Provider;

use AxonPHP\Cli\Project\Project;
use AxonPHP\Cli\Project\Tool;
use AxonPHP\Cli\Project\ToolType;

final class GitLabCiProvider implements Provider
{
    private const EXTENSION_INSTALLER = 'https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions';

    public function name(): string
    {
        return 'gitlab';
    }

    public function label(): string
    {
        return 'GitLab CI';
    }

    public function path(): string
    {
        return '.gitlab-ci.yml';
    }

    public function render(Project $project, array $branches): string
    {
        $qualityTools = $project->tools(ToolType::CodeStyle, ToolType::StaticAnalysis);

        $sections = [
            Yaml::HEADER,
            $this->stages([] !== $qualityTools),
            $this->workflow($branches),
        ];

        if ($project->usesComposer) {
            $sections[] = $this->composerDefaults($project);
        }

        if ([] !== $qualityTools) {
            $sections[] = $this->qualityJob($project, $qualityTools);
        }

        $sections[] = $this->testsJob($project);

        return implode("\n\n", $sections)."\n";
    }

    private function stages(bool $withQuality): string
    {
        $lines = ['stages:'];

        if ($withQuality) {
            $lines[] = '  - quality';
        }

        $lines[] = '  - test';

        return implode("\n", $lines);
    }

    /**
     * Runs merge request pipelines plus branch pipelines for the given branches,
     * so a push to a branch with an open merge request does not run twice.
     *
     * @param list<string> $branches
     */
    private function workflow(array $branches): string
    {
        $lines = [
            'workflow:',
            '  rules:',
            "    - if: \$CI_PIPELINE_SOURCE == 'merge_request_event'",
            '    - if: $CI_COMMIT_TAG',
        ];

        foreach ($branches as $branch) {
            $lines[] = '    - if: $CI_COMMIT_BRANCH == '.Yaml::quote($branch);
        }

        return implode("\n", $lines);
    }

    private function composerDefaults(Project $project): string
    {
        $extensions = implode(' ', ['@composer', 'zip', ...$project->extensions]);

        return implode("\n", [
            'variables:',
            '  COMPOSER_ALLOW_SUPERUSER: '.Yaml::quote('1'),
            '  COMPOSER_NO_INTERACTION: '.Yaml::quote('1'),
            '  COMPOSER_CACHE_DIR: $CI_PROJECT_DIR/.composer-cache',
            '',
            'default:',
            '  cache:',
            '    key:',
            '      files:',
            '        - composer.lock',
            '        - composer.json',
            '    paths:',
            '      - .composer-cache/',
            '  before_script:',
            '    - curl -sSLf -o /usr/local/bin/install-php-extensions '.self::EXTENSION_INSTALLER,
            '    - chmod +x /usr/local/bin/install-php-extensions',
            '    - install-php-extensions '.$extensions,
            '    - composer install --prefer-dist --no-progress',
        ]);
    }

    /**
     * @param non-empty-list<Tool> $tools
     */
    private function qualityJob(Project $project, array $tools): string
    {
        $lines = [
            'quality:',
            '  stage: quality',
            sprintf('  image: php:%s-cli', $project->latestPhpVersion()),
            '  script:',
        ];

        foreach ($tools as $tool) {
            $lines[] = '    - '.$tool->command;
        }

        return implode("\n", $lines);
    }

    private function testsJob(Project $project): string
    {
        $lines = [
            'tests:',
            '  stage: test',
            '  image: php:${PHP_VERSION}-cli',
            '  parallel:',
            '    matrix:',
            '      - PHP_VERSION: '.Yaml::inlineList($project->phpVersions),
            '  script:',
        ];

        if ($project->usesComposer) {
            $lines[] = '    - composer validate --strict';
        }

        $tools = $project->tools(ToolType::Tests);

        foreach ($tools as $tool) {
            $lines[] = '    - '.$tool->command;
        }

        if ([] === $tools) {
            $lines[] = '    - '.Yaml::LINT_COMMAND;
        }

        return implode("\n", $lines);
    }
}
